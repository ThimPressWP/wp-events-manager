<?php
/**
 * Unit tests for WPEMS\Repositories\BookingRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use Brain\Monkey\Functions;
use InvalidArgumentException;
use RuntimeException;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingQuery;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\BookingRepository
 */
class BookingRepositoryTest extends TestCase {

	/**
	 * @var BookingRepository
	 */
	private BookingRepository $repo;

	/**
	 * Set up the mock $wpdb and repository instance.
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

		$this->repo = new BookingRepository();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * Helper: make a full booking row.
	 *
	 * @param array $overrides Column overrides.
	 *
	 * @return array
	 */
	private function make_row( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'                  => 1,
				'legacy_post_id'      => null,
				'event_id'            => 100,
				'user_id'             => 42,
				'qty'                 => 2,
				'subtotal'            => '50.0000',
				'discount_total'      => '0.0000',
				'tax_rate'            => '0.0000',
				'tax_total'           => '0.0000',
				'total'               => '50.0000',
				'currency'            => 'USD',
				'coupon_id'           => null,
				'payment_method'      => 'paypal',
				'payment_mode'        => 'live',
				'gateway_order_id'    => 'ORD-123',
				'status'              => 'ea-pending',
				'payment_status'      => 'pending',
				'hold_expires_at_gmt' => null,
				'idempotency_key'     => 'key-abc',
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
	public function test_find_returns_null_for_negative(): void {
		$this->assertNull( $this->repo->find( -1 ) );
	}

	/**
	 * @test
	 */
	public function test_find_returns_null_for_unknown(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( null );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertNull( $this->repo->find( 999 ) );
	}

	/**
	 * @test
	 */
	public function test_find_returns_model_for_existing_row(): void {
		$row = $this->make_row();

		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( $row );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$model = $this->repo->find( 1 );

		$this->assertInstanceOf( BookingTableModel::class, $model );
		$this->assertSame( 1, $model->get_id() );
		$this->assertSame( 100, $model->get_event_id() );
	}

	// ─── find_by_idempotency_key() ──────────────────────────────────

	/**
	 * @test
	 */
	public function test_find_by_idempotency_key_returns_null_for_empty(): void {
		$this->assertNull( $this->repo->find_by_idempotency_key( '' ) );
	}

	/**
	 * @test
	 */
	public function test_find_by_idempotency_key_returns_model(): void {
		$row = $this->make_row( array( 'idempotency_key' => 'abc-123' ) );

		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_row' )->once()->andReturn( $row );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$model = $this->repo->find_by_idempotency_key( 'abc-123' );

		$this->assertInstanceOf( BookingTableModel::class, $model );
		$this->assertSame( 'abc-123', $model->get_idempotency_key() );
	}

	// ─── find_by_gateway_order() ────────────────────────────────────

	/**
	 * @test
	 */
	public function test_find_by_gateway_order_returns_null_for_empty_method(): void {
		$this->assertNull( $this->repo->find_by_gateway_order( '', 'ORDER-1' ) );
	}

	/**
	 * @test
	 */
	public function test_find_by_gateway_order_returns_null_for_empty_order(): void {
		$this->assertNull( $this->repo->find_by_gateway_order( 'paypal', '' ) );
	}

	// ─── find_by_legacy_post_id() ───────────────────────────────────

	/**
	 * @test
	 */
	public function test_find_by_legacy_post_id_returns_null_for_zero(): void {
		$this->assertNull( $this->repo->find_by_legacy_post_id( 0 ) );
	}

	// ─── insert() ───────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_insert_validates_required_keys(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'event_id' );

		$this->repo->insert( array( 'user_id' => 1 ) );
	}

	/**
	 * @test
	 */
	public function test_insert_validates_payment_status(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'payment_status' );

