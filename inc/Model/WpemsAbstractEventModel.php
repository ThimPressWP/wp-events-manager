<?php
namespace WPEMS\Model;

use WPEMS\Database\WpemsAbstractEventDatabase;

abstract class WpemsAbstractEventModel {
	protected WpemsAbstractEventDatabase $database;

	public function __construct( WpemsAbstractEventDatabase $database ) {
		$this->database = $database;
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
	abstract public function getEventSchedules( $event_id );
}
