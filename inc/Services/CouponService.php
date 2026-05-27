<?php
/**
 * Coupon business logic service.
 *
 * Validates coupon codes, computes discounts, commits usage atomically,
 * and voids usage on cancel/fail/expire.
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

use DateTimeImmutable;
use DateTimeZone;
use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;

/**
 * Coupon service.
 */
class CouponService {

	/** @var CouponRepository */
	private CouponRepository $coupons;

	/** @var CouponEventRepository */
	private CouponEventRepository $coupon_events;

	/** @var CouponUsageRepository */
	private CouponUsageRepository $coupon_usage;

	/**
	 * Constructor.
	 *
	 * @param CouponRepository      $coupons       Coupon repository.
	 * @param CouponEventRepository $coupon_events Coupon-event junction repository.
	 * @param CouponUsageRepository $coupon_usage  Coupon usage audit ledger.
	 */
	public function __construct(
		CouponRepository $coupons,
		CouponEventRepository $coupon_events,
		CouponUsageRepository $coupon_usage
	) {
		$this->coupons       = $coupons;
		$this->coupon_events = $coupon_events;
		$this->coupon_usage  = $coupon_usage;
	}

	/**
	 * Validate a coupon code against event/user/subtotal constraints.
	 *
	 * Returns a structured result — never throws for user-error.
	 *
	 * @param string $code             Coupon code (any case).
	 * @param int    $event_id         Event post ID.
	 * @param int    $user_id          User ID (0 for guest).
	 * @param int    $qty              Ticket quantity.
	 * @param string $eligible_subtotal Subtotal before discount (4-dp).
	 *
	 * @return CouponValidationResult
	 */
	public function validate( string $code, int $event_id, int $user_id, int $qty, string $eligible_subtotal ): CouponValidationResult {
		$code = strtoupper( trim( $code ) );

		// 1. Look up.
		$coupon = $this->coupons->find_by_code( $code );
		if ( null === $coupon ) {
			return CouponValidationResult::failure( 'not_found', 'Coupon not found.' );
		}

		// 2. Active?
		if ( ! $coupon->is_active() ) {
			return CouponValidationResult::failure( 'inactive', 'This coupon is no longer active.', $coupon );
		}

		// 3. Date window.
		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		if ( ! $coupon->is_in_window( $now ) ) {
			return CouponValidationResult::failure( 'out_of_window', 'This coupon is not valid at this time.', $coupon );
		}

		// 4. Global limit.
		if ( ! $coupon->has_global_capacity() ) {
			return CouponValidationResult::failure( 'global_limit', 'This coupon has reached its usage limit.', $coupon );
		}

		// 5. Per-user limit.
		$per_user = $coupon->get_usage_limit_per_user();
		if ( null !== $per_user && $user_id > 0 ) {
			$used = $this->coupon_usage->count_user_usage( $coupon->get_id(), $user_id );
			if ( $used >= $per_user ) {
				return CouponValidationResult::failure( 'user_limit', 'You have already used this coupon the maximum number of times.', $coupon );
			}
		}

		// 6. Event eligibility.
		if ( CouponModel::APPLIES_SPECIFIC === $coupon->get_applies_to() ) {
			if ( ! $this->coupon_events->coupon_applies_to_event( $coupon->get_id(), $event_id ) ) {
				return CouponValidationResult::failure( 'event_not_eligible', 'This coupon is not valid for this event.', $coupon );
			}
		}

		// 7. Min order.
		$min = $coupon->get_min_order_amount();
		if ( null !== $min && bccomp( $eligible_subtotal, $min, 4 ) < 0 ) {
			return CouponValidationResult::failure( 'min_order', 'Your order does not meet the minimum amount for this coupon.', $coupon );
		}

		// 8. Compute discount.
		$discount = $this->calculate_discount( $coupon, $eligible_subtotal );

		// 9. Cap to subtotal.
		if ( bccomp( $discount, $eligible_subtotal, 4 ) > 0 ) {
			$discount = $eligible_subtotal;
		}

		return CouponValidationResult::success( $coupon, $discount );
	}

	/**
	 * Calculate the discount amount for a coupon.
	 *
	 * Uses bcmath only — no float math.
	 *
	 * @param CouponModel $coupon            The coupon.
	 * @param string      $eligible_subtotal Subtotal before discount (4-dp).
	 *
	 * @return string DECIMAL(15,4) discount amount.
	 */
	public function calculate_discount( CouponModel $coupon, string $eligible_subtotal ): string {
		switch ( $coupon->get_discount_type() ) {
			case CouponModel::TYPE_PERCENT:
				$pct = $coupon->get_percent_value() ?? '0';
				return bcdiv( bcmul( $eligible_subtotal, $pct, 8 ), '100', 4 );

			case CouponModel::TYPE_AMOUNT:
				$amt = $coupon->get_amount_value() ?? '0';
				return bccomp( $amt, $eligible_subtotal, 4 ) > 0
					? $eligible_subtotal
					: bcadd( $amt, '0', 4 );

			case CouponModel::TYPE_HYBRID:
				$pct = $coupon->get_percent_value() ?? '0';
				$cap = $coupon->get_max_discount_amount() ?? '0';
				$raw = bcdiv( bcmul( $eligible_subtotal, $pct, 8 ), '100', 4 );
				return bccomp( $raw, $cap, 4 ) > 0
					? bcadd( $cap, '0', 4 )
					: $raw;

			default:
				return '0.0000';
		}
	}

	/**
	 * Commit coupon usage atomically.
	 *
	 * Must run inside the booking transaction. Returns false if the global
	 * or per-user limit is exceeded (caller should roll back the booking).
	 *
	 * @param CouponModel $coupon     The coupon.
	 * @param int         $booking_id Booking ID.
	 * @param int         $user_id    User ID (0 for guest).
	 * @param string      $discount   Discount amount (4-dp).
	 *
	 * @return bool True if usage committed successfully.
	 */
	public function commit_usage( CouponModel $coupon, int $booking_id, int $user_id, string $discount ): bool {
		$global_limit = $coupon->get_usage_limit() ?? 0;

		// Increment first (atomic guard against global limit).
		$ok = $this->coupons->increment_usage( $coupon->get_id(), $global_limit );
		if ( ! $ok ) {
			return false;
		}

		// Re-check per-user (race: another request may have committed between validate & commit).
		$per_user = $coupon->get_usage_limit_per_user();
		if ( null !== $per_user && $user_id > 0 ) {
			$used = $this->coupon_usage->count_user_usage( $coupon->get_id(), $user_id );
			if ( $used >= $per_user ) {
				$this->coupons->decrement_usage( $coupon->get_id() );
				return false;
			}
		}

		// Record usage.
		$this->coupon_usage->record_usage(
			$coupon->get_id(),
			$coupon->get_code(),
			$booking_id,
			$user_id > 0 ? $user_id : null,
			$discount
		);

		return true;
	}

	/**
	 * Void coupon usage for a booking (cancel/fail/expire).
	 *
	 * Idempotent — safe to call multiple times.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $reason     Void reason.
	 *
	 * @return bool True if voided (or already voided).
	 */
	public function void_usage( int $booking_id, string $reason ): bool {
		$row = $this->coupon_usage->find_for_booking( $booking_id );

		if ( null === $row ) {
			return false; // No coupon to void.
		}

		if ( ! empty( $row['voided_at_gmt'] ) ) {
			return true; // Already voided.
		}

		$voided = $this->coupon_usage->void_usage_for_booking( $booking_id, $reason );

		if ( $voided ) {
			$this->coupons->decrement_usage( (int) $row['coupon_id'] );
		}

		return true;
	}
}
