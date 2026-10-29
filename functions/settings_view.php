<?php
require_once __DIR__.'/portal.php';
function rmmc_settings(string $role):void{
    rmmc_require_roles($role);
    $user=rmmc_me();$student=$role==='Student'?rmmc_student():null;
    $staffProfile=$role!=='Student' && rmmc_table_exists('ite_staff_profiles')
        ? rmmc_one('SELECT * FROM ite_staff_profiles WHERE user_id=?','i',[(int)$_SESSION['user_id']]) : null;
    $errors=[];
    if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
        rmmc_verify_post();
        if(($_POST['action']??'')==='profile' && $role!=='Student' && rmmc_table_exists('ite_staff_profiles')) {
            $first=trim((string)($_POST['firstname']??''));$last=trim((string)($_POST['lastname']??''));
            $middle=trim((string)($_POST['middle_initial']??''));$birth=(string)($_POST['birthdate']??'');
            if($first===''||$last===''||strlen($first)>100||strlen($last)>100||strlen($middle)>10){$errors[]='Enter valid first and last names.';}
            if($birth!==''&&!DateTimeImmutable::createFromFormat('!Y-m-d',$birth)){$errors[]='Enter a valid birth date.';}
            if(!$errors){
                rmmc_run('INSERT INTO ite_staff_profiles (user_id,firstname,lastname,middle_initial,birthdate) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE firstname=VALUES(firstname),lastname=VALUES(lastname),middle_initial=VALUES(middle_initial),birthdate=VALUES(birthdate)', 'issss',[(int)$_SESSION['user_id'],$first,$last,$middle,$birth?:null]);
                require_once __DIR__.'/audit.php';rmmc_audit('profile_update','user:'.$_SESSION['user_id']);
                rmmc_flash('success','Staff profile updated.');rmmc_redirect('settings.php');
            }
        } else {
        $old=(string)($_POST['old_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
        $record=rmmc_one('SELECT password FROM users WHERE id=?','i',[(int)$_SESSION['user_id']]);
        if(!$record||!password_verify($old,(string)$record['password']))$errors[]='Current password is incorrect.';
        if(strlen($new)<10||strlen($new)>200)$errors[]='New password must have 10–200 characters.';
        if($new!==$confirm)$errors[]='Password confirmation does not match.';
        if(!$errors){rmmc_run('UPDATE users SET password=? WHERE id=?','si',[password_hash($new,PASSWORD_DEFAULT),(int)$_SESSION['user_id']]);require_once __DIR__.'/audit.php';rmmc_audit('password_change','user:'.$_SESSION['user_id']);rmmc_flash('success','Your password was updated.');rmmc_redirect('settings.php');}
        }
    }
    rmmc_shell_start('Settings',$role);
    ?><div class="welcome"><h1>Account settings</h1><p>Review account information and securely update your password.</p></div><div class="columns"><section class="panel"><h2>Account details</h2><div class="field"><label>Username / Student ID</label><input readonly value="<?=rmmc_e($user['username']??'')?>"></div><div class="field" style="margin-top:15px"><label>Role</label><input readonly value="<?=rmmc_e($role)?>"></div><?php if($role!=='Student' && rmmc_table_exists('ite_staff_profiles')):?>
    <h3>Staff profile</h3><form method="post"><input type="hidden" name="csrf" value="<?=rmmc_e(rmmc_csrf())?>"><input type="hidden" name="action" value="profile">
    <?php foreach(['firstname'=>'First name','lastname'=>'Last name','middle_initial'=>'Middle initial','birthdate'=>'Birthday'] as $key=>$text):?>
    <div class="field" style="margin-top:12px"><label><?=rmmc_e($text)?></label><input name="<?=rmmc_e($key)?>" type="<?=$key==='birthdate'?'date':'text'?>" maxlength="<?=$key==='middle_initial'?10:100?>" value="<?=rmmc_e($staffProfile[$key]??'')?>" <?=in_array($key,['firstname','lastname'],true)?'required':''?>></div><?php endforeach;?><button style="margin-top:14px">Save profile</button></form><?php endif;?>
    <?php if($student):?><?php foreach(['firstname'=>'First name','lastname'=>'Last name','student_number'=>'Student ID','course'=>'Course','year_level'=>'Year level'] as $key=>$text):?><div class="field" style="margin-top:15px"><label><?=rmmc_e($text)?></label><input readonly value="<?=rmmc_e($student[$key]??'')?>"></div><?php endforeach;?><?php endif;?><p class="small">Student identity, course, role, and attendance information are read-only. Contact an authorized administrator for corrections.</p></section><section class="panel"><h2>Change password</h2><?php foreach($errors as $message):?><div class="notice error"><?=rmmc_e($message)?></div><?php endforeach;?><form method="post"><input type="hidden" name="csrf" value="<?=rmmc_e(rmmc_csrf())?>"><input type="hidden" name="action" value="password"><div class="field"><label>Current password</label><input type="password" name="old_password" autocomplete="current-password" required></div><div class="field" style="margin-top:15px"><label>New password (at least 10 characters)</label><input type="password" name="new_password" minlength="10" autocomplete="new-password" required></div><div class="field" style="margin-top:15px"><label>Confirm new password</label><input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></div><button style="margin-top:19px">Update password</button></form></section></div><?php rmmc_shell_end();
}
