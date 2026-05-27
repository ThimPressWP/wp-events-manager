<?php
/**
 * Repository for wpems_bookings table.
 *
 * Owns every $wpdb interaction with bookings. No business rules —
 * just CRUD + lookup.
 *
 * @package WPEMS\Repositories
 * @since   3.0.0
 */

namespace WPEMS\Repositories;

defined( 'ABSPATH' ) || exit;

use InvalidArgumentException;
use RuntimeException;
use WPEMS\Models\BookingTableModel;
use WPEMS\Tables\TableNames;

/**
 * Booking repository.
 */
class BookingRepository {

	/**
	 * Explicit column list for booking reads.
	 *
	 * @var string[]
	 */
	private const SELECT_COLUMNS = array(
		'id',
		'legacy_post_id',
		'event_id',
		'user_id',
		'qty',
		'subtotal',
		'discount_total',
		'tax_rate',
		'tax_total',
		'total',
		'currency',
		'coupon_id',
		'payment_method',
		'payment_mode',
		'gateway_order_id',
		'status',
		'payment_status',
		'hold_expires_at_gmt',
		'idempotency_key',
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
		'created_at_gmt',
		'total',
		'status',
		'event_id',
	);

	/**
	 * Keys that insert() requires.
	 *
	 * @var string[]
	 */
	private const REQUIRED_INSERT_KEYS = array(
		'event_id',
		'user_id',
		'qty',
		'payment_method',
		'status',
		'payment_status',
	);

	/**
	 * Keys that update() must never overwrite.
	 *
	 * @var string[]
	 */
	private const IMMUTABLE_KEYS = array(
		'id',
		'legacy_post_id',
		'created_at_gmt',
	);

	/**
	 * Find a booking by primary key.
	 *
	 * @param int $id Booking ID.
	 *
	 * @return BookingTableModel|null
	 */
	public function find( int $id ): ?BookingTableModel {
		if ( $id <= 0 ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::bookings();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$sql     = $wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE id = %d LIMIT 1", $id );
		$row     = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? BookingTableModel::from_row( $row ) : null;
	}

	/**
	 * Find a booking by its idempotency key.
	 *
	 * @param string $key Idempotency key.
	 *
	 * @return BookingTableModel|null
	 */
	public function find_by_idempotency_key( string $key ): ?BookingTableModel {
		if ( '' === $key ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::bookings();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$sql     = $wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE idempotency_key = %s LIMIT 1", $key );
		$row     = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? BookingTableModel::from_row( $row ) : null;
	}

	/**
	 * Find a booking by gateway payment method and order ID.
	 *
	 * @param string $method   Payment method slug.
	 * @param string $order_id Gateway order identifier.
	 *
	 * @return BookingTableModel|null
	 */
	public function find_by_gateway_order( string $method, string $order_id ): ?BookingTableModel {
		if ( '' === $method || '' === $order_id ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::bookings();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$sql     = $wpdb->prepare(
			"SELECT {$columns} FROM {$table} WHERE payment_method = %s AND gateway_order_id = %s ORDER BY id DESC LIMIT 1",
			$method,
			$order_id
		);
		$row     = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? BookingTableModel::from_row( $row ) : null;
	}

	/**
	 * Find a booking by its legacy CPT post ID.
	 *
	 * @param int $legacy_post_id Legacy post ID.
	 *
	 * @return BookingTableModel|null
	 */
	public function find_by_legacy_post_id( int $legacy_post_id ): ?BookingTableModel {
		if ( $legacy_post_id <= 0 ) {
			return null;
		}

		global $wpdb;

		$table   = TableNames::bookings();
		$columns = implode( ', ', self::SELECT_COLUMNS );
		$sql     = $wpdb->prepare( "SELECT {$columns} FROM {$table} WHERE legacy_post_id = %d LIMIT 1", $legacy_post_id );
		$row     = $wpdb->get_row( $sql, ARRAY_A );

		return $row ? BookingTableModel::from_row( $row ) : null;
	}

	/**
	 * Insert a new booking row.
	 *
	 * @param array $data Column values.
	 *
	 * @return int New booking ID.
	 *
	 * @throws InvalidArgumentException If required keys are missing.
	 * @throws RuntimeException         If the database insert fails.
	 */
	public function insert( array $data ): int {
		foreach ( self::REQUIRED_INSERT_KEYS as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				throw new InvalidArgumentException( "BookingRepository::insert() requires '{$key}'." );
			}
		}

		$defaults = array(
			'subtotal'       => '0',
			'discount_total' => '0',
			'tax_rate'       => '0',
			'tax_total'      => '0',
			'total'          => '0',
			'currency'       => '',
		);

		$data = array_merge( $defaults, $data );

		$now                    = gmdate( 'Y-m-d H:i:s' );
		$data['created_at_gmt'] = $now;
		$data['updated_at_gmt'] = $now;

		global $wpdb;

		$wpdb->insert( TableNames::bookings(), $data );

		if ( '' !== $wpdb->last_error ) {
			throw new RuntimeException( $wpdb->last_error );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a booking row.
	 *
	 * @param int   $id   Booking ID.
	 * @param array $data Column values to update.
	 *
	 * @return bool True if the query executed successfully.
	 */
	public function update( int $id, array $data ): bool {
		if ( $id <= 0 ) {
			return false;
		}

		// Strip immutable keys.
		foreach ( self::IMMUTABLE_KEYS as $key ) {
			unset( $data[ $key ] );
		}

		$data['updated_at_gmt'] = gmdate( 'Y-m-d H:i:s' );

		global $wpdb;

		$rows = $wpdb->update( TableNames::bookings(), $data, array( 'id' => $id ) );

		return false !== $rows;
	}

	/**
	 * Idempotent insert keyed on legacy_post_id.
	 *
	 * @param array $row Row data including legacy_post_id.
	 *
	 * @return int Booking ID (new or existing).
	 *
	 * @throws InvalidArgumentException If legacy_post_id is missing or invalid.
	 */
	public function upsert_from_legacy( array $row ): int {
		if ( empty( $row['legacy_post_id'] ) || (int) $row['legacy_post_id'] <= 0 ) {
			throw new InvalidArgumentException( 'upsert_from_legacy() requires a positive legacy_post_id.' );
		}

		$now = gmdate( 'Y-m-d H:i:s' );

		$row['created_at_gmt'] = $row['created_at_gmt'] ?? $now;
		$row['updated_at_gmt'] = $now;

		global $wpdb;

		$table   = TableNames::bookings();
		$columns = implode( ', ', array_keys( $row ) );
		$formats = implode( ', ', array_fill( 0, count( $row ), '%s' ) );

		$update_cols = array(
			'event_id',
			'user_id',
			'qty',
			'subtotal',
			'total',
			'currency',
			'payment_method',
			'status',
			'payment_status',
			'updated_at_gmt',
		);

		$update_parts = array();
		foreach ( $update_cols as $col ) {
			$update_parts[] = "{$col} = VALUES({$col})";
		}
		$update_clause = implode( ', ', $update_parts );

		// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- placeholder count is derived from $row.
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} ({$columns}) VALUES ({$formats})
			ON DUPLICATE KEY UPDATE {$update_clause}",
			array_values( $row )
		);
		// phpcs:enable

		$wpdb->query( $sql );

		if ( (int) $wpdb->insert_id > 0 ) {
			return (int) $wpdb->insert_id;
		}

		// Existing row — look up by legacy_post_id.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE legacy_post_id = %d LIMIT 1",
				(int) $row['legacy_post_id']
			)
		);

		return (int) $existing;
	}

