<?php
namespace WPEMS\Tests\Unit\Emails;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Emails\BookingEmails;
use WPEMS\Models\BookingTableModel;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Tests\Unit\TestCase;

class BookingEmailsTest extends TestCase {

	private $bookings, $emails;

	protected function setUp(): void {
		parent::setUp();
		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->emails   = new BookingEmails( $this->bookings );

		Functions\when( 'get_option' )->alias( function ( $key ) {
			if ( 'admin_email' === $key ) return 'admin@test.com';
			return 'Subject';
		} );
		Functions\when( 'wp_mail' )->justReturn( true );
		Functions\when( 'get_userdata' )->justReturn( (object) [ 'user_email' => 'user@test.com' ] );
		Functions\when( 'get_the_title' )->justReturn( 'Test Event' );
	}
	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	private function booking( array $o = [] ): BookingTableModel {
		return BookingTableModel::from_row( array_merge( [ 'id'=>1,'legacy_post_id'=>null,'event_id'=>10,'user_id'=>2,'qty'=>1,'subtotal'=>'100','discount_total'=>'10','tax_rate'=>'5','tax_total'=>'4.5','total'=>'94.5','currency'=>'USD','coupon_id'=>null,'payment_method'=>'paypal','payment_mode'=>'live','gateway_order_id'=>null,'status'=>'ea-pending','payment_status'=>'pending','hold_expires_at_gmt'=>null,'idempotency_key'=>'k','created_at_gmt'=>'2026-01-01','updated_at_gmt'=>'2026-01-01' ], $o ) );
	}

	public function test_on_status_changed_processing_sends_both(): void {
		Functions\when( 'get_option' )->alias( fn($k) => 'admin_email' === $k ? 'admin@test.com' : 'Subject' );
		$b = $this->booking();
		$this->bookings->shouldReceive( 'find' )->with( 1 )->once()->andReturn( $b );

		$sent = [];
		Functions\when( 'wp_mail' )->alias( function ( $to ) use ( &$sent ) { $sent[] = $to; return true; } );

		$this->emails->on_status_changed( 1, 'ea-pending', 'ea-processing', '' );
		$this->assertContains( 'user@test.com', $sent );
		$this->assertContains( 'admin@test.com', $sent );
	}

	public function test_on_status_changed_completed_sends_customer_only(): void {
		$b = $this->booking();
		$this->bookings->shouldReceive( 'find' )->with( 1 )->once()->andReturn( $b );
		$sent = [];
		Functions\when( 'wp_mail' )->alias( function ( $to ) use ( &$sent ) { $sent[] = $to; return true; } );

		$this->emails->on_status_changed( 1, 'ea-processing', 'ea-completed', '' );
		$this->assertCount( 1, $sent );
		$this->assertSame( 'user@test.com', $sent[0] );
	}

	public function test_on_status_changed_skips_completed_if_already(): void {
		$b = $this->booking();
		$this->bookings->shouldReceive( 'find' )->with( 1 )->once()->andReturn( $b );
		$sent = [];
		Functions\when( 'wp_mail' )->alias( function ( $to ) use ( &$sent ) { $sent[] = $to; return true; } );

		$this->emails->on_status_changed( 1, 'ea-completed', 'ea-completed', '' );
		$this->assertEmpty( $sent );
	}

	public function test_render_pricing_block_includes_tax(): void {
		$b = $this->booking();
		$block = $this->emails->render_pricing_block( $b );
		$this->assertStringContainsString( '5%', $block );
		$this->assertStringContainsString( '94.5', $block );
	}

	public function test_email_skips_for_unknown_booking(): void {
		$this->bookings->shouldReceive( 'find' )->with( 999 )->once()->andReturn( null );
		$sent = false;
		Functions\when( 'wp_mail' )->alias( function () use ( &$sent ) { $sent = true; } );
		$this->emails->on_status_changed( 999, '', 'ea-processing', '' );
		$this->assertFalse( $sent );
	}
}

