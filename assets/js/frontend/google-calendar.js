

let CLIENT_ID;
let API_KEY;

// Google API script
const googleApiScript = document.createElement('script');
googleApiScript.src = 'https://apis.google.com/js/api.js';
googleApiScript.async = true;
googleApiScript.defer = true;
googleApiScript.onload = gapiLoaded;
document.head.appendChild(googleApiScript);

// Google Identity Services script
const googleGsiScript = document.createElement('script');
googleGsiScript.src = 'https://accounts.google.com/gsi/client';
googleGsiScript.async = true;
googleGsiScript.defer = true;
googleGsiScript.onload = gisLoaded;
document.head.appendChild(googleGsiScript);
// Attach event listener to the form
const form = document.getElementById('syncCalendar');

// Get the clientID input element
const clientIDInput = document.getElementById('clientID');
const apiKeyInput = document.getElementById('apiKey');

if(form) {
    form.addEventListener('submit', function(event) {
        event.preventDefault(); 
        
        // Assign the value of the input to the global variable
        CLIENT_ID = clientIDInput.value;
        API_KEY = apiKeyInput.value;
    
        // Lưu vào user meta
        saveApiKeyAndClientIdToUserMeta(CLIENT_ID, API_KEY);
        
        gapiLoaded();
        gisLoaded();
        document.getElementById('authorize_button').addEventListener('click', handleAuthClick);
        document.getElementById('signout_button').addEventListener('click', handleSignoutClick);
    });
}

const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';

// Authorization scopes required by the API; multiple scopes can be
// included, separated by spaces.
const SCOPES = 'https://www.googleapis.com/auth/calendar';

let tokenClient;
let gapiInited = false;
let gisInited = false;


function gapiLoaded() {
    gapi.load('client', initializeGapiClient);
}

/**
 * Callback after the API client is loaded. Loads the
 * discovery doc to initialize the API.
 */
async function initializeGapiClient() {
    if (API_KEY !== '') {
        try {
            await gapi.client.init({
                apiKey: API_KEY,
                discoveryDocs: [DISCOVERY_DOC],
            });
            gapiInited = true;
        } catch (error) {
            const errorMessage = "Error initializing Google API client: " + error.error?.message;
            document.getElementById('content').innerText = errorMessage;
            // const formContainer = document.getElementById('syncCalendar'); 
            // const formHTML = getOriginalFormHTML(); 
            // formContainer.innerHTML = formHTML;

        }
    }  
} 


/**
 * Callback after Google Identity Services are loaded.
 */
function gisLoaded() {
    if (CLIENT_ID !== '') {
        tokenClient = google.accounts.oauth2.initTokenClient({
            client_id: CLIENT_ID,
            scope: SCOPES,
            callback: '',
        });
        gisInited = true;
    }
}

/**
 *  Sign in the user upon button click.
 */
function handleAuthClick() {
    tokenClient.callback = async (resp) => {
        if (resp.error !== undefined) {
            throw (resp);
        }
        await listUpcomingEvents();
        await saveApiKeyAndClientIdToUserMeta(CLIENT_ID, API_KEY);
    };

    if (gapi.client.getToken() === null) {
        // Prompt the user to select a Google Account and ask for consent to share their data
        // when establishing a new session.
        tokenClient.requestAccessToken({ prompt: 'consent' });
    } else {
        // Skip display of account chooser and consent dialog for an existing session.
        tokenClient.requestAccessToken({ prompt: '' });
    }
}
/**
       *  Sign out the user upon button click.
       */
function handleSignoutClick() {
    const token = gapi.client.getToken();
    if (token !== null) {
      google.accounts.oauth2.revoke(token.access_token);
      gapi.client.setToken('');
      document.getElementById('content').innerText = '';
    //   document.getElementById('authorize_button').innerText = 'Authorize';
      document.getElementById('signout_button').style.visibility = 'hidden';
    }
  }

/**
 * Insert data to google calendar
 */
async function listUpcomingEvents() {
    
    for (let i = 0; i < bookingData.length; i++) {
        eventCalendar = bookingData[i];
        var request = gapi.client.calendar.events.insert({
            'calendarId': 'primary',
            'resource': eventCalendar
        });

        //  To display the link to frontend
        request.execute(function (eventCalendar) {
            const eventLink = document.getElementById('link_calendar');
            eventLink.href = eventCalendar.htmlLink;
            eventLink.textContent = 'Event created: ' + eventCalendar.htmlLink;
            eventLink.target = '_blank';
            if(eventLink) {
                appendPre(eventLink);
            }
        });
    }
}
function appendPre(text) {
    const pre = document.createElement('pre');
    pre.textContent = text;
    document.body.appendChild(pre);
}



async function saveApiKeyAndClientIdToUserMeta(CLIENT_ID, API_KEY) {
    try {
        console.log('Ajax URL:', ROUTER.ajaxurl);
        const response = await $.ajax({
            url: ROUTER.ajaxurl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                action: 'save_api_key_and_client_id', // The AJAX action
                clientId: CLIENT_ID,
                apiKey: API_KEY,
            }),
            dataType: 'json',
        });

        return response.success;
    } catch (error) {
        // console.error('Error saving apiKey and clientId:', error);
        return false;
    }
}
    
