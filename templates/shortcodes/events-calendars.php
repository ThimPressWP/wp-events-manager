<?php
include_once  WPEMS_INC . 'admin/class-wpems-admin-events-calendars.php';

$events_table = new WPEMS_Admin_Event_Calendar();
?>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		var calendarEl = document.getElementById('calendar');

		var calendar = new FullCalendar.Calendar(calendarEl, {
			timeZone: 'UTC',
			events: <?php echo ( json_encode( $events_table->load_events() ) ); ?>
		});
		calendar.render();

	});
</script>

<div id='calendar'>

</div>
<br>
