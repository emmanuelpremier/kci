<?php
/* admin/password.php — change your own password (self-service or forced). */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';

$admin = require_admin($conn);
$forced = !empty($admin['must_change_password']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        csrf_fail();
    }

    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'Please fill in all three password fields.';
    } elseif (!password_verify($current, (string)$admin['password_hash'])) {
        $error = 'Your current password is incorrect.';
    } elseif ($new !== $confirm) {
        $error = 'The new passwords do not match.';
    } elseif ($new === $current) {
        $error = 'Your new password must be different from the current one.';
    } elseif (($ruleError = admin_password_error($new, (string)$admin['username'], (string)$admin['display_name'])) !== '') {
        $error = $ruleError;
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $updated = false;
        try {
            $stmt = $conn->prepare(
                'UPDATE admin_users SET password_hash = ?, must_change_password = 0 WHERE id = ?'
            );
            if ($stmt !== false) {
                $id = (int)$admin['id'];
                $stmt->bind_param('si', $hash, $id);
                $updated = $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $ignored) {
            $updated = false;
        }
        if ($updated) {
            admin_log_action($conn, (int)$admin['id'], 'password_change', 'Password changed');
            flash_set('success', 'Your password has been changed.');
            header('Location: index.php');
            exit;
        }
        $error = 'The password could not be saved. Please try again.';
    }
}

$pageTitle = 'Change password';
$activeNav = '';
include __DIR__ . '/includes/layout_top.php';
?>
<section class="kci-page">
<div class="kci-card kci-card--narrow">
<h2>Change your password</h2>
<?php if ($forced): ?>
<p class="kci-flash kci-flash--warning" role="alert">Your password must be changed before you can continue.</p>
<?php endif; ?>
<?php if ($error !== ''): ?>
<p class="kci-flash kci-flash--error" role="alert"><?php echo e($error); ?></p>
<?php endif; ?>
<p class="kci-muted">Use at least 12 characters, mixing letters with a number or symbol.</p>
<form method="post" action="password.php">
<?php csrf_field(); ?>
<label class="kci-field">
<span>Current password</span>
<span class="kci-passwrap">
<input type="password" name="current_password" id="kci-password" autocomplete="current-password" required>
<button type="button" class="kci-showpass" id="kci-showpass" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye"></i></button>
</span>
</label>
<label class="kci-field">
<span>New password</span>
<span class="kci-passwrap">
<input type="password" name="new_password" id="kci-password-new" autocomplete="new-password" required minlength="12">
<button type="button" class="kci-showpass" id="kci-showpass-new" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye"></i></button>
</span>
</label>
<label class="kci-field">
<span>Confirm new password</span>
<span class="kci-passwrap">
<input type="password" name="confirm_password" id="kci-password-confirm" autocomplete="new-password" required minlength="12">
<button type="button" class="kci-showpass" id="kci-showpass-confirm" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye"></i></button>
</span>
</label>
<div class="kci-actions">
<?php if (!$forced): ?>
<a class="kci-btn kci-btn--ghost" href="index.php">Back to dashboard</a>
<?php endif; ?>
<button type="submit" class="kci-btn kci-btn--primary"><i class="fa-solid fa-key"></i> Save new password</button>
</div>
</form>
</div>
</section>

<?php include __DIR__ . '/includes/layout_bottom.php'; ?>