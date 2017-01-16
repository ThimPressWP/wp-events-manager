<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( !$args['event_id'] || !get_post( $args['event_id'] ) ) { ?>
    <p class="tp-event-notice error"><?php echo esc_html__( 'Invalid Event ID', 'tp-event' ); ?></p>
	<?php
} else {
	$event = get_post( $args['event_id'] );
	echo get_the_title( $args['event_id'] );

	$current_time = date( 'Y-m-d H:i' );
	$time         = tp_event_get_time( 'Y-m-d H:i', $event, false ); ?>
    <div class="event-countdown">

		<?php if ( $time > $current_time ) { ?>
			<?php $date = new DateTime( date( 'Y-m-d H:i', strtotime( $time ) ), new DateTimeZone( tp_event_get_timezone_string() ) ); ?>
            <div class="tp_event_counter" data-time="<?php echo esc_attr( $date->format( 'M j, Y H:i:s O' ) ) ?>"></div>
		<?php } else { ?>
            <p class="tp-event-notice error"><?php echo esc_html__( 'This event has expired', 'tp-event' ); ?></p>
		<?php } ?>

    </div>
<?php } ?>

