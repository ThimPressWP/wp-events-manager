<?php
/**
 * Unit tests for PayPal REST gateway mode.
 *
 * @package WPEMS\Tests\Unit\Gateways
 */

namespace WPEMS\Tests\Unit\Gateways;

use Mockery;
use RuntimeException;
use WPEMS\Gateways\PaypalGateway;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * Testable PayPal gateway.
 */
class TestablePaypalRestGateway extends PaypalGateway {
	/** @var array */
	public array $rest_order = array();

	/** @var array */
	public array $capture = array();

	/** @var array */
	public array $order = array();

	/** @var RuntimeException|null */
	public ?RuntimeException $capture_exception = null;

	/** @var bool */
	public bool $webhook_valid = true;

	/**
	 * Expose active mode.
	 *
	 * @return string
	 */
	public function expose_get_active_mode(): string {
		return $this->get_active_mode();
	}

	/**
	 * Expose map capture.
	 *
	 * @param array             $capture Capture.
	 * @param BookingTableModel $booking Booking.
	 *
	 * @return PaymentResult
	 */
	public function expose_map_rest_capture_to_payment_result( array $capture, BookingTableModel $booking ): PaymentResult {
		return $this->map_rest_capture_to_payment_result( $capture, $booking );
	}

	/**
	 * @inheritDoc
	 */
	protected function create_rest_order( BookingTableModel $booking ): array {
		if ( isset( $this->rest_order['throw'] ) ) {
			throw new RuntimeException( 'paypal_order_create_failed' );
		}

		return $this->rest_order;
	}

	/**
	 * @inheritDoc
	 */
	protected function capture_rest_order( string $order_id, string $idempotency_key ): array {
		if ( null !== $this->capture_exception ) {
			throw $this->capture_exception;
		}

		return $this->capture;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_rest_order( string $order_id ): array {
		return $this->order;
	}

	/**
	 * @inheritDoc
	 */
	protected function verify_rest_webhook( array $headers, string $payload ): bool {
		return $this->webhook_valid;
	}
}

/**
 * @covers \WPEMS\Gateways\PaypalGateway
 */
class PaypalGatewayRestTest extends TestCase {

	/**
	 * @test
	 */
	public function test_get_active_mode_auto_with_creds_returns_rest(): void {
		$this->mockOptions(
			array(
				'paypal_integration_mode'   => 'auto',
				'paypal_rest_client_id'     => 'client',
				'paypal_rest_client_secret' => 'secret',
			)
		);

		$this->assertSame( 'rest', $this->makeGateway()->expose_get_active_mode() );
	}

	/**
	 * @test
	 */
	public function test_get_active_mode_auto_without_creds_returns_standard(): void {
		$this->mockOptions( array( 'paypal_integration_mode' => 'auto' ) );

		$this->assertSame( 'standard', $this->makeGateway()->expose_get_active_mode() );
	}

	/**
	 * @test
	 */
	public function test_create_checkout_rest_returns_approve_url(): void {
		$this->mockOptions( array( 'paypal_integration_mode' => 'rest' ) );
		$gateway             = $this->makeGateway();
		$gateway->rest_order = array(
			'id'    => 'ORDER-123',
			'links' => array(
				array(
					'rel'  => 'approve',
					'href' => 'https://paypal.test/approve',
				),
			),
		);

		$result = $gateway->create_checkout( $this->booking() );

		$this->assertSame( CheckoutResult::FLOW_REDIRECT, $result->get_flow() );
		$this->assertSame( 'https://paypal.test/approve', $result->get_redirect_url() );
		$this->assertSame( 'ORDER-123', $result->get_gateway_order_id() );
		$this->assertSame( 'paypal_rest', $result->get_payment_mode() );
	}

	/**
	 * @test
	 */
	public function test_create_checkout_rest_throws_when_order_create_fails(): void {
		$this->mockOptions( array( 'paypal_integration_mode' => 'rest' ) );
		$gateway             = $this->makeGateway();
		$gateway->rest_order = array( 'throw' => true );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'paypal_order_create_failed' );

