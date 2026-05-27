<?php
/**
 * Structured result from CouponService::validate().
 *
 * Never throws for "invalid coupon" — the frontend needs the error_code
 * to display a user-friendly message.
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

use WPEMS\Models\CouponModel;

/**
 * Coupon validation result.
 */
final class CouponValidationResult {

	/** @var bool Whether the coupon is valid. */
	public bool $is_valid;

	/** @var CouponModel|null The coupon model if found. */
	public ?CouponModel $coupon;

	/** @var string Discount amount (4-dp string). */
	public string $discount;

	/** @var string Machine-readable error code. */
	public string $error_code;

	/** @var string Human-readable error message. */
	public string $error_message;

	/**
	 * Private constructor — use static factories.
	 */
	private function __construct() {}

	/**
	 * Create a successful validation result.
	 *
	 * @param CouponModel $coupon   Validated coupon.
	 * @param string      $discount Computed discount (4-dp).
	 *
	 * @return self
	 */
	public static function success( CouponModel $coupon, string $discount ): self {
		$r                = new self();
		$r->is_valid      = true;
		$r->coupon        = $coupon;
		$r->discount      = $discount;
		$r->error_code    = '';
		$r->error_message = '';

		return $r;
	}

	/**
	 * Create a failure validation result.
	 *
	 * @param string           $error_code    Machine-readable error code.
	 * @param string           $error_message Human-readable message.
	 * @param CouponModel|null $coupon        The coupon if found (may be null for 'not_found').
	 *
	 * @return self
	 */
	public static function failure( string $error_code, string $error_message, ?CouponModel $coupon = null ): self {
		$r                = new self();
		$r->is_valid      = false;
		$r->coupon        = $coupon;
		$r->discount      = '0.0000';
		$r->error_code    = $error_code;
		$r->error_message = $error_message;

		return $r;
	}
}
