<?php
/**
 * The Template for displaying archive events page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/archive-event.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$archive_title  = is_tax() ? single_term_title( '', false ) : post_type_archive_title( '', false );
$archive_title  = $archive_title ? $archive_title : __( 'Event List', 'wp-events-manager' );
$current_view   = isset( $_GET['event_view'] ) && 'list' === sanitize_key( wp_unslash( $_GET['event_view'] ) ) ? 'list' : 'grid';
$current_order  = isset( $_GET['event_order'] ) ? sanitize_key( wp_unslash( $_GET['event_order'] ) ) : '';
$search_query   = get_search_query( false );
$event_location = isset( $_GET['event_location'] ) ? sanitize_text_field( wp_unslash( $_GET['event_location'] ) ) : '';

get_header();
?>

<div class="wpems-event-page wpems-event-archive-page">
	<?php do_action( 'tp_event_before_main_content' ); ?>

	<header class="wpems-event-hero">
		<div class="wpems-event-hero__inner">
			<h1 class="wpems-event-hero__title"><?php echo esc_html( $archive_title ); ?></h1>
			<nav class="wpems-event-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'wp-events-manager' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'wp-events-manager' ); ?></a>
				<span aria-hidden="true">/</span>
				<span><?php esc_html_e( 'Pages', 'wp-events-manager' ); ?></span>
				<span aria-hidden="true">/</span>
				<span><?php esc_html_e( 'Event', 'wp-events-manager' ); ?></span>
			</nav>
		</div>
	</header>

	<main class="wpems-event-archive">
		<?php
		wpems_get_template(
			'search-form.php',
			array(
				's'              => $search_query,
				'event_location' => $event_location,
			)
		);
		?>

		<?php do_action( 'tp_event_archive_description' ); ?>

		<?php if ( have_posts() ) : ?>
			<?php do_action( 'tp_event_before_event_loop' ); ?>

			<div class="wpems-event-archive__toolbar">
				<div class="wpems-event-archive__count">
					<?php
					global $wp_query;
					printf(
						esc_html(
							_n(
								'%s event found',
								'%s events found',
								(int) $wp_query->found_posts,
								'wp-events-manager'
							)
						),
						esc_html( number_format_i18n( (int) $wp_query->found_posts ) )
					);
					?>
				</div>
				<div class="wpems-event-archive__actions">
					<form class="wpems-event-order" method="get">
						<?php foreach ( $_GET as $query_key => $query_value ) : ?>
							<?php
							$query_key = sanitize_key( $query_key );
							if ( ! $query_key || in_array( $query_key, array( 'event_order', 'paged' ), true ) ) {
								continue;
							}
							?>
							<?php foreach ( (array) wp_unslash( $query_value ) as $query_item ) : ?>
								<input type="hidden" name="<?php echo esc_attr( is_array( $query_value ) ? $query_key . '[]' : $query_key ); ?>" value="<?php echo esc_attr( sanitize_text_field( $query_item ) ); ?>" />
							<?php endforeach; ?>
						<?php endforeach; ?>
						<label class="screen-reader-text" for="wpems-event-order"><?php esc_html_e( 'Sort events', 'wp-events-manager' ); ?></label>
						<select id="wpems-event-order" name="event_order">
							<option value="" <?php selected( $current_order, '' ); ?>><?php esc_html_e( 'All', 'wp-events-manager' ); ?></option>
							<option value="date_asc" <?php selected( $current_order, 'date_asc' ); ?>><?php esc_html_e( 'Date: soonest', 'wp-events-manager' ); ?></option>
							<option value="date_desc" <?php selected( $current_order, 'date_desc' ); ?>><?php esc_html_e( 'Date: latest', 'wp-events-manager' ); ?></option>
							<option value="price_asc" <?php selected( $current_order, 'price_asc' ); ?>><?php esc_html_e( 'Price: low to high', 'wp-events-manager' ); ?></option>
							<option value="price_desc" <?php selected( $current_order, 'price_desc' ); ?>><?php esc_html_e( 'Price: high to low', 'wp-events-manager' ); ?></option>
						</select>
						<button type="submit" class="screen-reader-text"><?php esc_html_e( 'Apply sorting', 'wp-events-manager' ); ?></button>
					</form>
					<div class="wpems-event-view-switcher" aria-label="<?php esc_attr_e( 'Event view', 'wp-events-manager' ); ?>">
						<a class="<?php echo 'list' === $current_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'event_view', 'list' ) ); ?>" aria-label="<?php esc_attr_e( 'List view', 'wp-events-manager' ); ?>"><span aria-hidden="true"></span></a>
						<a class="<?php echo 'grid' === $current_view ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'event_view', 'grid' ) ); ?>" aria-label="<?php esc_attr_e( 'Grid view', 'wp-events-manager' ); ?>"><span aria-hidden="true"></span></a>
					</div>
				</div>
			</div>

			<div class="wpems-event-archive__body">
				<?php wpems_get_template( 'event-filter.php' ); ?>

				<ul class="wpems-event-list wpems-event-list--<?php echo esc_attr( $current_view ); ?>">
					<?php
					while ( have_posts() ) :
						the_post();
						wpems_get_template_part( 'content', 'event' );
					endwhile;
					?>
				</ul>
			</div>

			<?php do_action( 'tp_event_after_event_loop' ); ?>
		<?php else : ?>
			<div class="wpems-event-empty">
				<h2><?php esc_html_e( 'No events found', 'wp-events-manager' ); ?></h2>
				<p><?php esc_html_e( 'Try changing your search or filter options.', 'wp-events-manager' ); ?></p>
			</div>
		<?php endif; ?>
	</main>

	<?php do_action( 'tp_event_after_main_content' ); ?>
</div>

<?php get_footer(); ?>
