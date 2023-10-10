<?php
/**
 * The Template for displaying single events page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/single-event.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */

use WPEMS\Helper\Template;

defined( 'ABSPATH' ) || exit();

get_header(); ?>

	<?php
		/**
		 * tp_event_before_main_content hook
		 */
		do_action( 'tp_event_before_main_content' );
	?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<?php Template::instance()->get_frontend_template( 'content-single-event.php' ); ?>

		<?php endwhile; // end of the loop. ?>

	<?php
		/**
		 * tp_event_after_main_content hook
		 *
		 * @hooked tp_event_after_main_content - 10 (outputs closing divs for the content)
		 */
		do_action( 'tp_event_after_main_content' );
	?>

<?php
get_footer();
