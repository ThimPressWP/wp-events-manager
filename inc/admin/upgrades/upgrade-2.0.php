<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'TP_EVENT_INSTALLING' ) || ! TP_EVENT_INSTALLING ) {
    exit();
}

/**
 * Update options
 */
$prefix = 'thimpress_events';
$settings = get_option( $prefix );
if ( $settings ) {
    foreach ( $settings as $name => $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $n => $v ) {
                if ( $name === 'email' ) {
                    if ( $n != 'email_subject' ) {
                        update_option( $prefix . '_' . $name . '_' . $n, $v );
                    } else {
                        update_option( $prefix . '_' . $n, $v );
                    }
                } else if ( $name === 'checkout' ) {
                    update_option( $prefix . '_' . $name . '_' . $n, $v );
                } else {
                    update_option( $prefix . '_' . $n, $v );
                }

            }
        }
    }
}

/**
 * Update post meta
 */
$event_args = array(
    'post_type' => 'tp_event',
    'posts_per_page'    => -1,
    'post_status'       => 'any'
);
$events = new WP_Query( $event_args );
if ( $events->have_posts() ) {
    while( $events->have_posts() ) {
        $events->the_post();
        $date_start = get_post_meta( get_the_ID(), 'tp_event_date_start', true );
        $time_start = get_post_meta( get_the_ID(), 'tp_event_time_start', true );
        $date_end = get_post_meta( get_the_ID(), 'tp_event_date_end', true );
        $time_end = get_post_meta( get_the_ID(), 'tp_event_time_end', true );
        
        $start = $date_start . ' ' . $time_start;
        $end = $date_end . ' ' . $time_end;
        
        update_post_meta( get_the_ID(), 'tp_event_start', date( 'Y-m-d H:i:s', strtotime( $start ) ) );
        update_post_meta( get_the_ID(), 'tp_event_end', date( 'Y-m-d H:i:s', strtotime( $end ) ) );
        
        // update for event auth
        $qty = get_post_meta( get_the_ID(), 'thimpress_event_auth_quantity', true );
        $price = get_post_meta( get_the_ID(), 'thimpress_event_auth_cost', true );
        update_post_meta( get_the_ID(), 'tp_event_qty', absint( $qty ) );
        update_post_meta( get_the_ID(), 'tp_event_price', absint( $price ) );

        $use_start_time = $time_start ? 'yes' : '';
        update_post_meta( get_the_ID(), 'tp_event_use_start_time', $use_start_time );

        $use_end_time = $time_end ? 'yes' : '';
        update_post_meta( get_the_ID(), 'tp_event_use_end_time', $use_end_time );
    }
    wp_reset_query();
}
