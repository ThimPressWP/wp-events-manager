<?php

class WPEMS_Admin_Calendar_Data {

	public static function load_events() {
		$args = [
			'post_type'   => 'tp_event',
			'post_status' => 'publish',
			'numberposts' => -1,
		];

		$posts = get_posts( $args );

		$posts = WPEMS_Data_Pattern::get_postMeta( $posts );

		$calendar_events = array();
		$type            = '';
		$category        = '';
		foreach ( $posts as $key => $value ) {
			$getType = wp_get_post_terms( $value->ID, 'tp_event_type' );
			if ( isset( $getType ) ) {
				foreach ( $getType as $item ) {
					$type = $item->name;
				}
			}

			$getCategory = wp_get_post_terms( $value->ID, 'tp_event_category' );
			if ( isset( $getCategory ) ) {
				foreach ( $getCategory as $item ) {
					$category = $item->name;
				}
			}

			$calendar_events[] = array(
				'id'          => $value->ID,
				'title'       => $value->post_title,
				'start'       => $value->date_start,
				'end'         => $value->date_end,
				'date_start'  => $value->date_start,
				'date_end'    => $value->date_end,
				'time_start'  => $value->time_start,
				'time_end'    => $value->time_end,
				'location'    => $value->location,
				'price'       => floatval( $value->price ),
				'totalTicket' => floatval( $value->totalTicket ),
				'type'        => $type,
				'category'    => $category,
			);
		}
		return $calendar_events;
	}
}


