<?php
/**
 * Unit tests for WPEMS\Tables\SchemaManager.
 *
 * @package WPEMS\Tests\Unit\Tables
 */

namespace WPEMS\Tests\Unit\Tables;

use Brain\Monkey\Functions;
use InvalidArgumentException;
use WPEMS\Tables\SchemaManager;
use WPEMS\Tables\TableNames;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Tables\SchemaManager
 */
class SchemaManagerTest extends TestCase {

	/**
	 * Set up a mock $wpdb global with a known prefix.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$wpdb         = new \stdClass();
		$wpdb->prefix = 'wp_';

		$GLOBALS['wpdb'] = $wpdb;
	}

	/**
	 * Tear down the mock $wpdb global.
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
	public function test_get_create_table_sql_returns_create_for_each_table(): void {
		foreach ( TableNames::all() as $table ) {
			$sql = SchemaManager::get_create_table_sql( $table );

			$this->assertStringStartsWith( 'CREATE TABLE IF NOT EXISTS', $sql );
			$this->assertStringContainsString( $table, $sql );
		}
	}

	/**
	 * @test
	 */
	public function test_dbdelta_sql_uses_supported_create_table_form(): void {
		$method = new \ReflectionMethod( SchemaManager::class, 'get_dbdelta_sql' );
		$method->setAccessible( true );

		$sql = $method->invoke( null, TableNames::bookings() );

		$this->assertStringStartsWith( 'CREATE TABLE wp_wpems_bookings', $sql );
		$this->assertStringNotContainsString( 'IF NOT EXISTS', $sql );
	}

