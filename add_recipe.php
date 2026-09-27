<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: add_recipe.php  (Create – members only)
 *
 * GET  → empty recipe form (includes/recipe_form.php)
 * POST → CSRF check → server-side validation (validate_recipe_input)
 *        → optional image upload (type/size checked, random filename)
 *        → INSERT with a prepared statement, owned by the logged-in user
 *        → redirect to the new recipe with a success message
 */

require_once __DIR__ . '/includes/functions.php';
require_login();

$categories  = get_categories();
$categoryIds = array_map('intval', array_column($categories, 'id'));

// Default (empty) values for the form.
$data = [
    'title' => '', 'category_id' => '', 'description' => '', 'prep_time' => '',
    'difficulty' => '', 'spice_level' => '3', 'is_vegetarian' => 0,
    'ingredients' => '', 'instructions' => '',
];
$errors = [];

if (is_post()) {
    if (post_too_large()) {
        $errors['form'] = 'The upload was too large. Please choose an image of 2 MB or less.';
    } elseif (!verify_csrf()) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } else {
        [$data, $errors] = validate_recipe_input($categoryIds);

        // Image is optional; handle_image_upload() returns null if none was chosen.
        $uploadError = null;
        $image = handle_image_upload('image', $uploadError);
        if ($uploadError) {
            $errors['image'] = $uploadError;
        }
        // Don't keep an uploaded file if the rest of the form was invalid.
        if ($errors && $image) {
            delete_recipe_image($image);
            $image = null;
            $errors['image'] = 'Your photo was fine, but file inputs can\'t be remembered – please choose it again.';
        }

        if (!$errors) {
            try {
                $stmt = db()->prepare(
                    'INSERT INTO recipes (user_id, category_id, title, description, ingredients, instructions,
                                          prep_time, difficulty, spice_level, is_vegetarian, image)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    current_user_id(),
                    (int) $data['category_id'],
                    $data['title'],
                    $data['description'],
                    $data['ingredients'],
                    $data['instructions'],
                    (int) $data['prep_time'],
                    $data['difficulty'],
                    (int) $data['spice_level'],
                    $data['is_vegetarian'],
                    $image,
                ]);
                $newId = (int) db()->lastInsertId();

                set_flash('success', '"' . $data['title'] . '" has been added to LankaEats. Thank you for sharing!');
                redirect('recipe.php?id=' . $newId);
            } catch (PDOException $ex) {
                delete_recipe_image($image);
                error_log('[LankaEats] Add recipe failed: ' . $ex->getMessage());
                $errors['form'] = 'Something went wrong while saving. Please try again.';
            }
        }
    }
}

$pageTitle  = 'Add a recipe';
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<header class="page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= e(url('dashboard.php')) ?>">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Add recipe</li>
            </ol>
        </nav>
        <h1>Share a recipe</h1>
        <p class="section-lead">Write it the way you'd explain it to a friend in your kitchen.</p>
    </div>
</header>

<section class="pb-5">
    <div class="container" style="max-width: 980px;">
        <div class="form-card">
            <?php
            $formAction  = url('add_recipe.php');
            $submitLabel = 'Publish recipe';
            require __DIR__ . '/includes/recipe_form.php';
            ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
