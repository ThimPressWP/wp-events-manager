<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPEMS_Booking
 */
class WPEMS_Booking {

	private static $instance = null;
	public $post = null;
	public $ID = null;

	public function __construct( $id = null ) {

		if ( is_numeric( $id ) && get_post_type( $id ) === 'event_auth_book' ) {
			$this->post = get_post( $id );
		} else if ( $id instanceof WP_Post || is_object( $id ) ) {
			$this->post = $id;
		}

		if ( $this->post ) {
			$this->ID = $this->post->ID;
		}

	}

	/**
	 * Megic method
	 *
	 * @param type $key
	 *
	 * @return mixed
	 */
	public function __get( $key = null ) {

		switch ( $key ) {

			default:
				$result = get_post_meta( $this->ID, 'ea_booking_' . $key, true );
				break;
		}

		return $result;
	}

	// create booking
	public function create_booking( $args = array(), $payment ) {

		// current user
		$user = wp_get_current_user();
		// merge argument
		$args       = wp_parse_args( $args, array(
			'user_id'    => $user->ID,
			'event_id'   => 0,
			'qty'        => 1,
			'cost'       => 0,
			'payment_id' => false
		) );
		$booking_id = wp_insert_post( array(
			'post_title'   => sprintf( __( '%s booking event %s', 'wp-events-manager' ), $user->user_nicename, $args['event_id'] ),
			'post_content' => sprintf( __( '%s booking event %s with %s slot', 'wp-events-manager' ), $user->user_nicename, $args['event_id'], $args['qty'] ),
			'post_exceprt' => sprintf( __( '%s booking event %s with %s slot', 'wp-events-manager' ), $user->user_nicename, $args['event_id'], $args['qty'] ),
			'post_status'  => 'ea-pending',
			'post_type'    => 'event_auth_book'
		) );

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

	// update status
	public function update_status( $status = 'ea-completed' ) {
		if ( !$this->post || $this->post->post_type !== 'event_auth_book' ) {
			return;
		}
		if ( !$this->post || !$this->ID ) {
			throw new Exception( sprintf( __( 'Booking ID #%s is not exists.', 'wp-events-manager' ), $this->ID ) );
		}
		$old_status = get_post_status( $this->ID );

		if ( strpos( $status, 'ea-' ) === false ) {
			$status = 'ea-' . $status;
		}

		$id = wp_update_post( array( 'ID' => $this->ID, 'post_status' => $status ) );

		if ( $id && !is_wp_error( $id ) ) {
			// send email or anythings
			do_action( 'tp_event_updated_status', $id, $old_status, $status );

			do_action( 'tp_event_updated_status_' . $old_status . '_' . $status, $id, $old_status, $status );
		}
	}

	public static function instance( $id = null ) {
		$booking_id = null;
		if ( is_numeric( $id ) && get_post_type( $id ) === 'event_auth_book' ) {
			$post       = get_post( $id );
			$booking_id = $post->ID;
		} else if ( $id instanceof WP_Post || is_object( $id ) ) {
			$booking_id = $id->ID;
		}

		if ( !empty( self::$instance[$booking_id] ) ) {
			return self::$instance[$booking_id];
		}

		return self::$instance[$booking_id] = new self( $booking_id );
	}

}
