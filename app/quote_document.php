<?php
declare(strict_types=1);

function load_quote_document(PDO $pdo,int $id): array {
    $s=$pdo->prepare('SELECT q.*,c.name client_name,c.rut,c.email,c.phone,c.address,c.contact_name FROM quotes q JOIN quote_requests r ON r.id=q.request_id JOIN clients c ON c.id=r.client_id WHERE q.id=?');
    $s->execute([$id]);$q=$s->fetch();
    if(!$q)throw new InvalidArgumentException('Cotización no encontrada.');
    $s=$pdo->prepare('SELECT * FROM quote_items WHERE quote_id=? ORDER BY id');$s->execute([$id]);
    return [$q,$s->fetchAll()];
}

function quote_document_html(array $q,array $items): string {
    ob_start();
    try{require __DIR__.'/views/quote_document.php';return (string)ob_get_clean();}
    catch(Throwable $e){ob_end_clean();throw $e;}
}

function quote_pdf_filename(string $number): string {
    return preg_replace('/[^A-Za-z0-9._-]/','_',$number).'.pdf';
}
