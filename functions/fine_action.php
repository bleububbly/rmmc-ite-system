<?php
require_once __DIR__.'/clearance_logic.php';
rmmc_require_roles('Admin','Officer');rmmc_verify_post();
$id=(int)($_POST['fine_id']??0);$amount=(float)($_POST['amount']??0);$ref=trim((string)($_POST['reference']??''));
if($id<1||$amount<=0||strlen($ref)<2||strlen($ref)>120){http_response_code(422);exit('Valid amount and receipt reference required.');}
$db=rmmc_db();$db->begin_transaction();
try{
    $fine=rmmc_one('SELECT * FROM ite_event_fines WHERE id=? FOR UPDATE','i',[$id]);if(!$fine)throw new RuntimeException('Fine not found.');
    $paid=rmmc_one('SELECT COALESCE(SUM(amount),0) total FROM ite_fine_payments WHERE fine_id=?','i',[$id]);
    $due=round((float)$fine['amount']-(float)$paid['total'],2);
    if(round($amount,2)!=$amount||$amount>$due+.001)throw new RuntimeException('Payment amount exceeds the outstanding balance.');
    rmmc_run('INSERT INTO ite_fine_payments (fine_id,amount,reference,recorded_by) VALUES (?,?,?,?)','idsi',[$id,$amount,$ref,(int)$_SESSION['user_id']]);
    require_once __DIR__.'/audit.php';rmmc_audit('fine_payment','fine:'.$id,'Payment amount: '.$amount.' reference: '.$ref);
    $db->commit();rmmc_flash('success','Payment recorded with reference '. $ref .'.');
}catch(Throwable $e){$db->rollback();rmmc_flash('error','Payment could not be recorded: '.$e->getMessage());}
rmmc_redirect('/'.strtolower($_SESSION['role']).'/fines.php');
