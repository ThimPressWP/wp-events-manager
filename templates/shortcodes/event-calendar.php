<?php
use WPEMS\Model as Md;

wp_enqueue_script( 'wpems-calendar-js' );

$eventDB = new Md\WpemsEventsModel();
$events  = $eventDB->calendar_data();

if ( ! is_array( $events ) ) {
	return;
}
wp_localize_script( 'wpems-calendar-js', 'events', $events );

?>
<div id='calendar-frontend'></div>
<div class='wrapper-event'>
	<div class="show-event-frontend"></div>
</div>
