<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class Event_Paypal_Payment_Gateway extends Event_Abstract_Payment_Gateway {

    /**
     * id of payment
     * @var null
     */
    public $id = 'paypal';
    public $title = null;
    // email
    protected $paypal_email = null;
    // url
    protected $paypal_url = null;
    // payment url
    protected $paypal_payment_url = null;

    /**
     * payment title
     * @var null
     */
    public $_title = null;

    public function __construct() {
        $this->_title = __( 'Paypal', 'tp-event' );
        $this->title = __( 'PayPal', 'tp-event' );
        parent::__construct();
        $this->paypal_url = 'https://www.sandbox.paypal.com/';
        $this->paypal_payment_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        $this->paypal_email = tp_event_get_option( 'paypal_sanbox_email' );

        // production environment
        if ( tp_event_get_option( 'checkout_environment', 'test' ) === 'production' ) {
            $this->paypal_url = 'https://www.paypal.com/';
            $this->paypal_payment_url = 'https://www.paypal.com/cgi-bin/webscr';
            $this->paypal_email = tp_event_get_option( 'paypal_email' );
        }
        // // init process
        add_action( 'init', array( $this, 'payment_validation' ), 99 );
    }
    
    /**
     * 
     * @return boolean
     */
    public function is_available() {
        return ! empty( $this->paypal_email ) && get_option( 'thimpress_events_paypal_enable' ) === 'yes';
    }

    // callback
    public function payment_validation() {
        if ( isset( $_GET['event-auth-paypal-payment'] ) && $_GET['event-auth-paypal-payment'] ) {
            if ( !isset( $_GET['event-auth-paypal-nonce'] ) || !wp_verify_nonce( $_GET['event-auth-paypal-nonce'], 'event-auth-paypal-nonce' ) ) {
                return;
            }

            if ( sanitize_text_field( $_GET['event-auth-paypal-payment'] ) === 'completed' ) {
                tp_event_add_notice( 'success', sprintf( __( 'Payment is completed. We will send you email when payment status is completed', 'tp-event' ) ) );
            } else if ( sanitize_text_field( $_GET['event-auth-paypal-payment'] ) === 'cancel' ) {
                tp_event_add_notice( 'success', sprintf( __( 'Booking is cancel.', 'tp-event' ) ) );
            }
            // redirect
            $url = add_query_arg( array( 'event-auth-paypal-nonce' => $_GET['event-auth-paypal-nonce'] ), tp_event_account_url() );
            wp_redirect( $url );
            exit();
        }

        // validate payment notify_url, update status
        if ( !empty( $_POST ) && isset( $_POST['txn_type'] ) && $_POST['txn_type'] === 'web_accept' ) {
            if ( !isset( $_POST['payment_status'] ) )
                return;

            if ( empty( $_POST['custom'] ) )
                return;

            // transaction object
            $transaction_subject = stripcslashes( $_POST['custom'] );
            $transaction_subject = json_decode( $transaction_subject );

            $booking_id = false;
            if ( !isset( $transaction_subject->booking_id ) || !$booking_id = $transaction_subject->booking_id )
                return;

            $book = Event_Booking::instance( $booking_id );

            // santitize
            $pay_verify = array_merge( array( 'cmd' => '_notify-validate' ), array_map( 'stripcslashes', $_POST ) );

            $paypal_api_url = isset( $_POST['test_ipn'] ) && $_POST['test_ipn'] == 1 ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

            // $response = wp_remote_post( $paypal_api_url, array( 'body' => $pay_verify ) );
            $params = array(
                'body' => $pay_verify,
                'timeout' => 60,
                'httpversion' => '1.1',
                'compress' => false,
                'decompress' => false,
                'user-agent' => 'Event'
            );
            $response = wp_safe_remote_post( $paypal_api_url, $params );

            if ( !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                $body = wp_remote_retrieve_body( $response );

                if ( strtolower( $body ) === 'verified' ) {
                    // payment status
                    $payment_status = strtolower( $_POST['payment_status'] );

                    if ( in_array( $payment_status, array( 'pending', 'completed' ) ) ) {
                        $status = 'ea-completed';
                        $book->update_status( $status );
                    }
                }
            }
        }
    }

    /**
     * fields settings
     * @return array
     */
    public function admin_fields() {
        $prefix = 'thimpress_events_';
        return apply_filters( 'event_auth_paypal_admin_fields', array(
            array(
                'type' => 'section_start',
                'id' => 'paypal_settings',
                'title' => __( 'Paypal Settings', 'tp-event' ),
                'desc' => __( 'General options for system.', 'tp-event' )
            ),
            array(
                'type' => 'select',
                'title' => __( 'Enable', 'tp-event' ),
                'desc' => __( 'This controlls enable payment method', 'tp-event' ),
                'id' => $prefix . 'paypal_enable',
                'options' => array(
                    'no' => __( 'No', 'tp-event' ),
                    'yes' => __( 'Yes', 'tp-event' )
                )
            ),
            array(
                'type' => 'text',
                'title' => __( 'Paypal sandbox email', 'tp-event' ),
                'desc' => __( 'Paypal Business Email', 'tp-event' ),
                'id' => $prefix . 'paypal_sanbox_email',
                'default'   => ''
            ),
            array(
                'type' => 'text',
                'title' => __( 'Paypal email', 'tp-event' ),
                'desc' => __( 'Production environment', 'tp-event' ),
                'id' => $prefix . 'paypal_email',
                'default'   => ''
            ),
            array(
                'type' => 'section_end',
                'id' => 'paypal_settings'
            )
        ) );
    }

    /**
     * get_item_name
     * @return string
     */
    public function get_item_name( $booking_id = null ) {
        if ( !$booking_id )
            return;

        // book
        $book = Event_Booking::instance( $booking_id );
        $description = sprintf( '%s(%s)', $book->post->post_title, tp_event_format_price( $book->price, $book->currency ) );

        return $description;
    }

    /**
     * checkout url
     * @return url string
     */
    public function checkout_url( $booking_id = false ) {
        if ( !$booking_id ) {
            wp_send_json( array(
                'status' => false,
                'message' => __( 'Booking ID is not exists!', 'tp-event' )
            ) );
            die();
        }
        // book
        $book = tp_event_get_booking( $booking_id );

        // create nonce
        $nonce = wp_create_nonce( 'event-auth-paypal-nonce' );

        $user = get_userdata( $book->user_id );
        $email = $user->user_email;

        // query post
        $query = array(
            'cmd' => '_xclick',
            'amount' => (float) $book->price,
            'quantity' => '1',
            'business' => $this->paypal_email, // business email paypal
            'item_name' => $this->get_item_name( $booking_id ),
            'currency_code' => tp_event_get_currency(),
            'notify_url' => home_url(),
            'no_note' => '1',
            'shipping' => '0',
            'email' => $email,
            'rm' => '2',
            'no_shipping' => '1',
            'return' => add_query_arg( array( 'event-auth-paypal-payment' => 'completed', 'event-auth-paypal-nonce' => $nonce ), tp_event_account_url() ),
            'cancel_return' => add_query_arg( array( 'event-auth-paypal-payment' => 'cancel', 'event-auth-paypal-nonce' => $nonce ), tp_event_account_url() ),
            'custom' => json_encode( array( 'booking_id' => $booking_id, 'user_id' => $book->user_id ) )
        );

        // allow hook paypal param
        $query = apply_filters( 'event_auth_payment_paypal_params', $query );

        return $this->paypal_payment_url . '?' . http_build_query( $query );
    }

    public function process( $amount = false ) {
        if ( !$this->is_available() ) {
            return array(
                'status' => false,
                'message' => __( 'Email Business PayPal is invalid. Please contact administrator to setup PayPal email.', 'tp-event' )
            );
        }
        return array(
            'status' => true,
            'url' => $this->checkout_url( $amount )
        );
    }

}
