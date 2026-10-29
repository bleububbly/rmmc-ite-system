<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Admin');
http_response_code(410);exit('Legacy unverified student registration is disabled. Use the protected Admin student importer.');
