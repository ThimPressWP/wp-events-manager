<?php
/**
 * WP Events Manager Section End setting view
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/View
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

echo '</table>';
do_action( 'tp_event_after_' . $field['id'] . '_after' );
