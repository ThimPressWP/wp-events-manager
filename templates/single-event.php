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

//Filter
use WPEMS\Filter\Event\EventFilter;
use WPEMS\Filter\Event\Meta\EventMetaFilter;

// Model
use WPEMS\Models\Event\EventModel;
use WPEMS\Models\Event\Meta\EventMetaModel;
use	WPEMS\Models\Event\Meta\EventMetaConstants;

/**
 * Prevent loading this file directly
 */
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
			$event_id = get_the_ID();
			
			//Event Model
			$filter              = new EventFilter();
			$filter->post_status = 'publish';
			$filter->post_ids    = array( $event_id );
			$eventModel          = EventModel::get_event_data_from_db( $filter );

			//Key meta model
			$key_meta 			 = EventMetaConstants::TP_EVENT_DATE_END;

			//Event meta model
			$eventMetaValue 	 = $eventModel->get_meta_value_by_key( $key_meta );
	
			if ( $eventModel ) {
				echo '<pre>';
				print_r( $eventModel );
				print_r('<br>Meta_value: ' . $eventMetaValue );
				echo '</pre>';
			} else {
				echo '<pre>';
				echo 'The event does not exist or there is no data about the event';
				echo '</pre>';
			}
			?>

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

<?php
get_footer();
