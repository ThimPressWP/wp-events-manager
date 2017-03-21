<?php
/**
 * The Template for displaying all archive products.
 *
 * Override this template by copying it to yourtheme/tp-event/templates/archive-event.php
 *
 * @author 		ThimPress
 * @package 	tp-event/template
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header(); ?>

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

		<?php if ( have_posts() ) : ?>

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

				<?php while ( have_posts() ) : the_post(); ?>

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

	<?php
		/**
		 * tp_event_sidebar hook
		 *
		 * @hooked tp_event_get_sidebar - 10
		 */
		do_action( 'tp_event_sidebar' );
	?>

<?php get_footer(); ?>