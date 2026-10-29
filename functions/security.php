<?php
require_once __DIR__.'/bootstrap.php';
function checkRole(string $role):void { rmmc_require_roles($role); }
