<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

// errors messages
if ( !empty( $messages ) ) :
    ?>

    <?php foreach ( $messages as $code => $msgs ) : ?>
        <?php wpems_get_template( 'notices/' . $code . '.php', array( 'messages' => $msgs ) ); ?>
    <?php endforeach; ?>

<?php endif; ?>
