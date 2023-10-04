<?php
namespace WPEMS\Model;

use WPEMS\Database\WpemsEventDatabase;

class WpemsEventModel {
	private $database;
	private $event;

	public $title;
	public $thumbnail;
	public $content;
	public $startTime;
	public $endTime;
	public $startDate;
	public $endDate;
	public $registerEndTime;
	public $registerEndDate;
	public $locationIframe;
	public $iframe;
	public $schedules;

	public function __construct($event_id) {
        $this->database 	   = WpemsEventDatabase::get_instance();
		$this->event    	   = $this->fetchEventData( $event_id );

        $this->title 		   = $this->getEventDataValue( 'post_title' );
        $this->thumbnail 	   = $this->getEventDataValue( 'guid' );
        $this->content 		   = $this->getEventDataValue( 'post_content' );
        $this->startTime 	   = $this->getEventDataValue( 'tp_event_time_start' );
        $this->endTime 		   = $this->getEventDataValue( 'tp_event_time_end' );
        $this->startDate       = $this->getEventDataValue( 'tp_event_date_start' );
        $this->endDate 		   = $this->getEventDataValue( 'tp_event_date_end' );
        $this->registerEndTime = $this->getEventDataValue( 'tp_event_registration_end_time' );
        $this->registerEndDate = $this->getEventDataValue( 'tp_event_registration_end_date' );
        $this->locationIframe  = $this->getEventDataValue( 'tp_event_location_iframe' );
        $this->iframe 		   = $this->getEventDataValue( 'tp_event_iframe' );
        $this->schedules 	   = $this->getEventDataValue( 'tp_event_schedules' );
    }

	private function fetchEventData( $event_id ) {
        if ( ! $this->event || $this->event->ID !== $event_id ) {
            $this->event = $this->database->getEventData( $event_id );
        }
        return $this->event;
	}

	private function getEventDataValue($property) {
        return isset($this->event->$property) ? $this->event->$property : null;
    }
}
