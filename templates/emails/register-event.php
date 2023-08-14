<?php
/**
 * The Template for displaying email register event for user.
 *
 * Override this template by copying it to yourtheme/wp-events-manager/emails/register-event.php
 *
 * @author        ThimPress, leehld
 * @package       WP-Events-Manager/Template
 * @version       2.1.7
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $booking || ! $user || ! $email_body ) {
	return;
} ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<style type="text/css">
		table td,
		table th {
			font-size: 13px;
			padding: 5px 30px;
			border: 1px solid #eee;
		}
	</style>
</head>
<body>
<?php printf( $email_body ); ?>
</body>
</html>
