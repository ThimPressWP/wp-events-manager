<?php
/**
 * Unit tests for WPEMS\Services\CouponService.
 *
 * @package WPEMS\Tests\Unit\Services
 */

namespace WPEMS\Tests\Unit\Services;

use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Services\CouponService;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Services\CouponService
 */
class CouponServiceTest extends TestCase {

	private CouponService $svc;
	private $coupons;
	private $coupon_events;
	private $coupon_usage;

	protected function setUp(): void {
		parent::setUp();
		$this->coupons       = \Mockery::mock( CouponRepository::class );
		$this->coupon_events = \Mockery::mock( CouponEventRepository::class );
		$this->coupon_usage  = \Mockery::mock( CouponUsageRepository::class );
		$this->svc           = new CouponService( $this->coupons, $this->coupon_events, $this->coupon_usage );
	}

	private function make_coupon( array $overrides = array() ): CouponModel {
		return CouponModel::from_row( array_merge(
			array(
				'id'                   => 1,
				'code'                 => 'SAVE10',
				'discount_type'        => 'percent',
				'percent_value'        => '10.0000',
				'amount_value'         => null,
				'max_discount_amount'  => null,
				'applies_to'           => 'all',
				'usage_limit'          => 100,
				'usage_count'          => 0,
				'usage_limit_per_user' => null,
				'min_order_amount'     => null,
				'starts_at_gmt'        => null,
				'expires_at_gmt'       => null,
				'status'               => 'active',
				'created_at_gmt'       => '2026-01-01 00:00:00',
				'updated_at_gmt'       => '2026-01-01 00:00:00',
			),
			$overrides
		) );
	}

	// ─── validate() ─────────────────────────────────────────────────

