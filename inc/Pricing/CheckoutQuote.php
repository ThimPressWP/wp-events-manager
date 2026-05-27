<?php
/**
 * Immutable value object representing a checkout pricing snapshot.
 *
 * Returned by CheckoutQuoteService::quote() and used by BookingCheckoutService
 * to persist the pricing breakdown on the booking row.
 *
 * @package WPEMS\Pricing
 * @since   3.0.0
 */

namespace WPEMS\Pricing;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout quote value object.
 */
final class CheckoutQuote {

	/** @var string DECIMAL(15,4) subtotal before discount. */
	private string $subtotal;

	/** @var string DECIMAL(15,4) total discount applied. */
	private string $discount_total;

	/** @var string DECIMAL(7,4) tax rate percentage. */
	private string $tax_rate;

	/** @var string DECIMAL(15,4) computed tax amount. */
	private string $tax_total;

	/** @var string DECIMAL(15,4) final total. */
	private string $total;

	/** @var string ISO 4217 currency code. */
	private string $currency;

	/** @var int|null Coupon ID if applied. */
	private ?int $coupon_id;

	/** @var string|null Coupon code snapshot. */
	private ?string $coupon_code;

	/** @var string Tax label for display. */
	private string $tax_label;

	/**
	 * Constructor.
	 *
	 * @param string      $subtotal       Subtotal before discount.
	 * @param string      $discount_total Total discount.
	 * @param string      $tax_rate       Tax rate percentage.
	 * @param string      $tax_total      Computed tax amount.
	 * @param string      $total          Final total.
	 * @param string      $currency       ISO 4217 currency code.
	 * @param int|null    $coupon_id      Coupon ID if applied.
	 * @param string|null $coupon_code    Coupon code snapshot.
	 * @param string      $tax_label      Tax label for display.
	 */
	public function __construct(
		string $subtotal,
		string $discount_total,
		string $tax_rate,
		string $tax_total,
		string $total,
		string $currency,
		?int $coupon_id,
		?string $coupon_code,
		string $tax_label
	) {
		$this->subtotal       = $subtotal;
		$this->discount_total = $discount_total;
		$this->tax_rate       = $tax_rate;
		$this->tax_total      = $tax_total;
		$this->total          = $total;
		$this->currency       = $currency;
		$this->coupon_id      = $coupon_id;
		$this->coupon_code    = $coupon_code;
		$this->tax_label      = $tax_label;
	}

	/**
	 * Get subtotal before discount.
	 *
	 * @return string
	 */
	public function get_subtotal(): string {
		return $this->subtotal;
	}

	/**
	 * Get total discount applied.
	 *
	 * @return string
	 */
	public function get_discount_total(): string {
		return $this->discount_total;
	}

	/**
	 * Get tax rate percentage.
	 *
	 * @return string
	 */
	public function get_tax_rate(): string {
		return $this->tax_rate;
	}

	/**
	 * Get computed tax amount.
	 *
	 * @return string
	 */
	public function get_tax_total(): string {
		return $this->tax_total;
	}

	/**
	 * Get final total.
	 *
	 * @return string
	 */
	public function get_total(): string {
		return $this->total;
	}

	/**
	 * Get ISO 4217 currency code.
	 *
	 * @return string
	 */
	public function get_currency(): string {
		return $this->currency;
	}

	/**
	 * Get coupon ID if applied.
	 *
	 * @return int|null
	 */
	public function get_coupon_id(): ?int {
		return $this->coupon_id;
	}

	/**
	 * Get coupon code snapshot.
	 *
	 * @return string|null
	 */
	public function get_coupon_code(): ?string {
		return $this->coupon_code;
	}

	/**
	 * Get tax label for display.
	 *
	 * @return string
	 */
	public function get_tax_label(): string {
		return $this->tax_label;
	}

	/**
	 * Convert to associative array for AJAX responses.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'subtotal'       => $this->subtotal,
			'discount_total' => $this->discount_total,
			'tax_rate'       => $this->tax_rate,
			'tax_total'      => $this->tax_total,
			'total'          => $this->total,
			'currency'       => $this->currency,
			'coupon_id'      => $this->coupon_id,
			'coupon_code'    => $this->coupon_code,
			'tax_label'      => $this->tax_label,
		);
	}
}
