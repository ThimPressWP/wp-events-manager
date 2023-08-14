<?php
/**
 * The Template for displaying location in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/location.php
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

<?php if ( wpems_event_location() ) : ?>
	<div class="entry-location">
		<?php wpems_get_location_map(); ?>
	</div>
<?php endif; ?>
