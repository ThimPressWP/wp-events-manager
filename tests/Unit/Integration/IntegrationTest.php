<?php
namespace WPEMS\Tests\Unit\Integration;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Integration\GdprExporter;
use WPEMS\Integration\GdprEraser;
use WPEMS\Integration\EventCounts;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Tests\Unit\TestCase;

class GdprExporterTest extends TestCase {

	private $bookings, $exporter;

	protected function setUp(): void {
		parent::setUp();
		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->exporter = new GdprExporter( $this->bookings );
		Functions\when( 'get_the_title' )->justReturn( 'Test Event' );
	}
	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	private function booking(): BookingTableModel {
		return BookingTableModel::from_row( [ 'id'=>1,'legacy_post_id'=>null,'event_id'=>10,'user_id'=>2,'qty'=>1,'subtotal'=>'50','discount_total'=>'0','tax_rate'=>'0','tax_total'=>'0','total'=>'50','currency'=>'USD','coupon_id'=>null,'payment_method'=>'','payment_mode'=>null,'gateway_order_id'=>null,'status'=>'ea-completed','payment_status'=>'paid','hold_expires_at_gmt'=>null,'idempotency_key'=>'k','created_at_gmt'=>'2026-01-01','updated_at_gmt'=>'2026-01-01' ] );
	}

	public function test_register_exporter_adds_entry(): void {
		$exporters = $this->exporter->register_exporter( [] );
		$this->assertArrayHasKey( 'wpems-bookings', $exporters );
	}

	public function test_export_returns_empty_for_unknown_email(): void {
		Functions\when( 'get_user_by' )->justReturn( false );
		$r = $this->exporter->export( 'no@test.com' );
		$this->assertEmpty( $r['data'] );
		$this->assertTrue( $r['done'] );
	}

	public function test_export_returns_booking_data(): void {
		Functions\when( 'get_user_by' )->justReturn( (object) [ 'ID'=>2 ] );
		$this->bookings->shouldReceive( 'query' )->once()->andReturn( [ $this->booking() ] );
		$this->bookings->shouldReceive( 'count' )->once()->andReturn( 1 );

		$r = $this->exporter->export( 'user@test.com' );
		$this->assertCount( 1, $r['data'] );
		$this->assertTrue( $r['done'] );
	}
}

class GdprEraserTest extends TestCase {

	private $bookings, $eraser;

	protected function setUp(): void {
		parent::setUp();
		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->eraser   = new GdprEraser( $this->bookings );
	}
	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	private function booking(): BookingTableModel {
		return BookingTableModel::from_row( [ 'id'=>1,'legacy_post_id'=>null,'event_id'=>10,'user_id'=>2,'qty'=>1,'subtotal'=>'50','discount_total'=>'0','tax_rate'=>'0','tax_total'=>'0','total'=>'50','currency'=>'USD','coupon_id'=>null,'payment_method'=>'','payment_mode'=>null,'gateway_order_id'=>null,'status'=>'ea-completed','payment_status'=>'paid','hold_expires_at_gmt'=>null,'idempotency_key'=>'k','created_at_gmt'=>'2026-01-01','updated_at_gmt'=>'2026-01-01' ] );
	}

	public function test_erase_anonymises_user_id(): void {
		Functions\when( 'get_user_by' )->justReturn( (object) [ 'ID'=>2 ] );
		$this->bookings->shouldReceive( 'query' )->once()->andReturn( [ $this->booking() ] );
		$this->bookings->shouldReceive( 'update' )->with( 1, [ 'user_id'=>0, 'idempotency_key'=>null ] )->once()->andReturn( true );

		$r = $this->eraser->erase( 'user@test.com' );
		$this->assertSame( 0, $r['items_removed'] );
		$this->assertSame( 1, $r['items_retained'] );
		$this->assertTrue( $r['done'] );
	}

	public function test_erase_does_not_delete_booking(): void {
		Functions\when( 'get_user_by' )->justReturn( (object) [ 'ID'=>2 ] );
		$this->bookings->shouldReceive( 'query' )->once()->andReturn( [ $this->booking() ] );
		$this->bookings->shouldReceive( 'update' )->once()->andReturn( true );
		// delete() should never be called.

		$r = $this->eraser->erase( 'user@test.com' );
		$this->assertTrue( $r['done'] );
	}
}

class EventCountsTest extends TestCase {

	private $inventory, $counts;

	protected function setUp(): void {
		parent::setUp();
		$this->inventory = Mockery::mock( EventInventoryRepository::class );
		$this->counts    = new EventCounts( $this->inventory );
	}
	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	public function test_filter_booked_count_returns_table_value(): void {
		global $wpdb;
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( '15' );
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( fn($s) => $s );

		$this->assertSame( 15, $this->counts->filter_booked_count( 0, 10 ) );
	}

	public function test_filter_booked_count_falls_back_when_no_row(): void {
		global $wpdb;
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( fn($s) => $s );

		$this->assertSame( 99, $this->counts->filter_booked_count( 99, 10 ) );
	}

	public function test_filter_available_count_returns_null_for_unlimited(): void {
		$this->inventory->shouldReceive( 'get_available_quantity' )->with( 10 )->once()->andReturn( null );
		$this->assertNull( $this->counts->filter_available_count( 0, 10 ) );
	}

	public function test_filter_available_count_returns_int(): void {
		$this->inventory->shouldReceive( 'get_available_quantity' )->with( 10 )->once()->andReturn( 42 );
		$this->assertSame( 42, $this->counts->filter_available_count( 0, 10 ) );
	}
}
