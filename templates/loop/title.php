<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="entry-title">
	<?php if( ! is_singular( 'tp_event' ) || ! in_the_loop() ): ?>
		<h4><a href="<?php the_permalink() ?>">
	<?php else: ?>
		<h1>
	<?php endif; ?>
			<?php the_title(); ?>
	<?php if( ! is_singular( 'tp_event' ) || ! in_the_loop() ): ?>
		</a></h4>
	<?php else: ?>
		</h1>
	<?php endif; ?>
</div>