<?php
/**
 * Unit tests for WPEMS\Payments\CheckoutResult.
 *
 * @package WPEMS\Tests\Unit\Payments
 */

namespace WPEMS\Tests\Unit\Payments;

use InvalidArgumentException;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Payments\CheckoutResult
 */
class CheckoutResultTest extends TestCase {

	/** @test */
	public function test_redirect_factory_validates_required_fields(): void {
		$result = CheckoutResult::redirect(
			'https://checkout.stripe.com/sess_123',
			'sess_123',
			'stripe_checkout'
		);

		$this->assertTrue( $result->is_success() );
		$this->assertSame( CheckoutResult::FLOW_REDIRECT, $result->get_flow() );
		$this->assertSame( 'https://checkout.stripe.com/sess_123', $result->get_redirect_url() );
		$this->assertSame( 'sess_123', $result->get_gateway_order_id() );
		$this->assertSame( 'stripe_checkout', $result->get_payment_mode() );
		$this->assertSame( '', $result->get_error_code() );
		$this->assertSame( '', $result->get_error_message() );
	}

	/** @test */
	public function test_offline_factory_produces_success(): void {
		$result = CheckoutResult::offline( 'offline' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( CheckoutResult::FLOW_OFFLINE, $result->get_flow() );
		$this->assertSame( '', $result->get_redirect_url() );
		$this->assertNull( $result->get_gateway_order_id() );
		$this->assertSame( 'offline', $result->get_payment_mode() );
	}

	/** @test */
	public function test_offline_factory_default_mode(): void {
		$result = CheckoutResult::offline();

		$this->assertSame( 'offline', $result->get_payment_mode() );
	}

	/** @test */
	public function test_error_factory_produces_failure(): void {
		$result = CheckoutResult::error( 'gateway_error', 'Something went wrong' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( '', $result->get_flow() );
		$this->assertSame( '', $result->get_redirect_url() );
		$this->assertNull( $result->get_gateway_order_id() );
		$this->assertSame( '', $result->get_payment_mode() );
		$this->assertSame( 'gateway_error', $result->get_error_code() );
		$this->assertSame( 'Something went wrong', $result->get_error_message() );
	}

	/** @test */
	public function test_redirect_rejects_empty_url(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Redirect URL must not be empty' );

		CheckoutResult::redirect( '', 'order_123', 'stripe_checkout' );
	}

	/** @test */
	public function test_redirect_rejects_empty_order_id(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Gateway order ID must not be empty' );

		CheckoutResult::redirect( 'https://example.com', '', 'stripe_checkout' );
	}

	/** @test */
	public function test_redirect_rejects_invalid_payment_mode(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Invalid payment mode "bogus"' );

		CheckoutResult::redirect( 'https://example.com', 'order_123', 'bogus' );
	}

	/** @test */
	public function test_redirect_accepts_paypal_modes(): void {
		$rest = CheckoutResult::redirect( 'https://paypal.com/rest', 'ord_1', 'paypal_rest' );
		$this->assertSame( 'paypal_rest', $rest->get_payment_mode() );

		$standard = CheckoutResult::redirect( 'https://paypal.com/std', 'ord_2', 'paypal_standard' );
		$this->assertSame( 'paypal_standard', $standard->get_payment_mode() );
	}
}
