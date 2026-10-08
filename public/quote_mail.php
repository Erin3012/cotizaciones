<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/webmail.php';
require_login();
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$id=(int)($_GET['id']??$_POST['quote_id']??0);$error='';
try{[$q,$items]=load_quote_document(db(),$id);}catch(InvalidArgumentException $e){http_response_code(404);exit(e($e->getMessage()));}
$quoteHash=quote_document_hash($q,$items);
$mail=['to'=>$q['client_email']??'','subject'=>'Cotización '.$q['quote_number'].' — Metalrubber Ltda.','body'=>"Estimado/a ".($q['attention_name']??'').",\n\nAdjunto la cotización ".$q['quote_number']." para su revisión.\n\nQuedamos atentos a sus comentarios.\n\nSaludos cordiales,\nMetalrubber Ltda."];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    try{
        $mail=quote_mail_input($_POST);
        if(!hash_equals($quoteHash,(string)($_POST['quote_hash']??'')))throw new InvalidArgumentException('La cotización cambió. Vuelve a abrir esta pantalla desde su detalle para obtener el PDF actualizado.');
        $url=quote_webmail_compose_url($mail);
        // No mailbox writes or sends: the user reviews and sends in webmail.
        session_write_close();
        if(($_POST['response']??'')==='json'){header('Content-Type: application/json; charset=utf-8');echo json_encode(['url'=>$url],JSON_THROW_ON_ERROR);exit;}
        header('Location: '.$url,true,303);exit;
    }catch(InvalidArgumentException|LengthException $e){
        $error=$e->getMessage();
        if(($_POST['response']??'')==='json'){http_response_code(422);header('Content-Type: application/json; charset=utf-8');echo json_encode(['error'=>$error],JSON_THROW_ON_ERROR);exit;}
    }
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="referrer" content="no-referrer"><title>Redactar en webmail · Metalrubber</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="content" style="max-width:950px;margin:auto">
<div class="actions"><a class="btn btn-outline" href="index.php?page=quote&amp;id=<?=$id?>">Volver a la cotización</a><a class="btn btn-outline" href="<?=e(QUOTE_WEBMAIL_URL)?>" target="_blank" rel="noopener noreferrer">Iniciar sesión en webmail</a></div>
<section class="panel"><div class="panel-head"><h1>Redactar correo · <?=e($q['quote_number'])?></h1></div><div class="panel-body">
<?php if($error):?><div class="alert error" role="alert"><?=e($error)?></div><?php endif;?>
<p>Primero descarga el PDF; después elige webmail u Outlook, adjúntalo y revisa el mensaje antes de enviarlo. Comprueba que el remitente sea <strong><?=e(QUOTE_MAIL_SENDER)?></strong>.</p>
<div class="actions"><a class="btn btn-outline" href="index.php?action=pdf&amp;id=<?=$id?>" target="_blank" rel="noopener noreferrer">Descargar <?=e(quote_pdf_filename($q['quote_number']))?></a></div>
<p>El PDF <strong>se adjunta manualmente</strong>. Este módulo no guarda borradores ni envía correos. Webmail puede autoguardar mientras redactas.</p>
<form method="post" action="quote_mail.php" target="_blank" rel="noopener noreferrer"><input type="hidden" name="quote_id" value="<?=$id?>"><input type="hidden" name="quote_hash" value="<?=e($quoteHash)?>"><?=csrf_field()?>
<div class="field"><label for="to">Destinatario *</label><input class="input" type="email" id="to" name="to" value="<?=e($mail['to'])?>" maxlength="190" required><button class="btn btn-outline" type="button" data-copy="to">Copiar destinatario</button></div>
<div class="field"><label for="subject">Asunto *</label><input class="input" id="subject" name="subject" value="<?=e($mail['subject'])?>" maxlength="250" required><button class="btn btn-outline" type="button" data-copy="subject">Copiar asunto</button></div>
<div class="field"><label for="body">Mensaje *</label><textarea class="textarea" id="body" name="body" rows="10" maxlength="20000" required><?=e($mail['body'])?></textarea><button class="btn btn-outline" type="button" data-copy="body">Copiar mensaje</button></div>
<p id="copy-status" role="status" aria-live="polite"></p>
<p><a id="mail-popup-fallback" class="btn btn-outline" target="_blank" rel="noopener noreferrer" hidden>Abrir webmail en otra pestaña</a></p>
<div class="actions"><button class="btn btn-primary" type="submit">Abrir redacción en webmail ↗</button><a class="btn btn-outline" id="outlook-compose" href="#">Abrir en Outlook</a></div>
</form>
<p>Outlook se abre en su propia ventana si está instalado y configurado como aplicación de correo predeterminada. Si no, el equipo abrirá la aplicación que tenga configurada. Debes seleccionar el remitente correcto y adjuntar el PDF manualmente.</p>
<p>Se abrirá otra pestaña con destinatario, asunto y mensaje. Si cPanel te pide iniciar sesión y no conserva los campos, deja esta página abierta y vuelve a pulsar el botón después de entrar; también puedes copiar los campos manualmente.</p>
</div></section></main><script src="assets/quote_mail.js" defer></script></body></html>
