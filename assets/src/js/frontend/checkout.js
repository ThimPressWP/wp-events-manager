/* global crypto */
(function () {
    let data = window.wpemsCheckout;
    if (!data) return;

    let FORM_SELECTOR = '[data-wpems-checkout-form]';
    // Per-form state so multiple forms (or re-injected forms) each get a fresh key + debounce.
    let stateMap = new WeakMap();

    function newKey() {
        if (window.crypto && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID().replace(/-/g, '');
        }
        // Fallback for older browsers — 32 hex chars from Math.random.
        let s = '';
        for (let i = 0; i < 32; i++) {
            s += Math.floor(Math.random() * 16).toString(16);
        }
        return s;
    }

    function getState(form) {
        let s = stateMap.get(form);
        if (!s) {
            s = { key: newKey(), timer: null, bootstrapped: false };
            stateMap.set(form, s);
        }
        return s;
    }

    function call(form, action, payload) {
        let body = new URLSearchParams();
        body.append('action', 'wpems_checkout_' + action);
        body.append('nonce', data.nonce);
        body.append('event_id', form.dataset.eventId || '');
        Object.keys(payload).forEach(function (k) {
            if (payload[k] !== undefined && payload[k] !== null) {
                body.append(k, payload[k]);
            }
        });
        return fetch(data.ajaxUrl, {
            method: 'POST',
            body: body.toString(),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); });
    }

    function debounce(form, fn, ms) {
        let state = getState(form);
        return function () {
            let args = arguments;
            clearTimeout(state.timer);
            state.timer = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    function renderSummary(form, q) {
        let summaryEl = document.querySelector('[data-wpems-summary]');
        if (!summaryEl) return;
        let fields = {
            '[data-summary-subtotal]':  q.subtotal,
            '[data-summary-discount]':  q.discount_total,
            '[data-summary-tax-label]': q.tax_label,
            '[data-summary-tax]':       q.tax_total,
            '[data-summary-total]':     q.total + ' ' + q.currency,
        };
        Object.keys(fields).forEach(function (sel) {
            let el = summaryEl.querySelector(sel);
            if (el) el.textContent = fields[sel];
        });
    }

    function refreshQuote(form) {
        let qtyEl    = form.querySelector('[name="qty"]');
        let couponEl = form.querySelector('[name="coupon_code"]');
        if (!qtyEl) return;
        call(form, 'quote', {
            qty: qtyEl.value,
            coupon_code: couponEl ? couponEl.value : '',
        }).then(function (r) {
            if (r && r.success) renderSummary(form, r.data.quote);
        });
    }

    function validateCoupon(form) {
        let qtyEl       = form.querySelector('[name="qty"]');
        let couponEl    = form.querySelector('[name="coupon_code"]');
        let couponMsgEl = form.querySelector('[data-coupon-message]');
        if (!couponEl) return;
        if (!couponEl.value) {
            if (couponMsgEl) couponMsgEl.textContent = '';
            return;
        }
        call(form, 'validate_coupon', {
            qty: qtyEl ? qtyEl.value : 1,
            coupon_code: couponEl.value,
        }).then(function (r) {
            if (!r || !r.success) return;
            if (couponMsgEl) {
                couponMsgEl.textContent = r.data.valid
                    ? data.i18n.couponApplied
                    : (r.data.message || data.i18n.couponInvalid);
                couponMsgEl.dataset.state = r.data.valid ? 'ok' : 'error';
            }
            refreshQuote(form);
        });
    }

    function submitForm(form) {
        let qtyEl     = form.querySelector('[name="qty"]');
        let couponEl  = form.querySelector('[name="coupon_code"]');
        let methodEls = form.querySelectorAll('[name="payment_method"]');
        let submitBtn = form.querySelector('[type="submit"]');
        let errorEl   = form.querySelector('[data-form-error]');
        let state     = getState(form);

        if (submitBtn) submitBtn.disabled = true;
        if (errorEl) errorEl.textContent = '';

        let methodValue = '';
        methodEls.forEach(function (el) { if (el.checked) methodValue = el.value; });

        call(form, 'submit', {
            qty:             qtyEl ? qtyEl.value : 1,
            coupon_code:     couponEl ? couponEl.value : '',
            payment_method:  methodValue,
            idempotency_key: state.key,
        }).then(function (r) {
            if (!r || !r.success) {
                if (submitBtn) submitBtn.disabled = false;
                if (errorEl && r && r.data && r.data.message) errorEl.textContent = r.data.message;
                return;
            }
            if (r.data.next === 'redirect') {
                window.location.assign(r.data.url);
            }
        }).catch(function () {
            if (submitBtn) submitBtn.disabled = false;
        });
    }

    function bootstrap(form) {
        let state = getState(form);
        if (state.bootstrapped) return;
        state.bootstrapped = true;
        // Kick off the initial quote so the summary reflects qty=1 + no coupon.
        refreshQuote(form);
    }

    // Delegated handlers — survive AJAX-injected forms (modal/lightbox).
    document.addEventListener('change', function (e) {
        let form = e.target.closest && e.target.closest(FORM_SELECTOR);
        if (!form) return;
        if (e.target.matches('[name="qty"]')) {
            debounce(form, function () { refreshQuote(form); }, 200)();
        } else if (e.target.matches('[name="coupon_code"]')) {
            debounce(form, function () { validateCoupon(form); }, 250)();
        }
    });

    document.addEventListener('submit', function (e) {
        let form = e.target.closest && e.target.closest(FORM_SELECTOR);
        if (!form) return;
        e.preventDefault();
        submitForm(form);
    });

    // Bootstrap any forms already in the DOM at script load.
    document.querySelectorAll(FORM_SELECTOR).forEach(bootstrap);

    // Watch for forms injected later (modal/lightbox via load_form_register AJAX).
    if (typeof MutationObserver === 'function') {
        let observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes && m.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches(FORM_SELECTOR)) {
                        bootstrap(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll(FORM_SELECTOR).forEach(bootstrap);
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }
})();
