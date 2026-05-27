<?php
/**
 * Unit tests for Stripe gateway.
 *
 * @package WPEMS\Tests\Unit\Gateways
 */

namespace WPEMS\Tests\Unit\Gateways;

use Mockery;
use Stripe\Charge;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use WPEMS\Gateways\StripeGateway;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * Fake Stripe sessions service.
 */
class FakeStripeSessionsService {
	/** @var array */
	public array $created_params = array();

	/** @var array */
	public array $created_options = array();

	/** @var Session|null */
	public ?Session $created_session = null;

	/** @var Session|null */
	public ?Session $retrieved_session = null;

	/**
	 * Create session.
	 *
	 * @param array $params  Params.
	 * @param array $options Options.
	 *
	 * @return Session
	 */
	public function create( array $params, array $options ): Session {
		$this->created_params  = $params;
		$this->created_options = $options;

		return $this->created_session ?: Session::constructFrom(
			array(
				'id'  => 'sess_123',
				'url' => 'https://stripe.test/checkout',
			)
		);
	}

	/**
	 * Retrieve session.
	 *
	 * @param string $session_id Session ID.
	 * @param array  $params     Params.
	 *
	 * @return Session
	 */
	public function retrieve( string $session_id, array $params ): Session {
		return $this->retrieved_session ?: TestableStripeGateway::paidSession();
	}
}

/**
 * Fake Stripe client.
 */
class FakeStripeClient {
	/** @var object */
	public object $checkout;

	/** @var FakeStripeSessionsService */
	public FakeStripeSessionsService $sessions;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->sessions = new FakeStripeSessionsService();
		$this->checkout = (object) array( 'sessions' => $this->sessions );
	}
}

/**
 * Testable Stripe gateway.
 */
class TestableStripeGateway extends StripeGateway {
	/** @var FakeStripeClient */
	public FakeStripeClient $fake_client;

	/** @var Event|null */
	public ?Event $event = null;

	/** @var bool */
	public bool $throw_signature = false;

	/**
	 * Constructor.
	 *
	 * @param BookingRepository $bookings Booking repository.
	 */
	public function __construct( BookingRepository $bookings ) {
		parent::__construct( $bookings );
		$this->fake_client = new FakeStripeClient();
	}

	/**
	 * @inheritDoc
	 */
	protected function client() {
		return $this->fake_client;
	}

	/**
	 * @inheritDoc
	 */
	protected function verify_webhook( array $headers, string $payload ): Event {
		if ( $this->throw_signature ) {
			throw new SignatureVerificationException( 'bad signature' );
		}

		return $this->event ?: Event::constructFrom( array( 'id' => 'evt_empty', 'type' => 'unknown', 'data' => array( 'object' => (object) array() ) ) );
	}

	/**
	 * Expose amount conversion.
	 *
	 * @param string $amount   Amount.
	 * @param string $currency Currency.
	 *
	 * @return int
	 */
	public function expose_amount_to_stripe_int( string $amount, string $currency ): int {
		return $this->amount_to_stripe_int( $amount, $currency );
	}

	/**
	 * Build a paid session.
	 *
	 * @return Session
	 */
	public static function paidSession(): Session {
		$charge = Charge::constructFrom( array( 'id' => 'ch_123' ) );
		$pi     = PaymentIntent::constructFrom(
			array(
				'id'              => 'pi_123',
				'amount_received' => 2500,
				'currency'        => 'usd',
			)
		);
		$pi->latest_charge = $charge;

		$session                 = Session::constructFrom(
			array(
				'id'                 => 'sess_123',
				'url'                => 'https://stripe.test/checkout',
				'payment_status'     => 'paid',
				'status'             => 'complete',
				'client_reference_id' => '100',
				'metadata'           => array( 'booking_id' => '100' ),
			)
		);
		$session->payment_intent = $pi;

		return $session;
	}
}

/**
 * @covers \WPEMS\Gateways\StripeGateway
 */
class StripeGatewayTest extends TestCase {

	/**
	 * @test
	 */
	public function test_create_checkout_returns_session_url(): void {
		$this->mockUrls();
		$gateway = $this->makeGateway();

		$result = $gateway->create_checkout( $this->booking() );

		$this->assertSame( CheckoutResult::FLOW_REDIRECT, $result->get_flow() );
		$this->assertSame( 'https://stripe.test/checkout', $result->get_redirect_url() );
		$this->assertSame( 'sess_123', $result->get_gateway_order_id() );
		$this->assertSame( 'stripe_checkout', $result->get_payment_mode() );
	}

