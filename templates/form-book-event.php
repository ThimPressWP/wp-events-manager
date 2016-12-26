<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$event = new Auth_Event( $event_id );
$user_reg = $event->booked_quantity( get_current_user_id() );
?>

<?php if ( $user_reg = 0 || event_get_option( 'email_register_times' ) === 'many' ) : ?>

    <div class="event_register_area">

        <h2><?php echo esc_html( $event->get_title() ) ?></h2>

        <form name="event_register" class="event_register" method="POST">

            <?php if ( !$event->is_free() || event_get_option( 'email_register_times' ) === 'many' ) : ?>
                <!--allow set slot-->
                <div class="event_auth_form_field">
                    <label for="event_register_qty"><?php _e( 'Quantity', 'tp-event-auth' ) ?></label>
                    <input type="number" name="qty" value="1" min="1" id="event_register_qty"/>
                </div>
                <!--end allow set slot-->
            <?php else: ?>
                <!--disallow set slot-->
                <input type="hidden" name="qty" value="1" min="1"/>
            <?php endif; ?>

            <!--Hide payment option when cost is 0-->
            <?php if ( ! $event->is_free() ) : ?>
                <ul class="event_auth_payment_methods">
                    <?php $payments = event_auth_payments(); ?>
                    <?php
                        $i = 0;
                        foreach ( $payments as $id => $payment ) :
                            ?>
                            <li>
                                <input id="payment_method_<?php echo esc_attr( $id ) ?>" type="radio" name="payment_method" value="<?php echo esc_attr( $id ) ?>"<?php echo $i === 0 ? ' checked' : '' ?>/>
                                <label for="payment_method_<?php echo esc_attr( $id ) ?>"><?php echo esc_html( $payment->get_title() ) ?></label>
                            </li>
                            <?php
                            $i++;
                        endforeach;
                    ?>
                    <?php //do_action( 'event_auth_payment_gateways_select' ); ?>
                </ul>
            <?php endif; ?>
            <!--End hide payment option when cost is 0-->

            <div class="event_register_foot">
                <input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ) ?>" />
                <input type="hidden" name="action" value="event_auth_register" />
                <?php wp_nonce_field( 'event_auth_register_nonce', 'event_auth_register_nonce' ); ?>
                <button class="event_register_submit event_auth_button"><?php _e( 'Register Now', 'tp-event-auth' ); ?></button>
            </div>

        </form>

    </div>

<?php endif; ?>