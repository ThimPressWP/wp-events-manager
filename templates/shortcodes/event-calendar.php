<?php
use WPEMS\Event_Db as Db;

wp_enqueue_script( 'wpems-calendar-js' );

$eventDB = new Db\WPEMS_Event_DB();
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
