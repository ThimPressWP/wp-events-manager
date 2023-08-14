<?php
/**
 * The Template for displaying shortcode reset password.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/reset-password.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

wpems_print_notices();
?>
<form name="resetpassform" action="<?php echo esc_url( network_site_url( 'wp-login.php?action=resetpass', 'login_post' ) ); ?>" method="POST" class="event-auth-form">
	<input type="hidden" name="user_login" value="<?php echo esc_attr( $atts['login'] ); ?>" />

	<div class="user-pass1-wrap">
		<p class="form-row required">
			<label for="pass1"><?php _e( 'Password', 'wp-events-manager' ); ?></label>
		</p>

		<div class="wp-pwd">
			<span class="password-input-wrapper">
				<input type="password"  class="event_auth_input" name="pass1" />
			</span>
		</div>
	</div>

	<div class="user-pass2-wrap">
		<p class="form-row required">
			<label for="pass2"><?php _e( 'Confirm Password', 'wp-events-manager' ); ?></label>
		</p>

		<div class="wp-pwd">
			<span class="password-input-wrapper">
				<input type="password" name="pass2" class="event_auth_input" />
			</span>
		</div>
	</div>

	<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>

	<?php
	/**
	 * Fires following the 'Strength indicator' meter in the user password reset form.
	 *
	 * @since 3.9.0
	 *
	 * @param WP_User $user User object of the user whose password is being reset.
	 */
	do_action( 'event_auth_resetpass_form', $atts['login'] );
	?>
	<input type="hidden" name="key" value="<?php echo esc_attr( $atts['key'] ); ?>" />
	<p class="submit form-row required">
		<input type="submit" name="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Reset Password', 'wp-events-manager' ); ?>" />
	</p>
</form>

<p id="nav">
	<?php if ( ! is_user_logged_in() ) : ?>
		<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e( 'Log in', 'wp-events-manager' ); ?></a>
	<?php endif; ?>
	<?php
	if ( get_option( 'users_can_register' ) ) :
		$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register', 'wp-events-manager' ) );

		/** This filter is documented in wp-includes/general-template.php */
		echo ' | ' . $registration_url;
	endif;
	?>
</p>
