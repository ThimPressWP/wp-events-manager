<?php

class Event_Shortcode_Countdown extends Event_Abstract_Shortcodes {

    /**
     * template file
     * @var null
     */
    public $_template = null;

    /**
     * shortcode name
     * @var null
     */
    public $_shortcodeName = null;

    /**
     * atts shortcode: slide, navigation, pagination, events
     */
    public function __construct() {
        $this->_shortcodeName = 'tp_event_countdown';
        $this->_template = 'event-countdown.php';
        parent::__construct();
    }

    /**
     * parse and render atts shortcode
     * @param  [type] $atts
     * @return [type]
     */
    public function parses( $atts ) {
        if ( !empty( $atts['events'] ) ) {
            $ids = array_map( 'intval', array_map( 'trim', explode( ',', $atts['events'] ) ) );

            if ( is_single() ) {
                $ids = array_diff( $ids, array( get_the_ID() ) );
            }

            $args = array(
                'post_type' => 'tp_event',
                'post__in' => $ids
            );
            unset( $atts['events'] );
            return array( 'args' => $args, 'atts' => $atts );
        } else if ( in_the_loop() && is_singular( 'tp_event' ) ) {
            return $atts;
            $args = array(
                'post_type' => 'tp_event',
                'ID' => get_the_ID(),
                'post_status' => 'publish'
            );
            return array( 'args' => $args, 'atts' => $atts );
        }
        return $atts;
    }

}

new Event_Shortcode_Countdown();
