<?php
require_once __DIR__.'/../functions/portal.php';
rmmc_require_roles('Student');
$s=rmmc_student();if (!$s) {http_response_code(404);exit('Student profile not found');}
rmmc_shell_start('My QR Code','Student');
?><div class="welcome"><h1>My Attendance QR Code</h1><p>Present this code to an authorized Admin or Officer at a scheduled event session.</p></div><section class="panel" style="max-width:680px;text-align:center;margin:auto"><span class="pill">RMMC ITE Student ID</span><h2><?=rmmc_e($s['firstname'].' '.$s['lastname'])?></h2><p><?=rmmc_e($s['student_number'])?> · <?=rmmc_e($s['course'])?> · <?=rmmc_e($s['year_level'])?></p><?php if(!empty($s['qr_token'])): ?><img class="qr-preview" src="qr_image.php" alt="Personal student QR code"><div class="row" style="justify-content:center;margin-top:20px"><a class="btn" href="qr_image.php?download=1">Download QR Code</a><button class="ghost no-print" onclick="window.print()">Print QR Code</button></div><?php else: ?><div class="notice">Your secure QR token has not been provisioned. Ask the administrator to apply the database upgrade.</div><?php endif;?></section><?php rmmc_shell_end();
