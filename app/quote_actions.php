<?php
declare(strict_types=1);
require_once __DIR__.'/contacts.php';
require_once __DIR__.'/deletions.php';

function quote_expiry(string $issue, bool $custom, string $expiry=''): string
{
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$issue);
    if(!$date || $date->format('Y-m-d')!==$issue) throw new InvalidArgumentException('Fecha de emisión inválida.');
    if(!$custom) return $date->modify('+7 days')->format('Y-m-d');
    $end=DateTimeImmutable::createFromFormat('!Y-m-d',$expiry);
    if(!$end || $end->format('Y-m-d')!==$expiry || $end<$date) throw new InvalidArgumentException('Fecha de vencimiento inválida.');
    return $expiry;
}

function handle_save_quote_form(): never
{
    verify_csrf();
    $requestId=(int)($_POST['request_id']??0);
    $pdo=db(); $pdo->beginTransaction();
    try {
        if($requestId>0){
            $s=$pdo->prepare('SELECT r.*,c.name client_name,c.phone client_phone,c.email client_email,c.contact_name FROM quote_requests r JOIN clients c ON c.id=r.client_id WHERE r.id=?');
            $s->execute([$requestId]); $request=$s->fetch();
            if(!$request) throw new RuntimeException('Solicitud no encontrada.');
            $clientId=(int)$request['client_id'];
        } else {
            $clientName=trim((string)($_POST['client_name']??''));$rut=trim((string)($_POST['client_rut']??''));$email=trim((string)($_POST['client_email']??''));$phone=trim((string)($_POST['client_phone']??''));$address=trim((string)($_POST['client_address']??''));$contact=trim((string)($_POST['attention_name']??''));
            if($clientName===''||$rut===''||$address===''||$contact==='') exit('Completa nombre, RUT, dirección y atención del cliente.');
            if(!valid_rut($rut)) exit('El RUT del cliente no es válido. Usa el formato 12.345.678-9.');
            if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)) exit('El correo del cliente no es válido.');
            $normalized=strtoupper(preg_replace('/[^0-9K]/','',$rut));
            $s=$pdo->prepare("SELECT id FROM clients WHERE REPLACE(REPLACE(REPLACE(UPPER(rut),'.',''),'-',''),' ','')=?");$s->execute([$normalized]);$matches=$s->fetchAll();
            if(count($matches)>1) throw new RuntimeException('Hay más de una empresa registrada con este RUT. Revisa la ficha de clientes.');
            $clientId=$matches[0]['id']??null;
            if($clientId){ /* La ficha de empresa se administra por separado. */ }
            else{$s=$pdo->prepare('INSERT INTO clients (name,rut,email,phone,address,contact_name) VALUES (?,?,?,?,?,?)');$s->execute([$clientName,$rut,$email,$phone,$address,$contact]);$clientId=(int)$pdo->lastInsertId();}
            $firstDescription=trim((string)(((array)($_POST['description']??[]))[0]??'Cotización de trabajo'));
            $requestNumber=next_number('SOL','quote_requests','request_number');$s=$pdo->prepare('INSERT INTO quote_requests (request_number,client_id,service_product,technical_description,status) VALUES (?,?,?,?,?)');$s->execute([$requestNumber,$clientId,$firstDescription,trim((string)($_POST['notes']??''))?:$firstDescription,'preparation']);$requestId=(int)$pdo->lastInsertId();
            $request=['client_name'=>$clientName,'client_phone'=>$phone,'client_email'=>$email,'contact_name'=>$contact,'address'=>$address];
        }
        $recipient=resolve_quote_contact($pdo,(int)$clientId,$_POST);
        $issue=(string)($_POST['issue_date']??date('Y-m-d'));$expiry=(string)($_POST['expiry_date']??'');$number=trim((string)($_POST['quote_number']??''));
        if($number==='')$number=next_number('COT','quotes','quote_number');
        $expiry=quote_expiry($issue,($_POST['custom_expiry']??'')==='1',$expiry);
        if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._-]{2,29}$/',$number))exit('El folio contiene caracteres no permitidos.');
        $items=[];foreach((array)($_POST['description']??[]) as $i=>$description){$description=trim((string)$description);if($description==='')continue;$item=['description'=>$description,'quantity'=>(float)($_POST['quantity'][$i]??0),'unit'=>trim((string)($_POST['unit'][$i]??'c/u')),'unit_price'=>(float)($_POST['unit_price'][$i]??0),'discount_percent'=>(float)($_POST['discount_percent'][$i]??0)];if($item['quantity']<=0||$item['unit_price']<0||$item['discount_percent']<0||$item['discount_percent']>100)exit('Revisa cantidades, precios y descuentos.');$items[]=$item;}
        if(!$items)exit('Agrega al menos un ítem.');$totals=calculate_totals($items,19);
        $s=$pdo->prepare('INSERT INTO quotes (quote_number,request_id,issue_date,expiry_date,attention_name,client_phone,client_email,delivery_location,delivery_term,payment_method,status,tax_rate,subtotal,tax_amount,total,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->execute([$number,$requestId,$issue,$expiry,trim((string)($_POST['attention_name']??$request['contact_name']))?:null,trim((string)($_POST['client_phone']??$request['client_phone']))?:null,trim((string)($_POST['client_email']??$request['client_email']))?:null,trim((string)($_POST['delivery_location']??''))?:null,trim((string)($_POST['delivery_term']??''))?:null,trim((string)($_POST['payment_method']??''))?:null,'preparation',19,$totals['subtotal'],$totals['tax'],$totals['total'],trim((string)($_POST['notes']??''))?:null,current_user()['id']]);
        $quoteId=(int)$pdo->lastInsertId();$s=$pdo->prepare('INSERT INTO quote_items (quote_id,description,quantity,unit,unit_price,discount_percent,discount_amount,line_subtotal,line_total) VALUES (?,?,?,?,?,?,?,?,?)');foreach($items as $item){$calc=calculate_item($item['quantity'],$item['unit_price'],$item['discount_percent']);$s->execute([$quoteId,$item['description'],$item['quantity'],$item['unit'],$item['unit_price'],$item['discount_percent'],$calc['discount'],$calc['subtotal'],$calc['subtotal']]);}
        save_quote_contact($pdo,$quoteId,$recipient);
        add_history('quote',$quoteId,null,'preparation','Cotización creada.');$pdo->commit();flash('success','Cotización creada correctamente.');redirect('index.php?page=quote&id='.$quoteId);
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();exit('No se pudo guardar la cotización: '.e($e->getMessage()));}
}

