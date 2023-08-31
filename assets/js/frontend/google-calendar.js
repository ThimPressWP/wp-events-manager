


        // // Google API script
        // // const googleApiScript = document.createElement('script');
        // // googleApiScript.src = 'https://apis.google.com/js/api.js';
        // // googleApiScript.async = true;
        // // googleApiScript.defer = true;
        // // googleApiScript.onload = gapiLoaded;
        // // document.head.appendChild(googleApiScript);

        // // // Google Identity Services script
        // // const googleGsiScript = document.createElement('script');
        // // googleGsiScript.src = 'https://accounts.google.com/gsi/client';
        // // googleGsiScript.async = true;
        // // googleGsiScript.defer = true;
        // // googleGsiScript.onload = gisLoaded;
        // // document.head.appendChild(googleGsiScript);

        // const DISCOVERY_DOC = 'https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest';

        // // Authorization scopes required by the API; multiple scopes can be
        // // included, separated by spaces.
        // const SCOPES = 'https://www.googleapis.com/auth/calendar';

        // let tokenClient;
        // let gapiInited = false;
        // let gisInited = false;

        // $("#syncCalendar").submit(function (event) {
        //     event.preventDefault(); 
        //     let CLIENT_ID =  $('#clientID').val();
        //     let API_KEY = $('#apiKey').val();

        //     gapiLoaded(API_KEY);
        //     gisLoaded(CLIENT_ID);    
        //     $('#authorize_button').click(handleAuthClick);
        //     $('#signout_button').click(handleSignoutClick);
        // });    



        // function gapiLoaded(API_KEY) {
        //     gapi.load('client', initializeGapiClient(API_KEY));
        // }

        // /**
        //  * Callback after the API client is loaded. Loads the
        //  * discovery doc to initialize the API.
        //  */
        // async function initializeGapiClient(API_KEY) {
        //     if (API_KEY && API_KEY !== '') {
        //         try {
        //             await gapi.client.init({
        //                 apiKey: API_KEY,
        //                 discoveryDocs: [DISCOVERY_DOC],
        //             });
        //             gapiInited = true;
        //         } catch (error) {
        //             console.log(error);
        //             const errorMessage = "Error initializing Google API client: " + error.error?.message;
        //             // document.getElementById('content').innerText = errorMessage;
        //         }
        //     }  
        // } 


        // /**
        //  * Callback after Google Identity Services are loaded.
        //  */
        // function gisLoaded(CLIENT_ID) {
        //     if (CLIENT_ID && CLIENT_ID !== '') {
        //         tokenClient = google.accounts.oauth2.initTokenClient({
        //             client_id: CLIENT_ID,
        //             scope: SCOPES,
        //             callback: '',
        //         });
        //         gisInited = true;
        //     }
        // }

        // /**
        //  *  Sign in the user upon button click.
        //  */
        // function handleAuthClick() {
       
        //     tokenClient.callback = async (resp) => {
        //         if (resp.error !== undefined) {
        //             throw (resp);
        //         }
        //         await listUpcomingEvents();
               
        //         // Send the data to ajax file
        //         let formData = {
        //             action: 'save_api_key_and_client_id',
        //             api_key_client_id_nonce: $('#api_key_client_id_nonce').val(),
        //             clientId :$('#clientID').val(),
        //             apiKey: $('#apiKey').val(),
        //         };
        //         $.post(ROUTER.ajaxurl, formData, function(data) {});
        //     };

        //     if (gapi.client.getToken() === null) {
        //         // Prompt the user to select a Google Account and ask for consent to share their data
        //         // when establishing a new session.
        //         tokenClient.requestAccessToken({ prompt: 'consent' });
        //     } else {
        //         // Skip display of account chooser and consent dialog for an existing session.
        //         tokenClient.requestAccessToken({ prompt: '' });
        //     }
        // }
        // /**
        //      *  Sign out the user upon button click.
        //      */
        // function handleSignoutClick() {
        //     const token = gapi.client.getToken();
        //     if (token !== null) {
        //     google.accounts.oauth2.revoke(token.access_token);
        //     gapi.client.setToken('');
        //     document.getElementById('content').innerText = '';
        //     //   document.getElementById('authorize_button').innerText = 'Authorize';
        //     document.getElementById('signout_button').style.visibility = 'hidden';
        //     }
        // }

        // /**
        //  * Insert data to google calendar
        //  */
        // async function listUpcomingEvents() {
            
        //     for (let i = 0; i < ROUTER.bookingData.length; i++) {
        //         eventCalendar = ROUTER.bookingData[i];
        //         let request = gapi.client.calendar.events.insert({
        //             'calendarId': 'primary',
        //             'resource': eventCalendar
        //         });

        //         //  To display the link to frontend
        //         request.execute(function (eventCalendar) {
        //             const eventLink = document.getElementById('link_calendar');
        //             eventLink.href = eventCalendar.htmlLink;
        //             eventLink.textContent = 'Event created: ' + eventCalendar.htmlLink;
        //             eventLink.target = '_blank';
        //         });
        //     }
        // }  