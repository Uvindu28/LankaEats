<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: includes/footer.php
 *
 * Shared bottom of every page:
 *   - closes <main>
 *   - site footer (links + categories loaded from the database)
 *   - the reusable "quick view" recipe modal (filled by js/recipes.js)
 *   - back-to-top button
 *   - Bootstrap JS bundle and our three scripts
 */

$footerCategories = get_categories();
?>
</main>

<!-- ================= FOOTER ================= -->
<footer class="site-footer">
    <svg class="footer-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 30c120 20 240 30 360 20S600 0 720 10s240 40 360 40 240-30 360-40v60H0z" fill="currentColor"/>
    </svg>
    <div class="container">
        <div class="row g-4 py-4">
            <div class="col-lg-4">
                <a class="footer-brand" href="<?= e(url('index.php')) ?>">
                    <img src="<?= e(url('images/logo.svg')) ?>" alt="" width="40" height="40">
                    <span>Lanka<span class="brand-accent">Eats</span></span>
                </a>
                <p class="footer-text mt-3">
                    A home-grown digital recipe book celebrating the island's kitchens – from
                    grandmother's clay-pot curries to the clatter of a midnight kottu stall.
                </p>
            </div>
            <div class="col-6 col-lg-2 offset-lg-1">
                <h2 class="footer-heading">Explore</h2>
                <ul class="footer-links">
                    <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
                    <li><a href="<?= e(url('recipes.php')) ?>">All recipes</a></li>
                    <li><a href="<?= e(url('about.php')) ?>">Our story</a></li>
                    <li><a href="<?= e(url('contact.php')) ?>">Contact us</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="footer-heading">Categories</h2>
                <ul class="footer-links">
                    <?php foreach ($footerCategories as $cat): ?>
                        <li><a href="<?= e(url('recipes.php?category=' . $cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-3">
                <h2 class="footer-heading">Your kitchen</h2>
                <ul class="footer-links">
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= e(url('dashboard.php')) ?>">My dashboard</a></li>
                        <li><a href="<?= e(url('add_recipe.php')) ?>">Share a recipe</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(url('auth/register.php')) ?>">Create an account</a></li>
                        <li><a href="<?= e(url('auth/login.php')) ?>">Log in</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> LankaEats. All recipes and illustrations are original.</span>
            <span>ICT 2209 Web Technologies · Rajarata University of Sri Lanka</span>
        </div>
    </div>
</footer>

<!-- ================= RECIPE QUICK-VIEW MODAL =================
     One modal reused for every card; js/recipes.js fetches the recipe
     from api/recipes.php?id=… and fills the body. -->
<div class="modal fade recipe-modal" id="recipeModal" tabindex="-1" aria-labelledby="recipeModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h4" id="recipeModalTitle">Recipe</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="recipeModalBody">
                <div class="text-center py-5"><div class="spinner-border text-spice" role="status"><span class="visually-hidden">Loading…</span></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <a href="<?= e(url('recipes.php')) ?>" class="btn btn-spice" id="recipeModalLink">
                    Open full recipe <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Back-to-top button (shown by js/main.js after scrolling) -->
<button type="button" class="back-to-top" id="backToTop" aria-label="Back to top"
        data-bs-toggle="tooltip" data-bs-placement="left" title="Back to top">
    <i class="bi bi-arrow-up" aria-hidden="true"></i>
</button>

<!-- Bootstrap bundle (includes Popper for tooltips/dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Our scripts: each one only activates on pages that contain its elements -->
<script src="<?= e(url('js/main.js')) ?>"></script>
<script src="<?= e(url('js/validation.js')) ?>"></script>
<script src="<?= e(url('js/recipes.js')) ?>"></script>
</body>
</html>
