<?php
/**
 * Admin bootstrap.
 *
 * @package WPEMS\Admin
 */

namespace WPEMS\Admin;

defined( 'ABSPATH' ) || exit;

use WPEMS\Admin\Tools\BookingMigrationPage;
use WPEMS\Migrations\BookingMigrator;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentTransactionRepository;

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
