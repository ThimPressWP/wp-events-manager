<?php
namespace WPEMS\Model;

use Exception;
use WPEMS\Database as Db;

interface FilterModel {
	public function get_posts_filter( array $arguments );
	public function checkEvent( int | object $event );
}
interface CalendarModel {
	public  function calendar_data();
}

class WpemsEventsModel implements FilterModel, CalendarModel {
	public $data;
	public $pagination;
	private static $instances = [];

	protected function __construct( ) {
		$this->data       = new Db\WpemsEventsDatabase();
		$this->pagination =  WpemPaginationModel::getInstance();
	}

	 /**
     * Ensure only one instance is created at the moment
     */
	public static function getInstance() {
		$cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
	}

	/**
	 * To check the event argument when do template handling
	 *
	 * @param int | object of $event  that will take the post list or a single post
	 * @return array $post
	 */
	public function checkEvent( int | object $event ) {
		try {
			$post = null;
			if ( \is_numeric( $event ) && get_post_type( $event ) === 'tp_event' ) {
				$posts = $this->data->getPosts()->posts;
				foreach ( $posts as $value ) {
					if ( $event === $value->ID ) {
						$post = $value;
						break;
					}
				}
				// Checks if the object is of this class or has this class as one of its parents
			} elseif ( is_object( $event ) && is_a( $event, 'WP_Post' ) ) {
				$post = $event;
			}
			return $post;
		} catch ( Exception $e ) {
			echo 'There is a problem: ' . $e->getMessage();
		}
	}

	/**
	 * To get posts data when it has filter condition
	 *
	 * @param array $arguments that store the value of conditions
	 * @return array $array that store all posts that match the condition
	 */
	public function get_posts_filter( array $arguments ) {
		try {
			if ( isset( $arguments ) && is_array( $arguments ) ) {
				$filter_by_input_search = ! empty( $arguments['filter_by_input_search'] ) ? $arguments['filter_by_input_search'] : '';
				$filter_by_status       = ! empty( $arguments ['filter_by_status'] ) ? $arguments ['filter_by_status'] : '';
				$filter_by_type         = ! empty( $arguments ['filter_by_type'] ) ? $arguments ['filter_by_type'] : '';
				$filter_by_category     = ! empty( $arguments ['filter_by_category'] ) ? $arguments ['filter_by_category'] : '';
				$filter_by_date         = ! empty( $arguments ['filter_by_date'] ) ? $arguments ['filter_by_date'] : '';
				$filter_by_price        = ! empty( $arguments ['filter_by_price'] ) ? $arguments ['filter_by_price'] : '';
				$order_by               = ! empty( $arguments ['order_by'] ) ? $arguments ['order_by'] : '';
			}

			// Initialize arguments
			$args = array();

			// Search
			if ( ! empty( $filter_by_input_search ) ) {
				$args['s']              = $filter_by_input_search;
				$args['search_columns'] = array( 'post_content', 'post_excerpt', 'post_title' );
			}
			// Status
			$args = $this->data->status_query( $filter_by_status, $args );

			// Type
			$args = $this->data->add_taxonomy_filter( 'tp_event_type', $filter_by_type, $args );

			// Category
			$args = $this->data->add_taxonomy_filter( 'tp_event_category', $filter_by_category, $args );

			// Date
			if ( isset( $filter_by_date ) && is_array( $filter_by_date ) ) {
				$args = $this->data->date_query( $filter_by_date, $args );
			}

			// Price
			if ( ! empty( $filter_by_price ) && is_array( $filter_by_price ) && ( ! empty( $filter_by_price[0] || ! empty( $filter_by_price[1] ) ) ) ) {
				$args = $this->data->price_query( $filter_by_price, $args );
			}

			// Order by
			if ( ! empty( $order_by ) && $order_by !== 'GET' ) {
				$args = $this->data->orderby_query( $order_by, $args );
			}

			$pageIndex              = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$args['posts_per_page'] = $this->pagination->pageSize;
			$args['paged']          = $pageIndex;

			$array = $this->data->getPosts( $args );

			return $array;

		} catch ( Exception  $e ) {
			echo 'Something was wrong: ' . $e->getMessage();
		}
	}

	/**
	 * Create a data list to send to calendar screen
	 * @return array include properties to send to FullCalendar library to display
	 */
	public  function calendar_data() {
		try {
			$type            = '';
			$category        = '';
			$calendar_events = array();
			$args            = array();
			$getPosts        = $this->data->getPosts( $args )->posts;
			$posts           = $this->data->get_postsMeta( $getPosts );

			foreach ( $posts as $key => $value ) {
				$type = $this->data->get_postTerms( $value->ID, 'tp_event_type' );

				$category = $this->data->get_postTerms( $value->ID, 'tp_event_category' );

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
					'type'        => $type,
					'category'    => $category,
				);
			}
			return $calendar_events;
		} catch ( Exception $e ) {
			echo 'Something was wrong: ' . $e->getMessage();
		}
	}

	/**
	 * Check html request
	 */
	public function get_param( string $key, $default = '', string $sanitize_type = 'text', string $method = '' ) {
		switch ( strtolower( $method ) ) {
			case 'post':
				$values = $_POST ?? [];
				break;
			case 'get':
				$values = $_GET ?? [];
				break;
			default:
				$values = $_REQUEST ?? [];
		}

		return $this->sanitize_params_submitted( $values[ $key ] ?? $default, $sanitize_type );
	}

	/**
	 * To sanitize parameters
	 */
	public function sanitize_params_submitted( $value, string $type_content = 'text' ) {
		$value = wp_unslash( $value );

		if ( is_string( $value ) ) {
			switch ( $type_content ) {
				case 'html':
					$value = wp_kses_post( $value );
					break;
				case 'textarea':
					$value = sanitize_textarea_field( $value );
					break;
				case 'key':
					$value = sanitize_key( $value );
					break;
				case 'int':
					$value = (int) $value;
					break;
				case 'float':
					$value = (float) $value;
					break;
				default:
					$value = sanitize_text_field( $value );
			}
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = $this->sanitize_params_submitted( $v, $type_content );
			}
		}

		return $value;
	}

}



