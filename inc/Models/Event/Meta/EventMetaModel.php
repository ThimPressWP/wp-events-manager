<?php

namespace WPEMS\Models\Event\Meta;

use Exception;
use WPEMS\Databases\Event\Meta\EventMetaDatabase;
use WPEMS\Filter\Event\Meta\EventMetaFilter;
use stdClass;
use Throwable;

class EventMetaModel {
	/**
	 * Auto increment
	 *
	 * @var int
	 */
	public $meta_id = 0;
	/**
	 * @var string Post ID, foreign key
	 */
	public $post_id = 0;
	/**
	 * @var string meta key
	 */
	public $meta_key = 0;
	/**
	 * @var string meta value
	 */
	public $meta_value = '';

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}
	}

	/**
	 * Map array, object data to EventMetaModel.
	 * Use for data get from database.
	 *
	 * @param  array|object|mixed $data
	 * @return EventMetaModel
	 */
	public function map_to_object( $data ): EventMetaModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get all data, all keys of event
	 *
	 * @throws Exception
	 * @return stdClass|false
	 */
	public static function get_all_data_from_db( $post_id) {
		$event_meta_db 	     	         = EventMetaDatabase::getInstance();
		$filter                          = new EventMetaFilter();
		$filter->post_id 				 = $post_id;
		$filter->run_query_count         = false;
		$meta_data_rs                    = $event_meta_db->get_postmeta_events( $filter );
		$all_data                        = false;

		if (is_array($meta_data_rs)) {
			$all_data = new stdClass();
			foreach ($meta_data_rs as $value) {
				$all_data->{$value->meta_key} = new static($value);
			}
		}

		return $all_data;
	}

	/**
	 * Get meta_value from database by filter(meta_key. post_id).
	 * If not exists, return false.
	 * If exists, return UserItemModel.
	 *
	 * @param EventMetaFilter $filter
	 * @param bool $no_cache
	 * @return EventMetaModel|false
	 */
	public static function get_meta_value_from_db( EventMetaFilter $filter, bool $no_cache = true ) {
		$event_meta_db 					= EventMetaDatabase::getInstance();
		$event_meta_model 				= false;

		try {
			$event_meta_db->get_query_single_row( $filter );
			$query_single_row 			= $event_meta_db->get_postmeta_events( $filter );
			$event_rs     				= $event_meta_db->wpdb->get_row( $query_single_row );

			if ( $event_rs instanceof stdClass ) {
				$event_meta_model 		= new static( $event_rs );
			}

		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $event_meta_model;
	}
}
