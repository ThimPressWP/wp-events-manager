<?php
/**
 * Stripe Checkout payment gateway.
 *
 * @package WPEMS\Gateways
 * @since   3.0.0
 */

namespace WPEMS\Gateways;

defined( 'ABSPATH' ) || exit;

use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Webhook;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\AbstractPaymentGateway;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;

/**
 * Stripe hosted Checkout gateway.
 */
class StripeGateway extends AbstractPaymentGateway {

	/**
	 * Gateway feature flags.
	 *
	 * @var string[]
	 */
	protected const FEATURES = array( 'redirect_checkout', 'webhook', 'payment_sync', 'refund' );

	/**
	 * Stripe API version pinned for this gateway implementation.
	 */
	private const API_VERSION = '2024-06-20';

	/**
	 * Gateway identifier.
	 *
	 * @var string
	 */
	public $id = 'stripe';

	/**
	 * Booking repository.
	 *
	 * @var BookingRepository
	 */
	private BookingRepository $bookings;

	/**
	 * Stripe client.
	 *
	 * @var StripeClient|null
	 */
	private ?StripeClient $stripe_client = null;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository|null $bookings Booking repository.
	 */
	public function __construct( ?BookingRepository $bookings = null ) {
		$this->bookings = $bookings ? $bookings : new BookingRepository();
	}

	/**
	 * Get gateway title (translated lazily to avoid early textdomain load).
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Stripe', 'wp-events-manager' );
	}

	/**
	 * Get gateway description (translated lazily to avoid early textdomain load).
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Pay securely with Stripe Checkout.', 'wp-events-manager' );
	}

	/**
	 * Whether Stripe is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === $this->get_setting( 'enable', 'no' );
	}

	/**
	 * Whether Stripe can currently accept payment.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->is_enabled() && '' !== $this->get_setting( 'secret_key', '' );
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
				'id'    => 'stripe_settings',
				'title' => __( 'Stripe', 'wp-events-manager' ),
				'desc'  => sprintf(
					__( 'Webhook URL: %s', 'wp-events-manager' ),
					esc_url( home_url( '/wpems-webhook/stripe/' ) )
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
				'id'      => $this->setting_id( 'mode' ),
				'title'   => __( 'Mode', 'wp-events-manager' ),
				'options' => array(
					'test' => __( 'Test', 'wp-events-manager' ),
					'live' => __( 'Live', 'wp-events-manager' ),
				),
				'default' => 'test',
			),
			array(
				'type'  => 'text',
				'id'    => $this->setting_id( 'publishable_key' ),
				'title' => __( 'Publishable key', 'wp-events-manager' ),
			),
			array(
				'type'  => 'password',
				'id'    => $this->setting_id( 'secret_key' ),
				'title' => __( 'Secret key', 'wp-events-manager' ),
			),
			array(
				'type'  => 'password',
				'id'    => $this->setting_id( 'webhook_secret' ),
				'title' => __( 'Webhook signing secret', 'wp-events-manager' ),
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
				'title'   => __( 'Poll cadence (min)', 'wp-events-manager' ),
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
				'id'   => 'stripe_settings',
			),
		);
	}

	/**
	 * Create a hosted Stripe Checkout Session.
	 *
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return CheckoutResult
	 */
	public function create_checkout( BookingTableModel $booking ): CheckoutResult {
		$line_items = array(
			array(
				'price_data' => array(
					'currency'     => strtolower( $booking->get_currency() ),
					'product_data' => array(
						'name' => sprintf( 'Event booking #%d', $booking->get_id() ),
					),
					'unit_amount'  => $this->amount_to_stripe_int( $booking->get_total(), $booking->get_currency() ),
				),
				'quantity'   => 1,
			),
		);

		try {
			$session = $this->client()->checkout->sessions->create(
				array(
					'mode'                => 'payment',
					'line_items'          => $line_items,
					'success_url'         => home_url(
						add_query_arg(
							array(
								'wpems_gateway' => 'stripe',
								'action'        => 'return',
								'booking_id'    => $booking->get_id(),
								'session_id'    => '{CHECKOUT_SESSION_ID}',
							),
							'/'
						)
					),
					'cancel_url'          => home_url(
						add_query_arg(
							array(
								'wpems_gateway' => 'stripe',
								'action'        => 'cancel',
								'booking_id'    => $booking->get_id(),
							),
							'/'
						)
					),
					'client_reference_id' => (string) $booking->get_id(),
					'metadata'            => array(
						'booking_id' => (string) $booking->get_id(),
					),
				),
				array(
					'idempotency_key' => 'wpems-session-' . $booking->get_id(),
				)
			);
		} catch ( ApiErrorException $e ) {
			throw new RuntimeException( $e->getMessage(), 0, $e );
		}

		return CheckoutResult::redirect( (string) $session->url, (string) $session->id, 'stripe_checkout' );
	}

