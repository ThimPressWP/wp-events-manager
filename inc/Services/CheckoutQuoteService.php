<?php
/**
 * Checkout quote orchestrator service.
 *
 * Given event_id, user_id, qty, optional coupon code → returns a CheckoutQuote.
 * The frontend AJAX endpoint and BookingCheckoutService both call this.
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;
use WPEMS\Admin\SettingsManager;
use WPEMS\Pricing\CheckoutQuote;

/**
 * Checkout quote service.
 */
class CheckoutQuoteService {

	/** @var TaxService */
	private TaxService $tax;

	/** @var CouponService */
	private CouponService $coupons;

	/**
	 * Constructor.
	 *
	 * @param TaxService    $tax     Tax service.
	 * @param CouponService $coupons Coupon service.
	 */
	public function __construct( TaxService $tax, CouponService $coupons ) {
		$this->tax     = $tax;
		$this->coupons = $coupons;
	}

	/**
	 * Compute a full checkout quote.
	 *
	 * @param int    $event_id    Event post ID.
	 * @param int    $user_id     User ID (0 for guest).
	 * @param int    $qty         Ticket quantity.
	 * @param string $coupon_code Optional coupon code.
	 *
	 * @return CheckoutQuote
	 *
	 * @throws InvalidArgumentException If event_id or qty is invalid.
	 */
	public function quote( int $event_id, int $user_id, int $qty, string $coupon_code = '' ): CheckoutQuote {
		if ( $event_id <= 0 ) {
			throw new InvalidArgumentException( 'event_id_required' );
		}

		if ( $qty <= 0 ) {
			throw new InvalidArgumentException( 'qty_must_be_positive' );
		}

		// Subtotal.
		$price    = $this->get_event_price( $event_id );
		$subtotal = bcmul( $price, (string) $qty, 4 );

		// Coupon.
		$discount             = '0.0000';
		$coupon_id            = null;
		$coupon_code_snapshot = null;

		if ( '' !== $coupon_code ) {
			$result = $this->coupons->validate( $coupon_code, $event_id, $user_id, $qty, $subtotal );

			if ( $result->is_valid ) {
				$discount             = $result->discount;
				$coupon_id            = $result->coupon->get_id();
				$coupon_code_snapshot = $result->coupon->get_code();
			}
			// Invalid coupon silently ignored at quote stage.
		}

		// Taxable subtotal.
		$taxable = bcsub( $subtotal, $discount, 4 );
		if ( bccomp( $taxable, '0', 4 ) < 0 ) {
			$taxable = '0.0000';
		}

		// Tax.
		$currency  = $this->get_currency();
		$tax_rate  = $this->tax->is_enabled() ? $this->tax->get_rate() : '0.0000';
		$tax_total = $this->tax->calculate( $taxable, $currency );

		// Total.
		$total = bcadd( $taxable, $tax_total, 4 );

		return new CheckoutQuote(
			$subtotal,
			$discount,
			$tax_rate,
			$tax_total,
			$total,
			$currency,
			$coupon_id,
			$coupon_code_snapshot,
			$this->tax->get_label()
		);
	}

	/**
	 * Get the ticket price for an event.
	 *
	 * Protected so tests can stub and future filters can override.
	 *
	 * @param int $event_id Event post ID.
	 *
	 * @return string DECIMAL(15,4) as string.
	 */
	protected function get_event_price( int $event_id ): string {
		$raw = get_post_meta( $event_id, 'tp_event_price', true );

		if ( empty( $raw ) || ! is_numeric( $raw ) ) {
			return '0.0000';
		}

		return bcadd( (string) $raw, '0', 4 );
	}

	/**
	 * Get the store currency.
	 *
	 * Protected so tests can stub.
	 *
	 * @return string ISO 4217 code (3 chars, uppercase).
	 */
	protected function get_currency(): string {
		$currency = (string) SettingsManager::get_option( 'thimpress_events_currency', 'USD' );

		return strtoupper( substr( $currency, 0, 3 ) );
	}
}
