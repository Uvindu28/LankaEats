/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: js/validation.js
 *
 * FEATURE 3 – FORM VALIDATION (client side) for every form on the site:
 * register, login, contact and add/edit recipe.
 *
 * How it works
 *  - A form opts in with the attribute  data-validate  (and novalidate, so
 *    the browser's own bubbles don't compete with our messages).
 *  - Each field declares its rules in HTML:
 *        required                 must not be empty
 *        data-rule="email"        valid e-mail address
 *        data-rule="username"     3–20 letters, numbers or underscores
 *        data-rule="password"     8+ chars with upper, lower and a number
 *        data-rule="identifier"   username OR e-mail (login form)
 *        data-rule="image"        optional JPG/PNG/WEBP up to data-max-size bytes
 *        data-match="#password"   must equal another field
 *        minlength / maxlength    length limits
 *        min / max                numeric range (number & range inputs)
 *        data-min-lines="2"       textarea needs at least N non-empty lines
 *        data-label="Username"    friendly name used in messages
 *  - Fields are checked while typing (after the first blur) and again on
 *    submit. Errors are shown inline with Bootstrap's .is-invalid styling.
 *  - If everything passes, the form submits NORMALLY to its PHP handler,
 *    which validates everything again on the server (guide section 3.5).
 *
 * Also in this file (dynamic form helpers – FEATURE 1):
 *  - live password-strength meter + rules checklist
 *  - show/hide password buttons
 *  - live character counters
 *  - spice-level slider read-out
 *  - image preview + drag-and-drop highlight for uploads
 */
