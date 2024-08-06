<?php
/**
 * Class WPEMSBookingDB
 *
 *@package WPEventsManager/Databases
 *
 * @author vuxminhthanh
 */

namespace WPEventsManager\Databases;

defined( 'ABSPATH' ) || exit();

use WPEventsManager\Databases\WPEMSDatabase;
use WPEventsManager\Filters\WPEMSBookingFilter;
use WPEventsManager\Filters\WPEMSEventFilter;

class WPEMSBookingDB extends WPEMSDatabase {
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	// create booking
	public function create_booking( $args = array() ) {

		// current user
		$user = wp_get_current_user();
		// merge argument
		$args       = wp_parse_args(
			$args,
			array(
				'user_id'    => $user->ID,
				'event_id'   => 0,
				'qty'        => 1,
				'cost'       => 0,
				'payment_id' => false,
			)
		);
		$booking_id = wp_insert_post(
			array(
				'post_title'   => sprintf( __( '%1$s booking event %2$s', 'wp-events-manager' ), $user->user_nicename, $args['event_id'] ),
				'post_content' => sprintf( __( '%1$s booking event %2$s with %3$s slot', 'wp-events-manager' ), $user->user_nicename, $args['event_id'], $args['qty'] ),
				'post_exceprt' => sprintf( __( '%1$s booking event %2$s with %3$s slot', 'wp-events-manager' ), $user->user_nicename, $args['event_id'], $args['qty'] ),
				'post_status'  => 'ea-pending',
				'post_type'    => 'event_auth_book',
			)
		);

		if ( is_wp_error( $booking_id ) ) {
			return $booking_id;
		} else {
			foreach ( $args as $key => $val ) {
				update_post_meta( $booking_id, 'ea_booking_' . $key, $val );
			}

			do_action( 'tp_event_create_new_booking', $booking_id, $args );
			return $booking_id;
		}
	}

	public function update( WPEMSBookingFilter $filter ) {
		$filter->collection = $this->tb_posts;
		$this->update_execute( $filter );
	}

	/**
	 * get registered
	 * @return array
	 */
	public function get_registered( $event_id ) {
		$filter                   = new WPEMSBookingFilter();
		$filter->collection_alias = 'booked';
		$filter->collection       = $this->tb_posts;
		$filter->only_fields      = [ 'booked.*' ];
		$filter->join[]           = "LEFT JOIN {$this->tb_postmeta} AS event ON event.post_id = booked.ID";
		$filter->join[]           = "LEFT JOIN {$this->tb_postmeta} AS book_quanity ON book_quanity.post_id = booked.ID";
		$filter->join[]           = "LEFT JOIN {$this->tb_postmeta} AS user_booked ON user_booked.post_id = booked.ID";
		$filter->join[]           = "LEFT JOIN {$this->tb_users} AS user ON user.ID = user_booked.meta_value";
		$filter->where[]          = 'AND booked.post_type = "' . WPEMSBookingFilter::POST_TYPE_BOOKING . '"';
		$filter->where[]          = 'AND event.meta_key = "' . WPEMSBookingFilter::META_KEY_BOOKING_EVENT . '"';
		$filter->where[]          = 'AND event.meta_value = ' . $event_id;
		$filter->where[]          = 'AND user_booked.meta_key = "' . WPEMSBookingFilter::META_KEY_BOOKING_USER . '"';
		$filter->where[]          = 'AND book_quanity.meta_key = "' . WPEMSBookingFilter::META_KEY_BOOKING_QTY . '"';

		$db = WPEMSDatabase::getInstance();
		return $db->execute( $filter );
	}

	/**
	 * get booked quantity by event id
	 *
	 * @param event_id
	 * @param user_id
	 *
	 * @return int
	 */
	public function get_booked_quantity_by_event_id( int $event_id, int $user_id = 0, string $status = '' ): int {
		$filter                   = new WPEMSBookingFilter();
		$filter->only_fields      = [ 'pm.meta_value' ];
		$filter->collection_alias = 'pm';
		$filter->collection       = $this->tb_postmeta;
		$filter->join[]           = "INNER JOIN {$this->tb_posts} AS book ON book.ID = pm.post_id";
		$filter->join[]           = "INNER JOIN {$this->tb_postmeta} AS pm2 ON pm2.post_id = book.ID";
		$filter->join[]           = "INNER JOIN {$this->tb_postmeta} AS pm3 ON pm3.post_id = book.ID";
		$filter->join[]           = "INNER JOIN {$this->tb_posts} AS event ON event.ID = pm3.meta_value";
		$filter->join[]           = "INNER JOIN {$this->tb_users} AS user ON user.ID = pm2.meta_value";
		$filter->where[]          = 'AND pm.meta_key = "' . WPEMSBookingFilter::META_KEY_BOOKING_QTY . '"';
		$filter->where[]          = 'AND book.post_type = "' . WPEMSBookingFilter::POST_TYPE_BOOKING . '"';
		$filter->where[]          = 'AND pm2.meta_key = "' . WPEMSBookingFilter::META_KEY_BOOKING_USER . '"';
		$filter->where[]          = 'AND pm3.meta_key = "' . WPEMSBookingFilter::META_KEY_BOOKING_EVENT . '"';
		$filter->where[]          = 'AND event.ID = ' . $event_id;
		$filter->where[]          = 'AND event.post_type = "' . WPEMSEventFilter::POST_TYPE_EVENT . '"';
		$filter->run_query_count  = true;
		$filter->query_count      = true;
		$filter->field_count      = 'pm.meta_value';

		if ( empty( $status ) && empty( $user_id ) ) {
			$filter->where[] = 'AND book.post_status = "' . WPEMSBookingFilter::STATUS_COMPLETED . '"';
		}

		if ( ! empty( $status ) ) {
			$filter->where[] = 'AND book.post_status = "' . $status . '"';
		}

		if ( ! empty( $user_id ) ) {
			$filter->where[] = 'AND book.post_status = ' . $user_id;
		}

		$db = WPEMSDatabase::getInstance();
		return apply_filters( 'event_auth_booked_quanity', (int) $db->execute( $filter ) );
	}
}
