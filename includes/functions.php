<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: includes/functions.php
 *
 * Shared helper library loaded by EVERY page. It:
 *   1. loads config + the database connection,
 *   2. starts the session (the ONLY place session_start() is called),
 *   3. provides helpers for escaping output, reading/validating input,
 *      CSRF protection, authentication guards, flash messages,
 *      recipe queries, image uploads and small view helpers.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/* =====================================================================
 * 1. SESSION BOOTSTRAP
 * ===================================================================== */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');   // reject uninitialised session IDs
    ini_set('session.use_only_cookies', '1');  // never accept IDs from the URL
    session_name('LANKAEATS_SID');
    session_set_cookie_params([
        'lifetime' => 0,                        // until the browser closes
        'path'     => '/',
        'httponly' => true,                     // JS cannot read the cookie
        'samesite' => 'Lax',                    // basic CSRF mitigation
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/* =====================================================================
 * 2. OUTPUT ESCAPING & URL HELPERS
 * ===================================================================== */

/**
 * Escape any value for safe output inside HTML (text or attribute).
 * Use this on EVERY piece of dynamic data that is echoed.
 */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an absolute URL from a path relative to the project root. */
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/** Send a redirect to a project-relative path and stop the script. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** True when the current request is a POST (form submission). */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * True when a POST was bigger than PHP's post_max_size. PHP then silently
 * empties $_POST and $_FILES, so we detect it to show a clear message
 * instead of a confusing "session expired".
 */
function post_too_large(): bool
{
    return is_post() && empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

/* =====================================================================
 * 3. INPUT & VALIDATION HELPERS
 * ===================================================================== */

/**
 * Read a trimmed string from $_POST (default) or $_GET.
 * Arrays or missing keys safely become an empty string, so a crafted
 * request can never trigger a PHP type error.
 */
function input(string $key, string $source = 'POST'): string
{
    $bag   = $source === 'GET' ? $_GET : $_POST;
    $value = $bag[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Collapse runs of whitespace and trim – used for single-line text fields. */
function clean_line(string $value): string
{
    return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

/** Multibyte-safe length check (inclusive). */
function length_between(string $value, int $min, int $max): bool
{
    $len = mb_strlen($value, 'UTF-8');
    return $len >= $min && $len <= $max;
}

/** Valid e-mail address, max 100 chars (matches the users.email column). */
function is_valid_email(string $email): bool
{
    return strlen($email) <= 100 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Username: 3–20 characters, letters, numbers and underscores only. */
function is_valid_username(string $username): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_]{3,20}$/', $username);
}

/**
 * Password policy: at least 8 characters with an upper-case letter,
 * a lower-case letter and a number (mirrors js/validation.js).
 */
function is_strong_password(string $password): bool
{
    return strlen($password) >= 8
        && strlen($password) <= 72               // bcrypt only uses 72 bytes
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password);
}

/** Split a textarea into an array of non-empty, trimmed lines. */
function lines_to_array(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $lines = array_map('trim', $lines);
    // Remove bullet characters people often paste in ("- ", "* ", "1. ").
    $lines = array_map(fn ($l) => preg_replace('/^(?:[-*•]\s+|\d+[.)]\s+)/u', '', $l) ?? $l, $lines);
    return array_values(array_filter($lines, fn ($l) => $l !== ''));
}

/* =====================================================================
 * 4. CSRF PROTECTION
 * Every POST form prints csrf_field(); every POST handler calls verify_csrf().
 * ===================================================================== */

/** Get (or lazily create) the per-session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to drop inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Constant-time comparison of the submitted token with the session token. */
function verify_csrf(): bool
{
    $submitted = $_POST['csrf_token'] ?? '';
    return is_string($submitted) && $submitted !== '' && hash_equals(csrf_token(), $submitted);
}

/* =====================================================================
 * 5. AUTHENTICATION HELPERS
 * ===================================================================== */

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return is_logged_in() ? (int) $_SESSION['user_id'] : null;
}

function current_username(): string
{
    return (string) ($_SESSION['username'] ?? '');
}

/**
 * Guard for members-only pages. Remembers where the visitor wanted to go
 * so login.php can send them back there afterwards.
 */
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    $target = basename($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php');
    if (!empty($_SERVER['QUERY_STRING'])) {
        $target .= '?' . $_SERVER['QUERY_STRING'];
    }
    $_SESSION['redirect_after_login'] = $target;
    set_flash('warning', 'Please log in to continue.');
    redirect('auth/login.php');
}

/** Guard for login/register pages: logged-in users go to the dashboard. */
function require_guest(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

/**
 * Only allow redirects to local root-level pages such as
 * "edit_recipe.php?id=3" – prevents open-redirect attacks.
 */
function safe_redirect_target(?string $target): string
{
    if ($target && preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%-]*)?$/', $target)) {
        return $target;
    }
    return 'dashboard.php';
}

/* =====================================================================
 * 6. FLASH MESSAGES (one-time notices shown after a redirect)
 * ===================================================================== */

/** Queue a message. $type = success | danger | warning | info */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Return and clear all queued messages. */
function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* =====================================================================
 * 7. RECIPE & CATEGORY DATA ACCESS  (all queries are prepared statements)
 * ===================================================================== */

/** Allowed values shared by forms, validation and the API. */
const DIFFICULTIES = ['Easy', 'Medium', 'Hard'];

const SORT_OPTIONS = [
    'newest'   => 'r.created_at DESC, r.id DESC',
    'quickest' => 'r.prep_time ASC, r.title ASC',
    'mildest'  => 'r.spice_level ASC, r.title ASC',
    'spiciest' => 'r.spice_level DESC, r.title ASC',
    'az'       => 'r.title ASC',
];

/** All categories with how many recipes each one holds. */
function get_categories(): array
{
    $stmt = db()->prepare(
        'SELECT c.id, c.name, c.slug, c.description, COUNT(r.id) AS recipe_count
           FROM categories c
      LEFT JOIN recipes r ON r.category_id = c.id
       GROUP BY c.id, c.name, c.slug, c.description
       ORDER BY c.id'
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Columns selected for recipes (joined with category + author).
 * $full adds the long ingredients/instructions text for detail views.
 */
function recipe_select_sql(bool $full = false): string
{
    return 'SELECT ' . ($full ? 'r.ingredients, r.instructions, ' : '') . 'r.id, r.user_id, r.category_id, r.title, r.description, r.prep_time,
                   r.difficulty, r.spice_level, r.is_vegetarian, r.image, r.is_featured,
                   r.created_at, c.name AS category_name, c.slug AS category_slug,
                   u.username AS author
              FROM recipes r
              JOIN categories c ON c.id = r.category_id
              JOIN users u      ON u.id = r.user_id';
}

/**
 * Search/filter recipes. Used by recipes.php (first render) AND
 * api/recipes.php (live updates), so both always agree.
 *
 * @param array $filters q, category (slug), difficulty, veg (bool), sort
 */
function search_recipes(array $filters, int $limit = 60): array
{
    $where  = [];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        // Escape LIKE wildcards so "%" typed by a user is matched literally.
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $where[] = '(r.title LIKE :q1 OR r.description LIKE :q2 OR r.ingredients LIKE :q3)';
        $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;
    }

    if (!empty($filters['category'])) {
        $where[] = 'c.slug = :category';
        $params[':category'] = (string) $filters['category'];
    }

    if (!empty($filters['difficulty']) && in_array($filters['difficulty'], DIFFICULTIES, true)) {
        $where[] = 'r.difficulty = :difficulty';
        $params[':difficulty'] = $filters['difficulty'];
    }

    if (!empty($filters['veg'])) {
        $where[] = 'r.is_vegetarian = 1';
    }

    // ORDER BY cannot be bound, so it is chosen from a fixed whitelist.
    $order = SORT_OPTIONS[$filters['sort'] ?? 'newest'] ?? SORT_OPTIONS['newest'];

    $sql = recipe_select_sql()
         . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
         . ' ORDER BY ' . $order
         . ' LIMIT :limit';

    $stmt = db()->prepare($sql);
    foreach ($params as $name => $value) {
        $stmt->bindValue($name, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Read the filter values from the query string (shared by page + API). */
function filters_from_query(): array
{
    $sort = input('sort', 'GET');
    return [
        'q'          => mb_substr(input('q', 'GET'), 0, 80, 'UTF-8'),
        'category'   => preg_replace('/[^a-z-]/', '', input('category', 'GET')),
        'difficulty' => in_array(input('difficulty', 'GET'), DIFFICULTIES, true) ? input('difficulty', 'GET') : '',
        'veg'        => input('veg', 'GET') === '1',
        'sort'       => array_key_exists($sort, SORT_OPTIONS) ? $sort : 'newest',
    ];
}

/** One full recipe (with ingredients + instructions) or null. */
function get_recipe(int $id): ?array
{
    $stmt = db()->prepare(recipe_select_sql(true) . ' WHERE r.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $recipe = $stmt->fetch();
    return $recipe ?: null;
}

/** Featured recipes for the home page. */
function get_featured_recipes(int $limit = 6): array
{
    $stmt = db()->prepare(recipe_select_sql() . ' WHERE r.is_featured = 1 ORDER BY r.id LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Simple site-wide counters for the home page stats band. */
function get_site_stats(): array
{
    $stmt = db()->prepare(
        'SELECT (SELECT COUNT(*) FROM recipes)                        AS recipes,
                (SELECT COUNT(*) FROM users)                          AS members,
                (SELECT COUNT(*) FROM categories)                     AS categories,
                (SELECT COUNT(*) FROM recipes WHERE is_vegetarian = 1) AS vegetarian'
    );
    $stmt->execute();
    return array_map('intval', $stmt->fetch());
}

/**
 * Shape a recipe row for the JSON API. Raw (unescaped) values are returned;
 * js/recipes.js escapes everything before inserting it into the page.
 */
function recipe_to_api(array $r, bool $full = false): array
{
    $data = [
        'id'            => (int) $r['id'],
        'title'         => $r['title'],
        'description'   => $r['description'],
        'category'      => $r['category_name'],
        'category_slug' => $r['category_slug'],
        'prep_time'     => (int) $r['prep_time'],
        'prep_label'    => format_prep_time((int) $r['prep_time']),
        'difficulty'    => $r['difficulty'],
        'spice_level'   => (int) $r['spice_level'],
        'is_vegetarian' => (bool) $r['is_vegetarian'],
        'image_url'     => recipe_image_url($r['image']),
        'placeholder'   => url('images/cat-' . $r['category_slug'] . '.svg'),
        'author'        => $r['author'],
        'created'       => date('j M Y', strtotime($r['created_at'])),
        'url'           => url('recipe.php?id=' . (int) $r['id']),
    ];
    if ($full) {
        $data['ingredients']  = lines_to_array($r['ingredients']);
        $data['instructions'] = lines_to_array($r['instructions']);
    }
    return $data;
}

/* =====================================================================
 * 8. RECIPE FORM VALIDATION (server side – mirrors js/validation.js)
 * ===================================================================== */

/**
 * Validate the add/edit recipe form.
 * Returns [$data, $errors]; $data holds cleaned values for sticky fields.
 */
function validate_recipe_input(array $categoryIds): array
{
    $data = [
        'title'         => clean_line(input('title')),
        'category_id'   => input('category_id'),
        'description'   => trim(input('description')),
        'prep_time'     => input('prep_time'),
        'difficulty'    => input('difficulty'),
        'spice_level'   => input('spice_level'),
        'is_vegetarian' => isset($_POST['is_vegetarian']) ? 1 : 0,
        'ingredients'   => trim(input('ingredients')),
        'instructions'  => trim(input('instructions')),
    ];
    $errors = [];

    if (!length_between($data['title'], 3, 100)) {
        $errors['title'] = 'Title must be between 3 and 100 characters.';
    }
    if (!ctype_digit($data['category_id']) || !in_array((int) $data['category_id'], $categoryIds, true)) {
        $errors['category_id'] = 'Please choose a category.';
    }
    if (!length_between($data['description'], 20, 500)) {
        $errors['description'] = 'Description must be between 20 and 500 characters.';
    }
    if (!ctype_digit($data['prep_time']) || (int) $data['prep_time'] < 1 || (int) $data['prep_time'] > 600) {
        $errors['prep_time'] = 'Preparation time must be a whole number between 1 and 600 minutes.';
    }
    if (!in_array($data['difficulty'], DIFFICULTIES, true)) {
        $errors['difficulty'] = 'Please choose a difficulty level.';
    }
    if (!ctype_digit($data['spice_level']) || (int) $data['spice_level'] < 1 || (int) $data['spice_level'] > 5) {
        $errors['spice_level'] = 'Spice level must be between 1 and 5.';
    }
    if (count(lines_to_array($data['ingredients'])) < 2 || mb_strlen($data['ingredients']) > 5000) {
        $errors['ingredients'] = 'List at least 2 ingredients, one per line (max 5000 characters).';
    }
    if (count(lines_to_array($data['instructions'])) < 2 || mb_strlen($data['instructions']) > 10000) {
        $errors['instructions'] = 'Write at least 2 steps, one per line (max 10000 characters).';
    }

    return [$data, $errors];
}

/* =====================================================================
 * 9. IMAGE UPLOADS
 * ===================================================================== */

/**
 * Validate and store an uploaded recipe photo.
 *  - optional: returns null when no file was chosen
 *  - checks upload errors, size (<= 2 MB) and the REAL MIME type (finfo),
 *    not just the file extension
 *  - saves under a random filename in images/uploads/
 *
 * @return string|null saved filename, or null if nothing uploaded / error
 */
function handle_image_upload(string $field, ?string &$error): ?string
{
    $error = null;
    $file  = $_FILES[$field] ?? null;

    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])
        || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // nothing chosen – the image is optional
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
        || $file['size'] > MAX_UPLOAD_BYTES) {
        $error = 'The image must be 2 MB or smaller.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'The image could not be uploaded. Please try again.';
        return null;
    }

    // Detect the type from the file contents.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset(ALLOWED_IMAGE_TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
        $error = 'Only JPG, PNG or WEBP images are allowed.';
        return null;
    }

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        $error = 'Upload folder is not writable.';
        return null;
    }

    // Random, unguessable filename – the user's original name is never used.
    $filename = bin2hex(random_bytes(16)) . '.' . ALLOWED_IMAGE_TYPES[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        $error = 'The image could not be saved. Please try again.';
        return null;
    }
    return $filename;
}

/** Remove a previously uploaded image file (ignores missing files). */
function delete_recipe_image(?string $filename): void
{
    // basename() guarantees we never leave the uploads folder.
    if ($filename && is_file(UPLOAD_DIR . basename($filename))) {
        unlink(UPLOAD_DIR . basename($filename));
    }
}

/* =====================================================================
 * 10. SMALL VIEW HELPERS
 * ===================================================================== */

/** Public URL of an uploaded image, or null when the recipe has none. */
function recipe_image_url(?string $filename): ?string
{
    if ($filename && is_file(UPLOAD_DIR . basename($filename))) {
        return url(UPLOAD_URL . rawurlencode(basename($filename)));
    }
    return null;
}

/**
 * The picture area of a recipe card: the uploaded photo when one exists,
 * otherwise an original illustrated placeholder showing the dish name.
 */
function recipe_media(array $r, string $extraClass = ''): string
{
    $imageUrl = recipe_image_url($r['image'] ?? null);
    $slug     = e($r['category_slug']);

    if ($imageUrl) {
        return '<div class="recipe-media ' . e($extraClass) . '">'
             . '<img src="' . e($imageUrl) . '" alt="' . e($r['title']) . '" loading="lazy">'
             . '</div>';
    }

    return '<div class="recipe-media recipe-media--placeholder cat-bg-' . $slug . ' ' . e($extraClass) . '"'
         . ' role="img" aria-label="' . e($r['title']) . ' illustration">'
         . '<img src="' . e(url('images/cat-' . $r['category_slug'] . '.svg')) . '" alt="" class="placeholder-icon">'
         . '<span class="placeholder-title">' . e($r['title']) . '</span>'
         . '</div>';
}

/**
 * Server-side error helpers for forms. When PHP validation fails the page
 * is re-rendered with the field marked invalid and the message shown in
 * the same .invalid-feedback element that js/validation.js uses.
 */
function field_class(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' is-invalid' : '';
}

function field_error(array $errors, string $key): string
{
    $has = isset($errors[$key]);
    return '<div class="invalid-feedback' . ($has ? ' d-block' : '') . '" id="' . e($key) . '-error">'
         . ($has ? e($errors[$key]) : '') . '</div>';
}

/** "45 min" / "1 hr 30 min" */
function format_prep_time(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h . ' hr' . ($m ? ' ' . $m . ' min' : '');
}

/** Row of five chilli icons, the first $level of them "hot". */
function spice_meter(int $level): string
{
    $html = '<span class="spice-meter" data-bs-toggle="tooltip" title="Spice level ' . $level . ' of 5"'
          . ' aria-label="Spice level ' . $level . ' of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="bi bi-fire' . ($i <= $level ? ' hot' : '') . '" aria-hidden="true"></i>';
    }
    return $html . '</span>';
}

/** CSS modifier for the difficulty badge. */
function difficulty_class(string $difficulty): string
{
    return 'badge-diff-' . strtolower($difficulty);
}

/**
 * Render one recipe card (used on the home page and the browse page).
 * js/recipes.js builds identical markup for live results.
 */
function render_recipe_card(array $r): string
{
    ob_start(); ?>
    <div class="col-sm-6 col-lg-4 reveal">
        <article class="recipe-card h-100" data-recipe-id="<?= (int) $r['id'] ?>">
            <button type="button" class="recipe-card__open" data-recipe-open="<?= (int) $r['id'] ?>"
                    aria-label="Quick view: <?= e($r['title']) ?>">
                <?= recipe_media($r) ?>
                <?php if ($r['is_vegetarian']): ?>
                    <span class="veg-badge" data-bs-toggle="tooltip" title="Vegetarian"><span class="visually-hidden">Vegetarian</span></span>
                <?php endif; ?>
            </button>
            <div class="recipe-card__body">
                <span class="recipe-card__cat"><?= e($r['category_name']) ?></span>
                <h3 class="recipe-card__title">
                    <a href="<?= e(url('recipe.php?id=' . (int) $r['id'])) ?>"><?= e($r['title']) ?></a>
                </h3>
                <p class="recipe-card__desc"><?= e($r['description']) ?></p>
                <div class="recipe-card__meta">
                    <span><i class="bi bi-clock"></i> <?= e(format_prep_time((int) $r['prep_time'])) ?></span>
                    <span class="badge-diff <?= e(difficulty_class($r['difficulty'])) ?>"><?= e($r['difficulty']) ?></span>
                    <?= spice_meter((int) $r['spice_level']) ?>
                </div>
            </div>
        </article>
    </div>
    <?php
    return (string) ob_get_clean();
}
