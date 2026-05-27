<?php
/**
 * Unit tests for PayPal Standard/IPN gateway mode.
 *
 * @package WPEMS\Tests\Unit\Gateways
 */

namespace WPEMS\Tests\Unit\Gateways;

use Mockery;
use RuntimeException;
use WPEMS\Gateways\PaypalGateway;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * Testable PayPal Standard gateway.
 */
class TestablePaypalStandardGateway extends PaypalGateway {
	/** @var bool */
	public bool $ipn_verified = true;

	/**
	 * Expose standard checkout URL.
	 *
	 * @param BookingTableModel $booking Booking.
	 *
	 * @return string
	 */
	public function expose_build_standard_checkout_url( BookingTableModel $booking ): string {
		return $this->build_standard_checkout_url( $booking );
	}

	/**
	 * Expose real IPN verification.
	 *
	 * @param array $post IPN post.
	 *
	 * @return bool
	 */
	public function expose_verify_standard_ipn( array $post ): bool {
		return parent::verify_standard_ipn( $post );
	}

	/**
	 * @inheritDoc
	 */
	protected function verify_standard_ipn( array $raw_post ): bool {
		return $this->ipn_verified;
	}
}

/**
 * @covers \WPEMS\Gateways\PaypalGateway
 */
class PaypalGatewayStandardTest extends TestCase {

	/**
	 * @test
	 */
	public function test_build_standard_checkout_url_uses_sandbox_email_in_sandbox(): void {
		$this->mockOptions(
			array(
				'paypal_mode'          => 'sandbox',
				'paypal_sandbox_email' => 'sandbox@example.com',
			)
		);
		$this->mockUrls();

		$url = $this->makeGateway()->expose_build_standard_checkout_url( $this->booking() );

		parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $params );