		$this->repo->insert(
			array(
				'event_id'       => 100,
				'user_id'        => 42,
				'qty'            => 1,
				'payment_method' => 'paypal',
				'status'         => 'ea-pending',
			)
		);
	}

	/**
	 * @test
	 */
	public function test_insert_throws_on_db_error(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = 'Duplicate entry';
		$wpdb_mock->insert_id  = 0;

		$wpdb_mock->shouldReceive( 'insert' )->once()->andReturn( false );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Duplicate entry' );

		$this->repo->insert(
			array(
				'event_id'       => 100,
				'user_id'        => 42,
				'qty'            => 1,
				'payment_method' => 'paypal',
				'status'         => 'ea-pending',
				'payment_status' => 'pending',
			)
		);
	}

	/**
	 * @test
	 */
	public function test_insert_returns_new_id_on_success(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';
		$wpdb_mock->insert_id  = 55;

		$wpdb_mock->shouldReceive( 'insert' )->once()->andReturn( true );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$id = $this->repo->insert(
			array(
				'event_id'       => 100,
				'user_id'        => 42,
				'qty'            => 1,
				'payment_method' => 'paypal',
				'status'         => 'ea-pending',
				'payment_status' => 'pending',
			)
		);

		$this->assertSame( 55, $id );
	}

	// ─── update() ───────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_update_returns_false_for_zero_id(): void {
		$this->assertFalse( $this->repo->update( 0, array( 'status' => 'ea-completed' ) ) );
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
				'wp_wpems_bookings',
				\Mockery::on( function ( array $data ) {
					$this->assertArrayNotHasKey( 'id', $data );
					$this->assertArrayNotHasKey( 'legacy_post_id', $data );
					$this->assertArrayNotHasKey( 'created_at_gmt', $data );
					$this->assertArrayHasKey( 'updated_at_gmt', $data );
					$this->assertSame( 'ea-completed', $data['status'] );
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
				'legacy_post_id' => 888,
				'created_at_gmt' => '2020-01-01',
				'status'         => 'ea-completed',
			)
		);

		$this->assertTrue( $result );
	}

	// ─── query() ────────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_query_whitelists_order_by(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'ORDER BY created_at_gmt', $sql );
					return true;
				} ),
				20,
				0
			)
			->andReturn( 'SELECT * FROM wp_wpems_bookings ORDER BY created_at_gmt DESC LIMIT 20 OFFSET 0' );

		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn( array() );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$q           = new BookingQuery();
		$q->order_by = 'DROP TABLE bookings; --';

		$result = $this->repo->query( $q );

		$this->assertSame( array(), $result );
	}

	/**
	 * @test
	 */
	public function test_query_with_search_uses_like(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->shouldReceive( 'prepare' )
			->once()
			->with(
				\Mockery::on( function ( string $sql ) {
					$this->assertStringContainsString( 'idempotency_key LIKE', $sql );
					$this->assertStringContainsString( 'gateway_order_id LIKE', $sql );
					return true;
				} ),
				'%test-search%',
				'%test-search%',
				20,
				0
			)
			->andReturn( 'SELECT * FROM wp_wpems_bookings WHERE ... LIMIT 20 OFFSET 0' );

		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn( array() );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$q         = new BookingQuery();
		$q->search = 'test-search';

		$this->repo->query( $q );
		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function test_query_returns_models(): void {
		$row = $this->make_row();

		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn( array( $row ) );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$results = $this->repo->query( new BookingQuery() );

		$this->assertCount( 1, $results );
		$this->assertInstanceOf( BookingTableModel::class, $results[0] );
	}

	// ─── count() ────────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_count_returns_int(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->shouldReceive( 'get_var' )->once()->andReturn( '7' );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$q = new BookingQuery();

		$this->assertSame( 7, $this->repo->count( $q ) );
	}

	// ─── upsert_from_legacy() ───────────────────────────────────────

	/**
	 * @test
	 */
	public function test_upsert_from_legacy_requires_legacy_post_id(): void {
		$this->expectException( InvalidArgumentException::class );

		$this->repo->upsert_from_legacy( array( 'event_id' => 1 ) );
	}

	/**
	 * @test
	 */
	public function test_upsert_from_legacy_returns_insert_id_for_new_row(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->insert_id  = 99;
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'INSERT ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$id = $this->repo->upsert_from_legacy(
			array(
				'legacy_post_id' => 500,
				'event_id'       => 100,
				'user_id'        => 42,
			)
		);

		$this->assertSame( 99, $id );
	}

	/**
	 * @test
	 */
	public function test_upsert_from_legacy_looks_up_existing_on_update(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->insert_id  = 0;
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->andReturn( 'SQL ...' );
		$wpdb_mock->shouldReceive( 'query' )->once()->andReturn( 2 );
		$wpdb_mock->shouldReceive( 'get_var' )->once()->andReturn( '77' );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$id = $this->repo->upsert_from_legacy(
			array(
				'legacy_post_id' => 500,
				'event_id'       => 100,
			)
		);

		$this->assertSame( 77, $id );
	}
}
