<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class TP_Event_Payment_Gateway_Woocommerce extends TP_Event_Abstract_Payment_Gateway {

	/**
	 * id of payment
	 * @var null
	 */
	public $id = 'woocommerce';

	public $title = null;

	protected static $available = false;

	protected static $enable = false;

	protected static $cart_url = null;

	/**
	 * payment title
	 * @var null
	 */
	public $_title = null;

	public function __construct() {
		$this->_title = __( 'Woocommerce', 'tp-event' );
		$this->title  = __( 'Woocommerce', 'tp-event' );
		parent::__construct();
		$this->load();
	}

	public function load() {
		if ( !function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		self::$available = ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) ? true : false;
	}

	/*
	 * Check gateway available
	 */
	public function is_available() {
		return ( self::$available );
	}

	/**
	 * Check gateway enable
	 */
	public function is_enable(){
		if(!$this->is_available()){
			self::$enable = false;
		} else {
				self::$enable = tp_event_get_option( 'woo_payment_enable' );
		}
		return self::$enable;
	}


	/**
	 * fields settings
	 * @return array
	 */
	public function admin_fields() {
		$prefix = 'thimpress_events_';
		return apply_filters( 'tp_event_woo_admin_fields', array(
			array(
				'type'  => 'section_start',
				'id'    => 'woo_settings',
				'title' => __( 'Woocommerce', 'tp-event' ),
				'desc'  => __( 'Settings for WooCommerce checkout process.', 'tp-event' )
			),
			array(
				'type'    => 'checkbox',
				'title'   => __( 'Enable', 'tp-event' ),
				'desc'    => __( 'This controls enable payment method', 'tp-event' ),
				'id'      => $prefix . 'woo_payment_enable',
				'default' => false
			),
			array(
				'type' => 'section_end',
				'id'   => 'woo_settings'
			)
		) );
	}

	/**
	 * Checkout url with Woocommerce
	 *
	 * checkout url
	 * @return url string
	 */
	public function checkout_url( $booking_id = false ) {
		if ( !$booking_id ) {
			wp_send_json( array(
				'status'  => false,
				'message' => __( 'Booking ID is not exists!', 'tp-event' )
			) );
			die();
		}

		return $this::$cart_url;
	}

	/**
	 * Checkout process via Woocommerce gateway
	 *
	 * @param bool $amount
	 *
	 * @return array
	 */
	public function process( $amount = false ) {
		if ( !$this->is_available() ) {
			return array(
				'status'  => false,
				'message' => __( 'Please check Woocommerce checkout process settings again.', 'tp-event' )
			);
		}
		return array(
			'status' => true,
			'url'    => $this->checkout_url( $amount )
		);
	}


}
