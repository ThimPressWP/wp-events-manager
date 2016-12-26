'use strict';
( function ( $ ) {

    var Event_Auth = {
        init: function () {
            var $doc = $( document );
            /**
             * load register form
             */
            $doc.on( 'click', '.event-load-booking-form', this.load_form_register );
            $doc.on( 'submit', 'form.event_register:not(.active)', this.book_event_form );

            /**
             * Sanitize form field
             */
            this.sanitize_form_field();

            $doc.on( 'submit', '#event-lightbox .event-auth-form', this.ajax_login );
        },
        load_form_register: function ( e ) {
            e.preventDefault();
            var _this = $( this ),
                    _event_id = _this.attr( 'data-event' );

            $.ajax( {
                url: event_auth_object.ajaxurl,
                type: 'POST',
                dataType: 'html',
                async: false,
                data: {
                    event_id: _event_id,
                    nonce: event_auth_object.register_button,
                    action: 'load_form_register'
                },
                beforeSend: function () {
                    _this.append( '<i class="event-icon-spinner2 spinner"></i>' );
                }
            } )
                    .always( function () {
                        _this.find( '.event-icon-spinner2' ).remove();
                    } )
                    .done( function ( html ) {
                        Event_Auth.lightbox( html );
                    } )
                    .fail( function () {

                    } );
            return false;
        },
        /**
         * Ajax action register form
         * @returns boolean
         */
        book_event_form: function ( e ) {
            e.preventDefault();
            var _self = $( this ),
                    _data = _self.serializeArray(),
                    button = _self.find( 'button[type="submit"]' ),
                    _notices = _self.find( '.event-auth-notice' );

            $.ajax( {
                url: event_auth_object.ajaxurl,
                type: 'POST',
                data: _data,
                dataType: 'json',
                beforeSend: function () {
                    _notices.slideUp().remove();
                    button.addClass( 'event-register-loading' );
                    _self.addClass( 'active' );
                }
            } ).done( function ( res ) {
                button.removeClass( 'event-register-loading' );
                if ( typeof res.status === 'undefined' ) {
                    Event_Auth.set_message( _self, event_auth_object.something_wrong );
                    return;
                }

                if ( res.status === true && typeof res.url !== 'undefined' ) {
                    window.location.href = res.url;
                }

                if ( typeof res.message !== 'undefined' ) {
                    Event_Auth.set_message( _self, res.message );
                    return;
                }

            } ).fail( function () {
                button.removeClass( 'event-register-loading' );
                Event_Auth.set_message( _self, event_auth_object.something_wrong );
                return;
            } ).always(function(){
                _self.removeClass( 'active' );
            });
            // button.removeClass('event-register-loading');
            return false;
        },
        set_message: function ( form, message ) {
            var html = '<ul class="event-auth-notice error">';
            html += '<li class="event_auth_register_message_error">' + message + '</li>';
            html += '</ul>';
            form.find( '.event_register_foot' ).after( html );
        },
        /**
         * sanitize form field
         * @returns null
         */
        sanitize_form_field: function () {
            var _form_fields = $( '.form-row.form-required' );

            for ( var i = 0; i < _form_fields.length; i++ ) {
                var field = $( _form_fields[i] ),
                        input = field.find( 'input' );

                input.on( 'blur', function ( e ) {
                    e.preventDefault();
                    var _this = $( this ),
                            _form_row = _this.parents( '.form-row:first' );
                    if ( !_form_row.hasClass( 'form-required' ) ) return;

                    if ( _this.val() == '' ) {
                        _form_row.removeClass( 'validated' ).addClass( 'has-error' );
                    } else {
                        _form_row.removeClass( 'has-error' ).addClass( 'validated' );
                    }
                } );
            }
        },
        lightbox: function ( content ) {
            var html = [ ];
            html.push( '<div id="event-lightbox">' );
            html.push( content );
            html.push( '</div>' );

            $.magnificPopup.open( {
                items: {
                    type: 'inline',
                    src: $( html.join( '' ) )
                },
                mainClass: 'event-lightbox-wrap',
                callbacks: {
                    open: function () {
                        var lightbox = $( '#event-lightbox' );

                        lightbox.addClass( 'event-fade' );
                        var timeout = setTimeout( function () {
                            lightbox.addClass( 'event-in' );
                            clearTimeout( timeout );
                            Event_Auth.sanitize_form_field();
                        }, 100 );
                    },
                    close: function () {
                        var lightbox = $( '#event-lightbox' );
                        lightbox.remove();
                    }
                }
            } );
        },
        ajax_login: function ( e ) {
            e.preventDefault();

            var _this = $( this ),
                    _button = _this.find( '#wp-submit' ),
                    _lightbox = $( '#event-lightbox' ),
                    _data = _this.serializeArray();

            $.ajax( {
                url: event_auth_object.ajaxurl,
                type: 'POST',
                data: _data,
                async: false,
                beforeSend: function () {
//                    setTimeout( function(){
                        _lightbox.find( '.event-auth-notice' ).slideUp().remove();
//                    } );
                    _button.addClass( 'event-register-loading' );
                }
            } ).always( function () {
                _button.find( '.event-icon-spinner2' ).remove();
            } ).done( function ( res ) {
                if ( typeof res.notices !== 'undefined' ) {
                    _this.before( res.notices );
                }

                if ( typeof res.status !== 'undefined' && res.status === true ) {
                    if ( typeof res.redirect !== 'undefined' && res.redirect ) {
                        window.location.href = res.redirect;
                    } else {
                        window.location.reload();
                    }
                }
            } ).fail( function ( jqXHR, textStatus, errorThrown ) {
                var html = '<ul class="event-auth-notice error">';
                html += '<li>' + jqXHR + '</li>';
                html += '</ul>';
                _this.before( res.notices );
            } );

            return false;
        }
    };

    $( document ).ready( function () {
        Event_Auth.init();
    } );

} )( jQuery );
