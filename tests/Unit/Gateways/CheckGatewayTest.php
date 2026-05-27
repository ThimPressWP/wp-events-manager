<?php
/**
 * Unit tests for WPEMS\Gateways\CheckGateway.
 *
 * @package WPEMS\Tests\Unit\Gateways
 */

namespace WPEMS\Tests\Unit\Gateways;

use BadMethodCallException;
use Brain\Monkey\Functions;
use WPEMS\Gateways\CheckGateway;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentResult;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Gateways\CheckGateway
 */
class CheckGatewayTest extends TestCase {

	/**
	 * @test
	 */
	public function test_id_is_check(): void {
		$gateway = new CheckGateway();

		$this->assertSame( 'check', $gateway->id );
		$this->assertSame( 'check', $gateway->get_id() );
	}

	/**
	 * @test
	 */
	public function test_is_enabled_reads_check_enable_option(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'check_enable', null )
			->andReturn( 'yes' );

		$this->assertTrue( ( new CheckGateway() )->is_enabled() );
	}

	/**
	 * @test
	 */
	public function test_admin_fields_uses_check_prefix(): void {
		$fields = ( new CheckGateway() )->admin_fields();

		$this->assertSame( 'section_start', $fields[0]['type'] );
		$this->assertSame( 'check_settings', $fields[0]['id'] );
		$this->assertSame( 'check_enable', $fields[1]['id'] );
		$this->assertSame( 'yes_no', $fields[1]['type'] );
		$this->assertSame( 'no', $fields[1]['default'] );
		$this->assertSame( 'check_instructions', $fields[2]['id'] );
		$this->assertSame( 'textarea', $fields[2]['type'] );
		$this->assertSame( 'section_end', $fields[3]['type'] );
	}

	/**
	 * @test
	 */
	public function test_create_checkout_returns_offline_flow(): void {
		$result = ( new CheckGateway() )->create_checkout( BookingTableModel::from_row( array( 'id' => 20 ) ) );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( CheckoutResult::FLOW_OFFLINE, $result->get_flow() );
		$this->assertSame( 'offline', $result->get_payment_mode() );
		$this->assertNull( $result->get_gateway_order_id() );
	}

	/**
	 * @test
	 */
	public function test_handle_return_request_returns_unknown(): void {
		$result = ( new CheckGateway() )->handle_return_request( array() );

		$this->assertSame( PaymentResult::STATUS_UNKNOWN, $result->get_status() );
		$this->assertSame( 'check_has_no_return', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_handle_webhook_request_throws(): void {
		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'check_no_webhook' );

		( new CheckGateway() )->handle_webhook_request( array() );
	}

	/**
	 * @test
	 */
	public function test_sync_payment_status_returns_unknown(): void {
		$result = ( new CheckGateway() )->sync_payment_status( BookingTableModel::from_row( array( 'id' => 25 ) ) );

		$this->assertSame( PaymentResult::STATUS_UNKNOWN, $result->get_status() );
		$this->assertSame( 25, $result->get_booking_id() );
		$this->assertSame( 'check_no_sync', $result->get_message() );
	}

	/**
	 * @test
	 */
	public function test_get_instructions_html_strips_dangerous_tags(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'check_instructions', null )
			->andReturn( '<p>Mail a check.</p><script>alert(1)</script>' );

		Functions\expect( 'wp_kses_post' )
			->once()
			->with( '<p>Mail a check.</p><script>alert(1)</script>' )
			->andReturn( '<p>Mail a check.</p>' );

		$this->assertSame( '<p>Mail a check.</p>', ( new CheckGateway() )->get_instructions_html() );
	}

	/**
	 * @test
	 */
	public function test_supports_offline_checkout_and_not_webhook(): void {
		$gateway = new CheckGateway();

		$this->assertTrue( $gateway->supports( 'offline_checkout' ) );
		$this->assertFalse( $gateway->supports( 'webhook' ) );
	}
}
