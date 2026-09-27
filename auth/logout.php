<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: auth/logout.php  (Logout – guide section 3.3)
 *
 * Logging out is done with a POST request that carries a CSRF token
 * (the "Log out" button in the navbar is a small form). That way another
 * website cannot silently log our users out with a hidden link or image.
 *
 * On a valid POST the session is destroyed completely:
 *   1. empty the $_SESSION array
 *   2. expire the session cookie in the browser
 *   3. session_destroy() removes the data on the server
 * Then a brand-new, empty session is started only to carry the
 * "You have been logged out" flash message to the home page.
 *
 * If someone opens this URL directly (GET) while logged in, a small
 * confirmation page with the same POST form is shown instead.
 */

require_once dirname(__DIR__) . '/includes/functions.php';

if (is_post()) {
    if (!verify_csrf()) {
        set_flash('danger', 'Your session expired. Please try logging out again.');
        redirect(is_logged_in() ? 'dashboard.php' : 'index.php');
    }

    // 1. Clear all session variables.
    $_SESSION = [];

    // 2. Delete the session cookie (same path/domain it was created with).
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }

    // 3. Destroy the server-side session data.
    session_destroy();

    // Fresh empty session (new ID) just for the goodbye message.
    session_start();
    session_regenerate_id(true);
    set_flash('info', 'You have been logged out. See you again soon!');
    redirect('index.php');
}

// ---- GET request ----
if (!is_logged_in()) {
    redirect('index.php'); // nothing to log out from
}

$pageTitle = 'Log out';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="auth-wrap">
    <div class="container" style="max-width: 520px;">
        <div class="form-card text-center reveal">
            <img src="<?= e(url('images/logo.svg')) ?>" alt="" width="72" height="72" class="mb-3">
            <h1 class="h3">Log out of LankaEats?</h1>
            <p class="text-muted-warm">You are signed in as <strong><?= e(current_username()) ?></strong>.</p>
            <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="d-flex gap-2 justify-content-center">
                <?= csrf_field() ?>
                <a href="<?= e(url('dashboard.php')) ?>" class="btn btn-light">Stay logged in</a>
                <button type="submit" class="btn btn-spice"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>Log out</button>
            </form>
        </div>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