(function () {
    'use strict';

    /* ---------------- Rule patterns (mirror includes/functions.php) ---------------- */
    const PATTERNS = {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
        username: /^[A-Za-z0-9_]{3,20}$/,
    };
    const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Human-friendly field name for messages. */
    function labelOf(field) {
        if (field.dataset.label) return field.dataset.label;
        const label = field.id ? document.querySelector(`label[for="${field.id}"]`) : null;
        return label ? label.textContent.replace('*', '').trim() : 'This field';
    }

    /** Count non-empty lines in a textarea value. */
    function countLines(value) {
        return value.split(/\r?\n/).filter((line) => line.trim() !== '').length;
    }

    /**
     * Check one field against its declared rules.
     * @returns {string} an error message, or '' when the field is valid
     */
    function validateField(field) {
        const name = labelOf(field);
        const rule = field.dataset.rule;

        // Checkboxes (e.g. "I agree")
        if (field.type === 'checkbox') {
            return field.required && !field.checked ? `Please tick "${name}".` : '';
        }

        // File inputs are optional unless required; validate type + size.
        if (field.type === 'file') {
            const file = field.files && field.files[0];
            if (!file) return field.required ? `Please choose a file for ${name}.` : '';
            if (rule === 'image' && !IMAGE_TYPES.includes(file.type)) return 'Only JPG, PNG or WEBP images are allowed.';
            const max = parseInt(field.dataset.maxSize, 10);
            if (max && file.size > max) return `The image must be ${(max / 1048576).toFixed(0)} MB or smaller (yours is ${(file.size / 1048576).toFixed(1)} MB).`;
            return '';
        }

        const raw = field.value;
        const value = field.type === 'password' ? raw : raw.trim();

        if (field.required && value === '') {
            return field.tagName === 'SELECT' ? `Please choose ${name.toLowerCase()}.` : `${name} is required.`;
        }
        if (value === '') return ''; // optional + empty = fine

        // Length limits (native attributes)
        const min = parseInt(field.getAttribute('minlength'), 10);
        const max = parseInt(field.getAttribute('maxlength'), 10);
        if (min && value.length < min) return `${name} must be at least ${min} characters.`;
        if (max && value.length > max) return `${name} must be ${max} characters or fewer.`;

        // Numeric range
        if (field.type === 'number' || field.type === 'range') {
            const num = Number(value);
            const lo = field.min !== '' ? Number(field.min) : -Infinity;
            const hi = field.max !== '' ? Number(field.max) : Infinity;
            if (!Number.isInteger(num)) return `${name} must be a whole number.`;
            if (num < lo || num > hi) return `${name} must be between ${lo} and ${hi}.`;
        }

        // Named rules
        switch (rule) {
            case 'email':
                if (!PATTERNS.email.test(value)) return 'Please enter a valid e-mail address (e.g. name@example.com).';
                break;
            case 'username':
                if (!PATTERNS.username.test(value)) return 'Username must be 3–20 characters: letters, numbers or underscores only.';
                break;
            case 'identifier':
                if (value.includes('@') ? !PATTERNS.email.test(value) : !PATTERNS.username.test(value)) {
                    return 'Enter your username or a valid e-mail address.';
                }
                break;
            case 'password': {
                const missing = [];
                if (value.length < 8) missing.push('8+ characters');
                if (!/[A-Z]/.test(value)) missing.push('an upper-case letter');
                if (!/[a-z]/.test(value)) missing.push('a lower-case letter');
                if (!/\d/.test(value)) missing.push('a number');
                if (missing.length) return 'Password needs ' + missing.join(', ') + '.';
                break;
            }
        }

        // Minimum number of lines (ingredients / steps)
        const minLines = parseInt(field.dataset.minLines, 10);
        if (minLines && countLines(value) < minLines) {
            return `Please add at least ${minLines} lines to the ${name.toLowerCase()} (one item per line).`;
        }

        // Must match another field (confirm password)
        if (field.dataset.match) {
            const other = document.querySelector(field.dataset.match);
            if (other && other.value !== raw) return 'Passwords do not match.';
        }

        return '';
    }

    /** Find (or create) the element that displays this field's error. */
    function feedbackFor(field) {
        const wrapper = field.closest('.field') || field.parentElement;
        let fb = wrapper.querySelector('.invalid-feedback');
        if (!fb) {
            fb = document.createElement('div');
            fb.className = 'invalid-feedback';
            wrapper.appendChild(fb);
        }
        if (!fb.id) fb.id = (field.id || field.name) + '-error';
        return fb;
    }

    /** Paint the field valid/invalid and show the message. */
    function showResult(field, message) {
        const fb = feedbackFor(field);
        const invalid = message !== '';
        field.classList.toggle('is-invalid', invalid);
        // Only show the green "valid" state for fields that actually have a value.
        field.classList.toggle('is-valid', !invalid && field.type !== 'checkbox' && field.value !== '');
        field.setAttribute('aria-invalid', String(invalid));
        field.setAttribute('aria-describedby', fb.id);
        fb.textContent = message;
        fb.classList.toggle('d-block', invalid);
    }

    function check(field) {
        const message = validateField(field);
        showResult(field, message);
        return message === '';
    }

    /** Wire one form up. */
    function initForm(form) {
        const fields = Array.from(form.querySelectorAll('input, select, textarea')).filter(
            (f) => !['hidden', 'submit', 'button'].includes(f.type) && f.name
        );
        // The summary banner sits just above the form, inside the same card.
        const summary = (form.closest('.auth-form, .form-card') || form.parentElement).querySelector('[data-form-summary]');

        fields.forEach((field) => {
            // Validate while typing once the user has left the field once
            // (or tried to submit) – avoids shouting on the first keystroke.
            const evt = field.tagName === 'SELECT' || field.type === 'file' || field.type === 'checkbox' ? 'change' : 'input';
            field.addEventListener(evt, () => {
                if (field.dataset.touched || form.dataset.submitted) check(field);

                // Keep "confirm password" in sync when the first password changes.
                form.querySelectorAll(`[data-match="#${field.id}"]`).forEach((other) => {
                    if (other.dataset.touched || other.value) check(other);
                });
            });
            field.addEventListener('blur', () => {
                if (field.value !== '' || form.dataset.submitted) {
                    field.dataset.touched = '1';
                    check(field);
                }
            });
        });

        form.addEventListener('submit', (e) => {
            form.dataset.submitted = '1';
            const invalid = fields.filter((f) => !check(f));

            if (invalid.length) {
                // Stop the request – PHP never sees an obviously broken form.
                e.preventDefault();
                invalid[0].focus();
                const card = form.closest('.form-card, .auth-card') || form;
                card.classList.remove('shake');
                void card.offsetWidth; // restart the CSS animation
                card.classList.add('shake');
                if (summary) {
                    summary.textContent = invalid.length === 1
                        ? 'Please fix the highlighted field.'
                        : `Please fix the ${invalid.length} highlighted fields.`;
                    summary.classList.remove('d-none');
                }
                return;
            }

            // Valid → let the browser submit to PHP; prevent double submits.
            if (summary) summary.classList.add('d-none');
            const btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Please wait…';
            }
        });

        // Coming back with the browser's Back button can restore a disabled button.
        window.addEventListener('pageshow', () => {
            const btn = form.querySelector('[type="submit"]');
            if (btn && btn.dataset.originalText) { btn.disabled = false; btn.innerHTML = btn.dataset.originalText; }
        });
    }

    /* =================================================================
     * Dynamic form helpers (FEATURE 1 – dynamic content updates)
     * ================================================================= */

    // Live password-strength meter + rules checklist (register page).
    function initStrengthMeters() {
        document.querySelectorAll('[data-strength-meter]').forEach((input) => {
            const meter = document.querySelector(input.dataset.strengthMeter);
            const label = document.querySelector(input.dataset.strengthLabel);
            const rules = document.querySelector(input.dataset.strengthRules);
            const names = ['Too short', 'Weak', 'Fair', 'Good', 'Strong'];

            const update = () => {
                const v = input.value;
                const tests = {
                    length: v.length >= 8,
                    upper: /[A-Z]/.test(v),
                    lower: /[a-z]/.test(v),
                    number: /\d/.test(v),
                };
                let score = Object.values(tests).filter(Boolean).length;
                if (score === 4 && (v.length < 12 && !/[^A-Za-z0-9]/.test(v))) score = 3; // long or symbol = "Strong"
                if (v.length === 0) score = 0;

                if (meter) meter.dataset.score = String(score);
                if (label) label.textContent = v.length ? 'Strength: ' + names[score] : '';
                if (rules) {
                    Object.entries(tests).forEach(([key, ok]) => {
                        const li = rules.querySelector(`[data-check="${key}"]`);
                        if (li) li.classList.toggle('ok', ok);
                    });
                }
            };
            input.addEventListener('input', update);
            update();
        });
    }

    // Show / hide password buttons.
    function initPasswordToggles() {
        document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
            const input = document.querySelector(btn.dataset.togglePassword);
            if (!input) return;
            btn.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                btn.setAttribute('aria-pressed', String(show));
            });
        });
    }

    // Live "123 / 500" character counters.
    function initCounters() {
        document.querySelectorAll('[data-counter]').forEach((field) => {
            const out = document.querySelector(field.dataset.counter);
            const max = parseInt(field.getAttribute('maxlength'), 10);
            if (!out || !max) return;
            const update = () => {
                const len = field.value.length;
                out.textContent = `${len} / ${max}`;
                out.classList.toggle('is-near', len >= max * 0.9 && len < max);
                out.classList.toggle('is-over', len >= max);
            };
            field.addEventListener('input', update);
            update();
        });
    }

    // Spice-level range slider: show the number and a row of chilli icons.
    function initSpiceSliders() {
        document.querySelectorAll('input[type="range"][data-output]').forEach((range) => {
            const out = document.querySelector(range.dataset.output);
            const words = ['', 'Mild', 'Gentle warmth', 'Medium', 'Hot', 'Very hot'];
            const update = () => {
                const v = parseInt(range.value, 10);
                if (out) {
                    out.innerHTML = '<i class="bi bi-fire"></i>'.repeat(v) + ` ${v}/5 – ${words[v]}`;
                }
            };
            range.addEventListener('input', update);
            update();
        });
    }

    // Image preview + drag-and-drop highlight for recipe photos.
    function initImagePreviews() {
        document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
            const preview = document.querySelector(input.dataset.preview);
            const drop = input.closest('.image-drop');
            let objectUrl = null;

            input.addEventListener('change', () => {
                if (!preview) return;
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                const file = input.files && input.files[0];
                // Only preview files that pass validation.
                if (file && validateField(input) === '') {
                    objectUrl = URL.createObjectURL(file);
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = objectUrl;
                    img.alt = 'Preview of the selected image';
                    preview.appendChild(img);
                    preview.classList.add('has-image');
                } else {
                    preview.innerHTML = '';
                    preview.classList.remove('has-image');
                }
                check(input);
            });

            if (drop) {
                ['dragenter', 'dragover'].forEach((t) => drop.addEventListener(t, () => drop.classList.add('is-dragover')));
                ['dragleave', 'drop'].forEach((t) => drop.addEventListener(t, () => drop.classList.remove('is-dragover')));
            }
        });
    }

    function init() {
        document.querySelectorAll('form[data-validate]').forEach(initForm);
        initStrengthMeters();
        initPasswordToggles();
        initCounters();
        initSpiceSliders();
        initImagePreviews();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
