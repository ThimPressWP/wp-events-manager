<?php

class WPEMS_Admin_Calendar_Data {
	public function __construct(){}

	public static function load_events() {

		$posts = get_posts(
			[
				'post_type'   => 'tp_event',
				'post_status' => 'publish',
				'numberposts' => -1,
			]
		);

		$data = array();
		if ( $posts ) {
			foreach ( $posts as $key => $value ) {
				$data[] = array(
					'id'    => $value->ID,
					'title' => $value->post_title,
					'start' => get_post_meta( $value->ID, 'tp_event_date_start', true ),
					'end'   => get_post_meta( $value->ID, 'tp_event_date_end', true ),
				);

			}
		}
		return $data;
	}

}
new WPEMS_Admin_Calendar_Data;
