<?php
/**
 * WP Events Manager Post Types class
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Class
 * @version       2.1.7.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * register all post type
 */
class WPEMS_Custom_Post_Types {

	public function __construct() {

		// register post types
		add_action( 'after_setup_theme', array( $this, 'register_event_post_type' ) );
		add_action( 'init', array( $this, 'register_booking_post_type' ) );

		// register event category
		add_action( 'init', array( $this, 'register_event_category_tax' ) );
		// register event tag
		add_action( 'init', array( $this, 'register_event_tag_tax' ) );
		// register event type
		add_action( 'init', array( $this, 'register_event_type_tax' ) );

		// register post type status
		add_action( 'init', array( $this, 'register_booking_status' ) );

		// custom event post type column
		add_filter( 'manage_tp_event_posts_columns', array( $this, 'event_columns' ) );
		add_action( 'manage_tp_event_posts_custom_column', array( $this, 'event_column_content' ), 10, 2 );

		add_filter( 'manage_edit-tp_event_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );

		add_filter( 'manage_edit-tp_event_category_columns', array( $this, 'event_category_columns' ) );

		add_filter( 'manage_event_auth_book_posts_columns', array( $this, 'booking_columns' ) );
		add_action( 'manage_event_auth_book_posts_custom_column', array( $this, 'booking_column_content' ), 10, 2 );

		add_filter( 'post_updated_messages', array( $this, 'update_message' ) );

		if ( is_admin() ) {
			// filter booking event by user ID
			add_filter( 'parse_query', array( $this, 'request_query' ) );
		}
	}

	/**
	 * Register event post type
	 */
	public function register_event_post_type() {
		// post type
		$labels = array(
			'name'               => _x( 'Events', 'post type general name', 'wp-events-manager' ),
			'singular_name'      => _x( 'Event', 'post type singular name', 'wp-events-manager' ),
			'menu_name'          => _x( 'Events', 'admin menu', 'wp-events-manager' ),
			'name_admin_bar'     => _x( 'Event', 'add new on admin bar', 'wp-events-manager' ),
			'add_new'            => _x( 'Add New', 'event', 'wp-events-manager' ),
			'add_new_item'       => __( 'Add New Event', 'wp-events-manager' ),
			'new_item'           => __( 'New Event', 'wp-events-manager' ),
			'edit_item'          => __( 'Edit Event', 'wp-events-manager' ),
			'view_item'          => __( 'View Event', 'wp-events-manager' ),
			'all_items'          => __( 'Events', 'wp-events-manager' ),
			'search_items'       => __( 'Search Events', 'wp-events-manager' ),
			'parent_item_colon'  => __( 'Parent Events:', 'wp-events-manager' ),
			'not_found'          => __( 'No events found.', 'wp-events-manager' ),
			'not_found_in_trash' => __( 'No events found in Trash.', 'wp-events-manager' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Event post type.', 'wp-events-manager' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'tp-event-setting',
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => _x( 'events', 'URL slug', 'wp-events-manager' ),
				'with_front' => false,
			),
			'taxonomies'         => array( 'tp_event_category' ),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => true,
			'menu_position'      => 8,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
		);

		$args = apply_filters( 'tp_event_register_event_post_type_args', $args );
		register_post_type( 'tp_event', $args );
	}


	/**
	 * Register booking event post type
	 */
	public function register_booking_post_type() {
		// event auth book
		$labels = array(
			'name'               => _x( 'Bookings', 'post type general name', 'wp-events-manager' ),
			'singular_name'      => _x( 'Booking', 'post type singular name', 'wp-events-manager' ),
			'menu_name'          => _x( 'Bookings', 'admin menu', 'wp-events-manager' ),
			'name_admin_bar'     => _x( 'Booking', 'add new on admin bar', 'wp-events-manager' ),
			'add_new'            => _x( 'Add New', 'book', 'wp-events-manager' ),
			'add_new_item'       => __( 'Add New Booking', 'wp-events-manager' ),
			'new_item'           => __( 'New Booking', 'wp-events-manager' ),
			'edit_item'          => __( 'Booking Details', 'wp-events-manager' ),
			'view_item'          => __( 'View Booking', 'wp-events-manager' ),
			'all_items'          => __( 'Bookings', 'wp-events-manager' ),
			'search_items'       => __( 'Search Books', 'wp-events-manager' ),
			'parent_item_colon'  => __( 'Parent Books:', 'wp-events-manager' ),
			'not_found'          => __( 'No books found.', 'wp-events-manager' ),
			'not_found_in_trash' => __( 'No books found in Trash.', 'wp-events-manager' ),
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Description.', 'wp-events-manager' ),
			'public'             => true,
			'publicly_queryable' => false,
			'show_in_admin_bar'  => false,
			'show_ui'            => true,
			'show_in_menu'       => 'tp-event-setting',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => _x( 'event-book', 'URL slug', 'wp-events-manager' ) ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => false,
			'capabilities'       => array(
				'create_posts' => 'do_not_allow',
			),
			'map_meta_cap'       => true,
		);

		$args = apply_filters( 'event_auth_book_args', $args );
		register_post_type( 'event_auth_book', $args );
	}

	/**
	 * Register event category taxonomy
	 */
	public function register_event_category_tax() {

		$labels = array(
			'name'              => _x( 'Event Categories', 'taxonomy general name', 'wp-events-manager' ),
			'singular_name'     => _x( 'Event Category', 'taxonomy singular name', 'wp-events-manager' ),
			'search_items'      => __( 'Search Categories', 'wp-events-manager' ),
			'all_items'         => __( 'All Categories', 'wp-events-manager' ),
			'parent_item'       => __( 'Parent Category', 'wp-events-manager' ),
			'parent_item_colon' => __( 'Parent Category:', 'wp-events-manager' ),
			'edit_item'         => __( 'Edit Category', 'wp-events-manager' ),
			'update_item'       => __( 'Update Category', 'wp-events-manager' ),
			'add_new_item'      => __( 'Add New Category', 'wp-events-manager' ),
			'new_item_name'     => __( 'New Category Name', 'wp-events-manager' ),
			'menu_name'         => __( 'Category', 'wp-events-manager' ),
		);

		$args = array(
			'public'            => true,
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'tp-event-category' ),
		);

		register_taxonomy( 'tp_event_category', array( 'tp_event' ), $args );
	}

