<?php
/**
 * Unit tests for WPEMS\Payments\AbstractPaymentGateway.
 *
 * @package WPEMS\Tests\Unit\Payments
 */

namespace WPEMS\Tests\Unit\Payments;

use BadMethodCallException;
use Brain\Monkey\Functions;
use WPEMS\Models\BookingTableModel;
use WPEMS\Payments\AbstractPaymentGateway;
use WPEMS\Payments\CheckoutResult;
use WPEMS\Payments\PaymentResult;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test gateway implementation for exercising base behavior.
 */
class TestPaymentGateway extends AbstractPaymentGateway {

	/**
	 * @var string[]
	 */
	protected const FEATURES = array( 'redirect_checkout', 'payment_sync' );

	/**
	 * @var bool
	 */
	private bool $enabled;

	/**
	 * Constructor.
	 *
	 * @param bool $enabled Whether gateway is enabled.
	 */
	public function __construct( bool $enabled = true ) {
		$this->id          = 'test_gateway';
		$this->title       = 'Test Gateway';
		$this->description = 'Gateway used for unit tests.';
		$this->enabled     = $enabled;
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
	public function admin_fields(): array {
		return array(
			array(
				'id'   => $this->setting_id( 'enable' ),
				'type' => 'yes_no',
			),
		);
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

	/**
	 * Expose protected setting_id() for tests.
	 *
	 * @param string $suffix Setting suffix.
	 *
	 * @return string
	 */
	public function expose_setting_id( string $suffix ): string {
		return $this->setting_id( $suffix );
	}

	/**
	 * Expose protected get_setting() for tests.
	 *
	 * @param string $key     Setting suffix.
	 * @param mixed  $fallback Default value.
	 *
	 * @return mixed
	 */
	public function expose_get_setting( string $key, $fallback = '' ) {
		return $this->get_setting( $key, $fallback );
	}
}

/**
 * @covers \WPEMS\Payments\AbstractPaymentGateway
 */
class AbstractPaymentGatewayTest extends TestCase {

	/**
	 * @test
	 */
	public function test_public_properties_back_getters(): void {
		$gateway = new TestPaymentGateway();

		$this->assertSame( 'test_gateway', $gateway->id );
		$this->assertSame( 'Test Gateway', $gateway->title );
		$this->assertSame( 'Gateway used for unit tests.', $gateway->description );
		$this->assertSame( 'test_gateway', $gateway->get_id() );
		$this->assertSame( 'Test Gateway', $gateway->get_title() );
		$this->assertSame( 'Gateway used for unit tests.', $gateway->get_description() );
	}

	/**
	 * @test
	 */
	public function test_supports_uses_features_const_from_subclass(): void {
		$gateway = new TestPaymentGateway();

		$this->assertTrue( $gateway->supports( 'redirect_checkout' ) );
		$this->assertTrue( $gateway->supports( 'payment_sync' ) );
		$this->assertFalse( $gateway->supports( 'refund' ) );
	}

	/**
	 * @test
	 */
	public function test_refund_payment_throws_by_default(): void {
		$gateway = new TestPaymentGateway();
		$booking = BookingTableModel::from_row( array( 'id' => 123 ) );

		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Refund not supported by this gateway in v1.' );

		$gateway->refund_payment( $booking, '10.0000', 'customer_request' );
	}

	/**
	 * @test
	 */
	public function test_is_available_defaults_to_is_enabled(): void {
		$this->assertTrue( ( new TestPaymentGateway( true ) )->is_available() );
		$this->assertFalse( ( new TestPaymentGateway( false ) )->is_available() );
	}

	/**
	 * @test
	 */
	public function test_setting_id_concats_id_and_suffix(): void {
		$gateway = new TestPaymentGateway();

		$this->assertSame( 'test_gateway_enable', $gateway->expose_setting_id( 'enable' ) );
	}

	/**
	 * @test
	 */
	public function test_get_setting_reads_via_settings_manager(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'test_gateway_secret_key', null )
			->andReturn( 'sk_test_123' );

		$gateway = new TestPaymentGateway();

		$this->assertSame( 'sk_test_123', $gateway->expose_get_setting( 'secret_key', 'fallback' ) );
	}

	/**
	 * @test
	 */
	public function test_default_icon_url_is_empty(): void {
		$gateway = new TestPaymentGateway();

		$this->assertSame( '', $gateway->get_icon_url() );
	}
}
