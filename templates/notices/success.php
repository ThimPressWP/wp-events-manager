<?php
/**
 * The Template for displaying success notice.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/notices/success.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

foreach ( $messages as $message ) { ?>

	<div class="tp-event-notice success"><?php echo sprintf( '%s', $message ); ?></div>

<?php } ?>
