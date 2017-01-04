<?php

if ( !defined( 'ABSPATH' ) ) {
	exit();
}

class Event_Admin_Setting_General extends Event_Admin_Setting_Page {

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
		$this->id    = 'general';
		$this->label = __( 'General', 'tp-event' );
		parent::__construct();
	}

	/**
	 * Get options setting page
	 * @return type array
	 */
	public function get_settings() {
		$prefix = 'thimpress_events_';
		return apply_filters( 'event_admin_setting_page_' . $this->id, array(
			// Currency
			array(
				'type'  => 'section_start',
				'id'    => 'auth_currency_settings',
				'title' => __( 'General Options', 'tp-event' ),
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Currency', 'tp-event' ),
				'desc'    => __( 'This controlls what the currency prices', 'tp-event' ),
				'id'      => $prefix . 'currency',
				'options' => tp_event_currencies(),
				'default' => 'USD'
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Currency Position', 'tp-event' ),
				'desc'    => __( 'This controlls the position of the currency symbol', 'tp-event' ),
				'id'      => $prefix . 'currency_position',
				'options' => array(
					'left'        => __( 'Left', 'tp-event' ) . ' ' . '(£99.99)',
					'right'       => __( 'Right', 'tp-event' ) . ' ' . '(99.99£)',
					'left_space'  => __( 'Left with space', 'tp-event' ) . ' ' . '(£ 99.99)',
					'right_space' => __( 'Right with space', 'tp-event' ) . ' ' . '(99.99 £)',
				),
				'default' => 'left'
			),
			array(
				'type'    => 'text',
				'title'   => __( 'Thousand Separator', 'tp-event' ),
				'id'      => $prefix . 'currency_thousand',
				'default' => ',',
			),
			array(
				'type'    => 'text',
				'title'   => __( 'Decimal Separator', 'tp-event' ),
				'id'      => $prefix . 'currency_separator',
				'default' => '.',
			),
			array(
				'type'    => 'number',
				'title'   => __( 'Number of Decimals', 'tp-event' ),
				'id'      => $prefix . 'currency_num_decimal',
				'atts'    => array( 'step' => 'any' ),
				'default' => '2',
			),
			array(
				'type' => 'section_end',
				'id'   => 'auth_currency_settings'
			),
		) );
	}

}

return new Event_Admin_Setting_General();
