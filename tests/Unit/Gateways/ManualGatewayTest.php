<?php
/**
 * Unit tests for WPEMS\Gateways\ManualGateway.
 *
 * @package WPEMS\Tests\Unit\Gateways
 */

namespace WPEMS\Tests\Unit\Gateways;

use BadMethodCallException;
use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Gateways\CheckGateway;
use WPEMS\Gateways\ManualGateway;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentGatewayRegistry;
use WPEMS\Payments\PaymentResult;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Gateways\ManualGateway
 */
class ManualGatewayTest extends TestCase {

	/**
	 * Reset registry state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( PaymentGatewayRegistry::class, 'instance', null );
		$this->resetStaticProperty( PaymentGatewayRegistry::class, 'bootstrapped', false );
	}

	/**
	 * @test
	 */
	public function test_is_enabled_reads_manual_enable_option(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'manual_enable', null )
			->andReturn( 'yes' );

		$this->assertTrue( ( new ManualGateway() )->is_enabled() );
	}

	/**
	 * @test
	 */
	public function test_admin_fields_returns_section_with_enable_and_instructions(): void {
		$fields = ( new ManualGateway() )->admin_fields();

		$this->assertSame( 'section_start', $fields[0]['type'] );
		$this->assertSame( 'manual_settings', $fields[0]['id'] );
		$this->assertSame( 'manual_enable', $fields[1]['id'] );
		$this->assertSame( 'yes_no', $fields[1]['type'] );
		$this->assertSame( 'no', $fields[1]['default'] );
		$this->assertSame( 'manual_instructions', $fields[2]['id'] );
		$this->assertSame( 'textarea', $fields[2]['type'] );
		$this->assertSame( 'section_end', $fields[3]['type'] );
	}

	/**
	 * @test
	 */
	public function test_create_checkout_returns_offline_flow(): void {
		$result = ( new ManualGateway() )->create_checkout( BookingTableModel::from_row( array( 'id' => 10 ) ) );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( CheckoutResult::FLOW_OFFLINE, $result->get_flow() );
		$this->assertSame( 'offline', $result->get_payment_mode() );
		$this->assertNull( $result->get_gateway_order_id() );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_returns_unknown(): void {
		$result = ( new ManualGateway() )->handle_return_request( array() );

		$this->assertSame( PaymentResult::STATUS_UNKNOWN, $result->get_status() );
		$this->assertSame( 'manual_has_no_return', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_request_throws(): void {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'manual_no_webhook' );

		( new ManualGateway() )->handle_webhook_request( array() );
	}

	/**
	 * @test
	 */
	public function test_sync_payment_status_returns_unknown(): void {
		$result = ( new ManualGateway() )->sync_payment_status( BookingTableModel::from_row( array( 'id' => 15 ) ) );

		$this->assertSame( PaymentResult::STATUS_UNKNOWN, $result->get_status() );
		$this->assertSame( 15, $result->get_booking_id() );
		$this->assertSame( 'manual_no_sync', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_get_instructions_html_strips_dangerous_tags(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'manual_instructions', null )
			->andReturn( '<p>Pay by bank transfer.</p><script>alert(1)</script>' );

		Functions\expect( 'wp_kses_post' )
			->once()
			->with( '<p>Pay by bank transfer.</p><script>alert(1)</script>' )
			->andReturn( '<p>Pay by bank transfer.</p>' );

		$this->assertSame( '<p>Pay by bank transfer.</p>', ( new ManualGateway() )->get_instructions_html() );
	}

	/**
	 * @test
	 */
	public function test_supports_offline_checkout_and_not_webhook(): void {
		$gateway = new ManualGateway();

		$this->assertTrue( $gateway->supports( 'offline_checkout' ) );
		$this->assertFalse( $gateway->supports( 'webhook' ) );
	}

	/**
	 * @test
	 */
	public function test_registry_bootstrap_registers_manual_and_check_gateways(): void {
		$action_callback = null;

		Functions\expect( 'add_action' )
			->once()
			->with(
				'plugins_loaded',
				Mockery::on(
					function ( $callback ) use ( &$action_callback ): bool {
						$action_callback = $callback;

						return is_callable( $callback );
					}
				),
				20
			)
			->andReturn( true );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'do_action' )->justReturn( true );

		PaymentGatewayRegistry::bootstrap();
		$action_callback();

		$this->assertInstanceOf( ManualGateway::class, PaymentGatewayRegistry::instance()->get( 'manual' ) );
		$this->assertInstanceOf( CheckGateway::class, PaymentGatewayRegistry::instance()->get( 'check' ) );
	}
}
