<?php
/**
 * Unit tests for WPEMS\Migrations\BookingMigrator.
 *
 * @package WPEMS\Tests\Unit\Migrations
 */

namespace WPEMS\Tests\Unit\Migrations;

use Brain\Monkey\Functions;
use Mockery;
use RuntimeException;
use WPEMS\Migrations\BookingMigrator;
use WPEMS\Models\PaymentTransactionModel;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Tests\Unit\TestCase;
use WP_Post;

/**
 * @covers \WPEMS\Migrations\BookingMigrator
 * @covers \WPEMS\Migrations\BatchResult
 * @covers \WPEMS\Migrations\VerifyReport
 * @covers \WPEMS\Migrations\InventoryRebuildReport
 */
class BookingMigratorTest extends TestCase {

	/**
	 * Fake wpdb instance.
	 *
	 * @var FakeBookingMigratorWpdb
	 */
	private FakeBookingMigratorWpdb $wpdb;

	/**
	 * Legacy posts keyed by ID.
	 *
	 * @var array<int, WP_Post>
	 */
	private array $posts = array();

	/**
	 * Post meta keyed by post ID and meta key.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $meta = array();

	/**
	 * Booking repository mock.
	 *
	 * @var Mockery\MockInterface|BookingRepository
	 */
	private $bookings;

	/**
	 * Transaction repository mock.
	 *
	 * @var Mockery\MockInterface|PaymentTransactionRepository
	 */
	private $txns;

	/**
	 * Inventory repository mock.
	 *
	 * @var Mockery\MockInterface|EventInventoryRepository
	 */
	private $inventory;

	/**
	 * Testable migrator.
	 *
	 * @var TestableBookingMigrator
	 */
	private TestableBookingMigrator $migrator;

	/**
	 * Set up fixture state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->wpdb            = new FakeBookingMigratorWpdb();
		$GLOBALS['wpdb']       = $this->wpdb;
		$this->bookings        = Mockery::mock( BookingRepository::class );
		$this->txns            = Mockery::mock( PaymentTransactionRepository::class );
		$this->inventory       = Mockery::mock( EventInventoryRepository::class );
		$this->migrator        = new TestableBookingMigrator( $this->bookings, $this->txns, $this->inventory );
		$posts                 = &$this->posts;
		$meta                  = &$this->meta;

		Functions\when( 'get_post' )->alias(
			static function ( $post_id ) use ( &$posts ) {
				return $posts[ (int) $post_id ] ?? null;
			}
		);

		Functions\when( 'get_post_meta' )->alias(
			static function ( $post_id, $key = '', $single = false ) use ( &$meta ) {
				if ( '' === $key ) {
					return $meta[ (int) $post_id ] ?? array();
				}

				return $meta[ (int) $post_id ][ $key ] ?? '';
			}
		);
	}

	/**
	 * Tear down globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function test_count_pending_excludes_already_migrated(): void {
		$this->addLegacyPost( 10 );
		$this->addLegacyPost( 11 );
		$this->addLegacyPost( 12 );
		$this->wpdb->mark_migrated(
			array(
				'legacy_post_id' => 10,
				'event_id'       => 100,
				'status'         => 'ea-completed',
				'total'          => '20.0000',
			),
			501
		);

		$this->assertSame( 2, $this->migrator->count_pending() );
	}

	/**
	 * @test
	 */
	public function test_run_batch_imports_n_posts_and_decrements_remaining(): void {
		$this->addLegacyPost( 1, 'ea-pending', array( 'event_id' => 101, 'qty' => 1, 'price' => '10' ) );
		$this->addLegacyPost( 2, 'ea-pending', array( 'event_id' => 102, 'qty' => 2, 'price' => '20' ) );
		$this->addLegacyPost( 3, 'ea-pending', array( 'event_id' => 103, 'qty' => 1, 'price' => '30' ) );
		$this->expect_upsert_marks_migrated();

		$result = $this->migrator->run_batch( 2 );

		$this->assertSame( 2, $result->imported );
		$this->assertSame( 0, $result->failed );
		$this->assertSame( 1, $result->remaining );
		$this->assertTrue( $this->wpdb->is_migrated( 1 ) );
		$this->assertTrue( $this->wpdb->is_migrated( 2 ) );
		$this->assertFalse( $this->wpdb->is_migrated( 3 ) );
	}

