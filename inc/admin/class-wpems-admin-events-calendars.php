<?php

class WPEMS_Admin_Event_Calendar {


	public static function output() {
		// Calendar
		wp_enqueue_script( 'wpems-admin-fullcalendar-lb' );
		wp_enqueue_script( 'wpems-admin-calendar-js' );

		$events = new WPEMS_Admin_Calendar_Data();
		$events = $events->load_events();

		if ( ! is_array( $events ) ) {
			return;
		}
		wp_localize_script( 'wpems-admin-calendar-js', 'eventCalendarData', $events );

		?>
		<div id='calendar'></div>

		<br>
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
