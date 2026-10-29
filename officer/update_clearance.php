<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Officer');
http_response_code(410);exit('Legacy GET clearance update disabled. Use the Clearance approval form.');
