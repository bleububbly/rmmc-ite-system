<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Admin');
require_once __DIR__.'/../functions/clearance_view.php';
rmmc_clearance_staff('Admin');
