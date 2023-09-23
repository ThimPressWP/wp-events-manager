<?php
namespace WPEMS\Database;

abstract class WpemsAbstractEventDatabase {
	protected $wpdb;
	protected static $instance;

	protected function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	abstract public function getEventDatabaseTitle( $event_id );
	abstract public function getEventDatabaseThumbnail( $event_id );
	abstract public function getEventDatabaseContent( $event_id );
	abstract public function getEventDatabaseStartTime( $event_id );
	abstract public function getEventDatabaseEndTime( $event_id );
	abstract public function getEventDatabaseStartDate( $event_id );
	abstract public function getEventDatabaseEndDate( $event_id );
	abstract public function getEventDatabaseRegisterEndTime( $event_id );
	abstract public function getEventDatabaseRegisterEndDate( $event_id );
	abstract public function getEventDatabaseLocationF( $event_id );
	abstract public function getEventDatabaseIframe( $event_id );
	abstract public function getEventDatabaseSchedules( $event_id );
}
