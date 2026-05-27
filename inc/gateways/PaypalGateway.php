<?php
/**
 * PayPal REST and Standard payment gateway.
 *
 * @package WPEMS\Gateways
 * @since   3.0.0
 */

namespace WPEMS\Gateways;

defined( 'ABSPATH' ) || exit;

use BadMethodCallException;
use RuntimeException;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\AbstractPaymentGateway;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\PaymentTransactionRepository;

/**
 * PayPal gateway.
 */
class PaypalGateway extends AbstractPaymentGateway {

	/**
	 * Gateway feature flags.
	 *
	 * @var string[]
	 */
	protected const FEATURES = array( 'redirect_checkout', 'webhook', 'payment_sync', 'refund' );

	/**
	 * Gateway identifier.
	 *
	 * @var string
	 */
	public $id = 'paypal';

	/**
	 * Booking repository.
	 *
	 * @var BookingRepository
	 */
	private BookingRepository $bookings;

	/**
	 * Transaction repository.
	 *
	 * @var PaymentTransactionRepository
	 */
	private PaymentTransactionRepository $txns;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository|null            $bookings Booking repository.
	 * @param PaymentTransactionRepository|null $txns     Transaction repository.
	 */
	public function __construct( ?BookingRepository $bookings = null, ?PaymentTransactionRepository $txns = null ) {
		$this->bookings = $bookings ? $bookings : new BookingRepository();
		$this->txns     = $txns ? $txns : new PaymentTransactionRepository();
	}

	/**
	 * Get gateway title (translated lazily to avoid early textdomain load).
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'PayPal', 'wp-events-manager' );
	}

	/**
	 * Get gateway description (translated lazily to avoid early textdomain load).
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Pay with PayPal.', 'wp-events-manager' );
	}

	/**
	 * Whether PayPal is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === $this->get_setting( 'enable', 'no' );
	}

	/**
	 * Legacy compatibility alias.
	 *
	 * @return bool
	 */
	public function is_enable(): bool {
		return $this->is_enabled();
	}

	/**
	 * Whether PayPal can currently accept payment.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		if ( 'rest' === $this->get_active_mode() ) {
			return '' !== $this->get_setting( 'rest_client_id', '' )
				&& '' !== $this->get_setting( 'rest_client_secret', '' );
		}

		return '' !== $this->get_standard_receiver_email();
	}

	/**
	 * Gateway admin fields.
	 *
	 * @return array
	 */
	public function admin_fields(): array {
		return array(
			array(
				'type'  => 'section_start',
				'id'    => 'paypal_settings',
				'title' => __( 'PayPal', 'wp-events-manager' ),
				'desc'  => sprintf(
					__( 'REST webhook URL: %1$s. Standard IPN URL: %2$s.', 'wp-events-manager' ),
					esc_url( home_url( '/wpems-webhook/paypal-rest/' ) ),
					esc_url( home_url( '/wpems-webhook/paypal-ipn/' ) )
				),
			),
			array(
				'type'    => 'yes_no',
				'id'      => $this->setting_id( 'enable' ),
				'title'   => __( 'Enable', 'wp-events-manager' ),
				'default' => 'no',
			),
			array(
				'type'    => 'radio',
				'id'      => $this->setting_id( 'integration_mode' ),
				'title'   => __( 'Integration mode', 'wp-events-manager' ),
				'options' => array(
					'rest'     => __( 'REST (recommended)', 'wp-events-manager' ),
					'standard' => __( 'Standard (legacy)', 'wp-events-manager' ),
					'auto'     => __( 'Auto', 'wp-events-manager' ),
				),
				'default' => 'auto',
			),
			array(
				'type'    => 'radio',
				'id'      => $this->setting_id( 'mode' ),
				'title'   => __( 'Environment', 'wp-events-manager' ),
				'options' => array(
					'sandbox' => __( 'Sandbox', 'wp-events-manager' ),
					'live'    => __( 'Live', 'wp-events-manager' ),
				),
				'default' => 'sandbox',
			),
			array(
				'type'  => 'text',
				'id'    => $this->setting_id( 'rest_client_id' ),
				'title' => __( 'REST Client ID', 'wp-events-manager' ),
			),
			array(
				'type'  => 'password',
				'id'    => $this->setting_id( 'rest_client_secret' ),
				'title' => __( 'REST Client Secret', 'wp-events-manager' ),
			),
			array(
				'type'  => 'text',
				'id'    => $this->setting_id( 'rest_webhook_id' ),
				'title' => __( 'REST Webhook ID', 'wp-events-manager' ),
			),
			array(
				'type'  => 'email',
				'id'    => $this->setting_id( 'email' ),
				'title' => __( 'Standard PayPal email', 'wp-events-manager' ),
			),
			array(
				'type'  => 'email',
				'id'    => $this->setting_id( 'sandbox_email' ),
				'title' => __( 'Standard sandbox email', 'wp-events-manager' ),
			),
			array(
				'type'    => 'yes_no',
				'id'      => $this->setting_id( 'standard_ipn_enable' ),
				'title'   => __( 'Enable IPN', 'wp-events-manager' ),
				'default' => 'yes',
			),
			array(
				'type'    => 'yes_no',
				'id'      => $this->setting_id( 'sync_enable' ),
				'title'   => __( 'Cron sync', 'wp-events-manager' ),
				'default' => 'yes',
			),
			array(
				'type'    => 'number',
				'id'      => $this->setting_id( 'sync_pending_minutes' ),
				'title'   => __( 'Initial poll (minutes)', 'wp-events-manager' ),
				'default' => 5,
			),
			array(
				'type'    => 'number',
				'id'      => $this->setting_id( 'sync_max_attempts' ),
				'title'   => __( 'Max attempts', 'wp-events-manager' ),
				'default' => 24,
			),
			array(
				'type' => 'section_end',
				'id'   => 'paypal_settings',
			),
		);
	}

