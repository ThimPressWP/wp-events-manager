<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$event    = new WPEMS_Event( $event_id );
$user_reg = $event->booked_quantity( get_current_user_id() );
?>


<div class="event_register_area">

    <h2><?php echo esc_html( $event->get_title() ) ?></h2>

    <form name="event_register" class="event_register" method="POST">

		<?php if ( $user_reg == 0 && $event->is_free() && wpems_get_option( 'email_register_times' ) === 'once' ) { ?>
            <input type="hidden" name="qty" value="1" min="1" />
		<?php } else { ?>
            <div class="event_auth_form_field">
                <label for="event_register_qty"><?php _e( 'Quantity', 'wp-events-manager' ) ?></label>
                <input type="number" name="qty" value="1" min="1" max="<?php echo $event->get_slot_available() ?>" id="event_register_qty" />
            </div>
		<?php } ?>

		<?php $payments = wpems_gateways_enable(); ?>
        <!--Hide payment option when cost is 0-->
		<?php if ( !$event->is_free() ) {
			if ( $payments ) { ?>
                <ul class="event_auth_payment_methods">
					<?php $i = 0; ?>
					<?php foreach ( $payments as $id => $payment ) : ?>
                        <li>
                            <input id="payment_method_<?php echo esc_attr( $id ) ?>" type="radio" name="payment_method" value="<?php echo esc_attr( $id ) ?>"<?php echo $i === 0 ? ' checked' : '' ?>/>
                            <label for="payment_method_<?php echo esc_attr( $id ) ?>"><?php echo esc_html( $payment->get_title() ) ?></label>
                        </li>
						<?php $i ++; ?>
					<?php endforeach; ?>
                </ul>
			<?php } else {
				wpems_print_notice( 'error', esc_html__( 'There are no payment gateway available. Please contact administrator to setup it.', 'wp-events-manager' ) );
			}
		} ?>
        <!--End hide payment option when cost is 0-->

        <div class="event_register_foot">
            <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ) ?>" />
            <input type="hidden" name="action" value="event_auth_register" />
			<?php wp_nonce_field( 'event_auth_register_nonce', 'event_auth_register_nonce' ); ?>
            <button class="event_register_submit event_auth_button" <?php echo $payments ? '' : 'disabled="disabled"' ?>><?php _e( 'Register Now', 'wp-events-manager' ); ?></button>
        </div>

    </form>

</div>
