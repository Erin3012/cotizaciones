<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/deletions.php';
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('PRAGMA foreign_keys=ON');
$pdo->exec('CREATE TABLE clients(id INTEGER PRIMARY KEY,name TEXT,rut TEXT)');
$pdo->exec('CREATE TABLE quote_requests(id INTEGER PRIMARY KEY,client_id INTEGER REFERENCES clients(id))');
$pdo->exec('CREATE TABLE client_contacts(id INTEGER PRIMARY KEY,client_id INTEGER REFERENCES clients(id))');
$pdo->exec('CREATE TABLE quotes(id INTEGER PRIMARY KEY,request_id INTEGER REFERENCES quote_requests(id),quote_number TEXT,total INTEGER)');
$pdo->exec('CREATE TABLE quote_items(id INTEGER PRIMARY KEY,quote_id INTEGER REFERENCES quotes(id) ON DELETE CASCADE)');
$pdo->exec('CREATE TABLE request_attachments(id INTEGER PRIMARY KEY,request_id INTEGER REFERENCES quote_requests(id))');
$pdo->exec("INSERT INTO clients VALUES(1,'Cliente de prueba','1-9'),(2,'Otro cliente','2-7')");
$pdo->exec('INSERT INTO quote_requests VALUES(1,1),(2,1),(3,2)');
$pdo->exec('INSERT INTO client_contacts VALUES(1,1),(2,1),(3,2)');
$pdo->exec("INSERT INTO quotes VALUES(1,1,'PRUEBA-1',100),(2,2,'PRUEBA-2',200)");
$pdo->exec('INSERT INTO quote_items VALUES(1,1),(2,2)');
function assert_deletion(bool $value,string $message):void {if(!$value)throw new RuntimeException($message);}
function blocked_deletion(PDO $pdo,int $id):void {
    $pdo->beginTransaction();
    try{delete_client_records($pdo,$id);}catch(InvalidArgumentException $e){$pdo->rollBack();return;}
    $pdo->rollBack();throw new RuntimeException('Debió bloquear la eliminación.');
}
blocked_deletion($pdo,1);
assert_deletion((int)$pdo->query('SELECT COUNT(*) FROM client_contacts WHERE client_id=1')->fetchColumn()===2,'No debe borrar contactos al bloquear.');
$pdo->beginTransaction();delete_quote_record($pdo,1);$pdo->commit();
assert_deletion((int)$pdo->query('SELECT COUNT(*) FROM quote_items WHERE quote_id=1')->fetchColumn()===0,'Debe eliminar los ítems de la cotización.');
blocked_deletion($pdo,1);
$pdo->beginTransaction();delete_quote_record($pdo,2);$pdo->commit();
$pdo->exec('INSERT INTO request_attachments VALUES(1,1)');blocked_deletion($pdo,1);
assert_deletion((int)$pdo->query('SELECT COUNT(*) FROM quote_requests WHERE client_id=1')->fetchColumn()===2,'Debe conservar los respaldos al bloquear por adjuntos.');
$pdo->exec('DELETE FROM request_attachments');
$pdo->beginTransaction();delete_client_records($pdo,1);$pdo->rollBack();
assert_deletion((int)$pdo->query('SELECT COUNT(*) FROM clients WHERE id=1')->fetchColumn()===1,'Un error de auditoría debe permitir revertir toda la eliminación.');
$pdo->beginTransaction();$deleted=delete_client_records($pdo,1);$pdo->commit();
assert_deletion($deleted['id']===1,'Debe devolver datos para auditoría.');
foreach(['clients'=>'id','client_contacts'=>'client_id','quote_requests'=>'client_id'] as $table=>$column){
    assert_deletion((int)$pdo->query("SELECT COUNT(*) FROM $table WHERE $column=1")->fetchColumn()===0,'Debe eliminar únicamente los registros del cliente.');
    assert_deletion((int)$pdo->query("SELECT COUNT(*) FROM $table WHERE $column=2")->fetchColumn()===1,'Debe conservar el otro cliente y sus registros.');
}
blocked_deletion($pdo,1);
echo "OK: bloqueo con cotizaciones, eliminación individual e ítems, protección de adjuntos, rollback y aislamiento de clientes.\n";