	/**
	 * Create a PayPal checkout.
	 *
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return CheckoutResult
	 */
	public function create_checkout( BookingTableModel $booking ): CheckoutResult {
		if ( 'standard' === $this->get_active_mode() ) {
			$url      = $this->build_standard_checkout_url( $booking );
			$order_id = 'wpems-std-' . $booking->get_id() . '-' . substr( wp_generate_uuid4(), 0, 8 );

			return CheckoutResult::redirect( $url, $order_id, 'paypal_standard' );
		}

		$order    = $this->create_rest_order( $booking );
		$order_id = (string) ( $order['id'] ?? '' );
		$approve  = $this->find_link( $order, 'approve' );

		if ( '' === $order_id || '' === $approve ) {
			throw new RuntimeException( 'paypal_order_create_invalid_response' );
		}

		return CheckoutResult::redirect( $approve, $order_id, 'paypal_rest' );
	}

	/**
	 * Handle customer return from PayPal.
	 *
	 * @param array $request Sanitized request data.
	 *
	 * @return PaymentResult
	 */
	public function handle_return_request( array $request ): PaymentResult {
		$booking_id = (int) ( $request['booking_id'] ?? 0 );
		$booking    = $this->bookings->find( $booking_id );

		if ( null === $booking ) {
			return PaymentResult::unknown( 0, 'booking_missing' );
		}

		if ( 'paypal_standard' === $booking->get_payment_mode() ) {
			if ( 'cancel' === ( $request['action'] ?? '' ) ) {
				return PaymentResult::cancelled( $booking_id, 'user_cancelled' );
			}

			return PaymentResult::pending( $booking_id, 'paypal_standard_awaiting_ipn' );
		}

		if ( 'cancel' === ( $request['action'] ?? '' ) ) {
			return PaymentResult::cancelled( $booking_id, 'user_cancelled_return' );
		}

		$order_id = $booking->get_gateway_order_id();
		if ( null === $order_id || '' === $order_id ) {
			$order_id = (string) ( $request['token'] ?? '' );
		}
		if ( '' === $order_id ) {
			return PaymentResult::unknown( $booking_id, 'no_order_id' );
		}

		try {
			$capture = $this->capture_rest_order( $order_id, 'wpems-capture-' . $booking_id );
		} catch ( RuntimeException $e ) {
			if ( false === strpos( $e->getMessage(), 'ORDER_ALREADY_CAPTURED' ) && false === strpos( $e->getMessage(), '422' ) ) {
				throw $e;
			}

			$order = $this->get_rest_order( $order_id );
			return $this->map_rest_order_to_payment_result( $order, $booking );
		}

		return $this->map_rest_capture_to_payment_result( $capture, $booking );
	}

