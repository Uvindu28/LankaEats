<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: edit_recipe.php?id=N  (Update – owner only)
 *
 * Security
 *   - require_login(): guests are sent to the login page
 *   - owner check: the recipe's user_id must match the session user,
 *     otherwise the request is refused (and the UPDATE itself also
 *     includes "AND user_id = ?" as a second safety net)
 *   - CSRF token + full server-side validation on POST
 *
 * Images: a new upload replaces the old file; "Remove current photo"
 * deletes it. Old files are only deleted after the UPDATE succeeds.
 */

require_once __DIR__ . '/includes/functions.php';
require_login();

$id     = filter_var(input('id', 'GET'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$recipe = $id ? get_recipe($id) : null;

if (!$recipe) {
    set_flash('warning', 'That recipe could not be found.');
    redirect('dashboard.php');
}
if ((int) $recipe['user_id'] !== current_user_id()) {
    set_flash('danger', 'You can only edit recipes that you shared.');
    redirect('recipe.php?id=' . (int) $recipe['id']);
}

$categories  = get_categories();
$categoryIds = array_map('intval', array_column($categories, 'id'));

// Start with the saved values.
$data = [
    'title'         => $recipe['title'],
    'category_id'   => (string) $recipe['category_id'],
    'description'   => $recipe['description'],
    'prep_time'     => (string) $recipe['prep_time'],
    'difficulty'    => $recipe['difficulty'],
    'spice_level'   => (string) $recipe['spice_level'],
    'is_vegetarian' => (int) $recipe['is_vegetarian'],
    'ingredients'   => $recipe['ingredients'],
    'instructions'  => $recipe['instructions'],
];
$errors = [];

if (is_post()) {
    if (post_too_large()) {
        $errors['form'] = 'The upload was too large. Please choose an image of 2 MB or less.';
    } elseif (!verify_csrf()) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } else {
        [$data, $errors] = validate_recipe_input($categoryIds);

        $uploadError = null;
        $newImage = handle_image_upload('image', $uploadError);
        if ($uploadError) {
            $errors['image'] = $uploadError;
        }
        if ($errors && $newImage) {
            delete_recipe_image($newImage);
            $newImage = null;
            $errors['image'] = 'Your photo was fine, but file inputs can\'t be remembered – please choose it again.';
        }

        if (!$errors) {
            // Decide which image the recipe keeps.
            $oldImage   = $recipe['image'];
            $finalImage = $oldImage;
            if ($newImage) {
                $finalImage = $newImage;
            } elseif (isset($_POST['remove_image'])) {
                $finalImage = null;
            }

            try {
                $stmt = db()->prepare(
                    'UPDATE recipes
                        SET category_id = ?, title = ?, description = ?, ingredients = ?, instructions = ?,
                            prep_time = ?, difficulty = ?, spice_level = ?, is_vegetarian = ?, image = ?
                      WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([
                    (int) $data['category_id'],
                    $data['title'],
                    $data['description'],
                    $data['ingredients'],
                    $data['instructions'],
                    (int) $data['prep_time'],
                    $data['difficulty'],
                    (int) $data['spice_level'],
                    $data['is_vegetarian'],
                    $finalImage,
                    (int) $recipe['id'],
                    current_user_id(),
                ]);

                // Remove the old file only when it is no longer used.
                if ($oldImage && $oldImage !== $finalImage) {
                    delete_recipe_image($oldImage);
                }

                set_flash('success', 'Your changes to "' . $data['title'] . '" have been saved.');
                redirect('recipe.php?id=' . (int) $recipe['id']);
            } catch (PDOException $ex) {
                delete_recipe_image($newImage);
                error_log('[LankaEats] Edit recipe failed: ' . $ex->getMessage());
                $errors['form'] = 'Something went wrong while saving. Please try again.';
            }
        }
    }
}

$pageTitle  = 'Edit ' . $recipe['title'];
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<header class="page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= e(url('dashboard.php')) ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= e(url('recipe.php?id=' . (int) $recipe['id'])) ?>"><?= e($recipe['title']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
        <h1>Edit recipe</h1>
        <p class="section-lead">Update the details of <strong><?= e($recipe['title']) ?></strong>.</p>
    </div>
</header>

<section class="pb-5">
    <div class="container" style="max-width: 980px;">
        <div class="form-card">
            <?php
            $formAction   = url('edit_recipe.php?id=' . (int) $recipe['id']);
            $submitLabel  = 'Save changes';
            $currentImage = $recipe['image'];
            require __DIR__ . '/includes/recipe_form.php';
            ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
