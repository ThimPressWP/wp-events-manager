<?php
/**
 * The Template for displaying messages notice.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/notices/messages.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! empty( $messages ) ) {
	foreach ( $messages as $code => $msgs ) {
		wpems_get_template( 'notices/' . $code . '.php', array( 'messages' => $msgs ) );
	}
}
