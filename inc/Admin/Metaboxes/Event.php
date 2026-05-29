<?php
/**
 * Event metabox.
 *
 * @package WPEMS\Admin\Metaboxes
 */

namespace WPEMS\Admin\Metaboxes;

use WPEMS\Repositories\EventInventoryRepository;

defined( 'ABSPATH' ) || exit;

/**
 * WP Events Manager event metabox.
 */
class Event {

	/**
	 * Save event metabox data.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $posted  Posted data.
	 *
	 * @return void
	 */
	public static function save( $post_id, $posted ) {
		if ( empty( $posted ) ) {
			return;
		}

		foreach ( $posted as $name => $value ) {
			if ( strpos( $name, 'tp_event_' ) !== 0 ) {
				continue;
			}
			update_post_meta( $post_id, $name, self::sanitize_meta_value( $name, $value ) );
		}
		// Start
		$start  = ! empty( $posted['tp_event_date_start'] ) ? sanitize_text_field( $posted['tp_event_date_start'] ) : '';
		$start .= $start && ! empty( $posted['tp_event_time_start'] ) ? ' ' . sanitize_text_field( $posted['tp_event_time_start'] ) : '';

		// End
		$end  = ! empty( $posted['tp_event_date_end'] ) ? sanitize_text_field( $posted['tp_event_date_end'] ) : '';
		$end .= $end && ! empty( $posted['tp_event_time_end'] ) ? ' ' . sanitize_text_field( $posted['tp_event_time_end'] ) : '';

		$event_start = strtotime( $start );
		$event_end   = strtotime( $end );
		if ( strtotime( $start ) === strtotime( $end ) ) {
			++$event_end;
		}
		if ( ( $start && ! $end ) || ( strtotime( $start ) > strtotime( $end ) ) ) {
			\WPEMS\Admin\Metaboxes::add_error( __( 'Please make sure event time is validate! The end time must be in future of the start time!', 'wp-events-manager' ) );
			// wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
		}

		$time        = strtotime( current_time( 'Y-m-d H:i' ) );
		$offset_time = get_option( 'gmt_offset' ) * 60 * 60;

		$status = 'expired';
		if ( $event_start && $event_end ) {
			if ( $event_start > $time ) {
				$status = 'upcoming';
			} elseif ( $event_start <= $time && $time < $event_end ) {
				$status = 'happening';
			} elseif ( $time >= $event_end ) {
				$status = 'expired';
			}

			wp_schedule_single_event(
				$event_start - $offset_time,
				'tp_event_schedule_status',
				array(
					$post_id,
					'happening',
				)
			);
			wp_schedule_single_event(
				$event_end - $offset_time,
				'tp_event_schedule_status',
				array(
					$post_id,
					'expired',
				)
			);
		}

		update_post_meta( $post_id, 'tp_event_status', $status );

		// Sync capacity to the inventory table.
		if ( class_exists( EventInventoryRepository::class ) ) {
			$inventory = new EventInventoryRepository();
			$inventory->rebuild_for_event( $post_id );
		}
	}

	/**
	 * Sanitize event meta before storing it.
	 *
	 * @param string $name  Meta key.
	 * @param mixed  $value Unslashed posted value.
	 *
	 * @return mixed
	 */
	private static function sanitize_meta_value( $name, $value ) {
		if ( is_array( $value ) ) {
			return map_deep( $value, 'sanitize_text_field' );
		}

		if ( 'tp_event_iframe' === $name ) {
			$allowed_html           = wp_kses_allowed_html( 'post' );
			$allowed_html['iframe'] = array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'style'           => true,
				'loading'         => true,
				'referrerpolicy'  => true,
				'allow'           => true,
				'allowfullscreen' => true,
				'frameborder'     => true,
			);

			return wp_kses( $value, $allowed_html );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Render metabox.
	 *
	 * @return void
	 */
	public static function render() {
		wpems_get_admin_template( 'metaboxes/event-settings.php' );
	}
}

if ( ! \class_exists( 'WPEMS_Admin_Metabox_Event', false ) ) {
	\class_alias( Event::class, 'WPEMS_Admin_Metabox_Event' );
}
