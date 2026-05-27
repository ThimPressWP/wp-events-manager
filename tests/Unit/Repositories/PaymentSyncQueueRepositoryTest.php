<?php
/**
 * Unit tests for WPEMS\Repositories\PaymentSyncQueueRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use WPEMS\Repositories\PaymentSyncQueueRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\PaymentSyncQueueRepository
 */
class PaymentSyncQueueRepositoryTest extends TestCase {

	private PaymentSyncQueueRepository $repo;

	protected function setUp(): void {
		parent::setUp();
		$wpdb = new \stdClass();
		$wpdb->prefix = 'wp_';
		$wpdb->last_error = '';
		$wpdb->insert_id = 0;
		$wpdb->rows_affected = 0;
		$GLOBALS['wpdb'] = $wpdb;
		$this->repo = new PaymentSyncQueueRepository();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	private function make_next(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-01-01 12:00:00', new DateTimeZone( 'UTC' ) );
	}

	// ─── enqueue() ──────────────────────────────────────────────────

	/** @test */
	public function test_enqueue_inserts_new_row(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';

		$m->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'INSERT INTO', $sql );
					$this->assertStringContainsString( 'ON DUPLICATE KEY UPDATE', $sql );
					return true;
				} ),
				100,
				'paypal',
				'ORD-1',
				'2026-01-01 12:00:00'
			)
			->andReturn( 'INSERT ...' );

		$m->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $m;

		$this->repo->enqueue( 100, 'paypal', 'ORD-1', $this->make_next() );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_enqueue_skips_zero_booking(): void {
		$this->repo->enqueue( 0, 'paypal', null, $this->make_next() );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_enqueue_replaces_existing_row(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';

		$m->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$flat = preg_replace( '/\s+/', ' ', $sql );
					$this->assertStringContainsString( 'ON DUPLICATE KEY UPDATE', $flat );
					$this->assertStringContainsString( 'last_error = NULL', $flat );
					return true;
				} ),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any(),
				\Mockery::any()
			)
			->andReturn( 'INSERT ...' );

		$m->shouldReceive( 'query' )->once()->andReturn( 2 ); // 2 = update on dup.
		$GLOBALS['wpdb'] = $m;

		$this->repo->enqueue( 100, 'stripe', 'pi_123', $this->make_next() );
		$this->assertTrue( true );
	}

	// ─── dequeue_due() ──────────────────────────────────────────────

	/** @test */
	public function test_dequeue_due_returns_only_due_rows(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';

		$m->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'next_sync_at_gmt <=', $sql );
					$this->assertStringContainsString( 'ORDER BY next_sync_at_gmt ASC', $sql );
					return true;
				} ),
				\Mockery::any(),
				10
			)
			->andReturn( 'SELECT ...' );

		$m->shouldReceive( 'get_results' )->once()->andReturn(
			array(
				array( 'booking_id' => '100', 'payment_method' => 'paypal' ),
			)
		);

		$GLOBALS['wpdb'] = $m;

		$rows = $this->repo->dequeue_due( 10 );
		$this->assertCount( 1, $rows );
		$this->assertSame( '100', $rows[0]['booking_id'] );
	}

	/** @test */
	public function test_dequeue_due_respects_limit(): void {
		$this->assertSame( array(), $this->repo->dequeue_due( 0 ) );
	}

	// ─── update_attempt() ───────────────────────────────────────────

	/** @test */
	public function test_update_attempt_increments_counter(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';

		$m->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$flat = preg_replace( '/\s+/', ' ', $sql );
					$this->assertStringContainsString( 'sync_attempts = sync_attempts + 1', $flat );
					return true;
				} ),
				\Mockery::any(),
				\Mockery::any(),
				'timeout error',
				100
			)
			->andReturn( 'UPDATE ...' );

		$m->shouldReceive( 'query' )->once()->andReturn( 1 );
		$GLOBALS['wpdb'] = $m;

		$this->repo->update_attempt( 100, $this->make_next(), 'timeout error' );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_update_attempt_skips_zero_booking(): void {
		$this->repo->update_attempt( 0, $this->make_next() );
		$this->assertTrue( true );
	}

	// ─── remove() ───────────────────────────────────────────────────

	/** @test */
	public function test_remove_deletes_row(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'delete' )
			->once()
			->with( 'wp_wpems_payment_sync_queue', array( 'booking_id' => 100 ) )
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $m;

		$this->repo->remove( 100 );
		$this->assertTrue( true );
	}

	/** @test */
	public function test_remove_skips_zero_booking(): void {
		$this->repo->remove( 0 );
		$this->assertTrue( true );
	}

	// ─── get() ──────────────────────────────────────────────────────

	/** @test */
	public function test_get_returns_null_for_zero(): void {
		$this->assertNull( $this->repo->get( 0 ) );
	}

	/** @test */
	public function test_get_returns_row(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_row' )->once()->andReturn(
			array( 'booking_id' => '100', 'sync_attempts' => '3' )
		);
		$GLOBALS['wpdb'] = $m;

		$row = $this->repo->get( 100 );
		$this->assertIsArray( $row );
		$this->assertSame( '100', $row['booking_id'] );
	}

	// ─── count_pending() ────────────────────────────────────────────

	/** @test */
	public function test_count_pending_returns_int(): void {
		$m = \Mockery::mock( 'wpdb' );
		$m->prefix = 'wp_';
		$m->shouldReceive( 'prepare' )->once()->andReturn( 'SQL' );
		$m->shouldReceive( 'get_var' )->once()->andReturn( '5' );
		$GLOBALS['wpdb'] = $m;

		$this->assertSame( 5, $this->repo->count_pending() );
	}
}
