<?php
require_once __DIR__.'/audit.php';
function addLog(string $admin,string $action):void {
    rmmc_audit('legacy_action',$admin,$action);
}
