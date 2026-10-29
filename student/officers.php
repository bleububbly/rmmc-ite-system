<?php
require_once __DIR__.'/../functions/portal.php';rmmc_require_roles('Student');
$rows=rmmc_all('SELECT o.*,u.username FROM officers o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.organization,o.position');
rmmc_shell_start('Officers','Student');
?><div class="welcome"><h1>ITE officers</h1><p>Current officer positions and organizations in the system.</p></div><section class="panel"><div class="table-scroll"><table class="data-table"><tr><th>Username</th><th>Organization</th><th>Position</th></tr><?php foreach($rows as $o):?><tr><td><?=rmmc_e($o['username'])?></td><td><?=rmmc_e($o['organization']??'')?></td><td><?=rmmc_e($o['position']??'')?></td></tr><?php endforeach;?></table></div></section><?php rmmc_shell_end();
