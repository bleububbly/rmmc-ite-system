<?php
require_once __DIR__ . '/bootstrap.php';
function rmmc_shell_start(string $title, string $role): void {
    $prefix = strtolower($role);
    $profile = rmmc_me();
    $items = [
        ['Dashboard', 'dashboard.php', 'layout-dashboard'],
        ['Students', 'students.php', 'users'],
        ['Attendance', 'attendance.php', 'check-circle'],
        ['Events', 'events.php', 'calendar'],
        ['Clearance', 'clearance.php', 'shield-check'],
        ['Fines & Payments', 'fines.php', 'wallet'],
        ['QR Code', $role === 'Student' ? 'qr_code.php' : 'scanner.php', 'qr-code'],
        ['Settings', 'settings.php', 'settings'],
    ];
    if ($role === 'Student') {
        $items = array_values(array_filter($items, fn($it) => $it[0] !== 'Students'));
        array_splice($items, 4, 0, [['Notifications','notifications.php','bell'],['Officers','officers.php','users']]);
    } elseif ($role === 'Admin') {
        array_splice($items, 1, 0, [['Users','users.php','user'],['Import Students','import_students.php','upload'],['Officers','officers.php','users'],['Reports','reports.php','bar-chart'],['Activity Logs','activity_logs.php','list']]);
    }
    $page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=rmmc_e($title)?> · RMMC ITE</title><link rel="stylesheet" href="/assets/css/portal.css"></head>
    <body><div class="app-shell"><aside class="side" id="sidebar"><a class="brand" href="/<?=rmmc_e($prefix)?>/dashboard.php"><span class="brand-icon">ITE</span><span>RMMC ITE<small>Event & Attendance</small></span></a>
    <nav class="nav" aria-label="Main navigation"><?php foreach ($items as [$label,$url,$icon]): ?>
    <a href="/<?=rmmc_e($prefix)?>/<?=rmmc_e($url)?>" class="<?= $page === $url ? 'current' : '' ?>"><?=rmmc_e($label)?></a>
    <?php endforeach; ?></nav><a class="nav logout" href="/auth/logout.php">Log out</a></aside>
    <div class="workspace"><header class="top"><button class="menu" id="menu-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="sidebar">☰</button><div><span class="eyebrow">RMMC / ITE</span><strong><?=rmmc_e($title)?></strong></div><div class="top-account"><span><?=rmmc_e($profile['username'] ?? '')?></span><span class="role-label"><?=rmmc_e($role)?></span></div></header>
    <main class="content"><?php if (!empty($_SESSION['flash'])): [$kind,$message] = $_SESSION['flash']; unset($_SESSION['flash']); ?><div class="notice <?=rmmc_e($kind)?>" role="status"><?=rmmc_e($message)?></div><?php endif;
}
function rmmc_shell_end(): void { ?>
    </main><footer class="footer">RMMC ITE · Event and Attendance Management System</footer></div></div>
    <script>const toggle=document.getElementById('menu-toggle');toggle?.addEventListener('click',()=>{const open=document.body.classList.toggle('nav-open');toggle.setAttribute('aria-expanded',String(open));});document.querySelectorAll('.nav a').forEach(a=>a.addEventListener('click',()=>document.body.classList.remove('nav-open')));</script></body></html><?php }
function rmmc_badge(string $status): string {
    $v = strtolower($status);
    $cls = in_array($v, ['present','cleared','paid','ongoing'], true) ? 'good' : (in_array($v, ['absent','cancelled','not cleared'], true) ? 'bad' : 'warn');
    return '<span class="status '.$cls.'">'.rmmc_e($status).'</span>';
}
function rmmc_hello(): string {
    $hour = (int)date('G'); return $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
}
function rmmc_row_card(string $label, string $value, string $href = ''): void {
    echo '<'.($href ? 'a href="'.rmmc_e($href).'"' : 'div').' class="metric"><span>'.rmmc_e($label).'</span><strong>'.rmmc_e($value).'</strong>'.($href ? '<small>View details →</small>' : '').'</'.($href ? 'a' : 'div'). '>';
}
