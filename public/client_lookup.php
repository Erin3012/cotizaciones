<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$rut=trim((string)($_GET['rut']??''));
if($rut===''){echo json_encode(['found'=>false]);exit;}
$normalized=strtoupper(preg_replace('/[^0-9K]/','',$rut));
$s=db()->prepare("SELECT id,name,rut,email,phone,address,contact_name FROM clients WHERE rut=? OR REPLACE(REPLACE(REPLACE(UPPER(rut),'.',''),'-',''),' ','')=? LIMIT 1");
$s->execute([$rut,$normalized]);
$client=$s->fetch();
$contacts=[];
if($client){$s=db()->prepare('SELECT id,name,area,email,phone FROM client_contacts WHERE client_id=? AND active=1 ORDER BY name,area');$s->execute([$client['id']]);$contacts=$s->fetchAll();}
echo json_encode($client?['found'=>true,'client'=>$client,'contacts'=>$contacts]:['found'=>false],JSON_UNESCAPED_UNICODE);
