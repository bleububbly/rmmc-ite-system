<?php
require_once __DIR__.'/../functions/clearance_logic.php';
rmmc_require_roles('Admin','Officer');rmmc_verify_post();
$studentId=(int)($_POST['student_id']??0);$year=trim((string)($_POST['school_year']??''));$sem=trim((string)($_POST['semester']??''));
if($studentId<1||!$year||!$sem||!rmmc_new_ledger_available()){http_response_code(422);exit('Invalid clearance request.');}
$db=rmmc_db();$db->begin_transaction();
try {
    $st=rmmc_one('SELECT * FROM students WHERE id=? FOR UPDATE','i',[$studentId]);if(!$st)throw new RuntimeException('Student not found.');
    $state=rmmc_clearance_status($st,$year,$sem);
    if($state['blockers'])throw new RuntimeException('Clearance blocked: '.implode(', ',$state['blockers']));
    rmmc_run('INSERT INTO ite_clearance_approvals (student_id,school_year,semester,approved_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE approved_by=VALUES(approved_by),approved_at=CURRENT_TIMESTAMP','issi',[$studentId,$year,$sem,(int)$_SESSION['user_id']]);
    require_once __DIR__.'/../functions/audit.php';rmmc_audit('clearance_approve','student:'.$studentId,'Academic year: '.$year.' semester: '.$sem);
    $db->commit();rmmc_flash('success','Clearance approved.');
} catch(Throwable $e){$db->rollback();rmmc_flash('error',$e->getMessage());}
rmmc_redirect('/'.strtolower($_SESSION['role']).'/clearance.php?'.http_build_query(['school_year'=>$year,'semester'=>$sem,'student'=>$studentId]));
