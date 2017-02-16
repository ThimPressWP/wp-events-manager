<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$query = new WP_Query( $args );

tp_event_print_notices();

if ( !is_user_logged_in() ) {
	printf( __( 'You are not <a href="%s">login</a>', 'wp-event-manager' ), tp_event_login_url() );
	return;
}

if ( $query->have_posts() ) :
	?>

    <table>
        <thead>
        <th><?php _e( 'Booking ID', 'wp-event-manager' ); ?></th>
        <th><?php _e( 'Events', 'wp-event-manager' ); ?></th>
        <th><?php _e( 'Type', 'wp-event-manager' ); ?></th>
        <th><?php _e( 'Cost', 'wp-event-manager' ); ?></th>
        <th><?php _e( 'Quantity', 'wp-event-manager' ); ?></th>
        <th><?php _e( 'Method', 'wp-event-manager' ); ?></th>
        <th><?php _e( 'Status', 'wp-event-manager' ); ?></th>
        </thead>
        <tbody>
		<?php foreach ( $query->posts as $post ): ?>

			<?php $booking = TP_Event_Booking::instance( $post->ID ) ?>
            <tr>
                <td><?php printf( '%s', tp_event_format_ID( $post->ID ) ) ?></td>
                <td><?php printf( '<a href="%s">%s</a>', get_the_permalink( $booking->event_id ), get_the_title( $booking->event_id ) ) ?></td>
                <td><?php printf( '%s', floatval( $booking->price ) == 0 ? __( 'Free', 'wp-event-manager' ) : __( 'Cost', 'wp-event-manager' ) ) ?></td>
                <td><?php printf( '%s', tp_event_format_price( floatval( $booking->price ), $booking->currency ) ) ?></td>
                <td><?php printf( '%s', $booking->qty ) ?></td>
                <td><?php printf( '%s', $booking->payment_id ? tp_event_get_payment_title( $booking->payment_id ) : __( 'No payment', 'wp-event-manager' ) ) ?></td>
                <th><?php printf( '%s', tp_event_booking_status( $booking->ID ) ); ?></th>
            </tr>

		<?php endforeach; ?>
        </tbody>
    </table>

	<?php
	$args = array(
		'base'               => '%_%',
		'format'             => '?paged=%#%',
		'total'              => 1,
		'current'            => 0,
		'show_all'           => false,
		'end_size'           => 1,
		'mid_size'           => 2,
		'prev_next'          => true,
		'prev_text'          => __( '« Previous', 'wp-event-manager' ),
		'next_text'          => __( 'Next »', 'wp-event-manager' ),
		'type'               => 'plain',
		'add_args'           => false,
		'add_fragment'       => '',
		'before_page_number' => '',
		'after_page_number'  => ''
	);

	echo paginate_links( array(
		'base'      => str_replace( 9999999, '%#%', esc_url( get_pagenum_link( 9999999 ) ) ),
		'format'    => '?paged=%#%',
		'prev_text' => __( '« Previous', 'wp-event-manager' ),
		'next_text' => __( 'Next »', 'wp-event-manager' ),
		'current'   => max( 1, get_query_var( 'paged' ) ),
		'total'     => $query->max_num_pages
	) );
	?>

<?php endif; ?>

<?php wp_reset_postdata(); ?>
