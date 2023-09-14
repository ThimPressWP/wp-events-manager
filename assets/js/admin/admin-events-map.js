//show map by iframe

let iframeTextarea = document.getElementById('_iframe');
let mapContainer = document.querySelector('.show_map_iframe');
let errorMessage = document.querySelector('.error_message');

function updateMap() {
    let iframeContent = iframeTextarea.value.trim();
    mapContainer.innerHTML = iframeContent;

    if (iframeContent.toLowerCase().indexOf('<iframe') === 0 || iframeContent === '') {
        errorMessage.textContent = '';
    } else {
        mapContainer.innerHTML = '';
        errorMessage.textContent = 'Invalid or missing iframe.';
    }
}
iframeTextarea.addEventListener('input', updateMap);


// show map by api

// function initMap() {
//     const map = new google.maps.Map(document.getElementById("map"), {
//         center: { lat: 40.749933, lng: -73.98633 },
//         zoom: 13,
//         mapTypeControl: false,
//     });
//     const card = document.getElementById("pac-card");
//     const input = document.getElementById("pac-input");
// }

document.addEventListener('DOMContentLoaded', function () {
    updateMap();

    // toggle show api_field or show iframe_field
    function showApiField() {
        document.querySelector('.api_field').style.display = 'block';
        document.querySelector('.iframe_field').style.display = 'none';
    }

    function showIframeField() {
        document.querySelector('.api_field').style.display = 'none';
        document.querySelector('.iframe_field').style.display = 'block';
    }

    // listen for events when radio input changes
    const radioInputs = document.querySelectorAll('input[name="radio_input"]');
    radioInputs.forEach(function (radioInput) {
        radioInput.addEventListener('change', function () {
            if (radioInput.value === 'api') {
                showApiField();
            } else {
                showIframeField();
            }
        });
    });

    const selectedRadioValue = document.querySelector('#selected_radio_value').value;
    // check value hidden input and radio input
    if (selectedRadioValue === 'api') {
        showApiField();
        document.querySelector('input[name="radio_input"][value="api"]').checked = true;
    } else if (selectedRadioValue === 'iframe') {
        showIframeField();
        document.querySelector('input[name="radio_input"][value="iframe"]').checked = true;
    }

    // function that update value of input when value changes
    function updateSelectedRadioValue() {
        const selectedRadio = document.querySelector('input[name="radio_input"]:checked');
        if (selectedRadio) {
            document.querySelector('#selected_radio_value').value = selectedRadio.value;
        }
    }

    // event when value changes
    radioInputs.forEach(function (radioInput) {
        radioInput.addEventListener('change', updateSelectedRadioValue);
    });

    updateSelectedRadioValue();
});