	/**
	 * @test
	 */
	public function test_run_batch_idempotent_on_replay(): void {
		$this->addLegacyPost( 1, 'ea-pending', array( 'event_id' => 101, 'price' => '10' ) );
		$this->addLegacyPost( 2, 'ea-pending', array( 'event_id' => 102, 'price' => '20' ) );
		$this->expect_upsert_marks_migrated();

		$first  = $this->migrator->run_batch( 200 );
		$second = $this->migrator->run_batch( 200 );

		$this->assertSame( 2, $first->imported );
		$this->assertSame( 0, $first->remaining );
		$this->assertSame( 0, $second->imported );
		$this->assertSame( 0, $second->remaining );
		$this->assertCount( 2, $this->wpdb->booking_rows );
	}

	/**
	 * @test
	 */
	public function test_run_batch_continues_after_one_failure(): void {
		$this->addLegacyPost( 1, 'ea-pending', array( 'event_id' => 101, 'price' => '10' ) );
		$this->addLegacyPost( 2, 'ea-pending', array( 'event_id' => 102, 'price' => '20' ) );
		$this->addLegacyPost( 3, 'ea-pending', array( 'event_id' => 103, 'price' => '30' ) );

		$this->bookings->shouldReceive( 'upsert_from_legacy' )
			->andReturnUsing(
				function ( array $data ): int {
					if ( 2 === (int) $data['legacy_post_id'] ) {
						throw new RuntimeException( 'bad legacy row' );
					}

					$booking_id = 700 + count( $this->wpdb->booking_rows );
					$this->wpdb->mark_migrated( $data, $booking_id );

					return $booking_id;
				}
			);

		$result = $this->migrator->run_batch( 3 );

		$this->assertSame( 2, $result->imported );
		$this->assertSame( 1, $result->failed );
		$this->assertArrayHasKey( 2, $result->errors );
		$this->assertTrue( $this->wpdb->is_migrated( 1 ) );
		$this->assertFalse( $this->wpdb->is_migrated( 2 ) );
		$this->assertTrue( $this->wpdb->is_migrated( 3 ) );
		$this->assertSame( 1, $result->remaining );
	}

	/**
	 * @test
	 */
	public function test_map_legacy_post_handles_missing_meta_with_defaults(): void {
		$post = $this->addLegacyPost( 90, 'draft', array(), 9 );

		$data = $this->migrator->expose_map_legacy_post( $post );

		$this->assertSame( 90, $data['legacy_post_id'] );
		$this->assertSame( 0, $data['event_id'] );
		$this->assertSame( 9, $data['user_id'] );
		$this->assertSame( 1, $data['qty'] );
		$this->assertSame( '0.0000', $data['subtotal'] );
		$this->assertSame( '0.0000', $data['discount_total'] );
		$this->assertSame( '0.0000', $data['tax_total'] );
		$this->assertSame( '0.0000', $data['total'] );
		$this->assertSame( 'USD', $data['currency'] );
		$this->assertSame( '', $data['payment_method'] );
		$this->assertNull( $data['payment_mode'] );
		$this->assertNull( $data['gateway_order_id'] );
		$this->assertSame( 'ea-pending', $data['status'] );
		$this->assertSame( 'unpaid', $data['payment_status'] );
	}

	/**
	 * @test
	 *
	 * @dataProvider legacy_status_provider
	 */
	public function test_map_legacy_status_for_each_legacy_status( string $legacy_status, array $expected ): void {
		$this->assertSame( $expected, $this->migrator->expose_map_legacy_status( $legacy_status ) );
	}

