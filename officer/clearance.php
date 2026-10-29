<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Officer');
require_once __DIR__.'/../functions/clearance_view.php';
rmmc_clearance_staff('Officer');
