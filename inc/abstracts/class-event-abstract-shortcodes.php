<?php

/**
 * abstract class shortcodes
 */
abstract class Event_Abstract_Shortcodes {

    /**
     * template file
     * @var null
     */
    protected $_template = null;

    /**
     * shortcode name
     * @var null
     */
    protected $_shortcodeName = null;
    public $_atts = null;

    function __construct() {
        if ( !$this->_shortcodeName || !$this->_template )
            return;

        add_shortcode( $this->_shortcodeName, array( $this, 'add_shortcode' ) );
        add_action( 'tp_event_before_wrap_shortcode', array( $this, 'shortcode_start_wrap' ) );
        add_action( 'tp_event_after_wrap_shortcode', array( $this, 'shortcode_end_wrap' ) );
    }

    // add strat wrap shortcode html
    public function shortcode_start_wrap() {
        return '<div class="tp_event_wrapper ' . $this->_shortcodeName . '">';
    }

    public function add_shortcode( $atts, $content = null ) {
        do_action( 'tp_event_before_wrap_shortcode', $this->_shortcodeName );

        $this->_atts = $this->parses( $atts );
        $this->_template = apply_filters( 'tp_event_shortcode_template', $this->_template, $this->_shortcodeName );
        tp_event_get_template( 'shortcodes/' . $this->_template, $this->_atts );

        do_action( 'tp_event_after_wrap_shortcode', $this->_shortcodeName );
    }

    // add end wrap shortcode html
    public function shortcode_end_wrap() {
        return '</div>';
    }

    /**
     * parse atts
     * @param  array
     * @return array
     */
    public function parses( $atts ) {
        return apply_filters( 'tp_events_shortcode_atts', $atts, $this->_shortcodeName );
    }

}