	/**
	 * @return array<string, array{string, array<string, string>}>
	 */
	public function legacy_status_provider(): array {
		return array(
			'completed'  => array( 'ea-completed', array( 'status' => 'ea-completed', 'payment_status' => 'paid' ) ),
			'publish'    => array( 'publish', array( 'status' => 'ea-completed', 'payment_status' => 'paid' ) ),
			'processing' => array( 'ea-processing', array( 'status' => 'ea-processing', 'payment_status' => 'pending' ) ),
			'pending'    => array( 'ea-pending', array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) ),
			'draft'      => array( 'draft', array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) ),
			'cancelled'  => array( 'ea-cancelled', array( 'status' => 'ea-cancelled', 'payment_status' => 'cancelled' ) ),
			'trash'      => array( 'trash', array( 'status' => 'ea-cancelled', 'payment_status' => 'cancelled' ) ),
			'failed'     => array( 'ea-failed', array( 'status' => 'ea-failed', 'payment_status' => 'failed' ) ),
			'expired'    => array( 'ea-expired', array( 'status' => 'ea-expired', 'payment_status' => 'unpaid' ) ),
			'unknown'    => array( 'private', array( 'status' => 'ea-pending', 'payment_status' => 'unpaid' ) ),
		);
	}

	/**
	 * @test
	 */
	public function test_maybe_insert_legacy_transaction_skips_when_already_exists(): void {
		$post = $this->addLegacyPost(
			81,
			'publish',
			array(
				'payment_id'     => 'paypal',
				'price'          => '40',
				'currency'       => 'eur',
				'transaction_id' => 'TXN-1',
			)
		);

		$this->txns->shouldReceive( 'find_by_txn_id' )
			->once()
			->with( 'paypal_standard_txn', 'TXN-1' )
			->andReturn(
				PaymentTransactionModel::from_row(
					array(
						'id'                     => 10,
						'booking_id'             => 9001,
						'type'                   => 'paypal_standard_txn',
						'gateway_transaction_id' => 'TXN-1',
						'amount'                 => '40.0000',
						'currency'               => 'EUR',
						'status'                 => 'completed',
						'created_at_gmt'         => '2026-01-01 00:00:00',
					)
				)
			);
		$this->txns->shouldReceive( 'insert' )->never();

		$this->migrator->expose_maybe_insert_legacy_transaction( 9001, $post );
		$this->addToAssertionCount( 1 );
	}

	/**
	 * @test
	 */
	public function test_verify_returns_ok_after_complete_migration(): void {
		$completed = $this->addLegacyPost( 41, 'ea-completed', array( 'event_id' => 301, 'price' => '49.99' ) );
		$pending   = $this->addLegacyPost( 42, 'ea-pending', array( 'event_id' => 302, 'price' => '0' ) );

		$this->wpdb->mark_migrated( $this->migrator->expose_map_legacy_post( $completed ), 901 );
		$this->wpdb->mark_migrated( $this->migrator->expose_map_legacy_post( $pending ), 902 );

		$report = $this->migrator->verify();

		$this->assertTrue( $report->ok );
		$this->assertSame( 2, $report->legacy_total );
		$this->assertSame( 2, $report->new_total );
		$this->assertSame( 0, $report->diff_count );
		$this->assertSame( array(), $report->status_diff );
		$this->assertSame( array(), $report->total_amount_diff );
	}

	/**
	 * @test
	 */
	public function test_rebuild_inventory_visits_each_unique_event_once(): void {
		$this->wpdb->booking_rows = array(
			array( 'id' => 1, 'legacy_post_id' => 11, 'event_id' => 100, 'status' => 'ea-pending', 'total' => '10.0000' ),
			array( 'id' => 2, 'legacy_post_id' => 12, 'event_id' => 100, 'status' => 'ea-completed', 'total' => '20.0000' ),
			array( 'id' => 3, 'legacy_post_id' => 13, 'event_id' => 101, 'status' => 'ea-completed', 'total' => '30.0000' ),
			array( 'id' => 4, 'legacy_post_id' => null, 'event_id' => 102, 'status' => 'ea-completed', 'total' => '40.0000' ),
		);

		$this->inventory->shouldReceive( 'rebuild_for_event' )->once()->with( 100 );
		$this->inventory->shouldReceive( 'rebuild_for_event' )->once()->with( 101 );

		$report = $this->migrator->rebuild_inventory();

		$this->assertSame( 2, $report->events_processed );
		$this->assertSame( 2, $report->rows_updated );
	}

	/**
	 * Add a legacy booking post and meta fixture.
	 *
	 * @param int    $id     Post ID.
	 * @param string $status Post status.
	 * @param array  $meta   Short-key meta values.
	 * @param int    $author Post author.
	 *
	 * @return WP_Post
	 */
	private function addLegacyPost( int $id, string $status = 'ea-pending', array $meta = array(), int $author = 1 ): WP_Post {
		$post              = $this->makePost( $id, 'event_auth_book', 'Booking ' . $id, $status );
		$post->post_author = $author;
		$this->posts[ $id ] = $post;
		$this->wpdb->add_legacy_post( $post );

		foreach ( $meta as $key => $value ) {
			$meta_key = ( 0 === strpos( $key, 'ea_booking_' ) || 0 === strpos( $key, '_event_auth_book_' ) )
				? $key
				: 'ea_booking_' . $key;

			$this->meta[ $id ][ $meta_key ] = $value;
		}

		return $post;
	}

	/**
	 * Configure the booking repo mock to store migrated rows in the fake DB.
	 *
	 * @return void
	 */
	private function expect_upsert_marks_migrated(): void {
		$this->bookings->shouldReceive( 'upsert_from_legacy' )
			->andReturnUsing(
				function ( array $data ): int {
					$booking_id = 500 + count( $this->wpdb->booking_rows );
					$this->wpdb->mark_migrated( $data, $booking_id );

					return $booking_id;
				}
			);
	}
}

