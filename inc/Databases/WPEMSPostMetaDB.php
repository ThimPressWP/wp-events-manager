<?php
/**
 * Class WPEMSPostMetaDB
 * @package WPEventsManager/Databases
 * @version 1.0.0
 */

namespace WPEventsManager\Databases;

use WPEventsManager\Filters\WPEMSPostMetaFilter;

class WPEMSPostMetaDB extends WPEMSDatabase {

	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 *  Get questions
	 *
	 * @return array|object|null|int|string
	 * @throws Exception
	 * @version 1.0.0
	 */
	public function get_post_metas( WPEMSPostMetaFilter $filter, &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );
		$col_meta_id    = WPEMSPostMetaFilter::COL_META_ID;
		$col_post_id    = WPEMSPostMetaFilter::COL_POST_ID;
		$col_meta_key   = WPEMSPostMetaFilter::COL_META_KEY;

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_postmeta;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'pm';
		}

		$ca = $filter->collection_alias;

		// Find meta_id
		if ( ! empty( $filter->meta_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$ca}.{$col_meta_id} = %d", $filter->meta_id );
		}

		// Find post_id
		if ( ! empty( $filter->post_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$ca}.{$col_post_id} = %d", $filter->post_id );
		}

		// Find meta_key
		if ( ! empty( $filter->meta_key ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$ca}.{$col_meta_key} = %s", $filter->meta_key );
		}

		$filter = apply_filters( 'wpems/post-meta/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}
