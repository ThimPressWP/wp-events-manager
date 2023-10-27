<?php
namespace WPEMS\Model;
use WPEMS\Database as Database;
use Exception;

class EventsModel {
	public $data;
	public $pagination;
	private static $instances = [];
	public $filter_search     = '';
	public $filter_status     = '';
	public $filter_type       = '';
	public $filter_category   = '';
	public $filter_date       = [];
	public $filter_price      = [];
	public $order_by          = '';

	protected function __construct() {
		$this->data       = Database\EventsDatabase::getInstance();
		$this->pagination = PaginationModel::getInstance();
	}

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
	 * To get posts data when it has filter condition
	 *
	 * @param array $arguments that store the value of conditions
	 * @return array $array that store all posts that match the condition
	 */
	public function get_posts_filter( array $filter_queries ) {
		$array = array();
		try {
			if ( is_array( $filter_queries ) && count( $filter_queries ) > 0 ) {
				$this->filter_search   = isset( $filter_queries['filter_by_input_search'] ) ? $filter_queries['filter_by_input_search'] : '';
				$this->filter_status   = isset( $filter_queries ['filter_by_status'] ) ? $filter_queries ['filter_by_status'] : '';
				$this->filter_type     = isset( $filter_queries ['filter_by_type'] ) ? $filter_queries ['filter_by_type'] : '';
				$this->filter_category = isset( $filter_queries ['filter_by_category'] ) ? $filter_queries ['filter_by_category'] : '';
				$this->filter_date     = isset( $filter_queries ['filter_by_date'] ) ? $filter_queries ['filter_by_date'] : '';
				$this->filter_price    = isset( $filter_queries ['filter_by_price'] ) ? $filter_queries ['filter_by_price'] : '';
				$this->order_by        = isset( $filter_queries ['order_by'] ) ? $filter_queries ['order_by'] : '';
			}

			// Initialize filter_queries
			$args = array();

			// Search
			if ( ! empty( $this->filter_search ) ) {
				$args['s']              = $this->filter_search;
				$args['search_columns'] = array( 'post_content', 'post_excerpt', 'post_title' );
			}
			// Status
			$args = $this->data->status_query( $this->filter_status, $args );

			// Type
			$args = $this->data->add_taxonomy_filter( 'tp_event_type', $this->filter_type, $args );

			// Category
			$args = $this->data->add_taxonomy_filter( 'tp_event_category', $this->filter_category, $args );

			// Date
			if ( is_array( $this->filter_date ) && count( $this->filter_date ) > 0 ) {
				$args = $this->data->date_query( $this->filter_date, $args );
			}

			// Price
			if ( is_array( $this->filter_price ) && count( $this->filter_price ) > 0 && ( ! empty( $this->filter_price[0] || ! empty( $this->filter_price[1] ) ) ) ) {
				$args = $this->data->price_query( $this->filter_price, $args );
			}

			// Order by
			if ( ! empty( $this->order_by ) && $this->order_by !== 'GET' ) {
				$args = $this->data->orderby_query( $this->order_by, $args );
			}

			$pageIndex              = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$args['posts_per_page'] = $this->pagination->pageSize;
			$args['paged']          = $pageIndex;

			$array = $this->data->getPosts( $args );

			return $array;

		} catch ( Exception  $e ) {
			echo 'Something was wrong: ' . $e->getMessage();
			return $array;
		}
	}

	public function get_types_categories( $post_type ) {
		return $this->data->get_filter( $post_type );
	}
	/**
	 * Create a data list to send to calendar screen
	 * @return array include properties to send to FullCalendar library to display
	 */
	public  function calendar_data() {
		try {
			$types           = array();
			$categories      = array();
			$calendar_events = array();
			$posts           = array();
			$args            = array();
			$getPosts        = $this->data->getPosts( $args )->posts;
			if ( is_array( $getPosts ) ) {
				$posts = $this->data->get_postsMeta( $getPosts );
			}

			foreach ( $posts as $key => $value ) {
				$types      = $this->data->get_postTerms( $value->ID, 'tp_event_type' );
				$categories = $this->data->get_postTerms( $value->ID, 'tp_event_category' );

				$calendar_events[] = array(
					'id'          => $value->ID,
					'title'       => $value->post_title,
					'start'       => $value->date_start,
					'end'         => $value->date_end,
					'date_start'  => $value->date_start,
					'date_end'    => $value->date_end,
					'time_start'  => $value->time_start,
					'time_end'    => $value->time_end,
					'location'    => $value->location,
					'price'       => floatval( $value->price ),
					'totalTicket' => floatval( $value->totalTicket ),
					'types'       => $types,
					'categories'  => $categories,
				);
			}
			return $calendar_events;
		} catch ( Exception $e ) {
			echo 'Something was wrong: ' . $e->getMessage();
		}
	}
}



