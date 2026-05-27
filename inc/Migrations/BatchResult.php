<?php
/**
 * Booking migration batch result.
 *
 * @package WPEMS\Migrations
 * @since   3.0.0
 */

namespace WPEMS\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Value object returned after a migration batch.
 */
final class BatchResult {

	/**
	 * Number of legacy posts imported or upserted.
	 *
	 * @var int
	 */
	public int $imported = 0;

	/**
	 * Number of legacy posts skipped.
	 *
	 * @var int
	 */
	public int $skipped = 0;

	/**
	 * Number of legacy posts that failed.
	 *
	 * @var int
	 */
	public int $failed = 0;

	/**
	 * Error messages keyed by legacy post ID.
	 *
	 * @var array<int, string>
	 */
	public array $errors = array();

	/**
	 * Remaining unmigrated legacy posts after the batch.
	 *
	 * @var int
	 */
	public int $remaining = 0;
}
