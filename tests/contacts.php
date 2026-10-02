<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/contacts.php';
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE client_contacts (id INTEGER PRIMARY KEY AUTOINCREMENT,client_id INTEGER,name TEXT,area TEXT,email TEXT,phone TEXT,active INTEGER DEFAULT 1)');
$pdo->exec('CREATE TABLE quotes (id INTEGER PRIMARY KEY,contact_id INTEGER,contact_area TEXT,attention_name TEXT,client_email TEXT,client_phone TEXT)');
function check(bool $condition,string $message): void {if(!$condition)throw new RuntimeException($message);}
function rejects(callable $work): void {try{$work();}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Se esperaba rechazo de contacto inválido.');}
$sales=resolve_quote_contact($pdo,1,['contact_mode'=>'new','attention_name'=>'Ana Pérez','contact_area'=>'Ventas','client_email'=>'ana@example.test','client_phone'=>'111']);
$hr=resolve_quote_contact($pdo,1,['contact_mode'=>'new','attention_name'=>'Pedro Soto','contact_area'=>'Recursos Humanos']);
check($sales['id']!==$hr['id'],'Dos contactos de una empresa deben ser registros distintos.');
$pdo->exec('INSERT INTO quotes (id) VALUES (1)');save_quote_contact($pdo,1,$sales);
$pdo->prepare('UPDATE client_contacts SET email=?,phone=? WHERE id=?')->execute(['nuevo@example.test','999',$sales['id']]);
$old=$pdo->query('SELECT * FROM quotes WHERE id=1')->fetch();
check($old['client_email']==='ana@example.test'&&$old['client_phone']==='111','La ficha no debe modificar la copia histórica.');
rejects(fn()=>resolve_quote_contact($pdo,2,['contact_mode'=>'existing','contact_id'=>$sales['id']]));
$pdo->prepare('UPDATE client_contacts SET active=0 WHERE id=?')->execute([$sales['id']]);
rejects(fn()=>resolve_quote_contact($pdo,1,['contact_mode'=>'existing','contact_id'=>$sales['id']]));
$preserved=resolve_quote_contact($pdo,1,['contact_mode'=>'preserve'],$old);
check($preserved['name']==='Ana Pérez'&&$preserved['email']==='ana@example.test','Debe conservar el destinatario aunque esté desactivado.');
rejects(fn()=>resolve_quote_contact($pdo,1,['contact_mode'=>'new','attention_name'=>'Persona sin área']));
$pdo->exec("INSERT INTO client_contacts(client_id,name,area,email,phone) VALUES(1,'Heredado',NULL,'','')");
rejects(fn()=>resolve_quote_contact($pdo,1,['contact_mode'=>'existing','contact_id'=>(int)$pdo->lastInsertId()]));
$selected=resolve_quote_contact($pdo,1,['contact_mode'=>'existing','contact_id'=>$hr['id']]);
check($selected['area']==='Recursos Humanos','El selector debe recuperar el área correcta.');
echo "OK: contactos separados, pertenencia, área obligatoria, desactivación y copia histórica.\n";
