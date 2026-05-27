<?php
/**
 * Unit tests for WPEMS\Tables\TableNames.
 *
 * @package WPEMS\Tests\Unit\Tables
 */

namespace WPEMS\Tests\Unit\Tables;

use WPEMS\Tables\TableNames;
use WPEMS\Tests\Unit\TestCase;

/**
 * @covers \WPEMS\Tables\TableNames
 */
class TableNamesTest extends TestCase {

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
	public function test_bookings_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_bookings', TableNames::bookings() );
	}

	/**
	 * @test
	 */
	public function test_booking_meta_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_booking_meta', TableNames::booking_meta() );
	}

	/**
	 * @test
	 */
	public function test_event_inventory_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_event_inventory', TableNames::event_inventory() );
	}

	/**
	 * @test
	 */
	public function test_coupons_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_coupons', TableNames::coupons() );
	}

	/**
	 * @test
	 */
	public function test_coupon_events_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_coupon_events', TableNames::coupon_events() );
	}

	/**
	 * @test
	 */
	public function test_coupon_usage_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_coupon_usage', TableNames::coupon_usage() );
	}

	/**
	 * @test
	 */
	public function test_payment_events_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_payment_events', TableNames::payment_events() );
	}

	/**
	 * @test
	 */
	public function test_payment_transactions_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_payment_transactions', TableNames::payment_transactions() );
	}

	/**
	 * @test
	 */
	public function test_payment_sync_queue_returns_prefixed_name(): void {
		$this->assertSame( 'wp_wpems_payment_sync_queue', TableNames::payment_sync_queue() );
	}

	/**
	 * @test
	 */
	public function test_all_returns_nine_tables(): void {
		$this->assertCount( 9, TableNames::all() );
	}

	/**
	 * @test
	 */
	public function test_all_entries_are_unique(): void {
		$all = TableNames::all();
		$this->assertSame( 9, count( array_unique( $all ) ) );
	}

	/**
	 * @test
	 */
	public function test_custom_prefix_is_respected(): void {
		$GLOBALS['wpdb']->prefix = 'custom_';
		$this->assertSame( 'custom_wpems_bookings', TableNames::bookings() );
		$this->assertStringStartsWith( 'custom_wpems_', TableNames::payment_sync_queue() );
	}

	/**
	 * @test
	 */
	public function test_all_entries_start_with_wpdb_prefix(): void {
		$GLOBALS['wpdb']->prefix = 'test_';
		foreach ( TableNames::all() as $name ) {
			$this->assertStringStartsWith( 'test_wpems_', $name );
		}
	}
}
