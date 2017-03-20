<?php
/**
 * @Author: ducnvtt
 * @Date:   2016-02-22 17:03:48
 * @Last Modified by:     ducnvtt
 * @Last Modified time: 2 2016-03-02 14:10:16
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

wpems_print_notices();
?>

<?php if ( empty ( $_REQUEST['checkemail'] ) ) : ?>

    <form name="forgot-password" class="forgot-password event-auth-form" action="" method="post">

        <p class="form-row event_auth_forgot_password_message message">
            <?php _e( 'Please enter your username or email address. You will receive a link to create a new password via email.', 'wp-events-manager' ) ?>
        </p>
        <p class="form-row required">
            <label for="user_login" ><?php _e( 'Username or Email:', 'wp-events-manager' ) ?>
                <input type="text" name="user_login" id="user_login" class="input" value="<?php echo esc_attr( !empty( $_POST['user_login'] ) ? sanitize_text_field($_POST['user_login']) : '' ); ?>" size="20" /></label>
        </p>
    <?php
    /**
     * Fires inside the lostpassword form tags, before the hidden fields.
     *
     * @since 2.1.0
     */
    do_action( 'tp_event_forgot_password_form' );
    ?>
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); ?>" />
        <p class="form-row submit">
            <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Get New Password', 'wp-events-manager' ); ?>" />
        </p>

    </form>

    <div class="event_auth_lost_pass_footer">
        <a href="<?php echo esc_attr( wpems_login_url() ) ?>">
            <?php _e( 'Login', 'wp-events-manager' ); ?>
        </a> | 
        <?php if ( !is_user_logged_in() ) : ?>

            <a href="<?php echo esc_attr( wpems_register_url() ) ?>">
                <?php _e( 'Create new user', 'wp-events-manager' ); ?>
            </a>

        <?php endif; ?>
    </div>

    <?php do_action( 'tp_event_forgot_password_form_footer' ); ?>

<?php endif; ?>