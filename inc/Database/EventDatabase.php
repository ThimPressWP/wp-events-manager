<?php
namespace WPEMS\Database;

class EventDatabase {
	private static $instance;
	private $wpdb;

	private function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_event_data( $event_id ) {
		$event_data = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->wpdb->posts} WHERE ID = %d LIMIT 1", $event_id )
		);

		return $event_data;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $data [ 'post_title', ''];
	 * @return void
	 */
	// public function insert(array $data) {

	// 	$wpdb->insert('table_name', $data);
	// }
}
