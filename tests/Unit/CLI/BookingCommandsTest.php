<?php
namespace WPEMS\Tests\Unit\CLI;

use Mockery;
use WPEMS\CLI\BookingCommands;
use WPEMS\Migrations\BatchResult;
use WPEMS\Migrations\BookingMigrator;
use WPEMS\Migrations\InventoryRebuildReport;
use WPEMS\Migrations\VerifyReport;
use WPEMS\Payments\PaymentResult;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Tests\Unit\TestCase;

class BookingCommandsTest extends TestCase {

	private $migrator, $sync, $status, $bookings, $cmd;

	protected function setUp(): void {
		parent::setUp();
		$this->migrator = Mockery::mock( BookingMigrator::class );
		$this->sync     = Mockery::mock( PaymentSyncService::class );
		$this->status   = Mockery::mock( BookingStatusService::class );
		$this->bookings = Mockery::mock( BookingRepository::class );
		$this->cmd      = new class($this->migrator, $this->sync, $this->status, $this->bookings) extends BookingCommands {
			public array $logs = [];
			protected function cliLog( string $m ): void { $this->logs[] = $m; }
			protected function cliSuccess( string $m ): void { $this->logs[] = "OK:{$m}"; }
			protected function cliError( string $m ): void { throw new \RuntimeException( 'CLI error: ' . $m ); }
			protected function makeProgress( string $l, int $t ) { return null; }
			protected function tickProgress( $b, int $n ): void {}
			protected function finishProgress( $b ): void {}
			protected function formatTable( array $r ): void { $this->logs[] = 'table:' . count($r); }
		};
	}
	protected function tearDown(): void { Mockery::close(); parent::tearDown(); }

	public function test_migrate_dry_run_does_not_call_run_batch(): void {
		$this->migrator->shouldReceive( 'count_pending' )->once()->andReturn( 100 );
		$this->cmd->migrate( [], [ 'dry-run' => true ] );
		$this->assertStringContainsString( 'OK:Dry-run', end( $this->cmd->logs ) );
	}

	public function test_migrate_clamps_batch(): void {
		$this->migrator->shouldReceive( 'count_pending' )->once()->andReturn( 50 );
		$r = new BatchResult(); $r->imported = 50; $r->remaining = 0;
		$this->migrator->shouldReceive( 'run_batch' )->with( 1000 )->once()->andReturn( $r );

		$this->cmd->migrate( [], [ 'batch' => '5000' ] );
		$this->assertStringContainsString( 'Imported 50', end( $this->cmd->logs ) );
	}

	public function test_verify_migration_ok(): void {
		$report = new VerifyReport();
		$report->ok = true; $report->legacy_total = 10; $report->new_total = 10;
		$this->migrator->shouldReceive( 'verify' )->once()->andReturn( $report );

		$this->cmd->verify_migration( [], [] );
		$this->assertStringContainsString( 'OK:Verification passed', end( $this->cmd->logs ) );
	}

	public function test_sync_payments_passes_limit(): void {
		$r = PaymentResult::paid( 1, [] );
		$this->sync->shouldReceive( 'sync_due_bookings' )->with( 25 )->once()->andReturn( [ $r ] );

		$this->cmd->sync_payments( [], [ 'limit' => '25' ] );
		$this->assertStringContainsString( 'paid: 1', implode( '|', $this->cmd->logs ) );
	}

	public function test_mark_paid_calls_service(): void {
		$this->status->shouldReceive( 'mark_completed' )->with( 42, 'cli' )->once()->andReturn( true );
		$this->cmd->mark_paid( [ 42 ], [] );
		$this->assertStringContainsString( 'OK:Booking #42', end( $this->cmd->logs ) );
	}

	public function test_mark_paid_rejects_invalid_id(): void {
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'valid booking ID' );
		$this->cmd->mark_paid( [ 0 ], [] );
	}

	public function test_rebuild_inventory(): void {
		$r = new InventoryRebuildReport(); $r->events_processed = 5; $r->rows_updated = 30;
		$this->migrator->shouldReceive( 'rebuild_inventory' )->once()->andReturn( $r );

		$this->cmd->rebuild_inventory( [], [] );
		$this->assertStringContainsString( '5 events', end( $this->cmd->logs ) );
	}
}

