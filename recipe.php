<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: recipe.php?id=N  (single recipe view)
 *
 *  - Full recipe loaded by id with a prepared statement
 *  - Ingredients shown as an interactive checklist: click to strike an item
 *    through; a progress bar counts what you've gathered (js/recipes.js,
 *    remembered in localStorage)
 *  - Method can be shown / hidden with a toggle button; steps can be
 *    ticked off by clicking them
 *  - Owners see Edit / Delete buttons (delete asks for confirmation)
 *  - "More from this category" pulls related recipes from the database
 */

require_once __DIR__ . '/includes/functions.php';

$id     = filter_var(input('id', 'GET'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$recipe = $id ? get_recipe($id) : null;

if (!$recipe) {
    // Friendly 404 page
    http_response_code(404);
    $pageTitle = 'Recipe not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container" style="max-width: 640px;">
            <div class="empty-state">
                <img src="<?= e(url('images/cat-sweets.svg')) ?>" alt="">
                <h1 class="h3">We couldn't find that recipe</h1>
                <p>It may have been removed by its owner, or the link is incorrect.</p>
                <a href="<?= e(url('recipes.php')) ?>" class="btn btn-spice">Browse all recipes</a>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$ingredients  = lines_to_array($recipe['ingredients']);
$instructions = lines_to_array($recipe['instructions']);
$isOwner      = current_user_id() === (int) $recipe['user_id'];

// Related recipes from the same category (excluding this one).
$stmt = db()->prepare(recipe_select_sql() . ' WHERE r.category_id = ? AND r.id <> ? ORDER BY RAND() LIMIT 3');
$stmt->execute([$recipe['category_id'], $recipe['id']]);
$related = $stmt->fetchAll();

$pageTitle  = $recipe['title'];
$pageDesc   = mb_substr($recipe['description'], 0, 155);
$activePage = 'recipes';
require __DIR__ . '/includes/header.php';
?>

<article>
    <!-- ================= RECIPE HEADER ================= -->
    <header class="recipe-hero">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?= e(url('recipes.php')) ?>">Recipes</a></li>
                    <li class="breadcrumb-item"><a href="<?= e(url('recipes.php?category=' . $recipe['category_slug'])) ?>"><?= e($recipe['category_name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($recipe['title']) ?></li>
                </ol>
            </nav>

            <div class="row g-4 g-lg-5 align-items-center">
                <div class="col-lg-6 reveal reveal-left">
                    <?= recipe_media($recipe) ?>
                </div>
                <div class="col-lg-6 reveal reveal-right">
                    <span class="section-eyebrow"><?= e($recipe['category_name']) ?></span>
                    <h1><?= e($recipe['title']) ?></h1>
                    <p class="lead text-muted-warm"><?= e($recipe['description']) ?></p>

                    <div class="recipe-meta">
                        <span class="pill"><i class="bi bi-clock" aria-hidden="true"></i><?= e(format_prep_time((int) $recipe['prep_time'])) ?></span>
                        <span class="pill"><span class="badge-diff <?= e(difficulty_class($recipe['difficulty'])) ?>"><?= e($recipe['difficulty']) ?></span></span>
                        <span class="pill"><?= spice_meter((int) $recipe['spice_level']) ?></span>
                        <?php if ($recipe['is_vegetarian']): ?>
                            <span class="pill veg-pill">Vegetarian</span>
                        <?php endif; ?>
                        <span class="pill"><i class="bi bi-basket2" aria-hidden="true"></i><?= count($ingredients) ?> ingredients</span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="recipe-author">
                            <span class="user-chip__avatar"><?= e(strtoupper(substr($recipe['author'], 0, 1))) ?></span>
                            <span>Shared by <strong class="text-body"><?= e($recipe['author']) ?></strong><br>
                                <small><?= e(date('j F Y', strtotime($recipe['created_at']))) ?></small></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn-icon" id="copyLink" data-bs-toggle="tooltip" title="Copy link" aria-label="Copy link to this recipe">
                                <i class="bi bi-link-45deg" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="btn-icon" data-print data-bs-toggle="tooltip" title="Print recipe" aria-label="Print recipe">
                                <i class="bi bi-printer" aria-hidden="true"></i>
                            </button>
                            <?php if ($isOwner): ?>
                                <a href="<?= e(url('edit_recipe.php?id=' . (int) $recipe['id'])) ?>" class="btn-icon" data-bs-toggle="tooltip" title="Edit recipe" aria-label="Edit recipe">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </a>
                                <!-- Opens the confirmation modal (includes/delete_modal.php) -->
                                <span data-bs-toggle="tooltip" title="Delete recipe">
                                    <button type="button" class="btn-icon btn-icon-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-recipe-id="<?= (int) $recipe['id'] ?>" data-recipe-title="<?= e($recipe['title']) ?>" aria-label="Delete recipe">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ================= INGREDIENTS + METHOD ================= -->
    <section class="section pt-4">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="panel reveal" data-checklist="recipe-<?= (int) $recipe['id'] ?>">
                        <div class="panel-title">
                            <h2><i class="bi bi-basket2" aria-hidden="true"></i>Ingredients</h2>
                            <button type="button" class="btn btn-sm btn-light" data-checklist-reset>
                                <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reset
                            </button>
                        </div>
                        <div class="gather-progress" aria-hidden="true"><span></span></div>
                        <p class="gather-label" aria-live="polite">0 of <?= count($ingredients) ?> gathered</p>
                        <ul class="ingredient-list">
                            <?php foreach ($ingredients as $i => $item): ?>
                                <li>
                                    <label>
                                        <input type="checkbox" value="<?= $i ?>">
                                        <span class="tick" aria-hidden="true"></span>
                                        <span class="text"><?= e($item) ?></span>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="panel reveal" style="--reveal-delay:.1s">
                        <div class="panel-title">
                            <h2><i class="bi bi-list-check" aria-hidden="true"></i>Method</h2>
                            <!-- Show / hide toggle (js/recipes.js) -->
                            <button type="button" class="btn btn-sm btn-outline-spice" data-toggle-target="#methodSteps" aria-expanded="true" aria-controls="methodSteps">
                                <i class="bi bi-eye-slash me-1" aria-hidden="true"></i><span>Hide steps</span>
                            </button>
                        </div>
                        <div class="collapsible" id="methodSteps">
                            <p class="text-muted-warm small"><i class="bi bi-hand-index me-1" aria-hidden="true"></i>Tap a step when it's done.</p>
                            <ol class="step-list" data-steps>
                                <?php foreach ($instructions as $step): ?>
                                    <li tabindex="0"><p class="mb-0"><?= e($step) ?></p></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</article>

<!-- ================= RELATED ================= -->
<?php if ($related): ?>
    <section class="section bg-cream">
        <div class="container">
            <div class="section-head reveal">
                <span class="section-eyebrow">Keep cooking</span>
                <h2 class="section-title">More <?= e($recipe['category_name']) ?> recipes</h2>
            </div>
            <div class="row g-4">
                <?php foreach ($related as $r): ?>
                    <?= render_recipe_card($r) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
if ($isOwner) {
    require __DIR__ . '/includes/delete_modal.php';
}
require __DIR__ . '/includes/footer.php';
?>
