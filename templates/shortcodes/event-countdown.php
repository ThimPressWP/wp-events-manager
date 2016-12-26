<?php
/**
 * The Template for displaying all archive products.
 *
 * Override this template by copying it to yourtheme/tp-event/templates/shortcode/event-countdown.php
 *
 * @author 		ThimPress
 * @package 	tp-event
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$the_posts = new WP_Query( $args );

?>

<ul class="tp_event_owl_carousel owl-carousel owl-theme"<?php echo isset( $atts ) ? ' data-countdown="'.esc_js( json_encode( $atts ) ).'"' : '' ?> >

	<?php if ( $the_posts->have_posts() ) : ?>

		<?php
			/**
			 * tp_event_before_shop_loop hook
			 *
			 * @hooked tp_event_result_count - 20
			 * @hooked tp_event_catalog_ordering - 30
			 */
			do_action( 'tp_event_before_event_loop' );
		?>

		<?php while ( $the_posts->have_posts() ) : $the_posts->the_post(); ?>

			<?php tp_event_get_template_part( 'content', 'event' ); ?>

		<?php endwhile; // end of the loop. ?>

		<?php wp_reset_postdata(); ?>

		<?php
			/**
			 * tp_event_after_shop_loop hook
			 *
			 * @hooked tp_event_pagination - 10
			 */
			do_action( 'tp_event_after_event_loop' );
		?>

	<?php endif; ?>

</ul>