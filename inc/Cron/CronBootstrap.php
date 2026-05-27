<?php
/**
 * WPEMS cron job bootstrap.
 *
 * @package WPEMS\Cron
 */

namespace WPEMS\Cron;

use WPEMS\Repositories\BookingRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Tables\TableNames;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and dispatches WP-Cron jobs.
 */
class CronBootstrap {

	const SYNC_HOOK   = 'wpems_sync_pending_gateway_payments';
	const EXPIRE_HOOK = 'wpems_expire_booking_holds';
	const SCHEDULE    = 'wpems_5min';

	/** @var PaymentSyncService */
	private PaymentSyncService $sync;

	/** @var BookingRepository */
	private BookingRepository $bookings;

	/** @var BookingStatusService */
	private BookingStatusService $status;

	/**
	 * Constructor.
	 *
	 * @param PaymentSyncService  $sync     Payment sync service.
	 * @param BookingRepository   $bookings Booking repository.
	 * @param BookingStatusService $status   Status service.
	 */
	public function __construct(
		PaymentSyncService $sync,
		BookingRepository $bookings,
		BookingStatusService $status
	) {
		$this->sync     = $sync;
		$this->bookings = $bookings;
		$this->status   = $status;
	}

	/**
	 * Register hooks and schedule jobs.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'add_schedule' ) );
		add_action( self::SYNC_HOOK, array( $this, 'run_payment_sync' ) );
		add_action( self::EXPIRE_HOOK, array( $this, 'run_expire_holds' ) );

		// Schedule on init if not already scheduled (defensive).
		add_action(
			'init',
			function () {
				if ( ! wp_next_scheduled( self::SYNC_HOOK ) ) {
					wp_schedule_event( time() + 60, self::SCHEDULE, self::SYNC_HOOK );
				}
				if ( ! wp_next_scheduled( self::EXPIRE_HOOK ) ) {
					wp_schedule_event( time() + 90, self::SCHEDULE, self::EXPIRE_HOOK );
				}
			}
		);
	}

	/**
	 * Schedule jobs on activation.
	 *
	 * @return void
	 */
	public function activate(): void {
		if ( ! wp_next_scheduled( self::SYNC_HOOK ) ) {
			wp_schedule_event( time() + 60, self::SCHEDULE, self::SYNC_HOOK );
		}
		if ( ! wp_next_scheduled( self::EXPIRE_HOOK ) ) {
			wp_schedule_event( time() + 90, self::SCHEDULE, self::EXPIRE_HOOK );
		}
	}

	/**
	 * Unschedule jobs on deactivation.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		$sync_ts = wp_next_scheduled( self::SYNC_HOOK );
		if ( $sync_ts ) {
			wp_unschedule_event( $sync_ts, self::SYNC_HOOK );
		}

		$expire_ts = wp_next_scheduled( self::EXPIRE_HOOK );
		if ( $expire_ts ) {
			wp_unschedule_event( $expire_ts, self::EXPIRE_HOOK );
		}
	}

	/**
	 * Static activation wrapper (for register_activation_hook).
	 *
	 * @return void
	 */
	public static function activate_static(): void {
		$instance = new self(
			PaymentSyncService::instance(),
			new BookingRepository(),
			new BookingStatusService(
				new BookingRepository(),
				new \WPEMS\Repositories\EventInventoryRepository(),
				new \WPEMS\Services\CouponService(
					new \WPEMS\Repositories\CouponRepository(),
					new \WPEMS\Repositories\CouponEventRepository(),
					new \WPEMS\Repositories\CouponUsageRepository()
				),
				new \WPEMS\Repositories\PaymentSyncQueueRepository()
			)
		);
		$instance->activate();
	}

	/**
	 * Static deactivation wrapper (for register_deactivation_hook).
	 *
	 * @return void
	 */
	public static function deactivate_static(): void {
		$instance = new self(
			PaymentSyncService::instance(),
			new BookingRepository(),
			new BookingStatusService(
				new BookingRepository(),
				new \WPEMS\Repositories\EventInventoryRepository(),
				new \WPEMS\Services\CouponService(
					new \WPEMS\Repositories\CouponRepository(),
					new \WPEMS\Repositories\CouponEventRepository(),
					new \WPEMS\Repositories\CouponUsageRepository()
				),
				new \WPEMS\Repositories\PaymentSyncQueueRepository()
			)
		);
		$instance->deactivate();
	}

	/**
	 * Register the wpems_5min custom schedule.
	 *
	 * @param array $schedules Existing schedules.
	 *
	 * @return array
	 */
	public function add_schedule( array $schedules ): array {
		$schedules[ self::SCHEDULE ] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 5 minutes (WPEMS)', 'wp-events-manager' ),
		);

		return $schedules;
	}

	/**
	 * Run payment sync cron job.
	 *
	 * @return void
	 */
	public function run_payment_sync(): void {
		$limit   = (int) apply_filters( 'wpems_payment_sync_batch_size', 20 );
		$results = $this->sync->sync_due_bookings( $limit );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$by_status = array_count_values(
				array_map(
					function ( $r ) {
						return $r->get_status();
					},
					$results
				)
			);
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[WPEMS] payment sync: ' . wp_json_encode( $by_status ) );
		}
	}

	/**
	 * Run expire holds cron job.
	 *
	 * @return void
	 */
	public function run_expire_holds(): void {
		global $wpdb;

		$now   = gmdate( 'Y-m-d H:i:s' );
		$limit = (int) apply_filters( 'wpems_expire_holds_batch_size', 100 );
		$table = TableNames::bookings();

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, event_id, qty FROM {$table}
				WHERE status IN ('ea-pending','ea-processing')
				  AND payment_status IN ('unpaid','pending')
				  AND hold_expires_at_gmt IS NOT NULL
				  AND hold_expires_at_gmt <= %s
				LIMIT %d",
				$now,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable

		if ( empty( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$this->status->mark_expired( (int) $row['id'], 'hold_expired' );
		}
	}
}
