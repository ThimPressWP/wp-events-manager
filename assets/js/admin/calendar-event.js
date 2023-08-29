document.addEventListener('DOMContentLoaded', function () {
    let calendarEl = document.getElementById('calendar');
    if(calendarEl) {
            let calendar = new FullCalendar.Calendar(calendarEl, {
              initialView: 'dayGridMonth',
              events: eventCalendarData, // Pass from class-wpems-calendar-data.php

              eventDidMount: function (info) {
                    let event = info.event;
                    
                    let tooltip = document.createElement('div');
                    tooltip.className = 'event-tooltip';

                    // Give the information to tooltip
                    tooltip.innerHTML = `
                        <p><strong>${event.title}</strong></p>
                        <p>Time: ${event.extendedProps.date_start} ${event.extendedProps.time_start} - ${event.extendedProps.date_end} ${event.extendedProps.time_end}</p>
                        <p>Location: ${event.extendedProps.location}</p>
                        <p>Total tickets: ${event.extendedProps.totalTicket }</p>
                        <p>Price: ${event.extendedProps.price }</p>
                        <p>Type: ${event.extendedProps.type}</p>
                        <p>Category: ${event.extendedProps.category}</p>
                    `;
        
                    // Add to the screen
                    info.el.appendChild(tooltip);
        
                    // Display the tooltip when mouse enter
                    info.el.addEventListener('mouseenter', function () {
                        tooltip.style.display = 'block';
                    });
        
                    // Hidden the tooltip when mouse leave
                    info.el.addEventListener('mouseleave', function () {
                        tooltip.style.display = 'none';
                    });
                }
            });
            calendar.render();
        }
    }
);
        
      
    //   console.log(eventCalendarData)