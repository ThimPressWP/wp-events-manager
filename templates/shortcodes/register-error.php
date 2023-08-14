<?php
/**
 * The Template for displaying shortcode register error register.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/register-error.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<ul class="tp-event-notice error">
	<li><?php _e( 'Oops! Something went wrong.', 'wp-events-manager' ); ?></li>
</ul>
