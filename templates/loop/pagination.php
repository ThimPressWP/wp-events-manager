<?php

/**
 * The template for displaying page pagination in in archive event page.
 *
 * This template can be overridden by copying it to yourtheme/wp-event-manager/loop/pagination.php.
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
global $wp_query;

if ( $wp_query->max_num_pages <= 1 ) {
	return;
}

?>
<nav class="events-pagination">
	<?php
	echo paginate_links(
		apply_filters(
			'tp_events_pagination_args',
			array(
				'base'      => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
				'format'    => '',
				'add_args'  => '',
				'current'   => max( 1, get_query_var( 'paged' ) ),
				'total'     => $wp_query->max_num_pages,
				'prev_text' => __( 'Previous', 'wp-events-manager' ),
				'next_text' => __( 'Next', 'wp-events-manager' ),
				'type'      => 'list',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		)
	);
	?>
</nav>
