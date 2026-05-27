<?php
/**
 * Booking state-machine service.
 *
 * Every status change goes through transition() — no class outside this one
 * writes the status column directly. Side-effects (inventory, coupon void,
 * sync-queue, WP action hooks) are computed deterministically from (from, to).
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

use DomainException;
use InvalidArgumentException;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;

/**
 * Booking status service.
 */
class BookingStatusService {

	/**
	 * Allowed transition map: from → [ allowed to statuses ].
	 *
	 * @var array<string, string[]>
	 */
	private const TRANSITION_MAP = array(
		'ea-pending'    => array( 'ea-processing', 'ea-completed', 'ea-cancelled', 'ea-failed', 'ea-expired' ),
		'ea-processing' => array( 'ea-completed', 'ea-cancelled', 'ea-failed', 'ea-expired' ),
		'ea-completed'  => array( 'ea-refunded' ),
		// Terminal — no outgoing transitions.
		'ea-cancelled'  => array(),
		'ea-failed'     => array(),
		'ea-expired'    => array(),
		'ea-refunded'   => array(),
	);

	/**
	 * Terminal booking statuses that release coupons when entered
	 * from a non-completed state.
	 *
	 * @var string[]
	 */
	private const COUPON_VOID_TARGETS = array( 'ea-cancelled', 'ea-failed', 'ea-expired' );

	/**
	 * Payment statuses that indicate sync is complete.
	 *
	 * @var string[]
	 */
	private const SYNC_DONE_PAYMENT_STATUSES = array( 'paid', 'failed', 'cancelled', 'refunded' );

	/** @var BookingRepository */
	private BookingRepository $bookings;

	/** @var EventInventoryRepository */
	private EventInventoryRepository $inventory;

	/** @var CouponService */
	private CouponService $coupons;

	/** @var PaymentSyncQueueRepository */
	private PaymentSyncQueueRepository $sync_queue;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository          $bookings   Booking repository.
	 * @param EventInventoryRepository   $inventory  Inventory repository.
	 * @param CouponService              $coupons    Coupon service.
	 * @param PaymentSyncQueueRepository $sync_queue Sync queue repository.
	 */
	public function __construct(
		BookingRepository $bookings,
		EventInventoryRepository $inventory,
		CouponService $coupons,
		PaymentSyncQueueRepository $sync_queue
	) {
		$this->bookings   = $bookings;
		$this->inventory  = $inventory;
		$this->coupons    = $coupons;
		$this->sync_queue = $sync_queue;
	}

