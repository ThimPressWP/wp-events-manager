<?php

class WPEMS_Admin_Calendar_Data {

	public static function load_events() {
		$args = [
			'post_type'   => 'tp_event',
			'post_status' => 'publish',
			'numberposts' => -1,
		];

		$posts = get_posts( $args );

		$posts = \Wpems_Model_Event\WPEMS_Model_Event_List::get_postMeta( $posts );

		$calendar_events = array();
		foreach ( $posts as $key => $value ) {
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
				'type'        => wp_get_post_terms( $value->ID, 'tp_event_type' )[0]->name,
				'category'    => wp_get_post_terms( $value->ID, 'tp_event_category' )[0]->name,
			);
		}
		return $calendar_events;
	}
}
new WPEMS_Admin_Calendar_Data;

