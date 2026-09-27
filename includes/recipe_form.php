<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: includes/recipe_form.php
 *
 * The add/edit recipe form, shared by add_recipe.php and edit_recipe.php so
 * the markup and rules live in one place.
 *
 * Expects these variables from the including page:
 *   $data         array  current field values (sticky after errors)
 *   $errors       array  field => message from PHP validation
 *   $categories   array  rows from get_categories()
 *   $formAction   string URL the form posts to
 *   $submitLabel  string text on the submit button
 *   $currentImage ?string existing image filename (edit page only)
 *
 * Client-side rules are declared with data-* attributes and enforced by
 * js/validation.js; the same rules are re-checked in PHP by
 * validate_recipe_input() in includes/functions.php.
 */

$currentImage = $currentImage ?? null;
?>
<?php if (isset($errors['form'])): ?>
    <div class="alert alert-form" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= e($errors['form']) ?></div>
<?php elseif ($errors): ?>
    <div class="alert alert-form" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Please fix the highlighted fields below.</div>
<?php endif; ?>
<div class="alert alert-form d-none" role="alert" data-form-summary></div>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" data-validate novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <!-- Title -->
        <div class="col-md-8 field">
            <label for="title" class="form-label">Recipe title<span class="req">*</span></label>
            <input type="text" class="form-control<?= field_class($errors, 'title') ?>" id="title" name="title"
                   value="<?= e($data['title']) ?>" required minlength="3" maxlength="100" data-label="Title"
                   placeholder="e.g. Amma's Fish Ambul Thiyal">
            <?= field_error($errors, 'title') ?>
        </div>

        <!-- Category -->
        <div class="col-md-4 field">
            <label for="category_id" class="form-label">Category<span class="req">*</span></label>
            <select class="form-select<?= field_class($errors, 'category_id') ?>" id="category_id" name="category_id" required data-label="A category">
                <option value="">Choose…</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>"<?= (string) $data['category_id'] === (string) $cat['id'] ? ' selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'category_id') ?>
        </div>

        <!-- Description -->
        <div class="col-12 field">
            <label for="description" class="form-label">Short description<span class="req">*</span></label>
            <textarea class="form-control<?= field_class($errors, 'description') ?>" id="description" name="description" rows="3"
                      required minlength="20" maxlength="500" data-label="Description" data-counter="#descCounter"
                      placeholder="What makes this dish special? When is it eaten?"><?= e($data['description']) ?></textarea>
            <div class="d-flex justify-content-between">
                <span class="form-text">20–500 characters.</span>
                <span class="char-counter" id="descCounter" aria-live="polite"></span>
            </div>
            <?= field_error($errors, 'description') ?>
        </div>

        <!-- Prep time -->
        <div class="col-sm-6 col-lg-3 field">
            <label for="prep_time" class="form-label">Total time (minutes)<span class="req">*</span></label>
            <input type="number" class="form-control<?= field_class($errors, 'prep_time') ?>" id="prep_time" name="prep_time"
                   value="<?= e($data['prep_time']) ?>" required min="1" max="600" step="1" inputmode="numeric" data-label="Total time">
            <?= field_error($errors, 'prep_time') ?>
        </div>

        <!-- Difficulty -->
        <div class="col-sm-6 col-lg-3 field">
            <label for="difficulty" class="form-label">Difficulty<span class="req">*</span></label>
            <select class="form-select<?= field_class($errors, 'difficulty') ?>" id="difficulty" name="difficulty" required data-label="A difficulty">
                <option value="">Choose…</option>
                <?php foreach (DIFFICULTIES as $d): ?>
                    <option value="<?= e($d) ?>"<?= $data['difficulty'] === $d ? ' selected' : '' ?>><?= e($d) ?></option>
                <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'difficulty') ?>
        </div>

        <!-- Spice level (range slider with live read-out) -->
        <div class="col-sm-8 col-lg-4 field">
            <label for="spice_level" class="form-label">Spice level<span class="req">*</span></label>
            <input type="range" class="form-range spice-range<?= field_class($errors, 'spice_level') ?>" id="spice_level" name="spice_level"
                   min="1" max="5" step="1" value="<?= e($data['spice_level'] !== '' ? $data['spice_level'] : '3') ?>"
                   required data-label="Spice level" data-output="#spiceOut">
            <div class="spice-output" id="spiceOut" aria-live="polite"></div>
            <?= field_error($errors, 'spice_level') ?>
        </div>

        <!-- Vegetarian -->
        <div class="col-sm-4 col-lg-2 d-flex align-items-center">
            <div class="form-check form-switch mt-sm-4">
                <input class="form-check-input" type="checkbox" role="switch" id="is_vegetarian" name="is_vegetarian" value="1"<?= $data['is_vegetarian'] ? ' checked' : '' ?>>
                <label class="form-check-label fw-bold" for="is_vegetarian">Vegetarian</label>
            </div>
        </div>

        <!-- Ingredients -->
        <div class="col-lg-5 field">
            <label for="ingredients" class="form-label">Ingredients<span class="req">*</span></label>
            <textarea class="form-control<?= field_class($errors, 'ingredients') ?>" id="ingredients" name="ingredients" rows="10"
                      required maxlength="5000" data-min-lines="2" data-label="Ingredient list"
                      placeholder="One ingredient per line, e.g.&#10;1 cup red lentils&#10;1/2 teaspoon turmeric"><?= e($data['ingredients']) ?></textarea>
            <div class="form-text">One ingredient per line (at least 2).</div>
            <?= field_error($errors, 'ingredients') ?>
        </div>

        <!-- Instructions -->
        <div class="col-lg-7 field">
            <label for="instructions" class="form-label">Method<span class="req">*</span></label>
            <textarea class="form-control<?= field_class($errors, 'instructions') ?>" id="instructions" name="instructions" rows="10"
                      required maxlength="10000" data-min-lines="2" data-label="Method"
                      placeholder="One step per line, e.g.&#10;Rinse the lentils well.&#10;Simmer with turmeric until soft."><?= e($data['instructions']) ?></textarea>
            <div class="form-text">One step per line (at least 2). Numbers are added automatically.</div>
            <?= field_error($errors, 'instructions') ?>
        </div>

        <!-- Image upload (optional) -->
        <div class="col-12 field">
            <label for="image" class="form-label">Photo <span class="text-muted-warm fw-normal">(optional)</span></label>
            <?php if ($currentImage && recipe_image_url($currentImage)): ?>
                <div class="current-image d-flex align-items-center gap-3 mb-2">
                    <img src="<?= e(recipe_image_url($currentImage)) ?>" alt="Current photo">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                        <label class="form-check-label" for="remove_image">Remove current photo</label>
                    </div>
                </div>
            <?php endif; ?>
            <div class="image-drop">
                <i class="bi bi-image fs-2 text-spice" aria-hidden="true"></i>
                <p class="mb-2 small">Choose or drop a JPG, PNG or WEBP image (max 2 MB)<?= $currentImage ? ' to replace the current photo' : '' ?>.</p>
                <input type="file" class="form-control<?= field_class($errors, 'image') ?>" id="image" name="image"
                       accept="image/jpeg,image/png,image/webp" data-rule="image" data-max-size="<?= MAX_UPLOAD_BYTES ?>"
                       data-preview="#imagePreview" data-label="Photo">
                <div class="image-preview" id="imagePreview"></div>
            </div>
            <div class="form-text">No photo? We'll show one of our hand-drawn illustrations instead.</div>
            <?= field_error($errors, 'image') ?>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-end mt-4">
        <a href="<?= e(url('dashboard.php')) ?>" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-spice px-4"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i><?= e($submitLabel) ?></button>
    </div>
</form>
