<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/webmail.php';
function check_compose(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$input=['to'=>'prueba@example.invalid','subject'=>'Cotización COT-2026-0001 — Metalrubber','body'=>"Estimado/a:\nMaterial < 5 mm & caucho.\nSaludos."];
$url=quote_webmail_compose_url($input);
check_compose(parse_url($url,PHP_URL_HOST)==='metalrubber.cl','Host fijo');
check_compose(parse_url($url,PHP_URL_PORT)===2096,'Puerto webmail');
check_compose(parse_url($url,PHP_URL_SCHEME)==='https','HTTPS');
parse_str((string)parse_url($url,PHP_URL_QUERY),$params);
check_compose($params['_action']==='compose'&&$params['_task']==='mail','Redacción, no borrador');
check_compose($params['_to']===$input['to']&&$params['_subject']===$input['subject'],'Acentos y destinatario');
check_compose(str_contains($params['_body'],'&lt; 5 mm &amp; caucho.')&&str_contains($params['_body'],'<br>'),'Texto seguro y saltos');
check_compose(count($params)===5&&!str_contains($url,'cpsess'),'Sin sesión temporal ni adjunto ficticio');
foreach([['to'=>"a@example.invalid\r\nBcc:x@example.invalid"],['subject'=>"Hola\nOtra cabecera"],['body'=>'']] as $bad){
    try{quote_webmail_compose_url(array_replace($input,$bad));throw new RuntimeException('Aceptó entrada inválida');}catch(InvalidArgumentException $e){}
}
try{quote_webmail_compose_url(array_replace($input,['body'=>str_repeat('Texto largo ',700)]));throw new RuntimeException('No limita URL');}catch(LengthException $e){}
check_compose(!quote_mail_configured(),'Borradores deshabilitados incluso con configuración privada');
try{new NativeQuoteDraftMailbox();throw new RuntimeException('IMAP permitido');}catch(RuntimeException $e){check_compose(str_contains($e->getMessage(),'deshabilitada'),'Bloqueo de conexión nativa');}
$page=file_get_contents(dirname(__DIR__).'/public/quote_mail.php');
check_compose(!str_contains($page,'prepare_quote_draft(')&&!str_contains($page,'quote_email_drafts'),'Pantalla no registra borradores');
check_compose(str_contains($page,'verify_csrf()')&&str_contains($page,'require_login()'),'CSRF y autenticación');
check_compose(!str_contains($page,'<form')&&!str_contains($page,'outlook-web-compose'),'Sin pantalla intermedia de correo');
check_compose(str_contains($page,'quote_outlook_eml('),'Descarga EML directa');
echo "Webmail compose OK (sin conexiones ni mensajes)\n";
$outlook=quote_outlook_compose_url($input);
check_compose(str_starts_with($outlook,'mailto:'),'Outlook usa aplicación de correo, no envía');
parse_str(substr($outlook,strpos($outlook,'?')+1),$outlookParams);
check_compose($outlookParams['subject']===$input['subject'],'Asunto Outlook conserva acentos');
check_compose($outlookParams['body']===str_replace("\n","\r\n",$input['body']),'Cuerpo Outlook de texto sin HTML');
check_compose(!isset($outlookParams['attachment']),'No promete adjuntar automáticamente');
try{quote_outlook_compose_url(array_replace($input,['body'=>str_repeat('Texto ',700)]));throw new RuntimeException('No limita mailto');}catch(LengthException $e){}
echo "Outlook compose OK (sin abrir ni enviar correos)\n";
check_compose(parse_url(quote_outlook_web_url($input,'personal'),PHP_URL_HOST)==='outlook.live.com','Outlook personal');
check_compose(parse_url(quote_outlook_web_url($input,'business'),PHP_URL_HOST)==='outlook.office.com','Outlook Microsoft365');
try{quote_outlook_web_url($input,'https://malicioso.invalid');throw new RuntimeException('Acepta host libre');}catch(InvalidArgumentException $e){}
