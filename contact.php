<?php
/**
 * LankaEats – Sri Lankan Digital Recipe Book
 * File: contact.php  (Contact form – guide section 3.4)
 *
 * Flow
 *   GET  → show the form (name/e-mail pre-filled for logged-in members)
 *   POST → 1. CSRF token check + hidden "honeypot" field against spam bots
 *          2. server-side validation (js/validation.js already checked it
 *             in the browser – this is the second, trusted check)
 *          3. INSERT INTO messages with a prepared statement
 *          4. Post/Redirect/Get: redirect back with a success flash message
 *             (so refreshing the page can't submit the message twice)
 *
 * Optional e-mail notification with PHPMailer is included below as a
 * clearly marked, DISABLED stub (the project runs without Composer).
 */

require_once __DIR__ . '/includes/functions.php';

// Allowed subjects (the select box and the validation share this list).
$subjects = ['General question', 'Recipe request', 'Report a mistake', 'Feedback on the website', 'Something else'];

$values = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$errors = [];

// Pre-fill for logged-in members.
if (is_logged_in()) {
    $stmt = db()->prepare('SELECT username, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([current_user_id()]);
    if ($me = $stmt->fetch()) {
        $values['name']  = $me['username'];
        $values['email'] = $me['email'];
    }
}

if (is_post()) {
    $values = [
        'name'    => clean_line(input('name')),
        'email'   => strtolower(input('email')),
        'subject' => input('subject'),
        'message' => trim(input('message')),
    ];

    if (!verify_csrf()) {
        $errors['form'] = 'Your session expired. Please send the form again.';
    } elseif (input('website') !== '') {
        // Honeypot: real people never see or fill this hidden field.
        set_flash('success', 'Thank you! Your message has been sent.');
        redirect('contact.php');
    } elseif (time() - (int) ($_SESSION['last_contact_at'] ?? 0) < 30) {
        $errors['form'] = 'You just sent a message. Please wait a few seconds before sending another.';
    } else {
        // ---- Server-side validation ----
        if ($values['name'] === '') {
            $errors['name'] = 'Please tell us your name.';
        } elseif (!length_between($values['name'], 2, 100)) {
            $errors['name'] = 'Name must be between 2 and 100 characters.';
        }

        if ($values['email'] === '') {
            $errors['email'] = 'E-mail is required so we can reply.';
        } elseif (!is_valid_email($values['email'])) {
            $errors['email'] = 'Please enter a valid e-mail address.';
        }

        if (!in_array($values['subject'], $subjects, true)) {
            $errors['subject'] = 'Please choose a subject.';
        }

        if ($values['message'] === '') {
            $errors['message'] = 'Please write a message.';
        } elseif (!length_between($values['message'], 10, 2000)) {
            $errors['message'] = 'Message must be between 10 and 2000 characters.';
        }

        // ---- Store the message ----
        if (!$errors) {
            try {
                $stmt = db()->prepare('INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
                $stmt->execute([$values['name'], $values['email'], $values['subject'], $values['message']]);

                /* ---------------------------------------------------------------
                 * OPTIONAL: e-mail notification with PHPMailer (DISABLED)
                 * ---------------------------------------------------------------
                 * To enable: download PHPMailer into includes/PHPMailer/, fill in
                 * real SMTP details and remove the comment markers. The message is
                 * already safely stored in the database either way.
                 *
                 * require __DIR__ . '/includes/PHPMailer/src/PHPMailer.php';
                 * require __DIR__ . '/includes/PHPMailer/src/SMTP.php';
                 * require __DIR__ . '/includes/PHPMailer/src/Exception.php';
                 *
                 * $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                 * try {
                 *     $mail->isSMTP();
                 *     $mail->Host       = 'smtp.gmail.com';
                 *     $mail->SMTPAuth   = true;
                 *     $mail->Username   = 'your-address@gmail.com';   // placeholder
                 *     $mail->Password   = 'your-app-password';        // placeholder – never commit real ones
                 *     $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                 *     $mail->Port       = 587;
                 *     $mail->setFrom('your-address@gmail.com', 'LankaEats');
                 *     $mail->addAddress('your-address@gmail.com');
                 *     $mail->addReplyTo($values['email'], $values['name']);
                 *     $mail->Subject = 'LankaEats contact: ' . $values['subject'];
                 *     $mail->Body    = $values['message'];
                 *     $mail->send();
                 * } catch (PHPMailer\PHPMailer\Exception $mailEx) {
                 *     error_log('[LankaEats] Mail failed: ' . $mail->ErrorInfo);
                 * }
                 * ------------------------------------------------------------- */

                $_SESSION['last_contact_at'] = time();
                set_flash('success', 'Thank you, ' . $values['name'] . '! Your message has been received – we usually reply within two days.');
                redirect('contact.php');
            } catch (PDOException $ex) {
                error_log('[LankaEats] Contact insert failed: ' . $ex->getMessage());
                $errors['form'] = 'Sorry, your message could not be saved. Please try again.';
            }
        }
    }
}

$pageTitle  = 'Contact';
$pageDesc   = 'Get in touch with the LankaEats team – recipe requests, corrections and feedback.';
$activePage = 'contact';
require __DIR__ . '/includes/header.php';
?>

<header class="page-banner">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact</li>
            </ol>
        </nav>
        <h1 class="reveal">Let's talk food</h1>
        <p class="section-lead reveal">Missing a dish? Spotted a mistake? Want to say hello? Drop us a line.</p>
    </div>
</header>

<section class="pb-5">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <!-- Contact details -->
            <div class="col-lg-4">
                <div class="d-grid gap-3">
                    <div class="contact-card reveal reveal-left">
                        <span class="icon"><i class="bi bi-envelope-paper" aria-hidden="true"></i></span>
                        <div><h2 class="h6 mb-1 fw-bold">E-mail</h2><p>hello@lankaeats.lk</p></div>
                    </div>
                    <div class="contact-card reveal reveal-left" style="--reveal-delay:.08s">
                        <span class="icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
                        <div><h2 class="h6 mb-1 fw-bold">Where we cook</h2><p>Faculty of Technology, Rajarata University of Sri Lanka, Mihintale</p></div>
                    </div>
                    <div class="contact-card reveal reveal-left" style="--reveal-delay:.16s">
                        <span class="icon"><i class="bi bi-clock" aria-hidden="true"></i></span>
                        <div><h2 class="h6 mb-1 fw-bold">Reply time</h2><p>Usually within two days (a little longer during Avurudu!)</p></div>
                    </div>
                    <div class="contact-card reveal reveal-left" style="--reveal-delay:.24s">
                        <span class="icon"><i class="bi bi-patch-question" aria-hidden="true"></i></span>
                        <div><h2 class="h6 mb-1 fw-bold">Quick answers</h2><p>Many questions are answered in our <a href="<?= e(url('about.php#faq')) ?>">FAQ</a>.</p></div>
                    </div>
                </div>
            </div>

            <!-- Contact form -->
            <div class="col-lg-8">
                <div class="form-card reveal reveal-right">
                    <h2 class="h3 mb-1">Send a message</h2>
                    <p class="text-muted-warm">Fields marked <span class="text-danger">*</span> are required.</p>

                    <?php if (isset($errors['form'])): ?>
                        <div class="alert alert-form" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i><?= e($errors['form']) ?></div>
                    <?php elseif ($errors): ?>
                        <div class="alert alert-form" role="alert"><i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>Please fix the highlighted fields.</div>
                    <?php endif; ?>
                    <div class="alert alert-form d-none" role="alert" data-form-summary></div>

                    <form method="post" action="<?= e(url('contact.php')) ?>" data-validate novalidate>
                        <?= csrf_field() ?>

                        <!-- Honeypot: hidden from people, tempting for bots -->
                        <div class="visually-hidden" aria-hidden="true">
                            <label for="website">Leave this empty</label>
                            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 field">
                                <label for="name" class="form-label">Your name<span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    <input type="text" class="form-control<?= field_class($errors, 'name') ?>" id="name" name="name"
                                           value="<?= e($values['name']) ?>" required minlength="2" maxlength="100" data-label="Name" autocomplete="name">
                                </div>
                                <?= field_error($errors, 'name') ?>
                            </div>
                            <div class="col-md-6 field">
                                <label for="email" class="form-label">E-mail address<span class="req">*</span></label>
                                <div class="input-icon">
                                    <i class="bi bi-at" aria-hidden="true"></i>
                                    <input type="email" class="form-control<?= field_class($errors, 'email') ?>" id="email" name="email"
                                           value="<?= e($values['email']) ?>" required maxlength="100" data-rule="email" data-label="E-mail" autocomplete="email">
                                </div>
                                <?= field_error($errors, 'email') ?>
                            </div>
                            <div class="col-12 field">
                                <label for="subject" class="form-label">Subject<span class="req">*</span></label>
                                <select class="form-select<?= field_class($errors, 'subject') ?>" id="subject" name="subject" required data-label="A subject">
                                    <option value="">Choose a subject…</option>
                                    <?php foreach ($subjects as $s): ?>
                                        <option value="<?= e($s) ?>"<?= $values['subject'] === $s ? ' selected' : '' ?>><?= e($s) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= field_error($errors, 'subject') ?>
                            </div>
                            <div class="col-12 field">
                                <label for="message" class="form-label">Message<span class="req">*</span></label>
                                <textarea class="form-control<?= field_class($errors, 'message') ?>" id="message" name="message" rows="6"
                                          required minlength="10" maxlength="2000" data-label="Message" data-counter="#msgCounter"
                                          placeholder="Tell us what's on your mind…"><?= e($values['message']) ?></textarea>
                                <div class="d-flex justify-content-between">
                                    <span class="form-text">At least 10 characters.</span>
                                    <span class="char-counter" id="msgCounter" aria-live="polite"></span>
                                </div>
                                <?= field_error($errors, 'message') ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-spice btn-lg mt-4">
                            <i class="bi bi-send me-2" aria-hidden="true"></i>Send message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
