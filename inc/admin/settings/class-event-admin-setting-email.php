<?php

if ( !defined( 'ABSPATH' ) ) {
	exit();
}

class Event_Admin_Setting_Emails extends Event_Admin_Setting_Page {

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
		$this->id    = 'email';
		$this->label = __( 'Emails', 'tp-event' );
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
				'id'    => 'email_settings',
				'title' => __( 'Email Notifications', 'tp-event' ),
			),
			array(
				'type'    => 'select',
				'title'   => __( 'Enable', 'tp-event' ),
				'desc'    => __( 'This controlls what the email', 'tp-event' ),
				'id'      => $prefix . 'email_enable',
				'options' => array(
					'yes' => __( 'Yes', 'tp-event' ),
					'no'  => __( 'No', 'tp-event' )
				),
				'default' => 'yes'
			),
			array(
				'type'        => 'text',
				'title'       => __( 'From name', 'tp-event' ),
				'desc'        => __( 'This set email from name', 'tp-event' ),
				'placeholder' => get_option( 'blogname' ),
				'id'          => $prefix . 'email_from_name',
				'default'     => get_option( 'blog_name' )
			),
			array(
				'type'        => 'email',
				'title'       => __( 'Email from', 'tp-event' ),
				'desc'        => __( 'This set email send', 'tp-event' ),
				'placeholder' => get_option( 'admin_email' ),
				'id'          => $prefix . 'admin_email',
				'default'     => get_option( 'admin_email' )
			),
			array(
				'type'        => 'text',
				'title'       => __( 'Subject', 'tp-event' ),
				'desc'        => __( 'This set email subject', 'tp-event' ),
				'placeholder' => __( 'Register event', 'tp-event' ),
				'id'          => $prefix . 'email_subject',
				'default'     => ''
			),
			array(
				'type' => 'section_end',
				'id'   => 'email_settings'
			)
		) );
	}

}

return new Event_Admin_Setting_Emails();
