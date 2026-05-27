<?php
/**
 * Booking checkout service.
 *
 * Single entry point for "user clicked Book". Reserves inventory, atomically
 * commits coupon usage, inserts the booking row, and dispatches to the
 * appropriate flow (free / offline / online gateway).
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

use WPEMS\Repositories\BookingMetaRepository;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;

/**
 * Booking checkout service.
 */
class BookingCheckoutService {

	/**
	 * Hold expiry for online gateways (seconds).
	 *
	 * @var int
	 */
	private const HOLD_ONLINE_SECONDS = 1800; // 30 minutes.

	/**
	 * Hold expiry for offline gateways (seconds).
	 *
	 * @var int
	 */
	private const HOLD_OFFLINE_SECONDS = 172800; // 48 hours.

	/**
	 * Online payment methods that require redirect + sync.
	 *
	 * @var string[]
	 */
	private const ONLINE_METHODS = array( 'paypal', 'stripe' );

	/** @var BookingRepository */
	private BookingRepository $bookings;

	/** @var EventInventoryRepository */
	private EventInventoryRepository $inventory;

	/** @var CheckoutQuoteService */
	private CheckoutQuoteService $quotes;

	/** @var CouponService */
	private CouponService $coupons;

	/** @var CouponRepository */
	private CouponRepository $coupon_repo;

	/** @var BookingStatusService */
	private BookingStatusService $status;

	/** @var PaymentSyncQueueRepository */
	private PaymentSyncQueueRepository $sync_queue;

	/** @var BookingMetaRepository */
	private BookingMetaRepository $meta;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository          $bookings    Booking repository.
	 * @param EventInventoryRepository   $inventory   Inventory repository.
	 * @param CheckoutQuoteService       $quotes      Quote service.
	 * @param CouponService              $coupons     Coupon service.
	 * @param CouponRepository           $coupon_repo Coupon repository (for find by ID).
	 * @param BookingStatusService       $status      Status service.
	 * @param PaymentSyncQueueRepository $sync_queue  Sync queue repository.
	 * @param BookingMetaRepository      $meta        Booking meta repository.
	 */
	public function __construct(
		BookingRepository $bookings,
		EventInventoryRepository $inventory,
		CheckoutQuoteService $quotes,
		CouponService $coupons,
		CouponRepository $coupon_repo,
		BookingStatusService $status,
		PaymentSyncQueueRepository $sync_queue,
		BookingMetaRepository $meta
	) {
		$this->bookings    = $bookings;
		$this->inventory   = $inventory;
		$this->quotes      = $quotes;
		$this->coupons     = $coupons;
		$this->coupon_repo = $coupon_repo;
		$this->status      = $status;
		$this->sync_queue  = $sync_queue;
		$this->meta        = $meta;
	}

