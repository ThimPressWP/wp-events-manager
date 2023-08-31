<?php

wp_enqueue_script( 'wpems-calendar-js' );

$events = WPEMS_Calendar_DB::load_events();

if ( ! is_array( $events ) ) {
	return;
}
wp_localize_script( 'wpems-calendar-js', 'eventCalendarData', $events );

?>
<div id='calendar-frontend'></div>
<div class='wrapper_event'>
	<div class="showEvent"></div>
</div>
