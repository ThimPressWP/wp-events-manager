<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Event_Woo_Payment_Gateway extends Event_Abstract_Payment_Gateway {

	/**
	 * id of payment
	 * @var null
	 */
	public $id = 'woocommerce';

	public $title = null;

	protected static $available = false;

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

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'WC_Install' ) ) {
			self::$available = true;
		} else {
			self::$available = false;
		}

		if ( self::$available ) {
			add_filter( 'tp_event_get_currency', array( $this, 'woocommerce_currency' ), 50 );
			add_filter( 'tp_event_currency_symbol', array( $this, 'woocommerce_currency_symbol' ), 50, 2 );
			add_filter( 'tp_event_format_price', array( $this, 'woocommerce_price_format' ), 50, 3 );
		}

	}


	/**
	 * Return woocommerce currency setting
	 *
	 * @param $currency
	 *
	 * @return string
	 */
	public function woocommerce_currency( $currency ) {
		return get_woocommerce_currency();
	}

	/**
	 * Return woocommerce currency symbol
	 *
	 * @param $symbol
	 * @param $currency
	 *
	 * @return string
	 */
	public function woocommerce_currency_symbol( $symbol, $currency ) {
		return get_woocommerce_currency_symbol( $currency );
	}

	/**
	 * woocommerce_price_format get price within currency format using woocommerce setting
	 *
	 * @param  price formated $price_format
	 * @param  (float) $price         price
	 * @param  (string) $with_currency currency setting tp-hotel-booking
	 *
	 * @return string price formated
	 */
	public function woocommerce_price_format( $price_format, $price, $with_currency ) {
		return wc_price( $price );
	}


	/**
	 *
	 * @return boolean
	 */
	public function is_available() {
		return ( self::$available && tp_event_get_option( 'woo_payment_enable' ) === 'yes' );
	}


	/**
	 * fields settings
	 * @return array
	 */
	public function admin_fields() {
		$prefix = 'tp_event_';
		return apply_filters( 'tp_event_woo_admin_fields', array(
			array(
				'type'  => 'section_start',
				'id'    => 'woo_settings',
				'title' => __( 'Woocommerce', 'tp-event' ),
				'desc'  => __( 'Settings for WooCommerce checkout process.', 'tp-event' )
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Enable', 'tp-event' ),
				'desc'    => __( 'This controlls enable payment method', 'tp-event' ),
				'id'      => $prefix . 'woo_payment_enable',
				'options' => array(
					'no'  => __( 'No', 'tp-event' ),
					'yes' => __( 'Yes', 'tp-event' )
				)
			),
			array(
				'type' => 'section_end',
				'id'   => 'woo_settings'
			)
		) );
	}


}
