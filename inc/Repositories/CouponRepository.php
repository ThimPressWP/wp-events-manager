<?php
/**
 * Repository for wpems_coupons table.
 *
 * CRUD + atomic usage_count increment/decrement.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;
use RuntimeException;
use WPEMS\Models\CouponModel;
use WPEMS\Tables\TableNames;

/**
 * Coupon repository.
 */
class CouponRepository {

	/**
	 * Explicit column list for coupon reads.
	 *
	 * @var string[]
	 */
	private const SELECT_COLUMNS = array(
		'id',
		'code',
		'description',
		'discount_type',
		'percent_value',
		'amount_value',
		'max_discount_amount',
		'applies_to',
		'usage_limit',
		'usage_count',
		'usage_limit_per_user',
		'min_order_amount',
		'starts_at_gmt',
		'expires_at_gmt',
		'status',
		'created_at_gmt',
		'updated_at_gmt',
	);

	/**
	 * Columns allowed for ORDER BY.
	 *
	 * @var string[]
	 */
	private const ORDER_BY_WHITELIST = array(
		'id',
		'code',
		'usage_count',
		'created_at_gmt',
		'expires_at_gmt',
	);

	/**
	 * Keys that insert() requires.
	 *
	 * @var string[]
	 */
	private const REQUIRED_INSERT_KEYS = array(
		'code',
		'discount_type',
	);

	/**
	 * Keys that update() must never overwrite.
	 *
	 * @var string[]
	 */
	private const IMMUTABLE_KEYS = array(
		'id',
		'created_at_gmt',
		'usage_count',
	);

