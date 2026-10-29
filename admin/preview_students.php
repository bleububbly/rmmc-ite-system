<?php
require_once __DIR__.'/../functions/portal.php';
rmmc_require_roles('Admin');
$name=(string)($_SESSION['pending_csv']??'');if(!preg_match('/^[a-f0-9]{32}\.csv$/',$name)){rmmc_redirect('/admin/import_students.php');}
$file=__DIR__.'/../storage/private/imports/'.$name;
if(!is_file($file)){rmmc_redirect('/admin/import_students.php');}
$fp=fopen($file,'r');$header=fgetcsv($fp);$sample=[];$total=0;
while(($r=fgetcsv($fp))!==false){if(count($r)<6)continue;$total++;if(count($sample)<15)$sample[]=$r;}
fclose($fp);rmmc_shell_start('Preview CSV','Admin');
?><div class="welcome"><h1>Preview CSV import</h1><p><?=number_format($total)?> complete rows found. Only the first 15 rows are previewed.</p></div><section class="panel"><div class="table-scroll"><table class="data-table"><tr><th>Student ID</th><th>Last name</th><th>First name</th><th>Middle</th><th>Course</th><th>Year</th></tr><?php foreach($sample as $r):?><tr><?php foreach(array_slice($r,0,6) as $cell):?><td><?=rmmc_e($cell)?></td><?php endforeach;?></tr><?php endforeach;?></table></div><div class="row" style="margin-top:20px"><form method="post" action="process_students.php"><input type="hidden" name="csrf" value="<?=rmmc_e(rmmc_csrf())?>"><button <?=!$total?'disabled':''?>>Confirm import</button></form><a class="btn ghost" href="import_students.php">Back</a></div></section><?php rmmc_shell_end();
