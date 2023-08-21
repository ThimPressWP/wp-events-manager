(function($) {

    $(document).ready(function ($) {
        let calendarEl = document.getElementById('shortcode');
    
        let calendar = new FullCalendar.Calendar(calendarEl, {
            timeZone: 'UTC',
            events: shortcodeCalendarData 
    
        });
        calendar.render();
    
    });
})(jQuery);

