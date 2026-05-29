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

	function toggleAll( selector, shouldShow ) {
		document.querySelectorAll( selector ).forEach( function ( element ) {
			element.classList.toggle( 'hide-if-js', ! shouldShow );
		} );
	}

	function postAdminAction( action ) {
		var data = new window.URLSearchParams();

		if ( ! window.ajaxurl ) {
			return;
		}

		data.append( 'action', action );
		if ( 'event_remove_notice' === action && window.WPEMS_ADMIN ) {
			data.append( 'event_remove_notice_nonce', window.WPEMS_ADMIN.event_remove_notice_nonce || '' );
		}

		window.fetch( window.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
			},
			body: data.toString()
		} ).catch( function () {} );
	}

	var TPEventAdmin = {
		init: function () {
			this.initWidgets();
			this.initToggles();
			this.initNotices();
			this.initMetaBoxes();
		},

		initWidgets: function () {
			document.querySelectorAll( '#widgets-right .widget-content' ).forEach( function ( form ) {
				var firstWidget = form.querySelector( '.tp_event_admin_widget' );
				var firstTab = form.querySelector( '.tp_event_widget_tab li a' );

				if ( firstWidget ) {
					firstWidget.classList.add( 'active' );
				}
				if ( firstTab ) {
					firstTab.classList.add( 'button-primary' );
				}
			} );

			document.addEventListener( 'click', this.onWidgetTabClick.bind( this ) );
		},

		onWidgetTabClick: function ( event ) {
			var tab = closest( event.target, '.tp_event_widget_tab li a' );

			if ( ! tab ) {
				return;
			}

			event.preventDefault();

			var widgetContent = closest( tab, '.widget-content' );
			var parent = closest( tab, '.tp_event_widget_tab' );
			var tabContent = tab.dataset.tab || '';

			if ( parent ) {
				parent.querySelectorAll( 'li a' ).forEach( function ( item ) {
					item.classList.remove( 'button-primary' );
				} );
			}
			tab.classList.add( 'button-primary' );

			if ( widgetContent ) {
				widgetContent.querySelectorAll( '.tp_event_admin_widget' ).forEach( function ( widget ) {
					widget.classList.toggle( 'active', widget.dataset.status === tabContent );
				} );
			}
		},

		initToggles: function () {
			this.bindToggle(
				'input[name="thimpress_events_email_enable"]',
				'.email-setting-form-name, .email-setting-email-form, .email-setting-subject'
			);
			this.bindToggle(
				'input[name="thimpress_events_paypal_enable"]',
				'.paypal-production-email, .paypal-sandbox-mode, .paypal-sandbox-email'
			);
			this.bindToggle(
				'input[name="thimpress_events_allow_register_event"]',
				'.setting-currency, .setting-currency-position, .setting-currency-thousand, .setting-currency-separator, .setting-number-decimals'
			);
		},

		bindToggle: function ( inputSelector, targetSelector ) {
			document.querySelectorAll( inputSelector ).forEach( function ( input ) {
				toggleAll( targetSelector, input.checked );
				input.addEventListener( 'change', function () {
					toggleAll( targetSelector, input.checked );
				} );
			} );
		},

		initNotices: function () {
			document.addEventListener( 'click', function ( event ) {
				var button = closest( event.target, '.tp-event-dismiss-notice button' );

				if ( ! button ) {
					return;
				}

				event.preventDefault();
				postAdminAction( 'event_remove_notice' );

				var notice = closest( button, '.tp-event-dismiss-notice' );
				if ( notice ) {
					notice.remove();
				}
			} );
		},

		initMetaBoxes: function () {
			document.addEventListener( 'click', function ( event ) {
				var trigger = closest( event.target, '.event_meta_panel .open-extra' );

				if ( ! trigger ) {
					return;
				}

				event.preventDefault();
				TPEventAdmin.toggleMetaBoxExtra( trigger );
			} );
		},

		toggleMetaBoxExtra: function ( trigger ) {
			var target = document.querySelector( '#' + trigger.dataset.target || '' );

			if ( ! target ) {
				return;
			}

			var group = closest( target, '.option_group' );
			var nextText = trigger.dataset.text || '';
			var currentText = trigger.textContent;
			var shouldShow = 'yes' !== target.value;

			if ( group ) {
				group.classList.toggle( 'hide-if-js', ! shouldShow );
			}

			target.value = shouldShow ? 'yes' : '';
			trigger.dataset.text = currentText;
			trigger.textContent = nextText;
		}
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		TPEventAdmin.init();
	} );
}( window, document ) );
