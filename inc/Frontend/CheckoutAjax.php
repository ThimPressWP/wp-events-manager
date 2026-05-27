<?php
/**
 * Frontend AJAX checkout handler.
 *
 * @package WPEMS\Frontend
 */

namespace WPEMS\Frontend;

use WPEMS\Services\BookingCheckoutService;
use WPEMS\Services\CheckoutQuoteService;
use WPEMS\Services\CouponService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX checkout requests.
 */
class CheckoutAjax {

	const NONCE_ACTION = 'wpems_checkout';

	/** @var CheckoutQuoteService */
	private CheckoutQuoteService $quotes;

	/** @var BookingCheckoutService */
	private BookingCheckoutService $checkout;

	/** @var CouponService */
	private CouponService $coupons;

	/**
	 * Constructor.
	 *
	 * @param CheckoutQuoteService   $quotes   Quote service.
	 * @param BookingCheckoutService $checkout Checkout service.
	 * @param CouponService          $coupons  Coupon service.
	 */
	public function __construct(
		CheckoutQuoteService $quotes,
		BookingCheckoutService $checkout,
		CouponService $coupons
	) {
		$this->quotes   = $quotes;
		$this->checkout = $checkout;
		$this->coupons  = $coupons;
	}

	/**
	 * Register AJAX hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_wpems_checkout_quote', array( $this, 'ajax_quote' ) );
		add_action( 'wp_ajax_nopriv_wpems_checkout_quote', array( $this, 'ajax_quote' ) );

		add_action( 'wp_ajax_wpems_checkout_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_wpems_checkout_submit', array( $this, 'ajax_submit' ) );

		add_action( 'wp_ajax_wpems_checkout_validate_coupon', array( $this, 'ajax_validate_coupon' ) );
		add_action( 'wp_ajax_nopriv_wpems_checkout_validate_coupon', array( $this, 'ajax_validate_coupon' ) );
	}

	/**
	 * Sanitize and return the AJAX payload.
	 *
	 * @return array
	 */
	private function get_payload(): array {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		return array(
			'event_id'        => absint( $_POST['event_id'] ?? 0 ),
			'qty'             => max( 1, absint( $_POST['qty'] ?? 1 ) ),
			'coupon_code'     => strtoupper( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) ) ),
			'payment_method'  => sanitize_key( wp_unslash( $_POST['payment_method'] ?? '' ) ),
			'idempotency_key' => preg_replace( '/[^a-zA-Z0-9-]/', '', (string) ( $_POST['idempotency_key'] ?? '' ) ),
			'user_id'         => get_current_user_id(),
			'meta'            => is_array( $_POST['meta'] ?? null )
				? array_map( 'sanitize_text_field', wp_unslash( $_POST['meta'] ) )
				: array(),
		);
	}

	/**
	 * Send a JSON success response.
	 *
	 * @param array $data Response data.
	 *
	 * @return void
	 */
	private function send_success( array $data ): void {
		wp_send_json_success( $data );
	}

	/**
	 * Send a JSON error response.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $http    HTTP status code.
	 *
	 * @return void
	 */
	private function send_error( string $code, string $message, int $http = 400 ): void {
		wp_send_json_error(
			array(
				'code'    => $code,
				'message' => $message,
			),
			$http
		);
	}

	/**
	 * AJAX: Get a pricing quote.
	 *
	 * @return void
	 */
	public function ajax_quote(): void {
		try {
			$payload = $this->get_payload();
		} catch ( \Exception $e ) {
			$this->send_error( 'nonce_failed', $e->getMessage(), 403 );
		}

		if ( 0 === $payload['event_id'] ) {
			$this->send_error( 'event_required', __( 'Event ID is required.', 'wp-events-manager' ) );
		}

		try {
			$quote = $this->quotes->quote(
				$payload['event_id'],
				$payload['user_id'],
				$payload['qty'],
				$payload['coupon_code']
			);
			$this->send_success( array( 'quote' => $quote->to_array() ) );
		} catch ( \InvalidArgumentException $e ) {
			$this->send_error( 'invalid_args', $e->getMessage() );
		}
	}

	/**
	 * AJAX: Validate a coupon code.
	 *
	 * @return void
	 */
	public function ajax_validate_coupon(): void {
		try {
			$payload = $this->get_payload();
		} catch ( \Exception $e ) {
			$this->send_error( 'nonce_failed', $e->getMessage(), 403 );
		}

		if ( '' === $payload['coupon_code'] ) {
			$this->send_error( 'coupon_required', __( 'Coupon code is required.', 'wp-events-manager' ) );
		}

		// Get event price for subtotal calculation.
		$event_price = $this->get_event_price( $payload['event_id'] );
		$subtotal    = bcmul( $event_price, (string) $payload['qty'], 4 );

		$result = $this->coupons->validate(
			$payload['coupon_code'],
			$payload['event_id'],
			$payload['user_id'],
			$payload['qty'],
			$subtotal
		);

		$this->send_success(
			array(
				'valid'    => $result->is_valid,
				'code'     => $result->coupon ? $result->coupon->get_code() : '',
				'discount' => $result->discount,
				'error'    => $result->error_code,
				'message'  => $result->error_message,
			)
		);
	}

	/**
	 * AJAX: Submit the checkout.
	 *
	 * @return void
	 */
	public function ajax_submit(): void {
		try {
			$payload = $this->get_payload();
		} catch ( \Exception $e ) {
			$this->send_error( 'nonce_failed', $e->getMessage(), 403 );
		}

		// Validate idempotency key.
		$key_len = strlen( $payload['idempotency_key'] );
		if ( $key_len < 16 || $key_len > 64 ) {
			$this->send_error(
				'idempotency_key_invalid',
				__( 'Invalid idempotency key.', 'wp-events-manager' )
			);
		}

		$result = $this->checkout->create_checkout( $payload );

		if ( ! $result->success ) {
			$this->send_error( $result->error_code, $result->error_message );
		}

		$response = array();

		switch ( $result->next_step ) {
			case 'free_completed':
			case 'offline_pending':
				$response['next'] = 'redirect';
				$response['url']  = $this->order_received_url( $result->booking_id );
				break;
			case 'redirect':
				$response['next'] = 'redirect';
				$response['url']  = $result->redirect_url;
				break;
			default:
				$response['next'] = 'redirect';
				$response['url']  = $this->order_received_url( $result->booking_id );
				break;
		}

		$this->send_success( $response );
	}

	/**
	 * Build an order-received URL for a booking.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return string
	 */
	private function order_received_url( int $booking_id ): string {
		return add_query_arg(
			array(
				'wpems'      => 'order-received',
				'booking_id' => $booking_id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Get the price for an event.
	 *
	 * @param int $event_id Event ID.
	 *
	 * @return string DECIMAL string.
	 */
	private function get_event_price( int $event_id ): string {
		$price = (float) get_post_meta( $event_id, 'tp_event_price', true );
		return number_format( $price > 0 ? $price : 0, 4, '.', '' );
	}
}
