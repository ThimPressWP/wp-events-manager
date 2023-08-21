<?php
wp_enqueue_script( 'insert_calendar' );

$bookingData = get_posts(
	[
		'post_status' => 'ea-completed',
		'post_type'   => 'event_auth_book',
		'numberposts' => -1,
	]
);
$eventData   = array();

if ( ! empty( $bookingData ) ) {
	foreach ( $bookingData as $key => $value ) {
		$eventData[] = array(
			'summary'    => $value->post_content,
			'start'      => array(
				'dateTime' => str_replace(' ', 'T', $value->post_date),
				'timeZone' => 'UTC',
			),
			'end'        => array(
				'dateTime' => str_replace(' ', 'T', $value->post_date),
				'timeZone' => 'UTC',
			),
		);
	}
}

wp_localize_script( 'inset_calendar', 'insertGGCalendar', $eventData );

?>
<!-- Create a form to get CLIENT_ID and API_KEY from client google calendar -->
	<form id='syncCalendar' action="" method="post">
		<div>
			<label for=""> Client ID</label>
			<input type="text" name="calendar_clientID" >
		</div>
		<div>
			<label for=""> API Key</label>
			<input type="text" name='calendar_apiKey' >
		</div>
		<button type="submit" name="sync_calendar" >Submit</button>
	</form>

<?php

//  Initialize value for CLIENT_ID and API_KEY
 $key = array(
	[
		'clientID' => '',
		'apiKey' => ''
	]
 );

//  Check the condition to get the value  from client.
if(isset($_POST['sync_calendar'])){
	$calendar_clientID = !empty($_POST['calendar_clientID']) ? trim($_POST['calendar_clientID']) : '';
	$calendar_apiKey = !empty($_POST['calendar_apiKey']) ? trim($_POST['calendar_apiKey']) : '';
	
	if(empty($calendar_apiKey)) {
		echo 'Please enter your client ID from your google calendar.<br/>';
	}
	if(empty($calendar_apiKey)) {
		echo 'Please enter your API key from your google calendar.<br/>';
	}
	$key = array(
		[
			'clientID' => $calendar_clientID,
			'apiKey' => $calendar_apiKey
		]
	);
}

// Send CLIENT_ID and API_KEY to javascript file to handle the value
wp_localize_script( 'inset_calendar', 'clientID_apiKey_GGCalendar', $key );
?>


<br>
<!--  To display  message -->
<pre id="content" style="white-space: pre-wrap;"></pre>
<!-- To display the link after sending the data to google calendar -->
<a id='link_calendar'></a>
<br>
<!--Add buttons to initiate auth sequence and sign out-->
<button id="authorize_button" onclick="handleAuthClick()">Authorize</button>
<button id="signout_button" onclick="handleSignoutClick()">Sign Out</button>

<!-- To load the library -->
<script async defer src="https://apis.google.com/js/api.js" onload="gapiLoaded()"></script>
<script async defer src="https://accounts.google.com/gsi/client" onload="gisLoaded()"></script>




