cat > /home/claude/aquaculturesystem_final/assets/js/validations.js << 'EOF'
/**
 * Mugwe Fish Pond AMS - Form Validations
 * Client-side validation for all forms
 */

'use strict';

// ─── Run on DOM ready ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initLoginValidation();
    initRegisterValidation();
    initWaterQualityValidation();
    initPondFormValidation();
    initFeedFormValidation();
    initHarvestFormValidation();
    initPasswordConfirm();
    initPhoneFormat();
});

// ─── Helpers ──────────────────────────────────────────────────
function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('field-error');
    const span = document.createElement('span');
    span.className = 'field-error-msg';
    span.textContent = message;
    field.parentNode.appendChild(span);
}

function clearFieldError(field) {
    field.classList.remove('field-error');
    const msg = field.parentNode.querySelector('.field-error-msg');
    if (msg) msg.remove();
}

function validateRequired(field, label) {
    if (!field.value.trim()) {
        showFieldError(field, label + ' is required');
        return false;
    }
    clearFieldError(field);
    return true;
}

function validateRange(field, min, max, label) {
    const val = parseFloat(field.value);
    if (isNaN(val) || val < min || val > max) {
        showFieldError(field, label + ' must be between ' + min + ' and ' + max);
        return false;
    }
    clearFieldError(field);
    return true;
}

function validatePositive(field, label) {
    const val = parseFloat(field.value);
    if (isNaN(val) || val <= 0) {
        showFieldError(field, label + ' must be a positive number');
        return false;
    }
    clearFieldError(field);
    return true;
}

// ─── Login Form ───────────────────────────────────────────────
function initLoginValidation() {
    const form = document.querySelector('.login-form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        let ok = true;
        const u = form.querySelector('[name="username"]');
        const p = form.querySelector('[name="password"]');
        if (u && !u.value.trim()) { showFieldError(u, 'Username is required'); ok = false; }
        if (p && p.value.length < 4) { showFieldError(p, 'Password too short'); ok = false; }
        if (!ok) e.preventDefault();
    });
}

// ─── Register Form ────────────────────────────────────────────
function initRegisterValidation() {
    const form = document.querySelector('form.register-form, form[action*="register"]');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        let ok = true;
        ['username','email','phone','password'].forEach(function (name) {
            const f = form.querySelector('[name="' + name + '"]');
            if (f && !validateRequired(f, name.charAt(0).toUpperCase() + name.slice(1))) ok = false;
        });
        const email = form.querySelector('[name="email"]');
        if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            showFieldError(email, 'Invalid email address'); ok = false;
        }
        const phone = form.querySelector('[name="phone"]');
        if (phone && phone.value && !/^\+?[0-9]{9,15}$/.test(phone.value.replace(/\s/g,''))) {
            showFieldError(phone, 'Enter a valid phone number (e.g. +256772...)'); ok = false;
        }
        const pw = form.querySelector('[name="password"]');
        if (pw && pw.value && pw.value.length < 6) {
            showFieldError(pw, 'Password must be at least 6 characters'); ok = false;
        }
        if (!ok) e.preventDefault();
    });
}

// ─── Water Quality Form ───────────────────────────────────────
function initWaterQualityValidation() {
    const form = document.querySelector('form.water-quality-form, form[id="wqForm"]');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        let ok = true;
        const ph   = form.querySelector('[name="ph_level"]');
        const temp = form.querySelector('[name="water_temp"]');
        const doEl = form.querySelector('[name="dissolved_oxygen"]');
        if (ph   && !validateRange(ph,   0,   14, 'pH level'))           ok = false;
        if (temp && !validateRange(temp, 0,   45, 'Water temperature'))  ok = false;
        if (doEl && !validateRange(doEl, 0,   20, 'Dissolved Oxygen'))   ok = false;
        if (!ok) e.preventDefault();
    });

    // Live pH colour feedback
    const ph = form && form.querySelector('[name="ph_level"]');
    if (ph) {
        ph.addEventListener('input', function () {
            const v = parseFloat(this.value);
            if (isNaN(v)) return;
            this.style.borderColor =
                v < 6.5 || v > 8.5 ? '#ef4444' :
                v < 6.8 || v > 8.2 ? '#f59e0b' : '#10b981';
        });
    }
}

