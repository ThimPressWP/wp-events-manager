<?php
/**
 * The Template for displaying shortcode event calendar.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/event-calendar.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

use WPEMS\Model as Md;

wp_enqueue_script( 'wpems-calendar-js' );

$eventDB = Md\WpemsEventsModel::getInstance();
$events  = $eventDB->calendar_data();

if ( ! is_array( $events ) ) {
	return;
}
wp_localize_script( 'wpems-calendar-js', 'events', $events );

?>
<div id='calendar-frontend'></div>
<div class='wrapper-event'>
	<div class="show-event-frontend"></div>
</div>
