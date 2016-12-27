<?php

if ( !function_exists( 'event_auth_get_notice' ) ) {

	function event_auth_get_notice( $type = null ) {
		if ( $type ) {
			$notices = TP_Event()->_session->get( 'notices', array() );
			return isset( $notices[$type] ) ? $notices[$type] : array();
		}
	}

}

if ( !function_exists( 'event_auth_has_notice' ) ) {

	function event_auth_has_notice( $type = null ) {
		if ( $type ) {
			$notices = TP_Event()->_session->get( 'notices', array() );
			return isset( $notices[$type] );
		}
	}

}

if ( !function_exists( 'event_auth_print_notices' ) ) {

	function event_auth_print_notices() {
		if ( $notices = TP_Event()->_session->get( 'notices', array() ) ) {
			ob_start();
			tp_event_get_template( 'messages.php', array( 'messages' => $notices ) );
			$html = ob_get_clean();
			echo $html;
			TP_Event()->_session->set( 'notices', array() );
		}

	}

}

function event_auth_print_notice( $type = 'success', $message ) {
	if ( 'success' === $type ) {
		$message = apply_filters( 'event_auth_add_message', $message );
	}

	tp_event_get_template( "notices/{$type}.php", array(
		'messages' => array( apply_filters( 'event_auth_add_message_' . $type, $message ) )
	) );
}

function event_auth_get_currency() {
	return apply_filters( 'event_auth_get_currency', tp_event_get_option( 'currency', 'USD' ) );
}

/**
 * Get the list of common currencies
 *
 * @return mixed
 */
function event_auth_currencies() {
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

	return apply_filters( 'event_auth_currencies', $currencies );
}

function event_auth_get_currency_symbol( $currency = '' ) {
	if ( !$currency ) {
		$currency = event_auth_get_currency();
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

	return apply_filters( 'event_auth_currency_symbol', $currency_symbol, $currency );
}

function event_auth_format_price( $price, $with_currency = true ) {
	$position                  = tp_event_get_option( 'currency_position', 'left_space' );
	$price_thousands_separator = tp_event_get_option( 'currency_thousand', '.' );
	$price_decimals_separator  = tp_event_get_option( 'currency_separator', ',' );
	$price_number_of_decimal   = tp_event_get_option( 'currency_num_decimal', 2 );
	if ( !is_numeric( $price ) ) {
		$price = 0;
	}

	$price  = apply_filters( 'event_auth_price_switcher', $price );
	$before = $after = '';
	if ( $with_currency ) {
		if ( gettype( $with_currency ) != 'string' ) {
			$currency = event_auth_get_currency_symbol();
		} else {
			$currency = event_auth_get_currency_symbol( $with_currency );
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

	return apply_filters( 'event_auth_price_format', $price_format, $price, $with_currency );
}

// list payments gateway
function event_auth_payments() {
	return TP_Event()->payment_gateways()->get_payment_gateways();
}

// list payments gateway
function event_auth_get_payment_title( $payment_id = null ) {
	$payments = event_auth_payments();
	return isset( $payments[$payment_id] ) ? $payments[$payment_id]->title : '';
}

// format ID
function event_auth_format_ID( $id = null ) {
	return '#' . $id;
}

// booking status title
function event_auth_booking_status( $id = null ) {
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

function event_auth_get_payment_status() {
	return apply_filters( 'event_auth_get_payment_status', array(
		'ea-cancelled'  => sprintf( __( '<span class="event_booking_status cancelled">%s</span>', 'tp-event' ), __( 'Cancelled', 'tp-event' ) ),
		'ea-pending'    => sprintf( __( '<span class="event_booking_status pending">%s</span>', 'tp-event' ), __( 'Pending', 'tp-event' ) ),
		'ea-processing' => sprintf( __( '<span class="event_booking_status processing">%s</span>', 'tp-event' ), __( 'Processing', 'tp-event' ) ),
		'ea-completed'  => sprintf( __( '<span class="event_booking_status completed">%s</span>', 'tp-event' ), __( 'Completed', 'tp-event' ) ),
	) );
}

if ( !function_exists( 'event_auth_is_ajax' ) ) {
	/**
	 * is processing ajax request
	 * @return type boolean
	 */
	function event_auth_is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

if ( !function_exists( 'event_auth_create_new_user' ) ) {

	/**
	 * create new user
	 *
	 * @param type $username
	 * @param type $email
	 * @param type $password
	 *
	 * @return WP_Error or $user_id created
	 */
	function event_auth_create_new_user( $cred = array() ) {
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

		$errors = apply_filters( 'event_auth_register_errors', $errors, $username, $email, $password );
		if ( $errors->get_error_code() ) {
			return $errors;
		}

		$userdata = apply_filters( 'event_auth_create_new_user_data', array(
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

		do_action( 'event_auth_create_new_user', $user_id, $userdata );

		return $user_id;
	}

}

if ( !function_exists( 'event_auth_get_booking' ) ) {
	/**
	 * Get Booking
	 *
	 * @param type $booking_id
	 *
	 * @return Auth_Booking
	 */
	function event_auth_get_booking( $booking_id ) {
		return Auth_Booking::instance( $booking_id );
	}
}