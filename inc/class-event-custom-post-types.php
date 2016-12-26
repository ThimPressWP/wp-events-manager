<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

/**
 * register all post type
 */
class Event_Custom_Post_Types {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_post_status' ) );
        add_filter( 'post_updated_messages', array( $this, 'update_message' ) );
        add_filter( 'manage_tp_event_posts_columns', array( $this, 'column_filter' ) );
        add_action( 'manage_tp_event_posts_custom_column', array( $this, 'column_action' ), 10, 2 );
        add_filter( 'manage_edit-tp_event_sortable_columns', array( $this, 'sortable_columns' ) );
        // filter nav-menu
        add_filter( 'nav_menu_meta_box_object', array( $this, 'nav_menu_event' ) );
    }

    // register post type hook callback
    function register_post_type() {
        // post type
        $labels = array(
            'name' => _x( 'Events', 'post type general name', 'tp-event' ),
            'singular_name' => _x( 'Event', 'post type singular name', 'tp-event' ),
            'menu_name' => _x( 'Events', 'admin menu', 'tp-event' ),
            'name_admin_bar' => _x( 'Event', 'add new on admin bar', 'tp-event' ),
            'add_new' => _x( 'Add New', 'event', 'tp-event' ),
            'add_new_item' => __( 'Add New Event', 'tp-event' ),
            'new_item' => __( 'New Event', 'tp-event' ),
            'edit_item' => __( 'Edit Event', 'tp-event' ),
            'view_item' => __( 'View Event', 'tp-event' ),
            'all_items' => __( 'Events', 'tp-event' ),
            'search_items' => __( 'Search Events', 'tp-event' ),
            'parent_item_colon' => __( 'Parent Events:', 'tp-event' ),
            'not_found' => __( 'No events found.', 'tp-event' ),
            'not_found_in_trash' => __( 'No events found in Trash.', 'tp-event' )
        );

        $args = array(
            'labels' => $labels,
            'description' => __( 'Event post type.', 'tp-event' ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'tp-event-setting',
            'query_var' => true,
            'rewrite' => array( 'slug' => _x( 'events', 'URL slug', 'tp-event' ) ),
            'capability_type' => 'tp_event',
            'map_meta_cap' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 8,
            'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
        );

        $args = apply_filters( 'tp_event_register_event_post_type_args', $args );
        register_post_type( 'tp_event', $args );
    }

    public function register_post_status() {
        // post status // upcoming // expired // happenning
        $args = apply_filters( 'tp_event_register_upcoming_status_args', array(
            'label' => _x( 'Upcoming', 'tp-event' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Upcoming <span class="count">(%s)</span>', 'Upcoming <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tp-event-upcoming', $args );

        $args = apply_filters( 'tp_event_register_happening_status_args', array(
            'label' => _x( 'Happenning', 'tp-event' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Happenning <span class="count">(%s)</span>', 'Happenning <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tp-event-happenning', $args );

        $args = apply_filters( 'tp_event_register_expired_status_args', array(
            'label' => _x( 'Expired', 'tp-event' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>' ),
        ) );
        register_post_status( 'tp-event-expired', $args );
    }

    /**
     * add custom column
     * @param type $columns
     * @return array
     */
    public function column_filter( $columns ) {
        unset( $columns['author'], $columns['comments'], $columns['date'] );
        $columns['start'] = __( 'Start', 'tp-event' );
        $columns['end'] = __( 'End', 'tp-event' );
        $columns['author'] = __( 'Author', 'tp-event' );
        $columns['status'] = __( 'Status', 'tp-event' );
        $columns['date'] = __( 'Date', 'tp-event' );
        return $columns;
    }

    /**
     * column content
     * @param type $column
     * @param type $post_id
     */
    public function column_action( $column, $post_id ) {
        $date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
        switch ( $column ) {

            case 'status' :
                $status = get_post_status_object( get_post_status( $post_id ) );
                echo $status->label;
                break;
            case 'start' :
                $start = get_post_meta( $post_id, 'tp_event_start', true );
                if ( $start ) {
                    printf( '%s', date( $date_time_format, strtotime( $start ) ) );
                }
                break;
            case 'end' :
                $start = get_post_meta( $post_id, 'tp_event_end', true );
                if ( $start ) {
                    printf( '%s', date( $date_time_format, strtotime( $start ) ) );
                }
                break;
        }
    }
    
    /**
     * sortable columns
     * @param type $columns
     * @return array
     */
    public function sortable_columns( $columns ) {
        return wp_parse_args( $columns, array( 'start' => 'start', 'end' => 'end' ) );
    }

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
     * @param type $messages
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
		$messages[ $post_type ][1] .= $view_link;
		$messages[ $post_type ][6] .= $view_link;
		$messages[ $post_type ][9] .= $view_link;

		$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
		$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview event', 'tp-event' ) );
		$messages[ $post_type ][8]  .= $preview_link;
		$messages[ $post_type ][10] .= $preview_link;
	}
        return $messages;
    }

}

new Event_Custom_Post_Types();
