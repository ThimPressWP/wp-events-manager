<?php
/**
 * The Template for displaying pagination of archive events page.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/pagination.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

global $wp_query;

if ( $wp_query->max_num_pages <= 1 ) {
	return;
} ?>

<div class="events-pagination">
	<?php
	echo paginate_links(
		apply_filters(
			'tp_event_pagination_args',
			array(
				'base'      => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
				'format'    => '',
				'add_args'  => '',
				'current'   => max( 1, get_query_var( 'paged' ) ),
				'total'     => $wp_query->max_num_pages,
				'prev_text' => __( '<', 'wp-events-manager' ),
				'next_text' => __( '>', 'wp-events-manager' ),
				'type'      => 'list',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		)
	);
	?>
</div>
