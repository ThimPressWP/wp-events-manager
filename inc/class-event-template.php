<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TP_Event_Template {

    /**
     * Path to the includes directory
     * @var string
     */
    private $include_path = '';

    /**
     * The Constructor
     */
    public function __construct() {
        add_filter('template_include', array($this, 'template_loader'));
    }

    public function template_loader($template)
    {
        $post_type = get_post_type();

        $file = '';
        $find = array();
        if( $post_type !== 'tp_event' )
            return $template;

        if( is_post_type_archive( 'tp_event' ) )
        {
            $file = 'archive-event.php';
            $find[] = $file;
            $find[] = tp_event_template_path() . '/' . $file;
        }
        else if( is_single() )
        {
            $file = 'single-event.php';
            $find[] = $file;
            $find[] = tp_event_template_path() . '/' . $file;
        }

        if( $file )
        {
            $find[] = tp_event_template_path() . $file;
            $template = locate_template( array_unique( $find ) );
            if( ! $template )
            {
                $template = untrailingslashit( TP_EVENT_PATH ) . '/templates/' . $file;
            }
        }

        return $template;
    }
}

new TP_Event_Template();
