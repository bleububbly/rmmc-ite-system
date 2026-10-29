<?php
require_once __DIR__.'/bootstrap.php';
function rmmc_audit(string $action,string $target,string $details=''):void{
    if (!rmmc_table_exists('ite_audit_logs')) return;
    rmmc_run('INSERT INTO ite_audit_logs (actor_user_id,action,target,details) VALUES (?,?,?,?)','isss',[(int)($_SESSION['user_id']??0),$action,$target,$details]);
}
