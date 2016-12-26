<?php

if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Event_Admin_Metabox_Event_Timing {

    public static function init() {

    }

    public static function save( $post_id, $posted ) {
        
    }

    public static function render() {
        ?>
            <ul id="event-timing-period">
                <li class="event-timing-period" data-start="14-10-2016" data-start="15-10-2016">
                    <input type="text" name="timing-title" class="timing-title" value="11111111111" />
                </li>
                <li class="event-timing-period" data-start="14-10-2016" data-start="15-10-2016">
                    <input type="text" name="timing-title" class="timing-title" value="11111111111" />
                </li>
            </ul>
            <style type="text/css"></style>
        <?php
    }

}

Event_Admin_Metabox_Event_Timing::init();
