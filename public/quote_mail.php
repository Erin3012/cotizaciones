<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/webmail.php';
require_login();
$id=(int)($_GET['id']??$_POST['quote_id']??0);$error='';$success='';
try{[$q,$items]=load_quote_document(db(),$id);}catch(InvalidArgumentException $e){http_response_code(404);exit(e($e->getMessage()));}
$quoteHash=quote_document_hash($q,$items);
$mail=['to'=>$q['client_email']??'','subject'=>'Cotización '.$q['quote_number'].' — Metalrubber Ltda.','body'=>"Estimado/a ".($q['attention_name']??'').",\n\nAdjuntamos la cotización ".$q['quote_number']." para su revisión.\n\nQuedamos atentos a sus comentarios.\n\nSaludos cordiales,\nMetalrubber Ltda."];
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$mail=['to'=>(string)($_POST['to']??''),'subject'=>(string)($_POST['subject']??''),'body'=>(string)($_POST['body']??'')];
    try{
        if(!hash_equals($quoteHash,(string)($_POST['quote_hash']??'')))throw new InvalidArgumentException('La cotización cambió. Recarga esta página para preparar el PDF actualizado.');
        $result=prepare_quote_draft(db(),$q,$items,$mail,(int)current_user()['id'],($_POST['confirm_new']??'')==='1');
        $success=$result['reused']?'Este correo ya fue preparado. Consulta Borradores o Enviados en webmail.':'Borrador creado con el PDF adjunto. Revisa y envía el mensaje desde webmail.';
    }catch(Throwable $e){$error=$e instanceof InvalidArgumentException||$e instanceof RuntimeException&&!$e instanceof PDOException?$e->getMessage():'No se pudo preparar el borrador. La cotización sigue guardada; intenta nuevamente.';}
}
$s=db()->prepare('SELECT COUNT(*) FROM quote_email_drafts WHERE quote_id=?');$s->execute([$id]);$hasPrevious=(int)$s->fetchColumn()>0;
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Preparar correo · Metalrubber</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="content" style="max-width:950px;margin:auto">
<div class="actions"><a class="btn btn-outline" href="index.php?page=quote&amp;id=<?=$id?>">Volver a la cotización</a><a class="btn btn-outline" href="<?=e(QUOTE_WEBMAIL_URL)?>" target="_blank" rel="noopener noreferrer">Abrir webmail</a></div>
<section class="panel"><div class="panel-head"><h1>Preparar correo · <?=e($q['quote_number'])?></h1></div><div class="panel-body">
<?php if($error):?><div class="alert error" role="alert"><?=e($error)?></div><?php endif;?>
<?php if($success):?><div class="alert success" role="status"><?=e($success)?></div><?php endif;?>
<p>Remitente: <strong><?=e(QUOTE_MAIL_SENDER)?></strong>. Se adjuntará <strong><?=e(quote_pdf_filename($q['quote_number']))?></strong>. No se enviará ningún mensaje automáticamente.</p>
<?php if(!quote_mail_configured()):?><div class="alert error">El correo está deshabilitado hasta que se configure y verifique el buzón en el servidor. Puedes seguir descargando o imprimiendo la cotización.</div><?php endif;?>
<form method="post"><input type="hidden" name="quote_id" value="<?=$id?>"><input type="hidden" name="quote_hash" value="<?=e($quoteHash)?>"><?=csrf_field()?>
<div class="field"><label for="to">Destinatario *</label><input class="input" type="email" id="to" name="to" value="<?=e($mail['to'])?>" maxlength="190" required></div>
<div class="field"><label for="subject">Asunto *</label><input class="input" id="subject" name="subject" value="<?=e($mail['subject'])?>" maxlength="250" required></div>
<div class="field"><label for="body">Mensaje *</label><textarea class="textarea" id="body" name="body" rows="10" maxlength="20000" required><?=e($mail['body'])?></textarea></div>
<?php if($hasPrevious):?><p>Ya se preparó un correo para esta cotización. Si el contenido es igual se reutilizará; si cambió, el anterior no se modificará.</p><label><input type="checkbox" name="confirm_new" value="1"> Confirmo crear un nuevo borrador si la cotización o el mensaje cambiaron.</label><?php endif;?>
<div class="actions"><button class="btn btn-primary" type="submit" <?=quote_mail_configured()?'':'disabled'?>>Preparar borrador con PDF</button></div></form></div></section></main></body></html>
