<?php

wp_enqueue_script( 'google-calendar-js' );

// wp_add_inline_script(
// 	'google-calendar-js',
// 	'const ROUTER = ' . json_encode(
// 		array(
// 			'ajaxurl' => WPEMS_TEMPLATES_URI . 'shortcodes/events-calendars.php',
// 		)
// 	),
// 	'before'
// );

if ( $args['bookingData'] ) {
	$bookingData = $args['bookingData'];
}
wp_localize_script( 'google-calendar-js', 'bookingData', $bookingData );


// echo '<pre>';
// print_r($bookingData);
// echo '</pre>';

?>
<!-- Create a form to get CLIENT_ID and API_KEY from the client's Google calendar -->
<form id='syncCalendar' action="">
	<?php wp_nonce_field( 'save_api_key_and_client_id', 'api_key_client_id_nonce' ); ?>

	<table id='google-api'>
		<tr>
			<td><label for='clientID'>Client ID</label></td>
			<td><input type='text' name='<?php echo \Wpems_Model_Event\WPEMS_Model_Event_List::$_GOOGLE_CLIENTID; ?>' id='clientID'></td>
		</tr>
		<tr>
			<td><label for='apiKey'>API Key</label></td>
			<td><input type='text' name='<?php echo \Wpems_Model_Event\WPEMS_Model_Event_List::$_GOOGLE_APIKEY; ?>' id='apiKey'></td>
		</tr>
	</table>
	
	<button type='submit' name='' id='authorize_button'>Authorize</button>
	<button id="signout_button">Sign Out</button>
</form>

<!--  To display  message -->
<pre id="content" style="white-space: pre-wrap;"></pre>
<!-- To display the link after sending the data to google calendar -->
<a id='link_calendar'></a>
