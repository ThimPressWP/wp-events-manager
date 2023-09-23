<?php
namespace WPEMS\Model;

use WPEMS\Database\WpemsEventDatabase;

abstract class WpemsAbstractEventModel {
	protected $database;

	public function __construct() {
		$this->database = WpemsEventDatabase::get_instance();
	}

	abstract public function getEventTitle( $event_id );
	abstract public function getEventThumbnail( $event_id );
	abstract public function getEventContent( $event_id );
	abstract public function getEventStartTime( $event_id );
	abstract public function getEventEndTime( $event_id );
	abstract public function getEventStartDate( $event_id );
	abstract public function getEventEndDate( $event_id );
	abstract public function getEventRegisterEndTime( $event_id );
	abstract public function getEventRegisterEndDate( $event_id );
	abstract public function getEventLocationF( $event_id );
	abstract public function getEventIframe( $event_id );
	abstract public function getEventSchedules( $event_id );
}
