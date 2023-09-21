<?php
namespace WPEMS\Model;

use WPEMS\Database\WpemsAbstractEventDatabase;

class WpemsEventModel extends WpemsAbstractEventModel {
	// use this->database to get the title of event
	public function getEventTitle($event_id) {
		return $this->database->getEventDatabaseTitle($event_id);
	}

    public function getEventThumbnail($event_id) {
		return $this->database->getEventDatabaseThumbnail($event_id);
	}

    public function getEventContent($event_id) {
		return $this->database->getEventDatabaseContent($event_id);
	}

    public function getEventStartTime( $event_id ) {
		return $this->database->getEventDatabaseStartTime( $event_id );
	}

	public function getEventEndTime ( $event_id ) {
		return $this->database->getEventDatabaseEndTime( $event_id );
	}

	public function getEventStartDate ( $event_id ) {
		return $this->database->getEventDatabaseStartDate( $event_id );
	}

	public function getEventEndDate ( $event_id ) {
		return $this->database->getEventDatabaseEndDate( $event_id );
	}

	public function getEventRegisterEndTime ( $event_id ) {
		return $this->database->getEventDatabaseRegisterEndTime( $event_id );
	}

	public function getEventRegisterEndDate ( $event_id ) {
		return $this->database->getEventDatabaseRegisterEndDate( $event_id );
	}

    public function getEventLocationF ( $event_id ) {
		return $this->database->getEventDatabaseLoacationF( $event_id );
	}

    public function getEventSchedules ( $event_id ) {
		return $this->database->getEventDatabaseSchedules( $event_id );
	}
}
