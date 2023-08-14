<?php
/**
 * WP Events Manager Section Start setting view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

if ( isset( $field['title'] ) ) {
	echo '<h3>' . esc_html( $field['title'] ) . '</h3>';
	if ( isset( $field['desc'] ) ) {
		echo '<span class="description">' . esc_html( $field['desc'] ) . '</span>';
	}
	do_action( 'tp_event_before_' . $field['id'] . '_fields' );
	echo '<table class="form-table">';
}