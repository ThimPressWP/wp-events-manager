<?php

class WPEMS_Admin_Event_Calendar {

	public static function output() {
		// Calendar
		wp_enqueue_script( 'wpems-fullcalendar-lb-js' );
		wp_enqueue_script( 'calendar-event' );

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
