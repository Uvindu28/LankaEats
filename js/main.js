/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: js/main.js
 *
 * Shared behaviour loaded on every page (vanilla JavaScript, no libraries
 * except Bootstrap's own bundle for tooltips/modals/alerts).
 *
 * The guide's JavaScript features implemented in this file:
 *   FEATURE 1 – Dynamic content updates : FAQ toggle / expand-all / live filter, auto-closing alerts
 *   FEATURE 2 – Interactive image slider : auto-play + prev/next + dots + keyboard + swipe + pause on hover
 *   FEATURE 4 – Smooth scrolling         : anchor links (offset for sticky navbar) + back-to-top button
 *   FEATURE 5 – Event handling           : tooltips, navbar scroll state, card tilt on hover,
 *                                          delete-confirmation modal
 *   FEATURE 6 – Custom animations        : IntersectionObserver fade-ins, count-up numbers
 *
 * (Feature 3 – form validation – lives in js/validation.js and the live
 *  search/modal part of feature 1 lives in js/recipes.js.)
 */
(function () {
    'use strict';

    // Small shared namespace so the other scripts can reuse helpers.
    const LankaEats = (window.LankaEats = window.LankaEats || {});
    LankaEats.baseUrl = document.body.dataset.baseUrl || '/';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* =================================================================
     * FEATURE 2: INTERACTIVE IMAGE SLIDER (automatic + manual)
     * A custom slider – NOT Bootstrap's carousel. Markup lives in index.php.
     * ================================================================= */
    class Slider {
        constructor(root) {
            this.root = root;
            this.slides = Array.from(root.querySelectorAll('.le-slide'));
            this.dots = Array.from(root.querySelectorAll('[data-slide-to]'));
            this.progress = root.querySelector('.le-slider__progress');
            this.statusText = root.querySelector('.le-slider__status span');
            this.statusIcon = root.querySelector('.le-slider__status i');
            this.interval = parseInt(root.dataset.interval, 10) || 5000;
            this.index = 0;
            this.timer = null;
            this.hovering = false;
            this.focused = false;

            if (this.slides.length < 2) return;
            root.style.setProperty('--slide-duration', this.interval + 'ms');
            this.bindEvents();
            this.play();
        }

        bindEvents() {
            // Manual controls: previous / next buttons and the dots.
            this.root.querySelector('[data-slider-prev]').addEventListener('click', () => this.go(this.index - 1, true));
            this.root.querySelector('[data-slider-next]').addEventListener('click', () => this.go(this.index + 1, true));
            this.dots.forEach((dot) => {
                dot.addEventListener('click', () => this.go(parseInt(dot.dataset.slideTo, 10), true));
            });

            // Pause while the mouse is over the slider or it has keyboard focus.
            this.root.addEventListener('mouseenter', () => { this.hovering = true; this.pause(); });
            this.root.addEventListener('mouseleave', () => { this.hovering = false; this.play(); });
            this.root.addEventListener('focusin', () => { this.focused = true; this.pause(); });
            this.root.addEventListener('focusout', (e) => {
                if (!this.root.contains(e.relatedTarget)) { this.focused = false; this.play(); }
            });

            // Keyboard arrows when the slider is focused.
            this.root.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') { e.preventDefault(); this.go(this.index - 1, true); }
                if (e.key === 'ArrowRight') { e.preventDefault(); this.go(this.index + 1, true); }
            });

            // Touch swipe for phones.
            let startX = null;
            this.root.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
            this.root.addEventListener('touchend', (e) => {
                if (startX === null) return;
                const dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 45) this.go(this.index + (dx < 0 ? 1 : -1), true);
                startX = null;
            });

            // Don't keep sliding in a background tab.
            document.addEventListener('visibilitychange', () => (document.hidden ? this.pause() : this.play()));
        }

        /** Show slide n (wraps around). `manual` restarts the auto-play timer. */
        go(n, manual = false) {
            const total = this.slides.length;
            const next = (n + total) % total;

            this.slides.forEach((slide, i) => {
                const active = i === next;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                // Links in hidden slides must not be reachable with Tab.
                slide.querySelectorAll('a, button').forEach((el) => (active ? el.removeAttribute('tabindex') : el.setAttribute('tabindex', '-1')));
            });
            this.dots.forEach((dot, i) => {
                dot.classList.toggle('is-active', i === next);
                if (i === next) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current');
            });
            this.index = next;

            if (manual) { this.pause(); this.play(); }
        }

        play() {
            if (this.hovering || this.focused || document.hidden) return;
            clearTimeout(this.timer);
            this.root.classList.remove('is-paused');
            this.setStatus('bi-play-fill', 'Auto-playing');
            this.restartProgress();
            this.timer = setTimeout(() => { this.go(this.index + 1); this.play(); }, this.interval);
        }

        pause() {
            clearTimeout(this.timer);
            this.root.classList.add('is-paused');
            this.progress && this.progress.classList.remove('is-running');
            this.setStatus('bi-pause-fill', 'Paused');
        }

        restartProgress() {
            if (!this.progress) return;
            this.progress.classList.remove('is-running');
            void this.progress.offsetWidth; // force reflow so the CSS animation restarts
            this.progress.classList.add('is-running');
        }

        setStatus(icon, text) {
            if (this.statusIcon) this.statusIcon.className = 'bi ' + icon;
            if (this.statusText) this.statusText.textContent = text;
        }
    }

    function initSliders() {
        document.querySelectorAll('[data-slider]').forEach((el) => new Slider(el));
    }

    /* =================================================================
     * FEATURE 4: SMOOTH SCROLLING + BACK-TO-TOP
     * ================================================================= */
    function navOffset() {
        const nav = document.getElementById('siteNav');
        return (nav ? nav.offsetHeight : 0) + 12;
    }

    function smoothScrollTo(target) {
        const top = target.getBoundingClientRect().top + window.scrollY - navOffset();
        window.scrollTo({ top: Math.max(0, top), behavior: prefersReducedMotion ? 'auto' : 'smooth' });
    }

    function initSmoothScroll() {
        // One delegated listener handles every same-page anchor link.
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href*="#"]');
            if (!link || link.hasAttribute('data-bs-toggle')) return;

            const url = new URL(link.href, window.location.href);
            if (url.pathname !== window.location.pathname || !url.hash || url.hash === '#') return;

            const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
            if (!target) return;

            e.preventDefault();
            smoothScrollTo(target);
            history.pushState(null, '', url.hash);

            // Move keyboard focus to the section for accessibility.
            if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
            target.focus({ preventScroll: true });

            // Close the mobile menu after choosing a link.
            const openMenu = document.querySelector('.navbar-collapse.show');
            if (openMenu && window.bootstrap) bootstrap.Collapse.getOrCreateInstance(openMenu).hide();
        });

        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            backToTop.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
            });
        }
    }

    /* =================================================================
     * FEATURE 5: EVENT HANDLING
     * ================================================================= */

    // (a) Scroll events: navbar shadow + back-to-top visibility.
    //     requestAnimationFrame keeps the handler cheap.
    function initScrollEvents() {
        const nav = document.getElementById('siteNav');
        const backToTop = document.getElementById('backToTop');
        let ticking = false;

        const update = () => {
            const y = window.scrollY;
            if (nav) nav.classList.toggle('is-scrolled', y > 10);
            if (backToTop) backToTop.classList.toggle('is-visible', y > 450);
            ticking = false;
        };
        window.addEventListener('scroll', () => {
            if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
        }, { passive: true });
        update();
    }

    // (b) Bootstrap tooltips. Using the `selector` option means tooltips
    //     also work on cards that js/recipes.js adds later.
    function initTooltips() {
        if (!window.bootstrap) return;
        new bootstrap.Tooltip(document.body, { selector: '[data-bs-toggle="tooltip"]', trigger: 'hover focus' });
    }

    // (c) Hover effect: category cards tilt slightly towards the mouse.
    function initCardTilt() {
        if (prefersReducedMotion || !window.matchMedia('(pointer: fine)').matches) return;
        document.querySelectorAll('.cat-card').forEach((card) => {
            card.addEventListener('mousemove', (e) => {
                const r = card.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - 0.5;
                const y = (e.clientY - r.top) / r.height - 0.5;
                card.style.transform = `perspective(700px) translateY(-8px) rotateX(${(-y * 8).toFixed(2)}deg) rotateY(${(x * 8).toFixed(2)}deg)`;
            });
            card.addEventListener('mouseleave', () => { card.style.transform = ''; });
        });
    }

    // (d) Dashboard: fill the delete-confirmation modal with the recipe
    //     that was clicked (Bootstrap passes the button as relatedTarget).
    function initDeleteModal() {
        const modal = document.getElementById('deleteModal');
        if (!modal) return;
        modal.addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            if (!btn) return;
            modal.querySelector('#deleteRecipeId').value = btn.dataset.recipeId;
            modal.querySelector('#deleteRecipeTitle').textContent = btn.dataset.recipeTitle;
        });
    }

    /* =================================================================
     * FEATURE 1: DYNAMIC CONTENT UPDATES (site-wide parts)
     * ================================================================= */

    // (a) Flash messages close themselves after a few seconds.
    function initFlashMessages() {
        document.querySelectorAll('[data-autodismiss]').forEach((alert) => {
            const delay = parseInt(alert.dataset.autodismiss, 10) || 6000;
            setTimeout(() => {
                if (window.bootstrap && document.body.contains(alert)) bootstrap.Alert.getOrCreateInstance(alert).close();
            }, delay);
        });
    }

    // (b) FAQ accordion on about.php: toggle answers, expand/collapse all,
    //     and filter questions live as the user types.
    function initFaq() {
        const faq = document.querySelector('[data-faq]');
        if (!faq) return;
        const items = Array.from(faq.querySelectorAll('.faq-item'));
        const toggleAllBtn = document.getElementById('faqToggleAll');
        const filterInput = document.getElementById('faqFilter');
        const emptyMsg = document.getElementById('faqEmpty');

        const setOpen = (item, open) => {
            const btn = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');
            item.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', String(open));
            // max-height animates smoothly (CSS transition) to the content height.
            answer.style.maxHeight = open ? answer.scrollHeight + 'px' : '0px';
        };

        const syncToggleAll = () => {
            if (!toggleAllBtn) return;
            const allOpen = items.every((i) => i.classList.contains('is-open'));
            toggleAllBtn.querySelector('span').textContent = allOpen ? 'Collapse all' : 'Expand all';
            toggleAllBtn.querySelector('i').className = 'bi ' + (allOpen ? 'bi-arrows-collapse' : 'bi-arrows-expand') + ' me-1';
            toggleAllBtn.setAttribute('aria-pressed', String(allOpen));
        };

        items.forEach((item) => {
            item.querySelector('.faq-question').addEventListener('click', () => {
                setOpen(item, !item.classList.contains('is-open'));
                syncToggleAll();
            });
        });

        if (toggleAllBtn) {
            toggleAllBtn.addEventListener('click', () => {
                const openAll = !items.every((i) => i.classList.contains('is-open'));
                items.forEach((i) => setOpen(i, openAll));
                syncToggleAll();
            });
        }

        if (filterInput) {
            filterInput.addEventListener('input', () => {
                const term = filterInput.value.trim().toLowerCase();
                let visible = 0;
                items.forEach((item) => {
                    const match = item.textContent.toLowerCase().includes(term);
                    item.classList.toggle('d-none', !match);
                    if (match) visible++;
                });
                if (emptyMsg) emptyMsg.classList.toggle('d-none', visible > 0);
            });
        }
    }

    /* =================================================================
     * FEATURE 6: CUSTOM ANIMATIONS
     * ================================================================= */

    // (a) Count-up numbers ([data-count]) – eased with requestAnimationFrame.
    function countUp(el) {
        const target = parseInt(el.dataset.count, 10) || 0;
        if (prefersReducedMotion || target === 0) { el.textContent = target; return; }
        const duration = 1200;
        const start = performance.now();
        const step = (now) => {
            const t = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3); // easeOutCubic
            el.textContent = Math.round(target * eased);
            if (t < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    }

    // (b) Fade-in on scroll: IntersectionObserver adds .is-visible to .reveal
    //     elements the first time they enter the viewport (CSS does the motion).
    let revealObserver = null;

    function observeReveals(root = document) {
        const els = root.querySelectorAll('.reveal:not(.is-visible), [data-count]:not([data-counted])');
        if (!('IntersectionObserver' in window)) {
            els.forEach((el) => {
                el.classList.add('is-visible');
                if (el.dataset.count !== undefined) { el.dataset.counted = '1'; el.textContent = el.dataset.count; }
            });
            return;
        }
        if (!revealObserver) {
            revealObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    const el = entry.target;
                    el.classList.add('is-visible');
                    if (el.dataset.count !== undefined && !el.dataset.counted) {
                        el.dataset.counted = '1';
                        countUp(el);
                    }
                    revealObserver.unobserve(el);
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        }
        els.forEach((el) => revealObserver.observe(el));
    }
    LankaEats.observeReveals = observeReveals;

    /* ---------------- Start everything once the DOM is ready ---------------- */
    function init() {
        initScrollEvents();
        initSmoothScroll();
        initTooltips();
        initSliders();
        initCardTilt();
        initDeleteModal();
        initFlashMessages();
        initFaq();
        observeReveals();

        // If the page was opened with #section in the URL, scroll with the navbar offset.
        if (window.location.hash) {
            const target = document.getElementById(decodeURIComponent(window.location.hash.slice(1)));
            if (target) setTimeout(() => smoothScrollTo(target), 150);
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
