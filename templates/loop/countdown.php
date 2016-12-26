<?php
if ( !defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$time = tp_event_get_time( 'M j, Y H:i:s O', null, false );
$date = new DateTime( date( 'Y-m-d H:i:s', strtotime( $time ) ), new DateTimeZone( tp_event_get_timezone_string() ) );
?>
<div class="entry-countdown">

    <div class="tp_event_counter" data-time="<?php echo esc_attr( $date->format( 'M j, Y H:i:s O' ) ) ?>"></div>

</div>

<p style="clear:both"></p>
