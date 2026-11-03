<?php
/**
 * PayPal gateway.
 *
 * @package WPEMS/Gateways
 */

namespace WPEMS\Gateways;

use WPEMS\Models\BookingPostModel;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\WPEMS_Abstract_Payment_Gateway' ) ) {
	require_once dirname( __DIR__ ) . '/abstracts/class-wpems-abstract-payment-gateway.php';
}

class PaypalGateway extends \WPEMS_Abstract_Payment_Gateway {

	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = 'paypal';

	/**
	 * Gateway title.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Gateway icon.
	 *
	 * @var string
	 */
	public $_icon = '';

	/**
	 * PayPal account email.
	 *
	 * @var string
	 */
	protected $paypal_email = '';

	/**
	 * PayPal base URL.
	 *
	 * @var string
	 */
	protected $paypal_url = '';

	/**
	 * PayPal checkout URL.
	 *
	 * @var string
	 */
	protected $paypal_payment_url = '';

	/**
	 * Whether gateway is enabled.
	 *
	 * @var bool
	 */
	protected static $enable = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->title = __( 'PayPal', 'wp-events-manager' );
		$this->icon  = WPEMS_INC_URI . 'Gateways/' . $this->id . '/' . $this->id . '.png';
		$this->_icon = $this->icon;

		$this->configure_urls();

		parent::__construct();

