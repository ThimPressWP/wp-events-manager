<?php

namespace WPEMS\Databases\Event;

use Exception;
use WPEMS\Databases\Database;
use WPEMS\Filter\Filter;
use WPEMS\Helper\Utils;

class EventDatabase extends Database {
	private static $_instance;

	/**
	 * Get Instance
	 *
	 * @return EventDatabase
	 */
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get event data from database: wp_posts based on the filter
	 *
	 * @param Filter $filter
	 * @param int $total_rows
	 * @return array|int|object|string|null
	 * @throws Exception
	 */
	public function get_events( Filter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'e';
		}

		// where
		if ( ! empty( $filter->post_ids ) ) {
			$list_ids_format = Utils::db_format_array( $filter->post_ids, '%d' );
			$filter->where[] = $this->wpdb->prepare( 'AND e.ID IN (' . $list_ids_format . ')', $filter->post_ids );
		}

		return $this->execute( $filter, $total_rows );
	}
}
