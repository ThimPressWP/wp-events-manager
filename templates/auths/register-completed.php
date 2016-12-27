<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

tp_event_print_notices();
printf(
        __( 'You have successfully registered to <strong>%s</strong>. We have emailed your password to <i>%s</i> the email address you entered.', 'tp-event' ), get_bloginfo( 'name' ), $_REQUEST['registered']
);