		$this->assertStringStartsWith( 'https://www.sandbox.paypal.com/cgi-bin/webscr?', $url );
		$this->assertSame( 'sandbox@example.com', $params['business'] );
		$this->assertSame( '100', $params['custom'] );
		$this->assertSame( '25.00', $params['amount'] );
	}

	/**
	 * @test
	 */
	public function test_build_standard_checkout_url_throws_when_email_missing(): void {
		$this->mockOptions( array( 'paypal_mode' => 'sandbox' ) );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'paypal_standard_email_missing' );

		$this->makeGateway()->expose_build_standard_checkout_url( $this->booking() );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_for_standard_returns_pending(): void {
		$gateway = $this->makeGatewayWithBooking( $this->booking( array( 'payment_mode' => 'paypal_standard' ) ) );

		$result = $gateway->handle_return_request(
			array(
				'booking_id' => 100,
				'action'     => 'return',
			)
		);

		$this->assertSame( PaymentResult::STATUS_PENDING, $result->get_status() );
		$this->assertSame( 'paypal_standard_awaiting_ipn', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_handle_standard_ipn_failed_verification_returns_unknown(): void {
		$gateway               = $this->makeGateway();
		$gateway->ipn_verified = false;

		$result = $gateway->handle_standard_ipn( $this->ipnPayload() );

		$this->assertSame( PaymentResult::STATUS_UNKNOWN, $result->get_status() );
		$this->assertSame( 'ipn_verify_failed', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_handle_standard_ipn_completed_returns_paid_with_txn_id(): void {
		$this->mockOptions(
			array(
				'paypal_mode'          => 'sandbox',
				'paypal_sandbox_email' => 'sandbox@example.com',
			)
		);
		$gateway = $this->makeGatewayWithBooking( $this->booking() );

		$result = $gateway->handle_standard_ipn( $this->ipnPayload() );

		$this->assertSame( PaymentResult::STATUS_PAID, $result->get_status() );
		$this->assertSame( 'TXN-1', $result->get_gateway_event_id() );
		$this->assertSame( 'paypal_standard_txn', $result->get_transaction_data()['type'] );
		$this->assertSame( 'TXN-1', $result->get_transaction_data()['gateway_transaction_id'] );
	}

	/**
	 * @test
	 */
	public function test_handle_standard_ipn_amount_mismatch_returns_failed(): void {
		$this->mockOptions(
			array(
				'paypal_mode'          => 'sandbox',
				'paypal_sandbox_email' => 'sandbox@example.com',
			)
		);
		$gateway = $this->makeGatewayWithBooking( $this->booking() );
		$payload = $this->ipnPayload( array( 'mc_gross' => '1.00' ) );

		$result = $gateway->handle_standard_ipn( $payload );

		$this->assertSame( PaymentResult::STATUS_FAILED, $result->get_status() );
		$this->assertSame( 'ipn_mismatch', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_handle_standard_ipn_currency_mismatch_returns_failed(): void {
		$this->mockOptions(
			array(
				'paypal_mode'          => 'sandbox',
				'paypal_sandbox_email' => 'sandbox@example.com',
			)
		);
		$gateway = $this->makeGatewayWithBooking( $this->booking() );
		$payload = $this->ipnPayload( array( 'mc_currency' => 'EUR' ) );

		$result = $gateway->handle_standard_ipn( $payload );

		$this->assertSame( PaymentResult::STATUS_FAILED, $result->get_status() );
	}

	/**
	 * @test
	 */
	public function test_handle_standard_ipn_wrong_receiver_returns_failed(): void {
		$this->mockOptions(
			array(
				'paypal_mode'          => 'sandbox',
				'paypal_sandbox_email' => 'sandbox@example.com',
			)
		);
		$gateway = $this->makeGatewayWithBooking( $this->booking() );
		$payload = $this->ipnPayload( array( 'receiver_email' => 'other@example.com' ) );

		$result = $gateway->handle_standard_ipn( $payload );

		$this->assertSame( PaymentResult::STATUS_FAILED, $result->get_status() );
	}

	/**
	 * @test
	 */
	public function test_handle_standard_ipn_idempotent_with_same_txn_id(): void {
		$this->mockOptions(
			array(
				'paypal_mode'          => 'sandbox',
				'paypal_sandbox_email' => 'sandbox@example.com',
			)
		);
		$gateway = $this->makeGatewayWithBooking( $this->booking(), 2 );

		$first  = $gateway->handle_standard_ipn( $this->ipnPayload() );
		$second = $gateway->handle_standard_ipn( $this->ipnPayload() );

		$this->assertSame( $first->get_gateway_event_id(), $second->get_gateway_event_id() );
		$this->assertSame( 'TXN-1', $second->get_gateway_event_id() );
	}

	/**
	 * @test
	 */
	public function test_verify_standard_ipn_posts_cmd_notify_validate(): void {
		$this->mockOptions( array( 'paypal_mode' => 'sandbox' ) );
		$gateway = new class( Mockery::mock( BookingRepository::class ), Mockery::mock( PaymentTransactionRepository::class ) ) extends PaypalGateway {
			public function expose_verify( array $post ): bool {
				return parent::verify_standard_ipn( $post );
			}
		};

		\Brain\Monkey\Functions\expect( 'wp_remote_post' )
			->once()
			->with(
				'https://www.sandbox.paypal.com/cgi-bin/webscr',
				Mockery::on(
					function ( $args ): bool {
						return isset( $args['body'] ) && 0 === strpos( $args['body'], 'cmd=_notify-validate&' );
					}
				)
			)
			->andReturn( array( 'body' => 'VERIFIED' ) );
		\Brain\Monkey\Functions\expect( 'wp_remote_retrieve_body' )->once()->andReturn( 'VERIFIED' );

		$this->assertTrue( $gateway->expose_verify( $this->ipnPayload() ) );
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
	 * Mock URL helpers.
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
	 * @return TestablePaypalStandardGateway
	 */
	private function makeGateway(): TestablePaypalStandardGateway {
		return new TestablePaypalStandardGateway( Mockery::mock( BookingRepository::class ), Mockery::mock( PaymentTransactionRepository::class ) );
	}

	/**
	 * Make gateway with booking.
	 *
	 * @param BookingTableModel $booking Booking.
	 * @param int               $times   Expected lookup count.
	 *
	 * @return TestablePaypalStandardGateway
	 */
	private function makeGatewayWithBooking( BookingTableModel $booking, int $times = 1 ): TestablePaypalStandardGateway {
		$bookings = Mockery::mock( BookingRepository::class );
		$bookings->shouldReceive( 'find' )->times( $times )->with( 100 )->andReturn( $booking );

		return new TestablePaypalStandardGateway( $bookings, Mockery::mock( PaymentTransactionRepository::class ) );
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
					'payment_mode'     => 'paypal_standard',
					'gateway_order_id' => 'wpems-std-100-abc',
				),
				$overrides
			)
		);
	}

	/**
	 * IPN payload.
	 *
	 * @param array $overrides Overrides.
	 *
	 * @return array
	 */
	private function ipnPayload( array $overrides = array() ): array {
		return array_merge(
			array(
				'payment_status' => 'Completed',
				'receiver_email' => 'sandbox@example.com',
				'mc_gross'       => '25.00',
				'mc_currency'    => 'USD',
				'custom'         => '100',
				'txn_id'         => 'TXN-1',
			),
			$overrides
		);
	}
}
