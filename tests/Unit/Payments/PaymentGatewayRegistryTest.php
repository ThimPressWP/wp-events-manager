<?php
/**
 * Unit tests for WPEMS\Payments\PaymentGatewayRegistry.
 *
 * @package WPEMS\Tests\Unit\Payments
 */

namespace WPEMS\Tests\Unit\Payments;

use Brain\Monkey\Functions;
use InvalidArgumentException;
use Mockery;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\AbstractPaymentGateway;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentGatewayRegistry;
use WPEMS\Payments\PaymentResult;
use WPEMS\Tests\Unit\TestCase;

/**
 * Gateway implementation for registry tests.
 */
class RegistryTestGateway extends AbstractPaymentGateway {

	/**
	 * @var bool
	 */
	private bool $enabled;

	/**
	 * @var bool
	 */
	private bool $available;

	/**
	 * Constructor.
	 *
	 * @param string $id        Gateway ID.
	 * @param bool   $enabled   Whether the gateway is enabled.
	 * @param bool   $available Whether the gateway is available.
	 */
	public function __construct( string $id, bool $enabled = true, bool $available = true ) {
		$this->id        = $id;
		$this->title     = ucfirst( $id );
		$this->enabled   = $enabled;
		$this->available = $available;
	}

	/**
	 * @inheritDoc
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * @inheritDoc
	 */
	public function is_available(): bool {
		return $this->available;
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
		return PaymentResult::unknown( 0, 'not_implemented' );
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
 * No-argument gateway implementation for bootstrap tests.
 */
class RegistryBootstrapGateway extends RegistryTestGateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( 'bootstrap_gateway' );
	}
}

/**
 * @covers \WPEMS\Payments\PaymentGatewayRegistry
 */
class PaymentGatewayRegistryTest extends TestCase {

	/**
	 * Reset singleton state.
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
	public function test_instance_returns_same_object_across_calls(): void {
		$this->assertSame( PaymentGatewayRegistry::instance(), PaymentGatewayRegistry::instance() );
	}

	/**
	 * @test
	 */
	public function test_register_stores_gateway_by_id(): void {
		$gateway  = new RegistryTestGateway( 'manual' );
		$registry = PaymentGatewayRegistry::instance();

		$registry->register( $gateway );

		$this->assertTrue( $registry->has( 'manual' ) );
		$this->assertSame( $gateway, $registry->get( 'manual' ) );
	}

	/**
	 * @test
	 */
	public function test_register_rejects_empty_id(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Payment gateway ID must not be empty.' );

		PaymentGatewayRegistry::instance()->register( new RegistryTestGateway( '' ) );
	}

	/**
	 * @test
	 */
	public function test_register_overwrites_existing(): void {
		$first    = new RegistryTestGateway( 'manual', true, true );
		$second   = new RegistryTestGateway( 'manual', false, false );
		$registry = PaymentGatewayRegistry::instance();

		$registry->register( $first );
		$registry->register( $second );

		$this->assertCount( 1, $registry->all() );
		$this->assertSame( $second, $registry->get( 'manual' ) );
	}

	/**
	 * @test
	 */
	public function test_all_returns_zero_indexed_array(): void {
		$manual   = new RegistryTestGateway( 'manual' );
		$check    = new RegistryTestGateway( 'check' );
		$registry = PaymentGatewayRegistry::instance();

		$registry->register( $manual );
		$registry->register( $check );

		$this->assertSame( array( 0, 1 ), array_keys( $registry->all() ) );
		$this->assertSame( array( $manual, $check ), $registry->all() );
	}

	/**
	 * @test
	 */
	public function test_enabled_filters_by_is_enabled(): void {
		$enabled  = new RegistryTestGateway( 'enabled', true, true );
		$disabled = new RegistryTestGateway( 'disabled', false, true );
		$registry = PaymentGatewayRegistry::instance();

		$registry->register( $enabled );
		$registry->register( $disabled );

		$this->assertSame( array( $enabled ), $registry->enabled() );
	}

	/**
	 * @test
	 */
	public function test_available_filters_by_is_available(): void {
		$available   = new RegistryTestGateway( 'available', true, true );
		$unavailable = new RegistryTestGateway( 'unavailable', true, false );
		$registry    = PaymentGatewayRegistry::instance();

		$registry->register( $available );
		$registry->register( $unavailable );

		$this->assertSame( array( $available ), $registry->available() );
	}

	/**
	 * @test
	 */
	public function test_get_returns_null_for_unknown(): void {
		$this->assertNull( PaymentGatewayRegistry::instance()->get( 'missing' ) );
		$this->assertFalse( PaymentGatewayRegistry::instance()->has( 'missing' ) );
	}

	/**
	 * @test
	 */
	public function test_bootstrap_wires_hooks_once(): void {
		$action_callback = null;
		$filter_callback = null;

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

		Functions\expect( 'add_filter' )
			->once()
			->with(
				'wpems_payment_gateways',
				Mockery::on(
					function ( $callback ) use ( &$filter_callback ): bool {
						$filter_callback = $callback;

						return is_callable( $callback );
					}
				),
				20
			)
			->andReturn( true );

		PaymentGatewayRegistry::bootstrap();
		PaymentGatewayRegistry::bootstrap();

		$this->assertIsCallable( $action_callback );
		$this->assertIsCallable( $filter_callback );
	}

	/**
	 * @test
	 */
	public function test_bootstrap_filter_adds_registered_gateways_to_legacy_gateways(): void {
		$filter_callback = null;
		$gateway         = new RegistryTestGateway( 'manual' );
		$legacy          = new RegistryTestGateway( 'legacy' );

		Functions\when( 'add_action' )->justReturn( true );
		Functions\expect( 'add_filter' )
			->once()
			->with(
				'wpems_payment_gateways',
				Mockery::on(
					function ( $callback ) use ( &$filter_callback ): bool {
						$filter_callback = $callback;

						return is_callable( $callback );
					}
				),
				20
			)
			->andReturn( true );

		PaymentGatewayRegistry::bootstrap();
		PaymentGatewayRegistry::instance()->register( $gateway );

		$result = $filter_callback( array( 'legacy' => $legacy ) );

		$this->assertSame( $legacy, $result['legacy'] );
		$this->assertSame( $gateway, $result['manual'] );
	}

	/**
	 * @test
	 */
	public function test_bootstrap_plugins_loaded_callback_registers_filtered_default_gateways(): void {
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
		Functions\when( 'apply_filters' )->alias(
			function ( $tag, $value = null ) {
				if ( 'wpems_payment_gateway_registry_classes' === $tag ) {
					return array( RegistryBootstrapGateway::class );
				}

				return $value;
			}
		);
		Functions\expect( 'do_action' )
			->once()
			->with( 'wpems_payment_gateway_registry_ready', Mockery::type( PaymentGatewayRegistry::class ) );

		PaymentGatewayRegistry::bootstrap();
		$action_callback();

		$this->assertTrue( PaymentGatewayRegistry::instance()->has( 'bootstrap_gateway' ) );
	}
}
