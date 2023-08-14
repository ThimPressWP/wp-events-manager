<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WPEMS_INSTALLING' ) || ! WPEMS_INSTALLING ) {
	exit();
}

/**
 * Update event date meta
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

		$status   = get_post_status( get_the_ID() );
		$metadata = '';
		switch ( $status ) {
			case 'tp-event-upcoming':
				$metadata = 'upcoming';
				break;
			case 'tp-event-happenning':
				$metadata = 'happening';
				break;
			case 'tp-event-expired':
				$metadata = 'expired';
				break;
			default:
				$metadata = $status;
				break;
		}

		update_post_meta( get_the_ID(), 'tp_event_status', $metadata );

		if ( in_array( $status, array( 'tp-event-upcoming', 'tp-event-happenning', 'tp-event-expired' ) ) ) {
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
