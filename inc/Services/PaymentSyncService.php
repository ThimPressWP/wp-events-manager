<?php
/**
 * Payment synchronisation service.
 *
 * Single funnel for every payment-status check (return page, admin
 * "Check payment status", cron polling). Handles idempotency, gateway
 * dispatch, transaction-ledger writes, booking transition, sync-queue
 * maintenance, and backoff scheduling.
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

use DateTimeImmutable;
use DateTimeZone;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentEventRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;
use WPEMS\Repositories\PaymentTransactionRepository;

/**
 * Payment sync service.
 */
class PaymentSyncService {

	/** @var self|null */
	private static ?self $instance = null;

	/** @var BookingRepository */
	private BookingRepository $bookings;

	/** @var PaymentTransactionRepository */
	private PaymentTransactionRepository $txns;

	/** @var PaymentEventRepository */
	private PaymentEventRepository $events;

	/** @var PaymentSyncQueueRepository */
	private PaymentSyncQueueRepository $queue;

	/** @var BookingStatusService */
	private BookingStatusService $status;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository            $bookings Booking repository.
	 * @param PaymentTransactionRepository $txns     Transaction repository.
	 * @param PaymentEventRepository       $events   Payment event repository.
	 * @param PaymentSyncQueueRepository   $queue    Sync queue repository.
	 * @param BookingStatusService         $status   Booking status service.
	 */
	public function __construct(
		BookingRepository $bookings,
		PaymentTransactionRepository $txns,
		PaymentEventRepository $events,
		PaymentSyncQueueRepository $queue,
		BookingStatusService $status
	) {
		$this->bookings = $bookings;
		$this->txns     = $txns;
		$this->events   = $events;
		$this->queue    = $queue;
		$this->status   = $status;
	}

	/**
	 * Get the default runtime instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			$bookings = new BookingRepository();
			$queue    = new PaymentSyncQueueRepository();
			$coupons  = new CouponService(
				new CouponRepository(),
				new CouponEventRepository(),
				new CouponUsageRepository()
			);
			$status   = new BookingStatusService(
				$bookings,
				new EventInventoryRepository(),
				$coupons,
				$queue
			);

			self::$instance = new self(
				$bookings,
				new PaymentTransactionRepository(),
				new PaymentEventRepository(),
				$queue,
				$status
			);
		}

		return self::$instance;
	}

	/**
	 * Sync a single booking's payment status.
	 *
	 * Phase 9 will add real gateway dispatch here; for now, returns unknown
	 * for syncable bookings since no gateway registry is wired.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $source     One of: return, webhook, admin, cron, manual.
	 *
	 * @return PaymentResult
	 */
	public function sync_booking( int $booking_id, string $source = 'manual' ): PaymentResult {
		$booking = $this->bookings->find( $booking_id );

		if ( null === $booking ) {
			return PaymentResult::unknown( 0, 'booking_not_found' );
		}

		if ( ! $this->is_syncable( $booking ) ) {
			return PaymentResult::unknown( $booking_id, 'not_syncable' );
		}

		// Phase 9: $gateway = $this->gateways->get( $booking->get_payment_method() );
		// Phase 9: $result = $gateway->sync_payment_status( $booking, $source );
		// Phase 9: return $this->apply_result( $booking, $result, $source );

		return PaymentResult::unknown( $booking_id, 'gateway_not_wired' );
	}

	/**
	 * Sync all due bookings from the sync queue (cron entry point).
	 *
	 * @param int $limit Maximum number of rows to process.
	 *
	 * @return PaymentResult[] Results for each processed booking.
	 */
	public function sync_due_bookings( int $limit = 20 ): array {
		$rows    = $this->queue->dequeue_due( $limit );
		$results = array();

		foreach ( $rows as $row ) {
			$results[] = $this->sync_booking( (int) $row['booking_id'], 'cron' );
		}

		return $results;
	}

