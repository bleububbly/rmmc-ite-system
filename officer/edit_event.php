<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Officer');
$id=(int)($_GET['id']??0);
rmmc_redirect('/officer/events.php'.($id?'?edit='.$id:''));
