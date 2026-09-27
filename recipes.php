<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: recipes.php  (Browse / "Features" page)
 *
 * Search + filter all recipes:
 *   - live text search (title, description, ingredients)
 *   - category chips, difficulty select, sort order, vegetarian switch
 *
 * Progressive enhancement:
 *   - Without JavaScript the filter bar is a normal GET form and PHP renders
 *     the matching recipes below (so the page always works).
 *   - With JavaScript (js/recipes.js) every change is sent to
 *     api/recipes.php with fetch() and the results grid is redrawn WITHOUT
 *     a page reload. Clicking a card opens a quick-view modal whose content
 *     is also loaded from the API.
 */

require_once __DIR__ . '/includes/functions.php';

$filters    = filters_from_query();
$recipes    = search_recipes($filters);
$categories = get_categories();

$sortLabels = [
    'newest'   => 'Newest first',
    'quickest' => 'Quickest to make',
    'mildest'  => 'Mildest first',
    'spiciest' => 'Spiciest first',
    'az'       => 'Title A–Z',
];

$pageTitle  = 'Browse recipes';
$pageDesc   = 'Search and filter traditional Sri Lankan recipes by category, difficulty and vegetarian options.';
$activePage = 'recipes';
require __DIR__ . '/includes/header.php';
?>

<header class="page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Recipes</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1 class="reveal mb-1">Find your next favourite</h1>
                <p class="section-lead mb-0 reveal">Type an ingredient you have at home – coconut, jackfruit, prawns – and watch the list update.</p>
            </div>
            <?php if (is_logged_in()): ?>
                <a href="<?= e(url('add_recipe.php')) ?>" class="btn btn-spice reveal"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add a recipe</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<section class="pb-5">
    <div class="container">

        <!-- ================= FILTER BAR =================
             A real GET form (works without JS). js/recipes.js intercepts it. -->
        <form class="filter-panel" id="recipeFilters" method="get" action="<?= e(url('recipes.php')) ?>" role="search" aria-label="Filter recipes">
            <div class="row g-3 align-items-center">
                <div class="col-lg-5">
                    <div class="search-box<?= $filters['q'] !== '' ? ' has-value' : '' ?>">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <label for="q" class="visually-hidden">Search recipes</label>
                        <input type="search" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>"
                               placeholder="Search dishes or ingredients…" maxlength="80" autocomplete="off">
                        <button type="button" class="search-clear" id="searchClear" aria-label="Clear search"><i class="bi bi-x-circle" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="difficulty" class="visually-hidden">Difficulty</label>
                    <select class="form-select" id="difficulty" name="difficulty">
                        <option value="">Any difficulty</option>
                        <?php foreach (DIFFICULTIES as $d): ?>
                            <option value="<?= e($d) ?>"<?= $filters['difficulty'] === $d ? ' selected' : '' ?>><?= e($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-3">
                    <label for="sort" class="visually-hidden">Sort by</label>
                    <select class="form-select" id="sort" name="sort">
                        <?php foreach ($sortLabels as $key => $label): ?>
                            <option value="<?= e($key) ?>"<?= $filters['sort'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2">
                    <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="veg" name="veg" value="1"<?= $filters['veg'] ? ' checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="veg">Vegetarian</label>
                    </div>
                </div>

                <!-- Category chips (radio buttons styled as chips) -->
                <div class="col-12">
                    <fieldset class="chip-group">
                        <legend class="visually-hidden">Category</legend>
                        <input type="radio" class="btn-check" name="category" id="cat-all" value=""<?= $filters['category'] === '' ? ' checked' : '' ?>>
                        <label class="chip" for="cat-all">All</label>
                        <?php foreach ($categories as $cat): ?>
                            <input type="radio" class="btn-check" name="category" id="cat-<?= e($cat['slug']) ?>" value="<?= e($cat['slug']) ?>"<?= $filters['category'] === $cat['slug'] ? ' checked' : '' ?>>
                            <label class="chip" for="cat-<?= e($cat['slug']) ?>"><?= e($cat['name']) ?> <span class="opacity-75">(<?= (int) $cat['recipe_count'] ?>)</span></label>
                        <?php endforeach; ?>
                    </fieldset>
                </div>
            </div>
            <!-- Only needed when JavaScript is off -->
            <noscript><button type="submit" class="btn btn-spice btn-sm mt-3">Apply filters</button></noscript>
        </form>

        <!-- ================= RESULTS ================= -->
        <div class="results-bar">
            <span class="results-count" id="resultsCount" aria-live="polite">
                Showing <strong><?= count($recipes) ?></strong> recipe<?= count($recipes) === 1 ? '' : 's' ?>
            </span>
            <a href="<?= e(url('recipes.php')) ?>" class="btn btn-sm btn-outline-spice<?= ($filters['q'] || $filters['category'] || $filters['difficulty'] || $filters['veg']) ? '' : ' d-none' ?>" id="resetFilters">
                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset filters
            </a>
        </div>

        <div class="results-grid" id="resultsGrid" aria-busy="false">
            <div class="row g-4" id="resultsRow">
                <?php foreach ($recipes as $recipe): ?>
                    <?= render_recipe_card($recipe) ?>
                <?php endforeach; ?>
            </div>

            <!-- "No results" state (shown/hidden by PHP on first load, by JS afterwards) -->
            <div class="empty-state<?= $recipes ? ' d-none' : '' ?>" id="emptyState">
                <img src="<?= e(url('images/cat-rice-curry.svg')) ?>" alt="">
                <h3>No recipes match… yet</h3>
                <p>Try a different ingredient, remove a filter, or share this dish yourself.</p>
                <a href="<?= e(url('recipes.php')) ?>" class="btn btn-spice" id="emptyReset"><i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Clear all filters</a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
