( function ( window, document ) {
	'use strict';

	function coerceValue( value ) {
		if ( 'true' === value ) {
			return true;
		}

		if ( 'false' === value ) {
			return false;
		}

		return value;
	}

	function coerceOptions( options ) {
		Object.keys( options ).forEach( function ( key ) {
			options[ key ] = coerceValue( options[ key ] );
		} );

		return options;
	}

	function dataOptions( element ) {
		let raw = element.getAttribute( 'data-countdown' );

		if ( ! raw ) {
			return {};
		}

		try {
			return coerceOptions( JSON.parse( raw ) );
		} catch {
			return {};
		}
	}

	function Carousel( element, options ) {
		this.element = element;
		this.options = Object.assign(
			{
				navigation: true,
				slideSpeed: 300,
				paginationSpeed: 400,
				singleItem: true
			},
			options || {},
			dataOptions( element )
		);
		this.nav = null;
		this.slides = Array.prototype.slice.call( this.element.children );

		if ( false === this.options.slide ) {
			this.element.classList.remove( 'owl-carousel' );
			return;
		}

		this.init();
	}

	Carousel.prototype.init = function () {
		this.element.classList.add( 'wpems-carousel-ready' );

		this.slides.forEach( function ( slide ) {
			slide.style.flex = this.options.singleItem ? '0 0 100%' : '0 0 auto';
		}, this );

		if ( this.options.navigation ) {
			this.createNavigation();
		}
	};

	Carousel.prototype.createNavigation = function () {
		let nav = document.createElement( 'div' );
		let previous = document.createElement( 'button' );
		let next = document.createElement( 'button' );

		nav.className = 'wpems-carousel-nav';
		previous.type = 'button';
		next.type = 'button';
		previous.className = 'wpems-carousel-prev';
		next.className = 'wpems-carousel-next';
		previous.setAttribute( 'aria-label', 'Previous slide' );
		next.setAttribute( 'aria-label', 'Next slide' );
		previous.textContent = '<';
		next.textContent = '>';

		previous.addEventListener( 'click', this.go.bind( this, -1 ) );
		next.addEventListener( 'click', this.go.bind( this, 1 ) );

		nav.appendChild( previous );
		nav.appendChild( next );
		this.element.insertAdjacentElement( 'afterend', nav );
		this.nav = nav;
	};

	Carousel.prototype.slideWidth = function () {
		if ( this.options.singleItem ) {
			return this.element.clientWidth;
		}

		if ( this.slides.length ) {
			return this.slides[0].getBoundingClientRect().width;
		}

		return this.element.clientWidth;
	};

	Carousel.prototype.go = function ( direction ) {
		this.element.scrollBy(
			{
				left: direction * this.slideWidth(),
				behavior: 'smooth'
			}
		);
	};

	Carousel.prototype.destroy = function () {
		this.element.classList.remove( 'wpems-carousel-ready' );
		this.element.removeAttribute( 'style' );
		this.slides.forEach( function ( slide ) {
			slide.style.scrollSnapAlign = '';
			slide.style.flex = '';
		} );

		if ( this.nav && this.nav.parentNode ) {
			this.nav.parentNode.removeChild( this.nav );
		}
	};

	window.WPEMSCarousel = Carousel;
}( window, document ) );
