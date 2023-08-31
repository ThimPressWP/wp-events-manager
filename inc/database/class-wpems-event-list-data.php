<?php

class WPEMS_Event_DB {

	// Deal with status
	public static function status_handler( $filter_by_status, $query_args ) {
		if ( isset( $filter_by_status ) && ! empty( $filter_by_status ) ) {
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

	// To customize the input taxonomy
	public static function check_taxonomy( $taxonomy, $variable ) {
		$taxo = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $variable,
		);
		return $taxo;
	}

	// To add the taxonomy for filter data from database
	public static function add_taxonomy_filter( $taxonomy, $filter_value, $query_args ) {
		if ( isset( $filter_value ) && ! empty( $filter_value ) ) {
			$query_args['tax_query'][] = self::check_taxonomy( $taxonomy, $filter_value );
		}
		return $query_args;
	}
	//  Date handler
	public static function date_handler( $filter_by_date, $query_args ) {
		if ( is_array( $filter_by_date ) && isset( $filter_by_date[0] ) && isset( $filter_by_date[1] ) ) {
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

	// Price
	public static function price_handler( $filter_value, $query_args ) {
		if ( is_array( $filter_value ) && isset( $filter_value[0] ) && isset( $filter_value[1] ) ) {
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

	//Order by
	public static function orderby_handler( $order_by, $query_args ) {
		if ( isset( $order_by ) && ! empty( $order_by ) ) {
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

	// To get posts after filter
	public static function get_posts_data( $arguments ) {

		$filter_by_input_search = $arguments['filter_by_input_search'];
		$filter_by_status       = $arguments ['filter_by_status'];
		$filter_by_type         = $arguments ['filter_by_type'];
		$filter_by_category     = $arguments ['filter_by_category'];
		$filter_by_date         = $arguments ['filter_by_date'];
		$filter_by_price        = $arguments ['filter_by_price'];
		$order_by               = $arguments ['order_by'];

		// Initialize arguments
		$args = array();

		// Search
		if ( ! empty( $filter_by_input_search ) ) {
			$args['s']              = $filter_by_input_search;
			$args['search_columns'] = array( 'post_content', 'post_excerpt', 'post_title' );
		}
		// Status
		$args = self::status_handler( $filter_by_status, $args );

		// Type
		$args = self::add_taxonomy_filter( 'tp_event_type', $filter_by_type, $args );

		// Category
		$args = self::add_taxonomy_filter( 'tp_event_category', $filter_by_category, $args );

		// Date
		if ( isset( $filter_by_date ) ) {
			$args = self::date_handler( $filter_by_date, $args );
		}

		// Price
		if ( ! empty( $filter_by_price ) && is_array( $filter_by_price ) && ! empty( $filter_by_price[0] ) ) {
			$args = self::price_handler( $filter_by_price, $args );
		}

		// Order by
		if ( ! empty( $order_by ) && $order_by !== 'GET' ) {
			$args = self::orderby_handler( $order_by, $args );
		}

		$pageIndex              = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
		$args['posts_per_page'] = WPEMS_Shortcodes::$pageSize;
		$args['paged']          = $pageIndex;

		$array = WPEMS_Data_Pattern::get_posts( $args );

		return $array;
	}
}


