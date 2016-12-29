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
			array(
				'type'  => 'section_start',
				'id'    => 'general_settings',
				'title' => __( 'General Options', 'tp-event' ),
				'desc'  => __( 'General options for system.', 'tp-event' )
			),
			array(
				'type'  => 'select_page',
				'id'    => $prefix . 'event_archive_page_id',
				'title' => __( 'Event Archive Page', 'tp-event' ),
				'desc'  => __( 'Select page show events list.', 'tp-event' )
			),
			array(
				'type' => 'section_end',
				'id'   => 'general_settings'
			),
			array(
				'type'  => 'section_start',
				'id'    => 'auth_general_settings',
				'title' => __( 'Authentication Settings', 'tp-event' ),
				'desc'  => __( 'Auth setting page', 'tp-event' )
			),
			array(
				'type'  => 'select_page',
				'title' => __( 'Register Page', 'tp-event' ),
				'desc'  => __( 'This controlls which the register page.', 'tp-event' ),
				'id'    => $prefix . 'register_page_id',
			),
			array(
				'type'  => 'select_page',
				'title' => __( 'Login Page', 'tp-event' ),
				'desc'  => __( 'This controlls which the login page.', 'tp-event' ),
				'id'    => $prefix . 'login_page_id',
			),
//            array(
//                'type' => 'select_page',
//                'title' => __( 'Reset Password', 'tp-event' ),
//                'desc' => __( 'This controlls which the reset password page.', 'tp-event' ),
//                'id'    => $prefix . 'reset_password_page_id'
//            ),
//            array(
//                'type' => 'select_page',
//                'title' => __( 'Forgot Pass', 'tp-event' ),
//                'desc' => __( 'This controlls which the forgot password page.', 'tp-event' ),
//                'id' => $prefix . 'forgot_pass_page_id'
//            ),
			array(
				'type'  => 'select_page',
				'title' => __( 'My Account', 'tp-event' ),
				'desc'  => __( 'This controlls which the dashboard page.', 'tp-event' ),
				'id'    => $prefix . 'account_page_id',
			),
			array(
				'type'  => 'checkbox',
				'title' => __( 'Send email.', 'tp-event' ),
				'desc'  => __( 'Send notify when user register.', 'tp-event' ),
				'id'    => $prefix . 'register_notify',
			),
			array(
				'type' => 'section_end',
				'id'   => 'auth_general_settings'
			),
			// Currency
			array(
				'type'  => 'section_start',
				'id'    => 'auth_currency_settings',
				'title' => __( 'Currency', 'tp-event' ),
				'desc'  => __( 'Currency setting will show up on frontend', 'tp-event' )
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
				'type'        => 'text',
				'title'       => __( 'Thousand Separator', 'tp-event' ),
				'id'          => $prefix . 'currency_thousand',
				'default'     => ',',
				'placeholder' => ','
			),
			array(
				'type'        => 'text',
				'title'       => __( 'Decimal Separator', 'tp-event' ),
				'id'          => $prefix . 'currency_separator',
				'default'     => '.',
				'placeholder' => '.'
			),
			array(
				'type'        => 'number',
				'title'       => __( 'Number of Decimals', 'tp-event' ),
				'id'          => $prefix . 'currency_num_decimal',
				'atts'        => array( 'step' => 'any' ),
				'default'     => '2',
				'placeholder' => '2'
			),
			array(
				'type' => 'section_end',
				'id'   => 'auth_currency_settings'
			),
		) );
	}

}

return new Event_Admin_Setting_General();
