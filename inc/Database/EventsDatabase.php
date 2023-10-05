<?php

namespace  WPEMS\Database;
use WP_Query;
class EventsDatabase {
	private static $instances = [];

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
	 * Get all appropriate posts from WordPress database
	 *
	 * @param array $args that give the condition to take the data, if $args doesn't exist it will use $default_args
	 * @return array $posts via WP_Query function
	 */
	public function getPosts( array $args = null ) {
		$posts        = array();
		$default_args = [
			'post_type'      => 'tp_event',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'posts_per_page' => 9,
			'paged'          => 1,
		];

		$args  = wp_parse_args( $args, $default_args );
		$posts = new WP_Query( $args );

		return $posts;
	}
	/**
	 * Get data form post meta table
	 *
	 * @param array
	 */
	public  function get_postsMeta( array $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				if ( \is_object( $value ) ) {
					$value->date_start  = get_post_meta( $value->ID, 'tp_event_date_start', true );
					$value->date_end    = get_post_meta( $value->ID, 'tp_event_date_end', true );
					$value->time_start  = get_post_meta( $value->ID, 'tp_event_time_start', true );
					$value->time_end    = get_post_meta( $value->ID, 'tp_event_time_end', true );
					$value->price       = get_post_meta( $value->ID, 'tp_event_price', true );
					$value->totalTicket = get_post_meta( $value->ID, 'tp_event_qty', true );
					$value->location    = get_post_meta( $value->ID, 'tp_event_location', true );
				}
			}
		}
		return $array;
	}

	/**
	 * Get data form post term table
	 *
	 * @param int $id of the post
	 * @param string $term: the name of term
	 */
	public function get_postTerms( int $id, string $term ) {
		$item = null;
		if ( ! empty( $id ) && ! empty( $term ) ) {
			$item = wp_get_post_terms( $id, $term );
		}
		return $item;
	}


	/**
	 * To get filter data to display to the screen
	 *
	 * @param $taxonomy the taxonomy that need to get data from database
	 * @return array $filter_data of taxonomy itself
	 */
	public  function get_filter( string $taxonomy = null ) {
		$filter_data = array();
		if ( ! empty( $taxonomy ) ) {
			$filter_data = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);
		}
		return $filter_data;
	}

	/**
	 * To query the status filter
	 *
	 * @param string $filter_by_status that take from user
	 * @param array $query_args is an array of condition that will filter data from database
	 * @return array that includes conditions to filter data
	 */
	public function status_query( string $filter_by_status, array $query_args ) {
		if ( ! empty( $filter_by_status ) && is_array( $query_args ) ) {
			$query_args['meta_query'] = array(
				array(
					'key'     => 'tp_event_status',
					'value'   => $filter_by_status,
					'compare' => '=',
				),
			);
		}
		return $query_args;
	}

	/**
	 * To add the taxonomy for filter data from database
	 *
	 * @param string $taxonomy that is the name of taxonomy
	 * @param string $filter_value that take from user to filter data
	 * @param array $query_args is an array of condition that will filter data from database
	 * @return array
	 */
	public function add_taxonomy_filter( string $taxonomy, string $filter_value, array $query_args ) {
		if ( ! empty( $filter_value ) && is_array( $query_args ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $filter_value,
			);
		}
		return $query_args;
	}

	/**
	 * Date query
	 *
	 * @param array $filter_by_date includes the date start and date end that need for filter
	 * @param array $query_args that store the condition for date filter
	 * @return array of condition for date filter
	 */
	public function date_query( array $filter_by_date, array $query_args ) {
		if ( is_array( $filter_by_date ) && isset( $filter_by_date[0] ) && isset( $filter_by_date[1] ) && is_array( $query_args ) ) {
			$start_date = $filter_by_date[0];
			$end_date   = $filter_by_date[1];

			$query_args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => 'tp_event_date_start',
							'value'   => $start_date,
							'compare' => '>=',
							'type'    => 'DATE',
						),
						array(
							'key'     => 'tp_event_date_start',
							'value'   => $start_date,
							'compare' => '<=',
							'type'    => 'DATE',
						),
					),
					array(
						'key'     => 'tp_event_date_end',
						'value'   => $start_date,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
				array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => 'tp_event_date_end',
							'value'   => $end_date,
							'compare' => '<=',
							'type'    => 'DATE',
						),
						array(
							'key'     => 'tp_event_date_end',
							'value'   => $end_date,
							'compare' => '>=',
							'type'    => 'DATE',
						),
					),
					array(
						'key'     => 'tp_event_date_start',
						'value'   => $end_date,
						'compare' => '<=',
						'type'    => 'DATE',
					),
				),
			);
		}
		return $query_args;
	}


	/**
	 * Price query
	 *
	 * @param array $filter_value that store the min and max price for filter
	 * @param array $query_args that store the condition for price filter
	 * @return array of condition for price filter
	 */
	public function price_query( array $filter_value, array $query_args ) {
		if ( is_array( $filter_value ) && isset( $filter_value[0] ) && isset( $filter_value[1] ) && is_array( $query_args ) ) {
			$minimum = $filter_value[0];
			$maximum = $filter_value[1];

			if ( $minimum <= $maximum ) {
				$query_args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'key'     => 'tp_event_price',
						'value'   => $minimum,
						'compare' => '>=',
						'type'    => 'numeric',
					),
					array(
						'key'     => 'tp_event_price',
						'value'   => $maximum,
						'compare' => '<=',
						'type'    => 'numeric',
					),
				);
			}
		}
		return $query_args;
	}

	/**
	 * Order by
	 *
	 * @param array $order_by that store the value to reorder the order
	 * @param array $query_args that store the condition to reorder
	 * @return array $query_args of condition to reorder
	 */
	public function orderby_query( string $order_by, array $query_args ) {
		if ( ! empty( $order_by ) && is_string( $order_by ) && is_array( $query_args ) ) {
			// Order by price
			if ( strtolower( $order_by ) === 'high-low' || strtolower( $order_by ) === 'low-high' ) {
				$query_args['orderby']  = 'meta_value_num';
				$query_args['meta_key'] = 'tp_event_price';

				if ( strtolower( $order_by ) === 'high-low' ) {
					$query_args['order'] = 'DESC';
				}
				if ( strtolower( $order_by ) === 'low-high' ) {
					$query_args['order'] = 'ASC';
				}
			}

			// Order by the character
			if ( strtolower( $order_by ) === 'a-z' || strtolower( $order_by ) === 'z-a' ) {
				$query_args['orderby'] = 'title';
				if ( strtolower( $order_by ) === 'a-z' ) {
					$query_args['order'] = 'ASC';
				}
				if ( strtolower( $order_by ) === 'z-a' ) {
					$query_args['order'] = 'DESC';
				}
			}
			return $query_args;
		}
	}
}
