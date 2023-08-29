<?php

class WPEMS_Google_Calendar {
	public static function event_data() {
		$bookingData = get_posts(
			[
				'post_status' => 'ea-completed',
				'post_type'   => 'event_auth_book',
				'numberposts' => -1,
			]
		);
		return $bookingData;
	}

}

new WPEMS_Google_Calendar();
