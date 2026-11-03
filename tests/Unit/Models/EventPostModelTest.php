<?php
/**
 * Event post model tests.
 *
 * @package WPEMS\Tests\Unit\Models
 */

namespace WPEMS\Tests\Unit\Models;

use Brain\Monkey\Functions;
use Mockery;
use stdClass;
use WPEMS\Databases\EventDB;
use WPEMS\Databases\PostDB;
use WPEMS\Models\EventPostModel;
use WPEMS\Tests\Unit\TestCase;

/**
 * Test event model behavior.
 */
class EventPostModelTest extends TestCase {

	/**
	 * Reset model and database singletons.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->resetStaticProperty( EventPostModel::class, 'instances', array() );
		$this->resetStaticProperty( EventDB::class, 'instance', null );
		$this->resetStaticProperty( PostDB::class, 'instance', null );
	}

	/**
	 * It finds event posts.
	 *
	 * @return void
	 */
	public function test_find_returns_model_for_event_post(): void {
		global $wpdb;

		$wpdb = $this->makePostLookupWpdb( $this->makePostRow( 11, 'tp_event', 'Event title' ) );

		$event = EventPostModel::find( 11 );

		$this->assertInstanceOf( EventPostModel::class, $event );
		$this->assertSame( 11, $event->get_id() );
		$this->assertSame( 'Event title', $event->post_title );
		$this->assertStringContainsString( "AND p.post_type = 'tp_event'", $wpdb->last_query );
		$this->assertStringContainsString( 'AND p.ID = 11', $wpdb->last_query );
	}

	/**
	 * It returns false when filtered event lookup has no row.
	 *
	 * @return void
	 */
	public function test_find_returns_false_for_wrong_type(): void {
		global $wpdb;

		$wpdb = $this->makePostLookupWpdb( null );

		$this->assertFalse( EventPostModel::find( 12 ) );
		$this->assertStringContainsString( "AND p.post_type = 'tp_event'", $wpdb->last_query );
		$this->assertStringContainsString( 'AND p.ID = 12', $wpdb->last_query );
	}

	/**
	 * It reads price and free state from meta.
	 *
	 * @return void
	 */
	public function test_is_free_and_get_price_read_event_price(): void {
		$event = new EventPostModel( $this->makePost( 13, 'tp_event' ) );

		Functions\expect( 'get_post_meta' )
			->once()
			->with( 13, 'tp_event_price', true )
			->andReturn( '0' );

		$this->assertSame( 0.0, $event->get_price() );
		$this->assertTrue( $event->is_free() );
	}

	/**
	 * It exposes legacy event metadata through typed getters.
	 *
	 * @return void
	 */
	public function test_typed_getters_return_legacy_event_meta(): void {
		$event                   = new EventPostModel( $this->makePost( 15, 'tp_event' ) );
		$event->meta_data        = new stdClass();
		$event->is_got_meta_data = true;

		$event->meta_data->tp_event_qty        = '25';
		$event->meta_data->tp_event_status     = 'happening';
		$event->meta_data->tp_event_date_start = '2026-05-05';
		$event->meta_data->tp_event_time_start = '09:30';
		$event->meta_data->tp_event_date_end   = '2026-05-06';
		$event->meta_data->tp_event_time_end   = '17:00';
		$event->meta_data->tp_event_location   = 'Main Hall';

		$this->assertSame( 25, $event->get_quantity() );
		$this->assertSame( 'happening', $event->get_status() );
		$this->assertSame( '2026-05-05', $event->get_date_start() );
		$this->assertSame( '09:30', $event->get_time_start() );
		$this->assertSame( '2026-05-06', $event->get_date_end() );
		$this->assertSame( '17:00', $event->get_time_end() );
		$this->assertSame( 'Main Hall', $event->get_location() );
		$this->assertSame( 'Main Hall', $event->get_meta( 'location' ) );
	}

	/**
	 * It subtracts completed booking quantity from slots.
	 *
	 * @return void
	 */
	public function test_get_slot_available_subtracts_bookings(): void {
		global $wpdb;

		$wpdb           = Mockery::mock( stdClass::class );
		$wpdb->posts    = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->users    = 'wp_users';
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'prepared sql' );
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array( (object) array( 'qty' => '3' ) ) );

		$event                          = new EventPostModel( $this->makePost( 14, 'tp_event' ) );
		$event->meta_data               = new stdClass();
		$event->meta_data->tp_event_qty = 10;
		$event->is_got_meta_data        = true;

		$this->assertSame( 7, $event->get_slot_available() );
	}
}
