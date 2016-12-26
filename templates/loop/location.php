<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php if( tp_event_location() ): ?>
	<div class="entry-location">
		<span><?php _e( 'Location: ', 'tp-event' ) ?></span><?php echo tp_event_location(); ?>
	</div>
<?php endif; ?>