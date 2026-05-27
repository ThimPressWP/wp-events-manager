<?php
namespace WPEMS\Tests\Unit\Frontend;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Frontend\AccountBookings;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Tests\Unit\TestCase;

class AccountBookingsTest extends TestCase {

	private $bookings, $acct;

	protected function setUp(): void {
		parent::setUp();
		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->acct     = new AccountBookings( $this->bookings );
		Functions\when( 'get_the_title' )->justReturn( 'Test Event' );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'http://ex.com/account' );
		Functions\when( 'get_query_var' )->justReturn( 1 );
		Functions\when( 'add_query_arg' )->alias( fn($a,$u)=>$u.'?'.http_build_query($a) );
		Functions\when( 'esc_html_e' )->alias( fn($t) => print $t );
		Functions\when( 'esc_html__' )->returnArg( 1 );
	}
	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	private function booking(): BookingTableModel {
		return BookingTableModel::from_row( [ 'id'=>1,'legacy_post_id'=>null,'event_id'=>10,'user_id'=>2,'qty'=>1,'subtotal'=>'50','discount_total'=>'0','tax_rate'=>'0','tax_total'=>'0','total'=>'50','currency'=>'USD','coupon_id'=>null,'payment_method'=>'','payment_mode'=>null,'gateway_order_id'=>null,'status'=>'ea-completed','payment_status'=>'paid','hold_expires_at_gmt'=>null,'idempotency_key'=>'k','created_at_gmt'=>'2026-01-01','updated_at_gmt'=>'2026-01-01' ] );
	}

	public function test_shortcode_renders_login_prompt_for_guests(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		$html = $this->acct->shortcode_my_bookings( [] );
		$this->assertStringContainsString( 'log in', $html );
	}

	public function test_shortcode_queries_by_user_id(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 42 );

		$this->bookings->shouldReceive( 'query' )->once()->andReturn( [ $this->booking() ] );
		$this->bookings->shouldReceive( 'count' )->once()->andReturn( 1 );

		$html = $this->acct->shortcode_my_bookings( [] );
		$this->assertStringContainsString( 'ea-completed', $html );
		$this->assertStringContainsString( '50', $html );
	}

	public function test_fetch_for_user_sets_correct_query(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 7 );

		$this->bookings->shouldReceive( 'query' )->once()->andReturn( [] );
		$this->bookings->shouldReceive( 'count' )->once()->andReturn( 0 );

		$html = $this->acct->shortcode_my_bookings( [] );
		$this->assertStringContainsString( 'no bookings', $html );
	}
}
