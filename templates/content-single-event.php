<article id="tp_event-<?php the_ID(); ?>" <?php post_class('tp_single_event'); ?>>

	<?php
		/**
		 * tp_event_before_loop_room_summary hook
		 *
		 * @hooked tp_event_show_room_sale_flash - 10
		 * @hooked tp_event_show_room_images - 20
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
			 * tp_event_loop_event_countdown
			 */
			do_action( 'tp_event_loop_event_location' );

			/**
			 * tp_event_loop_event_countdown
			 */
			do_action( 'tp_event_loop_event_countdown' );

			/**
			 * tp_event_single_event_content hook
			 */
			do_action( 'tp_event_single_event_content' );
		?>

	</div><!-- .summary -->

	<?php
		/**
		 * tp_event_after_loop_room hook
		 *
		 * @hooked tp_event_output_room_data_tabs - 10
		 * @hooked tp_event_upsell_display - 15
		 * @hooked tp_event_output_related_products - 20
		 */
		do_action( 'tp_event_after_single_event' );
	?>

</article><!-- #product-<?php the_ID(); ?> -->