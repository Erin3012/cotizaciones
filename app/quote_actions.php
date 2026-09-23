<?php
declare(strict_types=1);

function handle_save_quote_form(): never
{
    verify_csrf();
    $requestId=(int)($_POST['request_id']??0);
    $s=db()->prepare('SELECT r.*,c.name client_name,c.phone client_phone,c.email client_email,c.contact_name FROM quote_requests r JOIN clients c ON c.id=r.client_id WHERE r.id=?');
    $s->execute([$requestId]); $request=$s->fetch();
    if(!$request) exit('Solicitud no encontrada.');
    $issue=(string)($_POST['issue_date']??date('Y-m-d'));
    $expiry=(string)($_POST['expiry_date']??'');
    $number=trim((string)($_POST['quote_number']??''));
    if($number==='') $number=next_number('COT','quotes','quote_number');
    if(!$expiry||$expiry<$issue) exit('La fecha de vencimiento es obligatoria y válida.');
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._-]{2,29}$/',$number)) exit('El folio contiene caracteres no permitidos.');
    $items=[];
    foreach((array)($_POST['description']??[]) as $i=>$description){
        $description=trim((string)$description);
        if($description==='') continue;
        $item=['description'=>$description,'quantity'=>(float)($_POST['quantity'][$i]??0),'unit'=>trim((string)($_POST['unit'][$i]??'c/u')),'unit_price'=>(float)($_POST['unit_price'][$i]??0),'discount_percent'=>(float)($_POST['discount_percent'][$i]??0)];
        if($item['quantity']<=0||$item['unit_price']<0||$item['discount_percent']<0||$item['discount_percent']>100) exit('Revisa cantidades, precios y descuentos.');
        $items[]=$item;
    }
    if(!$items) exit('Agrega al menos un ítem.');
    $totals=calculate_totals($items,19);
    $pdo=db(); $pdo->beginTransaction();
    try {
        $s=$pdo->prepare('INSERT INTO quotes (quote_number,request_id,issue_date,expiry_date,attention_name,client_phone,client_email,delivery_location,delivery_term,payment_method,status,tax_rate,subtotal,tax_amount,total,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->execute([$number,$requestId,$issue,$expiry,trim((string)($_POST['attention_name']??$request['contact_name']))?:null,trim((string)($_POST['client_phone']??$request['client_phone']))?:null,trim((string)($_POST['client_email']??$request['client_email']))?:null,trim((string)($_POST['delivery_location']??''))?:null,trim((string)($_POST['delivery_term']??''))?:null,trim((string)($_POST['payment_method']??''))?:null,'preparation',19,$totals['subtotal'],$totals['tax'],$totals['total'],trim((string)($_POST['notes']??''))?:null,current_user()['id']]);
        $quoteId=(int)$pdo->lastInsertId();
        $s=$pdo->prepare('INSERT INTO quote_items (quote_id,description,quantity,unit,unit_price,discount_percent,discount_amount,line_subtotal,line_total) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach($items as $item){$calc=calculate_item($item['quantity'],$item['unit_price'],$item['discount_percent']);$s->execute([$quoteId,$item['description'],$item['quantity'],$item['unit'],$item['unit_price'],$item['discount_percent'],$calc['discount'],$calc['subtotal'],$calc['subtotal']]);}
        $pdo->prepare('UPDATE quote_requests SET status="preparation" WHERE id=?')->execute([$requestId]);
        add_history('quote',$quoteId,null,'preparation','Cotización creada.');
        $pdo->commit(); flash('success','Cotización creada correctamente.'); redirect('index.php?page=quote&id='.$quoteId);
    } catch(Throwable $e) { $pdo->rollBack(); exit('No se pudo guardar la cotización: '.e($e->getMessage())); }
}

