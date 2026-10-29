<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Officer');
http_response_code(410);
exit('This legacy event endpoint was retired. Use Events → Manage events.');
