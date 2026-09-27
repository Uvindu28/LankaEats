<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: delete_recipe.php  (Delete – owner only, POST + CSRF)
 *
 * There is no page to look at here: the confirmation modal
 * (includes/delete_modal.php) POSTs the recipe id and a CSRF token.
 *   - GET requests are refused (a link or image tag can't delete anything)
 *   - the recipe must belong to the logged-in user
 *   - the DELETE statement repeats the owner check (AND user_id = ?)
 *   - the recipe's uploaded photo is removed from images/uploads/
 */

require_once __DIR__ . '/includes/functions.php';
require_login();

if (!is_post()) {
    set_flash('warning', 'Recipes can only be deleted with the Delete button.');
    redirect('dashboard.php');
}
if (!verify_csrf()) {
    set_flash('danger', 'Your session expired. Please try deleting again.');
    redirect('dashboard.php');
}

$id = filter_var(input('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

// Load only if it belongs to the current user.
$stmt = db()->prepare('SELECT id, title, image FROM recipes WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id ?: 0, current_user_id()]);
$recipe = $stmt->fetch();

if (!$recipe) {
    set_flash('danger', 'That recipe was not found, or it isn\'t yours to delete.');
    redirect('dashboard.php');
}

$stmt = db()->prepare('DELETE FROM recipes WHERE id = ? AND user_id = ?');
$stmt->execute([$recipe['id'], current_user_id()]);

delete_recipe_image($recipe['image']);

set_flash('success', '"' . $recipe['title'] . '" was deleted.');
redirect('dashboard.php');
