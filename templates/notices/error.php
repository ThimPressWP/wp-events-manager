<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<ul class="tp-event-notice error">

    <?php foreach ( $messages as $message ) : ?>

        <li><?php echo sprintf( '%s', $message  ) ?></li>

    <?php endforeach; ?>

</ul>