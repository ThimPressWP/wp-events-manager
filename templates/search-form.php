<?php
/**
 * Template for displaying form to search events.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/search-form.php
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

<?php
if ( ! ( is_post_type_archive( 'tp_event' ) || is_tax( array( 'tp_event_category', 'tp_event_tag', 'tp_event_type' ) ) ) ) {
	return;
}

$s              = isset( $s ) ? $s : get_search_query( false );
$event_location = isset( $event_location ) ? $event_location : ( isset( $_GET['event_location'] ) ? sanitize_text_field( wp_unslash( $_GET['event_location'] ) ) : '' );
?>
<form method="get" name="search-events" class="search-events-form wpems-event-search">
	<label class="screen-reader-text" for="wpems-event-search-keyword"><?php esc_html_e( 'Search events', 'wp-events-manager' ); ?></label>
	<div class="wpems-event-search__field wpems-event-search__field--keyword">
		<span class="wpems-event-search__icon" aria-hidden="true"></span>
		<input id="wpems-event-search-keyword" type="text" name="s" class="search-events-input" value="<?php echo esc_attr( $s ); ?>" placeholder="<?php esc_attr_e( 'Search', 'wp-events-manager' ); ?>"/>
	</div>

	<label class="screen-reader-text" for="wpems-event-search-location"><?php esc_html_e( 'Search by location', 'wp-events-manager' ); ?></label>
	<div class="wpems-event-search__field wpems-event-search__field--location">
		<span class="wpems-event-search__icon" aria-hidden="true"></span>
		<input id="wpems-event-search-location" type="text" name="event_location" value="<?php echo esc_attr( $event_location ); ?>" placeholder="<?php esc_attr_e( 'Location', 'wp-events-manager' ); ?>"/>
	</div>

	<input type="hidden" name="post_type" value="tp_event"/>
	<input type="hidden" name="ref" value="events"/>
	<button class="search-event-button wpems-event-button" type="submit"><?php esc_html_e( 'Search', 'wp-events-manager' ); ?></button>
</form>
