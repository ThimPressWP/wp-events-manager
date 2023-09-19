
document.addEventListener('DOMContentLoaded', function() {
    let authBtn = document.getElementById('authorization_button');
    if(authBtn) {
        authBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const client_id = document.getElementById('client_id').value;
            const client_secret = document.getElementById('client_secret').value;
            // (function ($) {
            //     let formData = {
            //         action: 'save_client_id',
            //         nonce: $('#tp-event-sync-google-calendar-nonce').val(),
            //         clientId :client_id,
            //         clientSecret: client_secret,
            //     };
            //     $.post(ROUTER.ajaxurl, formData, function(data) {
            //         console.log('ajax: ', data)
            //     });
            // })(jQuery);
        
            const scope = 'https://www.googleapis.com/auth/calendar'; 
            const redirect_uri = 'http://localhost:10046/wp-admin/admin.php?page=tp-event-setting&tab=event_sync_google_calendar'; 
            
            const authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' +
                'client_id=' + encodeURIComponent(client_id) +
                '&scope=' + encodeURIComponent(scope) +
                '&redirect_uri=' + encodeURIComponent(redirect_uri) +
                '&response_type=code' +
                '&access_type=offline' +
                '&prompt=consent'+
                '&state=' + encodeURIComponent(client_id) + '--' + encodeURIComponent(client_secret) ;
        
                window.location = authUrl;
        
        })
    }
})