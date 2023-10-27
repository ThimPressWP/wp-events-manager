<?php

namespace WPEMS\Model;

use WPEMS\Database\EventDatabase;

class EventMetaModel {
	/**
	 * Auto increment
	 *
	 * @var int
	 */
	public $meta_id = 0;
	/**
	 * @var string User ID, foreign key
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
	 * Map array, object data to EventModel.
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
	 * Update data to database.
	 *
	 * If user_item_id is empty, insert new data, else update data.
	 *
	 * @return EventMetaModel
	 * @throws Exception
	 */
	public function save(): EventMetaModel {
		$event_model = EventDatabase::getInstance();
		$data        = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		// Check if exists user_item_id.
		if ( empty( $this->meta_id ) ) { // Insert data.
			$meta_id_new = $event_model->insert_data( $data );
			if ( empty( $meta_id_new ) ) {
				throw new Exception( __METHOD__ . ': ' . 'Cannot insert data to database.' );
			}
		} else { // Update data.
			$event_model->update_data( $data );
		}

		if ( ! empty( $meta_id_new ) ) {
			$this->meta_id = $meta_id_new;
		}

		return $this;
	}
}
