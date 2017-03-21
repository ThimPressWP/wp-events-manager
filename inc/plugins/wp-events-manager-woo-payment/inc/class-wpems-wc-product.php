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

if ( !class_exists( 'WC_Product_Simple' ) )
	return;

class  WPEMS_WC_Product extends WC_Product_Simple {

	/*
	 * Event product data
	 */
	public $data = null;

	/**
	 * WPEMS_WC_Product constructor
	 *
	 * @param mixed $product
	 */
	public function __construct( $product ) {
		parent::__construct( $product );
	}

	/**
	 * Get event price
	 *
	 * @return mixed
	 */
	public function get_price() {
		$event = WPEMS_Event::instance( $this->post, $this->data );
		return $event->get_price();
	}

	/**
	 * Is purchasable event
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return true;
	}

	/**
	 * Set number event product
	 *
	 * @return mixed
	 */
	public function get_stock_quantity() {
		$event = WPEMS_Event::instance( $this->post );
		return $event->get_slot_available();
	}


	/**
	 * Check only allow one of this event to be bought in a single order
	 *
	 * @return bool
	 */
	public function is_sold_individually() {
		if ( get_option( 'thimpress_events_email_register_times', true ) == 'once' && !$this->get_price() ) {
			return true;
		} else {
			return false;
		}
	}


}