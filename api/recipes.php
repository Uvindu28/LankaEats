<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: api/recipes.php  (read-only JSON API)
 *
 * Used by js/recipes.js with fetch() so results update WITHOUT reloading
 * the page.
 *
 *   GET api/recipes.php?q=curry&category=rice-curry&difficulty=Easy&veg=1&sort=quickest
 *       → { success: true, count: 3, recipes: [ {...}, ... ] }
 *
 *   GET api/recipes.php?id=5
 *       → { success: true, recipe: { ...full details incl. ingredients[] and instructions[] } }
 *
 * All filtering happens in search_recipes() (includes/functions.php) with
 * prepared statements. Values are returned raw; the JavaScript escapes
 * them before putting them on the page.
 */

define('API_REQUEST', true); // db.php replies with JSON if MySQL is down

require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

/** Send a JSON response and stop. */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    json_response(['success' => false, 'error' => 'Only GET requests are allowed.'], 405);
}

try {
    // ---- Single recipe (quick-view modal) ----
    if (isset($_GET['id'])) {
        $id = filter_var(input('id', 'GET'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            json_response(['success' => false, 'error' => 'Invalid recipe id.'], 400);
        }
        $recipe = get_recipe($id);
        if (!$recipe) {
            json_response(['success' => false, 'error' => 'Recipe not found.'], 404);
        }
        json_response(['success' => true, 'recipe' => recipe_to_api($recipe, true)]);
    }

    // ---- Search / filter list (live search) ----
    $filters = filters_from_query();
    $rows    = search_recipes($filters);

    json_response([
        'success' => true,
        'count'   => count($rows),
        'filters' => $filters,
        'recipes' => array_map(fn ($r) => recipe_to_api($r), $rows),
    ]);
} catch (Throwable $ex) {
    error_log('[LankaEats] API error: ' . $ex->getMessage());
    json_response(['success' => false, 'error' => 'Server error. Please try again.'], 500);
}
