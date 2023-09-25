document.addEventListener('DOMContentLoaded', function() {
    // Deal with date range filter
    const dateElement = document.querySelector('.date');
    if (dateElement) {
        const picker = new Litepicker({ 
            element: dateElement,
            singleMode: false,
            plugins: ['ranges'],
            ranges: {
                autoApply: true,
            },
        });
    }

    // Handel hover on the title of the post and show read more button
    const titles = document.querySelectorAll('.event-title');
    const countdowns = document.querySelectorAll('.event-list-counter');
    const readMores = document.querySelectorAll('.read-more');
    if(titles && countdowns && readMores) {
        titles.forEach((title, index) => {
            const countdown = countdowns[index];
            const readMore = readMores[index];

            title.addEventListener('mouseover', () => {
                countdown.style.display = 'none';
                readMore.style.display = 'block';
            });
            title.addEventListener('mouseleave', () => {
                countdown.style.display = 'block';
                readMore.style.display = 'none';
            });
        });
    }

    // Deal with price 
    document.addEventListener('click', (e) => {
        const target = e.target;
        const priceOfMin = document.querySelector('.priceOfMin');
        const priceOfMax = document.querySelector('.priceOfMax');
        const price_min = document.querySelector('.price_min');
        const price_max = document.querySelector('.price_max');
        const orderby = document.querySelector('.orderby');

        if (priceOfMin && !priceOfMin.contains(target)) {
            priceOfMin.style.display = 'none';
        }
        if (priceOfMax && !priceOfMax.contains(target)) {
            priceOfMax.style.display = 'none';
        }

        if (target.classList.contains('price_min')) {
            priceOfMin.style.display = 'block';
            priceOfMax.style.display = 'none';
        }

        if (target.classList.contains('price_max')) {
            priceOfMax.style.display = 'block';
            priceOfMin.style.display = 'none';
        }
        if(target.tagName === 'LI' && target.hasAttribute('data-min-value')) {
            const selected = target.getAttribute('data-min-value');
            price_min.value = selected;
        }
        if(target.tagName === 'LI' && target.hasAttribute('data-max-value')) {
            const selected = target.getAttribute('data-max-value');
            price_max.value = selected;
        }        

        if(orderby) {
            orderby.addEventListener('change', (e) => {
                e.preventDefault();
                const target = e.target;
                let filterEvents = {};
                filterEvents.tp_event_order_by = target.value;
    
                const currentUrl = lpGetCurrentURLNoParam();
                window.location.href = lpAddQueryArgs( currentUrl, filterEvents );
            });
        }
    });
});

// For order of event list
const lpAddQueryArgs = ( endpoint, args ) => {
	const url = new URL( endpoint );

	Object.keys( args ).forEach( ( arg ) => {
		url.searchParams.set( arg, args[ arg ] );
	} );

	return url;
};


const lpGetCurrentURLNoParam = () => {
	let currentUrl = window.location.href;
	const hasParams = currentUrl.includes( '?' );
	if ( hasParams ) {
		currentUrl = currentUrl.split( '?' )[ 0 ];
	}

	return currentUrl;
};
// Pagination
const next = document.querySelector('.next');
if(next) {
    next.innerHTML = '<span class="dashicons dashicons-arrow-right-alt"></span>';
}

const prev = document.querySelector('.prev');
if(prev) {
    prev.innerHTML = '<span class="dashicons dashicons-arrow-left-alt"></span>';
}








