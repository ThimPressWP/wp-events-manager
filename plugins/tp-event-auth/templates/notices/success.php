<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

foreach ( $messages as $message ) : ?>

    <div class="event-auth-notice success"><?php echo sprintf( '%s', $message  ) ?></div>

<?php endforeach; ?>
