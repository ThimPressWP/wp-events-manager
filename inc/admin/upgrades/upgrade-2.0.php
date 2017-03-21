<?php

if ( !defined( 'ABSPATH' ) || !defined( 'WPEMS_INSTALLING' ) || !WPEMS_INSTALLING ) {
	exit();
}

/**
 * Update post meta
 */
$event_args = array(
	'post_type'      => 'tp_event',
	'posts_per_page' => - 1,
	'post_status'    => 'any'
);
$events     = new WP_Query( $event_args );
if ( $events->have_posts() ) {
	while ( $events->have_posts() ) {
		$events->the_post();

		// update for event auth
		$qty   = get_post_meta( get_the_ID(), 'thimpress_event_auth_quantity', true );
		$price = get_post_meta( get_the_ID(), 'thimpress_event_auth_cost', true );
		update_post_meta( get_the_ID(), 'tp_event_qty', absint( $qty ) );
		update_post_meta( get_the_ID(), 'tp_event_price', absint( $price ) );

		$start = strtotime( get_post_meta( get_the_ID(), 'tp_event_date_start', true ) . ' ' . get_post_meta( get_the_ID(), 'tp_event_time_start', true ) );
		$end   = strtotime( get_post_meta( get_the_ID(), 'tp_event_date_end', true ) . ' ' . get_post_meta( get_the_ID(), 'tp_event_time_end', true ) );
		$time  = current_time( 'timestamp' );


		$status = 'publish';
		if ( $start && $end ) {
			if ( $start > $time ) {
				$status = 'tp-event-upcoming';
			} else if ( $start <= $time && $time < $end ) {
				$status = 'tp-event-happenning';
			} else if ( $time >= $end ) {
				$status = 'tp-event-expired';
			}
		}
		if ( in_array( get_post_status( get_the_ID() ), array( 'tp-event-upcoming', 'tp-event-happenning', 'tp-event-expired' ) ) ) {
			wp_update_post( array( 'ID' => get_the_ID(), 'post_status' => $status ) );
		}

	}
	wp_reset_query();
}
