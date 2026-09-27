<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: includes/header.php
 *
 * Shared top of every page: <head> (fonts, Bootstrap 5, icons, custom CSS),
 * the responsive navigation bar and the flash-message stack.
 *
 * Before including this file a page may set:
 *   $pageTitle  – text for the <title> tag
 *   $pageDesc   – meta description
 *   $activePage – key of the nav item to highlight (home, recipes, about, contact, dashboard, login, register)
 *   $bodyClass  – extra CSS class(es) for <body>
 *
 * The navbar changes depending on the session:
 *   guest     → "Log in" + "Join free"
 *   logged in → "Dashboard" + user chip + "Log out" (POST form with CSRF token)
 */

$pageTitle  = $pageTitle  ?? 'Sri Lankan Digital Recipe Book';
$pageDesc   = $pageDesc   ?? 'LankaEats – browse, search and share traditional Sri Lankan recipes, from rice & curry to kokis.';
$activePage = $activePage ?? '';
$bodyClass  = $bodyClass  ?? '';

// Main navigation: key => [file, label, icon]
$navItems = [
    'home'    => ['index.php',   'Home',    'bi-house'],
    'recipes' => ['recipes.php', 'Recipes', 'bi-journal-richtext'],
    'about'   => ['about.php',   'About',   'bi-info-circle'],
    'contact' => ['contact.php', 'Contact', 'bi-chat-dots'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="theme-color" content="#9A4A24">
    <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>

    <!-- Mark that JavaScript is available so CSS can safely hide elements
         that JS will animate in (content stays visible without JS). -->
    <script>document.documentElement.classList.add('js');</script>

    <link rel="icon" type="image/svg+xml" href="<?= e(url('images/logo.svg')) ?>">

    <!-- Google Fonts: Fraunces (headings) + Manrope (body text) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 + Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Our own theme (loaded last so it overrides Bootstrap) -->
    <link rel="stylesheet" href="<?= e(url('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>" data-base-url="<?= e(BASE_URL) ?>">

<a class="skip-link" href="#main">Skip to main content</a>

<!-- ================= NAVIGATION ================= -->
<nav class="navbar navbar-expand-lg site-nav sticky-top" id="siteNav" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="<?= e(url('index.php')) ?>">
            <img src="<?= e(url('images/logo.svg')) ?>" alt="" width="42" height="42">
            <span>Lanka<span class="brand-accent">Eats</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="toggler-bar"></span><span class="toggler-bar"></span><span class="toggler-bar"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-lg-auto">
                <?php foreach ($navItems as $key => [$file, $label, $icon]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === $key ? ' active' : '' ?>"
                           href="<?= e(url($file)) ?>"<?= $activePage === $key ? ' aria-current="page"' : '' ?>>
                            <i class="bi <?= e($icon) ?> d-lg-none me-2" aria-hidden="true"></i><?= e($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="nav-actions">
                <?php if (is_logged_in()): ?>
                    <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>" href="<?= e(url('dashboard.php')) ?>">
                        <i class="bi bi-grid me-1" aria-hidden="true"></i>Dashboard
                    </a>
                    <span class="user-chip" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Signed in as <?= e(current_username()) ?>">
                        <span class="user-chip__avatar"><?= e(strtoupper(substr(current_username(), 0, 1))) ?></span>
                        <span class="user-chip__name"><?= e(current_username()) ?></span>
                    </span>
                    <!-- Logout is a POST form with a CSRF token, so other sites cannot log users out -->
                    <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-spice btn-sm">
                            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Log out
                        </button>
                    </form>
                <?php else: ?>
                    <a class="nav-link<?= $activePage === 'login' ? ' active' : '' ?>" href="<?= e(url('auth/login.php')) ?>">Log in</a>
                    <a class="btn btn-spice btn-sm" href="<?= e(url('auth/register.php')) ?>">
                        <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Join free
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ================= FLASH MESSAGES ================= -->
<?php $flashes = get_flashes(); ?>
<?php if ($flashes): ?>
    <div class="flash-stack" aria-live="polite">
        <?php foreach ($flashes as $flash):
            $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
            $icon = ['success' => 'bi-check2-circle', 'danger' => 'bi-x-circle', 'warning' => 'bi-exclamation-triangle', 'info' => 'bi-info-circle'][$type];
        ?>
            <div class="alert alert-<?= e($type) ?> alert-dismissible fade show flash" role="alert" data-autodismiss="6000">
                <i class="bi <?= e($icon) ?> me-2" aria-hidden="true"></i><?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="main">
