<?php
require_once __DIR__.'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
function api_reply(int $status,string $message):void{http_response_code($status);echo json_encode(['message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
// Use a JSON error rather than an HTML redirect for scanner requests.
if (!in_array($_SESSION['role']??'', ['Admin','Officer'],true) || empty($_SESSION['user_id'])) api_reply(403,'Admin or Officer login required.');
if (($_SERVER['REQUEST_METHOD']??'')!=='POST') api_reply(405,'POST required.');
if (!hash_equals(rmmc_csrf(), (string)($_POST['csrf']??''))) api_reply(419,'Session verification failed. Reload the scanner.');
if(!rmmc_table_exists('ite_attendance_scans')||!rmmc_has_column('students','qr_token')) api_reply(503,'Run the attendance database upgrade before scanning.');
$eventId=(int)($_POST['event_id']??0);$token=trim((string)($_POST['qr']??''));$slot=(string)($_POST['slot']??'');
$slots=['morning_in','morning_out','afternoon_in','afternoon_out'];
if($eventId<1||strlen($token)<20||strlen($token)>100||!in_array($slot,$slots,true))api_reply(422,'Select a valid event, slot, and QR token.');
$student=rmmc_one('SELECT s.*,u.status AS account_status FROM students s JOIN users u ON s.user_id=u.id WHERE s.qr_token=? LIMIT 1','s',[$token]);
if(!$student || strtolower((string)$student['account_status'])!=='active')api_reply(404,'QR is invalid or student account is inactive.');
$event=rmmc_one('SELECT * FROM events WHERE id=? AND status=?','is',[$eventId,'Ongoing']);
if(!$event)api_reply(409,'The event is not ongoing.');
$now=new DateTimeImmutable('now',new DateTimeZone('Asia/Manila'));
$date=$now->format('Y-m-d');
if($event['event_date']!==$date)api_reply(409,'This event is not scheduled for today. Multi-day sessions require a separate configured date.');
$scheduled=(string)($event[$slot]??'');
if($scheduled==='')api_reply(409,'This attendance slot is not configured for this event.');
// Re-parse without ambiguous expression precedence.
$when = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$date.' '.(strlen($scheduled)===5?$scheduled.':00':$scheduled),new DateTimeZone('Asia/Manila'));
if(!$when)api_reply(422,'Invalid event session time.');
$diff=$now->getTimestamp()-$when->getTimestamp();
if($diff < -1800||$diff > 3600)api_reply(409,'Outside this attendance slot’s permitted window (30 minutes before to 60 minutes after).');
foreach(['applicable_courses'=>'course','applicable_years'=>'year_level'] as $key=>$field){if (!empty($event[$key])){$allowed=array_map('strtoupper',array_map('trim',explode(',',(string)$event[$key])));if(!in_array(strtoupper(trim((string)$student[$field])),$allowed,true))api_reply(403,'This event does not apply to the student’s course or year level.');}}
$db=rmmc_db();$db->begin_transaction();
try {
    $previous=rmmc_one('SELECT id FROM ite_attendance_scans WHERE event_id=? AND student_id=? AND session_date=? AND slot=? FOR UPDATE','iiss',[$eventId,(int)$student['id'],$date,$slot]);
    if($previous){$db->rollback();api_reply(409,'This attendance slot was already recorded for this student.');}
    rmmc_run('INSERT INTO ite_attendance_scans (event_id,student_id,session_date,slot,scanned_at,recorded_by) VALUES (?,?,?,?,?,?)','iisssi',[$eventId,(int)$student['id'],$date,$slot,$now->format('Y-m-d H:i:s'),(int)$_SESSION['user_id']]);
    $records=rmmc_all('SELECT slot FROM ite_attendance_scans WHERE event_id=? AND student_id=? AND session_date=?','iis',[$eventId,(int)$student['id'],$date]);
    $done=array_column($records,'slot');$required=array_values(array_filter($slots,fn($s)=>!empty($event[$s])));
    $complete=!array_diff($required,$done);
    $current=rmmc_one('SELECT id,date FROM attendance WHERE student_id=? AND event_id=? LIMIT 1 FOR UPDATE','ii',[(int)$student['id'],$eventId]);
    if(!$current) {
        rmmc_run("INSERT INTO attendance (student_id,event_id,date,`$slot`,status,fine) VALUES (?,?,?,?,?,0)",'iisss',[(int)$student['id'],$eventId,$date,$now->format('H:i:s'),$complete?'Present':'Incomplete']);
    } elseif ($current['date']===$date) {
        rmmc_run("UPDATE attendance SET `$slot`=?,status=? WHERE id=?",'ssi',[$now->format('H:i:s'),$complete?'Present':'Incomplete',(int)$current['id']]);
    }
    // For unusual existing unique keys, the scan log remains the canonical per-slot record.
    $db->commit();
    api_reply(200,trim($student['firstname'].' '.$student['lastname']).' · '.$event['title'].' · '.ucwords(str_replace('_',' ',$slot)).' successfully recorded. '.($complete?'Attendance complete.':'Other required scans remain.'));
} catch (Throwable $e) {
    $db->rollback();
    if(($e instanceof mysqli_sql_exception)&&$e->getCode()===1062)api_reply(409,'This slot is already recorded.');
    error_log('Attendance scan failed: '.$e->getMessage());
    api_reply(500,'Unable to save the scan. Attendance was not confirmed.');
}
