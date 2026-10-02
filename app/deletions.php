<?php
declare(strict_types=1);

// The caller owns the transaction so deletion and its audit entry commit together.
function delete_client_records(PDO $pdo,int $id): array {
    if(!$pdo->inTransaction()) throw new LogicException('La eliminación requiere una transacción.');
    $lock=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
    $s=$pdo->prepare('SELECT id,name,rut FROM clients WHERE id=?'.$lock);
    $s->execute([$id]);$client=$s->fetch(PDO::FETCH_ASSOC);
    if(!$client) throw new InvalidArgumentException('Cliente no encontrado.');
    $s=$pdo->prepare('SELECT id FROM quote_requests WHERE client_id=?'.$lock);
    $s->execute([$id]);$s->fetchAll();
    $s=$pdo->prepare('SELECT COUNT(*) FROM quotes q JOIN quote_requests r ON r.id=q.request_id WHERE r.client_id=?');
    $s->execute([$id]);
    if((int)$s->fetchColumn()>0) throw new InvalidArgumentException('Primero debes eliminar cada una de las cotizaciones de este cliente.');
    $s=$pdo->prepare('SELECT COUNT(*) FROM request_attachments a JOIN quote_requests r ON r.id=a.request_id WHERE r.client_id=?');
    $s->execute([$id]);
    if((int)$s->fetchColumn()>0) throw new InvalidArgumentException('Este cliente conserva solicitudes con archivos adjuntos. No se eliminará para evitar perder sus respaldos.');
    $pdo->prepare('DELETE FROM client_contacts WHERE client_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM quote_requests WHERE client_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM clients WHERE id=?')->execute([$id]);
    return $client;
}

function delete_quote_record(PDO $pdo,int $id): array {
    if(!$pdo->inTransaction()) throw new LogicException('La eliminación requiere una transacción.');
    $lock=$pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
    $s=$pdo->prepare('SELECT quote_number,total FROM quotes WHERE id=?'.$lock);
    $s->execute([$id]);$quote=$s->fetch(PDO::FETCH_ASSOC);
    if(!$quote) throw new InvalidArgumentException('Cotización no encontrada.');
    $pdo->prepare('DELETE FROM quotes WHERE id=?')->execute([$id]);
    return $quote;
}
