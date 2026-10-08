<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/clients.php';
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE TABLE clients (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,rut TEXT NOT NULL,rut_key TEXT UNIQUE,email TEXT NOT NULL,phone TEXT NOT NULL,address TEXT NOT NULL,contact_name TEXT NOT NULL)");
// Older SQLite on cPanel lacks generated columns. Mirror MySQL's rut_key using a trigger in this fixture only.
$pdo->exec("CREATE TRIGGER client_rut_key AFTER INSERT ON clients BEGIN UPDATE clients SET rut_key=replace(replace(replace(upper(NEW.rut),'.',''),'-',''),' ','') WHERE id=NEW.id; END");
function check_client(bool $condition,string $message): void {if(!$condition)throw new RuntimeException($message);}
function rejects_client(callable $work): void {try{$work();}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Se esperaba rechazo de cliente inválido.');}
$data=['name'=>'Empresa de prueba','rut'=>'76.271.277-6','address'=>'Dirección de prueba'];
$id=create_client($pdo,$data);
check_client($id>0,'Debe crear un cliente sin cotización ni contacto.');
$stored=$pdo->query('SELECT * FROM clients')->fetch(PDO::FETCH_ASSOC);
check_client($stored['rut']===$data['rut']&&$stored['name']===$data['name'],'Debe conservar los datos de empresa.');
check_client($stored['email']===''&&$stored['phone']===''&&$stored['contact_name']==='','No debe inventar datos del contacto.');
rejects_client(fn()=>create_client($pdo,array_replace($data,['rut'=>'762712776'])));
rejects_client(fn()=>create_client($pdo,array_replace($data,['rut'=>'76.271.277-0'])));
rejects_client(fn()=>create_client($pdo,array_replace($data,['rut'=>'texto762712776'])));
rejects_client(fn()=>create_client($pdo,array_replace($data,['name'=>''])));
rejects_client(fn()=>create_client($pdo,array_replace($data,['address'=>''])));
check_client((int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn()===1,'Los errores no deben crear ni sobrescribir empresas.');
check_client($pdo->query('SELECT name FROM clients')->fetchColumn()===$data['name'],'Los duplicados no deben modificar la empresa.');
echo "OK: alta independiente, RUT válido, duplicados normalizados y campos obligatorios.\n";
