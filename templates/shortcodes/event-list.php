<?php
/**
 * The Template for displaying shortcode list events.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/shortcodes/event-list.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$list_event = new WP_Query( $args );
?>

<?php
/**
 * tp_event_before_main_content hook
 *
 * @hooked tp_event_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked tp_event_breadcrumb - 20
 */
do_action( 'tp_event_before_main_content' );
?>

<?php
/**
 * tp_event_archive_description hook
 *
 * @hooked tp_event_taxonomy_archive_description - 10
 * @hooked tp_event_room_archive_description - 10
 */
do_action( 'tp_event_archive_description' );
?>

<?php if ( $list_event->have_posts() ) : ?>

	<?php
	/**
	 * tp_event_before_event_loop hook
	 *
	 * @hooked tp_event_result_count - 20
	 * @hooked tp_event_catalog_ordering - 30
	 */
	do_action( 'tp_event_before_event_loop' );
	?>

	<ul>

		<?php
		while ( $list_event->have_posts() ) :
			$list_event->the_post();
			?>

			<?php wpems_get_template_part( 'content', 'event' ); ?>

		<?php endwhile; // end of the loop. ?>

	</ul>

	<?php
	/**
	 * tp_event_after_event_loop hook
	 *
	 * @hooked tp_event_pagination - 10
	 */
	do_action( 'tp_event_after_event_loop' );
	?>

<?php endif; ?>

<?php
/**
 * tp_event_after_main_content hook
 *
 * @hooked tp_event_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'tp_event_after_main_content' );
?>
