<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

wpems_print_notices();
printf(
        __( 'You have successfully registered to <strong>%s</strong>. We have emailed your password to <i>%s</i> the email address you entered.', 'wp-events-manager' ), get_bloginfo( 'name' ), sanitize_text_field($_REQUEST['registered'])
);
