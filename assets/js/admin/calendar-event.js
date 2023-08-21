
jQuery(document).ready(function ($) {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: 'UTC',
        events: eventCalendarData 
    });
    calendar.render();

});

