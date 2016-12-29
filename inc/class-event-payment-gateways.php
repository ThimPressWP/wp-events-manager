<?php

defined( 'ABSPATH' ) || exit();

class Event_Payment_Gateways {

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
					'Event_Paypal_Payment_Gateway',
					'Event_Woo_Payment_Gateway'
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
		$payment_gateways_available = array();
		if ( $this->gateways ) {
			foreach ( $this->gateways as $id => $gateway ) {
				if ( $gateway->is_available() ) {
					$payment_gateways_available[$id] = $gateway;
				}
			}
		}
		return $payment_gateways_available;
	}

	public static function instance() {
		if ( !self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
