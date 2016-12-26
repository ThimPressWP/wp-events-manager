<?php

if ( !function_exists( 'tp_event_get_timezone_string' ) ) {

    function tp_event_get_timezone_string() {
        $tzstring = get_option( 'timezone_string' );
        $gmt_offset = get_option( 'gmt_offset' );
        if ( !$tzstring ) {
            $timezones = timezone_identifiers_list();
            foreach ( $timezones as $key => $zone ) {
                $origin_dtz = new DateTimeZone( $zone );
                $origin_dt = new DateTime( 'now', $origin_dtz );
                $offset = $origin_dtz->getOffset( $origin_dt ) / 3600;
                if ( $offset == $gmt_offset ) {
                    $tzstring = $zone;
                }
            }
        }
        return $tzstring;
    }

}

//function tp_event_set_timezones( $gmt_offset ) {
//    $tzstring = get_option( 'timezone_string' );
//    if ( $tzstring ) {
//        date_default_timezone_set( $tzstring );
//    } else {
//        $timezones = timezone_identifiers_list();
//        foreach ( $timezones as $key => $zone ) {
//            $origin_dtz = new DateTimeZone( $zone );
//            $origin_dt = new DateTime( 'now', $origin_dtz );
//            $offset = $origin_dtz->getOffset( $origin_dt ) / 3600;
//            if ( $offset == $gmt_offset ) {
//                date_default_timezone_set( $zone );
//            }
//        }
//    }
//}
//tp_event_set_timezones( get_option( 'gmt_offset' ) );

add_action( 'widgets_init', 'tp_event_register_countdown_widget' );
if ( !function_exists( 'tp_event_register_countdown_widget' ) ) {

    function tp_event_register_countdown_widget() {
        register_widget( 'Event_Widget_Countdown' );
    }

}

if ( !function_exists( 'tp_event_get_template' ) ) {

    function tp_event_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        if ( $args && is_array( $args ) ) {
            extract( $args );
        }

        $located = tp_event_locate_template( $template_name, $template_path, $default_path );

        if ( !file_exists( $located ) ) {
            _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
            return;
        }
        // Allow 3rd party plugin filter template file from their plugin
        $located = apply_filters( 'tp_event_get_template', $located, $template_name, $args, $template_path, $default_path );

        do_action( 'tp_event_before_template_part', $template_name, $template_path, $located, $args );

        include( $located );

        do_action( 'tp_event_after_template_part', $template_name, $template_path, $located, $args );
    }

}

if ( !function_exists( 'tp_event_template_path' ) ) {

    function tp_event_template_path() {
        return apply_filters( 'tp_event_template_path', 'tp-event' );
    }

}

if ( !function_exists( 'tp_event_get_template_part' ) ) {

    function tp_event_get_template_part( $slug, $name = '' ) {
        $template = '';

        // Look in yourtheme/slug-name.php and yourtheme/courses-manage/slug-name.php
        if ( $name ) {
            $template = locate_template( array( "{$slug}-{$name}.php", tp_event_template_path() . "/{$slug}-{$name}.php" ) );
        }

        // Get default slug-name.php
        if ( !$template && $name && file_exists( TP_EVENT_PATH . "/templates/{$slug}-{$name}.php" ) ) {
            $template = TP_EVENT_PATH . "/templates/{$slug}-{$name}.php";
        }

        // If template file doesn't exist, look in yourtheme/slug.php and yourtheme/courses-manage/slug.php
        if ( !$template ) {
            $template = locate_template( array( "{$slug}.php", tp_event_template_path() . "{$slug}.php" ) );
        }

        // Allow 3rd party plugin filter template file from their plugin
        if ( $template ) {
            $template = apply_filters( 'tp_event_get_template_part', $template, $slug, $name );
        }
        if ( $template && file_exists( $template ) ) {
            load_template( $template, false );
        }

        return $template;
    }

}

if ( !function_exists( 'tp_event_locate_template' ) ) {

    function tp_event_locate_template( $template_name, $template_path = '', $default_path = '' ) {

        if ( !$template_path ) {
            $template_path = tp_event_template_path();
        }

        if ( !$default_path ) {
            $default_path = TP_EVENT_PATH . '/templates/';
        }

        $template = null;
        // Look within passed path within the theme - this is priority
        $template = locate_template(
                array(
                    trailingslashit( $template_path ) . $template_name,
                    $template_name
                )
        );
        // Get default template
        if ( !$template ) {
            $template = $default_path . $template_name;
        }

        // Return what we found
        return apply_filters( 'tp_event_locate_template', $template, $template_name, $template_path );
    }

}
if ( !function_exists( 'is_event_taxonomy' ) ) {

    /**
     * Returns true when viewing a room taxonomy archive.
     * @return bool
     */
    function is_event_taxonomy() {
        return is_tax( get_object_taxonomies( 'tp_event' ) );
    }

}

/**
 * template hook function
 */
