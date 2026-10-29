<?php
require_once __DIR__.'/bootstrap.php';
function createNotification(int $userId,string $message):void {
    if(!rmmc_table_exists('notifications')) return;
    rmmc_run('INSERT INTO notifications (user_id,message,status,date) VALUES (?,?,?,?)','isss',[$userId,$message,'Unread',date('Y-m-d')]);
}
