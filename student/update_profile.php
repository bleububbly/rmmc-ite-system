<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Student');
http_response_code(410);exit('Direct profile editing is disabled. Use Settings for password changes.');
