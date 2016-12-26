<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<ul class="event-auth-notice error">

    <?php foreach ( $messages as $message ) : ?>

        <li><?php echo sprintf( '%s', $message  ) ?></li>

    <?php endforeach; ?>

</ul>