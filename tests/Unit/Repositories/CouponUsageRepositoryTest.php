<?php
/**
 * Unit tests for WPEMS\Repositories\CouponUsageRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\CouponUsageRepository
 */
class CouponUsageRepositoryTest extends TestCase {

	private CouponUsageRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$wpdb = new \stdClass();
		$wpdb->prefix = 'wp_';
		$wpdb->last_error = '';
		$wpdb->insert_id = 0;
		$wpdb->rows_affected = 0;
		$GLOBALS['wpdb'] = $wpdb;
		$this->repo = new CouponUsageRepository();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/** @test */
	public function test_record_usage_inserts_row(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->insert_id = 77;
		$m->shouldReceive( 'insert' )->once()->andReturn( true );
		$GLOBALS['wpdb'] = $m;

		$id = $this->repo->record_usage( 1, 'SAVE10', 100, 42, '5.0000' );
		$this->assertSame( 77, $id );
	}

	/** @test */
	public function test_record_usage_returns_existing_id_on_duplicate(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = 'Duplicate entry';
		$m->insert_id = 0;
		$m->shouldReceive( 'insert' )->once()->andReturn( false );
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT id ...' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( '55' );
		$GLOBALS['wpdb'] = $m;

		$id = $this->repo->record_usage( 1, 'SAVE10', 100, 42, '5.0000' );
		$this->assertSame( 55, $id );
	}

	/** @test */
	public function test_void_usage_returns_false_for_zero_booking(): void {
		$this->assertFalse( $this->repo->void_usage_for_booking( 0, 'cancel' ) );
	}

	/** @test */
	public function test_void_usage_sets_voided_columns(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->rows_affected = 1;
		$m->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'voided_at_gmt', $sql );
					$this->assertStringContainsString( 'void_reason', $sql );
					$this->assertStringContainsString( 'voided_at_gmt IS NULL', $sql );
					return true;
				} ),
				\Mockery::any(),
				'cancelled',
				100
			)
			->andReturn( 'UPDATE ...' );
		$m->shouldReceive( 'query' )->once()->andReturn( 1 );
		$GLOBALS['wpdb'] = $m;

		$this->assertTrue( $this->repo->void_usage_for_booking( 100, 'cancelled' ) );
	}

	/** @test */
	public function test_void_usage_idempotent(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->last_error = '';
		$m->rows_affected = 0;
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$m->shouldReceive( 'query' )->once()->andReturn( 0 );
		$GLOBALS['wpdb'] = $m;

		// Already voided → WHERE doesn't match → false.
		$this->assertFalse( $this->repo->void_usage_for_booking( 100, 'cancelled' ) );
	}

	/** @test */
	public function test_count_coupon_usage_excludes_voided(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'voided_at_gmt IS NULL', $sql );
					return true;
				} ),
				5
			)
			->andReturn( 'SELECT COUNT ...' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( '3' );
		$GLOBALS['wpdb'] = $m;

		$this->assertSame( 3, $this->repo->count_coupon_usage( 5 ) );
	}

	/** @test */
	public function test_count_coupon_usage_returns_zero_for_invalid(): void {
		$this->assertSame( 0, $this->repo->count_coupon_usage( 0 ) );
	}

	/** @test */
	public function test_count_user_usage_returns_zero_for_guest(): void {
		$this->assertSame( 0, $this->repo->count_user_usage( 5, 0 ) );
	}

	/** @test */
	public function test_count_user_usage_returns_count(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT COUNT ...' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( '2' );
		$GLOBALS['wpdb'] = $m;

		$this->assertSame( 2, $this->repo->count_user_usage( 5, 42 ) );
	}

	/** @test */
	public function test_find_for_booking_returns_null_for_zero(): void {
		$this->assertNull( $this->repo->find_for_booking( 0 ) );
	}

	/** @test */
	public function test_find_for_booking_returns_row(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$m->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'id' => '1', 'coupon_id' => '5', 'booking_id' => '100' )
		);
		$GLOBALS['wpdb'] = $m;

		$row = $this->repo->find_for_booking( 100 );
		$this->assertIsArray( $row );
		$this->assertSame( '5', $row['coupon_id'] );
	}

	/** @test */
	public function test_find_for_booking_returns_null_when_missing(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$m->shouldReceive( 'get_row' )->once()->andReturn( null );
		$GLOBALS['wpdb'] = $m;

		$this->assertNull( $this->repo->find_for_booking( 999 ) );
	}
}
