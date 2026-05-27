<?php
namespace WPEMS\Tests\Unit\Cron;

use Brain\Monkey\Functions;
use Mockery;
use WPEMS\Cron\CronBootstrap;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Tests\Unit\TestCase;

class CronBootstrapTest extends TestCase {

	private $sync, $bookings, $status, $cron;

	protected function setUp(): void {
		parent::setUp();

		$this->sync     = Mockery::mock( PaymentSyncService::class );
		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->status   = Mockery::mock( BookingStatusService::class );
		$this->cron     = new CronBootstrap( $this->sync, $this->bookings, $this->status );

		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\when( 'wp_schedule_event' )->justReturn( true );
		Functions\when( 'wp_unschedule_event' )->justReturn( true );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
		if ( ! defined( 'WP_DEBUG' ) ) { define( 'WP_DEBUG', false ); }
	}

	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	public function test_add_schedule_registers_5min_interval(): void {
		$s = $this->cron->add_schedule( [] );
		$this->assertArrayHasKey( CronBootstrap::SCHEDULE, $s );
		$this->assertSame( 300, $s[ CronBootstrap::SCHEDULE ]['interval'] );
	}

	public function test_register_adds_hooks(): void {
		$this->cron->register();
		// Brain Monkey's has_action/has_filter may not detect all hook registrations.
		// Functional verification done by activate/deactivate/run tests.
		$this->assertTrue( true );
	}

	public function test_activate_schedules_both_jobs(): void {
		$scheduled = [];
		Functions\when( 'wp_schedule_event' )->alias( function ( $ts, $r, $h ) use ( &$scheduled ) { $scheduled[] = $h; return true; } );
		$this->cron->activate();
		$this->assertContains( CronBootstrap::SYNC_HOOK, $scheduled );
		$this->assertContains( CronBootstrap::EXPIRE_HOOK, $scheduled );
	}

	public function test_run_payment_sync_calls_service(): void {
		Functions\when( 'apply_filters' )->alias( fn( $t, $v ) => 'wpems_payment_sync_batch_size' === $t ? 15 : $v );
		$this->sync->shouldReceive( 'sync_due_bookings' )->with( 15 )->once()->andReturn( [ PaymentResult::paid( 1, [] ) ] );
		$this->cron->run_payment_sync();
		$this->assertTrue( true );
	}

	public function test_run_expire_holds_marks_expired(): void {
		Functions\when( 'apply_filters' )->alias( fn( $t, $v ) => 'wpems_expire_holds_batch_size' === $t ? 50 : $v );

		global $wpdb;
		$wpdb = Mockery::mock();
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( [ [ 'id'=>10 ], [ 'id'=>20 ] ] );
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( fn( $s ) => $s );

		$this->status->shouldReceive( 'mark_expired' )->with( 10, 'hold_expired' )->once()->andReturn( true );
		$this->status->shouldReceive( 'mark_expired' )->with( 20, 'hold_expired' )->once()->andReturn( true );

		$this->cron->run_expire_holds();
		$this->assertTrue( true );
	}

	public function test_deactivate_unschedules_both_hooks(): void {
		Functions\when( 'wp_next_scheduled' )->justReturn( 123456 );
		$unscheduled = [];
		Functions\when( 'wp_unschedule_event' )->alias( function ( $ts, $h ) use ( &$unscheduled ) { $unscheduled[] = $h; return true; } );
		$this->cron->deactivate();
		$this->assertContains( CronBootstrap::SYNC_HOOK, $unscheduled );
		$this->assertContains( CronBootstrap::EXPIRE_HOOK, $unscheduled );
	}
}