function quote_form_items(): array
{
    $items=[];
    foreach((array)($_POST['description']??[]) as $i=>$description){
        $description=trim((string)$description);
        if($description==='') continue;
        $item=['description'=>$description,'quantity'=>(float)($_POST['quantity'][$i]??0),'unit'=>trim((string)($_POST['unit'][$i]??'c/u')),'unit_price'=>(float)($_POST['unit_price'][$i]??0),'discount_percent'=>(float)($_POST['discount_percent'][$i]??0)];
        if($item['quantity']<=0||$item['unit_price']<0||$item['discount_percent']<0||$item['discount_percent']>100) exit('Revisa cantidades, precios y descuentos.');
        $items[]=$item;
    }
    if(!$items) exit('Agrega al menos un ítem.');
    return $items;
}

function handle_update_quote_form(): never
{
    verify_csrf();
    $id=(int)($_POST['quote_id']??0);
    $issue=(string)($_POST['issue_date']??date('Y-m-d'));
    $expiry=(string)($_POST['expiry_date']??'');
    $number=trim((string)($_POST['quote_number']??''));
    try { $expiry=quote_expiry($issue,($_POST['custom_expiry']??'')==='1',$expiry); }
    catch(InvalidArgumentException $e){http_response_code(422);exit(e($e->getMessage()));}
    if($id<=0) exit('Cotización no encontrada.');
    if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._-]{2,29}$/',$number)) exit('El folio contiene caracteres no permitidos.');
    $items=quote_form_items(); $totals=calculate_totals($items,19); $pdo=db(); $pdo->beginTransaction();
    try {
        $s=$pdo->prepare('SELECT q.*,r.client_id FROM quotes q JOIN quote_requests r ON r.id=q.request_id WHERE q.id=? FOR UPDATE'); $s->execute([$id]); $original=$s->fetch();if(!$original) throw new RuntimeException('Cotización no encontrada.');
        $recipient=resolve_quote_contact($pdo,(int)$original['client_id'],$_POST,$original);
        $s=$pdo->prepare('UPDATE quotes SET quote_number=?,issue_date=?,expiry_date=?,attention_name=?,client_phone=?,client_email=?,delivery_location=?,delivery_term=?,payment_method=?,subtotal=?,tax_amount=?,total=?,notes=? WHERE id=?');
        $s->execute([$number,$issue,$expiry,trim((string)($_POST['attention_name']??''))?:null,trim((string)($_POST['client_phone']??''))?:null,trim((string)($_POST['client_email']??''))?:null,trim((string)($_POST['delivery_location']??''))?:null,trim((string)($_POST['delivery_term']??''))?:null,trim((string)($_POST['payment_method']??''))?:null,$totals['subtotal'],$totals['tax'],$totals['total'],trim((string)($_POST['notes']??''))?:null,$id]);
        $pdo->prepare('DELETE FROM quote_items WHERE quote_id=?')->execute([$id]);
        $s=$pdo->prepare('INSERT INTO quote_items (quote_id,description,quantity,unit,unit_price,discount_percent,discount_amount,line_subtotal,line_total) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach($items as $item){$calc=calculate_item($item['quantity'],$item['unit_price'],$item['discount_percent']);$s->execute([$id,$item['description'],$item['quantity'],$item['unit'],$item['unit_price'],$item['discount_percent'],$calc['discount'],$calc['subtotal'],$calc['subtotal']]);}
        save_quote_contact($pdo,$id,$recipient);
        log_action('updated','quote',$id,['quote_number'=>$number,'total'=>$totals['total']]); $pdo->commit(); flash('success','Cotización actualizada correctamente.'); redirect('index.php?page=quote&id='.$id);
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack(); exit('No se pudo actualizar la cotización: '.e($e->getMessage()));}
}

function handle_delete_quote(): never
{
    verify_csrf(); $id=(int)($_POST['quote_id']??0); if($id<=0) exit('Cotización no encontrada.'); $pdo=db(); $pdo->beginTransaction();
    try {
        $quote=delete_quote_record($pdo,$id);
        log_action('deleted','quote',$id,['quote_number'=>$quote['quote_number'],'total'=>$quote['total']]);
        $pdo->commit(); flash('success','Cotización eliminada correctamente.'); redirect('index.php?page=quotes');
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('error',$e instanceof InvalidArgumentException?$e->getMessage():'No se pudo eliminar la cotización. Intenta nuevamente.');redirect('index.php?page=quotes');}
}