/**
 * Test subclass that exposes protected methods.
 */
class TestableBookingMigrator extends BookingMigrator {

	/**
	 * Expose legacy post mapping.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return array
	 */
	public function expose_map_legacy_post( WP_Post $post ): array {
		return $this->map_legacy_post( $post );
	}

	/**
	 * Expose status mapping.
	 *
	 * @param string $status Status.
	 *
	 * @return array
	 */
	public function expose_map_legacy_status( string $status ): array {
		return $this->map_legacy_status( $status );
	}

	/**
	 * Expose transaction migration.
	 *
	 * @param int     $booking_id Booking ID.
	 * @param WP_Post $post       Post.
	 *
	 * @return void
	 */
	public function expose_maybe_insert_legacy_transaction( int $booking_id, WP_Post $post ): void {
		$this->maybe_insert_legacy_transaction( $booking_id, $post );
	}
}

/**
 * Small fake for the wpdb query surface used by BookingMigrator.
 */
class FakeBookingMigratorWpdb {

	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	public string $prefix = 'wp_';

	/**
	 * Posts table.
	 *
	 * @var string
	 */
	public string $posts = 'wp_posts';

	/**
	 * Last DB error.
	 *
	 * @var string
	 */
	public string $last_error = '';

	/**
	 * Last inserted ID.
	 *
	 * @var int
	 */
	public int $insert_id = 0;

	/**
	 * Legacy posts keyed by ID.
	 *
	 * @var array<int, WP_Post>
	 */
	public array $legacy_posts = array();

	/**
	 * Migrated booking rows.
	 *
	 * @var array<int, array>
	 */
	public array $booking_rows = array();

	/**
	 * Last prepare args.
	 *
	 * @var array
	 */
	private array $last_prepare_args = array();

	/**
	 * Add a legacy post fixture.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return void
	 */
	public function add_legacy_post( WP_Post $post ): void {
		$this->legacy_posts[ (int) $post->ID ] = $post;
	}

