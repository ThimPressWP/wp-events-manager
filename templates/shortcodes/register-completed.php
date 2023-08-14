<?php
/**
 * The Template for displaying shortcode register completed.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/register-completed.php
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

printf(
	__( 'You have successfully registered to <strong>%1$s</strong>. We have emailed your password to <i>%2$s</i> the email address you entered.', 'wp-events-manager' ),
	get_bloginfo( 'name' ),
	sanitize_text_field( $_REQUEST['registered'] )
);
