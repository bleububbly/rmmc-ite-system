<?php
require_once __DIR__.'/bootstrap.php';
rmmc_require_roles('Admin','Officer');rmmc_verify_post();
$action=(string)($_POST['action']??'');$id=(int)($_POST['id']??0);
$back='/'.strtolower($_SESSION['role']).'/events.php';
require_once __DIR__.'/audit.php';
if($action==='finalize') {
    require_once __DIR__.'/clearance_logic.php';
    try { $created=rmmc_finalize_event($id);rmmc_audit('event_finalize','event:'.$id,'New fines: '.$created);rmmc_flash('success',"Event finalized. $created new absence fine(s) created; existing fines were not duplicated."); }
    catch(Throwable $e) {rmmc_flash('error',$e->getMessage());}
    rmmc_redirect($back);
}
if($action==='cancel') {
    if($id<1){http_response_code(400);exit('Invalid event');}
    if(rmmc_table_exists('ite_event_fines') && rmmc_one('SELECT id FROM ite_event_fines WHERE event_id=? LIMIT 1','i',[$id])) {
        rmmc_flash('error','Cancellation blocked because fines exist for this event. Reconcile the payment/fine ledger before cancelling.');
        rmmc_redirect($back);
    }
    rmmc_run('UPDATE events SET status = ? WHERE id = ?', 'si',['Cancelled',$id]);
    rmmc_audit('event_cancel','event:'.$id);
    rmmc_flash('success','Event cancelled. Existing attendance was retained.');rmmc_redirect($back);
}
if($action!=='save'){http_response_code(400);exit('Invalid event action');}
$title=trim((string)($_POST['title']??''));$date=(string)($_POST['event_date']??'');
if($title===''||strlen($title)>200||!DateTimeImmutable::createFromFormat('!Y-m-d',$date)) {http_response_code(422);exit('Valid title and event date required');}
$times=[];foreach(['time_start','time_end','morning_in','morning_out','afternoon_in','afternoon_out'] as $key){$v=trim((string)($_POST[$key]??''));if($v!==''&&!preg_match('/^\d{2}:\d{2}(?::\d{2})?$/',$v)){http_response_code(422);exit('Invalid session time');}$times[$key]=$v?:null;}
if($times['time_start']===null||$times['time_end']===null){http_response_code(422);exit('Start and end time required');}
if (($_POST['is_required']??'0')==='1' && !array_filter(['morning_in','morning_out','afternoon_in','afternoon_out'],fn($slot)=>$times[$slot]!==null)) {http_response_code(422);exit('A required event must configure at least one attendance slot.');}
$status=(string)($_POST['status']??'Upcoming'); if(!in_array($status,['Upcoming','Ongoing','Finished','Cancelled'],true)){http_response_code(422);exit('Invalid status');}
$organizer=(string)($_POST['organizer']??'ITE');if(!in_array($organizer,['ITE','COMSOC'],true))$organizer='ITE';
$fields=['title'=>$title,'description'=>trim((string)($_POST['description']??'')),'event_date'=>$date,'time_start'=>$times['time_start'],'time_end'=>$times['time_end'],'location'=>trim((string)($_POST['location']??'')),'required_join'=>trim((string)($_POST['required_join']??'')),'status'=>$status,'school_year'=>trim((string)($_POST['school_year']??'')),'semester'=>trim((string)($_POST['semester']??'')),'morning_in'=>$times['morning_in'],'morning_out'=>$times['morning_out'],'afternoon_in'=>$times['afternoon_in'],'afternoon_out'=>$times['afternoon_out']];
foreach(['organizer'=>$organizer,'is_required'=>(int)(($_POST['is_required']??'0')==='1'),'applicable_courses'=>trim((string)($_POST['applicable_courses']??'')),'applicable_years'=>trim((string)($_POST['applicable_years']??''))] as $key=>$val){if(rmmc_has_column('events',$key))$fields[$key]=$val;}
$isNew = $id === 0;
if($id) {
    $previous=rmmc_one('SELECT id,status FROM events WHERE id=?','i',[$id]); if(!$previous){http_response_code(404);exit('Event not found');}
    $sql='UPDATE events SET '.implode(',',array_map(fn($f)=>"`$f`=?",array_keys($fields))).' WHERE id=?';
    rmmc_run($sql,str_repeat('s',count($fields)).'i',array_merge(array_values($fields),[$id]));
} else {
    $sql='INSERT INTO events (`'.implode('`,`',array_keys($fields)).'`) VALUES ('.implode(',',array_fill(0,count($fields),'?')).')';
    rmmc_run($sql,str_repeat('s',count($fields)),array_values($fields));
    $id=(int)rmmc_db()->insert_id;
}
rmmc_audit($isNew?'event_create':'event_edit','event:'.$id);
if($status==='Finished' && !empty($fields['is_required']) && $date <= date('Y-m-d')) {
    require_once __DIR__.'/clearance_logic.php';
    try { $created=rmmc_finalize_event($id);rmmc_flash('success',"Event saved. Added $created new absence fine(s)."); }
    catch(Throwable $e) {rmmc_flash('error','Event saved, but fine finalization needs attention: '.$e->getMessage());}
 } else rmmc_flash('success',$isNew?'Event created.':'Event updated.');
if($isNew && rmmc_table_exists('notifications')) {
    // Notifications are a convenience; their failure must not undo a saved event.
    try {
        require_once __DIR__.'/notification.php';
        foreach(rmmc_all("SELECT user_id FROM students") as $s)createNotification((int)$s['user_id'],'New event: '.$title.' · '.$date);
    }catch(Throwable $e){error_log('Event notification error: '.$e->getMessage());}
}
rmmc_redirect($back);
