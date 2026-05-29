( function ( window, document ) {
	'use strict';

	let activeModal = null;

	function getFocusable( root ) {
		return Array.prototype.slice.call(
			root.querySelectorAll(
				'a[href], area[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), iframe, object, embed, [tabindex]:not([tabindex="-1"]), [contenteditable="true"]'
		)
	).filter( function ( element ) {
			return ! element.hasAttribute( 'disabled' ) && 'true' !== element.getAttribute( 'aria-hidden' );
		} );
	}

	function focusModal( dialog ) {
		let focusable = getFocusable( dialog );

		if ( focusable.length ) {
			focusable[0].focus();
			return;
		}

		dialog.focus();
	}

	function trapFocus( event, dialog ) {
		if ( 'Tab' !== event.key ) {
			return;
		}

		let focusable = getFocusable( dialog );
		if ( ! focusable.length ) {
			event.preventDefault();
			dialog.focus();
			return;
		}

		let first = focusable[0];
		let last = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function closeActive() {
		if ( ! activeModal ) {
			return;
		}

		let modal = activeModal;
		activeModal = null;

		document.removeEventListener( 'keydown', modal.onKeyDown );
		modal.overlay.classList.remove( 'event-in' );
		modal.dialog.classList.remove( 'event-in' );

		window.setTimeout( function () {
			if ( modal.overlay.parentNode ) {
				modal.overlay.parentNode.removeChild( modal.overlay );
			}
		}, 180 );

		if ( modal.previousFocus && 'function' === typeof modal.previousFocus.focus ) {
			modal.previousFocus.focus();
		}

		if ( 'function' === typeof modal.options.onClose ) {
			modal.options.onClose();
		}
	}

	function open( options ) {
		options = options || {};

		closeActive();

		let overlay = document.createElement( 'div' );
		let dialog = document.createElement( 'div' );
		let closeButton = document.createElement( 'button' );
		let previousFocus = document.activeElement;

		overlay.className = 'wpems-modal-overlay event-fade';

		dialog.className = ( options.mainClass || 'event-lightbox-wrap' ) + ' wpems-modal-dialog event-fade';
		dialog.setAttribute( 'role', 'dialog' );
		dialog.setAttribute( 'aria-modal', 'true' );
		dialog.setAttribute( 'tabindex', '-1' );

		closeButton.type = 'button';
		closeButton.className = 'wpems-modal-close';
		closeButton.setAttribute( 'aria-label', 'Close' );
		closeButton.textContent = 'x';

		closeButton.addEventListener( 'click', closeActive );
		dialog.appendChild( closeButton );

		if ( options.content instanceof window.HTMLElement ) {
			dialog.appendChild( options.content );
		} else {
			let content = document.createElement( 'div' );
			content.innerHTML = options.content || '';
			dialog.appendChild( content );
		}

		overlay.appendChild( dialog );
		document.body.appendChild( overlay );

		activeModal = {
			overlay: overlay,
			dialog: dialog,
			options: options,
			previousFocus: previousFocus,
			onKeyDown: function ( event ) {
				if ( 'Escape' === event.key ) {
					event.preventDefault();
					closeActive();
					return;
				}

				trapFocus( event, dialog );
			},
			close: closeActive
		};

		overlay.addEventListener( 'click', function ( event ) {
			if ( event.target === overlay ) {
				closeActive();
			}
		} );
		document.addEventListener( 'keydown', activeModal.onKeyDown );

		window.requestAnimationFrame( function () {
			overlay.classList.add( 'event-in' );
			dialog.classList.add( 'event-in' );
			focusModal( dialog );

			if ( 'function' === typeof options.onOpen ) {
				options.onOpen( dialog );
			}
		} );

		return activeModal;
	}

	window.WPEMSModal = {
		open: open,
		close: closeActive,
		getActive: function () {
			return activeModal;
		}
	};
}( window, document ) );
