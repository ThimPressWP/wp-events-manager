<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Auth_Post_Types {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );

        add_filter( 'manage_event_auth_book_posts_columns', array( $this, 'post_types_columns' ), 999 );
        add_action( 'manage_event_auth_book_posts_custom_column', array( $this, 'post_types_columns_content' ), 999, 2 );

        add_filter( 'manage_tp_event_posts_columns', array( $this, 'post_types_tp_event_columns' ), 999 );
        add_action( 'manage_tp_event_posts_custom_column', array( $this, 'post_types_tp_event_columns_content' ), 10, 2 );

        if ( is_admin() ) {
            // filter booking event by user ID
            add_filter( 'parse_query', array( $this, 'request_query' ) );
        }

        add_action( 'init', array( $this, 'register_post_status' ) );
    }

    public function register_post_types() {
        // event auth book
        $labels = array(
            'name' => _x( 'Books', 'post type general name', 'tp-event-auth' ),
            'singular_name' => _x( 'Book', 'post type singular name', 'tp-event-auth' ),
            'menu_name' => _x( 'Books', 'admin menu', 'tp-event-auth' ),
            'name_admin_bar' => _x( 'Book', 'add new on admin bar', 'tp-event-auth' ),
            'add_new' => _x( 'Add New', 'book', 'tp-event-auth' ),
            'add_new_item' => __( 'Add New Book', 'tp-event-auth' ),
            'new_item' => __( 'New Book', 'tp-event-auth' ),
            'edit_item' => __( 'Edit Book', 'tp-event-auth' ),
            'view_item' => __( 'View Book', 'tp-event-auth' ),
            'all_items' => __( 'Books', 'tp-event-auth' ),
            'search_items' => __( 'Search Books', 'tp-event-auth' ),
            'parent_item_colon' => __( 'Parent Books:', 'tp-event-auth' ),
            'not_found' => __( 'No books found.', 'tp-event-auth' ),
            'not_found_in_trash' => __( 'No books found in Trash.', 'tp-event-auth' )
        );

        $args = array(
            'labels' => $labels,
            'description' => __( 'Description.', 'tp-event-auth' ),
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'tp-event-setting',
            'query_var' => true,
            'rewrite' => array( 'slug' => _x( 'event-book', 'URL slug', 'tp-event-auth' ) ),
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array( 'title' ),
            'capabilities' => array(
                'create_posts' => 'do_not_allow'
            ),
            'map_meta_cap' => true
        );

        $args = apply_filters( 'event_auth_book_args', $args );
        register_post_type( 'event_auth_book', $args );
    }

    // post type custom column
    public function post_types_columns() {
        $columns = array();
        // set
        $columns['cb'] = __( '<label class="screen-reader-text __web-inspector-hide-shortcut__" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox">' );
        $columns['ID'] = __( 'ID', 'tp-event-auth' );
        $columns['event'] = __( 'Event', 'tp-event-auth' );
        $columns['user'] = __( 'User', 'tp-event-auth' );
        $columns['cost'] = __( 'Cost', 'tp-event-auth' );
        $columns['slot'] = __( 'Slot', 'tp-event-auth' );
        $columns['status'] = __( 'Status', 'tp-event-auth' );
        return $columns;
    }

    // post type custom column
    public function post_types_columns_content( $column, $booking_id ) {
        $booking = Auth_Booking::instance( $booking_id );
        $return = '';
        switch ( $column ) {
            case 'ID':
                # code...
                $return = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $booking->event_id ), event_auth_format_ID( $booking_id ) );
                break;
            case 'event':
                # code...
                $return = sprintf( '<a href="%s">%s</a>', get_edit_post_link( $booking->event_id ), get_the_title( $booking->event_id ) );
                break;
            case 'cost':
                # code...
                $return = $booking->price > 0 ? event_auth_format_price( $booking->price, $booking->currency ) : __( 'Free', 'tp-event-auth' );
                break;
            case 'slot':
                # code...
                $return = $booking->qty;
                break;
            case 'status':
                # code...
                $return = array();
                $return[] = sprintf( '%s', event_auth_booking_status( $booking_id ) );
                $return[] = $booking->payment_id ? sprintf( '<br />(%s)', event_auth_get_payment_title( $booking->payment_id ) ) : '';
                $return = implode( '', $return );
                break;
            case 'user':
                # code...
                $user = get_userdata( $booking->user_id );
                $return = array();
                $return[] = sprintf( __( '<a href="%s">%s</a><br />', 'tp-event-auth' ), admin_url( 'admin.php?page=tp-event-users&user_id=' . $booking->user_id ), $user->display_name );
                $return = implode( '', $return );
                break;
            default:
                # code...
                break;
        }

        echo $return;
    }

    public function post_types_tp_event_columns( $columns ) {
        unset( $columns['author'], $columns['comments'], $columns['date'] );
        $columns['type'] = __( 'Type', 'tp-event-auth' );
        $columns['booked_slot'] = __( 'Booked / Total', 'tp-event-auth' );
        $columns['date'] = __( 'Date', 'tp-event-auth' );
        $columns['author'] = __( 'Author', 'tp-event-auth' );
        return $columns;
    }

    public function post_types_tp_event_columns_content( $column, $event_id ) {
        $event = Auth_Event::instance( $event_id );
        $return = '';
        switch ( $column ) {
            case 'type':
                # code...
                if ( $event->is_free() ) {
                    $return = __( '<span class="event_auth_event_type">Free</span>', 'tp-event-auth' );
                } else {
                    $return = sprintf( __( '<span class="event_auth_event_type">%s/%s</span><br />', 'tp-event-auth' ), event_auth_format_price( $event->get_price() ), __( 'slot', 'tp-event-auth' ) );
                }
                break;
            case 'booked_slot':
                # code...
                $return = sprintf( '%s/%s', $event->booked_quantity(), (int) $event->quantity );
                break;
            default:
                # code...
                break;
        }
        echo $return;
    }

    // register post status
    public function register_post_status() {
        register_post_status( 'ea-cancelled', apply_filters( 'event_auth_register_status_cancelled', array(
            'label' => _x( 'Cancelled', 'Booking status', 'tp-event-auth' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>' ),
        ) ) );

        register_post_status( 'ea-pending', apply_filters( 'event_auth_register_status_pending', array(
            'label' => _x( 'Pending', 'Booking status', 'tp-event-auth' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>' ),
        ) ) );

        register_post_status( 'ea-processing', apply_filters( 'event_auth_register_status_processing', array(
            'label' => _x( 'Processing', 'Booking status', 'tp-event-auth' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>' ),
        ) ) );

        register_post_status( 'ea-completed', apply_filters( 'event_auth_register_status_completed', array(
            'label' => _x( 'Completed', 'Booking status', 'tp-event-auth' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' ),
        ) ) );
    }

    public function request_query( $query ) {
        global $typenow, $wp_query, $wp_post_statuses;

        if ( isset( $_GET['user_id'] ) && 'event_auth_book' === $typenow ) {
            // Status
            $query->query_vars['meta_key'] = 'ea_booking_user_id';
            $query->query_vars['meta_value'] = absint( sanitize_text_field( $_GET['user_id'] ) );
        }
        return $query;
    }

}

new Auth_Post_Types();
