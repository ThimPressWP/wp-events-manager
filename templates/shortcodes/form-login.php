<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

tp_event_print_notices();

?>

<form name="event_auth_login_form" action="" method="post" class="event-auth-form">

    <p class="form-row form-required">
        <label for="user_login"><?php _e( 'Username', 'tp-event' ) ?><span class="required">*</span></label>
        <input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr( ! empty( $_POST['user_login'] ) ? sanitize_text_field( $_POST['user_login'] ) : '' ) ?>" size="20" /></label>
    </p>

    <p class="form-row form-required">
        <label for="user_pass"><?php _e( 'Password', 'tp-event' ) ?><span class="required">*</span></label>
        <input type="password" name="user_pass" id="user_pass" class="input" value="" size="25" />
    </p>

    <?php do_action( 'event_auth_register_form' ); ?>

    <p class="form-row form-required">
        <label for="rememberme" class="inline">
            <input class="input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember me', 'tp-event' ); ?>
        </label>
    </p>

    <p class="submit form-row">
        <?php wp_nonce_field( 'auth-login-nonce', 'auth-nonce' ); ?>
        <input type="hidden" name="action" value="event_login_action" />
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); ?>" />
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Login', 'tp-event' ); ?>" />
    </p>

</form>

<p>
    <?php if ( get_option( 'users_can_register' ) ) : ?>
        <a href="<?php echo esc_attr( tp_event_register_url() ); ?>"><?php _e( 'Register', 'tp-event' ) ?></a> |
    <?php endif; ?>
    <a href="<?php echo esc_attr( wp_lostpassword_url() ); ?>"><?php _e( 'Forgot Password', 'tp-event' ) ?></a>
</p>
