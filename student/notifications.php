<?php
require_once __DIR__.'/../functions/portal.php';rmmc_require_roles('Student');
$rows=rmmc_table_exists('notifications')?rmmc_all('SELECT message,date FROM notifications WHERE user_id=? ORDER BY date DESC LIMIT 50','i',[(int)$_SESSION['user_id']]):[];
rmmc_shell_start('Notifications','Student');
?><div class="welcome"><h1>Notifications</h1><p>Your latest ITE event messages.</p></div><section class="panel"><?php foreach($rows as $row):?><div style="padding:15px 0;border-bottom:1px solid #e5e7eb"><p><?=rmmc_e($row['message'])?></p><small><?=rmmc_e($row['date'])?></small></div><?php endforeach;?><?php if(!$rows):?><p class="empty">No notifications yet.</p><?php endif;?></section><?php rmmc_shell_end();
