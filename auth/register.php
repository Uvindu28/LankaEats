<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: auth/register.php  (User registration – guide section 3.3)
 *
 * Flow
 *   GET  → show the sign-up form.
 *   POST → 1. check the CSRF token
 *          2. re-validate every field on the server (never trust the browser,
 *             even though js/validation.js already checked them)
 *          3. make sure the username and e-mail are not already taken
 *          4. store the user with password_hash() (bcrypt) – never plain text
 *          5. redirect to the login page with a success flash message
 *   On any error the form is shown again with inline messages and the
 *   username/e-mail kept ("sticky" values). Passwords are never echoed back.
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_guest(); // logged-in users don't need to register again

$values = ['username' => '', 'email' => ''];
$errors = [];

if (is_post()) {
    if (!verify_csrf()) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } else {
        // ---- Read input (strings only – arrays are ignored by input()) ----
        $values['username'] = input('username');
        $values['email']    = strtolower(input('email'));
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm  = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

        // ---- Server-side validation (same rules as js/validation.js) ----
        if ($values['username'] === '') {
            $errors['username'] = 'Username is required.';
        } elseif (!is_valid_username($values['username'])) {
            $errors['username'] = 'Username must be 3–20 characters: letters, numbers or underscores only.';
        }

        if ($values['email'] === '') {
            $errors['email'] = 'E-mail is required.';
        } elseif (!is_valid_email($values['email'])) {
            $errors['email'] = 'Please enter a valid e-mail address.';
        }

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (!is_strong_password($password)) {
            $errors['password'] = 'Password needs at least 8 characters with an upper-case letter, a lower-case letter and a number.';
        }

        if ($confirm === '') {
            $errors['confirm_password'] = 'Please confirm your password.';
        } elseif ($password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // ---- Duplicate check (one query covers both columns) ----
        if (!$errors) {
            $stmt = db()->prepare('SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 2');
            $stmt->execute([$values['username'], $values['email']]);
            foreach ($stmt->fetchAll() as $row) {
                if (strcasecmp($row['username'], $values['username']) === 0) {
                    $errors['username'] = 'That username is already taken – try another.';
                }
                if (strcasecmp($row['email'], $values['email']) === 0) {
                    $errors['email'] = 'An account with this e-mail already exists. Try logging in.';
                }
            }
        }

        // ---- Create the account ----
        if (!$errors) {
            try {
                $stmt = db()->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                $stmt->execute([
                    $values['username'],
                    $values['email'],
                    password_hash($password, PASSWORD_DEFAULT), // bcrypt hash incl. random salt
                ]);

                $_SESSION['last_registered'] = $values['username']; // pre-fills the login form
                set_flash('success', 'Welcome to LankaEats, ' . $values['username'] . '! Your account is ready – please log in.');
                redirect('auth/login.php');
            } catch (PDOException $ex) {
                // 23000 = unique key violation (someone registered the same name a moment ago)
                if ($ex->getCode() === '23000') {
                    $errors['username'] = 'That username or e-mail was just taken. Please choose another.';
                } else {
                    error_log('[LankaEats] Registration failed: ' . $ex->getMessage());
                    $errors['form'] = 'Something went wrong while creating your account. Please try again.';
                }
            }
        }
    }
}

