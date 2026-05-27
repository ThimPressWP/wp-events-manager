<?php
/**
 * Checkout dispatch result value object.
 *
 * Returned by BookingCheckoutService::create_checkout() to describe
 * the outcome: success/failure, next step, and optional redirect URL.
 *
 * @package WPEMS\Services
 * @since   3.0.0
 */

namespace WPEMS\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Checkout dispatch result.
 */
final class CheckoutDispatchResult {

	/** @var bool Whether the checkout succeeded. */
	public bool $success;

	/** @var int Booking ID (0 on failure). */
	public int $booking_id;

	/** @var string Next step: 'free_completed' | 'offline_pending' | 'redirect' | 'failed'. */
	public string $next_step;

	/** @var string Redirect URL for paid gateways (empty otherwise). */
	public string $redirect_url;

	/** @var string Machine-readable error code. */
	public string $error_code;

	/** @var string Human-readable error message. */
	public string $error_message;

	/**
	 * Private constructor — use static factories.
	 */
	private function __construct() {}

	/**
	 * Create a successful result.
	 *
	 * @param int    $booking_id   Booking ID.
	 * @param string $next_step    Next step identifier.
	 * @param string $redirect_url Redirect URL for paid gateways.
	 *
	 * @return self
	 */
	public static function success( int $booking_id, string $next_step, string $redirect_url = '' ): self {
		$r                = new self();
		$r->success       = true;
		$r->booking_id    = $booking_id;
		$r->next_step     = $next_step;
		$r->redirect_url  = $redirect_url;
		$r->error_code    = '';
		$r->error_message = '';

		return $r;
	}

	/**
	 * Create a failure result.
	 *
	 * @param string $error_code    Machine-readable error code.
	 * @param string $error_message Human-readable message.
	 * @param int    $booking_id    Booking ID if one was created.
	 *
	 * @return self
	 */
	public static function failure( string $error_code, string $error_message, int $booking_id = 0 ): self {
		$r                = new self();
		$r->success       = false;
		$r->booking_id    = $booking_id;
		$r->next_step     = 'failed';
		$r->redirect_url  = '';
		$r->error_code    = $error_code;
		$r->error_message = $error_message;

		return $r;
	}
}
