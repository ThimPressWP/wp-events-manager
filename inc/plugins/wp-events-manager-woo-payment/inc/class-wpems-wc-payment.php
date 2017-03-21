<?php
/*
 * @Author : leehld
 * @Date   : 2/9/2017
 * @Last Modified by: leehld
 * @Last Modified time: 2/9/2017
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'WPEMS_Abstract_Payment_Gateway' ) )
	return;

class WPEMS_WC_Payment extends WPEMS_Abstract_Payment_Gateway {

	/**
	 * id of payment
	 * @var null
	 */
	public $id = 'woo_payment';
	// title
	public $title = null;
	// enable
	protected static $enable = false;
	// icon
	public $icon = null;

	/**
	 * WPEMS_WC_Payment constructor
	 */
	public function __construct() {
		$this->title = __( 'Woocommerce', 'wp-events-manager-woo' );
		$this->icon  = WPEMS_WOO_INC_URI . '/' . $this->id . '.png';

		parent::__construct();
	}

	/*
	 * Check gateway enable
	 */
	public function is_enable() {
		$prefix = 'thimpress_events_';
		if ( !get_option( $prefix . 'woo_payment_enable', true ) || get_option( $prefix . 'woo_payment_enable', true ) === 'yes' ) {
			self::$enable = true;
		}
		return self::$enable;
	}

	/*
	 * Check gateway available
	 */
	public function is_available() {
		return true;
	}

	/**
	 * fields settings
	 * @return array
	 */
	public function admin_fields() {
		$prefix = 'thimpress_events_';
		return apply_filters( 'tp_event_woo_payment_admin_fields', array(
			array(
				'type'  => 'section_start',
				'id'    => 'woo_payment_settings',
				'title' => __( 'Woocommerce Payment Settings', 'wp-events-manager-woo' ),
				'desc'  => esc_html__( 'Make a payment with WooCommerce payment methods', 'wp-events-manager-woo' )
			),
			array(
				'type'    => 'yes_no',
				'title'   => __( 'Enable', 'wp-events-manager-woo' ),
				'desc'    => __( 'If WooCommerce Payment is enabled you can not use other payment gateways', 'wp-events-manager-woo' ),
				'id'      => $prefix . 'woo_payment_enable',
				'default' => 'yes'
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Register event process', 'wp-events-manager-woo' ),
				'id'      => $prefix . 'woo_event_register_process',
				'options' => array(
					'cart'     => __( 'Add to cart', 'wp-events-manager-woo' ),
					'checkout' => __( 'Go to checkout', 'wp-events-manager-woo' ),
				),
				'default' => 'cart'

			),
			array(
				'type' => 'section_end',
				'id'   => 'woo_payment_settings'
			)
		) );
	}

	/**
	 * Woo checkout process
	 *
	 * @param bool $amount
	 */
	public function process( $event_id = false ) {
		return array(
			'status' => true,
			'url'    => $this->checkout_url(),
			'event'  => get_the_title( $event_id )
		);
	}

	/**
	 * Redirect to woocommerce checkout page
	 */
	public function checkout_url() {
		global $woocommerce;
		if ( !$woocommerce->cart )
			return '';

		$process = get_option( 'thimpress_events_woo_event_register_process', true );
		if ( $process == 'cart' || !$process ) {
			return '';
		} else {
			return $woocommerce->cart->get_checkout_url() ? $woocommerce->cart->get_checkout_url() : '';
		}

	}
}

return new WPEMS_WC_Payment();