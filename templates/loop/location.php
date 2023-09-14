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

global $post;

$iframe_f = get_post_meta( $post->ID, 'tp_event_iframe', true );
?>

<?php if ( wpems_event_location() ) : ?>
	<div class="entry-location">
		<?php wpems_get_location_map(); ?>
	</div>
<?php elseif ( wpems_event_iframe_map() ) : ?>
	<div class="entry-location">
		<h6>Location</h6>
		<?php echo $iframe_f; ?>
	</div>
<?php endif; ?>
