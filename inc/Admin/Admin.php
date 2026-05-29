<?php
/**
 * Admin bootstrap.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

defined( 'ABSPATH' ) || exit;

use WPEMS\Admin\Bookings\AdminBookingManager;
use WPEMS\Admin\Bookings\BookingAdminActions;
use WPEMS\Admin\Coupons\AdminCouponManager;
use WPEMS\Admin\Coupons\CouponAdminActions;
use WPEMS\Admin\Tools\BookingMigrationPage;
use WPEMS\Migrations\BookingMigrator;
use WPEMS\Repositories\BookingMetaRepository;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\CouponService;
use WPEMS\Services\PaymentSyncService;

/**
 * WP Events Manager admin bootstrap.
 */
class Admin {

	/**
	 * Whether admin hooks have been registered.
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Register admin components.
	 *
	 * @return void
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		Menu::instance();
		Assets::init();
		Metaboxes::init();
		SettingsManager::init();
		self::init_booking_migration_page();
		self::init_booking_admin_page();
		self::init_coupon_admin_page();
	}

	/**
	 * Register the admin bookings page (Phase 17).
	 *
	 * @return void
	 */
	private static function init_booking_admin_page(): void {
		$bookings  = new BookingRepository();
		$inventory = new EventInventoryRepository();
		$queue     = new PaymentSyncQueueRepository();
		$coupons   = new CouponService(
			new CouponRepository(),
			new CouponEventRepository(),
			new CouponUsageRepository()
		);

		$status  = new BookingStatusService( $bookings, $inventory, $coupons, $queue );
		$sync    = PaymentSyncService::instance();
		$actions = new BookingAdminActions( $bookings, $status, $sync );

		( new AdminBookingManager(
			$bookings,
			new PaymentTransactionRepository(),
			$status,
			$sync,
			$actions,
			new BookingMetaRepository()
		) )->register();
	}

	/**
	 * Register the admin coupons page (Phase 18).
	 *
	 * @return void
	 */
	private static function init_coupon_admin_page(): void {
		$coupons       = new CouponRepository();
		$coupon_events = new CouponEventRepository();
		$coupon_usage  = new CouponUsageRepository();

		$service = new CouponService( $coupons, $coupon_events, $coupon_usage );
		$actions = new CouponAdminActions( $coupons, $coupon_events, $coupon_usage, $service );

		( new AdminCouponManager( $coupons, $coupon_events, $coupon_usage, $actions ) )->register();
	}

	/**
	 * Register the booking migration tool.
	 *
	 * @return void
	 */
	private static function init_booking_migration_page(): void {
		$migrator = new BookingMigrator(
			new BookingRepository(),
			new PaymentTransactionRepository(),
			new EventInventoryRepository()
		);

		( new BookingMigrationPage( $migrator ) )->register();
	}
}

if ( ! \class_exists( 'WPEMS_Admin', false ) ) {
	\class_alias( Admin::class, 'WPEMS_Admin' );
}
