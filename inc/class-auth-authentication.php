<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Auth_Authentication {

    private static $login_url = null;
    private static $register_url = null;
    private static $forgot_url = null;
    private static $account_url = null;
    private static $reset_url = null;
    private static $session = null;

    public static function init() {
        /**
         * Process Register
         * Login
         * Lost Password
         * Reset Password
         */
        add_action( 'init', array( __CLASS__, 'auth_init' ), 10 );
        add_action( 'init', array( __CLASS__, 'process_register' ), 50 );
        add_action( 'init', array( __CLASS__, 'process_login' ), 50 );
        add_action( 'init', array( __CLASS__, 'process_lost_password' ), 50 );
        add_action( 'init', array( __CLASS__, 'process_reset_password' ), 50 );

        // process
        add_action( 'wp_logout', array( __CLASS__, 'wp_logout' ) );
        add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
    }

    public static function auth_init() {

        self::$login_url = tp_event_login_url();

        self::$register_url = tp_event_register_url();

        self::$forgot_url = tp_event_forgot_password_url();

        self::$account_url = tp_event_account_url();

        self::$reset_url = tp_event_reset_password_url();
    }

    // redirect logout
    public static function wp_logout() {
        tp_event_add_notice( 'success', sprintf( '%s', __( 'You have been sign out!', 'tp-event' ) ) );
        wp_safe_redirect( self::$login_url );
        exit();
    }

    // shortcodes
    public static function event_auth( $atts, $content = null ) {
        extract( wp_parse_args( $atts, array(
            'page' => 'login'
        ) ) );

        $page = strtolower( $page );

        switch ( $page ) {
            case 'login':
                $page = 'login';
                break;

            case 'my_account':
                $page = 'my_account';
                break;

            case 'my-account':
                $page = 'my_account';
                break;

            case 'register':
                $page = 'register';
                break;

            case 'forgot_password':
                $page = 'forgot_password';
                break;

            case 'forgot-password':
                $page = 'forgot_password';
                break;

            default:
                $page = 'login';
                break;
        }

        return do_shortcode( '[event_auth_' . $page . ']' );
    }

    // shortcode login form
    public static function event_auth_login( $atts = array(), $content = null ) {
        if ( !( $login_page_id = tp_event_get_page_id( 'login' ) ) ) {
            return;
        }

        if ( is_user_logged_in() ) {
            return;
        }
		tp_event_get_template( 'auths/form-login.php' );
    }

    // shortcode register form
    public static function event_auth_register( $atts = array(), $content = null ) {
        if ( !( $register_page_id = tp_event_get_page_id( 'register' ) ) ) {
            return;
        }

        if ( !get_option( 'users_can_register' ) ) {
            // register not allowed
			tp_event_get_template( 'auths/form-register-not-allow.php' );
        } elseif ( !empty( $_REQUEST['registered'] ) ) {
            $email = sanitize_email( $_REQUEST['registered'] );
            $user = get_user_by( 'email', $email );
            if ( $user && $user->ID ) {
                wp_new_user_notification( $user->ID );
                // register completed
				tp_event_get_template( 'auths/register-completed.php' );
            } else {
                // error
                tp_event_get_template( 'auths/error.php' );
            }
        } elseif ( !is_user_logged_in() ) {
            // show register form
            tp_event_get_template( 'auths/form-register.php' );
        }
    }

    // shortcode lostpassword
    public static function forgot_pass( $atts = array(), $content = null ) {

        if ( !tp_event_get_page_id( 'forgot_pass' ) ) {
            return;
        }

        $checkemail = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] === 'confirm' ? true : false;

        if ( $checkemail ) {
            tp_event_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'tp-event' ) );
        } else {
            tp_event_get_template( 'auths/form-forgot-password.php' );
        }
    }

    // shortcode lostpassword
    public static function reset_password( $atts = array(), $content = null ) {
        if ( !tp_event_get_page_id( 'reset_password' ) ) {
            return;
        }
        $atts = wp_parse_args( $atts, array(
            'key' => isset( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : '',
            'login' => isset( $_REQUEST['login'] ) ? sanitize_text_field( $_REQUEST['login'] ) : ''
                ) );

        $atts = wp_parse_args( $atts, array(
            'user_login' => '',
            'redirect_to' => '',
            'checkemail' => isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] === 'confirm' ? true : false
                ) );

        if ( $atts['checkemail'] ) {
            tp_event_add_notice( 'success', __( 'Check your email for a link to reset your password.', 'tp-event' ) );
        }

        tp_event_get_template( 'auths/form-reset-password.php', array( 'atts' => $atts ) );
    }

    // shortcode account
    public static function my_account( $atts = array(), $content = null ) {
        $user = wp_get_current_user();
        $args = array(
            'post_type' => 'event_auth_book',
            'posts_per_page' => -1,
            'order' => 'DESC',
            'posts_per_page'    => tp_event_get_option( 'payment_litmit_showup', 10 ),
            'offset'    => ( ( get_query_var('paged') - 1 ) > 0 ? ( get_query_var('paged') - 1 ) : 0 ) * tp_event_get_option( 'payment_litmit_showup', 10 ),
            'meta_query' => array(
                array(
                    'key' => 'ea_booking_user_id',
                    'value' => $user->ID
                ),
            ),
        );
        $atts = new WP_Query( $args );
        tp_event_get_template( 'auths/my-account.php', array( 'query' => $atts ) );
        wp_reset_postdata();
    }

    public static function template_redirect() {
        if ( !is_page() ) {
            return;
        }

        global $post;
        if ( is_user_logged_in() && in_array( $post->ID, array( tp_event_get_page_id( 'login' ), tp_event_get_page_id( 'register' ) ) ) ) {
            wp_safe_redirect( self::$account_url );
            exit();
        }
    }

    /**
     * Process Register
     */
    public static function process_register() {
        if ( empty( $_POST['auth-nonce'] ) || !wp_verify_nonce( $_POST['auth-nonce'], 'auth-reigter-nonce' ) ) {
            return;
        }
        $username = !empty( $_POST['user_login'] ) ? $_POST['user_login'] : '';
        $email = !empty( $_POST['user_email'] ) ? $_POST['user_email'] : '';
        $password = !empty( $_POST['user_pass'] ) ? $_POST['user_pass'] : '';
        $password1 = !empty( $_POST['confirm_password'] ) ? $_POST['confirm_password'] : '';

        $user_id = tp_event_create_new_user( apply_filters( 'event_auth_user_process_register_data', array(
            'username' => $username, 'email' => $email, 'password' => $password, 'confirm_password' => $password1
                ) ) );

        if ( is_wp_error( $user_id ) ) {
            $fields = array();
            foreach ( $user_id->errors as $code => $message ) {
                if ( !$message[0] )
                    continue;
                if ( tp_event_is_ajax() ) {
                    $fields[$code] = $message[0];
                } else {
                    tp_event_add_notice( 'error', $message[0] );
                }
            }
            if ( tp_event_is_ajax() ) {
                wp_send_json( array( 'status' => false, 'fields' => $fields ) );
            }
        } else {

            $url = wp_get_referer();
            if ( !$url ) {
                $url = self::$register_url;
            }

            // not enable option 'register_notify' login user now
            $send_notify = tp_event_get_option( 'register_notify', true );
            if ( !$send_notify ) {
                wp_set_auth_cookie( $user_id, true, is_ssl() );
            } else {
                $url = add_query_arg( 'registered', $email, self::$register_url );
            }

            if ( tp_event_is_ajax() ) {
                wp_send_json( array( 'status' => true, 'redirect' => $url ) );
            } else {
                wp_safe_redirect( $url );
                exit();
            }
        }
    }

    /**
     * Process Login
     */
    public static function process_login() {

        $nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
        $nonce_value = isset( $_POST['auth-nonce'] ) ? $_POST['auth-nonce'] : $nonce_value;

        if ( !wp_verify_nonce( $nonce_value, 'auth-login-nonce' ) ) {
            return;
        }
        $redirect = self::$account_url;
        if ( !empty( $_POST['redirect_to'] ) && $_POST['redirect_to'] !== '/wp-admin/admin-ajax.php' ) {
            $redirect = $_POST['redirect_to'];
        } elseif ( wp_get_referer() ) {
            $redirect = wp_get_referer();
        }

        $redirect = strpos( $redirect, '/wp-admin/admin-ajax.php' ) ? self::$account_url : $redirect;

        try {

            $creds = array();
            $username = !empty( $_POST['user_login'] ) ? trim( $_POST['user_login'] ) : '';
            $password = !empty( $_POST['user_pass'] ) ? trim( $_POST['user_pass'] ) : '';

            $validation_error = new WP_Error();
            $validation_error = apply_filters( 'event_auth_process_login_errors', $validation_error, $username, $password );

            if ( $validation_error->get_error_code() ) {
                tp_event_add_notice( 'error', '<strong>' . __( 'ERROR', 'tp-event' ) . ':</strong> ' . $validation_error->get_error_message() );
            }

            if ( empty( $username ) ) {
                tp_event_add_notice( 'error', '<strong>' . __( 'ERROR', 'tp-event' ) . ':</strong> ' . __( 'Username is required.', 'tp-event' ) );
            }

            if ( empty( $_POST['user_pass'] ) ) {
                tp_event_add_notice( 'error', '<strong>' . __( 'ERROR', 'tp-event' ) . ':</strong> ' . __( 'Password is required.', 'tp-event' ) );
            }

            if ( is_email( $username ) && apply_filters( 'event_auth_get_username_from_email', true ) ) {
                $user = get_user_by( 'email', $username );

                if ( isset( $user->user_login ) ) {
                    $creds['user_login'] = $user->user_login;
                } else {
                    tp_event_add_notice( 'error', '<strong>' . __( 'ERROR', 'tp-event' ) . ':</strong> ' . __( 'A user could not be found with this email address.', 'tp-event' ) );
                }
            } else {
                $creds['user_login'] = $username;
            }

            $creds['user_password'] = $password;
            $creds['remember'] = isset( $_POST['rememberme'] );
            $secure_cookie = is_ssl() ? true : false;

            if ( !tp_event_has_notice( 'error' ) ) {
                $user = wp_signon( apply_filters( 'event_auth_login_credentials', $creds ), $secure_cookie );

                if ( is_wp_error( $user ) ) {
                    $message = $user->get_error_message();
                    $message = str_replace( wp_lostpassword_url(), self::$forgot_url, $message );
                    $message = str_replace( '<strong>' . esc_html( $creds['user_login'] ) . '</strong>', '<strong>' . esc_html( $username ) . '</strong>', $message );
                    tp_event_add_notice( 'error', $message );

                    // break
                    throw new Exception;
                } else {
                    tp_event_add_notice( 'success', __( 'You have logged in', 'tp-event' ) );

                    if ( !defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
                        wp_redirect( apply_filters( 'event_auth_login_redirect', $redirect, $user ) );
                        exit;
                    } else {
                        $response = array();
                        $response['status'] = true;
                        $response['redirect'] = apply_filters( 'event_auth_ajax_login_redirect', $redirect );
                        ob_start();
                        tp_event_print_notices();
                        $response['notices'] = ob_get_clean();
                        wp_send_json( $response );
                    }
                }
            }
        } catch ( Exception $ex ) {
            if ( $ex ) {
                tp_event_add_notice( 'error', $ex->getMessage() );
            }
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $response = array();
            $response['status'] = false;
            $response['redirect'] = apply_filters( 'event_auth_ajax_login_redirect', $redirect );
            ob_start();
            tp_event_print_notices();
            $response['notices'] = ob_get_clean();
            wp_send_json( $response );
        }
    }

    /**
     * Process Lost Password
     */
    public static function process_lost_password() {
        
    }

    /**
     * Process Reset Password
     */
    public static function process_reset_password() {
        
    }

}

Auth_Authentication::init();
