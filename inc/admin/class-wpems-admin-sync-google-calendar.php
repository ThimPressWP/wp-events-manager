<?php
namespace WPEMS\Event_Sync;
use WPEMS\Database as DB;

class WPEMS_Admin_Sync_Google_Calendar {

	public function sync_google_calendar() {
		$id            = get_current_user_id();
		$client_id     = get_user_meta( $id, 'google_client_id', true );
		$client_secret = get_user_meta( $id, 'google_client_secret', true );
		$code          = get_user_meta( $id, 'google_client_code', true );

		$redirect_uri  = 'http://localhost:10046/wp-admin/admin.php?page=tp-event-setting&tab=event_sync_google_calendar';
		$bookingData   = array();
		$events        = new DB\WPEMS_Event_DB();
		$bookingData   = $events->bookingData();
		$token         = array();
		$refresh_token = isset( $token['refresh_token'] ) ? $token['refresh_token'] : '';

		if ( ! empty( $client_id ) && ! empty( $client_secret ) && ! empty( $code ) && ! empty( $redirect_uri ) ) {
			$token = $this->getAccessToken(
				$client_id,
				$client_secret,
				$redirect_uri,
				$code,
				$refresh_token,
			);
		}

		$access_token = $token['access_token'] ?? '';

		if ( ! empty( $access_token ) ) {
			// Get event list from Google calendar
			$events = $this->get_events_from_google_calendar( $access_token );
			// Convert the data from JSON
			$events_data = json_decode( $events, true );

			$filterEvent = array();
			// Check result and handle the information
			if ( isset( $events_data['items'] ) ) {
				$event_list = $events_data['items'];
				if ( isset( $bookingData ) ) {
					$filterEvent = array_filter(
						$bookingData,
						function( $value ) use ( $event_list ) {
							foreach ( $event_list as $event ) {
								if ( $event['description'] === $value['description'] ) {
									return false;
								}
							}
							return true;
						}
					);
				}
			} else {
				echo 'There are no events on google calendar.';
			}

			// Create an event after user authorization
			if ( count( $filterEvent ) > 0 ) {
				foreach ( $filterEvent as $item ) {

					$response = $this->create_event_on_google_calendar( $access_token, $item );
					// Checking the Google response and handling
					$response_data = json_decode( $response, true );
					if ( isset( $response_data['error'] ) ) {
						// Handle the error
						echo 'Failed to create event: ' . $response_data['error']['message'];
					} else {
						// For success case
						?>
							<a href="<?php echo $response_data['htmlLink']; ?>">'Event created: ' <?php echo $response_data['htmlLink']; ?></a>
						<?php
					}
				}
			} else {
				echo 'There are no new events.';
			}
		}
	}

	private function getAccessToken( $client_id, $client_secret, $redirect_uri, $code, $refresh_token ) {
		$url = 'https://accounts.google.com/o/oauth2/token';

		if ( empty( $refresh_token ) ) {
			$post_data = array(
				'code'          => $code,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri'  => $redirect_uri,
				'grant_type'    => 'authorization_code',
			);

		} else {
			$post_data = array(
				'code'          => $code,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri'  => $redirect_uri,
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
			);
		}

		// Initialize cURL session
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		// Execute cURL session and get the response
		$response = curl_exec( $ch );

		// Check for cURL errors
		if ( $response === false ) {
			return curl_error( $ch );
		}

		// Close the cURL session
		curl_close( $ch );

		// Parse the JSON response
		$data = json_decode( $response, true );

		return $data;

	}

	// Get event list from Google Calendar
	private  function get_events_from_google_calendar( $access_token ) {
		$url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events?showDeleted=false&singleEvents=true';

		// Create header that store authorization and content type
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Content-Type: application/json',
		);

		// Create get request using curl and send access token

		$get_requests = curl_init();
		curl_setopt( $get_requests, CURLOPT_URL, $url );
		curl_setopt( $get_requests, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $get_requests, CURLOPT_RETURNTRANSFER, true );

		// Run request and get the response
		$response = curl_exec( $get_requests );

		// Check response and handle the error
		if ( $response === false ) {
			return curl_error( $get_requests );
		}

		// Close the curl connection
		curl_close( $get_requests );

		return $response;
	}


	// Create an event on google calendar
	private function create_event_on_google_calendar( $access_token, $event_data ) {
		$url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

		// Convert the event data to JSON
		$event_json = json_encode( $event_data );

		// Set up the request parameters
		$request_args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
			'body'    => $event_json,
		);

		// Send the POST request using wp_remote_post
		$response = wp_remote_post( $url, $request_args );

		// Check for errors in the response
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo "Something went wrong: $error_message";
		}

		// Get the response body /? why need to return $body?
		$body = wp_remote_retrieve_body( $response );

		return $body;
	}

}
