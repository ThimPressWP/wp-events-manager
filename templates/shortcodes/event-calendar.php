<?php

wp_enqueue_script( 'wpems-fullcalendar-lb' );
wp_enqueue_script( 'wpems-calendar-js' );

$events = new WPEMS_Admin_Calendar_Data();
$events = $events->load_events();

if ( ! is_array( $events ) ) {
	return;
}
wp_localize_script( 'wpems-calendar-js', 'eventCalendarData', $events );

?>
<div id='calendar'></div>