	/**
	 * Handle a PayPal REST webhook request.
	 *
	 * @param array $request Request data with headers and raw_payload.
	 *
	 * @return PaymentResult
	 */
	public function handle_webhook_request( array $request ): PaymentResult {
		$headers = (array) ( $request['headers'] ?? array() );
		$payload = (string) ( $request['raw_payload'] ?? '' );

		if ( ! $this->verify_rest_webhook( $headers, $payload ) ) {
			throw new RuntimeException( 'invalid_signature' );
		}

		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) ) {
			return PaymentResult::unknown( 0, 'invalid_payload' );
		}

		$type     = (string) ( $event['event_type'] ?? '' );
		$event_id = (string) ( $event['id'] ?? '' );
		$resource = (array) ( $event['resource'] ?? array() );
		$booking  = $this->find_booking_for_paypal_resource( $resource );

		if ( null === $booking ) {
			return PaymentResult::unknown( 0, 'booking_missing' );
		}

		if ( 'PAYMENT.CAPTURE.COMPLETED' === $type ) {
			return PaymentResult::paid(
				$booking->get_id(),
				$this->build_paypal_capture_transaction_data( $resource, $booking, $resource ),
				$event_id
			);
		}

		if ( in_array( $type, array( 'PAYMENT.CAPTURE.DENIED', 'PAYMENT.CAPTURE.DECLINED' ), true ) ) {
			return PaymentResult::failed( $booking->get_id(), strtolower( $type ), $event_id );
		}

		if ( 'PAYMENT.CAPTURE.REFUNDED' === $type ) {
			return PaymentResult::refunded(
				$booking->get_id(),
				array(
					'booking_id'             => $booking->get_id(),
					'type'                   => 'paypal_refund',
					'gateway_transaction_id' => (string) ( $resource['id'] ?? '' ),
					'amount'                 => '-' . ltrim( (string) ( $resource['amount']['value'] ?? '0' ), '-' ),
					'currency'               => (string) ( $resource['amount']['currency_code'] ?? $booking->get_currency() ),
					'status'                 => 'completed',
					'raw_response'           => $resource,
				),
				$event_id
			);
		}

		return PaymentResult::unknown( $booking->get_id(), 'ignored_event' );
	}

	/**
	 * Sync PayPal payment status.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param string            $source  Sync source.
	 *
	 * @return PaymentResult
	 */
	public function sync_payment_status( BookingTableModel $booking, string $source = 'manual' ): PaymentResult {
		if ( 'paypal_standard' === $booking->get_payment_mode() ) {
			return PaymentResult::unknown( $booking->get_id(), 'standard_requires_ipn_or_admin' );
		}

		$order_id = $booking->get_gateway_order_id();
		if ( null === $order_id || '' === $order_id ) {
			return PaymentResult::unknown( $booking->get_id(), 'no_order_id' );
		}

		$order = $this->get_rest_order( $order_id );
		return $this->map_rest_order_to_payment_result( $order, $booking );
	}

	/**
	 * Resolve active PayPal integration mode.
	 *
	 * @return string rest|standard
	 */
	protected function get_active_mode(): string {
		$mode = (string) $this->get_setting( 'integration_mode', 'auto' );

		if ( 'rest' === $mode || 'standard' === $mode ) {
			return $mode;
		}

		if ( '' !== $this->get_setting( 'rest_client_id', '' ) && '' !== $this->get_setting( 'rest_client_secret', '' ) ) {
			return 'rest';
		}

		return 'standard';
	}

	/**
	 * Get PayPal REST API base URL.
	 *
	 * @return string
	 */
	protected function get_rest_api_base_url(): string {
		return 'live' === $this->get_setting( 'mode', 'sandbox' )
			? 'https://api-m.paypal.com'
			: 'https://api-m.sandbox.paypal.com';
	}

	/**
	 * Get and cache a PayPal OAuth token.
	 *
	 * @return string
	 */
	protected function get_access_token(): string {
		$mode      = (string) $this->get_setting( 'mode', 'sandbox' );
		$cache_key = 'wpems_paypal_access_token_' . $mode;
		$cached    = get_transient( $cache_key );

		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$client_id = (string) $this->get_setting( 'rest_client_id', '' );
		$secret    = (string) $this->get_setting( 'rest_client_secret', '' );
		$response  = wp_remote_post(
			$this->get_rest_api_base_url() . '/v1/oauth2/token',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $secret ),
				),
				'body'    => array(
					'grant_type' => 'client_credentials',
				),
			)
		);

		$body = $this->decode_response_body( $response, 'paypal_oauth_failed' );

		$token = (string) ( $body['access_token'] ?? '' );
		if ( '' === $token ) {
			throw new RuntimeException( 'paypal_oauth_failed' );
		}

		$ttl = max( 1, (int) ( $body['expires_in'] ?? 3600 ) - 60 );
		set_transient( $cache_key, $token, $ttl );

		return $token;
	}

	/**
	 * Create a PayPal REST order.
	 *
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return array
	 */
	protected function create_rest_order( BookingTableModel $booking ): array {
		$params = array(
			'intent'              => 'CAPTURE',
			'purchase_units'      => array(
				array(
					'reference_id' => 'wpems-' . $booking->get_id(),
					'custom_id'    => (string) $booking->get_id(),
					'amount'       => array(
						'currency_code' => $booking->get_currency(),
						'value'         => $this->format_amount_2( $booking->get_total() ),
					),
				),
			),
			'application_context' => array(
				'return_url'  => home_url(
					add_query_arg(
						array(
							'wpems_gateway' => 'paypal',
							'action'        => 'return',
							'booking_id'    => $booking->get_id(),
						),
						'/'
					)
				),
				'cancel_url'  => home_url(
					add_query_arg(
						array(
							'wpems_gateway' => 'paypal',
							'action'        => 'cancel',
							'booking_id'    => $booking->get_id(),
						),
						'/'
					)
				),
				'user_action' => 'PAY_NOW',
			),
		);

		$response = wp_remote_post(
			$this->get_rest_api_base_url() . '/v2/checkout/orders',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization'     => 'Bearer ' . $this->get_access_token(),
					'Content-Type'      => 'application/json',
					'PayPal-Request-Id' => 'wpems-create-' . $booking->get_id(),
				),
				'body'    => wp_json_encode( $params ),
			)
		);

		return $this->decode_response_body( $response, 'paypal_order_create_failed' );
	}

	/**
	 * Capture a PayPal REST order.
	 *
	 * @param string $order_id        PayPal order ID.
	 * @param string $idempotency_key Idempotency key.
	 *
	 * @return array
	 */
	protected function capture_rest_order( string $order_id, string $idempotency_key ): array {
		$response = wp_remote_post(
			$this->get_rest_api_base_url() . '/v2/checkout/orders/' . rawurlencode( $order_id ) . '/capture',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization'     => 'Bearer ' . $this->get_access_token(),
					'Content-Type'      => 'application/json',
					'PayPal-Request-Id' => $idempotency_key,
				),
				'body'    => '{}',
			)
		);

		return $this->decode_response_body( $response, 'paypal_order_capture_failed' );
	}

	/**
	 * Retrieve a PayPal REST order.
	 *
	 * @param string $order_id PayPal order ID.
	 *
	 * @return array
	 */
	protected function get_rest_order( string $order_id ): array {
		$response = wp_remote_get(
			$this->get_rest_api_base_url() . '/v2/checkout/orders/' . rawurlencode( $order_id ),
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->get_access_token(),
				),
			)
		);

		return $this->decode_response_body( $response, 'paypal_order_get_failed' );
	}

	/**
	 * Verify a PayPal REST webhook signature.
	 *
	 * @param array  $headers Headers.
	 * @param string $payload Raw payload.
	 *
	 * @return bool
	 */
	protected function verify_rest_webhook( array $headers, string $payload ): bool {
		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) ) {
			return false;
		}

		$response = wp_remote_post(
			$this->get_rest_api_base_url() . '/v1/notifications/verify-webhook-signature',
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->get_access_token(),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'auth_algo'         => $this->get_header( $headers, 'PAYPAL-AUTH-ALGO' ),
						'cert_url'          => $this->get_header( $headers, 'PAYPAL-CERT-URL' ),
						'transmission_id'   => $this->get_header( $headers, 'PAYPAL-TRANSMISSION-ID' ),
						'transmission_sig'  => $this->get_header( $headers, 'PAYPAL-TRANSMISSION-SIG' ),
						'transmission_time' => $this->get_header( $headers, 'PAYPAL-TRANSMISSION-TIME' ),
						'webhook_id'        => $this->get_setting( 'rest_webhook_id', '' ),
						'webhook_event'     => $event,
					)
				),
			)
		);

		$body = $this->decode_response_body( $response, 'paypal_webhook_verify_failed' );
		return 'SUCCESS' === ( $body['verification_status'] ?? '' );
	}

	/**
	 * Map a PayPal REST order to a payment result.
	 *
	 * @param array             $order   PayPal order.
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return PaymentResult
	 */
	protected function map_rest_order_to_payment_result( array $order, BookingTableModel $booking ): PaymentResult {
		$status = strtoupper( (string) ( $order['status'] ?? '' ) );

		if ( 'COMPLETED' === $status ) {
			$capture = $this->extract_capture_from_order( $order );
			if ( array() === $capture ) {
				return PaymentResult::unknown( $booking->get_id(), 'capture_missing' );
			}

			return PaymentResult::paid(
				$booking->get_id(),
				$this->build_paypal_capture_transaction_data( $capture, $booking, $order ),
				(string) ( $capture['id'] ?? '' )
			);
		}

		if ( in_array( $status, array( 'VOIDED', 'DECLINED' ), true ) ) {
			return PaymentResult::failed( $booking->get_id(), strtolower( $status ) );
		}

		if ( in_array( $status, array( 'APPROVED', 'CREATED', 'SAVED', 'PAYER_ACTION_REQUIRED' ), true ) ) {
			return PaymentResult::pending( $booking->get_id(), strtolower( $status ) );
		}

		return PaymentResult::unknown( $booking->get_id(), 'paypal_status_' . strtolower( $status ) );
	}

	/**
	 * Map a PayPal REST capture response to a payment result.
	 *
	 * @param array             $capture PayPal capture or order response.
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return PaymentResult
	 */
	protected function map_rest_capture_to_payment_result( array $capture, BookingTableModel $booking ): PaymentResult {
		if ( isset( $capture['purchase_units'] ) ) {
			return $this->map_rest_order_to_payment_result( $capture, $booking );
		}

		$status = strtoupper( (string) ( $capture['status'] ?? '' ) );

		if ( 'COMPLETED' === $status ) {
			return PaymentResult::paid(
				$booking->get_id(),
				$this->build_paypal_capture_transaction_data( $capture, $booking, $capture ),
				(string) ( $capture['id'] ?? '' )
			);
		}

		if ( 'DECLINED' === $status ) {
			return PaymentResult::failed( $booking->get_id(), 'declined' );
		}

		if ( 'PENDING' === $status ) {
			return PaymentResult::pending( $booking->get_id(), 'pending' );
		}

		return PaymentResult::unknown( $booking->get_id(), 'paypal_capture_' . strtolower( $status ) );
	}

	/**
	 * Build a PayPal Standard checkout URL.
	 *
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return string
	 */
	protected function build_standard_checkout_url( BookingTableModel $booking ): string {
		$receiver = $this->get_standard_receiver_email();
		if ( '' === $receiver ) {
			throw new RuntimeException( 'paypal_standard_email_missing' );
		}

		$base   = 'live' === $this->get_setting( 'mode', 'sandbox' )
			? 'https://www.paypal.com/cgi-bin/webscr'
			: 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		$params = array(
			'cmd'           => '_xclick',
			'business'      => $receiver,
			'item_name'     => sprintf( 'Event booking #%d', $booking->get_id() ),
			'amount'        => $this->format_amount_2( $booking->get_total() ),
			'currency_code' => $booking->get_currency(),
			'quantity'      => 1,
			'no_shipping'   => 1,
			'no_note'       => 1,
			'custom'        => (string) $booking->get_id(),
			'return'        => home_url(
				add_query_arg(
					array(
						'wpems_gateway' => 'paypal',
						'action'        => 'return',
						'booking_id'    => $booking->get_id(),
					),
					'/'
				)
			),
			'cancel_return' => home_url(
				add_query_arg(
					array(
						'wpems_gateway' => 'paypal',
						'action'        => 'cancel',
						'booking_id'    => $booking->get_id(),
					),
					'/'
				)
			),
			'notify_url'    => home_url( '/wpems-webhook/paypal-ipn/' ),
		);

		return $base . '?' . http_build_query( $params, '', '&' );
	}

	/**
	 * Handle PayPal Standard IPN.
	 *
	 * @param array $post IPN data.
	 *
	 * @return PaymentResult
	 */
	public function handle_standard_ipn( array $post ): PaymentResult {
		if ( ! $this->verify_standard_ipn( $post ) ) {
			return PaymentResult::unknown( 0, 'ipn_verify_failed' );
		}

		$booking_id = (int) ( $post['custom'] ?? 0 );
		$booking    = $this->bookings->find( $booking_id );

		if ( null === $booking ) {
			return PaymentResult::unknown( 0, 'booking_missing' );
		}

		if ( ! $this->validate_standard_ipn_booking( $post, $booking ) ) {
			return PaymentResult::failed( $booking_id, 'ipn_mismatch' );
		}

		$status   = strtolower( (string) ( $post['payment_status'] ?? '' ) );
		$event_id = (string) ( $post['txn_id'] ?? '' );

		if ( 'completed' === $status ) {
			return PaymentResult::paid(
				$booking_id,
				array(
					'booking_id'             => $booking_id,
					'type'                   => 'paypal_standard_txn',
					'gateway_transaction_id' => $event_id,
					'amount'                 => (string) ( $post['mc_gross'] ?? '0' ),
					'currency'               => (string) ( $post['mc_currency'] ?? $booking->get_currency() ),
					'status'                 => 'completed',
					'raw_response'           => $post,
				),
				$event_id
			);
		}

		if ( 'pending' === $status ) {
			return PaymentResult::pending( $booking_id, (string) ( $post['pending_reason'] ?? '' ), $event_id );
		}

		if ( in_array( $status, array( 'failed', 'denied', 'expired' ), true ) ) {
			return PaymentResult::failed( $booking_id, $status, $event_id );
		}

		if ( in_array( $status, array( 'refunded', 'reversed' ), true ) ) {
			return PaymentResult::refunded(
				$booking_id,
				array(
					'booking_id'             => $booking_id,
					'type'                   => 'paypal_standard_refund',
					'gateway_transaction_id' => $event_id,
					'amount'                 => '-' . ltrim( (string) ( $post['mc_gross'] ?? '0' ), '-' ),
					'currency'               => (string) ( $post['mc_currency'] ?? $booking->get_currency() ),
					'status'                 => 'completed',
					'raw_response'           => $post,
				),
				$event_id
			);
		}

		return PaymentResult::unknown( $booking_id, 'ipn_status_' . $status );
	}

	/**
	 * Verify PayPal Standard IPN with PayPal.
	 *
	 * @param array $raw_post Raw IPN post data.
	 *
	 * @return bool
	 */
	protected function verify_standard_ipn( array $raw_post ): bool {
		$url      = 'live' === $this->get_setting( 'mode', 'sandbox' )
			? 'https://www.paypal.com/cgi-bin/webscr'
			: 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'body'    => 'cmd=_notify-validate&' . http_build_query( $raw_post, '', '&' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return 'VERIFIED' === trim( (string) wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Validate a PayPal Standard IPN against the booking.
	 *
	 * @param array             $post    IPN data.
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return bool
	 */
	protected function validate_standard_ipn_booking( array $post, BookingTableModel $booking ): bool {
		$expected_receiver = $this->get_standard_receiver_email();
		$receiver          = (string) ( $post['receiver_email'] ?? ( $post['business'] ?? '' ) );

		if ( '' === $expected_receiver || 0 !== strcasecmp( $receiver, $expected_receiver ) ) {
			return false;
		}

		if ( $this->format_amount_2( $booking->get_total() ) !== $this->format_amount_2( (string) ( $post['mc_gross'] ?? '0' ) ) ) {
			return false;
		}

		if ( strtoupper( (string) ( $post['mc_currency'] ?? '' ) ) !== strtoupper( $booking->get_currency() ) ) {
			return false;
		}

		if ( (int) ( $post['custom'] ?? 0 ) !== $booking->get_id() ) {
			return false;
		}

		return '' !== (string) ( $post['txn_id'] ?? '' );
	}

	/**
	 * Decode and validate an HTTP response body.
	 *
	 * @param mixed  $response     WP HTTP response.
	 * @param string $error_prefix Error prefix.
	 *
	 * @return array
	 */
	private function decode_response_body( $response, string $error_prefix ): array {
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( $error_prefix );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			throw new RuntimeException( $error_prefix . ':' . $code . ':' . $raw );
		}

		$body = json_decode( $raw, true );
		if ( ! is_array( $body ) ) {
			throw new RuntimeException( $error_prefix . ':invalid_json' );
		}

		return $body;
	}

	/**
	 * Find a link by rel in a PayPal response.
	 *
	 * @param array  $payload PayPal payload.
	 * @param string $rel     Link rel.
	 *
	 * @return string
	 */
	private function find_link( array $payload, string $rel ): string {
		foreach ( (array) ( $payload['links'] ?? array() ) as $link ) {
			if ( $rel === ( $link['rel'] ?? '' ) ) {
				return (string) ( $link['href'] ?? '' );
			}
		}

		return '';
	}

	/**
	 * Extract the first capture from an order.
	 *
	 * @param array $order PayPal order.
	 *
	 * @return array
	 */
	private function extract_capture_from_order( array $order ): array {
		$units = (array) ( $order['purchase_units'] ?? array() );
		foreach ( $units as $unit ) {
			$captures = (array) ( $unit['payments']['captures'] ?? array() );
			if ( ! empty( $captures[0] ) && is_array( $captures[0] ) ) {
				return $captures[0];
			}
		}

		return array();
	}

	/**
	 * Build capture transaction data.
	 *
	 * @param array             $capture Capture payload.
	 * @param BookingTableModel $booking Booking model.
	 * @param array             $raw     Raw response.
	 *
	 * @return array
	 */
	private function build_paypal_capture_transaction_data( array $capture, BookingTableModel $booking, array $raw ): array {
		$capture_id = (string) ( $capture['id'] ?? '' );

		return array(
			'booking_id'             => $booking->get_id(),
			'type'                   => 'paypal_capture',
			'gateway_transaction_id' => $capture_id,
			'gateway_capture_id'     => $capture_id,
			'amount'                 => (string) ( $capture['amount']['value'] ?? $booking->get_total() ),
			'currency'               => (string) ( $capture['amount']['currency_code'] ?? $booking->get_currency() ),
			'status'                 => 'completed',
			'raw_response'           => $raw,
		);
	}

	/**
	 * Find a booking for a PayPal webhook resource.
	 *
	 * @param array $paypal_resource Webhook resource.
	 *
	 * @return BookingTableModel|null
	 */
	private function find_booking_for_paypal_resource( array $paypal_resource ): ?BookingTableModel {
		$booking_id = (int) ( $paypal_resource['custom_id'] ?? 0 );
		if ( $booking_id > 0 ) {
			return $this->bookings->find( $booking_id );
		}

		$order_id = (string) ( $paypal_resource['supplementary_data']['related_ids']['order_id'] ?? '' );
		if ( '' !== $order_id ) {
			return $this->bookings->find_by_gateway_order( 'paypal', $order_id );
		}

		return null;
	}

	/**
	 * Get the Standard receiver email for the active environment.
	 *
	 * @return string
	 */
	private function get_standard_receiver_email(): string {
		return 'live' === $this->get_setting( 'mode', 'sandbox' )
			? (string) $this->get_setting( 'email', '' )
			: (string) $this->get_setting( 'sandbox_email', '' );
	}

	/**
	 * Read a header case-insensitively.
	 *
	 * @param array  $headers Headers.
	 * @param string $name    Header name.
	 *
	 * @return string
	 */
	private function get_header( array $headers, string $name ): string {
		foreach ( $headers as $key => $value ) {
			if ( 0 === strcasecmp( (string) $key, $name ) ) {
				return (string) $value;
			}
		}

		return '';
	}

	/**
	 * Format a decimal amount to PayPal's two-decimal string.
	 *
	 * @param string $amount Amount.
	 *
	 * @return string
	 */
	private function format_amount_2( string $amount ): string {
		if ( function_exists( 'bcadd' ) ) {
			return bcadd( $amount, '0', 2 );
		}

		return number_format( (float) $amount, 2, '.', '' );
	}

	/**
	 * Legacy process callback placeholder.
	 *
	 * @param int|false $booking_id Legacy booking post ID.
	 *
	 * @return array
	 */
	public function process( $booking_id = false ): array {
		return array(
			'status'  => false,
			'message' => __( 'PayPal checkout requires the new booking table checkout flow.', 'wp-events-manager' ),
		);
	}
}
