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

if ( !class_exists( 'TP_Event_Settings' ) )
	return;

class TP_Event_WC_Settings extends TP_Event_Settings {

	/**
	 * TP_Event_WC_Settings constructor
	 *
	 */
	public function __construct( $prefix = null ) {
		add_filter( 'wpems_payment_gateways', array( $this, 'add_wc_checkout_section' ) );

		parent::__construct();
	}

	public function add_wc_checkout_section( $sections ) {
		$sections['woo_payment'] = new TP_Event_WC_Payment();

		return $sections;
	}


}

return new TP_Event_WC_Settings();
