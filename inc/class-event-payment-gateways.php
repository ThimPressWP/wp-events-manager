<?php

defined( 'ABSPATH' ) || exit();

class TP_Event_Payment_Gateways {

	/**
	 * gateways method
	 * @var type array
	 */
	public $gateways = array();
	public static $instance = null;

	public function __construct() {
		$this->init();
	}

	public function init() {
		$payment_gatways =
			apply_filters( 'event_auth_payment_gateways',
				array(
					'TP_Event_Payment_Gateway_Paypal',
					'TP_Event_Payment_Gateway_Woocommerce'
				)
			);

		foreach ( $payment_gatways as $gateway ) {
			$gateway                      = is_string( $gateway ) ? new $gateway : $gateway;
			$this->gateways[$gateway->id] = $gateway;
		}

		return $this->gateways;
	}

	/**
	 * payment gateways available
	 * @return type array
	 */
	public function get_payment_gateways() {
		return $this->gateways;
	}

	public function get_payment_gateways_available() {
		$gateways  = $this->get_payment_gateways();
		$available = array();

		foreach ( $gateways as $id => $gateway ) {
			if ( $gateway->is_available() ) {
				$available[$id] = $gateway;
			}
		}

		return $available;
	}

	public static function instance() {
		if ( !self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}

TP_Event_Payment_Gateways::instance();