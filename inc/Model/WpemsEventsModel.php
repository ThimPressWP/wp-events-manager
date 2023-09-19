<?php
namespace WPEMS\Model;

use Throwable;

class WpemsEventsModel {
	/**
	 * Save client id and client secret and code to database for sync booked event to google calendar
	 * @param string  $client_id
	 * @param string  $client_secret
	 * @param string  $code - get from the url of google
	 */
	public function save_user_info( string $client_id, string $client_secret, string $code ) {
		$user_id = get_current_user_id();

		if ( $user_id && ! empty( $client_id ) && ! empty( $client_secret ) ) {

			// Update user meta with the values
			update_user_meta( $user_id, 'google_client_id', $client_id );
			update_user_meta( $user_id, 'google_client_secret', $client_secret );
			update_user_meta( $user_id, 'google_client_code', $code );
			$_SESSION['save_google_info'] = 'API key and Client ID saved successfully. ';
		} else {
			wp_send_json_error( array( 'message' => 'User not logged in.' ) );
		}
	}

	/**
	 * To get posts data when it has filter condition
	 * @param array $arguments that store the value of conditions
	 * @return array $array that store all posts that match the condition
	 */
	public function get_posts_filter( array $arguments ) {
		try {
			$filter_by_input_search = '';
			$filter_by_status       = '';
			$filter_by_type         = '';
			$filter_by_category     = '';
			$filter_by_date         = '';
			$filter_by_price        = '';
			$order_by               = '';

			if ( isset( $arguments ) && ! empty( $arguments ) ) {
				$filter_by_input_search = $arguments['filter_by_input_search'];
				$filter_by_status       = $arguments ['filter_by_status'];
				$filter_by_type         = $arguments ['filter_by_type'];
				$filter_by_category     = $arguments ['filter_by_category'];
				$filter_by_date         = $arguments ['filter_by_date'];
				$filter_by_price        = $arguments ['filter_by_price'];
				$order_by               = $arguments ['order_by'];
			}

			// Initialize arguments
			$args = array();

			// Search
			if ( ! empty( $filter_by_input_search ) ) {
				$args['s']              = $filter_by_input_search;
				$args['search_columns'] = array( 'post_content', 'post_excerpt', 'post_title' );
			}
			// Status
			$args = $this->status_handler( $filter_by_status, $args );

			// Type
			$args = $this->add_taxonomy_filter( 'tp_event_type', $filter_by_type, $args );

			// Category
			$args = $this->add_taxonomy_filter( 'tp_event_category', $filter_by_category, $args );

			// Date
			if ( isset( $filter_by_date ) ) {
				$args = $this->date_handler( $filter_by_date, $args );
			}

			// Price
			if ( ! empty( $filter_by_price ) && is_array( $filter_by_price ) && ( ! empty( $filter_by_price[0] || ! empty( $filter_by_price[1] ) ) ) ) {
				$args = $this->price_handler( $filter_by_price, $args );
			}

			// Order by
			if ( ! empty( $order_by ) && $order_by !== 'GET' ) {
				$args = $this->orderby_handler( $order_by, $args );
			}

			$pageIndex              = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
			$args['posts_per_page'] = \WPEMS_Shortcodes::$pageSize;
			$args['paged']          = $pageIndex;

			$array = $this->get_posts( $args );

			return $array;

		} catch ( Throwable $e ) {
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
			$get_posts       = $this->get_posts( $args )->posts;
			$posts           = $this->get_postsMeta( $get_posts );

			foreach ( $posts as $key => $value ) {
				$getType = wp_get_post_terms( $value->ID, 'tp_event_type' );
				if ( isset( $getType ) ) {
					foreach ( $getType as $item ) {
						$type = $item->name;
					}
				}

				$getCategory = wp_get_post_terms( $value->ID, 'tp_event_category' );
				if ( isset( $getCategory ) ) {
					foreach ( $getCategory as $item ) {
						$category = $item->name;
					}
				}

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
		} catch ( Throwable $e ) {
			echo 'Something was wrong: ' . $e->getMessage();
		}
	}

	/**
	 * Get data form post meta table
	 * @param array
	 */

	public  function get_postsMeta( $array ) {
		if ( is_array( $array ) ) {
			foreach ( $array as $key => $value ) {
				$value->date_start  = get_post_meta( $value->ID, 'tp_event_date_start', true );
				$value->date_end    = get_post_meta( $value->ID, 'tp_event_date_end', true );
				$value->time_start  = get_post_meta( $value->ID, 'tp_event_time_start', true );
				$value->time_end    = get_post_meta( $value->ID, 'tp_event_time_end', true );
				$value->price       = get_post_meta( $value->ID, 'tp_event_price', true );
				$value->totalTicket = get_post_meta( $value->ID, 'tp_event_qty', true );
				$value->location    = get_post_meta( $value->ID, 'tp_event_location', true );
			}
		}
		return $array;
	}


	/**
	 * To get filter data to display to the screen
	 * @param $taxonomy the taxonomy that need to get data from database
	 * @return array $filter_data of taxonomy itself
	 */
	public  function get_filter( $taxonomy ) {
		$filter_data = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		return $filter_data;
	}
	/**
	 * To get booked events from database
	 * @return array
	 */
	public function bookingData() {
		$bookingData = array();
		$eventData   = get_posts(
			[
				'post_status' => 'ea-completed',
				'post_type'   => 'event_auth_book',
				'numberposts' => -1,
			]
		);

		if ( ! empty( $eventData ) ) {
			foreach ( $eventData as $key => $value ) {
				$bookingData[] = array(
					'description' => str_replace( ' ', '_', $value->post_content . '_' . str_replace( ':', '_', $value->post_date ) ),
					'summary'     => $value->post_content,
					'post_status' => $value->post_status,
					'start'       => array(
						'dateTime' => str_replace( ' ', 'T', $value->post_date ),
						'timeZone' => 'UTC',
					),
					'end'         => array(
						'dateTime' => str_replace( ' ', 'T', $value->post_date ),
						'timeZone' => 'UTC',
					),
				);
			}
		}

		return $bookingData;
	}

	/**
	 * Get all appropriate posts from WordPress database
	 * @param array $args that give the condition to take the data, if $args doesn't exist it will use $default_args
	 * @return array $posts via WP_Query function
	 */
	public  function get_posts( array $args = null ) {
		$user_id = \get_current_user_id();
		$posts   = array();
		if ( $user_id !== 0 ) {
			$default_args = [
				'post_type'      => 'tp_event',
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'posts_per_page' => 9,
				'paged'          => 1,
				'user_id'        => $user_id,
			];

			$args  = wp_parse_args( $args, $default_args );
			$posts = new \WP_Query( $args );

			return $posts;
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
	private function sanitize_params_submitted( $value, string $type_content = 'text' ) {
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

	/**
	 * To handle the status filter
	 * @param string $filter_by_status that take from user
	 * @param array $query_args is an array of condition that will filter data from database
	 * @return array that includes conditions to filter data
	 */
	private function status_handler( string $filter_by_status, array $query_args ) {
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

	/**
	 * To add the taxonomy for filter data from database
	 * @param string $taxonomy that is the name of taxonomy
	 * @param string $filter_value that take from user to filter data
	 * @param array $query_args is an array of condition that will filter data from database
	 * @return array
	 */
	private function add_taxonomy_filter( string $taxonomy, string $filter_value, array $query_args ) {
		if ( isset( $filter_value ) && ! empty( $filter_value ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $filter_value,
			);
		}
		return $query_args;
	}

	/**
	 * Date handler
	 * @param array $filter_by_date includes the date start and date end that need for filter
	 * @param array $query_args that store the condition for date filter
	 * @return array of condition for date filter
	 */
	private function date_handler( array $filter_by_date, array $query_args ) {
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

	/**
	 * Price handler
	 * @param array $filter_value that store the min and max price for filter
	 * @param array $query_args that store the condition for price filter
	 * @return array of condition for price filter
	 */
	private function price_handler( array $filter_value, array $query_args ) {
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

	/**
	 * Order by
	 * @param array $order_by that store the value to reorder the order
	 * @param array $query_args that store the condition to reorder
	 * @return array $query_args of condition to reorder
	 */
	private function orderby_handler( string $order_by, array $query_args ) {
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
}



