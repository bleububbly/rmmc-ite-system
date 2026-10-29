<?php
require_once __DIR__ . '/../functions/bootstrap.php';
rmmc_verify_post();
$attempts = (int)($_SESSION['login_attempts'] ?? 0);
if ($attempts >= 8 && time() - (int)($_SESSION['first_failed_at'] ?? 0) < 300) {
    http_response_code(429); exit('Too many login attempts. Try again later.');
}
$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$portal = ($_POST['portal'] ?? '') === 'staff' ? 'staff' : 'student';
if ($username === '' || $password === '' || strlen($username) > 120) rmmc_redirect('/auth/login.php?portal='.$portal.'&error=1');
$user = rmmc_one('SELECT * FROM users WHERE username = ? LIMIT 1', 's', [$username]);
$isHash = $user && password_get_info((string)($user['password'] ?? ''))['algo'] !== null;
$valid = $user && ($isHash ? password_verify($password, (string)$user['password']) : hash_equals((string)$user['password'], $password));
$allowed = $user && (($portal === 'student' && $user['role'] === 'Student') || ($portal === 'staff' && in_array($user['role'], ['Admin','Officer'], true)));
if (!$valid || !$allowed || strtolower((string)($user['status'] ?? 'active')) !== 'active') {
    $_SESSION['first_failed_at'] ??= time();
    $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
    rmmc_redirect('/auth/login.php?portal='.$portal.'&error=1');
}
if (!$isHash || password_needs_rehash((string)$user['password'], PASSWORD_DEFAULT)) {
    rmmc_run('UPDATE users SET password = ? WHERE id = ?', 'si', [password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
}
if (!$isHash && rmmc_has_column('users', 'must_change_password')) {
    rmmc_run('UPDATE users SET must_change_password = 1 WHERE id = ?', 'i', [(int)$user['id']]);
}
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = (string)$user['role'];
$_SESSION['last_activity'] = time();
$_SESSION['login_attempts'] = 0;
unset($_SESSION['first_failed_at']);
$mustChange = rmmc_has_column('users', 'must_change_password') && (int)(rmmc_one('SELECT must_change_password FROM users WHERE id = ?', 'i', [(int)$user['id']])['must_change_password'] ?? 0) === 1;
$_SESSION['must_change_password'] = $mustChange || !$isHash;
rmmc_redirect($_SESSION['must_change_password'] ? '/auth/change_password.php' : '/'.strtolower($user['role']).'/dashboard.php');