	/**
	 * Run a filtered booking query.
	 *
	 * @param BookingQuery $q Query parameters.
	 *
	 * @return BookingTableModel[]
	 */
	public function query( BookingQuery $q ): array {
		global $wpdb;

		$table   = TableNames::bookings();
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

		return array_map( array( BookingTableModel::class, 'from_row' ), (array) $rows );
	}

	/**
	 * Count bookings matching the given query.
	 *
	 * @param BookingQuery $q Query parameters.
	 *
	 * @return int
	 */
	public function count( BookingQuery $q ): int {
		global $wpdb;

		$table = TableNames::bookings();

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
	 * Build WHERE clause from a BookingQuery.
	 *
	 * @param BookingQuery $q Query parameters.
	 *
	 * @return array{0: string, 1: array} SQL fragment and placeholder values.
	 */
	private function build_where( BookingQuery $q ): array {
		$clauses = array();
		$values  = array();

		if ( $q->event_id > 0 ) {
			$clauses[] = 'event_id = %d';
			$values[]  = $q->event_id;
		}

		if ( $q->user_id > 0 ) {
			$clauses[] = 'user_id = %d';
			$values[]  = $q->user_id;
		}

		if ( '' !== $q->status ) {
			$clauses[] = 'status = %s';
			$values[]  = $q->status;
		}

		if ( '' !== $q->payment_status ) {
			$clauses[] = 'payment_status = %s';
			$values[]  = $q->payment_status;
		}

		if ( '' !== $q->payment_method ) {
			$clauses[] = 'payment_method = %s';
			$values[]  = $q->payment_method;
		}

		if ( '' !== $q->date_from ) {
			$clauses[] = 'created_at_gmt >= %s';
			$values[]  = $q->date_from;
		}

		if ( '' !== $q->date_to ) {
			$clauses[] = 'created_at_gmt <= %s';
			$values[]  = $q->date_to;
		}

		if ( '' !== $q->search ) {
			$like      = '%' . $q->search . '%';
			$clauses[] = '( idempotency_key LIKE %s OR gateway_order_id LIKE %s )';
			$values[]  = $like;
			$values[]  = $like;
		}

		$where_sql = empty( $clauses )
			? ''
			: 'WHERE ' . implode( ' AND ', $clauses );

		return array( $where_sql, $values );
	}
}
