<?php
/*
 * @Author : leehld
 * @Date   : 2/13/2017
 * @Last Modified by: leehld
 * @Last Modified time: 2/13/2017
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'WPEMS_Booking' ) )
	return;

class WPEMS_WC_Checkout extends WPEMS_Booking {

	function __construct( $id = null ) {
		parent::__construct( $id );

		/**
		 * woo add new order hook
		 */
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woo_add_order' ) );
	}

	/**
	 * woo_add_order WooCoommerce hook create new order
	 *
	 * @param  [type] $order_id [description]
	 *
	 * @return [type]           [description]
	 */
	public function woo_add_order( $order_id ) {

		$cart_contents = wc()->cart->cart_contents;

		$create = false;
		$args   = array();
		foreach ( $cart_contents as $cart_key => $cart_content ) {
			if ( get_post_type( $cart_content['product_id'] ) === 'tp_event' ) {
				$create = true;
				$args   = array(
					'event_id'   => $cart_content['product_id'],
					'qty'        => $cart_content['quantity'],
					'price'      => $cart_content['line_total'],
					'payment_id' => 'woo_payment'
				);
				break;
			}
		}

		if ( $create === true ) {
			$old_order = get_post_meta( $order_id, '_WPEMS_Event_order', true );
			if ( $old_order ) {
				wp_delete_post( $old_order, true );
			}
			if ( $booking = $this->create_booking( $args, 'woo_payment' ) ) {
				update_post_meta( $booking, '_tp_event_woo_order', $order_id );
				update_post_meta( $order_id, '_tp_event_event_order', $booking );
				return true;
			}
		}
	}
}

new WPEMS_WC_Checkout();
