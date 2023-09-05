let iframeTextarea  = document.getElementById('_iframe');
let mapContainer    = document.querySelector('.show_map_iframe');
let errorMessage    = document.querySelector('.error_message');

function updateMap() {
    let iframeContent       = iframeTextarea.value.trim();
    mapContainer.innerHTML  = iframeContent;

    if (iframeContent.toLowerCase().indexOf('<iframe') === 0 || iframeContent === '') {
        errorMessage.textContent = '';
    } else {
        mapContainer.innerHTML   = '';
        errorMessage.textContent = 'Invalid or missing iframe.';
    }
}

iframeTextarea.addEventListener('input', updateMap);

document.addEventListener('DOMContentLoaded', updateMap);