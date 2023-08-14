<?php
/**
 * WP Events Manager Upgrade version 2.0.8 action
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Action
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * Update event date meta
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

		// update for event date meta
		$start = get_post_meta( get_the_ID(), 'tp_event_date_start', true );
		$end   = get_post_meta( get_the_ID(), 'tp_event_date_end', true );

		if ( $start ) {
			update_post_meta( get_the_ID(), 'tp_event_date_start', date( 'Y-m-d', strtotime( $start ) ) );
		}
		if ( $end ) {
			update_post_meta( get_the_ID(), 'tp_event_date_end', date( 'Y-m-d', strtotime( $end ) ) );
		}

	}
	wp_reset_query();
}
