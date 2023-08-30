
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
  
    var calendar = new FullCalendar.Calendar(calendarEl, {
        eventDidMount: function(info) {
        var event = info.event;
        const container = info.el.querySelector('.fc-event-title-container');
        container.setAttribute('data-id', event.id);             
      },
      events: eventCalendarData
    });

    document.addEventListener('click', function(e) {
        const target = e.target;
        const showEvent = document.querySelector('.showEvent');
        const wrapper = document.querySelector('.fc-event-title-container');
        const id = target.getAttribute('data-id');

        for(let i = 0; i < eventCalendarData.length; i++) {
            if(Number(id) !== null && Number(id) === Number(eventCalendarData[i].id)) {
                showEvent.innerHTML = `
                <span class="dashicons dashicons-no closeEvent"></span>
                <p><strong>${eventCalendarData[i].title}</strong></p>
                <p>Time: ${eventCalendarData[i].date_start} ${eventCalendarData[i].time_start} - ${eventCalendarData[i].date_end} ${eventCalendarData[i].time_end}</p>
                <p>Location: ${eventCalendarData[i].location}</p>
                <p>Total tickets: ${eventCalendarData[i].totalTicket }</p>
                <p>Price: $${eventCalendarData[i].price }</p>
                <p>Type: ${eventCalendarData[i].type}</p>
                <p>Category: ${eventCalendarData[i].category}</p>
            `;
            }
        }
        if(target.classList.contains('closeEvent')) {
          showEvent.style.display  = 'none';
        }
        else {
          showEvent.style.display = 'block';
        }
    
        // if(!target.contains(wrapper)) {
        //     showEvent.style.display = 'none';
        // }else {
        //     showEvent.style.display = 'block';
        // }
     
    })
  
    calendar.render();
});