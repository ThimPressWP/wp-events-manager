document.addEventListener('DOMContentLoaded', function() {
    // handle display of schedule
    const scheduleBodies = document.querySelectorAll('.schedule_body');

    scheduleBodies.forEach(scheduleBodie => {
        const minusIcon = scheduleBodie.querySelector('.dashicons-minus');
        const plusIcon  = scheduleBodie.querySelector('.dashicons-plus');
        const content   = scheduleBodie.querySelector('.schedule_body-content');

        minusIcon.addEventListener('click', function() {
            this.style.display      = 'none';
            content.style.display   = 'none';
            plusIcon.style.display  = 'block';
        });

        plusIcon.addEventListener('click', function() {
            this.style.display      = 'none';
            minusIcon.style.display = 'block';
            content.style.display   = 'block';
        });
    });

    // handle increase and decrease quantity
    const decreaseBtn      = document.querySelector('.decrease-btn');
    const increaseBtn      = document.querySelector('.increase-btn');
    const noTickets        = document.querySelector('.no_tickets');
    const hiddenInputField = document.querySelector('.edit_quantity input');
    
    hiddenInputField.value = 0;
    let currentValue = parseInt(hiddenInputField.value);

    decreaseBtn.addEventListener('click', function() {
        if (currentValue > 0) {
            currentValue--;
        }
        hiddenInputField.value = currentValue;
        noTickets.textContent  = currentValue;
    });

    increaseBtn.addEventListener('click', function() {
        currentValue++;
        hiddenInputField.value = currentValue;
        noTickets.textContent  = currentValue;
    });
});