// ─── Pond Form ────────────────────────────────────────────────
function initPondFormValidation() {
    const form = document.querySelector('form.pond-form, form[id="pondForm"]');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        let ok = true;
        const name = form.querySelector('[name="name"]');
        const size = form.querySelector('[name="size"]');
        const depth = form.querySelector('[name="depth"]');
        if (name  && !validateRequired(name, 'Pond name')) ok = false;
        if (size  && !validatePositive(size, 'Size'))      ok = false;
        if (depth && !validatePositive(depth, 'Depth'))    ok = false;
        if (!ok) e.preventDefault();
    });
}

// ─── Feed Record Form ─────────────────────────────────────────
function initFeedFormValidation() {
    const form = document.querySelector('form.feed-form, form[id="feedForm"]');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        let ok = true;
        const qty  = form.querySelector('[name="quantity"]');
        const cost = form.querySelector('[name="cost"]');
        if (qty  && !validatePositive(qty,  'Quantity')) ok = false;
        if (cost && cost.value && !validatePositive(cost, 'Cost')) ok = false;
        if (!ok) e.preventDefault();
    });
}

// ─── Harvest Form ─────────────────────────────────────────────
function initHarvestFormValidation() {
    const form = document.querySelector('form.harvest-form, form[id="harvestForm"]');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        let ok = true;
        const qty   = form.querySelector('[name="quantity"]');
        const price = form.querySelector('[name="sale_price"]');
        if (qty   && !validatePositive(qty,   'Quantity'))   ok = false;
        if (price && !validatePositive(price, 'Sale price')) ok = false;
        if (!ok) e.preventDefault();
    });

    // Auto-calculate total revenue
    function calcRevenue() {
        const qty   = parseFloat(form.querySelector('[name="quantity"]')   && form.querySelector('[name="quantity"]').value)   || 0;
        const wt    = parseFloat(form.querySelector('[name="avg_weight"]') && form.querySelector('[name="avg_weight"]').value) || 0;
        const price = parseFloat(form.querySelector('[name="sale_price"]') && form.querySelector('[name="sale_price"]').value) || 0;
        const rev   = form.querySelector('[name="total_revenue"]');
        if (rev && qty && wt && price) {
            rev.value = Math.round(qty * wt * price);
        }
    }

    ['quantity','avg_weight','sale_price'].forEach(function (name) {
        const f = form.querySelector('[name="' + name + '"]');
        if (f) f.addEventListener('input', calcRevenue);
    });
}

// ─── Password Confirm ─────────────────────────────────────────
function initPasswordConfirm() {
    const pw1 = document.querySelector('[name="password"]');
    const pw2 = document.querySelector('[name="confirm_password"]');
    if (!pw1 || !pw2) return;
    pw2.addEventListener('input', function () {
        if (pw2.value && pw2.value !== pw1.value) {
            showFieldError(pw2, 'Passwords do not match');
        } else {
            clearFieldError(pw2);
        }
    });
}

// ─── Phone Format Helper ──────────────────────────────────────
function initPhoneFormat() {
    document.querySelectorAll('input[type="tel"]').forEach(function (input) {
        input.addEventListener('blur', function () {
            const val = this.value.replace(/\s/g, '');
            if (val.startsWith('07') || val.startsWith('06')) {
                this.value = '+256' + val.slice(1);
            }
        });
        input.placeholder = input.placeholder || '+256700000000';
    });
}

// ─── Inject field-error CSS once ─────────────────────────────
(function () {
    const s = document.createElement('style');
    s.textContent = [
        '.field-error{border-color:#ef4444!important;box-shadow:0 0 0 3px rgba(239,68,68,.1)!important;}',
        '.field-error-msg{display:block;color:#dc2626;font-size:.8rem;margin-top:.3rem;}'
    ].join('');
    document.head.appendChild(s);
}());
EOF