<?php
/**
 * Unit tests for WPEMS\Payments\PaymentReturnRouter.
 *
 * @package WPEMS\Tests\Unit\Payments
 */

namespace WPEMS\Tests\Unit\Payments;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\AbstractPaymentGateway;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentGatewayRegistry;
use WPEMS\Payments\PaymentResult;
use WPEMS\Payments\PaymentReturnRouter;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Tests\Unit\TestCase;

/**
 * Gateway implementation for return router tests.
 */
class ReturnRouterTestGateway extends AbstractPaymentGateway {

	/**
	 * @var PaymentResult
	 */
	private PaymentResult $result;

	/**
	 * @var array
	 */
	public array $last_request = array();

	/**
	 * Constructor.
	 *
	 * @param PaymentResult $result Result returned by handle_return_request().
	 */
	public function __construct( PaymentResult $result ) {
		$this->id     = 'test_gateway';
		$this->result = $result;
	}

	/**
	 * @inheritDoc
	 */
	public function is_enabled(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function admin_fields(): array {
		return array();
	}

	/**
	 * @inheritDoc
	 */
	public function create_checkout( BookingTableModel $booking ): CheckoutResult {
		return CheckoutResult::offline();
	}

	/**
	 * @inheritDoc
	 */
	public function handle_return_request( array $request ): PaymentResult {
		$this->last_request = $request;

		return $this->result;
	}

	/**
	 * @inheritDoc
	 */
	public function handle_webhook_request( array $request ): PaymentResult {
		return PaymentResult::unknown( 0, 'not_implemented' );
	}

	/**
	 * @inheritDoc
	 */
	public function sync_payment_status( BookingTableModel $booking, string $source = 'manual' ): PaymentResult {
		return PaymentResult::unknown( $booking->get_id(), 'not_implemented' );
	}
}

/**
 * Test double for payment sync.
 */
class ReturnRouterTestPaymentSyncService extends PaymentSyncService {

	/**
	 * @var array
	 */
	public array $applied = array();

	/**
	 * Constructor intentionally skips parent dependencies.
	 */
	public function __construct() {}

	/**
	 * @inheritDoc
	 */
	public function apply_result( BookingTableModel $booking, PaymentResult $result, string $source ): PaymentResult {
		$this->applied[] = array( $booking, $result, $source );

		return $result;
	}
}

/**
 * @covers \WPEMS\Payments\PaymentReturnRouter
 */
class PaymentReturnRouterTest extends TestCase {

	/**
	 * Reset router and registry state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( PaymentReturnRouter::class, 'bootstrapped', false );
		$this->resetStaticProperty( PaymentGatewayRegistry::class, 'instance', null );
		$this->resetStaticProperty( PaymentSyncService::class, 'instance', null );

		Functions\when( 'home_url' )->alias(
			function ( string $path = '/' ): string {
				return 'https://example.test' . $path;
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			function ( array $args, string $url ): string {
				return $url . '?' . http_build_query( $args, '', '&' );
			}
		);
	}

	/**
	 * @test
	 */
	public function test_bootstrap_registers_template_redirect_once(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'template_redirect', array( PaymentReturnRouter::class, 'dispatch' ), 0 )
			->andReturn( true );

		PaymentReturnRouter::bootstrap();
		PaymentReturnRouter::bootstrap();

		$this->addToAssertionCount( 1 );
	}

	/**
	 * @test
	 */
	public function test_handle_request_ignores_non_gateway_request(): void {
		$this->assertNull( PaymentReturnRouter::handle_request( array( 'p' => 123 ) ) );
	}

	/**
	 * @test
	 */
	public function test_handle_request_sanitizes_and_delegates_to_gateway(): void {
		$gateway = new ReturnRouterTestGateway( PaymentResult::pending( 123, 'waiting' ) );
		PaymentGatewayRegistry::instance()->register( $gateway );
		$this->mockBookingLookup( 123 );
		$sync = $this->mockPaymentSyncApply();

		$url = PaymentReturnRouter::handle_request(
			array(
				'wpems_gateway' => 'Test_Gateway',
				'action'        => 'RETURN',
				'booking_id'    => '123abc',
				'session_id'    => '<b>sess_123</b>',
			)
		);

		$this->assertSame( 'test_gateway', $gateway->last_request['wpems_gateway'] );
		$this->assertSame( 'return', $gateway->last_request['action'] );
		$this->assertSame( 123, $gateway->last_request['booking_id'] );
		$this->assertSame( 'sess_123', $gateway->last_request['session_id'] );
		$this->assertSame( 'https://example.test/?wpems=order-received&payment_status=pending&booking_id=123&payment_message=waiting', $url );
		$this->assertCount( 1, $sync->applied );
		$this->assertSame( 'return', $sync->applied[0][2] );
	}

	/**
	 * @test
	 */
	public function test_handle_request_redirects_unknown_gateway_to_order_received(): void {
		$url = PaymentReturnRouter::handle_request(
			array(
				'wpems_gateway' => 'missing',
				'booking_id'    => 456,
			)
		);

		$this->assertSame( 'https://example.test/?wpems=order-received&payment_status=unknown&booking_id=456&payment_message=gateway_not_found', $url );
	}

	/**
	 * Mock booking lookup through BookingRepository.
	 *
	 * @param int $booking_id Booking ID.
	 *
	 * @return void
	 */
	private function mockBookingLookup( int $booking_id ): void {
		global $wpdb;

		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT booking' );
		$wpdb->shouldReceive( 'get_row' )->once()->with( 'SELECT booking', ARRAY_A )->andReturn(
			array(
				'id'                  => $booking_id,
				'event_id'            => 10,
				'user_id'             => 20,
				'qty'                 => 1,
				'subtotal'            => '10.0000',
				'discount_total'      => '0.0000',
				'tax_rate'            => '0.0000',
				'tax_total'           => '0.0000',
				'total'               => '10.0000',
				'currency'            => 'USD',
				'payment_method'      => 'test_gateway',
				'payment_mode'        => 'test',
				'gateway_order_id'    => 'ORDER-123',
				'status'              => 'ea-processing',
				'payment_status'      => 'pending',
				'hold_expires_at_gmt' => null,
				'idempotency_key'     => 'key',
				'created_at_gmt'      => '2026-01-01 00:00:00',
				'updated_at_gmt'      => '2026-01-01 00:00:00',
			)
		);
	}

	/**
	 * Mock PaymentSyncService::instance()->apply_result().
	 *
	 * @return ReturnRouterTestPaymentSyncService
	 */
	private function mockPaymentSyncApply(): ReturnRouterTestPaymentSyncService {
		$sync = new ReturnRouterTestPaymentSyncService();
		$this->resetStaticProperty( PaymentSyncService::class, 'instance', $sync );

		return $sync;
	}
}
