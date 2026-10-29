<?php
require_once __DIR__.'/portal.php';
function rmmc_staff_dashboard(string $role): void {
    rmmc_require_roles($role);
    $n = (int)(rmmc_one('SELECT COUNT(*) n FROM students')['n'] ?? 0);
    $events = rmmc_all('SELECT status,COUNT(*) n FROM events GROUP BY status');
    $byStatus = array_column($events, 'n', 'status');
    $courses = rmmc_all('SELECT UPPER(TRIM(course)) label, COUNT(*) n FROM students GROUP BY UPPER(TRIM(course)) ORDER BY n DESC');
    $years = rmmc_all('SELECT year_level label,COUNT(*) n FROM students GROUP BY year_level ORDER BY year_level');
    $maxCourse = max(1, ...array_map('intval', array_column($courses, 'n')));
    $maxYear = max(1, ...array_map('intval', array_column($years, 'n')));
    $name = rmmc_me()['username'] ?? $role;
    $staffProfile = rmmc_table_exists('ite_staff_profiles') ? rmmc_one('SELECT * FROM ite_staff_profiles WHERE user_id=?','i',[(int)$_SESSION['user_id']]) : null;
    $displayName = $staffProfile && trim($staffProfile['firstname'].' '.$staffProfile['lastname'])!=='' ? trim($staffProfile['firstname'].' '.$staffProfile['lastname']) : $name;
    rmmc_shell_start('Dashboard', $role);
    ?><div class="welcome"><h1><?=rmmc_e(rmmc_hello())?>, <?=rmmc_e($displayName)?>!</h1><p>Welcome to your RMMC ITE management portal.</p></div>
    <section class="id-card"><div class="avatar"><?=rmmc_e(strtoupper(substr($name,0,1)))?></div><div class="id-details"><h2><?=rmmc_e($displayName)?></h2><p>Staff ID / Username: <?=rmmc_e($name)?></p><p>Department: Information Technology Education</p></div><span class="pill"><?=rmmc_e($role)?></span></section>
    <section class="grid"><?php rmmc_row_card('Enrolled students',(string)$n,'students.php');rmmc_row_card('Upcoming events',(string)($byStatus['Upcoming']??0),'events.php?status=Upcoming');rmmc_row_card('Ongoing events',(string)($byStatus['Ongoing']??0),'events.php?status=Ongoing');rmmc_row_card('Finished events',(string)($byStatus['Finished']??0),'events.php?status=Finished'); ?></section>
    <div class="columns"><section class="panel"><div class="panel-header"><h2>Students by course</h2><a href="students.php">View students</a></div><?php foreach($courses as $r): ?><div class="chart-row"><a href="students.php?course=<?=urlencode((string)$r['label'])?>"><?=rmmc_e($r['label'] ?: 'Unassigned')?></a><div class="track"><div class="fill" style="width:<?=number_format(100 * (int)$r['n']/$maxCourse,1)?>%"></div></div><span><?=rmmc_e($r['n'])?></span></div><?php endforeach; ?><?php if (!$courses): ?><p class="empty">No student records yet.</p><?php endif; ?></section>
    <section class="panel"><div class="panel-header"><h2>Students by year level</h2><a href="students.php">View students</a></div><?php foreach($years as $r): ?><div class="chart-row"><a href="students.php?year=<?=urlencode((string)$r['label'])?>"><?=rmmc_e($r['label'] ?: 'Unassigned')?></a><div class="track"><div class="fill" style="width:<?=number_format(100 * (int)$r['n']/$maxYear,1)?>%"></div></div><span><?=rmmc_e($r['n'])?></span></div><?php endforeach; ?><?php if (!$years): ?><p class="empty">No student records yet.</p><?php endif; ?></section></div>
    <section class="panel"><div class="panel-header"><h2>Quick actions</h2></div><div class="row"><a class="btn" href="events.php">Manage events</a><a class="btn ghost" href="scanner.php">Scan a QR code</a><a class="btn ghost" href="clearance.php">Review clearances</a></div></section>
    <?php rmmc_shell_end();
}
function rmmc_student_dashboard(): void {
    rmmc_require_roles('Student');
    $student = rmmc_student();
    if (!$student) { http_response_code(404); exit('Student profile not found. Contact your administrator.'); }
    $sid = (int)$student['id'];
    $hasScans = rmmc_table_exists('ite_attendance_scans');
    $recent = $hasScans ? rmmc_all('SELECT s.session_date,s.slot,s.scanned_at,e.title FROM ite_attendance_scans s JOIN events e ON e.id=s.event_id WHERE s.student_id=? ORDER BY s.scanned_at DESC LIMIT 8','i',[$sid]) : [];
    $attended = (int)(rmmc_one('SELECT COUNT(DISTINCT event_id) n FROM attendance WHERE student_id=?','i',[$sid])['n'] ?? 0);
    $upcomingTotal=(int)(rmmc_one("SELECT COUNT(*) n FROM events WHERE status='Upcoming'")['n']??0);
    $upcoming = rmmc_all("SELECT id,title,event_date,time_start,location FROM events WHERE status='Upcoming' ORDER BY event_date LIMIT 5");
    $fine = rmmc_table_exists('ite_event_fines') ? rmmc_one('SELECT COALESCE(SUM(f.amount),0) - COALESCE((SELECT SUM(p.amount) FROM ite_fine_payments p JOIN ite_event_fines f2 ON p.fine_id=f2.id WHERE f2.student_id=?),0) AS due FROM ite_event_fines f WHERE f.student_id=?','ii',[$sid,$sid]) : null;
    $balance = max(0,(float)($fine['due'] ?? 0));
    $full = trim(($student['firstname']??'').' '.($student['lastname']??''));
    rmmc_shell_start('Student Dashboard','Student');
    ?><div class="welcome"><h1>Magandang RMMC, <?=rmmc_e($student['firstname'] ?? '')?>!</h1><p>Welcome to your ITE Student Portal.</p></div><section class="id-card"><div class="avatar"><?=rmmc_e(strtoupper(substr((string)($student['firstname']??'S'),0,1)))?></div><div class="id-details"><h2><?=rmmc_e($full)?></h2><p>Student ID: <?=rmmc_e($student['student_number'])?></p><p><?=rmmc_e($student['course'])?> · <?=rmmc_e($student['year_level'])?> · ITE Department</p></div><a class="btn" href="qr_code.php">Show My QR Code</a></section>
    <div class="grid"><?php rmmc_row_card('Recorded events',(string)$attended,'attendance.php');rmmc_row_card('Upcoming events',(string)$upcomingTotal,'events.php');rmmc_row_card('Outstanding fines','₱'.number_format($balance,2),'clearance.php'); ?></div>
    <div class="columns"><section class="panel"><div class="panel-header"><h2>Upcoming events</h2><a href="events.php">All events</a></div><?php if(!$upcoming):?><p class="empty">There are no upcoming events.</p><?php endif;?><?php foreach($upcoming as $e): ?><p><strong><?=rmmc_e($e['title'])?></strong><br><small><?=rmmc_e($e['event_date'])?> · <?=rmmc_e($e['location'])?></small></p><?php endforeach;?></section><section class="panel"><div class="panel-header"><h2>Recent QR scans</h2><a href="attendance.php">Attendance</a></div><?php if(!$recent): ?><p class="empty">No QR scans recorded in the new scan log.</p><?php endif; ?><?php foreach($recent as $r):?><p><strong><?=rmmc_e($r['title'])?></strong><br><small><?=rmmc_e($r['session_date'])?> · <?=rmmc_e(str_replace('_',' ',ucwords($r['slot'],'_')))?> · <?=rmmc_e($r['scanned_at'])?></small></p><?php endforeach;?></section></div>
    <?php rmmc_shell_end();
}
