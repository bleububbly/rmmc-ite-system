<?php
/** Shared application foundation; compatible with the existing mysqli database configuration. */
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}
require_once __DIR__ . '/../config/database.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(503);
    exit('Database connection unavailable. Verify config/database.php.');
}
mysqli_set_charset($conn, 'utf8mb4');
function rmmc_db(): mysqli { global $conn; return $conn; }
function rmmc_e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function rmmc_all(string $sql, string $types = '', array $args = []): array {
    $stmt = rmmc_db()->prepare($sql);
    if (!$stmt) { throw new RuntimeException('Database query preparation failed'); }
    try {
        if ($args) {
            $values = array_values($args);
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } finally { $stmt->close(); }
}
function rmmc_one(string $sql, string $types = '', array $args = []): ?array {
    return rmmc_all($sql, $types, $args)[0] ?? null;
}
function rmmc_run(string $sql, string $types = '', array $args = []): int {
    $stmt = rmmc_db()->prepare($sql);
    if (!$stmt) { throw new RuntimeException('Database update preparation failed'); }
    try {
        if ($args) { $values = array_values($args); $stmt->bind_param($types, ...$values); }
        $stmt->execute();
        return $stmt->affected_rows;
    } finally { $stmt->close(); }
}
function rmmc_has_column(string $table, string $column): bool {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table . $column)) return false;
    try {
        return (bool)rmmc_one("SHOW COLUMNS FROM `{$table}` LIKE ?", 's', [$column]);
    } catch (Throwable $e) { return false; }
}
function rmmc_table_exists(string $name): bool {
    return (bool)rmmc_one('SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', 's', [$name])['n'];
}
function rmmc_require_roles(string ...$roles): void {
    $role = $_SESSION['role'] ?? '';
    if (!isset($_SESSION['user_id']) || !in_array($role, $roles, true)) {
        if (!isset($_SESSION['user_id'])) { header('Location: /auth/login.php'); }
        else { http_response_code(403); echo 'Access denied.'; }
        exit;
    }
    if (isset($_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > 3600) {
        $_SESSION = []; session_destroy(); header('Location: /auth/login.php?expired=1'); exit;
    }
    $account = rmmc_me();
    if (!$account || strtolower((string)$account['status']) !== 'active' || $account['role'] !== $role) {
        $_SESSION=[];session_destroy();header('Location: /auth/login.php?expired=1');exit;
    }
    $_SESSION['last_activity'] = time();
    if (!empty($_SESSION['must_change_password']) && basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) !== 'change_password.php') {
        rmmc_redirect('/auth/change_password.php');
    }
}

function rmmc_csrf(): string {
    return $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)));
}
function rmmc_verify_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('POST required'); }
    if (!hash_equals(rmmc_csrf(), (string)($_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')))) {
        http_response_code(419); exit('Session verification failed. Reload this page and try again.');
    }
}
function rmmc_flash(string $type, string $message): void { $_SESSION['flash'] = [$type, $message]; }
function rmmc_redirect(string $path): void { header('Location: ' . $path); exit; }
function rmmc_me(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return rmmc_one('SELECT id,username,role,status FROM users WHERE id = ?', 'i', [(int)$_SESSION['user_id']]);
}
function rmmc_student(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return rmmc_one('SELECT * FROM students WHERE user_id = ?', 'i', [(int)$_SESSION['user_id']]);
}
function rmmc_period(): array {
    // Selectable based on actual event data, avoiding hardcoded academic years.
    $year = trim((string)($_GET['school_year'] ?? ''));
    $sem = trim((string)($_GET['semester'] ?? ''));
    return [$year, $sem];
}
