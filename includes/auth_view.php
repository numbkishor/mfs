<?php

declare(strict_types=1);

/**
 * Shared markup for the two sign-in screens.
 *
 * The card is the only thing in the flow, centred both ways on every
 * breakpoint. The theme toggle is positioned out of the flow so it can
 * never shift the card off the true centre of the viewport.
 *
 * Expects the including page to define:
 *   $portal      'employee' | 'customer'
 *   $pageTitle   (string)  browser tab title
 *   $heading     (string)  card heading
 *   $subheading  (string)  card sub-heading
 *   $errors      (array)   validation messages
 *   $submitClass (string)  css class for the submit button
 *   $hint        (string)  html shown under the divider
 *   $showRemember(bool)    render the "remember me" checkbox
 */

$portal       = $portal ?? 'employee';
$errors       = $errors ?? [];
$showRemember = $showRemember ?? false;
$submitClass  = $submitClass ?? 'btn-navy';
$hint         = $hint ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require __DIR__ . '/head.php'; ?>
</head>
<body>
<div class="mfs-auth">
    <div class="mfs-auth-toolbar">
        <button type="button" class="mfs-icon-btn" data-theme-toggle aria-label="Switch colour theme">
            <i class="bi" data-theme-icon aria-hidden="true"></i>
        </button>
    </div>

    <div class="mfs-auth-inner">
        <main class="mfs-auth-card">
            <div class="mfs-auth-brand">
                <span class="mfs-brand-mark" aria-hidden="true">M</span>
                <span class="mfs-brand-name">Microfinance LMS</span>
            </div>

            <nav class="mfs-segment" data-active="<?= e($portal) ?>" aria-label="Choose portal">
                <a href="<?= e(BASE_URL) ?>/auth/login_employee.php"<?= $portal === 'employee' ? ' class="active" aria-current="page"' : '' ?>>Employee</a>
                <a href="<?= e(BASE_URL) ?>/auth/login_customer.php"<?= $portal === 'customer' ? ' class="active" aria-current="page"' : '' ?>>Customer</a>
            </nav>

            <h1 class="mfs-auth-title"><?= e($heading) ?></h1>
            <p class="mfs-auth-subtitle"><?= e($subheading) ?></p>

            <?php render_flash(); ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="post" class="needs-validation" novalidate>
                <?php csrf_field(); ?>

                <div class="mfs-field">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                           required autofocus value="<?= e($_POST['username'] ?? '') ?>">
                    <div class="invalid-feedback">Username is required.</div>
                </div>

                <div class="mfs-field">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-control has-affix" id="password" name="password"
                           autocomplete="current-password" required>
                    <button type="button" class="mfs-reveal" data-toggle-password="password"
                            aria-label="Show password" aria-pressed="false" tabindex="-1">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    <div class="invalid-feedback">Password is required.</div>
                    <p class="mfs-hint" data-caps-hint>
                        <i class="bi bi-capslock" aria-hidden="true"></i> Caps Lock is on
                    </p>
                </div>

                <?php if ($showRemember): ?>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Keep me signed in</label>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn <?= e($submitClass) ?> w-100 py-2">Sign in</button>
            </form>

            <?php if ($hint !== ''): ?>
                <p class="mfs-auth-note"><?= $hint ?></p>
            <?php endif; ?>
        </main>

        <p class="mfs-auth-foot">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> · v<?= e(APP_VERSION) ?></p>
    </div>
</div>

<script src="<?= e(BASE_URL) ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js?v=<?= e(APP_VERSION) ?>"></script>
<script src="<?= e(BASE_URL) ?>/assets/js/app.js?v=<?= e(APP_VERSION) ?>"></script>
</body>
</html>
