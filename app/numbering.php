<?php
declare(strict_types=1);

function allocate_number(PDO $pdo,string $prefix,?int $year=null): string {
    if(!in_array($prefix,['COT','SOL'],true)||!$pdo->inTransaction())throw new LogicException('El folio requiere una transacción y un prefijo válido.');
    $year??=(int)(new DateTimeImmutable('now',new DateTimeZone('America/Santiago')))->format('Y');
    $mysql=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql';
    $sql=$mysql?'INSERT INTO number_counters(prefix,number_year,`last_value`) VALUES(?,?,0) ON DUPLICATE KEY UPDATE `last_value`=`last_value`':'INSERT INTO number_counters(prefix,number_year,`last_value`) VALUES(?,?,0) ON CONFLICT(prefix,number_year) DO NOTHING';
    $pdo->prepare($sql)->execute([$prefix,$year]);
    $s=$pdo->prepare('SELECT `last_value` FROM number_counters WHERE prefix=? AND number_year=?'.($mysql?' FOR UPDATE':''));
    $s->execute([$prefix,$year]);$next=(int)$s->fetchColumn()+1;
    $pdo->prepare('UPDATE number_counters SET `last_value`=? WHERE prefix=? AND number_year=?')->execute([$next,$prefix,$year]);
    return sprintf('%s-%04d-%04d',$prefix,$year,$next);
}

function retryable_transaction_error(Throwable $e): bool {
    return $e instanceof PDOException && ((string)$e->getCode()==='40001'||in_array((int)($e->errorInfo[1]??0),[1205,1213],true));
}
