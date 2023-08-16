<?php

class WPEMS_Admin_Event_Calendar {


	public function __construct() {     }
	public static function load_events() {
		global $wpdb;

		$query            = $wpdb->prepare(
			"
            SELECT * FROM $wpdb->posts AS post
                INNER JOIN $wpdb->postmeta AS meta ON post.ID = meta.post_id
            
            WHERE post.post_type = %s
        
            ",
			'tp_event'
		);
		$events           = $wpdb->get_results( $query );
		$formatted_events = array();

		foreach ( $events as $event ) {
			$formatted_events[] = array(
				'id'    => $event->ID,
				'title' => $event->post_title,
				'start' => $event->meta_key === 'tp_event_date_start' ? $event->meta_value : '',
			);
			// print_r($event);
		}

		return  $formatted_events;
	}



	public static function output() {
		include_once WPEMS_TEMPLATES . 'shortcodes/events-calendars.php';

		?>
		<table id="shortcode-eventCalendars">

			<tr>
				<td>
					Shortcode
				</td>
				<td class="shortcode-event-calendar">
					<input class="shortcode-event-calendar-input" type="text" value="[wp_event_calendars]">
					<i class="dashicons dashicons-admin-page"></i>
				</td>
			</tr>
			<tr>
				<td>

				</td>
				<td>
					Use to show event calendar on the frontend
				</td>
			</tr>
		</table>

		<?php

	}
}
new WPEMS_Admin_Event_Calendar();
