<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Student');
rmmc_redirect('/student/events.php');
