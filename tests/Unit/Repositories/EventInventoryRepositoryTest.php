<?php
/**
 * Unit tests for WPEMS\Repositories\EventInventoryRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use Brain\Monkey\Functions;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\EventInventoryRepository
 */
class EventInventoryRepositoryTest extends TestCase {

	/**
	 * @var EventInventoryRepository
	 */
	private EventInventoryRepository $repo;

	/**
	 * Set up mock $wpdb and repository.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$wpdb         = new \stdClass();
		$wpdb->prefix = 'wp_';
		$wpdb->last_error    = '';
		$wpdb->rows_affected = 0;

		$GLOBALS['wpdb'] = $wpdb;

		$this->repo = new EventInventoryRepository();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	// ─── reserve() ──────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_reserve_returns_false_for_zero_qty(): void {
		$this->assertFalse( $this->repo->reserve( 100, 0 ) );
	}

	/**
	 * @test
	 */
	public function test_reserve_returns_false_for_negative_qty(): void {
		$this->assertFalse( $this->repo->reserve( 100, -1 ) );
	}

	/**
	 * @test
	 */
	public function test_reserve_unlimited_capacity_always_succeeds(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 1;

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->reserve( 100, 5 ) );
	}

	/**
	 * @test
	 */
	public function test_reserve_at_capacity_fails(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 0;

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 0 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertFalse( $this->repo->reserve( 100, 1 ) );
	}

	// ─── release_hold() ─────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_release_hold_returns_false_for_zero_qty(): void {
		$this->assertFalse( $this->repo->release_hold( 100, 0 ) );
	}

	/**
	 * @test
	 */
	public function test_release_hold_returns_true_on_success(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 1;

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->release_hold( 100, 2 ) );
	}

	/**
	 * @test
	 */
	public function test_release_hold_idempotent_second_call_returns_false(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 0;

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 0 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		// Second call: held_qty already 0, WHERE clause doesn't match → false.
		$this->assertFalse( $this->repo->release_hold( 100, 2 ) );
	}

	// ─── confirm_hold() ─────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_confirm_hold_returns_false_for_zero_qty(): void {
		$this->assertFalse( $this->repo->confirm_hold( 100, 0 ) );
	}

	/**
	 * @test
	 */
	public function test_confirm_hold_moves_qty_atomically(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 1;

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'held_qty = held_qty -', $sql );
					$this->assertStringContainsString( 'confirmed_qty = confirmed_qty +', $sql );
					return true;
				} ),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any()
			)
			->andReturn( 'UPDATE ...' );

		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->confirm_hold( 100, 3 ) );
	}

	// ─── release_confirmed() ────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_release_confirmed_returns_false_for_zero_qty(): void {
		$this->assertFalse( $this->repo->release_confirmed( 100, 0 ) );
	}

	/**
	 * @test
	 */
	public function test_release_confirmed_returns_true_on_success(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix         = 'wp_';
		$wpdb_mock->last_error     = '';
		$wpdb_mock->rows_affected  = 1;

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'UPDATE ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->release_confirmed( 100, 1 ) );
	}

	// ─── get_available_quantity() ────────────────────────────────────

	/**
	 * @test
	 */
	public function test_get_available_quantity_returns_null_when_no_row(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( null );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertNull( $this->repo->get_available_quantity( 100 ) );
	}

	/**
	 * @test
	 */
	public function test_get_available_quantity_returns_null_when_unlimited(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'capacity' => '0', 'held_qty' => '5', 'confirmed_qty' => '3' )
		);

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertNull( $this->repo->get_available_quantity( 100 ) );
	}

	/**
	 * @test
	 */
	public function test_get_available_quantity_returns_correct_count(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'capacity' => '50', 'held_qty' => '10', 'confirmed_qty' => '20' )
		);

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertSame( 20, $this->repo->get_available_quantity( 100 ) );
	}

	/**
	 * @test
	 */
	public function test_get_available_quantity_clamps_to_zero(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn(
			// Over-allocated scenario.
			array( 'capacity' => '10', 'held_qty' => '8', 'confirmed_qty' => '5' )
		);

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertSame( 0, $this->repo->get_available_quantity( 100 ) );
	}

	// ─── rebuild_for_event() ────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_rebuild_for_event_idempotent(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		// First call: compute totals.
		$wpdb_mock->shouldReceive( 'prepare' )->andReturn( 'SQL ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'held' => '3', 'confirmed' => '7' )
		);

		// get_post_meta for capacity.
		Functions\when( 'get_post_meta' )->justReturn( 100 );

		// Upsert query.
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		// Should not throw.
		$this->repo->rebuild_for_event( 200 );

		$this->assertTrue( true ); // No exception = pass.
	}

	// ─── ensure_row() ───────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_ensure_row_executes_insert_ignore(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'INSERT IGNORE', $sql );
					return true;
				} ),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any()
			)
			->andReturn( 'INSERT IGNORE ...' );

		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->repo->ensure_row( 100, 50 );

		$this->assertTrue( true ); // No exception = pass.
	}
}
