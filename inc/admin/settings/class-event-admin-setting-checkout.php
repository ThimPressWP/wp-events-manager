<?php

if ( !defined( 'ABSPATH' ) ) {
	exit();
}

class TP_Event_Admin_Setting_Checkout extends TP_Event_Abstract_Setting {

	/**
	 * ID
	 * @var type mixed
	 */
	public $id = null;

	/**
	 * Title
	 * @var type string
	 */
	public $label = null;

	public function __construct() {
		$this->id    = 'event_checkout';
		$this->label = __( 'Checkout', 'tp-event' );
		add_filter( 'event_admin_settings_tabs_array', array( $this, 'add_setting_tab' ) );
		add_action( 'event_admin_setting_sections_' . $this->id, array( $this, 'output_section' ) );
		add_action( 'event_admin_setting_update_' . $this->id, array( $this, 'save' ) );
		add_action( 'event_admin_setting_' . $this->id, array( $this, 'output' ) );
	}

	/**
	 * Get options setting page
	 * @return type array
	 */
	public function get_settings() {
		$prefix = 'thimpress_events_';
		return apply_filters( 'event_admin_setting_page_' . $this->id, array(
			array(
				'type'  => 'section_start',
				'id'    => 'general_settings',
				'title' => __( 'Checkout Process', 'tp-event' ),
				'desc'  => __( 'General options for system.', 'tp-event' )
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Booking times free/email', 'tp-event' ),
				'desc'    => __( 'This controls how many time booking free event of an email', 'tp-event' ),
				'id'      => $prefix . 'email_register_times',
				'options' => array(
					'once' => __( 'Once', 'tp-event' ),
					'many' => __( 'Many', 'tp-event' )
				),
				'default' => 'many'
			),
			array(
				'type'        => 'number',
				'title'       => __( 'Cancel payment status', 'tp-event' ),
				'desc'        => __( 'How long cancel a payment (hour)', 'tp-event' ),
				'atts'        => array(
					'min'  => 0,
					'step' => 'any'
				),
				'id'          => $prefix . 'cancel_payment',
				'default'     => 12,
				'placeholder' => 12
			),
			array(
				'type' => 'section_end',
				'id'   => 'general_settings'
			)
		) );
	}

	/**
	 * Add Sections
	 */
	public function get_sections() {
		$sections['']     = __( 'Checkout General', 'tp-event' );
		$payment_gateways = tp_event_payment_gateways();
		if ( $payment_gateways ) {
			foreach ( $payment_gateways as $id => $gateway ) {
				$sections[$id] = $gateway->title;
			}
		}
		return $sections;
	}

	public function output( $tab ) {
		global $current_section;
		if ( $current_section ) {
			$gateways = tp_event_payment_gateways();
			foreach ( $gateways as $gateway ) {
				if ( $current_section === $gateway->id ) {
					$fields = $gateway->admin_fields();
					TP_Event_Admin_Settings::render_fields( $fields );
					break;
				}
			}
		} else {
			parent::output( $tab );
		}
	}

	public function save() {
		global $current_section;
		if ( $current_section ) {
			$gateways = tp_event_payment_gateways();
			foreach ( $gateways as $gateway ) {
				if ( $current_section === $gateway->id ) {
					$fields = $gateway->admin_fields();
					TP_Event_Admin_Settings::save_fields( $fields );
					break;
				}
			}
		} else {
			parent::save();
		}
	}

}

return new TP_Event_Admin_Setting_Checkout();
