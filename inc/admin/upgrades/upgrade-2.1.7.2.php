<?php
/**
 * WP Events Manager Upgrade version 2.1.7.2 action
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Action
 * @version       2.1.7.2
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

update_option( 'thimpress-event-version', '2.1.7.2' );

/**
 * Update post meta
 */
$event_args = array(
	'post_type'      => 'tp_event',
	'posts_per_page' => - 1,
	'post_status'    => 'any',
);
$events     = new WP_Query( $event_args );
if ( $events->have_posts() ) {
	while ( $events->have_posts() ) {
		$events->the_post();

		$status = get_post_status( get_the_ID() );

		$meta = '';
		switch ( $status ) {
			case 'tp-event-expired':
				$meta = 'expired';
				break;
			case 'tp-event-happenning':
				$meta = 'happening';
				break;
			case 'tp-event-upcoming':
				$meta = 'upcoming';
				break;
			default:
				break;
		}

		if ( $meta ) {
			update_post_meta( get_the_ID(), 'tp_event_status', $meta );
			wp_update_post(
				array(
					'ID'          => get_the_ID(),
					'post_status' => 'publish',
				)
			);
		}
	}
	wp_reset_query();
}
