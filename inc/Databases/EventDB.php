<?php
/**
 * Event database queries.
 *
 * @package WPEMS/Databases
 * @version 1.1.0
 * @since   2.3.0
 */

namespace WPEMS\Databases;

use WPEMS\Filters\EventFilter;

defined( 'ABSPATH' ) || exit;

/**
 * Class EventDB
 */
class EventDB extends DataBase {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function getInstance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get events by filter.
	 *
	 * Core filter-driven query method — mirrors PostDB::get_posts().
	 * Builds WHERE / JOIN clauses from EventFilter properties,
	 * then delegates to DataBase::execute().
	 *
	 * @param EventFilter $filter     Event filter.
	 * @param int         $total_rows Total rows (passed by reference).
	 *
	 * @return array|null|int|string
	 * @throws \Exception If query fails.
	 */
	public function get_events( EventFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'p';
		}

		$ca = $filter->collection_alias;

		// --- Post type ---
		$filter->where[] = $this->wpdb->prepare( "AND $ca.post_type = %s", $filter->post_type );

		// --- Single ID ---
		if ( ! empty( $filter->ID ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID = %d", $filter->ID );
		}

		// --- Post status ---
		$filter->post_status = (array) $filter->post_status;
		if ( ! empty( $filter->post_status ) ) {
			$post_status_format = PostDB::db_format_array( $filter->post_status, '%s' );
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholder list is generated from the sanitized status array size.
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_status IN ($post_status_format)", $filter->post_status );
		}

		// --- Taxonomy ---
		if ( ! empty( $filter->term_ids ) ) {
			$filter->term_ids = array_map( 'absint', $filter->term_ids );
			$term_ids_format  = implode( ',', $filter->term_ids );
			$filter->join[]   = "INNER JOIN $this->tb_term_relationships AS r_term_p ON $ca.ID = r_term_p.object_id";
			$filter->join[]   = "INNER JOIN $this->tb_term_taxonomy AS tx_p ON r_term_p.term_taxonomy_id = tx_p.term_taxonomy_id";
			$filter->where[]  = "AND r_term_p.term_taxonomy_id IN ($term_ids_format)";
			$filter->where[]  = $this->wpdb->prepare( 'AND tx_p.taxonomy = %s', $filter->taxonomy );
		}

		// --- Post IDs ---
		if ( ! empty( $filter->post_ids ) ) {
			$post_ids        = array_map( 'absint', $filter->post_ids );
			$post_ids_format = implode( ',', $post_ids );
			$filter->where[] = "AND $ca.ID IN ($post_ids_format)";
		}

		// --- Title search ---
		if ( ! empty( $filter->post_title ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_title LIKE %s", '%' . $filter->post_title . '%' );
		}

		// --- Post name ---
		if ( ! empty( $filter->post_name ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_name = %s", $filter->post_name );
		}

		// --- Author ---
		if ( isset( $filter->post_author ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_author = %d", $filter->post_author );
		}

		if ( ! empty( $filter->post_authors ) ) {
			$post_authors        = array_map( 'absint', $filter->post_authors );
			$post_authors_format = implode( ',', $post_authors );
			$filter->where[]     = "AND $ca.post_author IN ($post_authors_format)";
		}

		// --- Event-specific: date range via postmeta ---
		if ( ! empty( $filter->date_from ) ) {
			$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pm_date_start ON $ca.ID = pm_date_start.post_id AND pm_date_start.meta_key = 'tp_event_date_start'";
			$filter->where[] = $this->wpdb->prepare( 'AND pm_date_start.meta_value >= %s', $filter->date_from );
		}

		if ( ! empty( $filter->date_to ) ) {
			$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pm_date_end ON $ca.ID = pm_date_end.post_id AND pm_date_end.meta_key = 'tp_event_date_end'";
			$filter->where[] = $this->wpdb->prepare( 'AND pm_date_end.meta_value <= %s', $filter->date_to );
		}

		// --- Event-specific: meta status ---
		if ( ! empty( $filter->status ) ) {
			$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pm_status ON $ca.ID = pm_status.post_id AND pm_status.meta_key = 'tp_event_status'";
			$filter->where[] = $this->wpdb->prepare( 'AND pm_status.meta_value = %s', $filter->status );
		}

		$filter = apply_filters( 'wpems/event/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Get booking posts registered to an event.
	 *
	 * Uses the filter builder to construct a query across posts,
	 * postmeta, and users tables for bookings tied to the given event.
	 *
	 * @param EventFilter $filter Event filter (requires event_id).
	 * @param int         $total_rows Total rows (passed by reference).
	 *
	 * @return array
	 * @throws \Exception If query fails.
	 */
	public function get_registered_bookings( EventFilter $filter, int &$total_rows = 0 ): array {
		$event_id = absint( $filter->event_id );
		if ( ! $event_id ) {
			return array();
		}

		$filter->collection       = $this->tb_posts;
		$filter->collection_alias = 'booked';

		$filter->where[] = $this->wpdb->prepare( 'AND booked.post_type = %s', 'event_auth_book' );

		// Join event ID meta.
		$filter->join[]  = "LEFT JOIN $this->tb_postmeta AS event ON event.post_id = booked.ID";
		$filter->where[] = $this->wpdb->prepare( 'AND event.meta_key = %s', 'ea_booking_event_id' );
		$filter->where[] = $this->wpdb->prepare( 'AND event.meta_value = %d', $event_id );

		// Join booking quantity meta.
		$filter->join[]  = "LEFT JOIN $this->tb_postmeta AS book_quantity ON book_quantity.post_id = booked.ID";
		$filter->where[] = $this->wpdb->prepare( 'AND book_quantity.meta_key = %s', 'ea_booking_qty' );

		// Join booking user meta + users table.
		$filter->join[]  = "LEFT JOIN $this->tb_postmeta AS user_booked ON user_booked.post_id = booked.ID";
		$filter->join[]  = "LEFT JOIN $this->tb_users AS user ON user.ID = user_booked.meta_value";
		$filter->where[] = $this->wpdb->prepare( 'AND user_booked.meta_key = %s', 'ea_booking_user_id' );

		$filter = apply_filters( 'wpems/event/registered_bookings/filter', $filter );

		$results = $this->execute( $filter, $total_rows );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get booked quantity for an event.
	 *
	 * When `$filter->user_id` is set, returns the total quantity booked
	 * by that specific user regardless of booking status. When omitted,
	 * counts only bookings whose post_status is 'ea-completed'.
	 *
	 * @param EventFilter $filter Event filter (requires event_id; optional user_id).
	 *
	 * @return int
	 * @throws \Exception If query fails.
	 */
	public function get_booked_quantity( EventFilter $filter ): int {
		$event_id = absint( $filter->event_id );
		$user_id  = is_null( $filter->user_id ) ? null : absint( $filter->user_id );

		if ( ! $event_id ) {
			return 0;
		}

		$filter->collection       = $this->tb_postmeta;
		$filter->collection_alias = 'pm';
		$filter->only_fields      = array( 'SUM( pm.meta_value ) AS qty' );

		// Join booking post.
		$filter->join[]  = "INNER JOIN $this->tb_posts AS book ON book.ID = pm.post_id";
		$filter->where[] = $this->wpdb->prepare( 'AND pm.meta_key = %s', 'ea_booking_qty' );
		$filter->where[] = $this->wpdb->prepare( 'AND book.post_type = %s', 'event_auth_book' );

		// Scope by booking status when no specific user requested.
		if ( ! $user_id ) {
			$booking_status  = ! empty( $filter->booking_status ) ? $filter->booking_status : 'ea-completed';
			$filter->where[] = $this->wpdb->prepare( 'AND book.post_status = %s', $booking_status );
		}

		// Join user meta + users table only when user filtering is requested.
		if ( $user_id ) {
			$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pm2 ON pm2.post_id = book.ID";
			$filter->where[] = $this->wpdb->prepare( 'AND pm2.meta_key = %s', 'ea_booking_user_id' );
			$filter->join[]  = "INNER JOIN $this->tb_users AS user ON user.ID = pm2.meta_value";
			$filter->where[] = $this->wpdb->prepare( 'AND user.ID = %d', $user_id );
		}

		// Join event meta + events table.
		$filter->join[]  = "INNER JOIN $this->tb_postmeta AS pm3 ON pm3.post_id = book.ID";
		$filter->where[] = $this->wpdb->prepare( 'AND pm3.meta_key = %s', 'ea_booking_event_id' );
		$filter->join[]  = "INNER JOIN $this->tb_posts AS event ON event.ID = pm3.meta_value";
		$filter->where[] = $this->wpdb->prepare( 'AND event.ID = %d', $event_id );
		$filter->where[] = $this->wpdb->prepare( 'AND event.post_type = %s', 'tp_event' );

		$filter->run_query_count = false;
		$filter->limit           = -1;
		$filter->order_by        = '';
		$filter->order           = '';

		$filter = apply_filters( 'wpems/event/booked_quantity/filter', $filter );

		$query = $this->execute( $filter );
		$qty   = 0;
		if ( is_array( $query ) && ! empty( $query[0] ) ) {
			$qty = (int) $query[0]->qty;
		}

		return (int) apply_filters( 'event_auth_booked_quanity', $qty, $event_id, $user_id );
	}
}
