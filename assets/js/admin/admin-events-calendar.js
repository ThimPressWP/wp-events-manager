document.addEventListener('DOMContentLoaded', function() {
  if (typeof eventData !== 'undefined') {
    let calendarEl = document.getElementById('calendar-admin');
    let showEvent = document.querySelector('.show-event-admin');

    if (calendarEl && showEvent) {
      let calendar = new FullCalendar.Calendar(calendarEl, {
        eventDidMount: function(info) {
          let event = info.event;
          const container = info.el.querySelector('.fc-event-title-container');
          container.setAttribute('data-id', event.id);

          container.addEventListener('click', function(e) {
            e.stopPropagation();
            const id = container.getAttribute('data-id');

            if (eventData) {
              for (let i = 0; i < eventData.length; i++) {
                const eventDataId = Number(eventData[i]?.id);
                // Show this element when a click event occurs
                if (id !== null && Number(id) === eventDataId) {
                  showEvent.innerHTML = `
                    <h3>${eventData[i]?.title.charAt(0).toUpperCase() + eventData[i]?.title.slice(1)}</h3>
                    <p><strong>Time:</strong> ${eventData[i]?.date_start} ${eventData[i]?.time_start} - ${eventData[i]?.date_end} ${eventData[i]?.time_end}</p>
                    <p><strong>Location:</strong> ${eventData[i]?.location.charAt(0).toUpperCase() + eventData[i]?.location.slice(1) }</p>
                    <p><strong>Total tickets:</strong> ${eventData[i]?.totalTicket}</p>
                    <p><strong>Price:</strong> $${eventData[i]?.price}</p>
                    <p><strong>Type:</strong> ${eventData[i]?.type.charAt(0).toUpperCase() + eventData[i]?.type.slice(1) }</p>
                    <p><strong>Category:</strong> ${(eventData[i]?.category.charAt(0).toUpperCase() + eventData[i]?.category.slice(1) )}</p>
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
        events: eventData
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
