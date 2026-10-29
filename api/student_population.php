<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Admin','Officer');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
echo json_encode(rmmc_all('SELECT UPPER(TRIM(course)) AS course,COUNT(*) AS total FROM students GROUP BY UPPER(TRIM(course)) ORDER BY course'),JSON_UNESCAPED_UNICODE);
