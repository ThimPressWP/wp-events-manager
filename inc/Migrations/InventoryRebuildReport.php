<?php
/**
 * Booking migration inventory rebuild report.
 *
 * @package WPEMS\Migrations
 * @since   3.0.0
 */

namespace WPEMS\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Value object returned after rebuilding migrated booking inventory.
 */
final class InventoryRebuildReport {

	/**
	 * Number of distinct events processed.
	 *
	 * @var int
	 */
	public int $events_processed = 0;

	/**
	 * Number of inventory rebuild calls completed.
	 *
	 * @var int
	 */
	public int $rows_updated = 0;
}
