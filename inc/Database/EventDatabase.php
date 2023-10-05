<?php
namespace WPEMS\Database;

class EventDatabase {
	private static $instance;

	private function __construct() {}

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function getEventData( $event_id ) {

		$event_id = (int) $event_id;
		if ( ! $event_id ) {
			return false;
		}

		$event_data = false;
		// $event_data = wp_cache_get($event_id, 'posts');

		if ( ! $event_data ) {
			$event_data = get_post( $event_id );

			if ( ! $event_data ) {
				return false;
			}

			// Get data from wp_postmeta
			$post_meta = get_post_meta( $event_id );

			// Assign values from wp_postmeta to $event_data
			foreach ( $post_meta as $meta_key => $meta_value ) {
				$event_data->{$meta_key} = $meta_value[0];
			}

			$event_data = sanitize_post( $event_data, 'raw' );
			// wp_cache_add($event_data->ID, $event_data, 'posts');

		} elseif ( empty( $event_data->filter ) || 'raw' !== $event_data->filter ) {
			$event_data = sanitize_post( $event_data, 'raw' );
		}

		return $event_data;
	}
}
