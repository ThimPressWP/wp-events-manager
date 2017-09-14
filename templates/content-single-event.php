<?php
/**
 * The template for displaying single event content in single event page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/content-single-event.php
 *
 * @version     2.1
 * @package     WPEMS/Templates
 * @category    Templates
 * @author      Thimpress, leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
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
		do_action( 'tp_event_single_event_title' );

		/**
		 * tp_event_single_event_thumbnail hook
		 */
		do_action( 'tp_event_single_event_thumbnail' );

		/**
		 * tp_event_loop_event_countdown hook
		 */
		do_action( 'tp_event_loop_event_countdown' );

		/**
		 * tp_event_single_event_content hook
		 */
		do_action( 'tp_event_single_event_content' );

		/**
		 * tp_event_loop_event_location hook
		 */
		do_action( 'tp_event_loop_event_location' );
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