	/**
	 * Find a coupon by primary key.
	 *
	 * @param int $id Coupon ID.
	 *
	 * @return CouponModel|null
	 */
	public function find( int $id ): ?CouponModel {
		if ( $id <= 0 ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::coupons();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$sql     = $wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE id = %d LIMIT 1", $id );
		$row     = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? CouponModel::from_row( $row ) : null;
	}

	/**
	 * Find a coupon by its code (case-insensitive, trimmed).
	 *
	 * @param string $code Coupon code.
	 *
	 * @return CouponModel|null
	 */
	public function find_by_code( string $code ): ?CouponModel {
		$code = strtoupper( trim( $code ) );

		if ( '' === $code ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::coupons();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$sql     = $wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE code = %s LIMIT 1", $code );
		$row     = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? CouponModel::from_row( $row ) : null;
	}

	/**
	 * Insert a new coupon.
	 *
	 * @param array $data Column values.
	 *
	 * @return int New coupon ID.
	 *
	 * @throws InvalidArgumentException If required keys are missing.
	 * @throws RuntimeException         If the code already exists or insert fails.
	 */
	public function insert( array $data ): int {
		foreach ( self::REQUIRED_INSERT_KEYS as $key ) {
			if ( ! isset( $data[ $key ] ) || '' === trim( (string) $data[ $key ] ) ) {
				throw new InvalidArgumentException( "CouponRepository::insert() requires '{$key}'." );
			}
		}

		// Normalize code.
		$data['code'] = strtoupper( trim( $data['code'] ) );

		// Check uniqueness.
		$existing = $this->find_by_code( $data['code'] );
		if ( null !== $existing ) {
			throw new RuntimeException( 'duplicate_code' );
		}

		// Defaults.
		$data = array_merge(
			array(
				'usage_count' => 0,
				'status'      => 'active',
			),
			$data
		);

		$now                    = gmdate( 'Y-m-d H:i:s' );
		$data['created_at_gmt'] = $now;
		$data['updated_at_gmt'] = $now;

		global $wpdb;

		$wpdb->insert( TableNames::coupons(), $data );

		if ( '' !== $wpdb->last_error ) {
			throw new RuntimeException( $wpdb->last_error );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a coupon row.
	 *
	 * @param int   $id   Coupon ID.
	 * @param array $data Column values to update.
	 *
	 * @return bool True if the query executed successfully.
	 *
	 * @throws RuntimeException If a duplicate code is detected.
	 */
	public function update( int $id, array $data ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		// Strip immutable keys.
		foreach ( self::IMMUTABLE_KEYS as $key ) {
			unset( $data[ $key ] );
		}

		// Normalize and check code uniqueness if provided.
		if ( isset( $data['code'] ) ) {
			$data['code'] = strtoupper( trim( $data['code'] ) );

			$existing = $this->find_by_code( $data['code'] );
			if ( null !== $existing && $existing->get_id() !== $id ) {
				throw new RuntimeException( 'duplicate_code' );
			}
		}

		$data['updated_at_gmt'] = gmdate( 'Y-m-d H:i:s' );

		global $wpdb;

		$rows = $wpdb->update( TableNames::coupons(), $data, array( 'id' => $id ) );

		return false !== $rows;
	}

	/**
	 * Delete a coupon.
	 *
	 * Caller should void usage rows first (CouponService responsibility).
	 *
	 * @param int $id Coupon ID.
	 *
	 * @return bool True if at least one row was deleted.
	 */
	public function delete( int $id ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		global $wpdb;

		$rows = $wpdb->delete( TableNames::coupons(), array( 'id' => $id ) );

		return (bool) $rows;
	}

	/**
	 * Atomic conditional increment of usage_count.
	 *
	 * @param int $id                     Coupon ID.
	 * @param int $current_limit_snapshot Usage limit snapshot (0 = unlimited).
	 *
	 * @return bool True if the row was updated (usage allowed).
	 */
	public function increment_usage( int $id, int $current_limit_snapshot ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::coupons();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET usage_count = usage_count + 1, updated_at_gmt = %s
				WHERE id = %d
				  AND ( %d = 0 OR usage_count < %d )",
				$now,
				$id,
				$current_limit_snapshot,
				$current_limit_snapshot
			)
		);

		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Atomic decrement of usage_count (clamped to 0).
	 *
	 * Used when voiding usage on cancel/expire.
	 *
	 * @param int $id Coupon ID.
	 *
	 * @return bool True if the row was updated.
	 */
	public function decrement_usage( int $id ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = TableNames::coupons();
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET usage_count = GREATEST( CAST(usage_count AS SIGNED) - 1, 0 ),
				    updated_at_gmt = %s
				WHERE id = %d AND usage_count > 0",
				$now,
				$id
			)
		);

		return 1 === (int) $wpdb->rows_affected;
	}

	/**
	 * Run a filtered coupon query.
	 *
	 * @param CouponQuery $q Query parameters.
	 *
	 * @return CouponModel[]
	 */
	public function query( CouponQuery $q ): array {
		global $wpdb;

		$table   = TableNames::coupons();
		$columns = implode( ', ', self::SELECT_COLUMNS );

		list( $where_sql, $where_values ) = $this->build_where( $q );

		$order_by = in_array( $q->order_by, self::ORDER_BY_WHITELIST, true )
			? $q->order_by
			: 'created_at_gmt';

		$order = in_array( strtoupper( $q->order ), array( 'ASC', 'DESC' ), true )
			? strtoupper( $q->order )
			: 'DESC';

		$where_values[] = max( 1, $q->limit );
		$where_values[] = max( 0, $q->offset );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- WHERE placeholders are assembled in build_where().
		$sql = $wpdb->prepare(
			"SELECT {$columns} FROM {$table} {$where_sql} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d",
			...$where_values
		);
		// phpcs:enable

		$rows = $wpdb->get_results( $sql, ARRAY_A );

		return array_map( array( CouponModel::class, 'from_row' ), (array) $rows );
	}

	/**
	 * Count coupons matching the given query.
	 *
	 * @param CouponQuery $q Query parameters.
	 *
	 * @return int
	 */
	public function count( CouponQuery $q ): int {
		global $wpdb;

		$table = TableNames::coupons();

		list( $where_sql, $where_values ) = $this->build_where( $q );

		if ( empty( $where_values ) ) {
			$sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		} else {
			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- WHERE placeholders are assembled in build_where().
			$sql = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} {$where_sql}",
				...$where_values
			);
			// phpcs:enable
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Build WHERE clause from a CouponQuery.
	 *
	 * @param CouponQuery $q Query parameters.
	 *
	 * @return array{0: string, 1: array} SQL fragment and placeholder values.
	 */
	private function build_where( CouponQuery $q ): array {
		$clauses = array();
		$values  = array();

		if ( '' !== $q->status ) {
			$clauses[] = 'status = %s';
			$values[]  = $q->status;
		}

		if ( '' !== $q->discount_type ) {
			$clauses[] = 'discount_type = %s';
			$values[]  = $q->discount_type;
		}

		if ( '' !== $q->search ) {
			$clauses[] = 'code LIKE %s';
			$values[]  = '%' . $q->search . '%';
		}

		$where_sql = empty( $clauses )
			? ''
			: 'WHERE ' . implode( ' AND ', $clauses );

		return array( $where_sql, $values );
	}
}
