<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !$args['event_id'] || !get_post( $args['event_id'] ) ) {
	echo esc_html__( 'Invalid Event ID', 'tp-event' );
} else {
	$event = get_post( $args['event_id'] );
	echo get_the_title( $args['event_id'] );

	$time = tp_event_get_time( 'M j, Y H:i:s O', $event, false );
	$date = new DateTime( date( 'Y-m-d H:i:s', strtotime( $time ) ), new DateTimeZone( tp_event_get_timezone_string() ) );
	?>
    <div class="entry-countdown">

        <div class="tp_event_counter" data-time="<?php echo esc_attr( $date->format( 'M j, Y H:i:s O' ) ) ?>"></div>

    </div>
	<?php
}

