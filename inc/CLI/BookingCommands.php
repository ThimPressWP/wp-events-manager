<?php
namespace WPEMS\CLI;

use WPEMS\Migrations\BookingMigrator;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;

defined( 'ABSPATH' ) || exit;

class BookingCommands {

	private BookingMigrator $migrator;
	private PaymentSyncService $sync;
	private BookingStatusService $status;
	private BookingRepository $bookings;

	public function __construct(
		BookingMigrator $migrator,
		PaymentSyncService $sync,
		BookingStatusService $status,
		BookingRepository $bookings
	) {
		$this->migrator = $migrator;
		$this->sync     = $sync;
		$this->status   = $status;
		$this->bookings = $bookings;
	}

	public function migrate( array $args, array $assoc ): void {
		$batch = max( 10, min( 1000, (int) ( $assoc['batch'] ?? 200 ) ) );
		$limit = isset( $assoc['limit'] ) ? (int) $assoc['limit'] : 0;

		$pending = $this->migrator->count_pending();
		$this->cliLog( "Pending legacy bookings: {$pending}" );

		if ( ! empty( $assoc['dry-run'] ) ) {
			$this->cliSuccess( 'Dry-run complete. No data modified.' );
			return;
		}

		$progress = $this->makeProgress( 'Migrating', $pending );
		$imported = 0;
		$failed   = 0;
		$total    = 0;

		while ( $pending > 0 ) {
			$result = $this->migrator->run_batch( $batch );
			$imported += $result->imported;
			$failed   += $result->failed;
			$total    += $result->imported + $result->failed;

			if ( $result->imported + $result->failed === 0 ) break;

			$this->tickProgress( $progress, $result->imported + $result->failed );
			$pending = $result->remaining;

			if ( $limit > 0 && $total >= $limit ) break;
		}

		$this->finishProgress( $progress );
		$this->cliSuccess( "Imported {$imported}, failed {$failed}." );
	}

	public function verify_migration( array $args, array $assoc ): void {
		$report = $this->migrator->verify();

		$this->formatTable( [
			[ 'Metric' => 'Legacy Total', 'Value' => (string) $report->legacy_total ],
			[ 'Metric' => 'New Total',   'Value' => (string) $report->new_total ],
			[ 'Metric' => 'Diff Count',  'Value' => (string) $report->diff_count ],
		] );

		if ( $report->ok ) {
			$this->cliSuccess( 'Verification passed.' );
		} else {
			$this->cliError( 'Verification failed.' );
		}
	}

	public function rebuild_inventory( array $args, array $assoc ): void {
		$report = $this->migrator->rebuild_inventory();
		$this->cliSuccess( "Rebuilt {$report->events_processed} events ({$report->rows_updated} rows)." );
	}

	public function sync_payments( array $args, array $assoc ): void {
		$limit   = max( 1, (int) ( $assoc['limit'] ?? 50 ) );
		$results = $this->sync->sync_due_bookings( $limit );

		$byStatus = [];
		foreach ( $results as $r ) {
			$s = $r->get_status();
			$byStatus[ $s ] = ( $byStatus[ $s ] ?? 0 ) + 1;
		}

		$this->cliLog( 'Payment sync results:' );
		foreach ( $byStatus as $status => $count ) {
			$this->cliLog( "  {$status}: {$count}" );
		}
		$this->cliSuccess( 'Payment sync complete.' );
	}

	public function expire_holds( array $args, array $assoc ): void {
		$cron = new \WPEMS\Cron\CronBootstrap( $this->sync, $this->bookings, $this->status );
		$cron->run_expire_holds();
		$this->cliSuccess( 'Hold expiry check complete.' );
	}

	public function mark_paid( array $args, array $assoc ): void {
		$bookingId = (int) ( $args[0] ?? 0 );
		if ( $bookingId <= 0 ) {
			$this->cliError( 'Please provide a valid booking ID.' );
		}

		$reason = (string) ( $assoc['reason'] ?? 'cli' );
		$ok     = $this->status->mark_completed( $bookingId, $reason );

		if ( $ok ) {
			$this->cliSuccess( "Booking #{$bookingId} marked as paid." );
		} else {
			$this->cliError( "Failed — booking #{$bookingId} may already be paid or not found." );
		}
	}

	// ─── WP-CLI wrappers (overridable for tests) ───

	protected function cliLog( string $msg ): void { \WP_CLI::log( $msg ); }
	protected function cliSuccess( string $msg ): void { \WP_CLI::success( $msg ); }
	protected function cliError( string $msg ): void { \WP_CLI::error( $msg ); }
	protected function makeProgress( string $label, int $total ) { return \WP_CLI\Utils\make_progress_bar( $label, $total ); }
	protected function tickProgress( $bar, int $n ): void { if ( $bar ) $bar->tick( $n ); }
	protected function finishProgress( $bar ): void { if ( $bar ) $bar->finish(); }
	protected function formatTable( array $rows ): void { \WP_CLI\Utils\format_items( 'table', $rows, [ 'Metric','Value' ] ); }
}
