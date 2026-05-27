(function () {
    var data = window.wpemsCheckout;
    if (!data) return;

    var form = document.querySelector('[data-wpems-checkout-form]');
    if (!form) return;

    var qtyEl = form.querySelector('[name="qty"]');
    var couponEl = form.querySelector('[name="coupon_code"]');
    var methodEls = form.querySelectorAll('[name="payment_method"]');
    var submitBtn = form.querySelector('[type="submit"]');
    var summaryEl = document.querySelector('[data-wpems-summary]');
    var couponMsgEl = form.querySelector('[data-coupon-message]');
    var errorEl = form.querySelector('[data-form-error]');

    var idempotencyKey = crypto.randomUUID().replace(/-/g, '');
    var pendingTimer = null;

    function call(action, payload) {
        var body = new URLSearchParams({
            action: 'wpems_checkout_' + action,
            nonce: data.nonce,
            event_id: form.dataset.eventId,
        });
        Object.keys(payload).forEach(function (k) {
            body.append(k, payload[k]);
        });
        return fetch(data.ajaxUrl, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); });
    }

    function debounce(fn, ms) {
        return function () {
            var args = arguments;
            clearTimeout(pendingTimer);
            pendingTimer = setTimeout(function () { fn.apply(null, args); }, ms);
        };
    }

    function refreshQuote() {
        call('quote', {
            qty: qtyEl.value,
            coupon_code: couponEl ? couponEl.value : '',
        }).then(function (r) {
            if (!r.success) return;
            renderSummary(r.data.quote);
        });
    }

    function renderSummary(q) {
        if (!summaryEl) return;
        var el;
        el = summaryEl.querySelector('[data-summary-subtotal]');
        if (el) el.textContent = q.subtotal;
        el = summaryEl.querySelector('[data-summary-discount]');
        if (el) el.textContent = q.discount_total;
        el = summaryEl.querySelector('[data-summary-tax-label]');
        if (el) el.textContent = q.tax_label;
        el = summaryEl.querySelector('[data-summary-tax]');
        if (el) el.textContent = q.tax_total;
        el = summaryEl.querySelector('[data-summary-total]');
        if (el) el.textContent = q.total + ' ' + q.currency;
    }

    function validateCoupon() {
        if (!couponEl || !couponEl.value) {
            if (couponMsgEl) couponMsgEl.textContent = '';
            return;
        }
        call('validate_coupon', {
            qty: qtyEl.value,
            coupon_code: couponEl.value,
        }).then(function (r) {
            if (!r.success) return;
            if (couponMsgEl) {
                couponMsgEl.textContent = r.data.valid
                    ? data.i18n.couponApplied
                    : (r.data.message || data.i18n.couponInvalid);
                couponMsgEl.dataset.state = r.data.valid ? 'ok' : 'error';
            }
            refreshQuote();
        });
    }

    if (qtyEl) qtyEl.addEventListener('change', debounce(refreshQuote, 200));
    if (couponEl) couponEl.addEventListener('change', debounce(validateCoupon, 250));

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitBtn.disabled = true;
        if (errorEl) errorEl.textContent = '';

        var methodValue = '';
        methodEls.forEach(function (el) { if (el.checked) methodValue = el.value; });

        call('submit', {
            qty: qtyEl.value,
            coupon_code: couponEl ? couponEl.value : '',
            payment_method: methodValue,
            idempotency_key: idempotencyKey,
        }).then(function (r) {
            if (!r.success) {
                submitBtn.disabled = false;
                if (errorEl) errorEl.textContent = r.data.message;
                return;
            }
            if (r.data.next === 'redirect') {
                window.location.assign(r.data.url);
            }
        }).catch(function () {
            submitBtn.disabled = false;
        });
    });

    // Initial quote on page load.
    refreshQuote();
})();
