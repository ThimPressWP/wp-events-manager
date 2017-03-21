<?php
/**
 * Template Single Event post type
 */

get_header( ); ?>

	<?php
		/**
		 * tp_event_before_main_content hook
		 */
		do_action( 'tp_event_before_main_content' );
	?>

		<?php while ( have_posts() ) : the_post(); ?>

			<?php wpems_get_template_part( 'content', 'single-event' ); ?>

		<?php endwhile; // end of the loop. ?>

	<?php
		/**
		 * tp_event_after_main_content hook
		 *
		 * @hooked tp_event_after_main_content - 10 (outputs closing divs for the content)
		 */
		do_action( 'tp_event_after_main_content' );
	?>

<?php get_footer( );
