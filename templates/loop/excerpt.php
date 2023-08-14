<?php
/**
 * The Template for displaying excerpt in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/loop/excerpt.php
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

<div class="entry-content">
	<?php the_excerpt(); ?>
	<a class="tp_event_view-detail view-detail" href="<?php echo esc_attr( get_the_permalink() ); ?>">
		<?php printf( '%s', __( 'View Detail', 'wp-events-manager' ) ); ?>
	</a>
</div>
