<?php
/* notify.php - silent admin notifications via Gmail SMTP (PHPMailer).
 * include_once __DIR__ . '/notify.php'; then notify_admin($subject,$html,$replyTo=null).
 * Env: MAIL_USER, MAIL_APP_PASSWORD, MAIL_TO (defaults to MAIL_USER),
 * MAIL_FROM_NAME (defaults to "KCI Website"). Missing creds = silent no-op.
 */
if (!function_exists('kci_base_url')) {
function kci_base_url() {
try {
$https=false;
if(!empty($_SERVER['HTTPS'])&&strtolower((string)$_SERVER['HTTPS'])!=='off'){$https=true;}
if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])){$p=strtolower(trim(explode(',',(string)$_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));if($p==='https'){$https=true;}}
if((string)($_SERVER['SERVER_PORT']??'')==='443'){$https=true;}
$sch=$https?'https':'http';
$host=trim((string)($_SERVER['HTTP_HOST']??''));
if($host===''){$host='localhost';}
$host=(string)preg_replace('/[^A-Za-z0-9.\-:\[\]]/','',$host);
if($host===''){$host='localhost';}
$dir=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/'));
if($dir==='.'||$dir==='/'||$dir==='\\'){$dir='';}
$dir=rtrim($dir,'/');
if(substr($dir,-6)==='/admin'){$dir=substr($dir,0,-6);}
return rtrim($sch.'://'.$host.$dir,'/').'/';
} catch (Throwable $e) { return '/'; }
}
}
if (!function_exists('kci_admin_url')) {
function kci_admin_url($f) {
try { return kci_base_url().'admin/'.ltrim((string)$f,'/'); }
catch (Throwable $e) { return 'admin/'.ltrim((string)$f,'/'); }
}
}
if (!function_exists('notify_admin')) {
function notify_admin($subject,$htmlBody,$replyTo=null) {
try {
$u=trim((string)getenv('MAIL_USER'));
$praw=(string)getenv('MAIL_APP_PASSWORD');
$mto=trim((string)getenv('MAIL_TO'));
$fname=trim((string)getenv('MAIL_FROM_NAME'));
if($fname===''){$fname='KCI Website';}
if($u===''||trim($praw)===''){return false;}
$p=str_replace(' ','',trim($praw));
if($p===''){return false;}
if(!filter_var($u,FILTER_VALIDATE_EMAIL)){return false;}
if($mto===''||!filter_var($mto,FILTER_VALIDATE_EMAIL)){$mto=$u;}
$subj=trim((string)preg_replace('/[\r\n]+/',' ',(string)$subject));
if($subj===''){$subj='New website notification';}
if(strlen($subj)>200){$subj=substr($subj,0,200);}
$rt=null;
if($replyTo!==null&&$replyTo!==''){
$rt=trim((string)preg_replace('/[\r\n]+/','',(string)$replyTo));
if(!filter_var($rt,FILTER_VALIDATE_EMAIL)){$rt=null;}
}
require_once __DIR__.'/PHPMAILER/Exception.php';
require_once __DIR__.'/PHPMAILER/PHPMailer.php';
require_once __DIR__.'/PHPMAILER/SMTP.php';
$m=new \PHPMailer\PHPMailer\PHPMailer(true);
$m->SMTPDebug=0;
$m->isSMTP();
$m->Host='smtp.gmail.com';
$m->SMTPAuth=true;
$m->Username=$u;
$m->Password=$p;
$m->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
$m->Port=587;
$m->Timeout=5;
$m->CharSet='UTF-8';
$m->setFrom($u,$fname);
$m->addAddress($mto);
if($rt!==null){$m->addReplyTo($rt);}
$m->Subject=$subj;
$m->isHTML(true);
$m->Body=(string)$htmlBody;
$plain=(string)$htmlBody;
$plain=(string)preg_replace('/<(br\s*\/?|p\s*\/?|li\s*\/?|tr\s*\/?|h[1-6][^>]*)>/i',"\n",$plain);
$plain=strip_tags($plain);
$plain=html_entity_decode($plain,ENT_QUOTES|ENT_HTML5,'UTF-8');
$plain=(string)preg_replace("/[ \t]+/",' ',$plain);
$plain=(string)preg_replace("/\n{3,}/","\n\n",$plain);
$plain=trim($plain);
if($plain===''){$plain=$subj;}
$m->AltBody=$plain;
if(!$m->send()){error_log('notify_admin: send failed: '.$m->ErrorInfo);return false;}
return true;
} catch (Throwable $e) { error_log('notify_admin: '.$e->getMessage()); return false; }
}
}