	/**
	 * Transition a booking to a new status.
	 *
	 * Enforces the state machine, applies inventory effects, voids coupons
	 * on early-terminal states, manages the sync queue, and fires action hooks.
	 *
	 * @param int    $booking_id     Booking ID.
	 * @param string $to_status      Target booking status.
	 * @param string $payment_status Target payment status.
	 * @param string $reason         Reason for the transition.
	 *
	 * @return bool True on success, false if booking not found.
	 *
	 * @throws InvalidArgumentException If status values are invalid.
	 * @throws DomainException          If the transition is not allowed.
	 */
	public function transition( int $booking_id, string $to_status, string $payment_status, string $reason = '' ): bool {
		// 1. Load.
		$booking = $this->bookings->find( $booking_id );
		if ( null === $booking ) {
			return false;
		}

		// 2. Validate to_status.
		if ( ! in_array( $to_status, BookingTableModel::ALLOWED_STATUSES, true ) ) {
			throw new InvalidArgumentException( "invalid_status:{$to_status}" );
		}

		// 3. Validate payment_status.
		if ( ! in_array( $payment_status, BookingTableModel::ALLOWED_PAYMENT_STATUSES, true ) ) {
			throw new InvalidArgumentException( "invalid_payment_status:{$payment_status}" );
		}

		// 4. Check allowed transition.
		$from = $booking->get_status();

		// Idempotent no-op.
		if ( $from === $to_status && $booking->get_payment_status() === $payment_status ) {
			return true;
		}

		// Validate transition is allowed.
		if ( $from !== $to_status ) {
			$allowed = self::TRANSITION_MAP[ $from ] ?? array();
			if ( ! in_array( $to_status, $allowed, true ) ) {
				throw new DomainException( "illegal_transition:{$from}->{$to_status}" );
			}
		}

		// 5-6. Compute and apply inventory effect.
		$this->adjust_inventory_for_transition( $booking, $from, $to_status );

		// 7. Persist booking.
		$this->bookings->update(
			$booking_id,
			array(
				'status'         => $to_status,
				'payment_status' => $payment_status,
			)
		);

		// 8. Sync-queue maintenance.
		if ( in_array( $payment_status, self::SYNC_DONE_PAYMENT_STATUSES, true ) ) {
			$this->sync_queue->remove( $booking_id );
		}

		// 9. Coupon void on early-terminal (not from completed).
		if ( in_array( $to_status, self::COUPON_VOID_TARGETS, true ) && 'ea-completed' !== $from ) {
			$this->coupons->void_usage( $booking_id, $reason );
		}

		// 10. Fire hooks.
		do_action( 'wpems_booking_status_changed', $booking_id, $from, $to_status, $reason );
		do_action( "wpems_booking_status_{$to_status}", $booking_id, $reason );

		return true;
	}

	/**
	 * Compute and apply inventory adjustment for a status transition.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param string            $from    Current status.
	 * @param string            $to      Target status.
	 *
	 * @return void
	 */
	private function adjust_inventory_for_transition( BookingTableModel $booking, string $from, string $to ): void {
		// Same status → no inventory change.
		if ( $from === $to ) {
			return;
		}

		$event_id = $booking->get_event_id();
		$qty      = $booking->get_qty();

		// Pending/processing → completed: confirm hold.
		if ( in_array( $from, array( 'ea-pending', 'ea-processing' ), true ) && 'ea-completed' === $to ) {
			$this->inventory->confirm_hold( $event_id, $qty );
			return;
		}

		// Pending/processing → cancelled/failed/expired: release hold.
		if ( in_array( $from, array( 'ea-pending', 'ea-processing' ), true )
			&& in_array( $to, array( 'ea-cancelled', 'ea-failed', 'ea-expired' ), true )
		) {
			$this->inventory->release_hold( $event_id, $qty );
			return;
		}

		// Completed → refunded: release confirmed.
		if ( 'ea-completed' === $from && 'ea-refunded' === $to ) {
			$this->inventory->release_confirmed( $event_id, $qty );
			return;
		}

		// Pending → processing: noop (hold was taken at create time).
	}

	/**
	 * Mark a booking as completed (paid).
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $reason     Reason for the transition.
	 *
	 * @return bool
	 */
	public function mark_completed( int $booking_id, string $reason = '' ): bool {
		return $this->transition( $booking_id, 'ea-completed', 'paid', $reason );
	}

	/**
	 * Mark a booking as cancelled.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $reason     Reason for the transition.
	 *
	 * @return bool
	 */
	public function mark_cancelled( int $booking_id, string $reason = '' ): bool {
		return $this->transition( $booking_id, 'ea-cancelled', 'cancelled', $reason );
	}

	/**
	 * Mark a booking as failed.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $reason     Reason for the transition.
	 *
	 * @return bool
	 */
	public function mark_failed( int $booking_id, string $reason = '' ): bool {
		return $this->transition( $booking_id, 'ea-failed', 'failed', $reason );
	}

	/**
	 * Mark a booking as expired.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $reason     Reason for the transition.
	 *
	 * @return bool
	 */
	public function mark_expired( int $booking_id, string $reason = '' ): bool {
		return $this->transition( $booking_id, 'ea-expired', 'unpaid', $reason );
	}
}
