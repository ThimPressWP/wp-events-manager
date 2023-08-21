<?php

class WPEMS_Admin_Event_Calendar {

	public static function output() {
		// Calendar
		wp_enqueue_script( 'wpems-fullcalendar-lb-js' );
		wp_enqueue_script( 'calendar-event' );

		$event        = new WPEMS_Admin_Calendar_Data();
		$events_table = $event->load_events();

		if ( empty( $events_table ) ) {
			return;
		}

		wp_localize_script( 'calendar-event', 'eventCalendarData', $events_table );

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
			
			<tr>
				<td>
					Shortcode
				</td>
				<td class="shortcode-event-calendar">
					<input class="shortcode-event-calendar-input" type="text" value="[wp_event_sync_booking]">
					<i class="dashicons dashicons-admin-page"></i>
				</td>
			</tr>
			<tr>
				<td>
				</td>
				<td>
					Use to sync your ticket to google calendar
				</td>
			</tr>
		</table>

		<?php

	}
}
new WPEMS_Admin_Event_Calendar();
