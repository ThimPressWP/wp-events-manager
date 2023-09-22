<?php
namespace WPEMS\Database;

class WpemsEventDatabase extends WpemsAbstractEventDatabase {
	private function __construct() {
		// Call the parent class's constructor
		parent::__construct();
	}

	// Query to get the title of event
	public function getEventDatabaseTitle( $event_id ) {
		$event_title = get_the_title( $event_id );
		return $event_title;
	}

	public function getEventDatabaseThumbnail( $event_id ) {
		$event_thumbnail_url = get_the_post_thumbnail_url( $event_id, 'full' );
		return $event_thumbnail_url;
	}

	public function getEventDatabaseContent( $event_id ) {
		$event_content = get_the_content( null, false, $event_id );
		return $event_content;
	}

	public function getEventDatabaseStartTime( $event_id ) {
		$event_start_time = get_post_meta( $event_id, 'tp_event_time_start', true );
		return $event_start_time;
	}

	public function getEventDatabaseEndTime( $event_id ) {
		$event_end_time = get_post_meta( $event_id, 'tp_event_time_end', true );
		return $event_end_time;
	}

	public function getEventDatabaseStartDate( $event_id ) {
		$event_start_date = get_post_meta( $event_id, 'tp_event_date_start', true );
		return $event_start_date;
	}

	public function getEventDatabaseEndDate( $event_id ) {
		$event_end_date = get_post_meta( $event_id, 'tp_event_date_end', true );
		return $event_end_date;
	}

	public function getEventDatabaseRegisterEndTime( $event_id ) {
		$event_register_end_time = get_post_meta( $event_id, 'tp_event_registration_end_time', true );
		return $event_register_end_time;
	}

	public function getEventDatabaseRegisterEndDate( $event_id ) {
		$event_register_end_date = get_post_meta( $event_id, 'tp_event_registration_end_date', true );
		return $event_register_end_date;
	}
	
	public function getEventDatabaseLocationF( $event_id ) {
		$event_location_f = get_post_meta( $event_id, 'tp_event_location_iframe', true );
		return $event_location_f;
	}

	public function getEventDatabaseSchedules( $event_id ) {
		$event_schedules = get_post_meta( $event_id, 'tp_event_schedules', true );
		return $event_schedules;
	}
}
