<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: index.php  (Home page)
 *
 * Sections:
 *   - Hero with call-to-action buttons and live site statistics
 *   - In-page "jump to" links (smooth scrolling – js/main.js)
 *   - Custom image slider of featured dishes (auto + manual – js/main.js)
 *   - Category cards        (from the `categories` table, with recipe counts)
 *   - Featured recipes      (from the `recipes` table, is_featured = 1)
 *   - Community stats band  (count-up animation)
 *   - "How it works" steps and a call-to-action band
 */

require_once __DIR__ . '/includes/functions.php';

// ---- Data for the page (all prepared statements, see functions.php) ----
$featured   = get_featured_recipes(6);
$categories = get_categories();
$stats      = get_site_stats();

// Slider content: original illustrations in images/ + our own captions.
$slides = [
    [
        'image' => 'images/slide-rice-curry.svg',
        'alt'   => 'Illustration of rice and curry on a banana leaf',
        'tag'   => 'Rice & Curry',
        'title' => 'The Sunday banana-leaf feast',
        'text'  => 'Parippu, chicken curry, pol sambol and a crisp papadam – the plate every Sri Lankan grew up with.',
        'link'  => 'recipes.php?category=rice-curry',
    ],
    [
        'image' => 'images/slide-kottu.svg',
        'alt'   => 'Illustration of kottu roti being chopped on a griddle at night',
        'tag'   => 'Street Food',
        'title' => 'Midnight kottu, blade by blade',
        'text'  => 'Learn the rhythm behind the island\'s loudest dish – and make it on a regular frying pan.',
        'link'  => 'recipes.php?category=street-food',
    ],
    [
        'image' => 'images/slide-hoppers.svg',
        'alt'   => 'Illustration of plain and egg hoppers with lunu miris at sunrise',
        'tag'   => 'Breakfast',
        'title' => 'Hoppers with crispy lace edges',
        'text'  => 'Fermented overnight, swirled in a tiny wok and finished with a runny egg in the middle.',
        'link'  => 'recipes.php?category=breakfast',
    ],
    [
        'image' => 'images/slide-sweets.svg',
        'alt'   => 'Illustration of a brass tray of kokis and kavum beside an oil lamp',
        'tag'   => 'Sweets',
        'title' => 'An Avurudu table of sweets',
        'text'  => 'Kokis, kavum and watalappan – festive treats made with coconut, rice flour and kithul.',
        'link'  => 'recipes.php?category=sweets',
    ],
];

$pageTitle  = 'Home';
$pageDesc   = 'LankaEats is a digital recipe book of traditional Sri Lankan food – rice & curry, kottu, hoppers, sweets and more.';
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>

<!-- ================= HERO ================= -->
<section class="hero" id="top">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="hero-eyebrow reveal"><i class="bi bi-stars" aria-hidden="true"></i> Ayubowan – welcome to the island kitchen</span>
                <h1 class="reveal" style="--reveal-delay:.1s">Cook Sri Lanka's <span class="highlight">favourite</span> dishes at home</h1>
                <p class="hero-lead reveal" style="--reveal-delay:.2s">
                    From a quiet pot of parippu to the clatter of a kottu stall, LankaEats collects the
                    everyday and festive recipes of Sri Lanka – written simply, filtered by spice and time,
                    and shared by home cooks like you.
                </p>
                <div class="d-flex gap-3 flex-wrap justify-content-center justify-content-lg-start reveal" style="--reveal-delay:.3s">
                    <a href="<?= e(url('recipes.php')) ?>" class="btn btn-spice btn-lg">
                        <i class="bi bi-search me-2" aria-hidden="true"></i>Explore recipes
                    </a>
                    <a href="#how-it-works" class="btn btn-outline-spice btn-lg">How it works</a>
                </div>
                <div class="hero-stats reveal" style="--reveal-delay:.4s">
                    <div><strong data-count="<?= (int) $stats['recipes'] ?>"><?= (int) $stats['recipes'] ?></strong><span>Recipes</span></div>
                    <div><strong data-count="<?= (int) $stats['categories'] ?>"><?= (int) $stats['categories'] ?></strong><span>Categories</span></div>
                    <div><strong data-count="<?= (int) $stats['members'] ?>"><?= (int) $stats['members'] ?></strong><span>Home cooks</span></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-art reveal reveal-zoom" style="--reveal-delay:.2s">
                    <img src="<?= e(url('images/hero.svg')) ?>" alt="Illustration of rice and curry with sambol, dhal and papadam on a banana leaf" width="560" height="480">
                    <div class="hero-badge hero-badge--a"><i class="bi bi-fire text-danger" aria-hidden="true"></i> Pick your spice level</div>
                    <div class="hero-badge hero-badge--b"><i class="bi bi-clock text-spice" aria-hidden="true"></i> Many ready in 30 min</div>
                </div>
            </div>
        </div>

        <!-- In-page navigation: these anchors use JS smooth scrolling -->
        <nav class="chip-group justify-content-center justify-content-lg-start mt-4 reveal" aria-label="Jump to section">
            <a class="chip text-decoration-none" href="#dish-slider"><i class="bi bi-image me-1" aria-hidden="true"></i>Dish gallery</a>
            <a class="chip text-decoration-none" href="#categories"><i class="bi bi-grid me-1" aria-hidden="true"></i>Categories</a>
            <a class="chip text-decoration-none" href="#featured"><i class="bi bi-star-fill me-1" aria-hidden="true"></i>Featured</a>
            <a class="chip text-decoration-none" href="#how-it-works"><i class="bi bi-list-check me-1" aria-hidden="true"></i>How it works</a>
        </nav>
    </div>
