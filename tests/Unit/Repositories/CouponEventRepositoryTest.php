<?php
/**
 * Unit tests for WPEMS\Repositories\CouponEventRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use RuntimeException;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\CouponEventRepository
 */
class CouponEventRepositoryTest extends TestCase {

	private CouponEventRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$wpdb = new \stdClass();
		$wpdb->prefix = 'wp_';
		$wpdb->last_error = '';
		$GLOBALS['wpdb'] = $wpdb;
		$this->repo = new CouponEventRepository();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/** @test */
	public function test_set_events_replaces_existing_mapping(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->shouldReceive( 'query' )->with( 'START TRANSACTION' )->once();
		$m->shouldReceive( 'delete' )->once()->andReturn( 2 );
		$m->shouldReceive( 'insert' )->twice()->andReturn( true );
		$m->shouldReceive( 'query' )->with( 'COMMIT' )->once();
		$GLOBALS['wpdb'] = $m;
		$this->repo->set_events( 5, array( 10, 20 ) );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_set_events_dedupes_input(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->shouldReceive( 'query' )->with( 'START TRANSACTION' )->once();
		$m->shouldReceive( 'delete' )->once()->andReturn( 0 );
		$m->shouldReceive( 'insert' )->twice()->andReturn( true );
		$m->shouldReceive( 'query' )->with( 'COMMIT' )->once();
		$GLOBALS['wpdb'] = $m;
		$this->repo->set_events( 5, array( 10, 20, 10 ) );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_set_events_filters_zero_ids(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->shouldReceive( 'query' )->with( 'START TRANSACTION' )->once();
		$m->shouldReceive( 'delete' )->once()->andReturn( 0 );
		$m->shouldReceive( 'insert' )->once()->andReturn( true );
		$m->shouldReceive( 'query' )->with( 'COMMIT' )->once();
		$GLOBALS['wpdb'] = $m;
		$this->repo->set_events( 5, array( 0, 10 ) );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_set_events_skips_for_zero_coupon(): void {
		$this->repo->set_events( 0, array( 10, 20 ) );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_set_events_rolls_back_on_error(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = 'Insert failed';
		$m->shouldReceive( 'query' )->with( 'START TRANSACTION' )->once();
		$m->shouldReceive( 'delete' )->once()->andReturn( 0 );
		$m->shouldReceive( 'insert' )->once()->andReturn( false );
		$m->shouldReceive( 'query' )->with( 'ROLLBACK' )->once();
		$GLOBALS['wpdb'] = $m;
		$this->expectException( RuntimeException::class );
		$this->repo->set_events( 5, array( 10 ) );
	}

	/** @test */
	public function test_get_event_ids_returns_empty_for_zero(): void {
		$this->assertSame( array(), $this->repo->get_event_ids( 0 ) );
	}

	/** @test */
	public function test_get_event_ids_returns_int_array(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_col' )->once()->andReturn( array( '10', '20' ) );
		$GLOBALS['wpdb'] = $m;
		$this->assertSame( array( 10, 20 ), $this->repo->get_event_ids( 5 ) );
	}

	/** @test */
	public function test_coupon_applies_returns_false_for_zero(): void {
		$this->assertFalse( $this->repo->coupon_applies_to_event( 0, 10 ) );
		$this->assertFalse( $this->repo->coupon_applies_to_event( 5, 0 ) );
	}

	/** @test */
	public function test_coupon_applies_returns_true_when_mapped(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( '1' );
		$GLOBALS['wpdb'] = $m;
		$this->assertTrue( $this->repo->coupon_applies_to_event( 5, 10 ) );
	}

	/** @test */
	public function test_coupon_applies_returns_false_when_not(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( null );
		$GLOBALS['wpdb'] = $m;
		$this->assertFalse( $this->repo->coupon_applies_to_event( 5, 99 ) );
	}

	/** @test */
	public function test_delete_all_for_coupon_removes_rows(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'delete' )->once()->with( 'wp_wpems_coupon_events', array( 'coupon_id' => 5 ) )->andReturn( 3 );
		$GLOBALS['wpdb'] = $m;
		$this->repo->delete_all_for_coupon( 5 );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_delete_all_skips_zero(): void {
		$this->repo->delete_all_for_coupon( 0 );
		$this->assertTrue( true );
	}
}
