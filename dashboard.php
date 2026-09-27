<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: dashboard.php  (members only)
 *
 * The page users land on after logging in:
 *   - personal welcome + "member since"
 *   - stats cards (my recipes, total recipes, my vegetarian dishes, member since)
 *   - table of MY recipes with view / edit / delete actions
 *     (delete opens a confirmation modal – includes/delete_modal.php)
 */

require_once __DIR__ . '/includes/functions.php';
require_login();

$userId = current_user_id();

// Account details
$stmt = db()->prepare('SELECT username, email, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    // The account was removed while logged in – end the stale session safely.
    $_SESSION = [];
    session_regenerate_id(true);
    set_flash('warning', 'Your account could not be found. Please log in again.');
    redirect('auth/login.php');
}

// My recipes (newest first)
$stmt = db()->prepare(recipe_select_sql() . ' WHERE r.user_id = ? ORDER BY r.created_at DESC, r.id DESC');
$stmt->execute([$userId]);
$myRecipes = $stmt->fetchAll();

// Stats
$siteStats   = get_site_stats();
$myCount     = count($myRecipes);
$myVegCount  = count(array_filter($myRecipes, fn ($r) => (int) $r['is_vegetarian'] === 1));
$memberSince = date('M Y', strtotime($user['created_at']));

// Friendly greeting based on Sri Lankan time of day.
$hour     = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$pageTitle  = 'My dashboard';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="section pt-4">
    <div class="container">

        <!-- Welcome banner -->
        <div class="dash-welcome reveal">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <p class="mb-1 fw-bold"><?= e($greeting) ?>,</p>
                    <h1 class="mb-2"><?= e($user['username']) ?> 👋</h1>
                    <p>Here's everything you've cooked up on LankaEats. Signed in as <?= e($user['email']) ?>.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?= e(url('add_recipe.php')) ?>" class="btn btn-turmeric btn-lg text-nowrap"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add a recipe</a>
                </div>
            </div>
        </div>

        <!-- Stats cards -->
        <div class="row g-3 g-lg-4 mt-1">
            <div class="col-6 col-lg-3 reveal">
                <div class="stat-card">
                    <span class="icon i-spice"><i class="bi bi-journal-richtext" aria-hidden="true"></i></span>
                    <div><strong data-count="<?= $myCount ?>"><?= $myCount ?></strong><span>My recipes</span></div>
                </div>
            </div>
            <div class="col-6 col-lg-3 reveal" style="--reveal-delay:.08s">
                <div class="stat-card">
                    <span class="icon i-turmeric"><i class="bi bi-collection" aria-hidden="true"></i></span>
                    <div><strong data-count="<?= (int) $siteStats['recipes'] ?>"><?= (int) $siteStats['recipes'] ?></strong><span>Total recipes</span></div>
                </div>
            </div>
            <div class="col-6 col-lg-3 reveal" style="--reveal-delay:.16s">
                <div class="stat-card">
                    <span class="icon i-leaf"><i class="bi bi-flower1" aria-hidden="true"></i></span>
                    <div><strong data-count="<?= $myVegCount ?>"><?= $myVegCount ?></strong><span>My vegetarian</span></div>
                </div>
            </div>
            <div class="col-6 col-lg-3 reveal" style="--reveal-delay:.24s">
                <div class="stat-card">
                    <span class="icon i-chilli"><i class="bi bi-calendar3" aria-hidden="true"></i></span>
                    <div><strong class="fs-5"><?= e($memberSince) ?></strong><span>Member since</span></div>
                </div>
            </div>
        </div>

        <!-- My recipes -->
        <div class="panel mt-4 reveal">
            <div class="panel-title">
                <h2><i class="bi bi-book" aria-hidden="true"></i>My recipes</h2>
                <?php if ($myRecipes): ?>
                    <a href="<?= e(url('recipes.php')) ?>" class="btn btn-sm btn-light">Browse everyone's recipes</a>
                <?php endif; ?>
            </div>

            <?php if (!$myRecipes): ?>
                <div class="empty-state">
                    <img src="<?= e(url('images/cat-rice-curry.svg')) ?>" alt="">
                    <h3>Your recipe book is empty</h3>
                    <p>Share your first dish – even a simple pol sambol counts!</p>
                    <a href="<?= e(url('add_recipe.php')) ?>" class="btn btn-spice"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add your first recipe</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table dash-table align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Recipe</th>
                                <th scope="col" class="d-none d-md-table-cell">Difficulty</th>
                                <th scope="col" class="d-none d-lg-table-cell">Spice</th>
                                <th scope="col" class="d-none d-sm-table-cell">Time</th>
                                <th scope="col" class="d-none d-lg-table-cell">Added</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myRecipes as $r): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="dash-thumb"><?= recipe_media($r) ?></div>
                                            <div>
                                                <a class="dash-title" href="<?= e(url('recipe.php?id=' . (int) $r['id'])) ?>"><?= e($r['title']) ?></a>
                                                <div class="small text-muted-warm">
                                                    <?= e($r['category_name']) ?>
                                                    <?php if ($r['is_vegetarian']): ?> · <span class="text-leaf fw-bold">Vegetarian</span><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell"><span class="badge-diff <?= e(difficulty_class($r['difficulty'])) ?>"><?= e($r['difficulty']) ?></span></td>
                                    <td class="d-none d-lg-table-cell"><?= spice_meter((int) $r['spice_level']) ?></td>
                                    <td class="d-none d-sm-table-cell text-nowrap"><?= e(format_prep_time((int) $r['prep_time'])) ?></td>
                                    <td class="d-none d-lg-table-cell text-nowrap small"><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
                                    <td class="text-end text-nowrap">
                                        <a href="<?= e(url('recipe.php?id=' . (int) $r['id'])) ?>" class="btn-icon" data-bs-toggle="tooltip" title="View" aria-label="View <?= e($r['title']) ?>">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </a>
                                        <a href="<?= e(url('edit_recipe.php?id=' . (int) $r['id'])) ?>" class="btn-icon" data-bs-toggle="tooltip" title="Edit" aria-label="Edit <?= e($r['title']) ?>">
                                            <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                        </a>
                                        <!-- Tooltip on a wrapper because the button itself toggles the modal -->
                                        <span data-bs-toggle="tooltip" title="Delete">
                                            <button type="button" class="btn-icon btn-icon-danger" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                    data-recipe-id="<?= (int) $r['id'] ?>" data-recipe-title="<?= e($r['title']) ?>" aria-label="Delete <?= e($r['title']) ?>">
                                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                            </button>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require __DIR__ . '/includes/delete_modal.php';
require __DIR__ . '/includes/footer.php';
?>
