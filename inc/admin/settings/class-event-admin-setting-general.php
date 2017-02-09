<?php

if ( !defined( 'ABSPATH' ) ) {
	exit();
}

class TP_Event_Admin_Setting_General extends TP_Event_Abstract_Setting {

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
		$this->id    = 'event_general';
		$this->label = __( 'General', 'tp-event' );
		parent::__construct();
	}

	/**
	 * Get options setting page
	 * @return type array
	 */
	public function get_settings() {
		$prefix = 'thimpress_events_';

		$allow_register_event = tp_event_get_option( 'allow_register_event' );

		return apply_filters( 'event_admin_setting_page_' . $this->id, array(
			// Currency
			array(
				'type'  => 'section_start',
				'id'    => 'auth_currency_settings',
				'title' => __( 'General Options', 'tp-event' ),
			),
			array(
				'type'    => 'yes_no',
				'title'   => __( 'Event registration', 'tp-event' ),
				'desc'    => __( 'Allows user register events', 'tp-event' ),
				'id'      => $prefix . 'allow_register_event',
				'default' => 'yes'
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Currency', 'tp-event' ),
				'desc'    => __( 'This controls what the currency prices', 'tp-event' ),
				'id'      => $prefix . 'currency',
				'options' => tp_event_currencies(),
				'default' => 'USD',
				'class'   => 'setting-currency' . ( $allow_register_event == 'no' ? ' hide-if-js' : '' ) . apply_filters( 'tp_event_currency_setting_fields_class', '' )
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Currency Position', 'tp-event' ),
				'desc'    => __( 'This controls the position of the currency symbol', 'tp-event' ),
				'id'      => $prefix . 'currency_position',
				'options' => array(
					'left'        => __( 'Left', 'tp-event' ) . ' ' . '(£99.99)',
					'right'       => __( 'Right', 'tp-event' ) . ' ' . '(99.99£)',
					'left_space'  => __( 'Left with space', 'tp-event' ) . ' ' . '(£ 99.99)',
					'right_space' => __( 'Right with space', 'tp-event' ) . ' ' . '(99.99 £)',
				),
				'default' => 'left',
				'class'   => 'setting-currency-position' . ( $allow_register_event == 'no' ? ' hide-if-js' : '' ) . apply_filters( 'tp_event_currency_setting_fields_class', '' )
			),
			array(
				'type'    => 'text',
				'title'   => __( 'Thousand Separator', 'tp-event' ),
				'id'      => $prefix . 'currency_thousand',
				'default' => ',',
				'class'   => 'setting-currency-thousand' . ( $allow_register_event == 'no' ? ' hide-if-js' : '' ) . apply_filters( 'tp_event_currency_setting_fields_class', '' )
			),
			array(
				'type'    => 'text',
				'title'   => __( 'Decimal Separator', 'tp-event' ),
				'id'      => $prefix . 'currency_separator',
				'default' => '.',
				'class'   => 'setting-currency-separator' . ( $allow_register_event == 'no' ? ' hide-if-js' : '' ) . apply_filters( 'tp_event_currency_setting_fields_class', '' )
			),
			array(
				'type'    => 'number',
				'title'   => __( 'Number of Decimals', 'tp-event' ),
				'id'      => $prefix . 'currency_num_decimal',
				'atts'    => array( 'step' => 'any' ),
				'default' => '2',
				'class'   => 'setting-number-decimals' . ( $allow_register_event == 'no' ? ' hide-if-js' : '' ) . apply_filters( 'tp_event_currency_setting_fields_class', '' )
			),
			array(
				'type'  => 'text',
				'title' => __( 'Google Map API Key', 'tp-event' ),
				'id'    => $prefix . 'google_map_api_key',
				'desc'  => __( 'Refer on https://developers.google.com/maps/documentation/javascript/get-api-key#get-an-api-key', 'tp-event' ),
			),
			array(
				'type' => 'section_end',
				'id'   => 'auth_currency_settings'
			),
		) );
	}

}

return new TP_Event_Admin_Setting_General();