	/**
	 * Handle return from Stripe Checkout.
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

		if ( 'cancel' === ( $request['action'] ?? '' ) ) {
			return PaymentResult::cancelled( $booking_id, 'user_cancelled' );
		}

		$session_id = (string) ( $request['session_id'] ?? '' );
		if ( '' === $session_id ) {
			$session_id = (string) $booking->get_gateway_order_id();
		}

		if ( '' === $session_id ) {
			return PaymentResult::unknown( $booking_id, 'no_session_id' );
		}

		$session = $this->retrieve_checkout_session( $session_id );
		return $this->map_session_to_payment_result( $session, $booking );
	}

	/**
	 * Handle a Stripe webhook request.
	 *
	 * @param array $request Request data with headers and raw_payload.
	 *
	 * @return PaymentResult
	 */
	public function handle_webhook_request( array $request ): PaymentResult {
		$event = $this->verify_webhook(
			(array) ( $request['headers'] ?? array() ),
			(string) ( $request['raw_payload'] ?? '' )
		);

		$type   = (string) $event->type;
		$object = $event->data->object;

		if ( in_array( $type, array( 'checkout.session.completed', 'checkout.session.async_payment_succeeded' ), true ) ) {
			$booking = $this->booking_from_stripe_object( $object );
			if ( null === $booking ) {
				return PaymentResult::unknown( 0, 'booking_missing' );
			}

			return $this->with_gateway_event_id( $this->map_session_to_payment_result( $object, $booking ), (string) $event->id );
		}

		if ( 'checkout.session.async_payment_failed' === $type ) {
			$booking = $this->booking_from_stripe_object( $object );
			return null === $booking
				? PaymentResult::unknown( 0, 'booking_missing' )
				: PaymentResult::failed( $booking->get_id(), 'async_payment_failed', (string) $event->id );
		}

		if ( 'payment_intent.payment_failed' === $type ) {
			$booking = $this->booking_from_stripe_object( $object );
			$message = (string) ( $object->last_payment_error->message ?? 'failed' );

			return null === $booking
				? PaymentResult::unknown( 0, 'booking_missing' )
				: PaymentResult::failed( $booking->get_id(), $message, (string) $event->id );
		}

		if ( 'charge.refunded' === $type ) {
			$booking = $this->booking_from_stripe_object( $object );
			if ( null === $booking ) {
				return PaymentResult::unknown( 0, 'booking_missing' );
			}

			$amount = isset( $object->amount_refunded )
				? $this->amount_from_stripe_int( (int) $object->amount_refunded, (string) $object->currency )
				: '0';

			return PaymentResult::refunded(
				$booking->get_id(),
				array(
					'booking_id'             => $booking->get_id(),
					'type'                   => 'stripe_refund',
					'gateway_transaction_id' => (string) ( $object->refunds->data[0]->id ?? $object->id ),
					'gateway_charge_id'      => (string) $object->id,
					'amount'                 => '-' . ltrim( $amount, '-' ),
					'currency'               => strtoupper( (string) $object->currency ),
					'status'                 => 'completed',
					'raw_response'           => method_exists( $object, 'toArray' ) ? $object->toArray() : (array) $object,
				),
				(string) $event->id
			);
		}

		return PaymentResult::unknown( 0, 'ignored_event' );
	}

