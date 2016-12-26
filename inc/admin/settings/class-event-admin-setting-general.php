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
        $this->id = 'general';
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
                    'type' => 'section_start',
                    'id' => 'general_settings',
                    'title' => __( 'General Options', 'tp-event' ),
                    'desc' => __( 'General options for system.', 'tp-event' )
                ),
                array(
                    'type' => 'select_page',
                    'id' => $prefix . 'event_archive_page_id',
                    'title' => __( 'Event Archive Page', 'tp-event' ),
                    'desc' => __( 'Select page show events list.', 'tp-event' )
                ),
                array(
                    'type' => 'section_end',
                    'id' => 'general_settings'
                )
        ) );
    }

}

return new Event_Admin_Setting_General();
