<?php
/* notify.php - silent admin notifications via Brevo HTTPS API.
 * include_once __DIR__ . '/notify.php'; then notify_admin($subject,$html,$replyTo=null).
 * Env: BREVO_API_KEY, MAIL_SENDER (verified sender), MAIL_TO (recipient, defaults to MAIL_SENDER),
 * MAIL_FROM_NAME (defaults to "KCI Website"). Missing key/sender = silent no-op.
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
$apiKey=trim((string)getenv('BREVO_API_KEY'));
$sender=trim((string)getenv('MAIL_SENDER'));
$mto=trim((string)getenv('MAIL_TO'));
$fname=trim((string)getenv('MAIL_FROM_NAME'));
if($fname===''){$fname='KCI Website';}
if($apiKey===''||$sender===''){return false;}
if(!filter_var($sender,FILTER_VALIDATE_EMAIL)){return false;}
if($mto===''||!filter_var($mto,FILTER_VALIDATE_EMAIL)){$mto=$sender;}
$subj=trim((string)preg_replace('/[\r\n]+/',' ',(string)$subject));
if($subj===''){$subj='New website notification';}
if(strlen($subj)>200){$subj=substr($subj,0,200);}
$rt=null;
if($replyTo!==null&&$replyTo!==''){
$rt=trim((string)preg_replace('/[\r\n]+/','',(string)$replyTo));
if(!filter_var($rt,FILTER_VALIDATE_EMAIL)){$rt=null;}
}
$html=(string)$htmlBody;
$plain=(string)$htmlBody;
$plain=(string)preg_replace('/<(br\s*\/?|p\s*\/?|li\s*\/?|tr\s*\/?|h[1-6][^>]*)>/i',"\n",$plain);
$plain=strip_tags($plain);
$plain=html_entity_decode($plain,ENT_QUOTES|ENT_HTML5,'UTF-8');
$plain=(string)preg_replace("/[ \t]+/",' ',$plain);
$plain=(string)preg_replace("/\n{3,}/","\n\n",$plain);
$plain=trim($plain);
if($plain===''){$plain=$subj;}
$payload=array(
'sender'=>array('name'=>$fname,'email'=>$sender),
'to'=>array(array('email'=>$mto)),
'subject'=>$subj,
'htmlContent'=>$html,
'textContent'=>$plain
);
if($rt!==null){$payload['replyTo']=array('email'=>$rt);}
$json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if($json===false){error_log('notify_admin: brevo json encode failed');return false;}
$url='https://api.brevo.com/v3/smtp/email';
$redact=function($s) use ($apiKey){
$s=(string)$s;
$s=(string)preg_replace('/[\r\n]+/',' ',$s);
if($apiKey!==''&&strpos($s,$apiKey)!==false){$s=str_replace($apiKey,'[redacted]',$s);}
$s=trim($s);
if(strlen($s)>300){$s=substr($s,0,300);}
return $s;
};
$msgFromBody=function($body) use ($redact){
$body=(string)$body;
$j=json_decode($body,true);
if(is_array($j)&&isset($j['message'])&&is_string($j['message'])&&trim($j['message'])!==''){return $redact($j['message']);}
return $redact($body);
};
if(function_exists('curl_init')&&function_exists('curl_exec')){
$ch=curl_init($url);
if($ch===false){error_log('notify_admin: brevo send failed: HTTP 0 curl init failed');return false;}
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,$json);
curl_setopt($ch,CURLOPT_HTTPHEADER,array('api-key: '.$apiKey,'Content-Type: application/json','Accept: application/json'));
curl_setopt($ch,CURLOPT_TIMEOUT,5);
curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
$resp=curl_exec($ch);
$errno=curl_errno($ch);
$cerr=(string)curl_error($ch);
$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
curl_close($ch);
if($resp===false||$errno!==0){error_log('notify_admin: brevo send failed: HTTP '.$status.' '.$redact($cerr!==''?$cerr:'curl error '.$errno));return false;}
if($status>=200&&$status<300){return true;}
error_log('notify_admin: brevo send failed: HTTP '.$status.' '.$msgFromBody($resp));
return false;
}
$headers="Content-Type: application/json\r\nAccept: application/json\r\napi-key: ".$apiKey."\r\n";
$ctx=stream_context_create(array('http'=>array('method'=>'POST','header'=>$headers,'content'=>$json,'timeout'=>5,'ignore_errors'=>true)));
$resp=@file_get_contents($url,false,$ctx);
$status=0;
if(isset($http_response_header)&&is_array($http_response_header)){
foreach($http_response_header as $h){if(preg_match('#HTTP/\S+\s+(\d{3})#',(string)$h,$mm)){$status=(int)$mm[1];}}
}
if($resp===false){error_log('notify_admin: brevo send failed: HTTP '.$status.' stream request failed');return false;}
if($status>=200&&$status<300){return true;}
error_log('notify_admin: brevo send failed: HTTP '.$status.' '.$msgFromBody($resp));
return false;
} catch (Throwable $e) { error_log('notify_admin: brevo send failed: '.$e->getMessage()); return false; }
}
}
