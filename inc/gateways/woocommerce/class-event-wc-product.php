<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'WC_Product' ) )
	return;

class TP_Event_WC_Product extends WC_Product {

	public $data = null;
	public $total;

	function __construct( $product, $args = null ) {
		parent::__construct( $product, $args );
	}

	// get event price
	function get_price() {
		$event = Auth_Event::instance( $this->post, $this->data );
		return $event->get_price();
	}

	// event is purchasable
	function is_purchasable() {
		return true;
	}

}