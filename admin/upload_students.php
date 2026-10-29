<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Admin');rmmc_verify_post();
$file=$_FILES['student_file']??null;
if (!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>2097152||strtolower(pathinfo((string)$file['name'],PATHINFO_EXTENSION))!=='csv'){
    http_response_code(422);exit('Choose a valid CSV file no larger than 2 MB.');
}
$folder=__DIR__.'/../storage/private/imports';if(!is_dir($folder))mkdir($folder,0700,true);
$name=bin2hex(random_bytes(16)).'.csv';
if(!move_uploaded_file($file['tmp_name'],$folder.'/'.$name)){http_response_code(500);exit('Could not save uploaded CSV.');}
$_SESSION['pending_csv']=$name;
rmmc_redirect('/admin/preview_students.php');
