<?php
/**
 * WP Events Manager Event class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

class WPEMS_Event {

	public $post     = null;
	public $ID       = null;
	static $instance = null;

	public function __construct( $id = null ) {
		if ( is_numeric( $id ) && $id && get_post_type( $id ) === 'tp_event' ) {
			$this->post = get_post( $id );
		} elseif ( $id instanceof WP_Post || is_object( $id ) ) {
			$this->post = $id;
		}

		if ( $this->post ) {
			$this->ID = $this->post->ID;
		}
	}

	/**
	 * Magic method
	 *
	 * @param type $key
	 *
	 * @return mixed
	 */
	public function __get( $key = null ) {
		$result = null;
		switch ( $key ) {
			default:
				$result = get_post_meta( $this->ID, 'tp_event_' . $key, true );
				break;
		}
		return $result;
	}

	/**
	 * get event title
	 * @return string
	 */
	public function get_title() {
		return get_the_title( $this->ID );
	}

	/**
	 * is free
	 * @return type boolean
	 */
	public function is_free() {
		return ( ! $this->get_price() ) ? true : false;
	}

	/**
	 * get price
	 * @return type float
	 */
	public function get_price() {
		return floatval( $this->price );
	}

	/**
	 * registered
	 * @global type $wpdb
	 * @return array
	 */
	public function load_registered() {
		global $wpdb;
		$query = $wpdb->prepare(
			"
				SELECT booked.* FROM $wpdb->posts AS booked
					LEFT JOIN $wpdb->postmeta AS event ON event.post_id = booked.ID
					LEFT JOIN $wpdb->postmeta AS book_quanity ON book_quanity.post_id = booked.ID
					LEFT JOIN $wpdb->postmeta AS user_booked ON user_booked.post_id = booked.ID
					LEFT JOIN $wpdb->users AS user ON user.ID = user_booked.meta_value
				WHERE booked.post_type = %s
					AND event.meta_key = %s
					AND event.meta_value = %d
					AND user_booked.meta_key = %s
					AND book_quanity.meta_key = %s
			",
			'event_auth_book',
			'ea_booking_event_id',
			$this->ID,
			'ea_booking_user_id',
			'ea_booking_qty'
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * get available slot
	 * @return type
	 */
	public function get_slot_available() {
		return apply_filters( 'event_slot_available', $this->qty - $this->booked_quantity() );
	}

	/**
	 * register time
	 * @return init
	 */
	public function get_registered_time() {
		return apply_filters( 'event_registered_time', count( $this->load_registered() ) );
	}

	/**
	 * get booked quantity
	 * @global type $wpdb
	 *
	 * @param type  $user_id
	 *
	 * @return init
	 */
	public function booked_quantity( $user_id = null ) {
		global $wpdb;

		if ( $user_id && is_numeric( $user_id ) ) {
			$query = $wpdb->prepare(
				"
					SELECT SUM( pm.meta_value ) AS qty FROM $wpdb->postmeta AS pm
						INNER JOIN $wpdb->posts AS book ON book.ID = pm.post_id
						INNER JOIN $wpdb->postmeta AS pm2 ON pm2.post_id = book.ID
						INNER JOIN $wpdb->postmeta AS pm3 ON pm3.post_id = book.ID
						INNER JOIN $wpdb->posts AS event ON event.ID = pm3.meta_value
						INNER JOIN $wpdb->users AS user ON user.ID = pm2.meta_value
					WHERE
						pm.meta_key = %s
						AND book.post_type = %s
						AND pm2.meta_key = %s
						AND pm3.meta_key = %s
						AND event.ID = %d
						AND event.post_type = %s
						AND user.ID = %d
				",
				'ea_booking_qty',
				'event_auth_book',
				'ea_booking_user_id',
				'ea_booking_event_id',
				$this->ID,
				'tp_event',
				$user_id
			);
		} else {
			$query = $wpdb->prepare(
				"
					SELECT SUM( pm.meta_value ) AS qty FROM $wpdb->postmeta AS pm
						INNER JOIN $wpdb->posts AS book ON book.ID = pm.post_id
						INNER JOIN $wpdb->postmeta AS pm2 ON pm2.post_id = book.ID
						INNER JOIN $wpdb->postmeta AS pm3 ON pm3.post_id = book.ID
						INNER JOIN $wpdb->posts AS event ON event.ID = pm3.meta_value
						INNER JOIN $wpdb->users AS user ON user.ID = pm2.meta_value
					WHERE
						pm.meta_key = %s
						AND book.post_type = %s
						AND book.post_status = %s
						AND pm2.meta_key = %s
						AND pm3.meta_key = %s
						AND event.ID = %d
						AND event.post_type = %s
				",
				'ea_booking_qty',
				'event_auth_book',
				'ea-completed',
				'ea_booking_user_id',
				'ea_booking_event_id',
				$this->ID,
				'tp_event'
			);
		}

		return apply_filters( 'event_auth_booked_quanity', (int) $wpdb->get_var( $query ) );
	}

	/**
	 * WPEMS_Event instance
	 *
	 * @param WP_Post $id
	 *
	 * @return type
	 */
	public static function instance( $id, $option = null ) {
		$event_id = false;
		if ( is_numeric( $id ) && $id && get_post_type( $id ) === 'tp_event' ) {
			$post     = get_post( $id );
			$event_id = $post->ID;
		} elseif ( $id instanceof WP_Post || is_object( $id ) ) {
			$event_id = $id->ID;
		}

		if ( ! empty( self::$instance[ $event_id ] ) ) {
			return self::$instance[ $event_id ];
		}

		return self::$instance[ $event_id ] = new self( $event_id );
	}

}
