<?php
/**
 * Class EventMetaDatabase
 */

namespace WPEMS\Databases\Event\Meta;

use WPEMS\Databases\Database;
use WPEMS\Filter\Event\Meta\EventMetaFilter;

class EventMetaDatabase extends Database {
	private static $_instance;

	/**
	 * Get Instance
	 *
	 * @return EventMetaDatabase
	 */
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get event data from database: wp_postmeta based on the filter
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 */
	public function get_postmeta_events( EventMetaFilter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_postmeta;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'pm';
		}

		// Where
		if ( ! empty( $filter->post_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND pm.post_id = %d', $filter->post_id );
		}

		if ( ! empty( $filter->meta_key ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND pm.meta_key = %s', $filter->meta_key );
		}

		return $this->execute( $filter, $total_rows );
	}
}
