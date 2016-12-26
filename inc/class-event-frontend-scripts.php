<?php

defined( 'ABSPATH' ) || exit();

class Event_Frontend_Assets {

    /**
     * Register scripts
     * @since 1.4.1.4
     */
    public static function init() {
        add_action( 'event_before_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
    }

    /**
     * Register scripts
     * @param type $hook
     */
    public static function register_scripts( $hook ) {
        Event_Assets::register_script( 'thim-event-countdown-plugin-js', TP_EVENT_LIB_URI . '/countdown/js/jquery.plugin.min.js' );
        Event_Assets::register_script( 'thim-event-countdown-js', TP_EVENT_LIB_URI . '/countdown/js/jquery.countdown.min.js' );
        Event_Assets::register_style( 'thim-event-countdown-css', TP_EVENT_LIB_URI . '/countdown/css/jquery.countdown.css' );
        Event_Assets::localize_script( 'thim-event-countdown-js', 'TP_Event', tp_event_l18n() );

        // owl-carousel
        Event_Assets::register_script( 'thim-event-owl-carousel-js', TP_EVENT_LIB_URI . '/owl-carousel/js/owl.carousel.min.js' );
        Event_Assets::register_style( 'thim-event-owl-carousel-css', TP_EVENT_LIB_URI . '/owl-carousel/css/owl.carousel.css' );

        // events
        Event_Assets::register_script( 'thim-event', TP_EVENT_ASSETS_URI . '/js/frontend/events.js' );
        Event_Assets::register_style( 'thim-event', TP_EVENT_ASSETS_URI . '/css/frontend/events.css' );
    }

}

Event_Frontend_Assets::init();
