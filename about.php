<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: about.php  (About page)
 *
 * The story behind the project, its values, the technology used and a
 * collapsible FAQ. The FAQ is toggled by js/main.js (JS feature 1 –
 * dynamic content): each question expands/collapses its answer, there is
 * an "expand all" button and a live filter box that hides non-matching
 * questions.
 */

require_once __DIR__ . '/includes/functions.php';

$stats = get_site_stats();

// FAQ content kept in an array so the markup below stays short.
$faqs = [
    [
        'q' => 'Is LankaEats free to use?',
        'a' => 'Yes. Anyone can browse, search and read every recipe. Creating an account is also free and lets you add, edit and delete your own recipes from your dashboard.',
    ],
    [
        'q' => 'What does "vegetarian" mean on this site?',
        'a' => 'A recipe marked with the green leaf contains no meat, fish or seafood. Some vegetarian recipes, such as watalappan, still use eggs or dairy, so always check the ingredient list.',
    ],
    [
        'q' => 'How is the spice level worked out?',
        'a' => 'The cook who shares a recipe rates it from 1 (gentle, child-friendly) to 5 (proper Sri Lankan heat). Treat it as a guide – you can always add fewer chillies.',
    ],
    [
        'q' => 'Can I upload a photo of my dish?',
        'a' => 'Yes. When adding or editing a recipe you can attach a JPG, PNG or WEBP image up to 2 MB. If you skip it, we show one of our hand-drawn illustrations with the dish name instead.',
    ],
    [
        'q' => 'Where can I buy ingredients like goraka or kithul treacle?',
        'a' => 'In Sri Lanka any village kade or supermarket will have them. Abroad, look for Sri Lankan, South Indian or general Asian grocers; tamarind is a good stand-in for goraka and dark brown sugar can replace jaggery.',
    ],
    [
        'q' => 'Is my password safe?',
        'a' => 'Passwords are never stored as plain text. They are hashed with PHP\'s password_hash() function, and every form on the site is protected with a security token against forged requests.',
    ],
    [
        'q' => 'I found a mistake in a recipe. What should I do?',
        'a' => 'Please send us a note through the contact page with the recipe name and what needs fixing. If it is your own recipe, you can correct it straight from your dashboard.',
    ],
];

$pageTitle  = 'About';
$pageDesc   = 'The story behind LankaEats, a Sri Lankan digital recipe book, plus answers to common questions.';
$activePage = 'about';
require __DIR__ . '/includes/header.php';
?>

<!-- ================= BANNER ================= -->
<header class="page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About</li>
            </ol>
        </nav>
        <h1 class="reveal">Recipes that smell like home</h1>
        <p class="section-lead reveal">Why we built a recipe book for the island's kitchens – and how it works.</p>
    </div>
</header>

<!-- ================= STORY ================= -->
<section class="section pt-4">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 reveal reveal-left">
                <span class="section-eyebrow">Our story</span>
                <h2 class="section-title">Written down before it's forgotten</h2>
                <p>
                    In many Sri Lankan homes the best recipes are never written down. They live in a
                    grandmother's hands – a pinch of this, "enough" coconut milk, fry until it smells right.
                    When those cooks are no longer at the stove, the recipes quietly disappear with them.
                </p>
                <p>
                    LankaEats started as a simple idea: give those dishes a home online. Every recipe is
                    written in plain steps with real measurements, rated for spice and difficulty, and
                    tagged so a student in a hostel kitchen can find a 25-minute pol roti as easily as a
                    family planning an Avurudu table can find kokis and kavum.
                </p>
                <p class="mb-0">
                    The collection keeps growing because members add their own family favourites. Each
                    recipe belongs to the cook who shared it – only they can edit or remove it.
                </p>
            </div>
            <div class="col-lg-6 reveal reveal-right">
                <div class="story-img">
                    <div class="row g-3 text-center">
                        <?php foreach (['rice-curry' => 'Rice & Curry', 'street-food' => 'Street Food', 'breakfast' => 'Breakfast', 'sweets' => 'Sweets', 'short-eats' => 'Short Eats', 'drinks' => 'Drinks'] as $slug => $label): ?>
                            <div class="col-4">
                                <img src="<?= e(url('images/cat-' . $slug . '.svg')) ?>" alt="" class="img-fluid" width="120" height="120"
                                     data-bs-toggle="tooltip" title="<?= e($label) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-center text-muted-warm small mt-3 mb-0">Every illustration on LankaEats was drawn for this project.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= VALUES ================= -->
