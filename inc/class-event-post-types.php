<?php
if ( !defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * register all post type
 */
class TP_Event_Custom_Post_Types {

	public function __construct() {

		// register post types
		add_action( 'init', array( $this, 'register_event_post_type' ) );
		add_action( 'init', array( $this, 'register_booking_post_type' ) );

		// register event category
		add_action( 'init', array( $this, 'register_event_category_tax' ) );

		// register post type status
		add_action( 'init', array( $this, 'register_event_status' ) );
		add_action( 'init', array( $this, 'register_booking_status' ) );

		// custom event post type column
		add_filter( 'manage_tp_event_posts_columns', array( $this, 'event_columns' ) );
		add_action( 'manage_tp_event_posts_custom_column', array( $this, 'event_column_content' ), 10, 2 );
		add_filter( 'manage_edit-tp_event_sortable_columns', array( $this, 'sortable_columns' ) );

		add_filter( 'manage_event_auth_book_posts_columns', array( $this, 'booking_columns' ) );
		add_action( 'manage_event_auth_book_posts_custom_column', array( $this, 'booking_column_content' ), 10, 2 );

		add_filter( 'post_updated_messages', array( $this, 'update_message' ) );
		// filter nav-menu
		add_filter( 'nav_menu_meta_box_object', array( $this, 'nav_menu_event' ) );

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
			'name'               => _x( 'Events', 'post type general name', 'tp-event' ),
			'singular_name'      => _x( 'Event', 'post type singular name', 'tp-event' ),
			'menu_name'          => _x( 'Events', 'admin menu', 'tp-event' ),
			'name_admin_bar'     => _x( 'Event', 'add new on admin bar', 'tp-event' ),
			'add_new'            => _x( 'Add New', 'event', 'tp-event' ),
			'add_new_item'       => __( 'Add New Event', 'tp-event' ),
			'new_item'           => __( 'New Event', 'tp-event' ),
			'edit_item'          => __( 'Edit Event', 'tp-event' ),
			'view_item'          => __( 'View Event', 'tp-event' ),
			'all_items'          => __( 'Events', 'tp-event' ),
			'search_items'       => __( 'Search Events', 'tp-event' ),
			'parent_item_colon'  => __( 'Parent Events:', 'tp-event' ),
			'not_found'          => __( 'No events found.', 'tp-event' ),
			'not_found_in_trash' => __( 'No events found in Trash.', 'tp-event' )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Event post type.', 'tp-event' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => 'tp-event-setting',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => _x( 'events', 'URL slug', 'tp-event' ) ),
			'taxonomies'         => array( 'tp_event_category' ),
			'capability_type'    => 'tp_event',
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => true,
			'menu_position'      => 8,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
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
			'name'               => _x( 'Bookings', 'post type general name', 'tp-event' ),
			'singular_name'      => _x( 'Booking', 'post type singular name', 'tp-event' ),
			'menu_name'          => _x( 'Bookings', 'admin menu', 'tp-event' ),
			'name_admin_bar'     => _x( 'Booking', 'add new on admin bar', 'tp-event' ),
			'add_new'            => _x( 'Add New', 'book', 'tp-event' ),
			'add_new_item'       => __( 'Add New Booking', 'tp-event' ),
			'new_item'           => __( 'New Booking', 'tp-event' ),
			'edit_item'          => __( 'Booking Details', 'tp-event' ),
			'view_item'          => __( 'View Booking', 'tp-event' ),
			'all_items'          => __( 'Bookings', 'tp-event' ),
			'search_items'       => __( 'Search Books', 'tp-event' ),
			'parent_item_colon'  => __( 'Parent Books:', 'tp-event' ),
			'not_found'          => __( 'No books found.', 'tp-event' ),
			'not_found_in_trash' => __( 'No books found in Trash.', 'tp-event' )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Description.', 'tp-event' ),
			'public'             => true,
			'publicly_queryable' => false,
			'show_in_admin_bar'  => false,
			'show_ui'            => true,
			'show_in_menu'       => 'tp-event-setting',
			'query_var'          => true,
			'rewrite'            => array( 'slug' => _x( 'event-book', 'URL slug', 'tp-event' ) ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => false,
			'capabilities'       => array(
				'create_posts' => 'do_not_allow'
			),
			'map_meta_cap'       => true
		);

		$args = apply_filters( 'event_auth_book_args', $args );
		register_post_type( 'event_auth_book', $args );
	}

	/**
	 * Register event category taxonomy
	 */
	public function register_event_category_tax() {

		$labels = array(
			'name'              => _x( 'Event Categories', 'taxonomy general name', 'tp-event' ),
			'singular_name'     => _x( 'Event Category', 'taxonomy singular name', 'tp-event' ),
			'search_items'      => __( 'Search Categories', 'tp-event' ),
			'all_items'         => __( 'All Categories', 'tp-event' ),
			'parent_item'       => __( 'Parent Category', 'tp-event' ),
			'parent_item_colon' => __( 'Parent Category:', 'tp-event' ),
			'edit_item'         => __( 'Edit Category', 'tp-event' ),
			'update_item'       => __( 'Update Category', 'tp-event' ),
			'add_new_item'      => __( 'Add New Category', 'tp-event' ),
			'new_item_name'     => __( 'New Category Name', 'tp-event' ),
			'menu_name'         => __( 'Category', 'tp-event' ),
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
	 * Register event status
	 */
	public function register_event_status() {
		// post status // upcoming // expired // happening

		register_post_status( 'tp-event-upcoming', apply_filters( 'tp_event_register_upcoming_status_args', array(
			'label'                     => _x( 'Upcoming', 'tp-event' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Upcoming <span class="count">(%s)</span>', 'Upcoming <span class="count">(%s)</span>' ),
		) ) );

		register_post_status( 'tp-event-happenning', apply_filters( 'tp_event_register_happening_status_args', array(
			'label'                     => _x( 'Happening', 'tp-event' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Happening <span class="count">(%s)</span>', 'Happening <span class="count">(%s)</span>' ),
		) ) );

		register_post_status( 'tp-event-expired', apply_filters( 'tp_event_register_expired_status_args', array(
			'label'                     => _x( 'Expired', 'tp-event' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>' ),
		) ) );
	}

	/**
	 * Register booking status
	 */
	public function register_booking_status() {

		register_post_status( 'ea-cancelled', apply_filters( 'event_auth_register_status_cancelled', array(
			'label'                     => _x( 'Cancelled', 'Booking status', 'tp-event' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>' ),
		) ) );

		register_post_status( 'ea-pending', apply_filters( 'event_auth_register_status_pending', array(
			'label'                     => _x( 'Pending', 'Booking status', 'tp-event' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>' ),
		) ) );

		register_post_status( 'ea-processing', apply_filters( 'event_auth_register_status_processing', array(
			'label'                     => _x( 'Processing', 'Booking status', 'tp-event' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>' ),
		) ) );

		register_post_status( 'ea-completed', apply_filters( 'event_auth_register_status_completed', array(
			'label'                     => _x( 'Completed', 'Booking status', 'tp-event' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' ),
		) ) );

	}

	/**
	 * Add event custom columns
	 *
	 * @param type $columns
	 *
	 * @return array
	 */
	public function event_columns( $columns ) {
		unset( $columns['comments'], $columns['date'] );
		$columns['start']       = __( 'Start', 'tp-event' );
		$columns['end']         = __( 'End', 'tp-event' );
		$columns['status']      = __( 'Status', 'tp-event' );
		$columns['price']       = __( 'Price', 'tp-event' );
		$columns['booked_slot'] = __( 'Booked / Total', 'tp-event' );
		return $columns;
	}

	/**
	 * Event custom columns content
	 *
	 * @param type $column
	 * @param type $post_id
	 */
	public function event_column_content( $column, $post_id ) {
		$event = TP_Event_Event::instance( $post_id );
		switch ( $column ) {
			case 'status' :
				$status = get_post_status_object( get_post_status( $post_id ) );
				echo $status->label;
				break;
			case 'start' :
				$date_start = get_post_meta( $post_id, 'tp_event_date_start', true );
				$time_start = get_post_meta( $post_id, 'tp_event_time_start', true );
				if ( $date_start ) {
					printf( '%s', date( get_option( 'date_format' ), strtotime( $date_start ) ) );
				}
				if ( $time_start ) {
					printf( ' %s', date( get_option( 'time_format' ), strtotime( $time_start ) ) );
				}
				break;
			case 'end' :
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
					echo '<span class="event_auth_event_type">' . __( 'Free', 'tp-event' ) . '</span>';
				} else {
					echo sprintf( __( '<span class="event_auth_event_type">%s/%s</span>', 'tp-event' ), tp_event_format_price( $event->get_price() ), __( 'slot', 'tp-event' ) );
				}
				break;
			case 'booked_slot':
				$total = get_post_meta( $post_id, 'tp_event_qty', true ) ? get_post_meta( $post_id, 'tp_event_qty', true ) : esc_html__( 'Unlimited', 'tp-event' );
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
		$columns['cb']     = __( '<label class="screen-reader-text __web-inspector-hide-shortcut__" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">' );
		$columns['ID']     = __( 'ID', 'tp-event' );
		$columns['event']  = __( 'Event', 'tp-event' );
		$columns['user']   = __( 'User', 'tp-event' );
		$columns['cost']   = __( 'Cost', 'tp-event' );
		$columns['slot']   = __( 'Slot', 'tp-event' );
		$columns['status'] = __( 'Status', 'tp-event' );
		return $columns;
	}

	/**
	 * Booking custom columns content
	 *
	 * @param $column
	 * @param $booking_id
	 */
	public function booking_column_content( $column, $booking_id ) {
		$booking = TP_Event_Booking::instance( $booking_id );
		switch ( $column ) {
			case 'ID':
				echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $booking->ID ), tp_event_format_ID( $booking_id ) );
				break;
			case 'event':
				echo sprintf( '<a href="%s">%s</a>', get_edit_post_link( $booking->event_id ), get_the_title( $booking->event_id ) );
				break;
			case 'user':
				$user     = get_userdata( $booking->user_id );
				$return   = array();
				$return[] = sprintf( __( '<a href="%s">%s</a>', 'tp-event' ), admin_url( 'admin.php?page=tp-event-users&user_id=' . $booking->user_id ), $user->display_name );
				$return   = implode( '', $return );
				echo $return;
				break;
			case 'cost':
				echo $booking->price > 0 ? tp_event_format_price( $booking->price ) : __( 'Free', 'tp-event' );
				break;
			case 'slot':
				echo $booking->qty;
				break;
			case 'status':
				$return   = array();
				$return[] = sprintf( '%s', tp_event_booking_status( $booking_id ) );
				$return[] = $booking->payment_id ? '<p>' . __( sprintf( '(via %s)', tp_event_get_payment_title( $booking->payment_id ) ), 'tp-event' ) . '</p>' : '';
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
		return wp_parse_args( $columns, array( 'start' => 'start', 'end' => 'end' ) );
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
	 * Filter nav-menu
	 *
	 * @param null $object
	 *
	 * @return null
	 */
	public function nav_menu_event( $object = null ) {
		if ( isset( $object->name ) && $object->name === 'tp_event' ) {
			// default query
			$object->_default_query = array(
				'post_status' => array(
					'tp-event-upcoming',
					'tp-event-happenning',
					'tp-event-expired'
				)
			);
		}

		return $object;
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
			1  => __( 'Event updated.', 'tp-event' ),
			2  => __( 'Custom field updated.', 'tp-event' ),
			3  => __( 'Custom field deleted.', 'tp-event' ),
			4  => __( 'Event updated.', 'tp-event' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Book restored to revision from %s', 'tp-event' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Event updated.', 'tp-event' ),
			7  => __( 'Event saved.', 'tp-event' ),
			8  => __( 'Event submitted.', 'tp-event' ),
			9  => sprintf(
				__( 'Event scheduled for: <strong>%1$s</strong>.', 'tp-event' ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'tp-event' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Event draft updated.', 'tp-event' )
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View event', 'tp-event' ) );
			$messages[$post_type][1] .= $view_link;
			$messages[$post_type][6] .= $view_link;
			$messages[$post_type][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link      = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview event', 'tp-event' ) );
			$messages[$post_type][8] .= $preview_link;
			$messages[$post_type][10] .= $preview_link;
		}
		return $messages;
	}

}

new TP_Event_Custom_Post_Types();
