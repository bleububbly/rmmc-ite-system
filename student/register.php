<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Admin');
rmmc_redirect('/admin/import_students.php');
