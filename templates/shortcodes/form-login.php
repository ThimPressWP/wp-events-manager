<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

wpems_print_notices();

?>

<form name="event_auth_login_form" action="" method="post" class="event-auth-form">

    <p class="form-row form-required">
        <label for="user_login"><?php _e( 'Username', 'wp-events-manager' ) ?><span class="required">*</span></label>
        <input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr( ! empty( $_POST['user_login'] ) ? sanitize_text_field( $_POST['user_login'] ) : '' ) ?>" size="20" /></label>
    </p>

    <p class="form-row form-required">
        <label for="user_pass"><?php _e( 'Password', 'wp-events-manager' ) ?><span class="required">*</span></label>
        <input type="password" name="user_pass" id="user_pass" class="input" value="" size="25" />
    </p>

    <?php do_action( 'event_auth_register_form' ); ?>

    <p class="form-row form-required">
        <label for="rememberme" class="inline">
            <input class="input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember me', 'wp-events-manager' ); ?>
        </label>
    </p>

    <p class="submit form-row">
        <?php wp_nonce_field( 'auth-login-nonce', 'auth-nonce' ); ?>
        <input type="hidden" name="action" value="event_login_action" />
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); ?>" />
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Login', 'wp-events-manager' ); ?>" />
    </p>

</form>

<p>
    <?php if ( get_option( 'users_can_register' ) ) : ?>
        <a href="<?php echo esc_attr( wpems_register_url() ); ?>"><?php _e( 'Register', 'wp-events-manager' ) ?></a> |
    <?php endif; ?>
    <a href="<?php echo esc_attr( wp_lostpassword_url() ); ?>"><?php _e( 'Forgot Password', 'wp-events-manager' ) ?></a>
</p>
