document.addEventListener('DOMContentLoaded', function() {
  let calendarData;

  if (typeof events === 'undefined') {
     // Handle the case when events are not defined
     console.log('events are not defined');
    } else {
      calendarData = events !== 'undefined' ? JSON.parse(JSON.stringify(events)) : []; 

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

              if (calendarData) {
                for (let i = 0; i < calendarData.length; i++) {
                  const eventsId = Number(calendarData[i]?.id);

                  if (id !== null && Number(id) === eventsId) {
                    let types = calendarData[i]?.types?.map((item) => item?.name?.charAt(0).toUpperCase() + item?.name?.slice(1));
                    let categories = calendarData[i]?.categories?.map((item) => item?.name?.charAt(0).toUpperCase() + item?.name?.slice(1));

                    showEvent.innerHTML = `
                    <h3>${calendarData[i]?.title.charAt(0).toUpperCase() + calendarData[i]?.title?.slice(1) || ''}</h3>
                    <p><strong>Time:</strong> ${calendarData[i]?.date_start} ${calendarData[i]?.time_start} - ${calendarData[i]?.date_end} ${calendarData[i]?.time_end }</p>
                    <p><strong>Location:</strong> ${calendarData[i]?.location?.charAt(0).toUpperCase() + calendarData[i]?.location?.slice(1)  || ''}</p>
                    <p><strong>Total tickets:</strong> ${calendarData[i]?.totalTicket || ''}</p>
                    <p><strong>Price:</strong> $${calendarData[i]?.price || ''}</p>
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
          events: calendarData
        });

        // For hiding the showEvent
        document.body.addEventListener('click', function(e) {
          if (!e.target.closest('.fc-event-title-container')) {
            showEvent.style.display = 'none';
          }
        });

        calendar.render();
      }
   
  }
});
