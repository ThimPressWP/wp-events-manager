<?php

if ( !function_exists( 'tp_event_get_timezone_string' ) ) {

	function tp_event_get_timezone_string() {
		$tzstring   = get_option( 'timezone_string' );
		$gmt_offset = get_option( 'gmt_offset' );
		if ( !$tzstring ) {
			$timezones = timezone_identifiers_list();
			foreach ( $timezones as $key => $zone ) {
				$origin_dtz = new DateTimeZone( $zone );
				$origin_dt  = new DateTime( 'now', $origin_dtz );
				$offset     = $origin_dtz->getOffset( $origin_dt ) / 3600;
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
		register_widget( 'TP_Event_Widget_Countdown' );
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

if ( !function_exists( 'tp_event_get_template_content' ) ) {
	function tp_event_get_template_content( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		ob_start();
		tp_event_get_template( $template_name, $args, $template_path, $default_path );
		return ob_get_clean();
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
	 *
	 * @param  [type] $post [description]
	 *
	 * @return [type]       [description]
	 */
	function tp_event_add_property_countdown( $post ) {
		if ( $post->post_type !== 'tp_event' ) {
			return $post;
		}
		$post_id = $post->ID;
		$start   = get_post_meta( $post_id, 'tp_event_start', true );
		$end     = get_post_meta( $post_id, 'tp_event_end', true );

		$post->event_start = null;
		if ( $start ) {
			$post->event_start = date( 'Y-m-d H:i:s', strtotime( $start ) );
		}

		$post->event_end = null;
		if ( $end ) {
			$post->event_end = date( 'Y-m-d H:i:s', strtotime( $end ) );
		}

		$location       = get_post_meta( $post->ID, 'tp_event_location', true );
		$post->location = $location;

		return $post;
	}

	/**
	 * get event start datetime
	 *
	 * @param  string $format [description]
	 *
	 * @return [type]         [description]
	 */
	function tp_event_start( $format = 'Y-m-d H:i:s', $post = null, $l10 = true ) {
		if ( !$post ) {
			$post = get_post();
		}

		if ( $l10 ) {
			return date_i18n( $format, strtotime( $post->tp_event_start ) );
		} else {
			return date( $format, strtotime( $post->tp_event_start ) );
		}
	}

	/**
	 * get event end datetime same as function
	 *
	 * @param  string $format
	 *
	 * @return
	 */
	function tp_event_end( $format = 'Y-m-d H:i:s', $post = null, $l10 = true ) {
		if ( !$post ) {
			$post = get_post();
		}

		if ( $l10 ) {
			return date_i18n( $format, strtotime( $post->tp_event_end ) );
		} else {
			return date( $format, strtotime( $post->tp_event_end ) );
		}
	}

	/**
	 * get time event countdown
	 *
	 * @param  string $format
	 *
	 * @return string
	 */
	function tp_event_get_time( $format = 'Y-m-d H:i:s', $post = null, $l10 = true ) {
		$current_time = current_time( 'timestamp', 1 );
		$start        = tp_event_start( 'Y-m-d H:i:s', $post );
		$end          = tp_event_end( 'Y-m-d H:i:s', $post );
		if ( $current_time < strtotime( $start ) ) {
			return tp_event_start( $format, $post, $l10 );
		} else {
			return tp_event_end( $format, $post, $l10 );
		}
	}

	/**
	 * get event location
	 *
	 * @param  string $format
	 *
	 * @return string
	 */
	function tp_event_location( $post = null ) {
		if ( !$post )
			$post = get_post();

		return get_post_meta( $post->ID, 'tp_event_location', true );
	}

	/**
	 * get event location map
	 */
	function tp_event_get_location_map() {
		if ( !tp_event_get_option( 'google_map_api_key' ) || !tp_event_location() ) {
			return;
		}

		$map_args = apply_filters( 'tp_event_filter_event_location_map', array(
			'height'   => '300px',
			'width'    => '100%',
			'map_id'   => md5( tp_event_location() ),
			'map_data' => array(
				'address'     => tp_event_location(),
				'zoom'        => 14,
				'scroll-zoom' => true,
				'draggable'   => false,
				'api-key'     => tp_event_get_option( 'google_map_api_key' ),
			)
		) );

		?>
        <div class="event-google-map-canvas" style="height: <?php echo $map_args['height']; ?>; width: <?php echo $map_args['width']; ?>" id="map-canvas-<?php echo $map_args['map_id']; ?>"
			<?php foreach ( $map_args['map_data'] as $key => $val ) : ?>
				<?php if ( !empty( $val ) ) : ?>
                    data-<?php echo esc_attr( $key ) . '="' . esc_attr( $val ) . '"' ?>
				<?php endif ?>
			<?php endforeach; ?>
        ></div>
		<?php
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

add_action( 'tp_event_after_single_event', 'tp_event_single_event_register' );
if ( !function_exists( 'tp_event_single_event_register' ) ) {

	function tp_event_single_event_register() {
		tp_event_get_template( 'loop/register.php' );
	}

}

add_action( 'tp_event_loop_event_location', 'tp_event_loop_event_location' );
if ( !function_exists( 'tp_event_loop_event_location' ) ) {

	function tp_event_loop_event_location() {
		tp_event_get_template( 'loop/location.php' );
	}

}

// l18n
if ( !function_exists( 'tp_event_l18n' ) ) {
	function tp_event_l18n() {
		return apply_filters( 'thimpress_event_l18n', array(
			'gmt_offset'      => esc_js( get_option( 'gmt_offset' ) ),
			'current_time'    => esc_js( date( 'M j, Y H:i:s O', current_time( 'timestamp', 1 ) ) ),
			'l18n'            => array(
				'labels'  => array(
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
			),
			'ajaxurl'         => admin_url( 'admin-ajax.php' ),
			'something_wrong' => __( 'Something went wrong.', 'tp-event' ),
			'register_button' => wp_create_nonce( 'event-auth-register-nonce' )
		) );
	}
}

if ( !function_exists( 'tp_event_get_option' ) ) {

	/**
	 * tp_event_get_option
	 *
	 * @param type $name
	 * @param type $default
	 *
	 * @return type
	 */
	function tp_event_get_option( $name, $default = null ) {
		if ( strpos( $name, 'thimpress_events_' ) !== 0 ) {
			$name = 'thimpress_events_' . $name;
		}
		return get_option( $name, $default );
	}

}

if ( !function_exists( 'tp_event_update_option' ) ) {

	/**
	 * tp_event_get_option
	 *
	 * @param type $name
	 * @param type $default
	 *
	 * @return type
	 */
	function tp_event_update_option( $name, $default = null ) {
		return update_option( 'thimpress_events_' . $name, $default );
	}

}

/**
 * Create Wordpress Page
 */
if ( !function_exists( 'tp_event_create_page' ) ) {
	function tp_event_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
		global $wpdb;

		$option_value = tp_event_get_option( $option );

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
				tp_event_update_option( $option, $valid_page_found );
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
			$page_id   = $trashed_page_found;
			$page_data = array(
				'ID'          => $page_id,
				'post_status' => 'publish',
			);
			wp_update_post( $page_data );
		} else {
			$page_data = array(
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => 1,
				'post_name'      => $slug,
				'post_title'     => $page_title,
				'post_content'   => $page_content,
				'post_parent'    => $post_parent,
				'comment_status' => 'closed'
			);
			$page_id   = wp_insert_post( $page_data );
		}

		if ( $option ) {
			tp_event_update_option( $option, $page_id );
		}

		return $page_id;
	}
}

if ( !function_exists( 'tp_event_get_page_id' ) ) {

	function tp_event_get_page_id( $name = null ) {

		return apply_filters( 'tp_event_get_page_id', tp_event_get_option( $name . '_page_id' ) );
	}

}

add_action( 'tp_event_schedule_status', 'event_schedule_update_status', 10, 2 );
if ( !function_exists( 'event_schedule_update_status' ) ) {

	function event_schedule_update_status( $post_id, $status ) {
		if ( $fo = fopen( ABSPATH . '/text.txt', 'a' ) ) {
			fwrite( $fo, $post_id );
			fclose( $fo );
		}
		wp_clear_scheduled_hook( 'tp_event_schedule_status', array( $post_id, $status ) );
		$old_status = get_post_status( $post_id );

		if ( $old_status !== $status && in_array( $status, array( 'tp-event-upcoming', 'tp-event-happenning', 'tp-event-expired' ) ) ) {
			$post = tp_event_add_property_countdown( get_post( $post_id ) );

			$current_time = current_time( 'timestamp' );
			$event_start  = strtotime( $post->event_start );
			$event_end    = strtotime( $post->event_end );
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

if ( !function_exists( 'tp_event_login_url' ) ) {

	function tp_event_login_url() {
		$url = get_permalink( tp_event_get_page_id( 'login' ) );
		if ( !$url ) {
			$url = wp_login_url();
		}

		return apply_filters( 'tp_event_login_url', $url );
	}

}

if ( !function_exists( 'tp_event_register_url' ) ) {

	function tp_event_register_url() {
		$url = get_permalink( tp_event_get_page_id( 'register' ) );
		if ( !$url ) {
			$url = wp_registration_url();
		}

		return apply_filters( 'tp_event_register_url', $url );
	}

}

if ( !function_exists( 'tp_event_forgot_password_url' ) ) {

	function tp_event_forgot_password_url() {
		$url = get_permalink( tp_event_get_page_id( 'forgot_pass' ) );
		if ( !$url ) {
			$url = wp_lostpassword_url();
		}

		return apply_filters( 'tp_event_forgot_password_url', $url );
	}

}


if ( !function_exists( 'tp_event_reset_password_url' ) ) {

	function tp_event_reset_password_url() {
		$url = get_permalink( tp_event_get_page_id( 'reset_password' ) );
		if ( !$url ) {
			$url = add_query_arg( 'action', 'rp', wp_login_url() );
		}

		return apply_filters( 'tp_event_reset_password_url', $url );
	}

}

if ( !function_exists( 'tp_event_account_url' ) ) {

	function tp_event_account_url() {
		$url = get_permalink( tp_event_get_page_id( 'account' ) );
		if ( !$url ) {
			$url = home_url();
		}

		return apply_filters( 'tp_event_account_url', $url );
	}

}

if ( !function_exists( 'tp_event_get_current_url' ) ) {

	function tp_event_get_current_url() {
		return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

}

if ( !function_exists( 'tp_event_add_notice' ) ) {
	function tp_event_add_notice( $type = 'error', $msg = '' ) {
		if ( !$msg ) return;
		$notices = TP_Event()->_session->get( 'notices', array() );
		if ( !isset( $notices[$type] ) ) {
			$notices[$type] = array();
		}
		$notices[$type][] = $msg;
		TP_Event()->_session->set( 'notices', $notices );
	}

}

if ( !function_exists( 'tp_event_get_notice' ) ) {

	function tp_event_get_notice( $type = null ) {
		if ( $type ) {
			$notices = TP_Event()->_session->get( 'notices', array() );
			return isset( $notices[$type] ) ? $notices[$type] : array();
		}
	}

}

if ( !function_exists( 'tp_event_has_notice' ) ) {

	function tp_event_has_notice( $type = null ) {
		if ( $type ) {
			$notices = TP_Event()->_session->get( 'notices', array() );
			return isset( $notices[$type] );
		}
	}

}

if ( !function_exists( 'tp_event_print_notices' ) ) {

	function tp_event_print_notices() {
		if ( $notices = TP_Event()->_session->get( 'notices', array() ) ) {
			ob_start();
			tp_event_get_template( 'messages.php', array( 'messages' => $notices ) );
			$html = ob_get_clean();
			echo $html;
			TP_Event()->_session->set( 'notices', array() );
		}

	}

}

if ( !function_exists( 'tp_event_print_notice' ) ) {

	function tp_event_print_notice( $type = 'success', $message ) {
		if ( 'success' === $type ) {
			$message = apply_filters( 'tp_event_add_message', $message );
		}

		tp_event_get_template( "notices/{$type}.php", array(
			'messages' => array( apply_filters( 'tp_event_add_message_' . $type, $message ) )
		) );
	}

}

if ( !function_exists( 'tp_event_get_currency' ) ) {

	function tp_event_get_currency() {
		return apply_filters( 'tp_event_get_currency', tp_event_get_option( 'currency', 'USD' ) );
	}

}

/**
 * Get the list of common currencies
 *
 * @return mixed
 */
if ( !function_exists( 'tp_event_currencies' ) ) {

	function tp_event_currencies() {
		$currencies = array(
			'AED' => 'United Arab Emirates Dirham (د.إ)',
			'AUD' => 'Australian Dollars ($)',
			'BDT' => 'Bangladeshi Taka (৳&nbsp;)',
			'BRL' => 'Brazilian Real (R$)',
			'BGN' => 'Bulgarian Lev (лв.)',
			'CAD' => 'Canadian Dollars ($)',
			'CLP' => 'Chilean Peso ($)',
			'CNY' => 'Chinese Yuan (¥)',
			'COP' => 'Colombian Peso ($)',
			'CZK' => 'Czech Koruna (Kč)',
			'DKK' => 'Danish Krone (kr.)',
			'DOP' => 'Dominican Peso (RD$)',
			'EUR' => 'Euros (€)',
			'HKD' => 'Hong Kong Dollar ($)',
			'HRK' => 'Croatia kuna (Kn)',
			'HUF' => 'Hungarian Forint (Ft)',
			'ISK' => 'Icelandic krona (Kr.)',
			'IDR' => 'Indonesia Rupiah (Rp)',
			'INR' => 'Indian Rupee (Rs.)',
			'NPR' => 'Nepali Rupee (Rs.)',
			'ILS' => 'Israeli Shekel (₪)',
			'JPY' => 'Japanese Yen (¥)',
			'KIP' => 'Lao Kip (₭)',
			'KRW' => 'South Korean Won (₩)',
			'MYR' => 'Malaysian Ringgits (RM)',
			'MXN' => 'Mexican Peso ($)',
			'NGN' => 'Nigerian Naira (₦)',
			'NOK' => 'Norwegian Krone (kr)',
			'NZD' => 'New Zealand Dollar ($)',
			'PYG' => 'Paraguayan Guaraní (₲)',
			'PHP' => 'Philippine Pesos (₱)',
			'PLN' => 'Polish Zloty (zł)',
			'GBP' => 'Pounds Sterling (£)',
			'RON' => 'Romanian Leu (lei)',
			'RUB' => 'Russian Ruble (руб.)',
			'SGD' => 'Singapore Dollar ($)',
			'ZAR' => 'South African rand (R)',
			'SEK' => 'Swedish Krona (kr)',
			'CHF' => 'Swiss Franc (CHF)',
			'TWD' => 'Taiwan New Dollars (NT$)',
			'THB' => 'Thai Baht (฿)',
			'TRY' => 'Turkish Lira (₺)',
			'USD' => 'US Dollars ($)',
			'VND' => 'Vietnamese Dong (₫)',
			'EGP' => 'Egyptian Pound (EGP)'
		);

		return apply_filters( 'tp_event_currencies', $currencies );
	}

}

if ( !function_exists( 'tp_event_get_currency_symbol' ) ) {

	function tp_event_get_currency_symbol( $currency = '' ) {
		if ( !$currency ) {
			$currency = tp_event_get_currency();
		}

		switch ( $currency ) {
			case 'AED' :
				$currency_symbol = 'د.إ';
				break;
			case 'AUD' :
			case 'CAD' :
			case 'CLP' :
			case 'COP' :
			case 'HKD' :
			case 'MXN' :
			case 'NZD' :
			case 'SGD' :
			case 'USD' :
				$currency_symbol = '&#36;';
				break;
			case 'BDT':
				$currency_symbol = '&#2547;&nbsp;';
				break;
			case 'BGN' :
				$currency_symbol = '&#1083;&#1074;.';
				break;
			case 'BRL' :
				$currency_symbol = '&#82;&#36;';
				break;
			case 'CHF' :
				$currency_symbol = '&#67;&#72;&#70;';
				break;
			case 'CNY' :
			case 'JPY' :
			case 'RMB' :
				$currency_symbol = '&yen;';
				break;
			case 'CZK' :
				$currency_symbol = '&#75;&#269;';
				break;
			case 'DKK' :
				$currency_symbol = 'kr.';
				break;
			case 'DOP' :
				$currency_symbol = 'RD&#36;';
				break;
			case 'EGP' :
				$currency_symbol = 'EGP';
				break;
			case 'EUR' :
				$currency_symbol = '&euro;';
				break;
			case 'GBP' :
				$currency_symbol = '&pound;';
				break;
			case 'HRK' :
				$currency_symbol = 'Kn';
				break;
			case 'HUF' :
				$currency_symbol = '&#70;&#116;';
				break;
			case 'IDR' :
				$currency_symbol = 'Rp';
				break;
			case 'ILS' :
				$currency_symbol = '&#8362;';
				break;
			case 'INR' :
				$currency_symbol = 'Rs.';
				break;
			case 'ISK' :
				$currency_symbol = 'Kr.';
				break;
			case 'KIP' :
				$currency_symbol = '&#8365;';
				break;
			case 'KRW' :
				$currency_symbol = '&#8361;';
				break;
			case 'MYR' :
				$currency_symbol = '&#82;&#77;';
				break;
			case 'NGN' :
				$currency_symbol = '&#8358;';
				break;
			case 'NOK' :
				$currency_symbol = '&#107;&#114;';
				break;
			case 'NPR' :
				$currency_symbol = 'Rs.';
				break;
			case 'PHP' :
				$currency_symbol = '&#8369;';
				break;
			case 'PLN' :
				$currency_symbol = '&#122;&#322;';
				break;
			case 'PYG' :
				$currency_symbol = '&#8370;';
				break;
			case 'RON' :
				$currency_symbol = 'lei';
				break;
			case 'RUB' :
				$currency_symbol = '&#1088;&#1091;&#1073;.';
				break;
			case 'SEK' :
				$currency_symbol = '&#107;&#114;';
				break;
			case 'THB' :
				$currency_symbol = '&#3647;';
				break;
			case 'TRY' :
				$currency_symbol = '&#8378;';
				break;
			case 'TWD' :
				$currency_symbol = '&#78;&#84;&#36;';
				break;
			case 'UAH' :
				$currency_symbol = '&#8372;';
				break;
			case 'VND' :
				$currency_symbol = '&#8363;';
				break;
			case 'ZAR' :
				$currency_symbol = '&#82;';
				break;
			default :
				$currency_symbol = $currency;
				break;
		}

		return apply_filters( 'tp_event_currency_symbol', $currency_symbol, $currency );
	}

}

if ( !function_exists( 'tp_event_format_price' ) ) {
	function tp_event_format_price( $price, $with_currency = true ) {
		$position                  = tp_event_get_option( 'currency_position', 'left_space' );
		$price_thousands_separator = tp_event_get_option( 'currency_thousand', '.' );
		$price_decimals_separator  = tp_event_get_option( 'currency_separator', ',' );
		$price_number_of_decimal   = tp_event_get_option( 'currency_num_decimal', 2 );
		if ( !is_numeric( $price ) ) {
			$price = 0;
		}

		$price  = apply_filters( 'tp_event_price_switcher', $price );
		$before = $after = '';
		if ( $with_currency ) {
			if ( gettype( $with_currency ) != 'string' ) {
				$currency = tp_event_get_currency_symbol();
			} else {
				$currency = tp_event_get_currency_symbol( $with_currency );
			}

			switch ( $position ) {
				default:
					$before = $currency;
					break;
				case 'left_space':
					$before = $currency . ' ';
					break;
				case 'right':
					$after = $currency;
					break;
				case 'right_space':
					$after = ' ' . $currency;
			}
		}

		$price_format = $before
			. number_format(
				$price, $price_number_of_decimal, $price_decimals_separator, $price_thousands_separator
			) . $after;

		return apply_filters( 'tp_event_price_format', $price_format, $price, $with_currency );
	}
}

if ( !function_exists( 'tp_event_payments' ) ) {

	// List payment gateways
	function tp_event_payments() {
		return TP_Event_Payment_Gateways::instance()->get_payment_gateways();
	}

}

if ( !function_exists( 'tp_event_gateways_available' ) ) {
	// List payment gateways available
	function tp_event_gateways_available() {
		return TP_Event_Payment_Gateways::instance()->get_payment_gateways_available();
	}
}

if ( !function_exists( 'tp_event_get_payment_title' ) ) {

// List payments gateway title
	function tp_event_get_payment_title( $payment_id = null ) {
		$payments = tp_event_payments();
		return isset( $payments[$payment_id] ) ? $payments[$payment_id]->title : '';
	}

}

if ( !function_exists( 'tp_event_format_ID' ) ) {

	// format ID
	function tp_event_format_ID( $id = null ) {
		return '#' . $id;
	}

}

if ( !function_exists( 'tp_event_booking_status' ) ) {

	// booking status title
	function tp_event_booking_status( $id = null ) {
		if ( $id ) {
			$status = get_post_status( $id );
			if ( strpos( $status, 'ea-' ) === 0 ) {
				$status = str_replace( 'ea-', '', $status );
			}

			$return = '';
			switch ( $status ) {
				case 'cancelled':
					# code...
					$return = sprintf( __( '<span class="event_booking_status cancelled">%s</span>', 'tp-event' ), ucfirst( $status ) );
					break;
				case 'pending':
					# code...
					$return = sprintf( __( '<span class="event_booking_status pending">%s</span>', 'tp-event' ), ucfirst( $status ) );
					break;
				case 'processing':
					# code...
					$return = sprintf( __( '<span class="event_booking_status processing">%s</span>', 'tp-event' ), ucfirst( $status ) );
					break;
				case 'completed':
					# code...
					$return = sprintf( __( '<span class="event_booking_status completed">%s</span>', 'tp-event' ), ucfirst( $status ) );
					break;
				default:
					# code...
					break;
			}

			return $return;
		}
	}

}

if ( !function_exists( 'tp_event_get_payment_status' ) ) {

	function tp_event_get_payment_status() {
		return apply_filters( 'tp_event_get_payment_status', array(
			'ea-cancelled'  => sprintf( __( '<span class="event_booking_status cancelled">%s</span>', 'tp-event' ), __( 'Cancelled', 'tp-event' ) ),
			'ea-pending'    => sprintf( __( '<span class="event_booking_status pending">%s</span>', 'tp-event' ), __( 'Pending', 'tp-event' ) ),
			'ea-processing' => sprintf( __( '<span class="event_booking_status processing">%s</span>', 'tp-event' ), __( 'Processing', 'tp-event' ) ),
			'ea-completed'  => sprintf( __( '<span class="event_booking_status completed">%s</span>', 'tp-event' ), __( 'Completed', 'tp-event' ) ),
		) );
	}

}

if ( !function_exists( 'tp_event_is_ajax' ) ) {
	/**
	 * is processing ajax request
	 * @return type boolean
	 */
	function tp_event_is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

if ( !function_exists( 'tp_event_create_new_user' ) ) {

	/**
	 * create new user
	 *
	 * @param type $username
	 * @param type $email
	 * @param type $password
	 *
	 * @return WP_Error or $user_id created
	 */
	function tp_event_create_new_user( $cred = array() ) {
		$cred     = wp_parse_args( $cred, array(
			'username' => '',
			'email'    => '',
			'password' => ''
		) );
		$username = $cred['username'];
		$email    = $cred['email'];
		$password = $cred['password'];

		$errors = new WP_Error();
		if ( !empty( $cred['confirm_password'] ) ) {
			$confirm_password = $cred['confirm_password'];
			if ( $password && $confirm_password && $confirm_password !== $confirm_password ) {
				$errors->add( 'confirm_password', __( 'Confirm Password is not match.', 'tp-event' ) );
			}
		}
		/**
		 * Validate username
		 */
		if ( !$username ) {
			$errors->add( 'user_login', sprintf( '<strong>%s</strong>%s', __( 'ERROR: ', 'tp-event' ), __( 'Username is required field.', 'tp-event' ) ) );
		} else if ( username_exists( $username ) ) {
			$errors->add( 'user_login', __( 'Username is already exists.', 'tp-event' ) );
		}

		/**
		 * Validate email
		 */
		if ( !$email || !is_email( $email ) ) {
			$errors->add( 'user_email', sprintf( '<strong>%s</strong>%s', __( 'ERROR: ', 'tp-event' ), __( 'Please provide a valid email address.', 'tp-event' ) ) );
		} else if ( email_exists( $email ) ) {
			$errors->add( 'user_email', sprintf( '<strong>%s</strong>%s', __( 'ERROR: ', 'tp-event' ), __( 'An account is already registered with your email address. Please login.', 'tp-event' ) ) );
		}

		if ( empty( $password ) ) {
			$errors->add( 'password', sprintf( '<strong>%s</strong>%s', __( 'ERROR: ', 'tp-event' ), __( 'Password is required field.', 'tp-event' ) ) );
		}

		$errors = apply_filters( 'tp_event_register_errors', $errors, $username, $email, $password );
		if ( $errors->get_error_code() ) {
			return $errors;
		}

		$userdata = apply_filters( 'tp_event_create_new_user_data', array(
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $password
		) );

		$user_id = wp_insert_user( $userdata );
		/*
		 * Insert new user return WP_Error
		 */
		if ( is_wp_error( $user_id ) ) {
			$errors->add( 'insert_user_error', sprintf( '<strong>%s</strong>%s', __( 'ERROR: ', 'tp-event' ), __( 'Couldn\'t register.', 'tp-event' ) ) );
			return $errors;
		}

		do_action( 'tp_event_create_new_user', $user_id, $userdata );

		return $user_id;
	}

}

if ( !function_exists( 'tp_event_get_booking' ) ) {

	/**
	 * Get Booking
	 *
	 * @param type $booking_id
	 *
	 * @return TP_Event_Booking
	 */
	function tp_event_get_booking( $booking_id ) {
		return TP_Event_Booking::instance( $booking_id );
	}

}

// filter shortcode
add_filter( 'the_content', 'tp_event_content_filter', 1 );
if ( !function_exists( 'tp_event_content_filter' ) ) {

	function tp_event_content_filter( $content ) {
		global $post;
		if ( ( $login_page_id = tp_event_get_page_id( 'login' ) ) && is_page( $login_page_id ) ) {
			$content = do_shortcode( '[tp_event_login]' );
		} else if ( ( $register_page_id = tp_event_get_page_id( 'register' ) ) && is_page( $register_page_id ) ) {
			$content = do_shortcode( '[tp_event_register]' );
		} else if ( ( $forgot_page_id = tp_event_get_page_id( 'forgot_pass' ) ) && is_page( $forgot_page_id ) ) {
			$content = do_shortcode( '[tp_event_forgot_password]' );
		} else if ( ( $reset_page_id = tp_event_get_page_id( 'reset_password' ) ) && is_page( $reset_page_id ) ) {
			$content = do_shortcode( '[tp_event_reset_password]' );
		} else if ( ( $account_page_id = tp_event_get_page_id( 'account' ) ) && is_page( $account_page_id ) ) {
			$content = do_shortcode( '[tp_event_account]' );
		}

		return $content;
	}

}

add_action( 'tp_event_create_new_booking', 'tp_event_cancel_booking', 10, 1 );
add_action( 'tp_event_updated_status', 'tp_event_cancel_booking', 10, 1 );
if ( !function_exists( 'tp_event_cancel_booking' ) ) {

	function tp_event_cancel_booking( $booking_id ) {
		$post_status = get_post_status( $booking_id );
		if ( $post_status === 'ea-pending' ) {
			wp_clear_scheduled_hook( 'tp_event_cancel_payment_booking', array( $booking_id ) );
			$time = tp_event_get_option( 'cancel_payment', 12 ) * HOUR_IN_SECONDS;
			wp_schedule_single_event( time() + $time, 'tp_event_cancel_payment_booking', array( $booking_id ) );
		}
	}

}

// cancel payment order
add_action( 'tp_event_cancel_payment_booking', 'tp_event_cancel_payment_booking' );
if ( !function_exists( 'tp_event_cancel_payment_booking' ) ) {

	function tp_event_cancel_payment_booking( $booking_id ) {
		$post_status = get_post_status( $booking_id );

		if ( $post_status === 'ea-pending' ) {
			wp_update_post( array(
				'ID'          => $booking_id,
				'post_status' => 'ea-cancelled'
			) );
		}
	}

}