document.addEventListener('DOMContentLoaded', function() {
  if (typeof events !== 'undefined') {
    let calendarEl = document.getElementById('calendar-frontend');
    let showEvent = document.querySelector('.show-event-frontend');

    if (calendarEl && showEvent) {
      let calendar = new FullCalendar.Calendar(calendarEl, {
        eventDidMount: function(info) {
          let event = info.event;
          const container = info.el.querySelector('.fc-event-title-container');
          container.setAttribute('data-id', event.id);

          container.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = container.getAttribute('data-id');

            if (events) {
              for (let i = 0; i < events.length; i++) {
                const eventsId = Number(events[i]?.id);

                if (id !== null && Number(id) === eventsId) {
                  let types = events[i]?.types?.map((item) => item?.name?.charAt(0).toUpperCase() + item?.name?.slice(1));
                  let categories = events[i]?.categories?.map((item) => item?.name?.charAt(0).toUpperCase() + item?.name?.slice(1));

                  showEvent.innerHTML = `
                  <h3>${events[i]?.title.charAt(0).toUpperCase() + events[i]?.title?.slice(1) || ''}</h3>
                  <p><strong>Time:</strong> ${events[i]?.date_start} ${events[i]?.time_start} - ${events[i]?.date_end} ${events[i]?.time_end }</p>
                  <p><strong>Location:</strong> ${events[i]?.location?.charAt(0).toUpperCase() + events[i]?.location?.slice(1)  || ''}</p>
                  <p><strong>Total tickets:</strong> ${events[i]?.totalTicket || ''}</p>
                  <p><strong>Price:</strong> $${events[i]?.price || ''}</p>
                  <p><strong>Type:</strong> ${ types?.map(item => item) || ''} </p>
                  <p><strong>Category:</strong> ${categories?.map(item => item) || ''}</p>
                `;
                  showEvent.style.display = 'block';
                  showEvent.style.padding = '20px';
                  container.style.color = 'white';
                  container.style.backgroundColor = '#5f85db';
                }
              }
            }
          });
        },
        events: events
      });

      // For hiding the showEvent
      document.body.addEventListener('click', function(e) {
        if (!e.target.closest('.fc-event-title-container')) {
          showEvent.style.display = 'none';
        }
      });

      calendar.render();
    }
  } else {
    // Handle the case when events are not defined
    console.log('events are not defined');
  }
});