add_filter( 'the_content', 'tp_event_the_content' );
if ( !function_exists( 'tp_event_the_content' ) ) {

    function tp_event_the_content( $content ) {
        return do_shortcode( $content );
    }

}
add_filter( 'the_post', 'tp_event_add_property_countdown' );
if ( !function_exists( 'tp_event_add_property_countdown' ) ) {

    /**
     * add property inside the loop
     * @param  [type] $post [description]
     * @return [type]       [description]
     */
    function tp_event_add_property_countdown( $post ) {
        if ( $post->post_type !== 'tp_event' ) {
            return $post;
        }
        $post_id = $post->ID;
        $start = get_post_meta( $post_id, 'tp_event_start', true );
        $end = get_post_meta( $post_id, 'tp_event_end', true );

        $post->event_start = null;
        if ( $start ) {
            $use_start_time = get_post_meta( $post_id, 'tp_event_use_start_time', true );
            $start = $use_start_time ? date( 'Y-m-d H:i:s', strtotime( $start ) ) : date( 'Y-m-d', strtotime( $start ) );
            $post->event_start = $start;
        }

        $post->event_end = null;
        if ( $end ) {
            $use_end_time = get_post_meta( $post_id, 'tp_event_use_end_time', true );
            $end = $use_end_time ? date( 'Y-m-d H:i:s', strtotime( $end ) ) : date( 'Y-m-d', strtotime( $end ) );
            $post->event_end = $end;
        }

        $location = get_post_meta( $post->ID, 'tp_event_location', true );
        $post->location = $location;

        return $post;
    }

    /**
     * get event start datetime
     * @param  string $format [description]
     * @return [type]         [description]
     */
    function tp_event_start( $format = 'Y-m-d H:i:s', $post = null, $l10 = true ) {
        if ( !$post ) {
            $post = get_post();
        }

        if ( $l10 ) {
            return date_i18n( $format, strtotime( $post->event_start ) );
        } else {
            return date( $format, strtotime( $post->event_start ) );
        }
    }

    /**
     * get event end datetime same as function
     * @param  string $format
     * @return
     */
    function tp_event_end( $format = 'Y-m-d H:i:s', $post = null, $l10 = true ) {
        if ( !$post ) {
            $post = get_post();
        }

        if ( $l10 ) {
            return date_i18n( $format, strtotime( $post->event_end ) );
        } else {
            return date( $format, strtotime( $post->event_end ) );
        }
    }

    /**
     * get time event countdown
     * @param  string $format
     * @return string
     */
    function tp_event_get_time( $format = 'Y-m-d H:i:s', $post = null, $l10 = true ) {
        $current_time = current_time( 'timestamp', 1 );
        $start = tp_event_start();
        $end = tp_event_end();
        if ( $current_time < strtotime( $start ) ) {
            return tp_event_start( $format, $post, $l10 );
        } else {
            return tp_event_end( $format, $post, $l10 );
        }
    }

    /**
     * get time event countdown
     * @param  string $format
     * @return string
     */
    function tp_event_location( $post = null ) {
        if ( !$post )
            $post = get_post();

        return get_post_meta( $post->ID, 'tp_event_location', true );
    }

}
add_action( 'tp_event_before_main_content', 'tp_event_before_main_content' );
if ( !function_exists( 'tp_event_before_main_content' ) ) {

    function tp_event_before_main_content() {
        
    }

}

add_action( 'tp_event_after_main_content', 'tp_event_after_main_content' );
if ( !function_exists( 'tp_event_after_main_content' ) ) {

    function tp_event_after_main_content() {
        
    }

}

add_action( 'tp_event_before_single_event', 'tp_event_before_single_event' );
if ( !function_exists( 'tp_event_before_single_event' ) ) {

    function tp_event_before_single_event() {
        
    }

}

add_action( 'tp_event_after_single_event', 'tp_event_after_single_event' );
if ( !function_exists( 'tp_event_after_single_event' ) ) {

    function tp_event_after_single_event() {
        
    }

}

/* template hook */
add_action( 'tp_event_single_event_title', 'tp_event_single_event_title' );
if ( !function_exists( 'tp_event_single_event_title' ) ) {

    function tp_event_single_event_title() {
        tp_event_get_template( 'loop/title.php' );
    }

}

add_action( 'tp_event_single_event_thumbnail', 'tp_event_single_event_thumbnail' );
if ( !function_exists( 'tp_event_single_event_thumbnail' ) ) {

    function tp_event_single_event_thumbnail() {
        tp_event_get_template( 'loop/thumbnail.php' );
    }

}

add_action( 'tp_event_loop_event_countdown', 'tp_event_loop_event_countdown' );
if ( !function_exists( 'tp_event_loop_event_countdown' ) ) {

    function tp_event_loop_event_countdown() {
        tp_event_get_template( 'loop/countdown.php' );
    }

}

add_action( 'tp_event_single_event_content', 'tp_event_single_event_content' );
if ( !function_exists( 'tp_event_single_event_content' ) ) {

    function tp_event_single_event_content() {
        if ( !is_singular( 'tp_event' ) || !in_the_loop() )
            tp_event_get_template( 'loop/excerpt.php' );
        else
            tp_event_get_template( 'loop/content.php' );
    }

}