	/**
	 * Apply a payment result to a booking (the idempotency boundary).
	 *
	 * This is the single point where payment outcomes mutate booking state.
	 * All sync sources (return, webhook, admin, cron) converge here.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param PaymentResult     $result  Payment result from gateway.
	 * @param string            $source  Sync source identifier.
	 *
	 * @return PaymentResult The same result, passed through.
	 */
	public function apply_result( BookingTableModel $booking, PaymentResult $result, string $source ): PaymentResult {
		// 1. Idempotency guard.
		if ( null !== $result->get_gateway_event_id() ) {
			if ( $this->events->was_processed( $booking->get_payment_method(), $result->get_gateway_event_id() ) ) {
				return $result;
			}
		}

		// 2. Record event (record FIRST, then mutate state).
		$this->events->record(
			array(
				'gateway_id' => $booking->get_payment_method(),
				'event_id'   => $result->get_gateway_event_id() ?? ( 'manual-' . $this->generate_uuid() ),
				'event_type' => $source,
				'booking_id' => $booking->get_id(),
				'status'     => 'processed',
			)
		);

		// 3. Branch on status.
		$booking_id = $booking->get_id();

		switch ( $result->get_status() ) {
			case PaymentResult::STATUS_PAID:
				$this->txns->insert( $result->get_transaction_data() );
				$this->status->mark_completed( $booking_id, "paid_via_{$source}" );
				$this->queue->remove( $booking_id );
				break;

			case PaymentResult::STATUS_FAILED:
				$this->status->mark_failed( $booking_id, $result->get_message() );
				$this->queue->remove( $booking_id );
				break;

			case PaymentResult::STATUS_CANCELLED:
				$this->status->mark_cancelled( $booking_id, $result->get_message() );
				$this->queue->remove( $booking_id );
				break;

			case PaymentResult::STATUS_PENDING:
				$this->schedule_next_sync( $booking, $result );
				break;

			case PaymentResult::STATUS_UNKNOWN:
				$this->schedule_next_sync( $booking, $result );
				break;

			case PaymentResult::STATUS_REFUNDED:
				$txn_data = $result->get_transaction_data();
				if ( ! empty( $txn_data['parent_transaction_id'] ) ) {
					$this->txns->record_refund(
						(int) $txn_data['parent_transaction_id'],
						$txn_data['amount'] ?? '0',
						$txn_data['gateway_transaction_id'] ?? '',
						$txn_data['raw_response'] ?? array()
					);
				}
				$this->status->transition( $booking_id, 'ea-refunded', 'refunded', $result->get_message() );
				$this->queue->remove( $booking_id );
				break;
		}

		return $result;
	}

	/**
	 * Schedule the next sync attempt with backoff.
	 *
	 * Attempts 1-3: 5-minute backoff. Attempts 4+: 15-minute backoff.
	 * If max attempts exceeded, marks the booking as failed.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param PaymentResult     $result  Payment result for error message.
	 *
	 * @return void
	 */
	public function schedule_next_sync( BookingTableModel $booking, PaymentResult $result ): void {
		$row      = $this->queue->get( $booking->get_id() );
		$attempts = (int) ( $row['sync_attempts'] ?? 0 ) + 1;
		$minutes  = $attempts <= 3 ? 5 : 15;
		$max      = $this->get_max_sync_attempts();

		if ( $attempts >= $max ) {
			$this->status->mark_failed( $booking->get_id(), 'sync_max_attempts_exceeded' );
			$this->queue->remove( $booking->get_id() );
			return;
		}

		$next = new DateTimeImmutable( "+{$minutes} minutes", new DateTimeZone( 'UTC' ) );

		$this->queue->update_attempt(
			$booking->get_id(),
			$next,
			'' !== $result->get_message() ? $result->get_message() : null
		);
	}

	/**
	 * Record a sync error without transitioning the booking.
	 *
	 * Transient errors only update the queue row — they do NOT mark the
	 * booking as failed. Only apply_result() does that when the gateway
	 * explicitly returns FAILED.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param string            $message Error message.
	 *
	 * @return void
	 */
	public function record_sync_error( BookingTableModel $booking, string $message ): void {
		$this->queue->update_attempt(
			$booking->get_id(),
			new DateTimeImmutable( '+5 minutes', new DateTimeZone( 'UTC' ) ),
			$message
		);
	}

	/**
	 * Whether a booking is eligible for payment sync.
	 *
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return bool
	 */
	public function is_syncable( BookingTableModel $booking ): bool {
		return $booking->requires_payment_sync();
	}

	/**
	 * Get the max sync attempts setting.
	 *
	 * Overridden in tests to avoid static SettingsManager dependency.
	 *
	 * @return int
	 */
	protected function get_max_sync_attempts(): int {
		return (int) \WPEMS\Admin\SettingsManager::get_option( 'paypal_sync_max_attempts', 24 );
	}

	/**
	 * Generate a UUID v4.
	 *
	 * Overridden in tests for deterministic output.
	 *
	 * @return string
	 */
	protected function generate_uuid(): string {
		return wp_generate_uuid4();
	}
}
