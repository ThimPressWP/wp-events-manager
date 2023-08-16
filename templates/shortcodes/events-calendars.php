<?php
include_once  WPEMS_INC . 'database/class-wpems-calendar-data.php';

$event        = new WPEMS_Admin_Calendar_Data();
$events_table = $event->load_events();

array_unshift( $events_table, [ 'url' => WPEMS_ASSETS_URI . '/js/admin/calendar-event.js' ] );


if ( empty( $events_table ) ) {
	return;
}

wp_localize_script( 'calendar-event', 'passedData', $events_table );

?>
<div id='calendar'></div>
<?php