	/** @test */
	public function test_validate_not_found(): void {
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( null );
		$r = $this->svc->validate( 'NOPE', 1, 1, 1, '100.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'not_found', $r->error_code );
	}

	/** @test */
	public function test_validate_inactive(): void {
		$c = $this->make_coupon( array( 'status' => 'inactive' ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$r = $this->svc->validate( 'SAVE10', 1, 1, 1, '100.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'inactive', $r->error_code );
	}

	/** @test */
	public function test_validate_out_of_window_before_start(): void {
		$c = $this->make_coupon( array( 'starts_at_gmt' => '2099-01-01 00:00:00' ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$r = $this->svc->validate( 'SAVE10', 1, 1, 1, '100.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'out_of_window', $r->error_code );
	}

	/** @test */
	public function test_validate_out_of_window_after_end(): void {
		$c = $this->make_coupon( array( 'expires_at_gmt' => '2020-01-01 00:00:00' ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$r = $this->svc->validate( 'SAVE10', 1, 1, 1, '100.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'out_of_window', $r->error_code );
	}

	/** @test */
	public function test_validate_global_limit_exhausted(): void {
		$c = $this->make_coupon( array( 'usage_limit' => 10, 'usage_count' => 10 ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$r = $this->svc->validate( 'SAVE10', 1, 1, 1, '100.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'global_limit', $r->error_code );
	}

	/** @test */
	public function test_validate_user_limit_exhausted(): void {
		$c = $this->make_coupon( array( 'usage_limit_per_user' => 1 ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$this->coupon_usage->shouldReceive( 'count_user_usage' )->once()->with( 1, 42 )->andReturn( 1 );
		$r = $this->svc->validate( 'SAVE10', 1, 42, 1, '100.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'user_limit', $r->error_code );
	}

	/** @test */
	public function test_validate_user_limit_ignored_for_guest(): void {
		$c = $this->make_coupon( array( 'usage_limit_per_user' => 1 ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		// user_id=0 → per-user check is skipped.
		$r = $this->svc->validate( 'SAVE10', 1, 0, 1, '100.0000' );
		$this->assertTrue( $r->is_valid );
	}

	/** @test */
	public function test_validate_event_not_eligible(): void {
		$c = $this->make_coupon( array( 'applies_to' => 'specific' ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$this->coupon_events->shouldReceive( 'coupon_applies_to_event' )->once()->with( 1, 99 )->andReturn( false );
		$r = $this->svc->validate( 'SAVE10', 99, 1, 1, '100.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'event_not_eligible', $r->error_code );
	}

	/** @test */
	public function test_validate_min_order_not_met(): void {
		$c = $this->make_coupon( array( 'min_order_amount' => '50.0000' ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$r = $this->svc->validate( 'SAVE10', 1, 1, 1, '30.0000' );
		$this->assertFalse( $r->is_valid );
		$this->assertSame( 'min_order', $r->error_code );
	}

	/** @test */
	public function test_validate_success_caps_discount_at_subtotal(): void {
		// 100% discount → capped at subtotal.
		$c = $this->make_coupon( array( 'percent_value' => '100.0000' ) );
		$this->coupons->shouldReceive( 'find_by_code' )->once()->andReturn( $c );
		$r = $this->svc->validate( 'SAVE10', 1, 1, 1, '50.0000' );
		$this->assertTrue( $r->is_valid );
		$this->assertSame( '50.0000', $r->discount );
	}

	// ─── calculate_discount() ───────────────────────────────────────

	/** @test */
	public function test_calculate_discount_percent(): void {
		$c      = $this->make_coupon( array( 'percent_value' => '10.0000' ) );
		$result = $this->svc->calculate_discount( $c, '100.0000' );
		$this->assertSame( '10.0000', $result );
	}

	/** @test */
	public function test_calculate_discount_amount_capped_at_subtotal(): void {
		$c = $this->make_coupon( array(
			'discount_type' => 'amount',
			'amount_value'  => '200.0000',
		) );
		$result = $this->svc->calculate_discount( $c, '100.0000' );
		$this->assertSame( '100.0000', $result );
	}

	/** @test */
	public function test_calculate_discount_hybrid_cap_hit(): void {
		$c = $this->make_coupon( array(
			'discount_type'       => 'hybrid',
			'percent_value'       => '50.0000',
			'max_discount_amount' => '20.0000',
		) );
		// 50% of 100 = 50, but cap is 20.
		$result = $this->svc->calculate_discount( $c, '100.0000' );
		$this->assertSame( '20.0000', $result );
	}

	/** @test */
	public function test_calculate_discount_hybrid_below_cap(): void {
		$c = $this->make_coupon( array(
			'discount_type'       => 'hybrid',
			'percent_value'       => '10.0000',
			'max_discount_amount' => '20.0000',
		) );
		// 10% of 100 = 10, below cap of 20.
		$result = $this->svc->calculate_discount( $c, '100.0000' );
		$this->assertSame( '10.0000', $result );
	}

	// ─── commit_usage() ─────────────────────────────────────────────

	/** @test */
	public function test_commit_usage_rolls_back_per_user_race(): void {
		$c = $this->make_coupon( array( 'usage_limit_per_user' => 1 ) );

		$this->coupons->shouldReceive( 'increment_usage' )->once()->andReturn( true );
		$this->coupon_usage->shouldReceive( 'count_user_usage' )->once()->with( 1, 42 )->andReturn( 1 );
		$this->coupons->shouldReceive( 'decrement_usage' )->once()->with( 1 );

		$ok = $this->svc->commit_usage( $c, 100, 42, '10.0000' );
		$this->assertFalse( $ok );
	}

	/** @test */
	public function test_commit_usage_succeeds(): void {
		$c = $this->make_coupon();

		$this->coupons->shouldReceive( 'increment_usage' )->once()->andReturn( true );
		$this->coupon_usage->shouldReceive( 'record_usage' )->once();

		$ok = $this->svc->commit_usage( $c, 100, 42, '10.0000' );
		$this->assertTrue( $ok );
	}

	// ─── void_usage() ───────────────────────────────────────────────

	/** @test */
	public function test_void_usage_idempotent(): void {
		$this->coupon_usage->shouldReceive( 'find_for_booking' )->once()->with( 100 )->andReturn(
			array( 'coupon_id' => '5', 'voided_at_gmt' => '2026-01-01 00:00:00' )
		);

		$result = $this->svc->void_usage( 100, 'cancelled' );
		$this->assertTrue( $result ); // Already voided → true, no side effects.
	}

	/** @test */
	public function test_void_usage_decrements_counter(): void {
		$this->coupon_usage->shouldReceive( 'find_for_booking' )->once()->with( 100 )->andReturn(
			array( 'coupon_id' => '5', 'voided_at_gmt' => null )
		);
		$this->coupon_usage->shouldReceive( 'void_usage_for_booking' )->once()->with( 100, 'cancelled' )->andReturn( true );
		$this->coupons->shouldReceive( 'decrement_usage' )->once()->with( 5 );

		$result = $this->svc->void_usage( 100, 'cancelled' );
		$this->assertTrue( $result );
	}

	/** @test */
	public function test_void_usage_returns_false_when_no_usage(): void {
		$this->coupon_usage->shouldReceive( 'find_for_booking' )->once()->with( 100 )->andReturn( null );

		$this->assertFalse( $this->svc->void_usage( 100, 'cancelled' ) );
	}
}
