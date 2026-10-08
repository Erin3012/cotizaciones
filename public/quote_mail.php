<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/webmail.php';
require_login();
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if($_SERVER['REQUEST_METHOD']!=='POST'){
    $id=(int)($_GET['id']??0);
    redirect($id>0?'index.php?page=quote&id='.$id:'index.php?page=quotes');
}
verify_csrf();
$id=(int)($_POST['quote_id']??0);
$error='';$code=503;
try{
    [$q,$items]=load_quote_document(db(),$id);
    $mail=[
        'to'=>$q['client_email']??'',
        'subject'=>'Cotización '.$q['quote_number'].' — Metalrubber Ltda.',
        'body'=>"Estimado/a ".($q['attention_name']??'').",\n\nAdjunto la cotización ".$q['quote_number']." para su revisión.\n\nQuedamos atentos a sus comentarios.\n\nSaludos cordiales,\nMetalrubber Ltda.",
    ];
    $mail=quote_mail_input($mail);
    // Always use the latest saved snapshot; never create or send a mailbox message.
    $bytes=quote_outlook_eml($q,$mail,generate_quote_pdf($q,$items));
    $name=substr(quote_pdf_filename($q['quote_number']),0,-4).'.eml';
    session_write_close();
    header('Content-Type: message/rfc822');
    header('Content-Disposition: attachment; filename="'.$name.'"');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: '.strlen($bytes));
    echo $bytes;exit;
}catch(InvalidArgumentException $e){
    $code=422;
    $error=$e->getMessage()==='Cotización no encontrada.'?'Cotización no encontrada.':'Revisa el correo del contacto guardado en la cotización antes de descargar el archivo.';
}catch(Throwable $e){
    $error='No se pudo generar el correo con PDF. La cotización sigue guardada; puedes descargar el PDF por separado.';
}
if(($_POST['response']??'')==='json'){
    http_response_code($code);header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>$error],JSON_THROW_ON_ERROR);exit;
}
flash('error',$error);
redirect('index.php?page=quote&id='.$id);
