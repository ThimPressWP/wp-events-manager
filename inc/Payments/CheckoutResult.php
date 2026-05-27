<?php
/**
 * Immutable checkout result value object.
 *
 * Returned by AbstractPaymentGateway::create_checkout(). Carries the redirect
 * URL (for online gateways) or the "we are done" signal (for offline gateways),
 * plus the gateway_order_id to be saved on the booking row.
 *
 * @package WPEMS\Payments
 * @since   3.0.0
 */

namespace WPEMS\Payments;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;

/**
 * Checkout result.
 */
final class CheckoutResult {

	/** @var string Online gateway that requires a browser redirect. */
	public const FLOW_REDIRECT = 'redirect';

	/** @var string Offline gateway that completes without redirect. */
	public const FLOW_OFFLINE = 'offline';

	/**
	 * Valid payment modes for redirect gateways.
	 *
	 * @var string[]
	 */
	private const VALID_REDIRECT_MODES = array( 'paypal_rest', 'paypal_standard', 'stripe_checkout' );

	/** @var bool */
	private bool $success;

	/** @var string FLOW_REDIRECT | FLOW_OFFLINE | '' (on error). */
	private string $flow;

	/** @var string Redirect URL for online gateways. */
	private string $redirect_url;

	/** @var string|null Gateway-side order/session ID. */
	private ?string $gateway_order_id;

	/** @var string Payment mode identifier. */
	private string $payment_mode;

	/** @var string Machine-readable error code. */
	private string $error_code;

	/** @var string Human-readable error message. */
	private string $error_message;

	/**
	 * Private constructor — use static factories.
	 */
	private function __construct() {}

	// ─── Static factories ───────────────────────────────────────────

	/**
	 * Create a redirect result for online gateways.
	 *
	 * @param string $url              Redirect URL (must not be empty).
	 * @param string $gateway_order_id Gateway order/session ID (must not be empty).
	 * @param string $payment_mode     Payment mode identifier.
	 *
	 * @return self
	 *
	 * @throws InvalidArgumentException If url, order_id, or mode is invalid.
	 */
	public static function redirect( string $url, string $gateway_order_id, string $payment_mode ): self {
		if ( '' === $url ) {
			throw new InvalidArgumentException( 'Redirect URL must not be empty.' );
		}

		if ( '' === $gateway_order_id ) {
			throw new InvalidArgumentException( 'Gateway order ID must not be empty.' );
		}

		if ( ! in_array( $payment_mode, self::VALID_REDIRECT_MODES, true ) ) {
			throw new InvalidArgumentException(
				sprintf( 'Invalid payment mode "%s". Allowed: %s.', $payment_mode, implode( ', ', self::VALID_REDIRECT_MODES ) )
			);
		}

		$r                   = new self();
		$r->success          = true;
		$r->flow             = self::FLOW_REDIRECT;
		$r->redirect_url     = $url;
		$r->gateway_order_id = $gateway_order_id;
		$r->payment_mode     = $payment_mode;
		$r->error_code       = '';
		$r->error_message    = '';

		return $r;
	}

	/**
	 * Create an offline result for manual/check gateways.
	 *
	 * @param string $payment_mode Payment mode identifier (default 'offline').
	 *
	 * @return self
	 */
	public static function offline( string $payment_mode = 'offline' ): self {
		$r                   = new self();
		$r->success          = true;
		$r->flow             = self::FLOW_OFFLINE;
		$r->redirect_url     = '';
		$r->gateway_order_id = null;
		$r->payment_mode     = $payment_mode;
		$r->error_code       = '';
		$r->error_message    = '';

		return $r;
	}

	/**
	 * Create an error result.
	 *
	 * @param string $code    Machine-readable error code.
	 * @param string $message Human-readable error message.
	 *
	 * @return self
	 */
	public static function error( string $code, string $message ): self {
		$r                   = new self();
		$r->success          = false;
		$r->flow             = '';
		$r->redirect_url     = '';
		$r->gateway_order_id = null;
		$r->payment_mode     = '';
		$r->error_code       = $code;
		$r->error_message    = $message;

		return $r;
	}

	// ─── Getters ────────────────────────────────────────────────────

	/**
	 * Whether the checkout was successful.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Get the flow type.
	 *
	 * @return string FLOW_REDIRECT, FLOW_OFFLINE, or '' on error.
	 */
	public function get_flow(): string {
		return $this->flow;
	}

	/**
	 * Get the redirect URL.
	 *
	 * @return string
	 */
	public function get_redirect_url(): string {
		return $this->redirect_url;
	}

	/**
	 * Get the gateway order/session ID.
	 *
	 * @return string|null
	 */
	public function get_gateway_order_id(): ?string {
		return $this->gateway_order_id;
	}

	/**
	 * Get the payment mode identifier.
	 *
	 * @return string
	 */
	public function get_payment_mode(): string {
		return $this->payment_mode;
	}

	/**
	 * Get the error code.
	 *
	 * @return string
	 */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/**
	 * Get the error message.
	 *
	 * @return string
	 */
	public function get_error_message(): string {
		return $this->error_message;
	}
}
