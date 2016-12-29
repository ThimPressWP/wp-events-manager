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

	public $is_enable = false;

	/**
	 * payment title
	 * @var null
	 */
	public $_title = null;

	public function __construct() {
		$this->_title = __( 'Woocommerce', 'tp-event' );
		$this->title  = __( 'Woocommerce', 'tp-event' );
		parent::__construct();

	}

	/**
	 *
	 * @return boolean
	 */
	public function is_available() {
		return get_option( 'thimpress_events_woo_payment_enable' ) === 'yes';
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
				'id'   => 'paypal_settings'
			)
		) );
	}


}
