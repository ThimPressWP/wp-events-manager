<?php
/**
 * Unit tests for WPEMS\Payments\PaymentWebhookRouter.
 *
 * @package WPEMS\Tests\Unit\Payments
 */

namespace WPEMS\Tests\Unit\Payments;

use Brain\Monkey\Functions;
use WPEMS\Payments\PaymentWebhookRouter;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Payments\PaymentWebhookRouter
 */
class PaymentWebhookRouterTest extends TestCase {

	/**
	 * Reset bootstrap state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( PaymentWebhookRouter::class, 'bootstrapped', false );
	}

	/**
	 * @test
	 */
	public function test_bootstrap_registers_hooks_once(): void {
		Functions\expect( 'add_action' )
			->once()
			->with( 'init', array( PaymentWebhookRouter::class, 'register_rewrite_rules' ) )
			->andReturn( true );
		Functions\expect( 'add_filter' )
			->once()
			->with( 'query_vars', array( PaymentWebhookRouter::class, 'add_query_vars' ) )
			->andReturn( true );
		Functions\expect( 'add_action' )
			->once()
			->with( 'parse_request', array( PaymentWebhookRouter::class, 'dispatch' ) )
			->andReturn( true );

		PaymentWebhookRouter::bootstrap();
		PaymentWebhookRouter::bootstrap();

		$this->addToAssertionCount( 3 );
	}

	/**
	 * @test
	 */
	public function test_register_rewrite_rules_adds_payment_webhook_endpoints(): void {
		Functions\expect( 'add_rewrite_rule' )
			->once()
			->with( '^wpems-webhook/paypal-rest/?$', 'index.php?wpems_paypal_rest=1', 'top' )
			->andReturn( true );
		Functions\expect( 'add_rewrite_rule' )
			->once()
			->with( '^wpems-webhook/paypal-ipn/?$', 'index.php?wpems_paypal_ipn=1', 'top' )
			->andReturn( true );
		Functions\expect( 'add_rewrite_rule' )
			->once()
			->with( '^wpems-webhook/stripe/?$', 'index.php?wpems_stripe_webhook=1', 'top' )
			->andReturn( true );

		PaymentWebhookRouter::register_rewrite_rules();

		$this->addToAssertionCount( 3 );
	}

	/**
	 * @test
	 */
	public function test_add_query_vars_adds_each_webhook_var_once(): void {
		$vars = PaymentWebhookRouter::add_query_vars( array( 'existing', 'wpems_stripe_webhook' ) );

		$this->assertSame(
			array( 'existing', 'wpems_stripe_webhook', 'wpems_paypal_rest', 'wpems_paypal_ipn' ),
			$vars
		);
	}

	/**
	 * @test
	 */
	public function test_handle_wp_request_ignores_non_webhook_request(): void {
		$wp = (object) array( 'query_vars' => array( 'p' => 123 ) );

		$this->assertNull( PaymentWebhookRouter::handle_wp_request( $wp ) );
	}
}