	/**
	 * Sync Stripe payment status.
	 *
	 * @param BookingTableModel $booking Booking model.
	 * @param string            $source  Sync source.
	 *
	 * @return PaymentResult
	 */
	public function sync_payment_status( BookingTableModel $booking, string $source = 'manual' ): PaymentResult {
		$session_id = $booking->get_gateway_order_id();
		if ( null === $session_id || '' === $session_id ) {
			return PaymentResult::unknown( $booking->get_id(), 'no_session_id' );
		}

		$session = $this->retrieve_checkout_session( $session_id );
		return $this->map_session_to_payment_result( $session, $booking );
	}

	/**
	 * Get Stripe client.
	 *
	 * @return StripeClient
	 */
	protected function client() {
		if ( null === $this->stripe_client ) {
			$this->stripe_client = new StripeClient(
				array(
					'api_key'        => $this->get_setting( 'secret_key', '' ),
					'stripe_version' => self::API_VERSION,
				)
			);
		}

		return $this->stripe_client;
	}

	/**
	 * Retrieve a Checkout Session with expanded PaymentIntent and Charge.
	 *
	 * @param string $session_id Stripe Checkout Session ID.
	 *
	 * @return Session
	 */
	protected function retrieve_checkout_session( string $session_id ): Session {
		return $this->client()->checkout->sessions->retrieve(
			$session_id,
			array(
				'expand' => array( 'payment_intent', 'payment_intent.latest_charge' ),
			)
		);
	}

	/**
	 * Verify webhook signature.
	 *
	 * @param array  $headers Headers.
	 * @param string $payload Raw payload.
	 *
	 * @return Event
	 */
	protected function verify_webhook( array $headers, string $payload ): Event {
		$sig    = $this->get_header( $headers, 'Stripe-Signature' );
		$secret = (string) $this->get_setting( 'webhook_secret', '' );

		return Webhook::constructEvent( $payload, $sig, $secret );
	}

	/**
	 * Map a Checkout Session to a payment result.
	 *
	 * @param Session|object    $session Checkout Session.
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return PaymentResult
	 */
	protected function map_session_to_payment_result( $session, BookingTableModel $booking ): PaymentResult {
		$payment_status = (string) ( $session->payment_status ?? '' );
		$status         = (string) ( $session->status ?? '' );

		if ( 'paid' === $payment_status || 'no_payment_required' === $payment_status || ( 'unpaid' === $payment_status && 'complete' === $status ) ) {
			return PaymentResult::paid( $booking->get_id(), $this->build_stripe_transaction_data( $session, $booking ) );
		}

		if ( 'unpaid' === $payment_status && 'open' === $status ) {
			return PaymentResult::pending( $booking->get_id(), 'stripe_session_open' );
		}

		if ( 'unpaid' === $payment_status && 'expired' === $status ) {
			return PaymentResult::failed( $booking->get_id(), 'session_expired' );
		}

		return PaymentResult::unknown( $booking->get_id(), 'stripe_session_' . $payment_status . '_' . $status );
	}

	/**
	 * Whether a currency is zero-decimal in Stripe.
	 *
	 * @param string $code Currency code.
	 *
	 * @return bool
	 */
	protected function zero_decimal_currency( string $code ): bool {
		$zero = array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF' );

		return in_array( strtoupper( $code ), $zero, true );
	}

	/**
	 * Convert a decimal amount to Stripe integer minor units.
	 *
	 * @param string $amount   Decimal amount.
	 * @param string $currency Currency code.
	 *
	 * @return int
	 */
	protected function amount_to_stripe_int( string $amount, string $currency ): int {
		$multiplier = $this->zero_decimal_currency( $currency ) ? '1' : '100';

		if ( function_exists( 'bcmul' ) ) {
			return (int) bcmul( $amount, $multiplier, 0 );
		}

		return (int) floor( (float) $amount * (float) $multiplier );
	}