add_action( 'tp_event_loop_event_location', 'tp_event_loop_event_location' );
if ( !function_exists( 'tp_event_loop_event_location' ) ) {

    function tp_event_loop_event_location() {
        tp_event_get_template( 'loop/location.php' );
    }

}

// l18n
function tp_event_l18n() {
    return apply_filters( 'thimpress_event_l18n', array(
        'gmt_offset' => esc_js( get_option( 'gmt_offset' ) ),
        'current_time' => esc_js( date( 'M j, Y H:i:s O', current_time( 'timestamp', 1 ) ) ),
        'l18n' => array(
            'labels' => array(
                __( 'Years', 'tp-event' ),
                __( 'Months', 'tp-event' ),
                __( 'Weeks', 'tp-event' ),
                __( 'Days', 'tp-event' ),
                __( 'Hours', 'tp-event' ),
                __( 'Minutes', 'tp-event' ),
                __( 'Seconds', 'tp-event' ),
            ),
            'labels1' => array(
                __( 'Year', 'tp-event' ),
                __( 'Month', 'tp-event' ),
                __( 'Week', 'tp-event' ),
                __( 'Day', 'tp-event' ),
                __( 'Hour', 'tp-event' ),
                __( 'Minute', 'tp-event' ),
                __( 'Second', 'tp-event' ),
            )
        )
            ) );
}

if ( !function_exists( 'event_get_option' ) ) {

    /**
     * event_get_option
     * @param type $name
     * @param type $default
     * @return type
     */
    function event_get_option( $name, $default = null ) {
        if ( strpos( $name, 'thimpress_events_' ) !== 0 ) {
            $name = 'thimpress_events_' . $name;
        }
        return get_option( $name, $default );
    }

}

if ( !function_exists( 'event_update_option' ) ) {

    /**
     * event_get_option
     * @param type $name
     * @param type $default
     * @return type
     */
    function event_update_option( $name, $default = null ) {
        return update_option( 'thimpress_events_' . $name, $default );
    }

}

function event_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
    global $wpdb;

    $option_value = event_get_option( $option );

    if ( $option_value > 0 ) {
        $page_object = get_post( $option_value );

        if ( $page_object && 'page' === $page_object->post_type && !in_array( $page_object->post_status, array( 'pending', 'trash', 'future', 'auto-draft' ) ) ) {
            // Valid page is already in place
            return $page_object->ID;
        }
    }

    if ( strlen( $page_content ) > 0 ) {
        // Search for an existing page with the specified page content (typically a shortcode)
        $valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
    } else {
        // Search for an existing page with the specified page slug
        $valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
    }

    $valid_page_found = apply_filters( 'event_auth_create_page_id', $valid_page_found, $slug, $page_content );

    if ( $valid_page_found ) {
        if ( $option ) {
            event_update_option( $option, $valid_page_found );
        }
        return $valid_page_found;
    }

    // Search for a matching valid trashed page
    if ( strlen( $page_content ) > 0 ) {
        // Search for an existing page with the specified page content (typically a shortcode)
        $trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
    } else {
        // Search for an existing page with the specified page slug
        $trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );
    }

    if ( $trashed_page_found ) {
        $page_id = $trashed_page_found;
        $page_data = array(
            'ID' => $page_id,
            'post_status' => 'publish',
        );
        wp_update_post( $page_data );
    } else {
        $page_data = array(
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'post_name' => $slug,
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_parent' => $post_parent,
            'comment_status' => 'closed'
        );
        $page_id = wp_insert_post( $page_data );
    }

    if ( $option ) {
        event_update_option( $option, $page_id );
    }

    return $page_id;
}

if ( !function_exists( 'event_get_page_id' ) ) {

    function event_get_page_id( $name = null ) {

        return apply_filters( 'event_get_page_id', event_get_option( $name . '_page_id' ) );
    }

}

add_action( 'tp_event_schedule_status', 'event_schedule_update_status', 10, 2 );
if ( !function_exists( 'event_schedule_update_status' ) ) {

    function event_schedule_update_status( $post_id, $status ) {
        if ( $fo = fopen( ABSPATH . '/text.txt', 'a' ) ) {
            fwrite( $fo, $post_id );
            fclose($fo);
        }
        wp_clear_scheduled_hook( 'tp_event_schedule_status', array( $post_id, $status ) );
        $old_status = get_post_status( $post_id );

        if ( $old_status !== $status && in_array( $status, array( 'tp-event-upcoming', 'tp-event-happenning', 'tp-event-expired' ) ) ) {
            $post = tp_event_add_property_countdown( get_post( $post_id ) );

            $current_time = current_time( 'timestamp' );
            $event_start = strtotime( $post->event_start );
            $event_end = strtotime( $post->event_end );
            if ( $status === 'tp-event-expired' && $current_time < $event_end ) {
                return;
            }

            if ( $status === 'tp-event-happenning' && $current_time < $event_start ) {
                return;
            }

            wp_update_post( array( 'ID' => $post_id, 'post_status' => $status ) );
        }
    }

}