<section class="section bg-cream">
    <div class="container">
        <div class="section-head text-center reveal">
            <span class="section-eyebrow">What we care about</span>
            <h2 class="section-title">Our kitchen rules</h2>
        </div>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-3 reveal">
                <div class="value-card">
                    <i class="bi bi-heart" aria-hidden="true"></i>
                    <h3>Authentic, not fussy</h3>
                    <p>Real home-style cooking with ingredients you can actually find.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 reveal" style="--reveal-delay:.1s">
                <div class="value-card">
                    <i class="bi bi-book" aria-hidden="true"></i>
                    <h3>Clear steps</h3>
                    <p>One ingredient per line, one action per step – easy to follow with messy hands.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 reveal" style="--reveal-delay:.2s">
                <div class="value-card">
                    <i class="bi bi-people" aria-hidden="true"></i>
                    <h3>Community owned</h3>
                    <p>Recipes come from members, and every cook stays in charge of their own dishes.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3 reveal" style="--reveal-delay:.3s">
                <div class="value-card">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    <h3>Safe by design</h3>
                    <p>Hashed passwords, protected forms and careful handling of every upload.</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2 text-center">
            <div class="col-4 stat reveal"><strong class="text-spice" data-count="<?= (int) $stats['recipes'] ?>">0</strong><span class="text-muted-warm">recipes</span></div>
            <div class="col-4 stat reveal"><strong class="text-spice" data-count="<?= (int) $stats['members'] ?>">0</strong><span class="text-muted-warm">members</span></div>
            <div class="col-4 stat reveal"><strong class="text-spice" data-count="<?= (int) $stats['categories'] ?>">0</strong><span class="text-muted-warm">categories</span></div>
        </div>
    </div>
</section>

<!-- ================= BEHIND THE BUILD ================= -->
<section class="section">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5 reveal">
                <span class="section-eyebrow">Behind the build</span>
                <h2 class="section-title">How LankaEats is made</h2>
                <p class="text-muted-warm">
                    LankaEats is an individual mini project for ICT 2209 – Web Technologies. It is built
                    without frameworks so every part is visible and explainable.
                </p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <?php
                    $tech = [
                        ['bi-filetype-html', 'HTML5 & CSS3', 'Semantic markup and a custom spice-palette theme.'],
                        ['bi-bootstrap', 'Bootstrap 5', 'Responsive grid, modal and tooltips.'],
                        ['bi-filetype-js', 'Vanilla JavaScript', 'Slider, live search, validation and animations.'],
                        ['bi-filetype-php', 'PHP 8', 'Sessions, authentication, CRUD and a JSON API.'],
                        ['bi-database', 'MySQL + PDO', 'Prepared statements for every query.'],
                        ['bi-git', 'Git', 'Version history with meaningful commits.'],
                    ];
                    foreach ($tech as $i => [$icon, $title, $text]): ?>
                        <div class="col-sm-6 reveal" style="--reveal-delay:<?= $i * 0.06 ?>s">
                            <div class="contact-card">
                                <span class="icon"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i></span>
                                <div><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= FAQ (collapsible – js/main.js) ================= -->
<section class="section bg-cream" id="faq">
    <div class="container" style="max-width: 860px;">
        <div class="section-head text-center reveal">
            <span class="section-eyebrow">Questions</span>
            <h2 class="section-title">Frequently asked</h2>
        </div>

        <div class="d-flex flex-column flex-sm-row gap-2 mb-3 reveal">
            <div class="search-box flex-grow-1">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="faqFilter" class="visually-hidden">Filter questions</label>
                <input type="search" id="faqFilter" class="form-control" placeholder="Filter questions, e.g. password" autocomplete="off">
            </div>
            <button type="button" class="btn btn-outline-spice" id="faqToggleAll" aria-pressed="false">
                <i class="bi bi-arrows-expand me-1" aria-hidden="true"></i><span>Expand all</span>
            </button>
        </div>

        <div class="faq" data-faq>
            <?php foreach ($faqs as $i => $faq): ?>
                <div class="faq-item reveal">
                    <h3 class="m-0 h6">
                        <button type="button" class="faq-question" id="faq-q<?= $i ?>" aria-expanded="false" aria-controls="faq-a<?= $i ?>">
                            <span><?= e($faq['q']) ?></span>
                            <span class="faq-icon"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
                        </button>
                    </h3>
                    <div class="faq-answer" id="faq-a<?= $i ?>" role="region" aria-labelledby="faq-q<?= $i ?>">
                        <p><?= e($faq['a']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="text-center text-muted-warm mt-3 d-none" id="faqEmpty">No questions match your filter.</p>

        <p class="text-center mt-4 mb-0 reveal">
            Still curious? <a href="<?= e(url('contact.php')) ?>">Send us a message</a> and we'll get back to you.
        </p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
