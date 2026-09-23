<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_login();
header('Content-Type: application/json; charset=utf-8');
$rut=trim((string)($_GET['rut']??''));
if($rut===''){echo json_encode(['found'=>false]);exit;}
$normalized=strtoupper(preg_replace('/[^0-9K]/','',$rut));
$s=db()->prepare("SELECT id,name,rut,email,phone,address,contact_name FROM clients WHERE rut=? OR REPLACE(REPLACE(UPPER(rut),'.',''),'-','')=? LIMIT 1");
$s->execute([$rut,$normalized]);
$client=$s->fetch();
echo json_encode($client?['found'=>true,'client'=>$client]:['found'=>false],JSON_UNESCAPED_UNICODE);
