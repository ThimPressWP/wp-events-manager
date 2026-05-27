<?php
/**
 * Booking migration verification report.
 *
 * @package WPEMS\Migrations
 * @since   3.0.0
 */

namespace WPEMS\Migrations;

defined( 'ABSPATH' ) || exit;

/**
 * Value object returned after migration verification.
 */
final class VerifyReport {

	/**
	 * Total legacy booking posts.
	 *
	 * @var int
	 */
	public int $legacy_total = 0;

	/**
	 * Total migrated booking rows.
	 *
	 * @var int
	 */
	public int $new_total = 0;

	/**
	 * Difference between legacy and new totals.
	 *
	 * @var int
	 */
	public int $diff_count = 0;

	/**
	 * Per-status count differences.
	 *
	 * @var array<string, int>
	 */
	public array $status_diff = array();

	/**
	 * Per-status total amount differences.
	 *
	 * @var array<string, string>
	 */
	public array $total_amount_diff = array();

	/**
	 * Whether verification found no differences.
	 *
	 * @var bool
	 */
	public bool $ok = false;
}