$pageTitle  = 'Create an account';
$pageDesc   = 'Join LankaEats for free and share your favourite Sri Lankan recipes.';
$activePage = 'register';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="auth-wrap">
    <div class="container">
        <div class="auth-card mx-auto reveal" style="max-width: 1020px;">
            <div class="row g-0">
                <!-- Decorative side panel -->
                <div class="col-lg-5">
                    <div class="auth-side auth-side--leaf">
                        <div>
                            <span class="section-eyebrow text-white">Join the kitchen</span>
                            <h2 class="mt-2">Keep your family recipes safe</h2>
                            <p>A free account lets you share dishes with the whole island.</p>
                        </div>
                        <img class="auth-art" src="<?= e(url('images/cat-breakfast.svg')) ?>" alt="">
                        <div>
                            <div class="perk"><i class="bi bi-check2-circle" aria-hidden="true"></i> Add recipes with photos</div>
                            <div class="perk"><i class="bi bi-check2-circle" aria-hidden="true"></i> Edit or delete them any time</div>
                            <div class="perk"><i class="bi bi-check2-circle" aria-hidden="true"></i> Your own dashboard</div>
                        </div>
                    </div>
                </div>

                <!-- Registration form -->
                <div class="col-lg-7">
                    <div class="auth-form">
                        <h1>Create your account</h1>
                        <p class="text-muted-warm">Already a member? <a href="<?= e(url('auth/login.php')) ?>">Log in here</a>.</p>

                        <?php if (isset($errors['form'])): ?>
                            <div class="alert alert-form" role="alert"><?= e($errors['form']) ?></div>
                        <?php elseif ($errors): ?>
                            <div class="alert alert-form" role="alert">Please fix the highlighted fields.</div>
                        <?php endif; ?>
                        <div class="alert alert-form d-none" role="alert" data-form-summary></div>

                        <!-- data-validate → js/validation.js checks the form before it is sent to PHP -->
                        <form method="post" action="<?= e(url('auth/register.php')) ?>" data-validate novalidate>
                            <?= csrf_field() ?>

                            <div class="mb-3 field">
                                <label for="username" class="form-label">Username<span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <input type="text" class="form-control<?= field_class($errors, 'username') ?>" id="username" name="username"
                                           value="<?= e($values['username']) ?>" required minlength="3" maxlength="20"
                                           data-rule="username" data-label="Username" autocomplete="username" autofocus>
                                </div>
                                <div class="form-text">3–20 characters: letters, numbers and underscores.</div>
                                <?= field_error($errors, 'username') ?>
                            </div>

                            <div class="mb-3 field">
                                <label for="email" class="form-label">E-mail address<span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-at" aria-hidden="true"></i>
                                    <input type="email" class="form-control<?= field_class($errors, 'email') ?>" id="email" name="email"
                                           value="<?= e($values['email']) ?>" required maxlength="100"
                                           data-rule="email" data-label="E-mail" autocomplete="email">
                                </div>
                                <?= field_error($errors, 'email') ?>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6 field">
                                    <label for="password" class="form-label">Password<span class="req">*</span></label>
                                    <div class="input-icon">
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                        <input type="password" class="form-control has-toggle<?= field_class($errors, 'password') ?>" id="password" name="password"
                                               required maxlength="72" data-rule="password" data-label="Password" autocomplete="new-password"
                                               data-strength-meter="#pwMeter" data-strength-label="#pwLabel" data-strength-rules="#pwRules">
                                        <button type="button" class="password-toggle" data-toggle-password="#password" aria-label="Show password" aria-pressed="false">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <!-- Live strength meter (updated by js/validation.js) -->
                                    <div class="strength-meter" id="pwMeter" data-score="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                                    <div class="strength-label" id="pwLabel" aria-live="polite"></div>
                                    <ul class="password-rules" id="pwRules">
                                        <li data-check="length">8+ characters</li>
                                        <li data-check="upper">Upper-case letter</li>
                                        <li data-check="lower">Lower-case letter</li>
                                        <li data-check="number">A number</li>
                                    </ul>
                                    <?= field_error($errors, 'password') ?>
                                </div>
                                <div class="col-md-6 field">
                                    <label for="confirm_password" class="form-label">Confirm password<span class="req">*</span></label>
                                    <div class="input-icon">
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                        <input type="password" class="form-control has-toggle<?= field_class($errors, 'confirm_password') ?>" id="confirm_password"
                                               name="confirm_password" required maxlength="72" data-match="#password"
                                               data-label="Password confirmation" autocomplete="new-password">
                                        <button type="button" class="password-toggle" data-toggle-password="#confirm_password" aria-label="Show password" aria-pressed="false">
                                            <i class="bi bi-eye" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <?= field_error($errors, 'confirm_password') ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-spice btn-lg w-100 mt-4">
                                <i class="bi bi-person-plus me-2" aria-hidden="true"></i>Create account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
