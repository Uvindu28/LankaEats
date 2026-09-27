<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: includes/delete_modal.php
 *
 * Bootstrap modal that asks "Are you sure?" before a recipe is deleted.
 * Included on dashboard.php and recipe.php. Any button with
 *   data-bs-toggle="modal" data-bs-target="#deleteModal"
 *   data-recipe-id="…" data-recipe-title="…"
 * opens it; js/main.js copies the id/title into the form.
 * The form POSTs to delete_recipe.php with a CSRF token.
 */
?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= e(url('delete_recipe.php')) ?>" style="border-radius: var(--radius-lg);">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="deleteRecipeId" value="">
            <div class="modal-header border-0">
                <h2 class="modal-title h5" id="deleteModalTitle"><i class="bi bi-exclamation-triangle text-danger me-2" aria-hidden="true"></i>Delete this recipe?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="mb-1">You are about to permanently delete <strong id="deleteRecipeTitle">this recipe</strong>.</p>
                <p class="text-muted-warm small mb-0">This cannot be undone. Its photo (if any) will be removed too.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep it</button>
                <button type="submit" class="btn btn-danger rounded-pill px-4"><i class="bi bi-trash3 me-1" aria-hidden="true"></i>Yes, delete</button>
            </div>
        </form>
    </div>
</div>
