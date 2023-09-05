document.addEventListener('DOMContentLoaded', function() {
    
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
                //window.location.href = 'http://localhost:10022/event-list?tp_event_order_by=low-high';
            });

        }

    });
});


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