</section>

<!-- ================= IMAGE SLIDER =================
     Custom slider (not Bootstrap's carousel): auto-play every 5 s,
     prev/next buttons, dots, keyboard arrows, swipe, pause on hover/focus. -->
<section class="section" id="dish-slider">
    <div class="container">
        <div class="section-head reveal">
            <span class="section-eyebrow">Dish gallery</span>
            <h2 class="section-title">A taste of the island</h2>
            <p class="section-lead">Hover to pause, use the arrows (or your keyboard's ← →) to flip through our favourite plates.</p>
        </div>

        <div class="le-slider reveal" data-slider data-interval="5000" tabindex="0"
             role="region" aria-roledescription="carousel" aria-label="Featured Sri Lankan dishes">
            <div class="le-slides">
                <?php foreach ($slides as $i => $slide): ?>
                    <div class="le-slide<?= $i === 0 ? ' is-active' : '' ?>" role="group" aria-roledescription="slide"
                         aria-label="<?= $i + 1 ?> of <?= count($slides) ?>"<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
                        <img src="<?= e(url($slide['image'])) ?>" alt="<?= e($slide['alt']) ?>" <?= $i === 0 ? '' : 'loading="lazy"' ?>>
                        <div class="le-slide__caption">
                            <span class="tag"><?= e($slide['tag']) ?></span>
                            <h3><?= e($slide['title']) ?></h3>
                            <p><?= e($slide['text']) ?></p>
                            <a class="btn btn-turmeric" href="<?= e(url($slide['link'])) ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?>>
                                See <?= e($slide['tag']) ?> recipes <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="le-slider__btn le-slider__btn--prev" data-slider-prev aria-label="Previous slide">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="le-slider__btn le-slider__btn--next" data-slider-next aria-label="Next slide">
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>

            <div class="le-slider__dots">
                <?php foreach ($slides as $i => $slide): ?>
                    <button type="button" class="le-slider__dot<?= $i === 0 ? ' is-active' : '' ?>" data-slide-to="<?= $i ?>"
                            aria-label="Show slide <?= $i + 1 ?>: <?= e($slide['title']) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>></button>
                <?php endforeach; ?>
            </div>

            <div class="le-slider__status" aria-live="polite"><i class="bi bi-play-fill" aria-hidden="true"></i><span>Auto-playing</span></div>
            <div class="le-slider__progress"></div>
        </div>
    </div>
</section>

<!-- ================= CATEGORIES (from DB) ================= -->
<section class="section pt-0" id="categories">
    <div class="container">
        <div class="section-head text-center reveal">
            <span class="section-eyebrow">Browse by craving</span>
            <h2 class="section-title">Six kitchens, one island</h2>
            <p class="section-lead">Every category links straight to a filtered list of recipes.</p>
        </div>
        <div class="row g-3 g-md-4 row-cols-2 row-cols-md-3 row-cols-xl-6">
            <?php foreach ($categories as $i => $cat): ?>
                <div class="col reveal" style="--reveal-delay:<?= $i * 0.07 ?>s">
                    <a class="cat-card" href="<?= e(url('recipes.php?category=' . $cat['slug'])) ?>">
                        <img src="<?= e(url('images/cat-' . $cat['slug'] . '.svg')) ?>" alt="" width="86" height="86">
                        <h3><?= e($cat['name']) ?></h3>
                        <span class="count"><?= (int) $cat['recipe_count'] ?> recipe<?= (int) $cat['recipe_count'] === 1 ? '' : 's' ?></span>
                        <p class="d-none d-md-block"><?= e($cat['description']) ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================= FEATURED RECIPES (from DB) ================= -->
<section class="section bg-cream" id="featured">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 section-head reveal">
            <div>
                <span class="section-eyebrow">Cook's picks</span>
                <h2 class="section-title mb-0">Featured recipes</h2>
            </div>
            <a href="<?= e(url('recipes.php')) ?>" class="btn btn-outline-spice">View all recipes <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
        </div>

        <?php if ($featured): ?>
            <div class="row g-4">
                <?php foreach ($featured as $recipe): ?>
                    <?= render_recipe_card($recipe) ?>
                <?php endforeach; ?>
            </div>
            <p class="text-center text-muted-warm mt-4 mb-0 small"><i class="bi bi-eye me-1" aria-hidden="true"></i>Tip: click a picture for a quick view without leaving the page.</p>
        <?php else: ?>
            <div class="empty-state">
                <img src="<?= e(url('images/logo.svg')) ?>" alt="">
                <h3>No featured recipes yet</h3>
                <p>Check back soon – the kitchen is warming up.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ================= STATS BAND (count-up animation) ================= -->
<section class="section-sm">
    <div class="container">
        <div class="stats-band reveal reveal-zoom">
            <div class="row g-4">
                <div class="col-6 col-md-3 stat"><strong data-count="<?= (int) $stats['recipes'] ?>">0</strong><span>Recipes shared</span></div>
                <div class="col-6 col-md-3 stat"><strong data-count="<?= (int) $stats['vegetarian'] ?>">0</strong><span>Vegetarian dishes</span></div>
                <div class="col-6 col-md-3 stat"><strong data-count="<?= (int) $stats['members'] ?>">0</strong><span>Home cooks</span></div>
                <div class="col-6 col-md-3 stat"><strong data-count="<?= (int) $stats['categories'] ?>">0</strong><span>Food categories</span></div>
            </div>
        </div>
    </div>
</section>

<!-- ================= HOW IT WORKS ================= -->
<section class="section" id="how-it-works">
    <div class="container">
        <div class="section-head text-center reveal">
            <span class="section-eyebrow">How it works</span>
            <h2 class="section-title">From craving to curry in three steps</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4 reveal">
                <div class="how-step">
                    <div class="num">1</div>
                    <h3>Find your dish</h3>
                    <p>Search by name or ingredient and filter by category, difficulty or vegetarian – results update as you type.</p>
                </div>
            </div>
            <div class="col-md-4 reveal" style="--reveal-delay:.1s">
                <div class="how-step">
                    <div class="num">2</div>
                    <h3>Cook with a checklist</h3>
                    <p>Tick off ingredients as you gather them and hide the method until you are ready to start cooking.</p>
                </div>
            </div>
            <div class="col-md-4 reveal" style="--reveal-delay:.2s">
                <div class="how-step">
                    <div class="num">3</div>
                    <h3>Share your family recipe</h3>
                    <p>Create a free account to add, edit and manage your own recipes – with a photo if you like.</p>
                </div>
            </div>
        </div>

        <div class="cta-band mt-5 reveal">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <?php if (is_logged_in()): ?>
                        <h2 class="h3">Got a recipe your amma swears by, <?= e(current_username()) ?>?</h2>
                        <p class="mb-0">Add it to LankaEats and keep it safe for the next generation of cooks.</p>
                    <?php else: ?>
                        <h2 class="h3">Got a recipe your amma swears by?</h2>
                        <p class="mb-0">Join LankaEats for free and share it with cooks across the island and beyond.</p>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= e(url('add_recipe.php')) ?>" class="btn btn-turmeric btn-lg"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Share a recipe</a>
                    <?php else: ?>
                        <a href="<?= e(url('auth/register.php')) ?>" class="btn btn-turmeric btn-lg"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Create free account</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
