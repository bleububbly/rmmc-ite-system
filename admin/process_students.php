<?php
require_once __DIR__.'/../functions/bootstrap.php';
rmmc_require_roles('Admin');rmmc_verify_post();
if(!rmmc_has_column('students','qr_token')||!rmmc_has_column('users','must_change_password')){http_response_code(503);exit('Apply the database upgrade before importing students.');}
$name=(string)($_SESSION['pending_csv']??'');if(!preg_match('/^[a-f0-9]{32}\.csv$/',$name)){http_response_code(400);exit('Missing import file.');}
$file=__DIR__.'/../storage/private/imports/'.$name;
$fp=@fopen($file,'r');if(!$fp){http_response_code(404);exit('CSV file not found.');}
fgetcsv($fp);$created=0;$skipped=0;$errors=0;
while(($row=fgetcsv($fp))!==false){
    if(count($row)<6){$errors++;continue;}
    [$number,$last,$first,$middle,$course,$year]=array_map('trim',array_slice($row,0,6));
    if(!preg_match('/^[A-Za-z0-9-]{4,30}$/',$number)||$last===''||$first===''||$course===''||$year===''||strlen($last)>100||strlen($first)>100){$errors++;continue;}
    $db=rmmc_db();$db->begin_transaction();
    try{
        $exists=rmmc_one('SELECT id FROM users WHERE username=? LIMIT 1','s',[$number]);
        if($exists){$db->rollback();$skipped++;continue;}
        rmmc_run('INSERT INTO users (username,password,role,status,must_change_password) VALUES (?,?,?,?,1)','ssss',[$number,password_hash($last,PASSWORD_DEFAULT),'Student','Active']);
        $uid=$db->insert_id;
        $cols=['user_id','student_number','firstname','lastname','course','year_level','qr_token'];
        $vals=[$uid,$number,$first,$last,$course,$year,bin2hex(random_bytes(32))];
        // Reuse an existing optional middle initial field if defined.
        if(rmmc_has_column('students','middlename')){$cols[]='middlename';$vals[]=$middle;}
        elseif(rmmc_has_column('students','middle_initial')){$cols[]='middle_initial';$vals[]=$middle;}
        $sql='INSERT INTO students (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',array_fill(0,count($cols),'?')).')';
        rmmc_run($sql,str_repeat('s',count($vals)),$vals);
        $db->commit();$created++;
    }catch(Throwable $e){$db->rollback();$errors++;error_log('Student import rejected row: '.$e->getMessage());}
}
fclose($fp);@unlink($file);unset($_SESSION['pending_csv']);
rmmc_flash('success',"Import complete: $created created, $skipped duplicate(s), $errors invalid or failed row(s).");
rmmc_redirect('/admin/students.php');
