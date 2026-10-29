<?php
require_once __DIR__.'/../functions/portal.php';
rmmc_require_roles('Admin');
$rows=rmmc_table_exists('ite_audit_logs')?rmmc_all('SELECT a.*,u.username FROM ite_audit_logs a LEFT JOIN users u ON u.id=a.actor_user_id ORDER BY a.created_at DESC LIMIT 200'):[];
rmmc_shell_start('Activity Logs','Admin');
?><div class="welcome"><h1>Audit log</h1><p>Recorded changes to events, payments, account profiles, and clearance approvals.</p></div><section class="panel"><div class="table-scroll"><table class="data-table"><tr><th>Actor</th><th>Action</th><th>Target</th><th>Details</th><th>Time</th></tr><?php foreach($rows as $a):?><tr><td><?=rmmc_e($a['username']??'Unknown')?></td><td><?=rmmc_e($a['action'])?></td><td><?=rmmc_e($a['target'])?></td><td><?=rmmc_e($a['details'])?></td><td><?=rmmc_e($a['created_at'])?></td></tr><?php endforeach;?></table></div><?php if(!$rows):?><p class="empty">No audit logs yet or migration not applied.</p><?php endif;?></section><?php rmmc_shell_end();
