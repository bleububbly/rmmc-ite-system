<?php
require_once __DIR__.'/../functions/portal.php';rmmc_require_roles('Admin');
$rows=rmmc_all('SELECT id,username,role,status FROM users ORDER BY role,username LIMIT 200');
rmmc_shell_start('User Accounts','Admin');
?><div class="welcome"><h1>User accounts</h1><p>Active account list. Passwords and QR tokens are never displayed.</p></div><section class="panel"><div class="table-scroll"><table class="data-table"><tr><th>Account ID</th><th>Username</th><th>Role</th><th>Status</th></tr><?php foreach($rows as $u):?><tr><td><?=(int)$u['id']?></td><td><?=rmmc_e($u['username'])?></td><td><?=rmmc_e($u['role'])?></td><td><?=rmmc_badge($u['status'])?></td></tr><?php endforeach;?></table></div></section><?php rmmc_shell_end();
