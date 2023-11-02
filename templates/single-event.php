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

use WPEMS\Filter\EventFilter;
use WPEMS\Helper\Template;
use WPEMS\Model\EventModel;
use WPEMS\TemplateHooks\SingleEventTemplate;

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

			$event_id            = get_the_ID();
			$filter              = new EventFilter();
			$filter->post_status = 'publish';
			$filter->post_ids    = array( $event_id );
			$eventModel          = EventModel::get_event_model_from_db( $filter );
			if ( $eventModel ) {
				$singleEventTemplate = new SingleEventTemplate();
				echo '<pre>';
				// print_r( $eventModel );
				echo '</pre>';

				// echo $singleEventTemplate->html_date_end( $eventModel );
				// echo $singleEventTemplate->html_time_end( $eventModel );
				// echo $singleEventTemplate->html_registration_end_date( $eventModel );
				// echo $singleEventTemplate->html_map_by_iframe( $eventModel );
			} else {
				echo 'Sự kiện không tồn tại.';
			}
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
