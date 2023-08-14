<?php
/**
 * The Template for displaying error notice.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/notices/error.php
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

	<?php foreach ( $messages as $message ) { ?>

		<li><?php echo sprintf( '%s', $message ); ?></li>

	<?php } ?>

</ul>
