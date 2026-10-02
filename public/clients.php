<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/contacts.php';
require_once dirname(__DIR__).'/app/clients.php';
require_once dirname(__DIR__).'/app/deletions.php';
require_login();
$id=(int)($_GET['id']??$_POST['client_id']??0);$error='';
$new=isset($_GET['new'])||($_POST['action']??'')==='create_company';
if($new)$id=0;
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$pdo=db();$pdo->beginTransaction();
    try{
        $action=(string)($_POST['action']??'');
        if($action!=='create_company'){
            $s=$pdo->prepare('SELECT id FROM clients WHERE id=?');$s->execute([$id]);if(!$s->fetchColumn())throw new RuntimeException('Empresa no encontrada.');
        }
        if($action==='delete_company'){
            if(($_POST['confirm_delete']??'')!=='1')throw new InvalidArgumentException('Confirma la eliminación del cliente.');
            $deleted=delete_client_records($pdo,$id);
            log_action('deleted','client',$id,['name'=>$deleted['name'],'rut'=>$deleted['rut']]);
            $pdo->commit();flash('success','Cliente y contactos eliminados correctamente.');redirect('clients.php');
        }elseif($action==='create_company'){
            $id=create_client($pdo,$_POST);
        }elseif($action==='company'){
            $name=trim((string)($_POST['name']??''));$address=trim((string)($_POST['address']??''));
            if($name===''||strlen($name)>180||$address===''||strlen($address)>255)throw new InvalidArgumentException('Completa razón social y dirección válidas.');
            $pdo->prepare('UPDATE clients SET name=?,address=? WHERE id=?')->execute([$name,$address,$id]);
        }elseif($action==='contact'){
            $contact=contact_data($_POST);$contactId=(int)($_POST['contact_id']??0);
            if($contactId){
                $s=$pdo->prepare('SELECT id FROM client_contacts WHERE id=? AND client_id=?');$s->execute([$contactId,$id]);if(!$s->fetchColumn())throw new RuntimeException('Contacto no encontrado.');
                $pdo->prepare('UPDATE client_contacts SET name=?,area=?,email=?,phone=? WHERE id=? AND client_id=?')->execute([$contact['name'],$contact['area'],$contact['email'],$contact['phone'],$contactId,$id]);
            }else{$pdo->prepare('INSERT INTO client_contacts (client_id,name,area,email,phone) VALUES (?,?,?,?,?)')->execute([$id,$contact['name'],$contact['area'],$contact['email'],$contact['phone']]);}
        }elseif($action==='toggle'){
            $pdo->prepare('UPDATE client_contacts SET active=1-active WHERE id=? AND client_id=?')->execute([(int)($_POST['contact_id']??0),$id]);
        }else{throw new InvalidArgumentException('Acción inválida.');}
        log_action($action,'client',$id);$pdo->commit();flash('success','Datos guardados.');redirect('clients.php?id='.$id);
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($new)$id=0;$error=$e instanceof InvalidArgumentException?$e->getMessage():'No se pudo guardar. Revisa los datos e intenta nuevamente.';}
}
$client=null;$contacts=[];$search=trim((string)($_GET['q']??''));
if($id){$s=db()->prepare('SELECT * FROM clients WHERE id=?');$s->execute([$id]);$client=$s->fetch();if(!$client){http_response_code(404);exit('Empresa no encontrada.');}$s=db()->prepare('SELECT * FROM client_contacts WHERE client_id=? ORDER BY active DESC,name,area');$s->execute([$id]);$contacts=$s->fetchAll();}
else{$s=db()->prepare('SELECT id,name,rut,address FROM clients WHERE name LIKE ? OR rut LIKE ? ORDER BY name');$s->execute(['%'.$search.'%','%'.$search.'%']);$clients=$s->fetchAll();}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Clientes · Metalrubber</title><link rel="stylesheet" href="assets/style.css"></head><body><div class="shell"><aside class="side"><a class="brand" href="index.php?page=quotes"><span class="brand-mark">M</span><span><strong>METALRUBBER</strong><small>Cotizaciones</small></span></a><nav class="nav"><a href="quote_new.php">Nueva cotización</a><a href="index.php?page=quotes">Cotizaciones</a><a class="active" href="clients.php">Clientes</a></nav></aside><div class="main"><header class="top"><button class="menu" aria-label="Abrir menú" onclick="document.querySelector('.shell').classList.toggle('menu-open')">☰</button><h1>Clientes</h1><a href="logout.php">Salir</a></header><main class="content">
<?php foreach(consume_flash() as $message):?><div class="alert success"><?=e($message['message'])?></div><?php endforeach;?>
<?php if($error):?><div class="alert error" role="alert"><?=e($error)?></div><?php endif;?>
<?php if($new):?>
<div class="actions"><a class="btn btn-outline" href="clients.php">Volver a clientes</a></div>
<section class="panel"><div class="panel-head"><h2>Nuevo cliente</h2></div><div class="panel-body">
<p>Registra la empresa. Después podrás agregar personas de contacto y sus áreas desde la ficha.</p>
<form method="post" action="clients.php?new=1"><input type="hidden" name="action" value="create_company"><?=csrf_field()?>
<div class="form-grid">
<div class="field"><label for="new-company-rut">RUT *</label><input class="input" id="new-company-rut" name="rut" value="<?=e($_POST['rut']??'')?>" maxlength="20" placeholder="76.271.277-6" required aria-describedby="rut-help"><small id="rut-help">Una empresa por RUT, con tantos contactos como necesites.</small></div>
<div class="field"><label for="new-company-name">Nombre o razón social *</label><input class="input" id="new-company-name" name="name" value="<?=e($_POST['name']??'')?>" maxlength="180" required></div>
<div class="field"><label for="new-company-address">Dirección *</label><input class="input" id="new-company-address" name="address" value="<?=e($_POST['address']??'')?>" maxlength="255" required></div>
</div><div class="actions"><button class="btn btn-primary">Guardar cliente</button><a class="btn btn-outline" href="clients.php">Cancelar</a></div>
</form></div></section>
<?php elseif(!$client):?>
<div class="actions"><a class="btn btn-primary" href="clients.php?new=1">Nuevo cliente</a></div>
<section class="panel"><div class="panel-head"><h2>Empresas y contactos</h2></div><div class="panel-body"><form method="get" class="filters"><div class="field"><label for="search">Nombre o RUT</label><input id="search" class="input" name="q" value="<?=e($search)?>"></div><button class="btn btn-dark">Buscar</button></form></div><div class="table-wrap"><table class="table"><thead><tr><th>Empresa</th><th>RUT</th><th>Dirección</th><th></th></tr></thead><tbody><?php foreach($clients as $c):?><tr><td><?=e($c['name'])?></td><td><?=e($c['rut'])?></td><td><?=e($c['address'])?></td><td><a href="clients.php?id=<?=$c['id']?>">Ver ficha y contactos</a></td></tr><?php endforeach;?></tbody></table></div><?php if(!$clients):?><p class="empty">No hay empresas con esta búsqueda.</p><?php endif;?></section>
<?php else:?>
<div class="actions"><a class="btn btn-danger" href="client_delete.php?id=<?=$id?>">Eliminar cliente</a></div>
<div class="actions"><a class="btn btn-outline" href="clients.php">Volver a clientes</a><a class="btn btn-outline" href="index.php?page=quotes&q=<?=rawurlencode($client['rut'])?>">Ver cotizaciones</a></div>
<section class="panel"><div class="panel-head"><h2><?=e($client['name'])?> · <?=e($client['rut'])?></h2></div><div class="panel-body"><form method="post"><input type="hidden" name="client_id" value="<?=$id?>"><input type="hidden" name="action" value="company"><?=csrf_field()?><div class="form-grid"><div class="field"><label for="company-name">Razón social</label><input class="input" id="company-name" name="name" value="<?=e($client['name'])?>" maxlength="180" required></div><div class="field"><label for="company-address">Dirección</label><input class="input" id="company-address" name="address" value="<?=e($client['address'])?>" maxlength="255" required></div></div><div class="actions"><button class="btn btn-primary">Guardar empresa</button></div></form></div></section>
<section class="panel"><div class="panel-head"><h2>Contactos de la empresa</h2></div><div class="panel-body">
<?php foreach($contacts as $c):?>
<details style="margin-bottom:18px;border-bottom:1px solid #ddd;padding:10px"><summary><?=e($c['name'])?> — <?=e($c['area']?:'Área pendiente')?> · <?=$c['active']?'Activo':'Inactivo'?></summary>
<form method="post"><input type="hidden" name="client_id" value="<?=$id?>"><input type="hidden" name="contact_id" value="<?=$c['id']?>"><input type="hidden" name="action" value="contact"><?=csrf_field()?>
<div class="form-grid"><?php foreach(['name'=>'Nombre de persona *','area'=>'Área *','email'=>'Correo','phone'=>'Teléfono'] as $key=>$label):?><div class="field"><label for="contact-<?=$c['id']?>-<?=$key?>"><?=$label?></label><input class="input" id="contact-<?=$c['id']?>-<?=$key?>" name="<?=$key?>" type="<?=$key==='email'?'email':'text'?>" value="<?=e($c[$key]??'')?>" <?=in_array($key,['name','area'],true)?'required':''?>></div><?php endforeach;?></div><div class="actions"><button class="btn btn-primary">Guardar contacto</button></div></form>
<form method="post"><input type="hidden" name="client_id" value="<?=$id?>"><input type="hidden" name="contact_id" value="<?=$c['id']?>"><input type="hidden" name="action" value="toggle"><?=csrf_field()?><div class="actions"><button class="btn btn-outline"><?=$c['active']?'Desactivar':'Reactivar'?></button></div></form></details>
<?php endforeach;?>
<h3>Agregar contacto</h3><form method="post"><input type="hidden" name="client_id" value="<?=$id?>"><input type="hidden" name="action" value="contact"><?=csrf_field()?>
<div class="form-grid"><?php foreach(['name'=>'Nombre de persona *','area'=>'Área *','email'=>'Correo','phone'=>'Teléfono'] as $key=>$label):?><div class="field"><label for="new-<?=$key?>"><?=$label?></label><input class="input" id="new-<?=$key?>" name="<?=$key?>" type="<?=$key==='email'?'email':'text'?>" <?=in_array($key,['name','area'],true)?'required':''?>></div><?php endforeach;?></div><div class="actions"><button class="btn btn-primary">Agregar contacto</button></div></form></div></section>
<?php endif;?></main></div></div></body></html>
