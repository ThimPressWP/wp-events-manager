<?php

use WPEMS\Event_Db as Db;

class WPEMS_Admin_Event_Calendar {

	public static function output() {
		// Calendar
		wp_enqueue_script( 'wpems-admin-calendar-js' );

		$eventDB = new Db\WPEMS_Event_DB();
		$events  = $eventDB->calendar_data();

		if ( ! is_array( $events ) ) {
			return;
		}
		wp_localize_script( 'wpems-admin-calendar-js', 'eventData', $events );

		?>
		<div id='calendar-admin'></div>
		<div class='wrapper-event'>
			<div class="show-event-admin"></div>
		</div>
		<br>
		<table id="shortcode-eventCalendars">
			<tr>
				<td>
					Shortcode
				</td>
				<td class="shortcode-event-calendar">
					<input class="shortcode-event-calendar-input" type="text" value="[wp_event_calendars]">
					<!-- <i class="dashicons dashicons-admin-page"></i> -->
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
