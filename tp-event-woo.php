<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !defined( 'TP_Event_Woo_File' ) ) {
	define( 'TP_Event_Woo_File', __FILE__ );
	require_once dirname(__FILE__) .'includes/event-woo-constants.php';
}

/**
 * Class TP_Event_Woo_Payment
 */
class TP_Event_Woo_Payment {

	/**
	 * Hold the instance of TP_Event_Woo_Payment
	 *
	 * @var null
	 */
	protected static $_instance = null;
	protected static $_wc_loaded = false;

	/**
	 * Constructor
	 */
	function __construct() {

	}

}