	/**
	 * @test
	 */
	public function test_create_checkout_uses_idempotency_key(): void {
		$this->mockUrls();
		$gateway = $this->makeGateway();

		$gateway->create_checkout( $this->booking() );

		$this->assertSame( 'wpems-session-100', $gateway->fake_client->sessions->created_options['idempotency_key'] );
		$this->assertSame( 2500, $gateway->fake_client->sessions->created_params['line_items'][0]['price_data']['unit_amount'] );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_paid_returns_paid_with_pi_and_charge_ids(): void {
		$gateway = $this->makeGatewayWithBooking( $this->booking() );

		$result = $gateway->handle_return_request(
			array(
				'booking_id'  => 100,
				'session_id'  => 'sess_123',
				'action'      => 'return',
			)
		);

		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 'pi_123', $result->get_transaction_data()['gateway_payment_intent_id'] );
		$this->assertSame( 'ch_123', $result->get_transaction_data()['gateway_charge_id'] );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_unpaid_open_returns_pending(): void {
		$gateway = $this->makeGatewayWithBooking( $this->booking() );
		$gateway->fake_client->sessions->retrieved_session = Session::constructFrom(
			array(
				'id'             => 'sess_open',
				'payment_status' => 'unpaid',
				'status'         => 'open',
			)
		);

		$result = $gateway->handle_return_request( array( 'booking_id' => 100, 'session_id' => 'sess_open' ) );

		$this->assertSame( PaymentResult::STATUS_PENDING, $result->get_status() );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_unpaid_expired_returns_failed(): void {
		$gateway = $this->makeGatewayWithBooking( $this->booking() );
		$gateway->fake_client->sessions->retrieved_session = Session::constructFrom(
			array(
				'id'             => 'sess_expired',
				'payment_status' => 'unpaid',
				'status'         => 'expired',
			)
		);

		$result = $gateway->handle_return_request( array( 'booking_id' => 100, 'session_id' => 'sess_expired' ) );

		$this->assertSame( PaymentResult::STATUS_FAILED, $result->get_status() );
		$this->assertSame( 'session_expired', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_invalid_signature_throws(): void {
		$gateway                  = $this->makeGateway();
		$gateway->throw_signature = true;

		$this->expectException( SignatureVerificationException::class );

		$gateway->handle_webhook_request( array( 'raw_payload' => '{}' ) );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_session_completed_returns_paid_with_event_id(): void {
		$gateway        = $this->makeGatewayWithBooking( $this->booking() );
		$gateway->event = Event::constructFrom(
			array(
				'id'   => 'evt_123',
				'type' => 'checkout.session.completed',
				'data' => array( 'object' => TestableStripeGateway::paidSession() ),
			)
		);

		$result = $gateway->handle_webhook_request( array( 'raw_payload' => '{}' ) );

		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 'evt_123', $result->get_gateway_event_id() );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_payment_failed_returns_failed(): void {
		$gateway = $this->makeGatewayWithBooking( $this->booking() );
		$pi      = PaymentIntent::constructFrom(
			array(
				'id'                 => 'pi_fail',
				'metadata'           => array( 'booking_id' => '100' ),
				'last_payment_error' => array( 'message' => 'card declined' ),
			)
		);
		$gateway->event = Event::constructFrom(
			array(
				'id'   => 'evt_fail',
				'type' => 'payment_intent.payment_failed',
				'data' => array( 'object' => $pi ),
			)
		);

		$result = $gateway->handle_webhook_request( array( 'raw_payload' => '{}' ) );

		$this->assertSame( PaymentResult::STATUS_FAILED, $result->get_status() );
		$this->assertSame( 'evt_fail', $result->get_gateway_event_id() );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_charge_refunded_returns_refunded(): void {
		$gateway = $this->makeGatewayWithBooking( $this->booking() );
		$charge  = Charge::constructFrom(
			array(
				'id'              => 'ch_123',
				'amount_refunded' => 2500,
				'currency'        => 'usd',
				'metadata'        => array( 'booking_id' => '100' ),
				'refunds'         => array(
					'data' => array(
						array( 'id' => 're_123' ),
					),
				),
			)
		);
		$gateway->event = Event::constructFrom(
			array(
				'id'   => 'evt_refund',
				'type' => 'charge.refunded',
				'data' => array( 'object' => $charge ),
			)
		);

		$result = $gateway->handle_webhook_request( array( 'raw_payload' => '{}' ) );

		$this->assertSame( PaymentResult::STATUS_REFUNDED, $result->get_status() );
		$this->assertSame( 'evt_refund', $result->get_gateway_event_id() );
		$this->assertSame( '-25.0000', $result->get_transaction_data()['amount'] );
	}

	/**
	 * @test
	 */
	public function test_amount_to_stripe_int_jpy_returns_yen_count(): void {
		$this->assertSame( 1234, $this->makeGateway()->expose_amount_to_stripe_int( '1234.0000', 'JPY' ) );
	}

	/**
	 * @test
	 */
	public function test_amount_to_stripe_int_usd_multiplies_by_100(): void {
		$this->assertSame( 1234, $this->makeGateway()->expose_amount_to_stripe_int( '12.3400', 'USD' ) );
	}

	/**
	 * Mock URL functions.
	 *
	 * @return void
	 */
	private function mockUrls(): void {
		\Brain\Monkey\Functions\when( 'add_query_arg' )->alias(
			function ( $args, $path ) {
				return $path . '?' . http_build_query( $args, '', '&' );
			}
		);
		\Brain\Monkey\Functions\when( 'home_url' )->alias(
			function ( $path = '' ) {
				return 'https://example.test' . $path;
			}
		);
	}

	/**
	 * Make gateway.
	 *
	 * @return TestableStripeGateway
	 */
	private function makeGateway(): TestableStripeGateway {
		return new TestableStripeGateway( Mockery::mock( BookingRepository::class ) );
	}

	/**
	 * Make gateway with booking repo.
	 *
	 * @param BookingTableModel $booking Booking.
	 *
	 * @return TestableStripeGateway
	 */
	private function makeGatewayWithBooking( BookingTableModel $booking ): TestableStripeGateway {
		$bookings = Mockery::mock( BookingRepository::class );
		$bookings->shouldReceive( 'find' )->with( 100 )->andReturn( $booking );

		return new TestableStripeGateway( $bookings );
	}

	/**
	 * Make booking.
	 *
	 * @return BookingTableModel
	 */
	private function booking(): BookingTableModel {
		return BookingTableModel::from_row(
			array(
				'id'               => 100,
				'total'            => '25.0000',
				'currency'         => 'USD',
				'payment_method'   => 'stripe',
				'payment_mode'     => 'stripe_checkout',
				'gateway_order_id' => 'sess_123',
			)
		);
	}
}
