
const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';

// Authorization scopes required by the API; multiple scopes can be
// included, separated by spaces.
const SCOPES = 'https://www.googleapis.com/auth/calendar';

let tokenClient;
let gapiInited = false;
let gisInited = false;
let CLIENT_ID;
let API_KEY;

// window.addEventListener('load' , () => {
   
    let submit  = document.querySelector('#sync_google_calendar');

    submit?.addEventListener('click', function(e) {
        
        e.preventDefault();
        
        CLIENT_ID = document.querySelector('#client_id').value;
        API_KEY = document.querySelector('#api_key').value;
        
        gapiLoaded(API_KEY);
        gisLoaded(CLIENT_ID); 
        handleAuthClick();
        console.log('load')

        // document.querySelector('.button-primary')?.addEventListener('click', handleAuthClick);
        // document.querySelector('#signout_button').addEventListener('click', handleSignoutClick);
    });
        
// })


function gapiLoaded(API_KEY) {
    gapi.load('client', initializeGapiClient(API_KEY));
}

/**
 * Callback after the API client is loaded. Loads the
 * discovery doc to initialize the API.
 */
async function initializeGapiClient(API_KEY) {
    if (API_KEY && API_KEY !== '') {
        try {
            await gapi.client.init({
                apiKey: API_KEY,
                discoveryDocs: [DISCOVERY_DOC],
            });
            gapiInited = true;
        } catch (error) {
            console.log(error);
            const errorMessage = "Error initializing Google API client: " + error.error?.message;
            document.getElementById('link_calendar').innerText = errorMessage;
        }
    }  
} 


/**
 * Callback after Google Identity Services are loaded.
 */
function gisLoaded(CLIENT_ID) {
    if (CLIENT_ID && CLIENT_ID !== '') {
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
    // tokenClient.callback = async (resp) => {
    //     if (resp.error !== undefined) {
    //         throw (resp);
    //         console.log(resp)
    //     }
    //     // await listUpcomingEvents();  
    //     const accessToken = resp.access_token;
    //     //todo get refresh token????
    //     // console.log(resp);
    //      // Send the clientID and apiKey to ajax file
    //     (function ($) {
    //         let formData = {
    //             action: 'save_api_key_and_client_id',
    //             api_key_client_id_nonce: $('#api_key_client_id_nonce').val(),
    //             clientId :$('#clientID').val(),
    //             apiKey: $('#apiKey').val(),
    //             access_token: accessToken,
    //         };
    //         $.post(ROUTER.ajaxurl, formData, function(data) {
    //             // console.log('ajax: ', data)
    //         });
    //     })(jQuery);
    // }
   
    // if (gapi.client.getToken() === null) {
    //     // Prompt the user to select a Google Account and ask for consent to share their data
    //     // when establishing a new session.
    //     tokenClient.requestAccessToken({ prompt: 'consent' });
    // } else {
    //     // Skip display of account chooser and consent dialog for an existing session.
    //     tokenClient.requestAccessToken({ prompt: '' });
    // }
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
    // document.getElementById('authorize_button').innerText = 'Authorize';
    // document.getElementById('signout_button').style.visibility = 'hidden';
    }
}

// **
//  * Insert data to google calendar-- use php to do this task from now on
//  */ 

// console.log(ROUTER.bookingData)
// async function listUpcomingEvents() {
//     if(ROUTER.bookingData.length > 0) {
//         let response;
//         let eventCalendar = [];
//         // Get event list from google calendar and check the existed event 
//         for(let i = 0; i< ROUTER.bookingData.length; i++) {
//             try {
//                 const get_request = {
//                     'calendarId': 'primary',
//                     'showDeleted': false,
//                     'singleEvents': true,
//                 };
//                 // Make request to google for get the list event from google calendar
//                 response = await gapi.client.calendar.events.list(get_request);
//                 // If the status === 200 => get all event items
//                 if(response.status === 200) {
//                     const existedEvents = response.result.items; 

//                     for(let j = 0; j < existedEvents.length; j++) {
//                         // Take the id of google event
//                         const id = existedEvents[j].summary;
//                         // Create an unique event array
//                         eventCalendar = ROUTER.bookingData.filter(item => item.summary !== id);
//                     }
//                 }
//                 const events = response.result.items;
//                 if (!events || events.length == 0) {
//                     document.getElementById('link_calendar').innerText = 'No events found.';
//                     return;
//                 }

//             } catch (err) {
//                 document.getElementById('link_calendar').innerText = err.message;
//                 return;
//             }
//        } 
//        // INSERT
//        try {
//             // console.log('eventCalendar1: ', eventCalendar);
//             if(eventCalendar.length > 0) {
//                 eventCalendar.forEach(event => {
//                     // Create inserting request to send to google calendar
//                     let insert_request = gapi.client.calendar.events.insert({
//                         'calendarId': 'primary',
//                         'resource': event,
//                     });
//                     // Execute the inserting request
//                     insert_request.execute(function (event) {                
//                         if (event.error) {
//                             console.error('Error creating event:', event.error);
//                         } else {
//                             const eventLink = document.getElementById('link_calendar');
//                             eventLink.href = event.htmlLink;
//                             eventLink.textContent = 'Event created: ' + event.htmlLink;
//                             eventLink.target = '_blank';
//                             console.log('Event created:', event);
//                         }
//                     }); 
                    
//                 });
//             }
//         } catch (err) {
//             document.getElementById('link_calendar').innerText = err.message;
//             return;
//         }
//     }
//     else {
//         document.getElementById('link_calendar').innerText = 'There are no booked event.';
//     }
// }
// console.log(ROUTER);

document.querySelector('.authorization').addEventListener('click', () => {

    const client_id = document.getElementById('client_id').value;
    const scope = 'https://www.googleapis.com/auth/calendar'; 
    const redirect_uri = 'http://localhost:10046/wp-admin/admin.php'; 
    
    const authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' +
        'client_id=' + encodeURIComponent(client_id) +
        '&scope=' + encodeURIComponent(scope) +
        '&redirect_uri=' + encodeURIComponent(redirect_uri) +
        '&response_type=code' +
        '&access_type=offline' +
        '&prompt=consent';

        window.location = authUrl;
})