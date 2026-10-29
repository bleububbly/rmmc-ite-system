<?php
require_once __DIR__.'/../functions/portal.php';rmmc_require_roles('Admin');
$rows=rmmc_all('SELECT o.*,u.username FROM officers o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.organization,o.position');
rmmc_shell_start('Officers','Admin');
?><div class="welcome"><h1>Registered officers</h1><p>Officer records and assigned organization roles.</p></div><section class="panel"><div class="table-scroll"><table class="data-table"><tr><th>Username</th><th>Organization</th><th>Position</th><th>School Year</th></tr><?php foreach($rows as $o):?><tr><td><?=rmmc_e($o['username'])?></td><td><?=rmmc_e($o['organization']??'')?></td><td><?=rmmc_e($o['position']??'')?></td><td><?=rmmc_e($o['school_year']??'')?></td></tr><?php endforeach;?></table></div></section><?php rmmc_shell_end();