		add_action( 'init', array( $this, 'payment_validation' ), 99 );
	}

	/**
	 * Configure PayPal URLs and email from settings.
	 *
	 * @return void
	 */
	private function configure_urls() {
		$this->paypal_url         = 'https://www.paypal.com/';
		$this->paypal_payment_url = 'https://www.paypal.com/cgi-bin/webscr';
		$this->paypal_email       = sanitize_email( wpems_get_option( 'paypal_email', '' ) );

		if ( wpems_get_option( 'paypal_sandbox_mode' ) ) {
			$this->paypal_url         = 'https://www.sandbox.paypal.com/';
			$this->paypal_payment_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			$this->paypal_email       = sanitize_email( wpems_get_option( 'paypal_sanbox_email', '' ) );
		}
	}

	/**
	 * Check gateway availability.
	 *
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Check gateway enabled state.
	 *
	 * @return bool
	 */
	public function is_enable() {
		self::$enable = ! empty( $this->paypal_email ) && 'yes' === wpems_get_option( 'paypal_enable' );

		return apply_filters( 'tp_event_enable_paypal_payment', self::$enable );
	}

	/**
	 * Validate PayPal return and IPN callbacks.
	 *
	 * @return void
	 */
	public function payment_validation() {
		$get_data  = $this->sanitize_superglobal( $_GET );
		$post_data = $this->sanitize_superglobal( $_POST );

		if ( ! empty( $get_data['event-auth-paypal-payment'] ) ) {
			$this->handle_return_request( $get_data );
			return;
		}

		if ( empty( $post_data['txn_type'] ) || 'web_accept' !== $post_data['txn_type'] ) {
			return;
		}

		if ( empty( $post_data['payment_status'] ) || empty( $post_data['custom'] ) ) {
			return;
		}

		if ( ! $this->verify_paypal_ipn( $_POST ) ) {
			return;
		}

		$transaction_subject = json_decode( $post_data['custom'], true );
		if ( empty( $transaction_subject['booking_id'] ) ) {
			return;
		}

		$booking = BookingPostModel::find( absint( $transaction_subject['booking_id'] ) );
		if ( ! $booking ) {
			return;
		}

		if ( ! $this->is_valid_ipn_payment( $post_data, $booking, $transaction_subject ) ) {
			return;
		}

		$payment_status = strtolower( $post_data['payment_status'] );
		if ( 'pending' === $payment_status ) {
			$booking->update_status( 'ea-processing' );
		} elseif ( 'completed' === $payment_status ) {
			$booking->update_status( 'ea-completed' );
		}
	}

	/**
	 * Validate PayPal IPN values against the local booking.
	 *
	 * @param array            $post_data           Sanitized IPN data.
	 * @param BookingPostModel $booking             Booking model.
	 * @param array            $transaction_subject Decoded custom payload.
	 *
	 * @return bool
	 */
	private function is_valid_ipn_payment( array $post_data, BookingPostModel $booking, array $transaction_subject ): bool {
		$receiver_email = $this->get_ipn_receiver_email( $post_data );
		if ( ! $receiver_email || ! hash_equals( strtolower( $this->paypal_email ), strtolower( $receiver_email ) ) ) {
			return false;
		}

		if ( ! empty( $transaction_subject['user_id'] ) && absint( $transaction_subject['user_id'] ) !== $booking->get_user_id() ) {
			return false;
		}

		if ( $booking->get_payment_id() && 'paypal' !== $booking->get_payment_id() ) {
			return false;
		}

		$payment_amount = isset( $post_data['mc_gross'] ) ? $post_data['mc_gross'] : '';
		if ( $this->normalize_paypal_amount( $payment_amount ) !== $this->normalize_paypal_amount( $booking->get_price() ) ) {
			return false;
		}

		$payment_currency = isset( $post_data['mc_currency'] ) ? strtoupper( sanitize_text_field( $post_data['mc_currency'] ) ) : '';
		$booking_currency = strtoupper( $booking->get_currency() ? $booking->get_currency() : wpems_get_currency() );

		return $payment_currency && hash_equals( $booking_currency, $payment_currency );
	}

	/**
	 * Get the PayPal receiver email from IPN fields.
	 *
	 * @param array $post_data Sanitized IPN data.
	 *
	 * @return string
	 */
	private function get_ipn_receiver_email( array $post_data ): string {
		if ( ! empty( $post_data['receiver_email'] ) ) {
			return sanitize_email( $post_data['receiver_email'] );
		}

		if ( ! empty( $post_data['business'] ) ) {
			return sanitize_email( $post_data['business'] );
		}

		return '';
	}

	/**
	 * Normalize a PayPal amount for exact two-decimal comparisons.
	 *
	 * @param mixed $amount Raw amount.
	 *
	 * @return string
	 */
	private function normalize_paypal_amount( $amount ): string {
		return number_format( (float) $amount, 2, '.', '' );
	}

	/**
	 * Handle PayPal return/cancel request.
	 *
	 * @param array $get_data Sanitized GET data.
	 *
	 * @return void
	 */
	private function handle_return_request( array $get_data ) {
		$result = $get_data['event-auth-paypal-payment'];
		if ( ! in_array( $result, array( 'completed', 'cancel' ), true ) ) {
			return;
		}

		if ( empty( $get_data['tp-event-paypal-nonce'] ) || ! wp_verify_nonce( $get_data['tp-event-paypal-nonce'], 'tp-event-paypal-nonce' ) ) {
			return;
		}

		if ( 'completed' === $result ) {
			wpems_add_notice( 'success', __( 'Payment is completed. We will send you email when payment status is completed', 'wp-events-manager' ) );
		} elseif ( 'cancel' === $result ) {
			wpems_add_notice( 'success', __( 'Booking is cancel.', 'wp-events-manager' ) );
		}

		wp_safe_redirect( wpems_account_url() );
		exit();
	}

	/**
	 * Gateway settings fields.
	 *
	 * @return array
	 */
	public function admin_fields() {
		$prefix        = 'thimpress_events_';
		$paypal_enable = wpems_get_option( 'paypal_enable' );

		return apply_filters(
			'tp_event_paypal_admin_fields',
			array(
				array(
					'type'  => 'section_start',
					'id'    => 'paypal_settings',
					'title' => __( 'Paypal Settings', 'wp-events-manager' ),
					'desc'  => esc_html__( 'Make payment via Paypal', 'wp-events-manager' ),
				),
				array(
					'type'    => 'yes_no',
					'title'   => __( 'Enable', 'wp-events-manager' ),
					'id'      => $prefix . 'paypal_enable',
					'default' => 'no',
					'desc'    => apply_filters( 'tp_event_filter_enable_paypal_gateway', '' ),
				),
				array(
					'type'    => 'text',
					'title'   => __( 'Paypal email', 'wp-events-manager' ),
					'id'      => $prefix . 'paypal_email',
					'default' => '',
					'class'   => 'paypal-production-email' . ( 'no' === $paypal_enable ? ' hide-if-js' : '' ),
				),
				array(
					'type'    => 'checkbox',
					'title'   => __( 'Sandbox mode', 'wp-events-manager' ),
					'id'      => $prefix . 'paypal_sandbox_mode',
					'default' => false,
					'class'   => 'paypal-sandbox-mode' . ( 'no' === $paypal_enable ? ' hide-if-js' : '' ),
				),
				array(
					'type'    => 'text',
					'title'   => __( 'Paypal Sandbox email', 'wp-events-manager' ),
					'id'      => $prefix . 'paypal_sanbox_email',
					'default' => '',
					'class'   => 'paypal-sandbox-email' . ( 'no' === $paypal_enable ? ' hide-if-js' : '' ),
				),
				array(
					'type' => 'section_end',
					'id'   => 'paypal_settings',
				),
			)
		);
	}

	/**
	 * Get PayPal item name.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return string
	 */
	public function get_item_name( $booking_id = null ) {
		$booking_id = absint( $booking_id );
		if ( ! $booking_id ) {
			return '';
		}

		$booking = BookingPostModel::find( $booking_id );
		if ( ! $booking ) {
			return '';
		}

		return sprintf(
			'%s(%s)',
			$booking->post_title,
			wpems_format_price( $booking->get_price(), $booking->get_currency() )
		);
	}

	/**
	 * Build checkout URL.
	 *
	 * @param int|false $booking_id Booking ID.
	 *
	 * @return string
	 */
	public function checkout_url( $booking_id = false ) {
		$booking_id = absint( $booking_id );
		if ( ! $booking_id ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'Booking ID is not exists!', 'wp-events-manager' ),
				)
			);
		}

		$booking = BookingPostModel::find( $booking_id );
		if ( ! $booking ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => __( 'Booking ID is not exists!', 'wp-events-manager' ),
				)
			);
		}

		$user  = get_userdata( $booking->get_user_id() );
		$email = $user ? sanitize_email( $user->user_email ) : '';
		$nonce = wp_create_nonce( 'tp-event-paypal-nonce' );

		$query = array(
			'cmd'           => '_xclick',
			'amount'        => $booking->get_price(),
			'quantity'      => '1',
			'business'      => $this->paypal_email,
			'item_name'     => $this->get_item_name( $booking_id ),
			'currency_code' => wpems_get_currency(),
			'notify_url'    => home_url(),
			'no_note'       => '1',
			'shipping'      => '0',
			'email'         => $email,
			'rm'            => '2',
			'no_shipping'   => '1',
			'return'        => add_query_arg(
				array(
					'event-auth-paypal-payment' => 'completed',
					'tp-event-paypal-nonce'     => $nonce,
				),
				wpems_account_url()
			),
			'cancel_return' => add_query_arg(
				array(
					'event-auth-paypal-payment' => 'cancel',
					'tp-event-paypal-nonce'     => $nonce,
				),
				wpems_account_url()
			),
			'custom'        => wp_json_encode(
				array(
					'booking_id' => $booking_id,
					'user_id'    => $booking->get_user_id(),
				)
			),
		);

		$query = apply_filters( 'tp_event_paypal_payment_params', $query );

		return $this->paypal_payment_url . '?' . http_build_query( $query, '', '&' );
	}

	/**
	 * Process payment.
	 *
	 * @param int|false $booking_id Booking ID.
	 *
	 * @return array
	 */
	public function process( $booking_id = false ) {
		if ( ! $this->is_enable() ) {
			return array(
				'status'  => false,
				'message' => __( 'Email Business PayPal is invalid. Please contact administrator to setup PayPal email.', 'wp-events-manager' ),
			);
		}

		return array(
			'status' => true,
			'url'    => $this->checkout_url( $booking_id ),
		);
	}

	/**
	 * Sanitize request array.
	 *
	 * @param array $data Raw data.
	 *
	 * @return array
	 */
	private function sanitize_superglobal( array $data ): array {
		$sanitized = array();

		foreach ( $data as $key => $value ) {
			$key = sanitize_key( $key );
			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_superglobal( $value );
				continue;
			}

			$sanitized[ $key ] = sanitize_text_field( wp_unslash( $value ) );
		}

		return $sanitized;
	}

	/**
	 * Verify PayPal IPN by posting back original unslashed payload.
	 *
	 * @param array $post_data Raw POST data.
	 *
	 * @return bool
	 */
	private function verify_paypal_ipn( array $post_data ): bool {
		$raw_post = wp_unslash( $post_data );

		$body = array_merge( array( 'cmd' => '_notify-validate' ), $raw_post );

		// Use site configuration instead of POST data to determine verification endpoint
		$url = wpems_get_option( 'paypal_sandbox_mode' )
			? 'https://www.sandbox.paypal.com/cgi-bin/webscr'
			: 'https://www.paypal.com/cgi-bin/webscr';

		$response = wp_safe_remote_post(
			$url,
			array(
				'body'        => $body,
				'timeout'     => 60,
				'httpversion' => '1.1',
				'compress'    => false,
				'decompress'  => false,
				'user-agent'  => 'WP Events Manager',
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		return 'verified' === strtolower( trim( wp_remote_retrieve_body( $response ) ) );
	}
}
