<?php
/** Run from CLI: php scripts/migrate.php. Apply a database backup FIRST. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/../functions/bootstrap.php';
$db = rmmc_db();
$required = ['users', 'students', 'events', 'attendance'];
foreach ($required as $table) { if (!rmmc_table_exists($table)) { fwrite(STDERR, "Missing existing table: $table\n"); exit(1); } }
if (!isset($argv[1]) || $argv[1] !== '--apply') {
    echo "BACK UP your MySQL database, then run: php scripts/migrate.php --apply\nNo changes were made.\n";
    exit(0);
}
foreach (['users'=>['must_change_password'=>'TINYINT(1) NOT NULL DEFAULT 0'],
           'students'=>['qr_token'=>'VARCHAR(100) NULL UNIQUE'],
           'events'=>['organizer'=>"VARCHAR(30) NOT NULL DEFAULT 'ITE'", 'is_required'=>'TINYINT(1) NOT NULL DEFAULT 0', 'applicable_courses'=>'VARCHAR(255) NULL', 'applicable_years'=>'VARCHAR(120) NULL']] as $table=>$columns) {
    foreach ($columns as $column=>$definition) {
        if (!rmmc_has_column($table, $column)) {
            $db->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "Added $table.$column\n";
        }
    }
}
// Allow an intermediate "Incomplete" status without deleting or resetting legacy rows.
if (rmmc_has_column('attendance','status')) {
    $col = rmmc_one("SHOW COLUMNS FROM `attendance` WHERE Field = 'status'");
    if ($col && stripos((string)$col['Type'], 'enum(') === 0) {
        $db->query("ALTER TABLE attendance MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Pending'");
        echo "Expanded attendance.status to preserve incomplete scan states\n";
    }
}
if (rmmc_has_column('events','status')) {
    $col = rmmc_one("SHOW COLUMNS FROM `events` WHERE Field = 'status'");
    if ($col && stripos((string)$col['Type'], 'enum(') === 0) {
        $db->query("ALTER TABLE events MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'Upcoming'");
        echo "Expanded events.status to support cancellation without deleting attendance\n";
    }
}
$sql = file_get_contents(__DIR__ . '/../migrations/001_upgrade.sql');
foreach (array_filter(array_map('trim', explode(';', (string)$sql))) as $statement) {
    // Remove leading SQL comments on each statement.
    $statement = preg_replace('/^\s*(?:--[^\n]*\n\s*)+/', '', $statement);
    if (trim($statement)) $db->query($statement);
}
// Legacy text passwords are not touched: they are rehashed on successful login.
// Reuse existing tokens if already provisioned. Never store a QR password in the token.
$rows = rmmc_all("SELECT id FROM students WHERE qr_token IS NULL OR qr_token = ''");
foreach ($rows as $row) {
    rmmc_run('UPDATE students SET qr_token = ? WHERE id = ? AND (qr_token IS NULL OR qr_token = \'\')', 'si', [bin2hex(random_bytes(32)), (int)$row['id']]);
}
echo "Upgrade complete. Unique QR tokens initialized.\n";
