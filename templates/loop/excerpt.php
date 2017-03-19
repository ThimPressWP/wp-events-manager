<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div class="entry-content">
	<?php the_excerpt(); ?>
	<a class="tp_event_view-detail view-detail" href="<?php echo esc_attr( get_the_permalink() ); ?>">
	<?php printf( '%s', __( 'View Detail', 'wp-events-manager' ) ) ?>
	</a>
</div>