<?php
require_once __DIR__.'/../functions/portal.php';
rmmc_require_roles('Admin');
$all=(int)(rmmc_one('SELECT COUNT(*) n FROM attendance')['n']??0);
$present=(int)(rmmc_one("SELECT COUNT(*) n FROM attendance WHERE status='Present'")['n']??0);
$absent=(int)(rmmc_one("SELECT COUNT(*) n FROM attendance WHERE status='Absent'")['n']??0);
$incomplete=(int)(rmmc_one("SELECT COUNT(*) n FROM attendance WHERE status='Incomplete'")['n']??0);
rmmc_shell_start('Reports','Admin');
?><div class="welcome"><h1>Attendance reports</h1><p>Database totals for event attendance entries. These figures represent attendance records, not unique students.</p></div><div class="grid"><?php rmmc_row_card('All records',(string)$all,'attendance.php');rmmc_row_card('Present',(string)$present,'attendance.php?status=Present');rmmc_row_card('Absent',(string)$absent,'attendance.php?status=Absent');rmmc_row_card('Incomplete',(string)$incomplete,'attendance.php?status=Incomplete'); ?></div><section class="panel"><h2>Attendance completion rate</h2><p><strong><?= $all>0?number_format($present*100/$all,1):'0.0' ?>%</strong> present records</p><div class="track"><div class="fill" style="width:<?= $all>0?number_format($present*100/$all,2):'0' ?>%"></div></div><p class="small">This figure does not represent the number of unique attendees.</p></section><?php rmmc_shell_end();
