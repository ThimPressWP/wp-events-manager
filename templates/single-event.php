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
defined( 'ABSPATH' ) || exit();

get_header(); ?>

	<div class="wpems-event-page wpems-event-single-page">
		<?php do_action( 'tp_event_before_main_content' ); ?>

		<header class="wpems-event-hero">
			<div class="wpems-event-hero__inner">
				<h1 class="wpems-event-hero__title"><?php esc_html_e( 'Event', 'wp-events-manager' ); ?></h1>
				<nav class="wpems-event-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'wp-events-manager' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'wp-events-manager' ); ?></a>
					<span aria-hidden="true">/</span>
					<span><?php esc_html_e( 'Pages', 'wp-events-manager' ); ?></span>
					<span aria-hidden="true">/</span>
					<span><?php esc_html_e( 'Event', 'wp-events-manager' ); ?></span>
				</nav>
			</div>
		</header>

		<?php
		while ( have_posts() ) :
			the_post();
			?>

			<?php wpems_get_template_part( 'content', 'single-event' ); ?>

		<?php endwhile; // end of the loop. ?>

		<?php do_action( 'tp_event_after_main_content' ); ?>
	</div>

<?php
get_footer();