	/**
	 * Create a checkout from the given arguments.
	 *
	 * @param array $args {
	 *     @type int    $event_id        Event post ID.
	 *     @type int    $user_id         User ID (0 for guest).
	 *     @type int    $qty             Ticket quantity.
	 *     @type string $coupon_code     Optional coupon code.
	 *     @type string $payment_method  'paypal' | 'stripe' | 'manual' | 'check' | '' (free).
	 *     @type string $idempotency_key Client-generated unique key.
	 *     @type array  $meta            Optional extra meta key/value pairs.
	 * }
	 *
	 * @return CheckoutDispatchResult
	 */
	public function create_checkout( array $args ): CheckoutDispatchResult {
		$event_id        = (int) ( $args['event_id'] ?? 0 );
		$user_id         = (int) ( $args['user_id'] ?? 0 );
		$qty             = (int) ( $args['qty'] ?? 1 );
		$coupon_code     = (string) ( $args['coupon_code'] ?? '' );
		$payment_method  = (string) ( $args['payment_method'] ?? '' );
		$idempotency_key = (string) ( $args['idempotency_key'] ?? '' );
		$extra_meta      = (array) ( $args['meta'] ?? array() );

		// 1. Idempotency short-circuit.
		if ( '' !== $idempotency_key ) {
			$existing = $this->bookings->find_by_idempotency_key( $idempotency_key );
			if ( null !== $existing && 'ea-pending' !== $existing->get_status() ) {
				return CheckoutDispatchResult::success(
					$existing->get_id(),
					$this->resolve_next_step( $existing->get_status() )
				);
			}
		}

		// 2. Quote.
		$quote = $this->quotes->quote( $event_id, $user_id, $qty, $coupon_code );

		// 3. Decide flow.
		$is_free    = 0 === bccomp( $quote->get_total(), '0', 4 );
		$gateway_id = $is_free ? '' : $payment_method;

		if ( ! $is_free && '' === $gateway_id ) {
			return CheckoutDispatchResult::failure(
				'payment_method_required',
				'A payment method is required for paid bookings.'
			);
		}

		// 4. Reserve inventory.
		$reserved = $this->inventory->reserve( $event_id, $qty );
		if ( ! $reserved ) {
			return CheckoutDispatchResult::failure(
				'out_of_stock',
				'Not enough tickets available.'
			);
		}

		// 5-8. Insert booking + coupon commit in transaction.
		global $wpdb;

		try {
			$wpdb->query( 'START TRANSACTION' );

			// 5. Insert booking row.
			$booking_id = $this->bookings->insert(
				array(
					'event_id'            => $event_id,
					'user_id'             => $user_id,
					'qty'                 => $qty,
					'subtotal'            => $quote->get_subtotal(),
					'discount_total'      => $quote->get_discount_total(),
					'tax_rate'            => $quote->get_tax_rate(),
					'tax_total'           => $quote->get_tax_total(),
					'total'               => $quote->get_total(),
					'currency'            => $quote->get_currency(),
					'coupon_id'           => $quote->get_coupon_id(),
					'payment_method'      => $is_free ? '' : $payment_method,
					'payment_mode'        => null,
					'gateway_order_id'    => null,
					'status'              => 'ea-pending',
					'payment_status'      => $is_free ? 'paid' : 'unpaid',
					'hold_expires_at_gmt' => $this->compute_hold_expiry( $is_free, $payment_method ),
					'idempotency_key'     => $idempotency_key,
				)
			);

			// 6. Commit coupon usage.
			if ( null !== $quote->get_coupon_id() ) {
				$coupon = $this->coupon_repo->find( $quote->get_coupon_id() );

				if ( null !== $coupon ) {
					$ok = $this->coupons->commit_usage(
						$coupon,
						$booking_id,
						$user_id,
						$quote->get_discount_total()
					);

					if ( ! $ok ) {
						$wpdb->query( 'ROLLBACK' );
						$this->inventory->release_hold( $event_id, $qty );
						return CheckoutDispatchResult::failure(
							'coupon_no_longer_available',
							'The coupon is no longer available.'
						);
					}
				}
			}

			// 7. Persist extra meta.
			foreach ( $extra_meta as $key => $value ) {
				$this->meta->update( $booking_id, (string) $key, $value );
			}

			// 8. Commit transaction.
			$wpdb->query( 'COMMIT' );

		} catch ( \Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );
			$this->inventory->release_hold( $event_id, $qty );
			return CheckoutDispatchResult::failure(
				'checkout_failed',
				$e->getMessage()
			);
		}

		// 9. Branch on flow.
		if ( $is_free ) {
			$this->status->mark_completed( $booking_id, 'free_booking' );
			return CheckoutDispatchResult::success( $booking_id, 'free_completed' );
		}

		if ( in_array( $payment_method, self::ONLINE_METHODS, true ) ) {
			// Online gateway: transition to processing + enqueue sync.
			$this->status->transition( $booking_id, 'ea-processing', 'pending' );
			$this->sync_queue->enqueue(
				$booking_id,
				$payment_method,
				null,
				new \DateTimeImmutable( '+5 minutes', new \DateTimeZone( 'UTC' ) )
			);

			// Phase 9 will add: $gateway->create_checkout() → redirect_url.
			return CheckoutDispatchResult::success( $booking_id, 'redirect' );
		}

		// Offline gateway (manual/check).
		$this->status->transition( $booking_id, 'ea-processing', 'pending' );
		return CheckoutDispatchResult::success( $booking_id, 'offline_pending' );
	}

	/**
	 * Compute the hold expiry timestamp.
	 *
	 * @param bool   $is_free Whether the booking is free.
	 * @param string $method  Payment method slug.
	 *
	 * @return string|null GMT datetime string, or null for free bookings.
	 */
	private function compute_hold_expiry( bool $is_free, string $method ): ?string {
		if ( $is_free ) {
			return null;
		}

		$seconds = in_array( $method, self::ONLINE_METHODS, true )
			? self::HOLD_ONLINE_SECONDS
			: self::HOLD_OFFLINE_SECONDS;

		return gmdate( 'Y-m-d H:i:s', time() + $seconds );
	}

	/**
	 * Resolve next_step from a booking status (for idempotency short-circuit).
	 *
	 * @param string $status Booking status.
	 *
	 * @return string Next step identifier.
	 */
	private function resolve_next_step( string $status ): string {
		switch ( $status ) {
			case 'ea-completed':
				return 'free_completed';
			case 'ea-processing':
				return 'offline_pending';
			default:
				return 'redirect';
		}
	}
}
