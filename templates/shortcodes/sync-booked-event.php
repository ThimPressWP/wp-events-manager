<?php

// wp_enqueue_script( 'wpems-sync-google-js' );

// wp_add_inline_script(
// 	'wpems-sync-google-js',
// 	'const ROUTER = ' . json_encode(
// 		array(
// 			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
// 		)
// 	),
// 	'before'
// );


// if(!empty($args['access_token'])) {
// 	echo 'Your events has been pushed to your google calendar automatically.';

// }else {
?>
		<!-- Create a form to get CLIENT_ID and API_KEY from the client's Google calendar -->
		<!-- <form id='syncCalendar' action="" method='POST'>
			<?php //wp_nonce_field( 'api_key_and_client_id_action', 'api_key_client_id_nonce' ); ?>

			<table id='google-api'>
				<tr>
					<td><label for='clientID'>Client ID</label></td>
					<td><input type='text' name='wpems_google_clientID' id='clientID'></td>
				</tr>
				<tr>
					<td><label for='apiKey'>API Key</label></td>
					<td><input type='text' name='wpems_google_apiKey' id='apiKey'></td>
				</tr>
			</table>
			
			<button type='submit' name='' id='authorize_button'>Authorize</button>
			<button id="signout_button">Sign Out</button>
		</form>

		<!-- To display  message  -->
		<p id="content" ></p>
		<!-- To display the link after sending the data to google calendar -->
		<a id='link_calendar'></a> -->

<!-- 
		<script async defer src="https://apis.google.com/js/api.js" onload="gapiLoaded()"></script>
		<script async defer src="https://accounts.google.com/gsi/client" onload="gisLoaded()"></script> -->

	<?php
	// }

