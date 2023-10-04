<?php
namespace WPEMS\Model;

use WPEMS\Database\WpemsEventDatabase;

class WpemsEventModel {
	private $database;
	private $event;

	private $title;
	private $thumbnail;
	private $content;
	private $startTime;
	private $endTime;
	private $startDate;
	private $endDate;
	private $registerEndTime;
	private $registerEndDate;
	private $locationIframe;
	private $iframe;
	private $schedules;

	public function __construct($event_id) {
        $this->database = WpemsEventDatabase::get_instance();
		$this->event = $this->fetchEventData($event_id);

        $this->title = $this->getEventDataValue('post_title');
        $this->thumbnail = $this->getEventDataValue('guid');
        $this->content = $this->getEventDataValue('post_content');
        $this->startTime = $this->getEventDataValue('tp_event_time_start');
        $this->endTime = $this->getEventDataValue('tp_event_time_end');
        $this->startDate = $this->getEventDataValue('tp_event_date_start');
        $this->endDate = $this->getEventDataValue('tp_event_date_end');
        $this->registerEndTime = $this->getEventDataValue('tp_event_registration_end_time');
        $this->registerEndDate = $this->getEventDataValue('tp_event_registration_end_date');
        $this->locationIframe = $this->getEventDataValue('tp_event_location_iframe');
        $this->iframe = $this->getEventDataValue('tp_event_iframe');
        $this->schedules = $this->getEventDataValue('tp_event_schedules');
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

    public function getEventTitle() {
        return $this->title;
    }

    public function getEventThumbnail() {
        return $this->thumbnail;
    }

    public function getEventContent() {
        return $this->content;
    }

    public function getEventStartTime() {
        return $this->startTime;
    }

    public function getEventEndTime() {
        return $this->endTime;
    }

    public function getEventStartDate() {
        return $this->startDate;
    }

    public function getEventEndDate() {
        return $this->endDate;
    }

    public function getEventRegisterEndTime() {
        return $this->registerEndTime;
    }

    public function getEventRegisterEndDate() {
        return $this->registerEndDate;
    }

    public function getEventLocationF() {
        return $this->locationIframe;
    }

    public function getEventIframe() {
        return $this->iframe;
    }

    public function getEventSchedules() {
        return $this->schedules;
    }
}
// class WpemsEventModel {
// 	private $database;
// 	private $event;

// 	public function __construct() {
//         $this->database = WpemsEventDatabase::get_instance();
//     }

// 	private function fetchEventData( $event_id ) {
//         if ( ! $this->event || $this->event->ID !== $event_id ) {
//             $this->event = $this->database->getEventData( $event_id );
//         }
//         return $this->event;
// 	}

// 	private function getEventDataValue( $event_id, $property ) {
// 		$event_data = $this->fetchEventData( $event_id );
// 		return isset( $event_data->$property ) ? $event_data->$property : null;
// 	}

// 	public function getEventTitle( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'post_title' );
// 	}

// 	/**
// 	 * guid stores thumbnail path
// 	 */
// 	public function getEventThumbnail( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'guid' );
// 	}

// 	public function getEventContent( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'post_content' );
// 	}

// 	public function getEventStartTime( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_time_start' );
// 	}

// 	public function getEventEndTime( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_time_end' );
// 	}

// 	public function getEventStartDate( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_date_start' );
// 	}

// 	public function getEventEndDate( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_date_end' );
// 	}

// 	public function getEventRegisterEndTime( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_registration_end_time' );
// 	}

// 	public function getEventRegisterEndDate( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_registration_end_date' );
// 	}

// 	public function getEventLocationF( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_location_iframe' );
// 	}

// 	public function getEventIframe( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_iframe' );
// 	}

// 	public function getEventSchedules( $event_id ) {
// 		return $this->getEventDataValue( $event_id, 'tp_event_schedules' );
// 	}
// }
