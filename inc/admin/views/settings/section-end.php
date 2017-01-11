<?php
/*
 * @author leehld
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

echo '</table>';
do_action( 'tp_event_after_' . $field['id'] . '_after' );