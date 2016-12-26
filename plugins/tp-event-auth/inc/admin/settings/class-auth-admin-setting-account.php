<?php

if ( !defined( 'ABSPATH' ) ) {
    exit();
}

class Event_Admin_Setting_Account extends Event_Admin_Setting_Page {

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
        $this->id = 'account';
        $this->label = __( 'Account', 'tp-event-auth' );
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
                    'id' => 'account_settings'
                ),
                array(
                    'type' => 'number',
                    'title' => __( 'Payments limited display', 'tp-event-auth' ),
                    'desc' => __( 'How many payment show up on account page', 'tp-event-auth' ),
                    'id' => $prefix . 'payment_litmit_showup',
                    'atts' => array(
                        'step' => 1,
                        'min' => 0
                    ),
                    'placeholder'  => 10,
                    'default' => 10
                ),
                array(
                    'type' => 'section_end',
                    'id' => 'account_settings'
                )
        ) );
    }

}

return new Event_Admin_Setting_Account();
