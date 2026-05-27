<?php
/**
 * Unit tests for WPEMS\Repositories\CouponRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use InvalidArgumentException;
use RuntimeException;
use WPEMS\Models\CouponModel;
use WPEMS\Repositories\CouponQuery;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\CouponRepository
 */
class CouponRepositoryTest extends TestCase {

	/**
	 * @var CouponRepository
	 */
	private CouponRepository $repo;

	/**
	 * Set up mock $wpdb and repository.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$wpdb              = new \stdClass();
		$wpdb->prefix      = 'wp_';
		$wpdb->last_error  = '';
		$wpdb->insert_id   = 0;
		$wpdb->rows_affected = 0;

		$GLOBALS['wpdb'] = $wpdb;

		$this->repo = new CouponRepository();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * Helper: make a coupon row.
	 *
	 * @param array $overrides Overrides.
	 *
	 * @return array
	 */
	private function make_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'                  => 1,
				'code'                => 'SAVE10',
				'description'         => 'Test coupon',
				'discount_type'       => 'percent',
				'percent_value'       => '10.0000',
				'amount_value'        => null,
				'max_discount_amount' => null,
				'applies_to'          => 'all',
				'usage_limit'         => 100,
				'usage_count'         => 5,
				'usage_limit_per_user' => 1,
				'min_order_amount'    => '0.0000',
				'starts_at_gmt'       => null,
				'expires_at_gmt'      => null,
				'status'              => 'active',
				'created_at_gmt'      => '2026-01-01 00:00:00',
				'updated_at_gmt'      => '2026-01-01 00:00:00',
			),
			$overrides
		);
	}

	// ─── find() ──────────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_find_returns_null_for_zero(): void {
		$this->assertNull( $this->repo->find( 0 ) );
	}

	/**
	 * @test
	 */
	public function test_find_returns_model(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( $this->make_row() );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$model = $this->repo->find( 1 );

		$this->assertInstanceOf( CouponModel::class, $model );
		$this->assertSame( 1, $model->get_id() );
	}

	// ─── find_by_code() ─────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_find_by_code_returns_null_for_empty(): void {
		$this->assertNull( $this->repo->find_by_code( '' ) );
	}

	/**
	 * @test
	 */
	public function test_find_by_code_uppercases_input(): void {
		$row = $this->make_row( array( 'code' => 'SAVE10' ) );

		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::any(),
				'SAVE10' // Verify uppercase.
			)
			->andReturn( 'SELECT ...' );

		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( $row );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$model = $this->repo->find_by_code( 'save10' ); // Lowercase input.

		$this->assertInstanceOf( CouponModel::class, $model );
	}

	// ─── insert() ───────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_insert_requires_code(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'code' );

		$this->repo->insert( array( 'discount_type' => 'percentage' ) );
	}

	/**
	 * @test
	 */
	public function test_insert_requires_discount_type(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'discount_type' );

		$this->repo->insert( array( 'code' => 'TEST' ) );
	}

	/**
	 * @test
	 */
	public function test_insert_rejects_duplicate_code(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		// find_by_code check returns existing row.
		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( $this->make_row() );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'duplicate_code' );

		$this->repo->insert(
			array(
				'code'          => 'SAVE10',
				'discount_type' => 'percentage',
			)
		);
	}

	/**
	 * @test
	 */
	public function test_insert_returns_new_id(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';
		$wpdb_mock->insert_id  = 42;

		// find_by_code returns null (no duplicate).
		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( null );

		// insert call.
		$wpdb_mock->shouldReceive( 'insert' )->once()->andReturn( true );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$id = $this->repo->insert(
			array(
				'code'          => 'NEW20',
				'discount_type' => 'fixed',
			)
		);

		$this->assertSame( 42, $id );
	}

	// ─── update() ───────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_update_returns_false_for_zero_id(): void {
		$this->assertFalse( $this->repo->update( 0, array( 'status' => 'expired' ) ) );
	}

	/**
	 * @test
	 */
	public function test_update_strips_immutable_keys(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'update' )
			->once()
			->with(
				'wp_wpems_coupons',
				\Mockery::on( function ( array $data ) {
					$this->assertArrayNotHasKey( 'id', $data );
					$this->assertArrayNotHasKey( 'created_at_gmt', $data );
					$this->assertArrayNotHasKey( 'usage_count', $data );
					$this->assertSame( 'expired', $data['status'] );
					return true;
				} ),
				array( 'id' => 5 )
			)
			->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$result = $this->repo->update(
			5,
			array(
				'id'             => 999,
				'created_at_gmt' => '2020-01-01',
				'usage_count'    => 100,
				'status'         => 'expired',
			)
		);

		$this->assertTrue( $result );
	}

	// ─── delete() ───────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_delete_returns_false_for_zero_id(): void {
		$this->assertFalse( $this->repo->delete( 0 ) );
	}

	/**
	 * @test
	 */
	public function test_delete_returns_true_on_success(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'delete' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->delete( 5 ) );
	}

	// ─── increment_usage() ──────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_increment_usage_returns_false_for_zero_id(): void {
		$this->assertFalse( $this->repo->increment_usage( 0, 10 ) );
	}

	/**
	 * @test
	 */
	public function test_increment_usage_succeeds_below_limit(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 1;

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->increment_usage( 1, 10 ) );
	}

	/**
	 * @test
	 */
	public function test_increment_usage_fails_at_limit(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 0;

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 0 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertFalse( $this->repo->increment_usage( 1, 1 ) );
	}

	/**
	 * @test
	 */
	public function test_increment_usage_unlimited_when_zero_snapshot(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 1;

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					// 0 = unlimited: the SQL should have %d = 0 OR usage_count < %d.
					$this->assertStringContainsString( 'usage_count < %d', $sql );
					return true;
				} ),
				\Mockery::any(),
				\Mockery::any(),
				0,  // $current_limit_snapshot.
				0
			)
			->andReturn( 'UPDATE ...' );

		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->increment_usage( 1, 0 ) );
	}

	// ─── decrement_usage() ──────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_decrement_usage_returns_false_for_zero_id(): void {
		$this->assertFalse( $this->repo->decrement_usage( 0 ) );
	}

	/**
	 * @test
	 */
	public function test_decrement_usage_clamps_at_zero(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 0;

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'GREATEST', $sql );
					$this->assertStringContainsString( 'usage_count > 0', $sql );
					return true;
				} ),
				\Mockery::any(),
				\Mockery::any()
			)
			->andReturn( 'UPDATE ...' );

		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 0 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		// usage_count = 0, so WHERE fails → false.
		$this->assertFalse( $this->repo->decrement_usage( 1 ) );
	}

	// ─── query() ────────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_query_order_by_whitelisted(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					// Bogus order_by should default to created_at_gmt.
					$this->assertStringContainsString( 'ORDER BY created_at_gmt', $sql );
					return true;
				} ),
				20,
				0
			)
			->andReturn( 'SELECT ...' );

		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn( array() );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$q           = new CouponQuery();
		$q->order_by = 'EVIL_COLUMN';

		$result = $this->repo->query( $q );

		$this->assertSame( array(), $result );
	}

	/**
	 * @test
	 */
	public function test_query_returns_models(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn( array( $this->make_row() ) );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$results = $this->repo->query( new CouponQuery() );

		$this->assertCount( 1, $results );
		$this->assertInstanceOf( CouponModel::class, $results[0] );
	}

	// ─── count() ────────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_count_returns_int(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->shouldReceive( 'get_var' )->once()->andReturn( '12' );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertSame( 12, $this->repo->count( new CouponQuery() ) );
	}
}
