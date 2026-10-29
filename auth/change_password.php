<?php
require_once __DIR__ . '/../functions/bootstrap.php';
rmmc_require_roles('Student','Officer','Admin');
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    rmmc_verify_post();
    $new = (string)($_POST['new_password'] ?? '');
    if (strlen($new) < 10 || strlen($new) > 200 || $new !== ($_POST['confirm_password'] ?? '')) {
        $error = 'Use a matching password with at least 10 characters.';
    } else {
        rmmc_run('UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($new, PASSWORD_DEFAULT), (int)$_SESSION['user_id']]);
        if (rmmc_has_column('users', 'must_change_password')) {
            rmmc_run('UPDATE users SET must_change_password = 0 WHERE id = ?', 'i', [(int)$_SESSION['user_id']]);
        }
        unset($_SESSION['must_change_password']); session_regenerate_id(true);
        rmmc_flash('success', 'Your password has been updated.');
        rmmc_redirect('/'.strtolower($_SESSION['role']).'/dashboard.php');
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Change password · RMMC ITE</title><link rel="stylesheet" href="/assets/css/portal.css"></head><body><main class="panel" style="max-width:500px;margin:8vh auto"><h1>Set your new password</h1><p>For account security, update your temporary password before continuing.</p><?php if ($error): ?><div class="notice error"><?=rmmc_e($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=rmmc_e(rmmc_csrf())?>"><div class="field"><label>New password</label><input type="password" name="new_password" minlength="10" required autocomplete="new-password"></div><div class="field" style="margin:18px 0"><label>Confirm password</label><input type="password" name="confirm_password" minlength="10" required autocomplete="new-password"></div><button>Update password</button></form><p><a href="/auth/logout.php">Log out</a></p></main></body></html>
