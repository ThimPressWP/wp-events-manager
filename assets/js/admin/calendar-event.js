
var response = passedData;
var url = response[0].url;

response.shift();

jQuery(document).ready(function ($) {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: 'UTC',
        events: response
    });
    calendar.render();

});

