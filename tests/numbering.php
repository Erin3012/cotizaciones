<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/numbering.php';
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE number_counters(prefix TEXT,number_year INTEGER,last_value INTEGER,PRIMARY KEY(prefix,number_year))');
function check_number(bool $ok):void{if(!$ok)throw new RuntimeException('Prueba de numeración fallida.');}
$pdo->beginTransaction();check_number(allocate_number($pdo,'COT',2026)==='COT-2026-0001');check_number(allocate_number($pdo,'SOL',2026)==='SOL-2026-0001');$pdo->commit();
$pdo->beginTransaction();check_number(allocate_number($pdo,'COT',2026)==='COT-2026-0002');$pdo->rollBack();
$pdo->beginTransaction();check_number(allocate_number($pdo,'COT',2026)==='COT-2026-0002');$pdo->commit();
$pdo->beginTransaction();check_number(allocate_number($pdo,'COT',2027)==='COT-2027-0001');$pdo->commit();
try{allocate_number($pdo,'COT',2026);throw new RuntimeException('Debió exigir transacción.');}catch(LogicException $e){}
$error=new PDOException('deadlock',40001);check_number(retryable_transaction_error($error));
echo "OK: contadores por año y prefijo, rollback y reintentos limitados.\n";
