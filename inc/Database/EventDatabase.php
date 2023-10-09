<?php
namespace WPEMS\Database;

use WPEMS\Model\EventModel;

class EventDatabase {
	private static $instance;
	private $wpdb;

	private function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Get a single instance of the EventDatabase class
	 *
	 * @return EventDatabase
	 */
	public static function get_instance(): EventDatabase {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * get event data from the database based on the event ID
	 *
	 * @param integer $event_id
	 * @return EventModel
	 */
	public function get_event_data( int $event_id ): EventModel {
		if ( $event_id <= 0 ) {
			return false;
		}

		$event_data = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID = %d LIMIT 1", $event_id )
		);

		// return $event_data;
		return new EventModel( $event_data );
	}
}