	/**
	 * Convert Stripe minor units to decimal string.
	 *
	 * @param int    $amount   Stripe amount.
	 * @param string $currency Currency code.
	 *
	 * @return string
	 */
	private function amount_from_stripe_int( int $amount, string $currency ): string {
		if ( $this->zero_decimal_currency( $currency ) ) {
			return (string) $amount;
		}

		if ( function_exists( 'bcdiv' ) ) {
			return bcdiv( (string) $amount, '100', 4 );
		}

		return number_format( $amount / 100, 4, '.', '' );
	}

	/**
	 * Build transaction data for a paid Stripe session.
	 *
	 * @param Session|object    $session Checkout Session.
	 * @param BookingTableModel $booking Booking model.
	 *
	 * @return array
	 */
	private function build_stripe_transaction_data( $session, BookingTableModel $booking ): array {
		$payment_intent = $session->payment_intent ?? null;
		$charge         = is_object( $payment_intent ) ? ( $payment_intent->latest_charge ?? null ) : null;
		$pi_id          = is_object( $payment_intent ) ? (string) ( $payment_intent->id ?? '' ) : (string) $payment_intent;
		$charge_id      = is_object( $charge ) ? (string) ( $charge->id ?? '' ) : ( is_string( $charge ) ? $charge : null );
		$currency       = is_object( $payment_intent ) ? (string) ( $payment_intent->currency ?? $booking->get_currency() ) : $booking->get_currency();
		$amount         = is_object( $payment_intent ) && isset( $payment_intent->amount_received )
			? $this->amount_from_stripe_int( (int) $payment_intent->amount_received, $currency )
			: $booking->get_total();

		return array(
			'booking_id'                => $booking->get_id(),
			'type'                      => 'stripe_payment_intent',
			'gateway_transaction_id'    => $pi_id,
			'gateway_payment_intent_id' => $pi_id,
			'gateway_charge_id'         => $charge_id,
			'amount'                    => $amount,
			'currency'                  => strtoupper( $currency ),
			'status'                    => 'completed',
			'raw_response'              => method_exists( $session, 'toArray' ) ? $session->toArray() : (array) $session,
		);
	}

	/**
	 * Find booking from a Stripe object metadata/client reference.
	 *
	 * @param object $stripe_object Stripe object.
	 *
	 * @return BookingTableModel|null
	 */
	private function booking_from_stripe_object( $stripe_object ): ?BookingTableModel {
		$booking_id = (int) ( $stripe_object->metadata['booking_id'] ?? 0 );
		if ( ! $booking_id && isset( $stripe_object->metadata->booking_id ) ) {
			$booking_id = (int) $stripe_object->metadata->booking_id;
		}
		if ( ! $booking_id && isset( $stripe_object->client_reference_id ) ) {
			$booking_id = (int) $stripe_object->client_reference_id;
		}

		return $booking_id > 0 ? $this->bookings->find( $booking_id ) : null;
	}

	/**
	 * Clone a payment result with a gateway event ID.
	 *
	 * @param PaymentResult $result   Result.
	 * @param string        $event_id Event ID.
	 *
	 * @return PaymentResult
	 */
	private function with_gateway_event_id( PaymentResult $result, string $event_id ): PaymentResult {
		switch ( $result->get_status() ) {
			case PaymentResult::STATUS_PAID:
				return PaymentResult::paid( $result->get_booking_id(), $result->get_transaction_data(), $event_id );
			case PaymentResult::STATUS_PENDING:
				return PaymentResult::pending( $result->get_booking_id(), $result->get_message(), $event_id );
			case PaymentResult::STATUS_FAILED:
				return PaymentResult::failed( $result->get_booking_id(), $result->get_message(), $event_id );
			case PaymentResult::STATUS_CANCELLED:
				return PaymentResult::cancelled( $result->get_booking_id(), $result->get_message(), $event_id );
			case PaymentResult::STATUS_REFUNDED:
				return PaymentResult::refunded( $result->get_booking_id(), $result->get_transaction_data(), $event_id );
			default:
				return $result;
		}
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
}