	/**
	 * Register event tag taxonomy
	 */
	public function register_event_tag_tax() {
		$labels = array(
			'name'              => _x( 'Event Tags', 'taxonomy general name', 'wp-events-manager' ),
			'singular_name'     => _x( 'Event Tag', 'taxonomy singular name', 'wp-events-manager' ),
			'search_items'      => __( 'Search Tags', 'wp-events-manager' ),
			'all_items'         => __( 'All Tags', 'wp-events-manager' ),
			'parent_item'       => __( 'Parent Tag', 'wp-events-manager' ),
			'parent_item_colon' => __( 'Parent Tag:', 'wp-events-manager' ),
			'edit_item'         => __( 'Edit Tag', 'wp-events-manager' ),
			'update_item'       => __( 'Update Tag', 'wp-events-manager' ),
			'add_new_item'      => __( 'Add New Tag', 'wp-events-manager' ),
			'new_item_name'     => __( 'New Tag Name', 'wp-events-manager' ),
			'menu_name'         => __( 'Tag', 'wp-events-manager' ),
		);

		$args = array(
			'public'            => true,
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'tp-event-tag' ),
		);

		register_taxonomy( 'tp_event_tag', array( 'tp_event' ), $args );
	}

	/**
	 * Register event type taxonomy
	 */
	public function register_event_type_tax() {
		$labels = array(
			'name'              => _x( 'Event Types', 'taxonomy general name', 'wp-events-manager' ),
			'singular_name'     => _x( 'Event Type', 'taxonomy singular name', 'wp-events-manager' ),
			'search_items'      => __( 'Search Types', 'wp-events-manager' ),
			'all_items'         => __( 'All Types', 'wp-events-manager' ),
			'parent_item'       => __( 'Parent Type', 'wp-events-manager' ),
			'parent_item_colon' => __( 'Parent Type:', 'wp-events-manager' ),
			'edit_item'         => __( 'Edit Type', 'wp-events-manager' ),
			'update_item'       => __( 'Update Type', 'wp-events-manager' ),
			'add_new_item'      => __( 'Add New Type', 'wp-events-manager' ),
			'new_item_name'     => __( 'New Type Name', 'wp-events-manager' ),
			'menu_name'         => __( 'Types', 'wp-events-manager' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'tp-event-type' ),
		);

		register_taxonomy( 'tp_event_type', array( 'tp_event' ), $args );
	}

	/**
	 * Register booking status
	 */
	public function register_booking_status() {

		register_post_status(
			'ea-cancelled',
			apply_filters(
				'event_auth_register_status_cancelled',
				array(
					'label'                     => _x( 'Cancelled', 'Booking status', 'wp-events-manager' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>' ),
				)
			)
		);

		register_post_status(
			'ea-pending',
			apply_filters(
				'event_auth_register_status_pending',
				array(
					'label'                     => _x( 'Pending', 'Booking status', 'wp-events-manager' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>' ),
				)
			)
		);

		register_post_status(
			'ea-processing',
			apply_filters(
				'event_auth_register_status_processing',
				array(
					'label'                     => _x( 'Processing', 'Booking status', 'wp-events-manager' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>' ),
				)
			)
		);

		register_post_status(
			'ea-completed',
			apply_filters(
				'event_auth_register_status_completed',
				array(
					'label'                     => _x( 'Completed', 'Booking status', 'wp-events-manager' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' ),
				)
			)
		);

	}

	/**
	 * Custom event columns.
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function event_columns( $columns ) {
		unset( $columns['comments'], $columns['date'] );
		$columns['start']       = __( 'Start', 'wp-events-manager' );
		$columns['end']         = __( 'End', 'wp-events-manager' );
		$columns['status']      = __( 'Status', 'wp-events-manager' );
		$columns['price']       = __( 'Price', 'wp-events-manager' );
		$columns['booked_slot'] = __( 'Booked / Total', 'wp-events-manager' );

		return $columns;
	}


	/**
	 * Custom event category columns.
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function event_category_columns( $columns ) {
		unset( $columns['posts'] );

		return $columns;
	}

	/**
	 * Event custom columns content
	 *
	 * @param type $column
	 * @param type $post_id
	 */
	public function event_column_content( $column, $post_id ) {
		$event = WPEMS_Event::instance( $post_id );
		switch ( $column ) {
			case 'status':
				echo $status = get_post_meta( $post_id, 'tp_event_status', true );
				break;
			case 'start':
				$date_start = get_post_meta( $post_id, 'tp_event_date_start', true );
				$time_start = get_post_meta( $post_id, 'tp_event_time_start', true );
				if ( $date_start ) {
					printf( '%s', date( get_option( 'date_format' ), strtotime( $date_start ) ) );
				}
				if ( $time_start ) {
					printf( ' %s', date( get_option( 'time_format' ), strtotime( $time_start ) ) );
				}
				break;
			case 'end':
				$date_end = get_post_meta( $post_id, 'tp_event_date_end', true );
				$time_end = get_post_meta( $post_id, 'tp_event_time_end', true );
				if ( $date_end ) {
					printf( '%s', date( get_option( 'date_format' ), strtotime( $date_end ) ) );
				}
				if ( $time_end ) {
					printf( ' %s', date( get_option( 'time_format' ), strtotime( $time_end ) ) );
				}
				break;
			case 'price':
				if ( $event->is_free() ) {
					echo '<span class="event_auth_event_type">' . __( 'Free', 'wp-events-manager' ) . '</span>';
				} else {
					echo sprintf( __( '<span class="event_auth_event_type">%1$s/%2$s</span>', 'wp-events-manager' ), wpems_format_price( $event->get_price() ), __( 'slot', 'wp-events-manager' ) );
				}
				break;
			case 'booked_slot':
				$total = get_post_meta( $post_id, 'tp_event_qty', true ) ? get_post_meta( $post_id, 'tp_event_qty', true ) : esc_html__( 'Unlimited', 'wp-events-manager' );
				echo sprintf( '%s / %s', $event->booked_quantity(), $total );
				break;
			default:
				break;
		}
	}


	/**
	 * Add booking custom columns
	 *
	 * @return array
	 */
	public function booking_columns() {
		$columns = array();
		// set
		$columns['cb']           = __( '<label class="screen-reader-text __web-inspector-hide-shortcut__" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">' );
		$columns['ID']           = __( 'ID', 'wp-events-manager' );
		$columns['event']        = __( 'Event', 'wp-events-manager' );
		$columns['user']         = __( 'User', 'wp-events-manager' );
		$columns['booking_date'] = __( 'Date', 'wp-events-manager' );
		$columns['cost']         = __( 'Cost', 'wp-events-manager' );
		$columns['slot']         = __( 'Quantity', 'wp-events-manager' );
		$columns['status']       = __( 'Status', 'wp-events-manager' );

		return $columns;
	}

	/**
	 * Booking custom columns content
	 *
	 * @param $column
	 * @param $booking_id
	 */
	public function booking_column_content( $column, $booking_id ) {
		$booking = WPEMS_Booking::instance( $booking_id );
		switch ( $column ) {
			case 'ID':
				echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $booking->ID ), wpems_format_ID( $booking_id ) );
				break;
			case 'event':
				echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $booking->event_id ), get_the_title( $booking->event_id ) );
				break;
			case 'user':
				$user     = get_userdata( $booking->user_id );
				$return   = array();
				$return[] = sprintf( __( '<a href="%1$s">%2$s</a>', 'wp-events-manager' ), admin_url( 'admin.php?page=tp-event-users&user_id=' . $booking->user_id ), $user->display_name );
				$return   = implode( '', $return );
				echo $return;
				break;
			case 'booking_date':
				echo get_the_date( '', $booking->ID );
				break;
			case 'cost':
				echo $booking->price > 0 ? wpems_format_price( $booking->price ) : __( 'Free', 'wp-events-manager' );
				break;
			case 'slot':
				echo $booking->qty;
				break;
			case 'status':
				$return   = array();
				$return[] = sprintf( '%s', wpems_booking_status( $booking_id ) );
				$return[] = $booking->payment_id ? '<p>' . __( sprintf( '(via %s)', wpems_get_payment_title( $booking->payment_id ) ), 'wp-events-manager' ) . '</p>' : '';
				$return   = implode( '', $return );
				echo $return;
				break;
			default:
				break;
		}
	}

	/**
	 * sortable columns
	 *
	 * @param type $columns
	 *
	 * @return array
	 */
	public function sortable_columns( $columns ) {
		return wp_parse_args(
			$columns,
			array(
				'start' => 'event_start_date',
				'end'   => 'event_end_date',
			)
		);
	}

	/**
	 * @return bool
	 */
	private function _is_admin_sort() {
		if ( ! ( is_admin() && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] == 'tp_event' ) ) {
			return false;
		}

		if ( ! isset( $_REQUEST['orderby'] ) || ! in_array(
			$_REQUEST['orderby'],
			array(
				'event_start_date',
				'event_end_date',
			)
		) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param $join
	 *
	 * @return string
	 */
	public function posts_join_paged( $join ) {
		if ( ! $this->_is_admin_sort() ) {
			return $join;
		}

		global $wpdb;

		$order_by = $_REQUEST['orderby'];

		if ( $order_by == 'event_start_date' ) {
			$join .= " INNER JOIN {$wpdb->prefix}postmeta AS event_date ON event_date.post_id = {$wpdb->prefix}posts.ID AND event_date.meta_key = 'tp_event_date_start' ";
		}

		if ( $order_by == 'event_end_date' ) {
			$join .= " INNER JOIN {$wpdb->prefix}postmeta AS event_date ON event_date.post_id = {$wpdb->prefix}posts.ID AND event_date.meta_key = 'tp_event_date_end' ";
		}

		return $join;
	}

	/**
	 * @param $order_by
	 *
	 * @return string
	 */
	public function posts_orderby( $order_by ) {
		if ( ! $this->_is_admin_sort() ) {
			return $order_by;
		}

		$order          = isset( $_REQUEST['order'] ) ? $_REQUEST['order'] : 'asc';
		$order          = strtoupper( $order );
		$allowed_orders = [ 'ASC', 'DESC' ];

		if ( ! in_array( $order, $allowed_orders, true ) ) {
			$order = 'ASC';
		}

		return "event_date.meta_value {$order}";
	}

	/**
	 * Filter booking event by user ID
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function request_query( $query ) {
		global $typenow, $wp_query, $wp_post_statuses;

		if ( isset( $_GET['user_id'] ) && 'event_auth_book' === $typenow ) {
			// Status
			$query->query_vars['meta_key']   = 'ea_booking_user_id';
			$query->query_vars['meta_value'] = absint( sanitize_text_field( $_GET['user_id'] ) );
		}

		return $query;
	}

	/**
	 * update post message
	 *
	 * @param type $messages
	 *
	 * @return type array
	 */
	public function update_message( $messages ) {
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );
		if ( $post_type !== 'tp_event' ) {
			return $messages;
		}
		$messages['tp_event'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Event updated.', 'wp-events-manager' ),
			2  => __( 'Custom field updated.', 'wp-events-manager' ),
			3  => __( 'Custom field deleted.', 'wp-events-manager' ),
			4  => __( 'Event updated.', 'wp-events-manager' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Book restored to revision from %s', 'wp-events-manager' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Event updated.', 'wp-events-manager' ),
			7  => __( 'Event saved.', 'wp-events-manager' ),
			8  => __( 'Event submitted.', 'wp-events-manager' ),
			9  => sprintf(
				__( 'Event scheduled for: <strong>%1$s</strong>.', 'wp-events-manager' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'wp-events-manager' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Event draft updated.', 'wp-events-manager' ),
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link                  = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View event', 'wp-events-manager' ) );
			$messages[ $post_type ][1] .= $view_link;
			$messages[ $post_type ][6] .= $view_link;
			$messages[ $post_type ][9] .= $view_link;

			$preview_permalink           = add_query_arg( 'preview', 'true', $permalink );
			$preview_link                = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview event', 'wp-events-manager' ) );
			$messages[ $post_type ][8]  .= $preview_link;
			$messages[ $post_type ][10] .= $preview_link;
		}

		return $messages;
	}

	public function wpems_events_archive( $query ) {
		echo'<pre>';
		print_r( $query );
		die;
	}

}

new WPEMS_Custom_Post_Types();
