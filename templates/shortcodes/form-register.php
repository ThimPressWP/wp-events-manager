<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

wpems_print_notices();
?>

<form name="event_auth_register_form" action="" method="post" class="event-auth-form">

    <p class="form-row form-required">
        <label for="user_login"><?php _e( 'Username', 'wp-events-manager' ) ?><span class="required">*</span></label>
        <input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr( ! empty( $_POST['user_login'] ) ? sanitize_text_field( $_POST['user_login'] ) : '' ); ?>" size="20" />
    </p>

    <p class="form-row form-required">
        <label for="user_email"><?php _e( 'Email', 'wp-events-manager' ) ?><span class="required">*</span></label>
        <input type="email" name="user_email" id="user_email" class="input" value="<?php echo esc_attr( ! empty( $_POST['user_email'] ) ? sanitize_text_field( $_POST['user_email'] ) : '' ); ?>" size="25" />
    </p>

    <p class="form-row form-required">
        <label for="user_pass"><?php _e( 'Password', 'wp-events-manager' ) ?><span class="required">*</span></label>
        <input type="password" name="user_pass" id="user_pass" class="input" value="" size="25" />
    </p>

    <p class="form-row form-required">
        <label for="confirm_password"><?php _e( 'Confirm Password', 'wp-events-manager' ) ?><span class="required">*</span></label>
        <input type="password" name="confirm_password" id="confirm_password" class="input" value="" size="25" /></label>
    </p>

    <?php do_action( 'event_auth_register_form' ); ?>

    <?php $send_notify = wpems_get_option( 'register_notify', true ); ?>
    <?php if ( $send_notify ) : ?>
        <p id="reg_passmail" class="form-row">
            <?php _e( 'Registration confirmation will be emailed to you.', 'wp-events-manager' ); ?>
        </p>
    <?php endif; ?>

    <p class="submit form-row">
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); ?>" />
		<?php wp_nonce_field( 'auth-reigter-nonce', 'auth-nonce' ); ?>
        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Register', 'wp-events-manager' ); ?>" />
    </p>

</form>

<p id="nav">
    <a href="<?php echo esc_url( wpems_login_url() ); ?>"><?php _e( 'Log in', 'wp-events-manager' ); ?></a> |
    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Password Lost and Found', 'wp-events-manager' ) ?>"><?php _e( 'Forgot password?', 'wp-events-manager' ); ?></a>
</p>