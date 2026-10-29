<?php
require_once __DIR__.'/bootstrap.php';
function rmmc_new_ledger_available(): bool { return rmmc_table_exists('ite_event_fines') && rmmc_table_exists('ite_fine_payments') && rmmc_table_exists('ite_clearance_approvals'); }
function rmmc_applies(array $event,array $st):bool {
    foreach(['applicable_courses'=>'course','applicable_years'=>'year_level'] as $key=>$studentField) {
        $filter=trim((string)($event[$key]??''));
        if($filter!==''){
            $values=array_map(fn($v)=>strtoupper(trim($v)),explode(',',$filter));
            if(!in_array(strtoupper(trim((string)($st[$studentField]??''))),$values,true))return false;
        }
    }
    return true;
}
function rmmc_event_completed(array $event,int $studentId):bool {
    // Legacy Present rows stay valid. New records require all configured slots.
    $legacy=rmmc_one('SELECT status FROM attendance WHERE event_id=? AND student_id=? LIMIT 1','ii',[(int)$event['id'],$studentId]);
    if ($legacy && $legacy['status']==='Present') {
        $full = rmmc_one('SELECT * FROM attendance WHERE event_id=? AND student_id=? LIMIT 1','ii',[(int)$event['id'],$studentId]);
        $requiredSlots = array_values(array_filter(['morning_in','morning_out','afternoon_in','afternoon_out'],fn($v)=>!empty($event[$v])));
        if ($requiredSlots && !array_filter($requiredSlots,fn($slot)=>empty($full[$slot]))) return true;
    }
    if(!rmmc_table_exists('ite_attendance_scans'))return false;
    $slots=array_values(array_filter(['morning_in','morning_out','afternoon_in','afternoon_out'],fn($v)=>!empty($event[$v])));
    if(!$slots)return false;
    $done=rmmc_all('SELECT DISTINCT slot FROM ite_attendance_scans WHERE event_id=? AND student_id=? AND session_date=?','iis',[(int)$event['id'],$studentId,$event['event_date']]);
    return !array_diff($slots,array_column($done,'slot'));
}
/** Idempotent fine generation: one ₱20 fine per truly missed required event. */
function rmmc_finalize_event(int $eventId):int {
    if(!rmmc_new_ledger_available())throw new RuntimeException('Apply database upgrade first.');
    $event=rmmc_one('SELECT * FROM events WHERE id=?','i',[$eventId]);
    if(!$event||$event['status']!=='Finished')throw new RuntimeException('Only finished events can be finalized.');
    if(empty($event['is_required']))return 0;
    if (!array_filter(['morning_in','morning_out','afternoon_in','afternoon_out'], fn($slot)=>!empty($event[$slot]))) {
        throw new RuntimeException('Configure at least one required attendance slot before finalizing an event.');
    }
    $students=rmmc_all('SELECT id,course,year_level FROM students');$created=0;
    foreach($students as $s){
        if(!rmmc_applies($event,$s))continue;
        $studentId=(int)$s['id'];
        if(rmmc_event_completed($event,$studentId))continue;
        $partial=rmmc_table_exists('ite_attendance_scans')? rmmc_one('SELECT id FROM ite_attendance_scans WHERE event_id=? AND student_id=? LIMIT 1','ii',[$eventId,$studentId]):null;
        if($partial)continue; // Incomplete attendance requires manual review, not an automatic absence fine.
        $created+=rmmc_run('INSERT IGNORE INTO ite_event_fines (event_id,student_id,amount,reason) VALUES (?,?,20.00,?)','iis',[$eventId,$studentId,'Absent from required '.$event['organizer'].' event']);
    }
    return $created;
}
function rmmc_clearance_status(array $st,string $year,string $sem):array {
    if(!rmmc_new_ledger_available())return ['status'=>'Upgrade required','blockers'=>['Apply the new ledger migration.'],'due'=>0];
    $sid=(int)$st['id'];
    $events=rmmc_all("SELECT * FROM events WHERE school_year=? AND semester=? AND is_required=1 AND status='Finished' ORDER BY event_date",'ss',[$year,$sem]);
    $blockers=[];
    $totalEvents=(int)(rmmc_one('SELECT COUNT(*) n FROM events WHERE school_year=? AND semester=?','ss',[$year,$sem])['n']??0);
    if($year===''||$sem===''||$totalEvents===0)$blockers[]='No configured events for the selected academic year and semester';
    $pending=rmmc_all("SELECT * FROM events WHERE school_year=? AND semester=? AND is_required=1 AND status IN ('Upcoming','Ongoing')",'ss',[$year,$sem]);
    foreach($pending as $ev){if(rmmc_applies($ev,$st))$blockers[]=$ev['organizer'].' – '.$ev['title'].': required event not completed';}
    foreach($events as $ev){
        if(!rmmc_applies($ev,$st))continue;
        if(!rmmc_event_completed($ev,$sid))$blockers[]=$ev['organizer'].' – '.$ev['title'].': attendance not complete';
    }
    $dueRow=rmmc_one("SELECT COALESCE(SUM(f.amount),0) AS total,(SELECT COALESCE(SUM(p.amount),0) FROM ite_fine_payments p JOIN ite_event_fines f2 ON p.fine_id=f2.id JOIN events e2 ON e2.id=f2.event_id WHERE f2.student_id=? AND e2.school_year=? AND e2.semester=?) AS paid FROM ite_event_fines f JOIN events e ON e.id=f.event_id WHERE f.student_id=? AND e.school_year=? AND e.semester=?",'ississ',[$sid,$year,$sem,$sid,$year,$sem]);
    $due=max(0,(float)($dueRow['total']??0)-(float)($dueRow['paid']??0));
    if($due>0.001)$blockers[]='Outstanding fines: ₱'.number_format($due,2);
    $approved=rmmc_one('SELECT * FROM ite_clearance_approvals WHERE student_id=? AND school_year=? AND semester=?','iss',[$sid,$year,$sem]);
    $status=$blockers?'Pending Requirements':($approved?'Cleared':'Pending Approval');
    if($approved&&$blockers)$status='Pending Requirements';
    return ['status'=>$status,'blockers'=>$blockers,'due'=>$due,'approved'=>$approved];
}
