<?php
namespace WPEMS\Model;
use WP_Query;
class PaginationModel {
	public $pageSize           = 9;
	public $pageIndex          = 1;
	public $totalPost          = 0;
	public $current_item_start = 1;
	public $current_item_end   = 0;
	public $max_num_pages      = 0;
	private  static $instances = [];

	protected function __construct() {}

	/**
	 * Ensure only one instance is created at the moment
	 */
	public static function getInstance() {
		$cls = static::class;
		if ( ! isset( self::$instances[ $cls ] ) ) {
			self::$instances[ $cls ] = new static();
		}
		return self::$instances[ $cls ];
	}

	/**
	 * For pagination feature
	 *
	 * @param WP_Query $getPosts take from WP_Query method
	 * @return WP_Query $pagination that store properties need for setting pagination on the screen
	 */
	public function pagination( WP_Query $getPosts ) {
		$pagination = array();

		if ( $getPosts !== null ) {
			$start = ( ( $this->pageIndex - 1 ) * $this->pageSize + 1 ) <= 0 ? 1 : ( ( $this->pageIndex - 1 ) * $this->pageSize + 1 );
			$end   = min( ( $start + ( ! empty( $getPosts->post_count ) ? $getPosts->post_count : 1 ) ) - 1, $this->pageSize );

			// Pagination information
			$pagination = array(
				'pageIndex'          => get_query_var( 'paged' ) !== 1 ? get_query_var( 'paged' ) : $this->pageIndex,
				'totalPost'          => ! empty( $getPosts->found_posts ) ? $getPosts->found_posts : $this->totalPost,  // The total getPosts that match the query condition
				'current_item_start' => $start,
				// post_count return the real number of getPosts that display on current page.
				'current_item_end'   => ( $end === 0 ) ? 1 : $end,
				'max_num_pages'      => ! empty( $getPosts->max_num_pages ) ? $getPosts->max_num_pages : 0,
			);

		}
		return $pagination;
	}
}
