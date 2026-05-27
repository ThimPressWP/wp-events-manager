<?php
/**
 * Unit tests for WPEMS\Repositories\BookingMetaRepository.
 *
 * @package WPEMS\Tests\Unit\Repositories
 */

namespace WPEMS\Tests\Unit\Repositories;

use WPEMS\Repositories\BookingMetaRepository;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Repositories\BookingMetaRepository
 */
class BookingMetaRepositoryTest extends TestCase {

	/**
	 * @var BookingMetaRepository
	 */
	private BookingMetaRepository $repo;

	/**
	 * Set up mock $wpdb and repository.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$wpdb         = new \stdClass();
		$wpdb->prefix = 'wp_';
		$wpdb->last_error = '';

		$GLOBALS['wpdb'] = $wpdb;

		$this->repo = new BookingMetaRepository();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	// ─── get() ──────────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_get_returns_default_for_zero_booking_id(): void {
		$this->assertSame( 'fallback', $this->repo->get( 0, 'some_key', 'fallback' ) );
	}

	/**
	 * @test
	 */
	public function test_get_returns_default_for_empty_key(): void {
		$this->assertSame( 'fallback', $this->repo->get( 1, '', 'fallback' ) );
	}

	/**
	 * @test
	 */
	public function test_get_returns_default_for_missing_key(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_var' )->once()->andReturn( null );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertSame( 'default_val', $this->repo->get( 1, 'missing_key', 'default_val' ) );
	}

	/**
	 * @test
	 */
	public function test_get_returns_unserialized_value(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_var' )->once()->andReturn( 'hello' );

		$GLOBALS['wpdb'] = $wpdb_mock;

		// maybe_unserialize is stubbed in TestCase to return arg 1.
		$this->assertSame( 'hello', $this->repo->get( 1, 'greeting' ) );
	}

	// ─── update() ───────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_update_returns_false_for_invalid_inputs(): void {
		$this->assertFalse( $this->repo->update( 0, 'key', 'val' ) );
		$this->assertFalse( $this->repo->update( 1, '', 'val' ) );
	}

	/**
	 * @test
	 */
	public function test_update_inserts_when_missing(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		// Existence check returns null (no existing row).
		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT meta_id ...' );
		$wpdb_mock->shouldReceive( 'get_var' )->once()->andReturn( null );

		// Should call insert.
		$wpdb_mock->shouldReceive( 'insert' )
			->once()
			->with(
				'wp_wpems_booking_meta',
				\Mockery::on( function ( array $data ) {
					$this->assertSame( 5, $data['booking_id'] );
					$this->assertSame( 'attendee_name', $data['meta_key'] );
					$this->assertSame( 'John Doe', $data['meta_value'] );
					return true;
				} )
			)
			->andReturn( true );

		$GLOBALS['wpdb'] = $wpdb_mock;

		// maybe_serialize is stubbed to returnArg in base TestCase.
		\Brain\Monkey\Functions\when( 'maybe_serialize' )->returnArg( 1 );

		$this->assertTrue( $this->repo->update( 5, 'attendee_name', 'John Doe' ) );
	}

	/**
	 * @test
	 */
	public function test_update_replaces_when_exists(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		// Existence check returns an existing meta_id.
		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT meta_id ...' );
		$wpdb_mock->shouldReceive( 'get_var' )->once()->andReturn( '42' );

		// Should call update (not insert).
		$wpdb_mock->shouldReceive( 'update' )
			->once()
			->with(
				'wp_wpems_booking_meta',
				array( 'meta_value' => 'Jane Doe' ),
				array( 'meta_id' => 42 )
			)
			->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		\Brain\Monkey\Functions\when( 'maybe_serialize' )->returnArg( 1 );

		$this->assertTrue( $this->repo->update( 5, 'attendee_name', 'Jane Doe' ) );
	}

	// ─── delete() ───────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_delete_returns_false_for_invalid_inputs(): void {
		$this->assertFalse( $this->repo->delete( 0, 'key' ) );
		$this->assertFalse( $this->repo->delete( 1, '' ) );
	}

	/**
	 * @test
	 */
	public function test_delete_returns_true_on_success(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'delete' )
			->once()
			->with(
				'wp_wpems_booking_meta',
				array(
					'booking_id' => 5,
					'meta_key'   => 'attendee_name',
				)
			)
			->andReturn( 1 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertTrue( $this->repo->delete( 5, 'attendee_name' ) );
	}

	/**
	 * @test
	 */
	public function test_delete_returns_false_when_nothing_deleted(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'delete' )->once()->andReturn( 0 );

		$GLOBALS['wpdb'] = $wpdb_mock;

		$this->assertFalse( $this->repo->delete( 5, 'nonexistent_key' ) );
	}

	// ─── get_all() ──────────────────────────────────────────────────

	/**
	 * @test
	 */
	public function test_get_all_returns_empty_for_zero_booking_id(): void {
		$this->assertSame( array(), $this->repo->get_all( 0 ) );
	}

	/**
	 * @test
	 */
	public function test_get_all_returns_keyed_array(): void {
		$wpdb_mock = \Mockery::mock( 'wpdb' );
		$wpdb_mock->prefix     = 'wp_';
		$wpdb_mock->last_error = '';

		$wpdb_mock->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
		$wpdb_mock->shouldReceive( 'get_results' )->once()->andReturn(
			array(
				array( 'meta_key' => 'name', 'meta_value' => 'John' ),
				array( 'meta_key' => 'email', 'meta_value' => 'john@example.com' ),
			)
		);

		$GLOBALS['wpdb'] = $wpdb_mock;

		$result = $this->repo->get_all( 5 );

		$this->assertSame(
			array(
				'name'  => 'John',
				'email' => 'john@example.com',
			),
			$result
		);
	}
}
