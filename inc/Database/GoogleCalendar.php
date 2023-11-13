<?php
namespace WPEMS\Database;

class GoogleCalendar {
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

new GoogleCalendar();
