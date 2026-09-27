<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: auth/login.php  (User login – guide section 3.3)
 *
 * Flow
 *   POST → 1. check the CSRF token and the attempt limiter
 *          2. validate input (username OR e-mail + password)
 *          3. look the user up with a prepared statement
 *          4. password_verify() the submitted password against the stored hash
 *          5. on success: session_regenerate_id(true) (prevents session
 *             fixation), store the user in the session, redirect to the
 *             dashboard (or the page they originally asked for)
 *          6. on failure: ONE generic message, so attackers can't learn
 *             whether a username exists
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_guest();

const MAX_LOGIN_ATTEMPTS = 5;   // failed tries allowed…
const LOCKOUT_SECONDS    = 120; // …before a short cool-down

// A valid bcrypt hash used when the user does not exist, so a wrong username
// takes as long to reject as a wrong password (no timing hints).
const DUMMY_HASH = '$2y$10$qBGR7kgXnvJzot22ldGV6ukLFq/9RTMptmeRrtRyE0LyyiDcXugTi';

// Pre-fill the identifier after a fresh registration.
$identifier = (string) ($_SESSION['last_registered'] ?? '');
unset($_SESSION['last_registered']);
$errors = [];

// Simple per-session limiter: ['count' => n, 'until' => timestamp]
$attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'until' => 0];
$lockedFor = max(0, (int) $attempts['until'] - time());

if (is_post()) {
    $identifier = input('identifier');
    $password   = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!verify_csrf()) {
        $errors['form'] = 'Your session expired. Please try again.';
    } elseif ($lockedFor > 0) {
        $errors['form'] = 'Too many failed attempts. Please wait ' . $lockedFor . ' seconds and try again.';
    } else {
        if ($identifier === '') {
            $errors['identifier'] = 'Please enter your username or e-mail.';
        } elseif (mb_strlen($identifier) > 100) {
            $errors['identifier'] = 'That username or e-mail is too long.';
        }
        if ($password === '') {
            $errors['password'] = 'Please enter your password.';
        }

        if (!$errors) {
            // Username OR e-mail – one prepared query handles both.
            $stmt = db()->prepare('SELECT id, username, password FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$identifier, strtolower($identifier)]);
            $user = $stmt->fetch();

            $passwordOk = password_verify($password, $user['password'] ?? DUMMY_HASH);

            if ($user && $passwordOk) {
                // Upgrade the hash automatically if PHP's default algorithm changes.
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $upd = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
                    $upd->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                }

                // New session ID after login → stops session-fixation attacks.
                session_regenerate_id(true);
                $_SESSION['user_id']  = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                unset($_SESSION['login_attempts'], $_SESSION['csrf_token']); // fresh CSRF token too

                $target = safe_redirect_target($_SESSION['redirect_after_login'] ?? null);
                unset($_SESSION['redirect_after_login']);

                set_flash('success', 'Welcome back, ' . $user['username'] . '! Ayubowan 🙏');
                redirect($target);
            }

            // Failed: count it, maybe start a cool-down, show a generic message.
            $attempts['count']++;
            if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
                $attempts = ['count' => 0, 'until' => time() + LOCKOUT_SECONDS];
                $errors['form'] = 'Too many failed attempts. Please wait ' . LOCKOUT_SECONDS . ' seconds and try again.';
            } else {
                $left = MAX_LOGIN_ATTEMPTS - $attempts['count'];
                $errors['form'] = 'Incorrect username/e-mail or password. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.';
            }
            $_SESSION['login_attempts'] = $attempts;
        }
    }
}

$pageTitle  = 'Log in';
$pageDesc   = 'Log in to LankaEats to manage your recipes.';
$activePage = 'login';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="auth-wrap">
    <div class="container">
        <div class="auth-card mx-auto reveal" style="max-width: 960px;">
            <div class="row g-0">
                <!-- Login form -->
                <div class="col-lg-7 order-2 order-lg-1">
                    <div class="auth-form">
                        <h1>Welcome back</h1>
                        <p class="text-muted-warm">New here? <a href="<?= e(url('auth/register.php')) ?>">Create a free account</a>.</p>

                        <?php if (isset($errors['form'])): ?>
                            <div class="alert alert-form" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= e($errors['form']) ?></div>
                        <?php endif; ?>
                        <div class="alert alert-form d-none" role="alert" data-form-summary></div>

                        <form method="post" action="<?= e(url('auth/login.php')) ?>" data-validate novalidate>
                            <?= csrf_field() ?>

                            <div class="mb-3 field">
                                <label for="identifier" class="form-label">Username or e-mail<span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <input type="text" class="form-control<?= field_class($errors, 'identifier') ?>" id="identifier" name="identifier"
                                           value="<?= e($identifier) ?>" required maxlength="100" data-rule="identifier"
                                           data-label="Username or e-mail" autocomplete="username" <?= $identifier === '' ? 'autofocus' : '' ?>>
                                </div>
                                <?= field_error($errors, 'identifier') ?>
                            </div>

                            <div class="mb-3 field">
                                <label for="password" class="form-label">Password<span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-lock" aria-hidden="true"></i>
                                    <input type="password" class="form-control has-toggle<?= field_class($errors, 'password') ?>" id="password" name="password"
                                           required data-label="Password" autocomplete="current-password" <?= $identifier !== '' ? 'autofocus' : '' ?>>
                                    <button type="button" class="password-toggle" data-toggle-password="#password" aria-label="Show password" aria-pressed="false">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <?= field_error($errors, 'password') ?>
                            </div>

                            <button type="submit" class="btn btn-spice btn-lg w-100 mt-2">
                                <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Log in
                            </button>
                        </form>

                        <div class="demo-hint mt-4">
                            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                            <strong>Demo account:</strong> username <code>demo</code> · password <code>Demo@123</code>
                        </div>
                    </div>
                </div>

                <!-- Decorative side panel -->
                <div class="col-lg-5 order-1 order-lg-2">
                    <div class="auth-side">
                        <div>
                            <span class="section-eyebrow text-white">Your kitchen</span>
                            <h2 class="mt-2">The pot is on the fire</h2>
                            <p>Log in to add new recipes and manage the ones you've shared.</p>
                        </div>
                        <img class="auth-art" src="<?= e(url('images/cat-street-food.svg')) ?>" alt="">
                        <p class="mb-0 small">"Measure with your heart, but write it down for the next person." – a LankaEats rule</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