	/**
	 * Mark a legacy post as migrated.
	 *
	 * @param array $data       Booking row data.
	 * @param int   $booking_id Booking ID.
	 *
	 * @return void
	 */
	public function mark_migrated( array $data, int $booking_id ): void {
		$data['id']           = $booking_id;
		$this->booking_rows[] = $data;
	}

	/**
	 * Whether a legacy post ID has a migrated booking row.
	 *
	 * @param int $legacy_post_id Legacy post ID.
	 *
	 * @return bool
	 */
	public function is_migrated( int $legacy_post_id ): bool {
		foreach ( $this->booking_rows as $row ) {
			if ( (int) ( $row['legacy_post_id'] ?? 0 ) === $legacy_post_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Minimal prepare double.
	 *
	 * @param string $query SQL.
	 * @param mixed  ...$args Args.
	 *
	 * @return string
	 */
	public function prepare( string $query, ...$args ): string {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		$this->last_prepare_args = $args;

		return $query;
	}

	/**
	 * Minimal get_var double.
	 *
	 * @param string $query SQL.
	 *
	 * @return string|int|null
	 */
	public function get_var( string $query ) {
		if ( false !== strpos( $query, 'COUNT(*)' ) && false !== strpos( $query, 'NOT EXISTS' ) ) {
			return count( $this->pending_legacy_ids() );
		}

		return null;
	}

	/**
	 * Minimal get_col double.
	 *
	 * @param string $query SQL.
	 *
	 * @return array
	 */
	public function get_col( string $query ): array {
		if ( false !== strpos( $query, 'SELECT p.ID' ) ) {
			$limit = (int) ( $this->last_prepare_args[1] ?? 200 );

			return array_slice( $this->pending_legacy_ids(), 0, $limit );
		}

		if ( false !== strpos( $query, 'SELECT DISTINCT event_id' ) ) {
			$ids = array();
			foreach ( $this->booking_rows as $row ) {
				if ( null !== ( $row['legacy_post_id'] ?? null ) && (int) ( $row['event_id'] ?? 0 ) > 0 ) {
					$ids[] = (int) $row['event_id'];
				}
			}

			sort( $ids );

			return array_values( array_unique( $ids ) );
		}

		return array();
	}

	/**
	 * Minimal get_results double.
	 *
	 * @param string $query  SQL.
	 * @param string $output Output mode.
	 *
	 * @return array
	 */
	public function get_results( string $query, string $output = ARRAY_A ): array {
		if ( false !== strpos( $query, 'FROM wp_posts' ) ) {
			$rows = array();
			foreach ( $this->legacy_posts as $post ) {
				$rows[] = array(
					'ID'          => (int) $post->ID,
					'post_status' => (string) $post->post_status,
				);
			}

			return $rows;
		}

		if ( false !== strpos( $query, 'FROM wp_wpems_bookings' ) ) {
			return $this->group_migrated_rows_by_status();
		}

		return array();
	}

	/**
	 * Pending legacy post IDs.
	 *
	 * @return int[]
	 */
	private function pending_legacy_ids(): array {
		$ids = array();
		foreach ( $this->legacy_posts as $post ) {
			if ( ! $this->is_migrated( (int) $post->ID ) ) {
				$ids[] = (int) $post->ID;
			}
		}

		sort( $ids );

		return $ids;
	}

	/**
	 * Group migrated rows in the shape returned by wpdb.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function group_migrated_rows_by_status(): array {
		$groups = array();

		foreach ( $this->booking_rows as $row ) {
			if ( null === ( $row['legacy_post_id'] ?? null ) ) {
				continue;
			}

			$status = (string) ( $row['status'] ?? '' );
			if ( ! isset( $groups[ $status ] ) ) {
				$groups[ $status ] = array(
					'status'       => $status,
					'total_count'  => 0,
					'total_amount' => '0.0000',
				);
			}

			$groups[ $status ]['total_count']++;
			$groups[ $status ]['total_amount'] = number_format(
				(float) $groups[ $status ]['total_amount'] + (float) ( $row['total'] ?? 0 ),
				4,
				'.',
				''
			);
		}

		return array_values( $groups );
	}
}
