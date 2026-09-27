/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: js/recipes.js
 *
 * FEATURE 1 – DYNAMIC CONTENT UPDATES (recipe features)
 *   (a) Live search & filtering on recipes.php – every change calls
 *       api/recipes.php with fetch() and redraws the results WITHOUT a
 *       page reload (debounced typing, cancelled stale requests, loading
 *       and "no results" states, URL kept in sync for sharing/back button).
 *   (b) Quick-view modal – clicking any recipe card loads the full recipe
 *       from the API into a Bootstrap modal (FEATURE 5 – event handling).
 *   (c) Ingredient checklist – click to strike through, progress bar,
 *       remembered per recipe in localStorage.
 *   (d) Show / hide the method, tick off steps, copy link, print.
 *
 * All data from the API is escaped with escapeHtml() before it is placed
 * in the page, so recipe text can never inject HTML/JS (XSS).
 */
(function () {
    'use strict';

    const baseUrl = document.body.dataset.baseUrl || '/';
    const API = baseUrl + 'api/recipes.php';

    /* ---------------- Small helpers ---------------- */

    /** Escape text for safe use inside HTML. */
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    /** Wait until the user stops typing before running fn. */
    function debounce(fn, delay) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
    }

    function spiceMeter(level) {
        let icons = '';
        for (let i = 1; i <= 5; i++) icons += `<i class="bi bi-fire${i <= level ? ' hot' : ''}" aria-hidden="true"></i>`;
        return `<span class="spice-meter" data-bs-toggle="tooltip" title="Spice level ${level} of 5" aria-label="Spice level ${level} of 5">${icons}</span>`;
    }

    /** Photo if uploaded, otherwise the illustrated placeholder (mirrors recipe_media() in PHP). */
    function mediaHtml(r) {
        if (r.image_url) {
            return `<div class="recipe-media"><img src="${escapeHtml(r.image_url)}" alt="${escapeHtml(r.title)}" loading="lazy"></div>`;
        }
        return `<div class="recipe-media recipe-media--placeholder cat-bg-${escapeHtml(r.category_slug)}" role="img" aria-label="${escapeHtml(r.title)} illustration">
                    <img src="${escapeHtml(r.placeholder)}" alt="" class="placeholder-icon">
                    <span class="placeholder-title">${escapeHtml(r.title)}</span>
                </div>`;
    }

    /** One result card – same markup as render_recipe_card() in functions.php. */
    function cardHtml(r, index) {
        return `
        <div class="col-sm-6 col-lg-4 card-pop" style="--pop-delay:${Math.min(index, 8) * 0.05}s">
            <article class="recipe-card h-100" data-recipe-id="${r.id}">
                <button type="button" class="recipe-card__open" data-recipe-open="${r.id}" aria-label="Quick view: ${escapeHtml(r.title)}">
                    ${mediaHtml(r)}
                    ${r.is_vegetarian ? '<span class="veg-badge" data-bs-toggle="tooltip" title="Vegetarian"><span class="visually-hidden">Vegetarian</span></span>' : ''}
                </button>
                <div class="recipe-card__body">
                    <span class="recipe-card__cat">${escapeHtml(r.category)}</span>
                    <h3 class="recipe-card__title"><a href="${escapeHtml(r.url)}">${escapeHtml(r.title)}</a></h3>
                    <p class="recipe-card__desc">${escapeHtml(r.description)}</p>
                    <div class="recipe-card__meta">
                        <span><i class="bi bi-clock"></i> ${escapeHtml(r.prep_label)}</span>
                        <span class="badge-diff badge-diff-${escapeHtml(r.difficulty.toLowerCase())}">${escapeHtml(r.difficulty)}</span>
                        ${spiceMeter(r.spice_level)}
                    </div>
                </div>
            </article>
        </div>`;
    }

    /* =================================================================
     * (a) LIVE SEARCH & FILTERING  (recipes.php)
     * ================================================================= */
    function initLiveSearch() {
        const form = document.getElementById('recipeFilters');
        if (!form) return;

        const grid = document.getElementById('resultsGrid');
        const row = document.getElementById('resultsRow');
        const empty = document.getElementById('emptyState');
        const count = document.getElementById('resultsCount');
        const resetBtn = document.getElementById('resetFilters');
        const emptyReset = document.getElementById('emptyReset');
        const searchInput = document.getElementById('q');
        const searchBox = searchInput.closest('.search-box');
        const clearBtn = document.getElementById('searchClear');
        let controller = null; // lets us cancel an older request that is still running

        /** Build ?q=…&category=… from the current form values (empty values skipped). */
        function currentParams() {
            const params = new URLSearchParams();
            new FormData(form).forEach((value, key) => {
                if (String(value).trim() !== '' && !(key === 'sort' && value === 'newest')) params.set(key, String(value).trim());
            });
            return params;
        }

        function hasActiveFilters(params) {
            return ['q', 'category', 'difficulty', 'veg'].some((k) => params.has(k));
        }

        function describe(params, total) {
            const bits = [];
            if (params.get('q')) bits.push(`matching “${escapeHtml(params.get('q'))}”`);
            if (params.get('category')) {
                const label = form.querySelector(`label[for="cat-${CSS.escape(params.get('category'))}"]`);
                if (label) bits.push('in ' + escapeHtml(label.childNodes[0].textContent.trim()));
            }
            // Extra filters go in brackets: "… in Sweets (easy, vegetarian)"
            const extras = [];
            if (params.get('difficulty')) extras.push(escapeHtml(params.get('difficulty').toLowerCase()));
            if (params.get('veg')) extras.push('vegetarian');
            if (extras.length) bits.push(`(${extras.join(', ')})`);
            return `Showing <strong>${total}</strong> recipe${total === 1 ? '' : 's'}${bits.length ? ' ' + bits.join(' ') : ''}`;
        }

        async function load() {
            const params = currentParams();

            // Cancel the previous request so a slow old response can't overwrite a newer one.
            if (controller) controller.abort();
            controller = new AbortController();

            grid.classList.add('is-loading');
            grid.setAttribute('aria-busy', 'true');

            try {
                const res = await fetch(`${API}?${params.toString()}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.error || 'Request failed');

                row.innerHTML = data.recipes.map(cardHtml).join('');
                empty.classList.toggle('d-none', data.count > 0);
                count.innerHTML = describe(params, data.count);
                resetBtn.classList.toggle('d-none', !hasActiveFilters(params));

                // Keep the address bar in sync (shareable URL, survives refresh).
                const qs = params.toString();
                history.replaceState(null, '', 'recipes.php' + (qs ? '?' + qs : ''));
            } catch (err) {
                if (err.name === 'AbortError') return; // a newer search replaced this one
                row.innerHTML = '';
                empty.classList.add('d-none');
                count.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Could not load recipes. Please try again.</span>';
            } finally {
                grid.classList.remove('is-loading');
                grid.setAttribute('aria-busy', 'false');
            }
        }

        const loadDebounced = debounce(load, 300);

        // Typing → wait 300 ms after the last key; everything else → immediately.
        searchInput.addEventListener('input', () => {
            searchBox.classList.toggle('has-value', searchInput.value !== '');
            loadDebounced();
        });
        form.addEventListener('change', (e) => { if (e.target !== searchInput) load(); });

        // Enter key / submit → no page reload.
        form.addEventListener('submit', (e) => { e.preventDefault(); load(); });

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            searchBox.classList.remove('has-value');
            searchInput.focus();
            load();
        });

        const resetAll = (e) => {
            e.preventDefault();
            form.reset();
            // form.reset() restores the values the page was *loaded* with, so clear explicitly.
            searchInput.value = '';
            searchBox.classList.remove('has-value');
            form.querySelector('#cat-all').checked = true;
            form.querySelector('#difficulty').value = '';
            form.querySelector('#sort').value = 'newest';
            form.querySelector('#veg').checked = false;
            load();
        };
        resetBtn.addEventListener('click', resetAll);
        emptyReset.addEventListener('click', resetAll);
    }

    /* =================================================================
     * (b) QUICK-VIEW MODAL – loaded dynamically from the API
     * ================================================================= */
    function initQuickView() {
        const modalEl = document.getElementById('recipeModal');
        if (!modalEl || !window.bootstrap) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const titleEl = document.getElementById('recipeModalTitle');
        const bodyEl = document.getElementById('recipeModalBody');
        const linkEl = document.getElementById('recipeModalLink');
        const cache = new Map(); // id → recipe, so re-opening is instant

        const spinner = '<div class="text-center py-5"><div class="spinner-border text-spice" role="status"><span class="visually-hidden">Loading…</span></div></div>';

        function render(r) {
            titleEl.textContent = r.title;
            linkEl.href = r.url;
            bodyEl.innerHTML = `
                ${mediaHtml(r)}
                <div class="recipe-meta">
                    <span class="pill"><i class="bi bi-tag"></i>${escapeHtml(r.category)}</span>
                    <span class="pill"><i class="bi bi-clock"></i>${escapeHtml(r.prep_label)}</span>
                    <span class="pill"><span class="badge-diff badge-diff-${escapeHtml(r.difficulty.toLowerCase())}">${escapeHtml(r.difficulty)}</span></span>
                    <span class="pill">${spiceMeter(r.spice_level)}</span>
                    ${r.is_vegetarian ? '<span class="pill veg-pill">Vegetarian</span>' : ''}
                </div>
                <p>${escapeHtml(r.description)}</p>
                <p class="small text-muted-warm"><i class="bi bi-person me-1"></i>Shared by <strong>${escapeHtml(r.author)}</strong> on ${escapeHtml(r.created)}</p>
                <div class="row g-4">
                    <div class="col-md-5">
                        <div data-checklist="recipe-${r.id}">
                            <h3 class="h5">Ingredients</h3>
                            <div class="gather-progress" aria-hidden="true"><span></span></div>
                            <p class="gather-label" aria-live="polite"></p>
                            <ul class="ingredient-list">
                                ${r.ingredients.map((item, i) => `
                                    <li><label><input type="checkbox" value="${i}"><span class="tick" aria-hidden="true"></span><span class="text">${escapeHtml(item)}</span></label></li>`).join('')}
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h3 class="h5">Method</h3>
                        <ol class="step-list" data-steps>
                            ${r.instructions.map((s) => `<li tabindex="0"><p class="mb-0">${escapeHtml(s)}</p></li>`).join('')}
                        </ol>
                    </div>
                </div>`;
            initChecklists(bodyEl);
        }

        // Event delegation: works for server-rendered AND live-search cards.
        document.addEventListener('click', async (e) => {
            const trigger = e.target.closest('[data-recipe-open]');
            if (!trigger) return;
            e.preventDefault();
            const id = trigger.dataset.recipeOpen;

            titleEl.textContent = 'Loading…';
            bodyEl.innerHTML = spinner;
            modal.show();

            try {
                if (!cache.has(id)) {
                    const res = await fetch(`${API}?id=${encodeURIComponent(id)}`, { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (!res.ok || !data.success) throw new Error(data.error || 'Could not load recipe');
                    cache.set(id, data.recipe);
                }
                render(cache.get(id));
            } catch (err) {
                titleEl.textContent = 'Oops';
                bodyEl.innerHTML = `<div class="empty-state"><h3>Couldn't load this recipe</h3><p>${escapeHtml(err.message)}</p></div>`;
            }
        });
    }

    /* =================================================================
     * (c) INGREDIENT CHECKLIST + (d) STEPS
     * ================================================================= */
    function storageGet(key) { try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { return []; } }
    function storageSet(key, value) { try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* private mode – ignore */ } }

    function initChecklists(root = document) {
        root.querySelectorAll('[data-checklist]').forEach((list) => {
            const key = 'lankaeats-' + list.dataset.checklist;
            const boxes = Array.from(list.querySelectorAll('.ingredient-list input[type="checkbox"]'));
            const bar = list.querySelector('.gather-progress span');
            const label = list.querySelector('.gather-label');

            // Restore ticks saved from a previous visit.
            const saved = storageGet(key);
            boxes.forEach((b) => { b.checked = saved.includes(b.value); });

            const update = () => {
                const done = boxes.filter((b) => b.checked);
                const pct = boxes.length ? Math.round((done.length / boxes.length) * 100) : 0;
                if (bar) bar.style.width = pct + '%';
                if (label) {
                    label.textContent = done.length === boxes.length && boxes.length
                        ? 'Everything gathered – time to cook! 🍛'
                        : `${done.length} of ${boxes.length} gathered`;
                }
                storageSet(key, done.map((b) => b.value));
            };
            boxes.forEach((b) => b.addEventListener('change', update));

            const reset = list.querySelector('[data-checklist-reset]');
            if (reset) reset.addEventListener('click', () => { boxes.forEach((b) => { b.checked = false; }); update(); });
            update();
        });

        // Click (or Enter/Space) on a step to mark it done.
        root.querySelectorAll('[data-steps] li').forEach((li) => {
            const toggle = () => li.classList.toggle('is-done');
            li.addEventListener('click', toggle);
            li.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); }
            });
        });
    }

    /** Show / hide a section (e.g. the method on recipe.php). */
    function initToggles() {
        document.querySelectorAll('[data-toggle-target]').forEach((btn) => {
            const target = document.querySelector(btn.dataset.toggleTarget);
            if (!target) return;
            target.style.maxHeight = target.scrollHeight + 'px';

            btn.addEventListener('click', () => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                if (!expanded) target.style.maxHeight = target.scrollHeight + 'px';
                target.classList.toggle('is-collapsed', expanded);
                btn.setAttribute('aria-expanded', String(!expanded));
                btn.querySelector('span').textContent = expanded ? 'Show steps' : 'Hide steps';
                btn.querySelector('i').className = 'bi ' + (expanded ? 'bi-eye' : 'bi-eye-slash') + ' me-1';
            });
            // Content height can change when the window is resized.
            window.addEventListener('resize', () => {
                if (!target.classList.contains('is-collapsed')) target.style.maxHeight = target.scrollHeight + 'px';
            });
        });
    }

    /** Copy-link and print buttons on recipe.php. */
    function initRecipeActions() {
        const copyBtn = document.getElementById('copyLink');
        if (copyBtn) {
            copyBtn.addEventListener('click', async () => {
                let ok = false;
                try { await navigator.clipboard.writeText(window.location.href); ok = true; } catch (e) { ok = false; }
                const tip = window.bootstrap && bootstrap.Tooltip.getInstance(copyBtn);
                const msg = ok ? 'Link copied!' : 'Copy failed – use the address bar';
                if (tip) { tip.setContent({ '.tooltip-inner': msg }); tip.show(); setTimeout(() => tip.setContent({ '.tooltip-inner': 'Copy link' }), 1800); }
                copyBtn.querySelector('i').className = 'bi ' + (ok ? 'bi-check2' : 'bi-x') ;
                setTimeout(() => { copyBtn.querySelector('i').className = 'bi bi-link-45deg'; }, 1800);
            });
        }
        document.querySelectorAll('[data-print]').forEach((btn) => btn.addEventListener('click', () => window.print()));
    }

    function init() {
        initLiveSearch();
        initQuickView();
        initChecklists();
        initToggles();
        initRecipeActions();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
