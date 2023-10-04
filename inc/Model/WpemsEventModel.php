<?php
namespace WPEMS\Model;

use WPEMS\Database\WpemsEventDatabase;

class WpemsEventModel {
	private $database;

	public function __construct() {
        $this->database = WpemsEventDatabase::get_instance();
    }

	private function fetchEventData( $event_id ) {
        $event_data = $this->database->getEventData( $event_id );
        return $event_data;
	}

	private function getEventDataValue( $event_id, $property ) {
		$event_data = $this->fetchEventData( $event_id );
		return $event_data->$property;
	}

	public function getEventTitle( $event_id ) {
		return $this->getEventDataValue( $event_id, 'post_title' );
	}

	/**
	 * guid stores thumbnail path
	 */
	public function getEventThumbnail( $event_id ) {
		return $this->getEventDataValue( $event_id, 'guid' );
	}

	public function getEventContent( $event_id ) {
		return $this->getEventDataValue( $event_id, 'post_content' );
	}

	public function getEventStartTime( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_time_start' );
	}

	public function getEventEndTime( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_time_end' );
	}

	public function getEventStartDate( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_date_start' );
	}

	public function getEventEndDate( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_date_end' );
	}

	public function getEventRegisterEndTime( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_registration_end_time' );
	}

	public function getEventRegisterEndDate( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_registration_end_date' );
	}

	public function getEventLocationF( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_location_iframe' );
	}

	public function getEventIframe( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_iframe' );
	}

	public function getEventSchedules( $event_id ) {
		return $this->getEventDataValue( $event_id, 'tp_event_schedules' );
	}
}
