<?php
namespace WPEMS\Model;

class WpemPaginationModel {
	public $pageSize           = 1;
	public $pageIndex          = 1;
	public $totalPost          = 0;
	public $current_item_start = 1;
	public $current_item_end   = 0;
	public $max_num_pages      = 0;

	public function pagination( object $getPosts ) {
		$pagination = array();
		$end        = min( $this->current_item_start + ( ! empty( $getPosts->post_count ) ? $getPosts->post_count : 0 ) - 1, $this->totalPost );
		// Pagination information
		$pagination = array(
			'pageIndex'          => get_query_var( 'paged' ) !== 1 ? get_query_var( 'paged' ) : $this->pageIndex,
			'totalPost'          => ! empty( $getPosts->found_posts ) ? $getPosts->found_posts : $this->totalPost,  // The total getPosts that match the query condition
			'current_item_start' => ( ( $this->pageIndex - 1 ) * $this->pageSize + 1 ) <= 0 ? 1 : ( ( $this->pageIndex - 1 ) * $this->pageSize + 1 ),
			// post_count return the real number of getPosts that display on current page.
			'current_item_end'   => ( $end === 0 ) || ( $end === 1 ) ? 1 : $end,
			'max_num_pages'      => ! empty( $getPosts->max_num_pages ) ? $getPosts->max_num_pages : 0,
		);
		return $pagination;
	}
}
