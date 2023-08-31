<?php

class WPEMS_Data_Pattern {

	public static function get_postMeta( $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$value->date_start  = get_post_meta( $value->ID, 'tp_event_date_start', true );
				$value->date_end    = get_post_meta( $value->ID, 'tp_event_date_end', true );
				$value->time_start  = get_post_meta( $value->ID, 'tp_event_time_start', true );
				$value->time_end    = get_post_meta( $value->ID, 'tp_event_time_end', true );
				$value->price       = get_post_meta( $value->ID, 'tp_event_price', true );
				$value->totalTicket = get_post_meta( $value->ID, 'tp_event_qty', true );
				$value->location    = get_post_meta( $value->ID, 'tp_event_location', true );
			}
		}
		return $array;
	}

	// To get filter data to display to the screen
	public static function get_filter( $taxonomy ) {
		$filter_data = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		return $filter_data;
	}

	public static function get_posts( $args ) {
		$posts = array();

		$default_args = [
			'post_type'      => 'tp_event',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'posts_per_page' => 9,
			'paged'          => 1,
		];

		$args = wp_parse_args( $args, $default_args );

		$posts = new WP_Query( $args );

		return $posts;
	}
}


