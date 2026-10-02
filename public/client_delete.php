<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_login();
$id=(int)($_GET['id']??0);
$s=db()->prepare('SELECT name,rut FROM clients WHERE id=?');$s->execute([$id]);$client=$s->fetch();
if(!$client){http_response_code(404);exit('Cliente no encontrado.');}
$s=db()->prepare('SELECT COUNT(*) FROM quotes q JOIN quote_requests r ON r.id=q.request_id WHERE r.client_id=?');$s->execute([$id]);$count=(int)$s->fetchColumn();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Eliminar cliente · Metalrubber</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="login-wrap"><section class="login-card">
<p class="eyebrow">Acción irreversible</p><h1>Eliminar cliente</h1>
<p><strong><?=e($client['name'])?></strong><br>RUT: <?=e($client['rut'])?></p>
<?php if($count):?>
<div class="alert error" role="alert">Este cliente tiene <?=$count?> cotización(es). Primero debes eliminarlas una por una.</div>
<div class="actions"><a class="btn btn-primary" href="index.php?page=quotes&q=<?=rawurlencode($client['rut'])?>">Ver cotizaciones del cliente</a><a class="btn btn-outline" href="clients.php?id=<?=$id?>">Volver</a></div>
<?php else:?>
<p>Se eliminarán la empresa, todos sus contactos y las solicitudes internas que ya no tengan cotizaciones. Esta acción no se puede deshacer. El registro de auditoría se conservará.</p>
<p>Si existen solicitudes antiguas con archivos adjuntos, se bloqueará la eliminación para proteger sus respaldos.</p>
<form method="post" action="clients.php"><input type="hidden" name="action" value="delete_company"><input type="hidden" name="client_id" value="<?=$id?>"><?=csrf_field()?>
<label><input type="checkbox" name="confirm_delete" value="1" required> Confirmo que quiero eliminar este cliente y sus contactos.</label>
<div class="actions"><a class="btn btn-outline" href="clients.php?id=<?=$id?>">Cancelar</a><button class="btn btn-danger" type="submit">Eliminar definitivamente</button></div></form>
<?php endif;?></section></main></body></html>
