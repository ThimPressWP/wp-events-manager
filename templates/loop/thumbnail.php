<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php if( has_post_thumbnail() ):  ?>

	<div class="entry-thumbnail">
		<?php if( ! is_singular( 'tp_event' ) || ! in_the_loop() ): ?>
			<a href="<?php the_permalink() ?>">
		<?php endif; ?>
				<?php the_post_thumbnail( ); ?>
		<?php if( ! is_singular( 'tp_event' ) || ! in_the_loop() ): ?>
			</a>
		<?php endif; ?>
	</div>

<?php endif; ?>