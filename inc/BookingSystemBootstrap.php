<?php
/**
 * Wires runtime hooks for the table-backed booking system.
 *
 * @package WPEMS
 */

namespace WPEMS;

use WPEMS\CLI\BookingCommands;
use WPEMS\Cron\CronBootstrap;
use WPEMS\Emails\BookingEmails;
use WPEMS\Frontend\AccountBookings;
use WPEMS\Frontend\CheckoutAjax;
use WPEMS\Integration\EventCounts;
use WPEMS\Integration\GdprEraser;
use WPEMS\Integration\GdprExporter;
use WPEMS\Migrations\BookingMigrator;
use WPEMS\Repositories\BookingMetaRepository;
use WPEMS\Repositories\BookingRepository;
use WPEMS\Repositories\CouponEventRepository;
use WPEMS\Repositories\CouponRepository;
use WPEMS\Repositories\CouponUsageRepository;
use WPEMS\Repositories\EventInventoryRepository;
use WPEMS\Repositories\PaymentSyncQueueRepository;
use WPEMS\Repositories\PaymentTransactionRepository;
use WPEMS\Services\BookingCheckoutService;
use WPEMS\Services\BookingStatusService;
use WPEMS\Services\CheckoutQuoteService;
use WPEMS\Services\CouponService;
use WPEMS\Services\PaymentSyncService;
use WPEMS\Services\TaxService;

defined( 'ABSPATH' ) || exit;

/**
 * Single entry point for booting Phase 19/20 runtime wiring.
 */
final class BookingSystemBootstrap {

	/** @var bool */
	private static bool $booted = false;

	/**
	 * Build dependencies and register frontend + cron hooks.
	 */
	public static function init(): void {
		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		$bookings    = new BookingRepository();
		$inventory   = new EventInventoryRepository();
		$booking_meta = new BookingMetaRepository();
		$sync_queue  = new PaymentSyncQueueRepository();

		$coupons = new CouponService(
			new CouponRepository(),
			new CouponEventRepository(),
			new CouponUsageRepository()
		);

		$status = new BookingStatusService( $bookings, $inventory, $coupons, $sync_queue );

		$tax            = new TaxService();
		$quote_service  = new CheckoutQuoteService( $tax, $coupons );
		$checkout       = new BookingCheckoutService(
			$bookings,
			$inventory,
			$quote_service,
			$coupons,
			new CouponRepository(),
			$status,
			$sync_queue,
			$booking_meta
		);

		// Frontend AJAX.
		( new CheckoutAjax( $quote_service, $checkout, $coupons ) )->register();

		// Cron jobs.
		( new CronBootstrap( PaymentSyncService::instance(), $bookings, $status ) )->register();

		// Phase 21 integrations — emails, user account, GDPR, event counts.
		( new BookingEmails( $bookings ) )->register();
		( new AccountBookings( $bookings ) )->register();
		( new GdprExporter( $bookings ) )->register();
		( new GdprEraser( $bookings ) )->register();
		( new EventCounts( $inventory ) )->register();

		// Phase 22 — WP-CLI commands (only when running under wp-cli).
		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( BookingCommands::class ) ) {
			$migrator = new BookingMigrator(
				$bookings,
				new PaymentTransactionRepository(),
				$inventory
			);
			$cli = new BookingCommands(
				$migrator,
				PaymentSyncService::instance(),
				$status,
				$bookings
			);
			\WP_CLI::add_command( 'wpems bookings', $cli );
		}
	}
}
