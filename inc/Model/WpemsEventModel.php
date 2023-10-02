<?php
namespace WPEMS\Model;

use WPEMS\Database\WpemsEventDatabase;

class WpemsEventModel {
	protected $database;

	public function __construct() {
		$this->database = new WpemsEventDatabase();
	}

	public function getEventTitle( $event_id ) {
		$event_data = $this->database->get_instance( $event_id );
		return $event_data->post_title;
	}

	// public function getEventThumbnail( $event_id ) {
	// 	$event_data = $this->database->get_instance( $event_id );
	// 	return $event_data->;
	// }

	public function getEventContent( $event_id ) {
		$event_data = $this->database->get_instance( $event_id );
		return $event_data->post_content;
	}

	public function getEventStartTime( $event_id ) {
		$event_data = $this->database->get_instance( $event_id );
		return $event_data->tp_event_time_start;
	}

	// public function getEventEndTime( $event_id ) {
	// 	$event_data = $this->database->get_instance( $event_id );
	//     return $event_data->tp_event_time_end;
	// }

	// public function getEventStartDate( $event_id ) {
	// 	$event_data = $this->database->get_instance( $event_id );
	//     return $event_data->tp_event_date_start;
	// }

	// public function getEventEndDate( $event_id ) {
	// 	$event_data = $this->database->get_instance( $event_id );
	//     return $event_data->tp_event_date_end;
	// }

	// public function getEventRegisterEndTime( $event_id ) {
	// 	$event_data = $this->database->get_instance( $event_id );
	//     return $event_data->tp_event_registration_end_time;
	// }

	// public function getEventRegisterEndDate( $event_id ) {
	// 	$event_data = $this->database->get_instance( $event_id );
	//     return $event_data->tp_event_registration_end_date;
	// }

	// public function getEventLocationF( $event_id ) {
	// 	$event_data = $this->database->get_instance( $event_id );
	//     return $event_data->tp_event_location_iframe;
	// }

	// public function getEventIframe( $event_id ) {
	// 	return $this->database->getEventDatabaseIframe( $event_id );
	// }

	// public function getEventSchedules( $event_id ) {
	// 	return $this->database->getEventDatabaseSchedules( $event_id );
	// }
}
