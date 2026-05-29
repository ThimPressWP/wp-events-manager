( function ( window, document ) {
	'use strict';

	function closest( element, selector ) {
		while ( element && element !== document ) {
			if ( element.matches( selector ) ) {
				return element;
			}

			element = element.parentElement;
		}

		return null;
	}

	function config() {
		return window.WPEMS || {};
	}

	function serializeForm( form ) {
		return new window.URLSearchParams( new window.FormData( form ) );
	}

	function removeAll( root, selector ) {
		root.querySelectorAll( selector ).forEach( function ( element ) {
			element.remove();
		} );
	}

	function insertHtmlBefore( element, html ) {
		element.insertAdjacentHTML( 'beforebegin', html );
	}

	function ensureResponse( response ) {
		if ( ! response.ok ) {
			throw new Error( 'Request failed' );
		}

		return response;
	}

	function requestBody( body ) {
		return {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: body.toString()
		};
	}

	function escapeHtml( value ) {
		let wrapper = document.createElement( 'div' );
		wrapper.textContent = value || '';
		return wrapper.innerHTML;
	}

	let TPEventFrontend = {
		init: function () {
			document.addEventListener( 'click', this.onClick.bind( this ) );
			document.addEventListener( 'submit', this.onSubmit.bind( this ) );
			this.sanitizeFormFields();
			this.initCountdowns();
			this.initCarousels();
		},

		onClick: function ( event ) {
			let trigger = closest( event.target, '.event-load-booking-form' );

			if ( ! trigger ) {
				return;
			}

			event.preventDefault();
			this.loadFormRegister( trigger );
		},

		onSubmit: function ( event ) {
			let form = event.target;

			if ( form.matches( 'form.event_register:not(.active)' ) ) {
				event.preventDefault();
				this.bookEventForm( form );
				return;
			}

			if ( form.matches( '#event-lightbox .event-auth-form' ) ) {
				event.preventDefault();
				this.ajaxLogin( form );
			}
		},

		loadFormRegister: function ( trigger ) {
			let settings = config();
			let spinner = document.createElement( 'i' );
			let data = new window.URLSearchParams();

			if ( ! settings.ajaxurl ) {
				return;
			}

			spinner.className = 'event-icon-spinner2 spinner';
			trigger.appendChild( spinner );

			data.append( 'event_id', trigger.dataset.event || '' );
			data.append( 'nonce', settings.register_button || '' );
			data.append( 'action', 'load_form_register' );

			window.fetch( settings.ajaxurl, requestBody( data ) )
				.then( ensureResponse )
				.then( function ( response ) {
					return response.text();
				} )
				.then( this.lightbox.bind( this ) )
				.catch( function () {
					window.alert( settings.something_wrong || 'Something went wrong.' );
				} )
				.finally( function () {
					spinner.remove();
				} );
		},

		bookEventForm: function ( form ) {
			let settings = config();
			let button = form.querySelector( 'button[type="submit"]' );

			if ( ! settings.ajaxurl ) {
				return;
			}

			removeAll( form, '.tp-event-notice' );
			if ( button ) {
				button.classList.add( 'event-register-loading' );
			}
			form.classList.add( 'active' );

			window.fetch( settings.ajaxurl, requestBody( serializeForm( form ) ) )
				.then( ensureResponse )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( response ) {
					if ( 'undefined' === typeof response.status ) {
						TPEventFrontend.setMessage( form, settings.something_wrong || 'Something went wrong.' );
						return;
					}

					if ( true === response.status && response.url ) {
						window.location.href = response.url;
						return;
					}

					if ( true === response.status && '' === response.url && response.event ) {
						TPEventFrontend.closeLightbox();
						TPEventFrontend.showCartMessage( response.event );
					}

					if ( response.message ) {
						TPEventFrontend.setMessage( form, response.message );
					}
				} )
				.catch( function () {
					TPEventFrontend.setMessage( form, settings.something_wrong || 'Something went wrong.' );
				} )
				.finally( function () {
					if ( button ) {
						button.classList.remove( 'event-register-loading' );
					}
					form.classList.remove( 'active' );
				} );
		},

		closeLightbox: function () {
			if ( window.WPEMSModal && 'function' === typeof window.WPEMSModal.close ) {
				window.WPEMSModal.close();
			}
		},

		showCartMessage: function ( eventName ) {
			let settings = config();

			document.querySelectorAll( '.woocommerce-message' ).forEach( function ( message ) {
				message.hidden = true;
			} );

			window.setTimeout( function () {
				document.querySelectorAll( '.entry-register, .event_register_foot' ).forEach( function ( target ) {
					target.insertAdjacentHTML(
						'beforeend',
						'<div class="woocommerce-message">' + ( settings.woo_cart_url || '' ) + '<p>"' + escapeHtml( eventName ) + '"' + ( settings.add_to_cart || '' ) + '</p></div>'
					);
				} );
			}, 100 );
		},

		setMessage: function ( form, message ) {
			let footer = form.querySelector( '.event_register_foot' );

			if ( ! footer ) {
				return;
			}

			footer.insertAdjacentHTML(
				'beforeend',
				'<div class="tp-event-notice error"><div class="event_auth_register_message_error">' + message + '</div></div>'
			);
		},

		sanitizeFormFields: function () {
			document.querySelectorAll( '.form-row.form-required input' ).forEach( function ( input ) {
				if ( input.dataset.wpemsValidated ) {
					return;
				}

				input.dataset.wpemsValidated = '1';
				input.addEventListener( 'blur', function () {
					let row = closest( input, '.form-row' );

					if ( ! row || ! row.classList.contains( 'form-required' ) ) {
						return;
					}

					row.classList.toggle( 'has-error', '' === input.value );
					row.classList.toggle( 'validated', '' !== input.value );
				} );
			} );
		},

		lightbox: function ( content ) {
			let wrapper = document.createElement( 'div' );

			if ( ! window.WPEMSModal || 'function' !== typeof window.WPEMSModal.open ) {
				return;
			}

			wrapper.id = 'event-lightbox';
			wrapper.innerHTML = content;

			window.WPEMSModal.open( {
				content: wrapper,
				mainClass: 'event-lightbox-wrap',
				onOpen: function () {
					wrapper.classList.add( 'event-fade' );
					window.requestAnimationFrame( function () {
						wrapper.classList.add( 'event-in' );
						TPEventFrontend.sanitizeFormFields();
					} );
				}
			} );
		},

		ajaxLogin: function ( form ) {
			let settings = config();
			let button = form.querySelector( '#wp-submit' );
			let lightbox = document.querySelector( '#event-lightbox' );

			if ( ! settings.ajaxurl ) {
				return;
			}

			if ( lightbox ) {
				removeAll( lightbox, '.tp-event-notice' );
			}
			if ( button ) {
				button.classList.add( 'event-register-loading' );
			}

			window.fetch( settings.ajaxurl, requestBody( serializeForm( form ) ) )
				.then( ensureResponse )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( response ) {
					if ( response.notices ) {
						insertHtmlBefore( form, response.notices );
					}

					if ( true === response.status ) {
						if ( response.redirect ) {
							window.location.href = response.redirect;
						} else {
							window.location.reload();
						}
					}
				} )
				.catch( function () {
					insertHtmlBefore( form, '<ul class="tp-event-notice error"><li>' + ( settings.something_wrong || 'Something went wrong.' ) + '</li></ul>' );
				} )
				.finally( function () {
					if ( button ) {
						button.classList.remove( 'event-register-loading' );
					}
				} );
		},

		initCountdowns: function () {
			let settings = config();

			if ( 'function' !== typeof window.WPEMSCountdown ) {
				return;
			}

			document.querySelectorAll( '.tp_event_counter' ).forEach( function ( counter ) {
				new window.WPEMSCountdown( counter, {
					labels: settings.l18n ? settings.l18n.labels : null,
					labels1: settings.l18n ? settings.l18n.labels1 : null,
					serverSync: settings.current_time,
					gmtOffset: settings.gmt_offset
				} );
			} );
		},

		initCarousels: function () {
			if ( 'function' !== typeof window.WPEMSCarousel ) {
				return;
			}

			document.querySelectorAll( '.tp_event_owl_carousel' ).forEach( function ( carousel ) {
				new window.WPEMSCarousel( carousel, {
					navigation: true,
					slideSpeed: 300,
					paginationSpeed: 400,
					singleItem: true
				} );
			} );
		}
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		TPEventFrontend.init();
	} );
}( window, document ) );
