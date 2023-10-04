<?php
/**
 * The Template for displaying content single event.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/content-single-event.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$event_id       = get_the_ID();
$event_template = new WPEMS\Template\WpemsEventTemplate( $event_id );
?>

<article id="tp_event-<?php the_ID(); ?>" <?php post_class( 'tp_single_event' ); ?>>
	<?php
	/**
	 * tp_event_before_single_event hook
	 *
	 */
	do_action( 'tp_event_before_single_event' );
	?>

	<div class="summary entry-summary">

		<?php
		/**
		 * tp_event_single_event_title hook
		 */
		// do_action( 'tp_event_single_event_title' );
		$event_template->displayEventTitle( $event_id );

		/**
		 * tp_event_single_event_thumbnail hook
		 */
		// do_action( 'tp_event_single_event_thumbnail' );
		$event_template->displayEventThumbnail( $event_id );

		/**
		 * tp_event_single_event_thumbnail hook
		 */
		// do_action( 'tp_event_loop_event_information' );
		$event_template->displayEventInformation( $event_id );

		/**
		 * tp_event_loop_event_countdown hook
		 */
		// do_action( 'tp_event_loop_event_countdown' );
		$event_template->displayEventCountdown( $event_id );


		/**
		 * tp_event_single_event_content hook
		 */
		// do_action( 'tp_event_single_event_content' );
		$event_template->displayEventContent( $event_id );

		/**
		 * tp_event_loop_event_location hook
		 */
		// do_action( 'tp_event_loop_event_location' );
		$event_template->displayEventIframe( $event_id );

		/**
		 * tp_event_loop_event_location hook
		 */
		// do_action( 'tp_event_loop_schedule' );
		$event_template->displayEventSchedules( $event_id );
		?>

	</div><!-- .summary -->

	<?php
	/**
	 * tp_event_after_single_event hook
	 *
	 * @hooked tp_event_after_single_event - 10
	 */
	do_action( 'tp_event_after_single_event' );
	?>

</article><!-- #product-<?php the_ID(); ?> -->
