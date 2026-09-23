<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_login();
$id=(int)($_GET['id']??0);$s=db()->prepare('SELECT quote_number,total FROM quotes WHERE id=?');$s->execute([$id]);$q=$s->fetch();if(!$q)exit('Cotizacion no encontrada.');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Eliminar cotizacion</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="login-wrap"><section class="login-card"><p class="eyebrow">Accion irreversible</p><h1>Eliminar<br><em style="color:#b09d00;font-style:normal">cotizacion</em></h1><p>Se eliminara la cotizacion <strong><?=e($q['quote_number'])?></strong> y sus items. El cliente y la solicitud original se conservaran.</p><form method="post" action="index.php"><input type="hidden" name="action" value="delete_quote"><input type="hidden" name="quote_id" value="<?=$id?>"><?=csrf_field()?><div class="actions"><a class="btn btn-outline" href="index.php?page=quote&id=<?=$id?>">Cancelar</a><button class="btn btn-danger" type="submit">Eliminar definitivamente</button></div></form></section></main></body></html>