		$gateway->create_checkout( $this->booking() );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_captures_order(): void {
		$booking          = $this->booking( array( 'gateway_order_id' => 'ORDER-123' ) );
		$gateway          = $this->makeGatewayWithBooking( $booking );
		$gateway->capture = $this->capture();

		$result = $gateway->handle_return_request(
			array(
				'booking_id' => 100,
				'action'     => 'return',
			)
		);

		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 'CAP-123', $result->get_transaction_data()['gateway_capture_id'] );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_falls_back_to_get_when_already_captured(): void {
		$booking                    = $this->booking( array( 'gateway_order_id' => 'ORDER-123' ) );
		$gateway                    = $this->makeGatewayWithBooking( $booking );
		$gateway->capture_exception = new RuntimeException( '422 ORDER_ALREADY_CAPTURED' );
		$gateway->order             = $this->completedOrder();

		$result = $gateway->handle_return_request(
			array(
				'booking_id' => 100,
				'action'     => 'return',
			)
		);

		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 'CAP-123', $result->get_gateway_event_id() );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_cancel_action_returns_cancelled(): void {
		$gateway = $this->makeGatewayWithBooking( $this->booking() );

		$result = $gateway->handle_return_request(
			array(
				'booking_id' => 100,
				'action'     => 'cancel',
			)
		);

		$this->assertSame( PaymentResult::STATUS_CANCELLED, $result->get_status() );
		$this->assertSame( 'user_cancelled_return', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_sync_payment_status_maps_completed_to_paid(): void {
		$gateway        = $this->makeGateway();
		$gateway->order = $this->completedOrder();

		$result = $gateway->sync_payment_status( $this->booking( array( 'gateway_order_id' => 'ORDER-123' ) ) );

		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 'paypal_capture', $result->get_transaction_data()['type'] );
	}

	/**
	 * @test
	 */
	public function test_sync_payment_status_maps_approved_to_pending(): void {
		$gateway        = $this->makeGateway();
		$gateway->order = array( 'status' => 'APPROVED' );

		$result = $gateway->sync_payment_status( $this->booking( array( 'gateway_order_id' => 'ORDER-123' ) ) );

		$this->assertSame( PaymentResult::STATUS_PENDING, $result->get_status() );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_invalid_signature_throws(): void {
		$gateway                = $this->makeGateway();
		$gateway->webhook_valid = false;

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'invalid_signature' );

		$gateway->handle_webhook_request( array( 'raw_payload' => '{}' ) );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_capture_completed_returns_paid_with_event_id(): void {
		$booking = $this->booking();
		$gateway = $this->makeGatewayWithBooking( $booking );
		$payload = wp_json_encode(
			array(
				'id'         => 'WH-1',
				'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
				'resource'   => array(
					'id'        => 'CAP-123',
					'custom_id' => '100',
					'amount'    => array(
						'value'         => '25.00',
						'currency_code' => 'USD',
					),
				),
			)
		);

		$result = $gateway->handle_webhook_request( array( 'raw_payload' => $payload ) );

		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 'WH-1', $result->get_gateway_event_id() );
	}

	/**
	 * @test
	 */
	public function test_map_capture_payment_result_includes_capture_id_in_transaction_data(): void {
		$gateway = $this->makeGateway();
		$result  = $gateway->expose_map_rest_capture_to_payment_result( $this->capture(), $this->booking() );

		$this->assertSame( 'CAP-123', $result->get_transaction_data()['gateway_capture_id'] );
		$this->assertSame( 'CAP-123', $result->get_transaction_data()['gateway_transaction_id'] );
	}

	/**
	 * Mock settings.
	 *
	 * @param array $settings Settings.
	 *
	 * @return void
	 */
	private function mockOptions( array $settings ): void {
		\Brain\Monkey\Functions\when( 'get_option' )->alias(
			function ( $key, $default = null ) use ( $settings ) {
				return $settings[ $key ] ?? $default;
			}
		);
	}

	/**
	 * Make gateway.
	 *
	 * @return TestablePaypalRestGateway
	 */
	private function makeGateway(): TestablePaypalRestGateway {
		return new TestablePaypalRestGateway( Mockery::mock( BookingRepository::class ), Mockery::mock( PaymentTransactionRepository::class ) );
	}

	/**
	 * Make gateway with booking repo.
	 *
	 * @param BookingTableModel $booking Booking.
	 *
	 * @return TestablePaypalRestGateway
	 */
	private function makeGatewayWithBooking( BookingTableModel $booking ): TestablePaypalRestGateway {
		$bookings = Mockery::mock( BookingRepository::class );
		$bookings->shouldReceive( 'find' )->with( 100 )->andReturn( $booking );

		return new TestablePaypalRestGateway( $bookings, Mockery::mock( PaymentTransactionRepository::class ) );
	}

	/**
	 * Make booking.
	 *
	 * @param array $overrides Overrides.
	 *
	 * @return BookingTableModel
	 */
	private function booking( array $overrides = array() ): BookingTableModel {
		return BookingTableModel::from_row(
			array_merge(
				array(
					'id'               => 100,
					'total'            => '25.0000',
					'currency'         => 'USD',
					'payment_method'   => 'paypal',
					'payment_mode'     => 'paypal_rest',
					'gateway_order_id' => null,
				),
				$overrides
			)
		);
	}

	/**
	 * Capture payload.
	 *
	 * @return array
	 */
	private function capture(): array {
		return array(
			'id'     => 'CAP-123',
			'status' => 'COMPLETED',
			'amount' => array(
				'value'         => '25.00',
				'currency_code' => 'USD',
			),
		);
	}

	/**
	 * Completed order payload.
	 *
	 * @return array
	 */
	private function completedOrder(): array {
		return array(
			'id'             => 'ORDER-123',
			'status'         => 'COMPLETED',
			'purchase_units' => array(
				array(
					'payments' => array(
						'captures' => array( $this->capture() ),
					),
				),
			),
		);
	}
}
