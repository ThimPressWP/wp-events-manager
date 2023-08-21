var clientID_apiKey_GGCalendar;
var insertGGCalendar;

// const CLIENT_ID = '98000664356-r1n8rqjco3k4qvu6g8m81jjahi0nnck0.apps.googleusercontent.com';
// const API_KEY = 'AIzaSyCSg85WJh3nNlJJKg30zWekfxi9DQcyK4E';

const CLIENT_ID = clientID_apiKey_GGCalendar[0].clientID ?? '';
const API_KEY = clientID_apiKey_GGCalendar[0].apiKey ?? '';

// Discovery doc URL for APIs used by the quickstart
const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';

// Authorization scopes required by the API; multiple scopes can be
// included, separated by spaces.
const SCOPES = 'https://www.googleapis.com/auth/calendar';

let tokenClient;
let gapiInited = false;
let gisInited = false;

document.getElementById('authorize_button').style.visibility = 'hidden';
document.getElementById('signout_button').style.visibility = 'hidden';

/**
 * Callback after api.js is loaded.
 */
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
            maybeEnableButtons();
        } catch (error) {
            const errorMessage = "Error initializing Google API client: " + error.error?.message;
            document.getElementById('content').innerText = errorMessage;
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
        maybeEnableButtons();
    }
}
/**
 * Enables user interaction after all libraries are loaded.
 */
function maybeEnableButtons() {
    if (clientID_apiKey_GGCalendar[0].clientID !== '' && clientID_apiKey_GGCalendar[0].apiKey !== '') {
        document.getElementById('syncCalendar').style.visibility = 'hidden';
    }
    
    if (gapiInited && gisInited) {
        document.getElementById('authorize_button').style.visibility = 'visible';
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
        document.getElementById('signout_button').style.visibility = 'visible';
        document.getElementById('authorize_button').innerText = 'Refresh';
        await listUpcomingEvents();
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
        document.getElementById('authorize_button').innerText = 'Authorize';
        document.getElementById('signout_button').style.visibility = 'hidden';
    }
}

/**
 * Insert data to google calendar
 */
async function listUpcomingEvents() {
    
    for (let i = 0; i < insertGGCalendar.length; i++) {
        eventCalendar = insertGGCalendar[i];
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
            appendPre(eventLink);
        });
    }
}


