<?php

if ( !defined( 'ABSPATH' ) ) {
    exit();
}

class Event_Admin_Setting_Email extends Event_Admin_Setting_Page {

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
        $this->id = 'email';
        $this->label = __( 'Emails', 'tp-event-auth' );
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
                    'type' => 'section_start',
                    'id' => 'email_settings'
                ),
                array(
                    'type' => 'select',
                    'title' => __( 'Enable', 'tp-event-auth' ),
                    'desc' => __( 'This controlls what the email', 'tp-event-auth' ),
                    'id' => $prefix . 'email_enable',
                    'options' => array(
                        'yes' => __( 'Yes', 'tp-event-auth' ),
                        'no' => __( 'No', 'tp-event-auth' )
                    ),
                    'default' => 'yes'
                ),
                array(
                    'type' => 'text',
                    'title' => __( 'From name', 'tp-event-auth' ),
                    'desc' => __( 'This set email from name', 'tp-event-auth' ),
                    'placeholder' => get_option( 'blogname' ),
                    'id' => $prefix . 'email_from_name',
                    'default' => get_option( 'blog_name' )
                ),
                array(
                    'type' => 'email',
                    'title' => __( 'Email from', 'tp-event-auth' ),
                    'desc' => __( 'This set email send', 'tp-event-auth' ),
                    'placeholder' => get_option( 'admin_email' ),
                    'id' => $prefix . 'admin_email',
                    'default' => get_option( 'admin_email' )
                ),
                array(
                    'type' => 'text',
                    'title' => __( 'Subject', 'tp-event-auth' ),
                    'desc' => __( 'This set email subject', 'tp-event-auth' ),
                    'placeholder' => __( 'Register event', 'tp-event-auth' ),
                    'id' => $prefix . 'email_subject',
                    'default' => ''
                ),
                array(
                    'type' => 'section_end',
                    'id' => 'email_settings'
                )
        ) );
    }

}

return new Event_Admin_Setting_Email();
