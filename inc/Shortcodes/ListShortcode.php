<?php

/**
 * Shortcode list event.
 */

namespace WPEMS\Shortcodes;

use WPEMS\Helper\Singleton;
use WPEMS\Database\EventListData;
use WPEMS\Shortcodes\Sanitize\RequestPattern;
use WPEMS\Shortcodes\Data\DataPattern;


class ListShortcode extends AbstractShortcode {
	use singleton;
	protected $shortcode_name = 'list';

	public static $pageSize = 9;

	/**
	 * Show list_event
	 *
	 * @param $attrs []
	 *
	 * @return string
	 */
	public function render( $attrs ): string {
		$content   = '';
		$template  = 'event-list-display.php';
		$shortcode = 'event-list';

		$filter_by_input_search = '';
		$filter_by_status       = '';
		$filter_by_type         = '';
		$filter_by_category     = '';
		$filter_by_date         = '';
		$filter_by_price        = '';
		$order_by               = '';
		$getDateInput           = '';
		$getPriceMin            = '';
		$getPriceMax            = '';

		// Get value from frontend
		if ( isset( $_GET['search_event_list'] ) ) {
			$fields = [
				'_FILTER_SEARCH_CHAR' => &$filter_by_input_search,
				'_FILTER_STATUS'      => &$filter_by_status,
				'_FILTER_TYPE'        => &$filter_by_type,
				'_FILTER_CATEGORY'    => &$filter_by_category,
				'_FILTER_SEARCH_DATE' => &$getDateInput,
				'_FILTER_PRICE_MIN'   => &$getPriceMin,
				'_FILTER_PRICE_MAX'   => &$getPriceMax,
			];

			foreach ( $fields as $field => &$value ) {
				$value = RequestPattern::get_param( "\Wpems_Model_Event\WPEMS_Model_Event_List::$field", 'GET' );
			}

			$filter_by_date  = explode( ' - ', $getDateInput );
			$filter_by_price = [ $getPriceMin, $getPriceMax ];
		}

		$order_by = RequestPattern::get_param( 'tp_event_order_by', 'GET' );

		// Give arguments to the database
		$get_posts = EventListData::get_posts_data(
			[
				'filter_by_input_search' => $filter_by_input_search,
				'filter_by_status'       => $filter_by_status,
				'filter_by_type'         => $filter_by_type,
				'filter_by_category'     => $filter_by_category,
				'filter_by_date'         => $filter_by_date,
				'filter_by_price'        => $filter_by_price,
				'order_by'               => $order_by,
			]
		);

		$posts = $get_posts->posts;
		$posts = DataPattern::get_postMeta( $posts );

		// Get data from database to send to frontend
		$get_types      = DataPattern::get_filter( 'tp_event_type' );
		$get_categories = DataPattern::get_filter( 'tp_event_category' );

		// Create an array of number for price input
		$number_array = array();
		for ( $i = 0; $i <= 500; $i += 10 ) {
			$number_array[] = $i;
		}

		$pageIndex          = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		$current_item_start = 0;
		$current_item_end   = 0;
		$totalPost          = $get_posts->found_posts;
		$current_item_start = ( $pageIndex - 1 ) * self::$pageSize + 1;
		$current_item_end   = min( $current_item_start + $get_posts->post_count - 1, $totalPost );

		// Give data to fronted to display on the screen
		$attrs = shortcode_atts(
			array(
				'query_posts'            => $get_posts,
				'posts'                  => $posts,
				'types'                  => $get_types,
				'categories'             => $get_categories,
				'numbers'                => $number_array,
				'totalPost'              => $totalPost,
				'pageIndex'              => $pageIndex,
				'current_item_start'     => $current_item_start,
				'current_item_end'       => $current_item_end,
				'dateInput'              => $getDateInput,
				'filter_by_input_search' => $filter_by_input_search,
				'filter_by_status'       => $filter_by_status,
				'filter_by_type'         => $filter_by_type,
				'filter_by_category'     => $filter_by_category,
				'getPriceMin'            => $getPriceMin,
				'getPriceMax'            => $getPriceMax,
				'order_by'               => $order_by,
			),
			$attrs,
		);

		ob_start();
		try {
			if ( empty( $attrs ) ) {
				$attrs = [];
			}

			self::shortcode_wrapper_start( $shortcode );
			wpems_get_template( 'shortcodes/' . $template, array( 'args' => $attrs ) );
			self::shortcode_wrapper_end( $shortcode );

			$content = ob_get_clean();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			error_log( $e->getMessage() );
		}

		return $content;
	}
}
