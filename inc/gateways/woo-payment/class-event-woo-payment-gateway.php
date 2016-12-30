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
			add_filter( 'tp_event_get_currency', array( $this, 'woocommerce_currency' ), 10 );
		}

	}


	public function woocommerce_currency() {
		return get_woocommerce_currency();
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
