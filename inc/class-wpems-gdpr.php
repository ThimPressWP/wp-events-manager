<?php
/**
 * WP Events Manager GDPR class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * Class WPEMS_GDPR
 */
class WPEMS_GDPR {

	/**
	 * WPEMS_GDPR constructor.
	 */
	public function __construct() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_booking_personal_data_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_booking_personal_data_eraser' ) );
	}

	/**
	 * @param $exporters
	 *
	 * @return mixed
	 */
	public function register_booking_personal_data_exporter( $exporters ) {
		$exporters['wpems-booking'] = array(
			'exporter_friendly_name' => __( 'WPEMS Booking' ),
			'callback'               => array( $this, 'exporter_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * @param $erasers
	 *
	 * @return mixed
	 */
	public function register_booking_personal_data_eraser( $erasers ) {
		$erasers['wpems-booking'] = array(
			'eraser_friendly_name' => __( 'WPEMS Booking' ),
			'callback'             => array( $this, 'eraser_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * @param $email_address
	 * @param int $page
	 *
	 * @return array
	 */
	public function exporter_personal_data( $email_address, $page = 1 ) {

		$data_to_export = array();

		$user = get_user_by( 'email', $email_address );
		if ( false === $user ) {
			return array(
				'data' => $data_to_export,
				'done' => true,
			);
		}

		$bookings = $this->_query_booking( $user->ID );

		foreach ( $bookings as $booking_id ) {
			$booking = WPEMS_Booking::instance( $booking_id );

			$post_data_to_export = array(
				array(
					'name'  => __( 'ID', 'wp-events-manager' ),
					'value' => '#' . $booking_id,
				),
				array(
					'name'  => __( 'Created Date', 'wp-events-manager' ),
					'value' => get_the_date( get_option( 'date_format' ), $booking_id ),
				),
				array(
					'name'  => __( 'Customer Details', 'wp-events-manager' ),
					'value' => $email_address,
				),
				array(
					'name'  => __( 'Items', 'wp-events-manager' ),
					'value' => get_the_title( $booking->event_id ),
				),
				array(
					'name'  => __( 'Total', 'wp-events-manager' ),
					'value' => wpems_format_price( floatval( $booking->price ), $booking->currency ),
				),
				array(
					'name'  => __( 'Payment Method', 'wp-events-manager' ),
					'value' => $booking->payment_id ? wpems_get_payment_title( $booking->payment_id ) : __( 'No payment', 'wp-events-manager' ),
				),
				array(
					'name'  => __( 'Status', 'wp-events-manager' ),
					'value' => wpems_booking_status( $booking_id ),
				),
			);

			$data_to_export[] = array(
				'group_id'    => 'event_auth_book',
				'group_label' => __( 'Event Booking', 'wp-events-manager' ),
				'item_id'     => "post-{$booking_id}",
				'data'        => $post_data_to_export,
			);
		}

		return array(
			'data' => $data_to_export,
			'done' => true,
		);
	}

	/**
	 * @param $email_address
	 * @param int $page
	 *
	 * @return array
	 */
	public function eraser_personal_data( $email_address, $page = 1 ) {
		$eraser_data = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => 1,
		);

		if ( ! $user = get_user_by( 'email', $email_address ) ) {
			return $eraser_data;
		}

		$bookings = $this->_query_booking( $user->ID );
		foreach ( $bookings as $booking_id ) {
			wp_delete_post( $booking_id, true );
		}
		$eraser_data['items_removed'] = true;

		return $eraser_data;
	}

	/**
	 * @param $user_id
	 *
	 * @return array|null|object
	 */
	private function _query_booking( $user_id ) {

		if ( ! $user_id ) {
			return array();
		}

		global $wpdb;

		$booking = array();
		$query   = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT booking.ID FROM {$wpdb->prefix}posts AS booking 
				INNER JOIN {$wpdb->prefix}postmeta AS booking_meta ON booking.ID = booking_meta.post_id
				WHERE 
				booking.post_type = %s AND booking_meta.meta_key = %s AND booking_meta.meta_value = %s",
				'event_auth_book',
				'ea_booking_user_id',
				$user_id
			),
			ARRAY_A
		);

		if ( $query ) {
			foreach ( $query as $item ) {
				$booking[] = $item['ID'];
			}
		}

		return $booking;
	}
}

new WPEMS_GDPR();
