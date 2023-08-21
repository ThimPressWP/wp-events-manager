<?php

// Calendar
wp_enqueue_script( 'wpems-fullcalendar-lb-js' );
wp_enqueue_script( 'calendar' );
wp_enqueue_script( 'insert_calendar' );

$shortcode       = new WPEMS_Admin_Calendar_Data();
$shortcode_table = $shortcode->load_events();

if ( empty( $shortcode_table ) ) {
	return;
}

wp_localize_script( 'calendar', 'shortcodeCalendarData', $shortcode_table );

$bookingData = get_posts(
	[
		'post_status' => 'ea-completed',
		'post_type'   => 'event_auth_book',
		'numberposts' => -1,
	]
);
$eventData   = array();

if ( ! empty( $bookingData ) ) {
	foreach ( $bookingData as $key => $value ) {
		$eventData[] = array(
			'summary'    => $value->post_title,
			'event_date' => $value->post_date,
			'start'      => array(
				'dateTime' => get_post_meta( $value->ID, 'tp_event_date_start', true ),
				'timeZone' => 'UTC',
			),
			'end'        => array(
				'dateTime' => get_post_meta( $value->ID, 'tp_event_date_end', true ),
				'timeZone' => 'UTC',
			),
		);
	}
}

wp_localize_script( 'inset_calendar', 'insertGGCalendar', $eventData );

?>

<div id="shortcode"></div>



