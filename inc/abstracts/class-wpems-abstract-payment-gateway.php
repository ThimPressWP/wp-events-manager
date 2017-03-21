<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

abstract class WPEMS_Abstract_Payment_Gateway {

	/**
	 * id of payment
	 * @var null
	 */
	protected $id = null;

	/**
	 * payment title
	 * @var null
	 */
	protected $title = null;
	// is enable
	public $is_enable = false;
	// icon
	public $icon = null;

	public function __construct() {
		add_action( 'event_admin_setting_page_checkout_section', array( $this, 'add_sections' ) );
		add_action( 'event_auth_payment_gateways_select', array( $this, 'event_auth_gateways' ) );
	}

	public function add_sections( $sections ) {
		$sections[$this->id] = $this->title;
		return $sections;
	}

	public function get_title() {
		return apply_filters( 'event_payment_title', $this->title, $this->id );
	}

	/**
	 * payment process
	 * @return null
	 */
	protected function process( $event_id = false ) {

	}

	/**
	 * refund action
	 * @return null
	 */
	protected function refund() {

	}

	/**
	 * payment send email
	 * @return null
	 */
	public function send_email() {

	}

	/**
	 * admin setting fields
	 * @return array
	 */
	public function admin_fields() {
		return array();
	}

	/**
	 * enable
	 * @return boolean
	 */
	public function is_enable() {
		if ( wpems_get_option( $this->id . '_enable', 'yes' ) === 'yes' ) {
			return $this->is_enable = true;
		}
		return $this->is_enable = false;
	}

	/**
	 * event_auth_gateways fontend display
	 * @return html
	 */
	public function event_auth_gateways() {
		$html = array();

		$html[] = '<input id="payment_method_' . esc_attr( $this->id ) . '" type="radio" name="payment_method" value="' . esc_attr( $this->id ) . '"/>';
		$html[] = '<label for="payment_method_' . esc_attr( $this->id ) . '"><img width="115" height="50" src="' . esc_attr( $this->_icon ) . '" /></label>';

		echo implode( '', $html );
	}

	public function is_available() {
		return true;
	}

	/**
	 * add notice message completed when payment completed
	 * @return null
	 */
	public function completed_process_message() {
		if ( !tp_event_has_notice( 'success' ) ) {
			tp_event_has_notice( 'success', __( 'Payment completed. We will send you email when payment method verify.', 'wp-events-manager' ) );
		}
	}

}