	/**
	 * @test
	 */
	public function test_get_create_table_sql_for_unknown_table_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		SchemaManager::get_create_table_sql( 'wp_wpems_nonexistent' );
	}

	/**
	 * @test
	 */
	public function test_schema_version_round_trip(): void {
		$stored = array();

		Functions\expect( 'update_option' )
			->once()
			->with( SchemaManager::OPTION_KEY, '9.9.9', false )
			->andReturnUsing(
				function ( $key, $value ) use ( &$stored ) {
					$stored[ $key ] = $value;
					return true;
				}
			);

		Functions\expect( 'get_option' )
			->once()
			->with( SchemaManager::OPTION_KEY, '0.0.0' )
			->andReturnUsing(
				function ( $key ) use ( &$stored ) {
					return $stored[ $key ] ?? '0.0.0';
				}
			);

		SchemaManager::set_schema_version( '9.9.9' );
		$this->assertSame( '9.9.9', SchemaManager::get_schema_version() );
	}

	/**
	 * @test
	 */
	public function test_get_schema_version_defaults_to_zero(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( SchemaManager::OPTION_KEY, '0.0.0' )
			->andReturn( '0.0.0' );

		$this->assertSame( '0.0.0', SchemaManager::get_schema_version() );
	}

	/**
	 * @test
	 */
	public function test_upgrade_skips_when_version_matches(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( SchemaManager::OPTION_KEY, '0.0.0' )
			->andReturn( SchemaManager::SCHEMA_VERSION );

		// If upgrade() would call install(), it would require ABSPATH . 'wp-admin/includes/upgrade.php'
		// which doesn't exist in tests. So if this test passes without error, upgrade was skipped.
		SchemaManager::upgrade();

		// No assertions needed — the test passes if upgrade() returns without attempting install().
		$this->assertTrue( true );
	}

	/**
	 * @test
	 */
	public function test_bookings_sql_contains_expected_columns(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::bookings() );

		$expected_columns = array(
			'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
			'legacy_post_id BIGINT UNSIGNED NULL',
			'event_id BIGINT UNSIGNED NOT NULL',
			'user_id BIGINT UNSIGNED NOT NULL',
			'qty INT UNSIGNED NOT NULL DEFAULT 1',
			'subtotal DECIMAL(15,4)',
			'discount_total DECIMAL(15,4)',
			'tax_rate DECIMAL(7,4)',
			'tax_total DECIMAL(15,4)',
			'total DECIMAL(15,4)',
			'currency CHAR(3)',
			'coupon_id BIGINT UNSIGNED NULL',
			'payment_method VARCHAR(20)',
			'payment_mode VARCHAR(20)',
			'gateway_order_id VARCHAR(64)',
			'status VARCHAR(20)',
			'payment_status VARCHAR(20)',
			'hold_expires_at_gmt DATETIME NULL',
			'idempotency_key VARCHAR(64)',
			'created_at_gmt DATETIME NOT NULL',
			'updated_at_gmt DATETIME NOT NULL',
		);

		foreach ( $expected_columns as $col ) {
			$this->assertStringContainsString( $col, $sql, "Missing column spec: {$col}" );
		}
	}

	/**
	 * @test
	 */
	public function test_bookings_sql_contains_expected_indexes(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::bookings() );

		$this->assertStringContainsString( 'PRIMARY KEY  (id)', $sql );
		$this->assertStringContainsString( 'UNIQUE KEY legacy_post_id', $sql );
		$this->assertStringContainsString( 'UNIQUE KEY idempotency_key', $sql );
		$this->assertStringContainsString( 'KEY event_status (event_id, status)', $sql );
		$this->assertStringContainsString( 'KEY status_hold_expiry (status, hold_expires_at_gmt)', $sql );
		$this->assertStringContainsString( 'KEY payment_order (payment_method, gateway_order_id)', $sql );
		$this->assertStringContainsString( 'KEY booking_list', $sql );
	}

	/**
	 * @test
	 */
	public function test_event_inventory_has_event_id_as_pk(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::event_inventory() );

		$this->assertStringContainsString( 'PRIMARY KEY  (event_id)', $sql );
		$this->assertStringContainsString( 'capacity INT UNSIGNED NOT NULL DEFAULT 0', $sql );
		$this->assertStringContainsString( 'held_qty INT UNSIGNED NOT NULL DEFAULT 0', $sql );
		$this->assertStringContainsString( 'confirmed_qty INT UNSIGNED NOT NULL DEFAULT 0', $sql );
	}

	/**
	 * @test
	 */
	public function test_coupons_sql_contains_unique_code_index(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::coupons() );

		$this->assertStringContainsString( 'UNIQUE KEY code (code)', $sql );
		$this->assertStringContainsString( 'discount_type VARCHAR(20)', $sql );
		$this->assertStringContainsString( 'percent_value DECIMAL(7,4)', $sql );
		$this->assertStringContainsString( 'amount_value DECIMAL(15,4)', $sql );
	}

	/**
	 * @test
	 */
	public function test_payment_transactions_sql_contains_unique_type_txn_id(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::payment_transactions() );

		$this->assertStringContainsString( 'UNIQUE KEY type_txn_id (type, gateway_transaction_id)', $sql );
		$this->assertStringContainsString( 'KEY booking_type_status (booking_id, type, status)', $sql );
	}

	/**
	 * @test
	 */
	public function test_payment_sync_queue_has_booking_id_as_pk(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::payment_sync_queue() );

		$this->assertStringContainsString( 'PRIMARY KEY  (booking_id)', $sql );
		$this->assertStringContainsString( 'KEY next_sync (next_sync_at_gmt)', $sql );
		$this->assertStringContainsString( 'KEY method_next_sync (payment_method, next_sync_at_gmt)', $sql );
	}

	/**
	 * @test
	 */
	public function test_no_sql_contains_enum(): void {
		foreach ( TableNames::all() as $table ) {
			$sql = SchemaManager::get_create_table_sql( $table );
			$this->assertStringNotContainsString( 'ENUM', strtoupper( $sql ), "Table {$table} must not use ENUM" );
		}
	}

	/**
	 * @test
	 */
	public function test_no_sql_contains_foreign_key(): void {
		foreach ( TableNames::all() as $table ) {
			$sql = SchemaManager::get_create_table_sql( $table );
			$this->assertStringNotContainsString( 'FOREIGN KEY', strtoupper( $sql ), "Table {$table} must not use FOREIGN KEY" );
		}
	}

	/**
	 * @test
	 */
	public function test_dbdelta_two_space_primary_key(): void {
		// dbDelta requires two spaces between PRIMARY KEY and the opening parenthesis.
		foreach ( TableNames::all() as $table ) {
			$sql = SchemaManager::get_create_table_sql( $table );
			$this->assertMatchesRegularExpression( '/PRIMARY KEY  \(/', $sql, "Table {$table} must have two spaces before PK column" );
		}
	}

	/**
	 * @test
	 */
	public function test_payment_events_unique_gateway_event(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::payment_events() );

		$this->assertStringContainsString( 'UNIQUE KEY gateway_event (gateway_id, event_id)', $sql );
		$this->assertStringContainsString( 'payload_hash CHAR(64)', $sql );
	}

	/**
	 * @test
	 */
	public function test_coupon_usage_unique_coupon_booking(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::coupon_usage() );

		$this->assertStringContainsString( 'UNIQUE KEY coupon_booking (coupon_id, booking_id)', $sql );
		$this->assertStringContainsString( 'KEY coupon_user_void (coupon_id, user_id, voided_at_gmt)', $sql );
		$this->assertStringContainsString( 'discount_applied DECIMAL(15,4) NOT NULL', $sql );
	}

	/**
	 * @test
	 */
	public function test_coupon_events_unique_coupon_event(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::coupon_events() );

		$this->assertStringContainsString( 'UNIQUE KEY coupon_event (coupon_id, event_id)', $sql );
		$this->assertStringContainsString( 'KEY event_id (event_id)', $sql );
	}

	/**
	 * @test
	 */
	public function test_booking_meta_indexes(): void {
		$sql = SchemaManager::get_create_table_sql( TableNames::booking_meta() );

		$this->assertStringContainsString( 'KEY booking_meta_key (booking_id, meta_key)', $sql );
		$this->assertStringContainsString( 'KEY meta_key (meta_key)', $sql );
		$this->assertStringContainsString( 'meta_value LONGTEXT NULL', $sql );
	}
}
