( function ( window ) {
	'use strict';

	const SECOND = 1000;
	const MINUTE = 60;
	const HOUR = 60 * MINUTE;
	const DAY = 24 * HOUR;
	const WEEK = 7 * DAY;
	const MONTH = 30 * DAY;
	const YEAR = 365 * DAY;

	function defaultLabels() {
		return {
			labels: [ 'Years', 'Months', 'Weeks', 'Days', 'Hours', 'Minutes', 'Seconds' ],
			labels1: [ 'Year', 'Month', 'Week', 'Day', 'Hour', 'Minute', 'Second' ]
		};
	}

	function localizedLabels() {
		let defaults = defaultLabels();
		let l10n = window.WPEMS && window.WPEMS.l18n ? window.WPEMS.l18n : {};

		return {
			labels: l10n.labels || defaults.labels,
			labels1: l10n.labels1 || defaults.labels1
		};
	}

	function parseDate( value ) {
		if ( value instanceof Date ) {
			return new Date( value.getTime() );
		}

		if ( ! value ) {
			return null;
		}

		let date = new Date( value );

		return Number.isNaN( date.getTime() ) ? null : date;
	}

	function getGmtOffset( options ) {
		if ( 'undefined' !== typeof options.gmtOffset ) {
			return parseFloat( options.gmtOffset ) || 0;
		}

		if ( 'undefined' !== typeof options.gmt_offset ) {
			return parseFloat( options.gmt_offset ) || 0;
		}

		if ( window.WPEMS && 'undefined' !== typeof window.WPEMS.gmt_offset ) {
			return parseFloat( window.WPEMS.gmt_offset ) || 0;
		}

		return 0;
	}

	function serverOffset( value ) {
		let serverDate = parseDate( value );

		if ( ! serverDate ) {
			return 0;
		}

		return serverDate.getTime() - Date.now();
	}

	function splitSeconds( seconds ) {
		let remaining = Math.max( 0, seconds );
		let years = Math.floor( remaining / YEAR );
		remaining -= years * YEAR;

		let months = Math.floor( remaining / MONTH );
		remaining -= months * MONTH;

		let weeks = Math.floor( remaining / WEEK );
		remaining -= weeks * WEEK;

		let days = Math.floor( remaining / DAY );
		remaining -= days * DAY;

		let hours = Math.floor( remaining / HOUR );
		remaining -= hours * HOUR;

		let minutes = Math.floor( remaining / MINUTE );
		remaining -= minutes * MINUTE;

		return [ years, months, weeks, days, hours, minutes, remaining ];
	}

	function visibleParts( parts ) {
		let firstVisible = parts.findIndex( function ( value ) {
			return value > 0;
		} );

		if ( -1 === firstVisible ) {
			firstVisible = parts.length - 1;
		}

		return parts.slice( firstVisible ).map( function ( value, offset ) {
			return {
				index: firstVisible + offset,
				value: value
			};
		} );
	}

	function Countdown( element, options ) {
		let labels = localizedLabels();

		this.element = element;
		this.options = options || {};
		this.labels = this.options.labels || labels.labels;
		this.labels1 = this.options.labels1 || labels.labels1;
		this.until = parseDate( this.options.until ) || parseDate( element.getAttribute( 'data-time' ) ) || new Date();
		this.until = new Date( this.until.getTime() - getGmtOffset( this.options ) * HOUR * SECOND );
		this.serverOffset = serverOffset( this.options.serverSync );
		this.timer = null;

		this.start();
	}

	Countdown.prototype.remainingSeconds = function () {
		return Math.max( 0, Math.floor( ( this.until.getTime() - ( Date.now() + this.serverOffset ) ) / SECOND ) );
	};

	Countdown.prototype.labelFor = function ( index, value ) {
		let source = 1 === value ? this.labels1 : this.labels;

		return source[ index ] || '';
	};

	Countdown.prototype.render = function () {
		let parts = splitSeconds( this.remainingSeconds() );
		let fragment = document.createDocumentFragment();

		this.element.setAttribute( 'data-seconds-left', String( this.remainingSeconds() ) );
		this.element.textContent = '';

		visibleParts( parts ).forEach( function ( part ) {
			let section = document.createElement( 'span' );
			let amount = document.createElement( 'span' );
			let period = document.createElement( 'span' );

			section.className = 'countdown-section';
			amount.className = 'countdown-amount';
			period.className = 'countdown-period';

			amount.textContent = String( part.value );
			period.textContent = this.labelFor( part.index, part.value );

			section.appendChild( amount );
			section.appendChild( period );
			fragment.appendChild( section );
		}, this );

		this.element.appendChild( fragment );
	};

	Countdown.prototype.start = function () {
		let self = this;

		this.stop();
		this.render();

		this.timer = window.setInterval( function () {
			self.render();

			if ( 0 === self.remainingSeconds() ) {
				self.stop();
			}
		}, SECOND );
	};

	Countdown.prototype.stop = function () {
		if ( this.timer ) {
			window.clearInterval( this.timer );
			this.timer = null;
		}
	};

	window.WPEMSCountdown = Countdown;
}( window ) );
