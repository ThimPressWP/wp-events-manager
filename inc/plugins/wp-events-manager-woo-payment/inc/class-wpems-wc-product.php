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
global $woocommerce;

if ( $woocommerce && version_compare( $woocommerce->version, '3.0.0', '<' ) ) {
	require_once 'class-wpems-wc-2x-product.php';
	return;
} else {
	class  WPEMS_WC_Product extends WC_Product_Simple {

		/*
		 * Event product data
		 */

		/**
		 * WPEMS_WC_Product constructor
		 *
		 * @param mixed $product
		 */
		public function __construct( $product = 0 ) {
			// Should not call constructor of parent
			//parent::__construct( $product );
			if ( is_numeric( $product ) && $product > 0 ) {
				$this->set_id( $product );
			} elseif ( $product instanceof self ) {
				$this->set_id( absint( $product->get_id() ) );
			} elseif ( !empty( $product->ID ) ) {
				$this->set_id( absint( $product->ID ) );
			}
		}

		/**
		 * Get event price
		 *
		 * @return mixed
		 */
		public function get_price( $context = 'view' ) {
			$event = WPEMS_Event::instance( $this->get_id() );
			return $event->get_price();
		}

		/**
		 * Is purchasable event
		 *
		 * @return bool
		 */
		public function is_purchasable( $content = 'view' ) {
			return true;
		}

		/**
		 * Set number event product
		 *
		 * @return mixed
		 */
		public function get_stock_quantity( $context = 'view' ) {
			$event = WPEMS_Event::instance( get_post( $this->get_id() ) );
			return $event->get_slot_available();
		}

		public function get_stock_status( $context = 'view' ) {
			return $this->get_stock_quantity( $context ) > 0 ? 'instock' : '';
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

		/**
		 * @param string $context
		 *
		 * @return bool
		 */
		public function exists( $context = 'view' ) {
			return $this->get_id() && ( get_post_type( $this->get_id() ) == 'tp_event' ) && ( !in_array( get_post_status( $this->get_id() ), array( 'draft', 'auto-draft' ) ) );
		}

		public function is_virtual() {
			return true;
		}

		/**
		 * @param string $context
		 *
		 * @return string
		 */
		public function get_name( $context = 'view' ) {
			return get_the_title( $this->get_id() );
		}
	}
}