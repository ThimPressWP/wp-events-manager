<?php
/**
 * Unit tests for WPEMS\Services\CheckoutQuoteService.
 *
 * @package WPEMS\Tests\Unit\Services
 */

namespace WPEMS\Tests\Unit\Services;

use InvalidArgumentException;
use WPEMS\Pricing\CheckoutQuote;
use WPEMS\Services\CheckoutQuoteService;
use WPEMS\Services\CouponService;
use WPEMS\Services\CouponValidationResult;
use WPEMS\Services\TaxService;
use WPEMS\Tests\Unit\TestCase;

/**
 * Testable subclass that stubs protected helpers.
 */
class TestableCheckoutQuoteService extends CheckoutQuoteService {

	public string $event_price = '100.0000';
	public string $currency    = 'USD';

	protected function get_event_price( int $event_id ): string {
		return $this->event_price;
	}

	protected function get_currency(): string {
		return $this->currency;
	}
}

/**
 * @covers \WPEMS\Services\CheckoutQuoteService
 */
class CheckoutQuoteServiceTest extends TestCase {

	private $tax;
	private $coupon_svc;
	private TestableCheckoutQuoteService $svc;

	protected function setUp(): void {
		parent::setUp();
		$this->tax        = \Mockery::mock( TaxService::class );
		$this->coupon_svc = \Mockery::mock( CouponService::class );
		$this->svc        = new TestableCheckoutQuoteService( $this->tax, $this->coupon_svc );
	}

	/** @test */
	public function test_quote_no_coupon_no_tax(): void {
		$this->svc->event_price = '100.0000';

		$this->tax->shouldReceive( 'is_enabled' )->andReturn( false );
		$this->tax->shouldReceive( 'calculate' )->andReturn( '0.0000' );
		$this->tax->shouldReceive( 'get_rate' )->never();
		$this->tax->shouldReceive( 'get_label' )->andReturn( 'Tax' );

		$q = $this->svc->quote( 1, 1, 2 );

		$this->assertInstanceOf( CheckoutQuote::class, $q );
		$this->assertSame( '200.0000', $q->get_subtotal() );
		$this->assertSame( '0.0000', $q->get_discount_total() );
		$this->assertSame( '0.0000', $q->get_tax_rate() );
		$this->assertSame( '0.0000', $q->get_tax_total() );
		$this->assertSame( '200.0000', $q->get_total() );
		$this->assertNull( $q->get_coupon_id() );
	}

	/** @test */
	public function test_quote_with_tax_10_percent(): void {
		$this->svc->event_price = '100.0000';

		$this->tax->shouldReceive( 'is_enabled' )->andReturn( true );
		$this->tax->shouldReceive( 'get_rate' )->andReturn( '10.0000' );
		$this->tax->shouldReceive( 'calculate' )->with( '100.0000', 'USD' )->andReturn( '10.0000' );
		$this->tax->shouldReceive( 'get_label' )->andReturn( 'Tax' );

		$q = $this->svc->quote( 1, 1, 1 );

		$this->assertSame( '100.0000', $q->get_subtotal() );
		$this->assertSame( '10.0000', $q->get_tax_rate() );
		$this->assertSame( '10.0000', $q->get_tax_total() );
		$this->assertSame( '110.0000', $q->get_total() );
	}

	/** @test */
	public function test_quote_with_coupon_before_tax(): void {
		$this->svc->event_price = '100.0000';

		$coupon = \WPEMS\Models\CouponModel::from_row( array(
			'id'            => 5,
			'code'          => 'SAVE10',
			'discount_type' => 'percent',
			'percent_value' => '10.0000',
			'status'        => 'active',
			'created_at_gmt' => '2026-01-01 00:00:00',
			'updated_at_gmt' => '2026-01-01 00:00:00',
		) );

		$result = CouponValidationResult::success( $coupon, '10.0000' );

		$this->coupon_svc->shouldReceive( 'validate' )->once()->andReturn( $result );

		$this->tax->shouldReceive( 'is_enabled' )->andReturn( true );
		$this->tax->shouldReceive( 'get_rate' )->andReturn( '10.0000' );
		// Taxable = 100 - 10 = 90.
		$this->tax->shouldReceive( 'calculate' )->with( '90.0000', 'USD' )->andReturn( '9.0000' );
		$this->tax->shouldReceive( 'get_label' )->andReturn( 'Tax' );

		$q = $this->svc->quote( 1, 1, 1, 'save10' );

		$this->assertSame( '100.0000', $q->get_subtotal() );
		$this->assertSame( '10.0000', $q->get_discount_total() );
		$this->assertSame( '9.0000', $q->get_tax_total() );
		$this->assertSame( '99.0000', $q->get_total() );
		$this->assertSame( 5, $q->get_coupon_id() );
		$this->assertSame( 'SAVE10', $q->get_coupon_code() );
	}

	/** @test */
	public function test_quote_discount_exceeds_subtotal_clamps_to_zero_taxable(): void {
		$this->svc->event_price = '10.0000';

		$coupon = \WPEMS\Models\CouponModel::from_row( array(
			'id'             => 1,
			'code'           => 'HUGE',
			'discount_type'  => 'amount',
			'amount_value'   => '50.0000',
			'status'         => 'active',
			'created_at_gmt' => '2026-01-01 00:00:00',
			'updated_at_gmt' => '2026-01-01 00:00:00',
		) );

		// Discount > subtotal.
		$result = CouponValidationResult::success( $coupon, '50.0000' );

		$this->coupon_svc->shouldReceive( 'validate' )->once()->andReturn( $result );

		$this->tax->shouldReceive( 'is_enabled' )->andReturn( true );
		$this->tax->shouldReceive( 'get_rate' )->andReturn( '10.0000' );
		// Taxable clamped to 0.
		$this->tax->shouldReceive( 'calculate' )->with( '0.0000', 'USD' )->andReturn( '0.0000' );
		$this->tax->shouldReceive( 'get_label' )->andReturn( 'Tax' );

		$q = $this->svc->quote( 1, 1, 1, 'HUGE' );

		$this->assertSame( '0.0000', $q->get_total() );
	}

	/** @test */
	public function test_quote_invalid_coupon_silently_ignored(): void {
		$this->svc->event_price = '100.0000';

		$result = CouponValidationResult::failure( 'not_found', 'Not found' );
		$this->coupon_svc->shouldReceive( 'validate' )->once()->andReturn( $result );

		$this->tax->shouldReceive( 'is_enabled' )->andReturn( false );
		$this->tax->shouldReceive( 'calculate' )->andReturn( '0.0000' );
		$this->tax->shouldReceive( 'get_label' )->andReturn( 'Tax' );

		$q = $this->svc->quote( 1, 1, 1, 'BADCODE' );

		$this->assertSame( '0.0000', $q->get_discount_total() );
		$this->assertNull( $q->get_coupon_id() );
		$this->assertSame( '100.0000', $q->get_total() );
	}

	/** @test */
	public function test_quote_throws_on_zero_event_id(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'event_id_required' );
		$this->svc->quote( 0, 1, 1 );
	}

	/** @test */
	public function test_quote_throws_on_zero_qty(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'qty_must_be_positive' );
		$this->svc->quote( 1, 1, 0 );
	}
}
