<?php
declare(strict_types=1);

function handle_save_quote_form(): never
{
    verify_csrf();
    $requestId=(int)($_POST['request_id']??0);
    $pdo=db(); $pdo->beginTransaction();
    try {
        if($requestId>0){
            $s=$pdo->prepare('SELECT r.*,c.name client_name,c.phone client_phone,c.email client_email,c.contact_name FROM quote_requests r JOIN clients c ON c.id=r.client_id WHERE r.id=?');
            $s->execute([$requestId]); $request=$s->fetch();
            if(!$request) exit('Solicitud no encontrada.');
        } else {
            $clientName=trim((string)($_POST['client_name']??''));$rut=trim((string)($_POST['client_rut']??''));$email=trim((string)($_POST['client_email']??''));$phone=trim((string)($_POST['client_phone']??''));$address=trim((string)($_POST['client_address']??''));$contact=trim((string)($_POST['attention_name']??''));
            if($clientName===''||$rut===''||$email===''||$phone===''||$address===''||$contact==='') exit('Completa todos los datos obligatorios del cliente.');
            if(!valid_rut($rut)||!filter_var($email,FILTER_VALIDATE_EMAIL)) exit('Revisa el RUT y el correo del cliente.');
            $s=$pdo->prepare('SELECT id FROM clients WHERE rut=?');$s->execute([$rut]);$clientId=$s->fetchColumn();
            if($clientId){$s=$pdo->prepare('UPDATE clients SET name=?,email=?,phone=?,address=?,contact_name=? WHERE id=?');$s->execute([$clientName,$email,$phone,$address,$contact,$clientId]);}
            else{$s=$pdo->prepare('INSERT INTO clients (name,rut,email,phone,address,contact_name) VALUES (?,?,?,?,?,?)');$s->execute([$clientName,$rut,$email,$phone,$address,$contact]);$clientId=(int)$pdo->lastInsertId();}
            $firstDescription=trim((string)(((array)($_POST['description']??[]))[0]??'Cotización de trabajo'));
            $requestNumber=next_number('SOL','quote_requests','request_number');$s=$pdo->prepare('INSERT INTO quote_requests (request_number,client_id,service_product,technical_description,status) VALUES (?,?,?,?,?)');$s->execute([$requestNumber,$clientId,$firstDescription,trim((string)($_POST['notes']??''))?:$firstDescription,'preparation']);$requestId=(int)$pdo->lastInsertId();
            $request=['client_name'=>$clientName,'client_phone'=>$phone,'client_email'=>$email,'contact_name'=>$contact,'address'=>$address];
        }
        $issue=(string)($_POST['issue_date']??date('Y-m-d'));$expiry=(string)($_POST['expiry_date']??'');$number=trim((string)($_POST['quote_number']??''));
        if($number==='')$number=next_number('COT','quotes','quote_number');
        if(!$expiry||$expiry<$issue)exit('La fecha de vencimiento es obligatoria y válida.');
        if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._-]{2,29}$/',$number))exit('El folio contiene caracteres no permitidos.');
        $items=[];foreach((array)($_POST['description']??[]) as $i=>$description){$description=trim((string)$description);if($description==='')continue;$item=['description'=>$description,'quantity'=>(float)($_POST['quantity'][$i]??0),'unit'=>trim((string)($_POST['unit'][$i]??'c/u')),'unit_price'=>(float)($_POST['unit_price'][$i]??0),'discount_percent'=>(float)($_POST['discount_percent'][$i]??0)];if($item['quantity']<=0||$item['unit_price']<0||$item['discount_percent']<0||$item['discount_percent']>100)exit('Revisa cantidades, precios y descuentos.');$items[]=$item;}
        if(!$items)exit('Agrega al menos un ítem.');$totals=calculate_totals($items,19);
        $s=$pdo->prepare('INSERT INTO quotes (quote_number,request_id,issue_date,expiry_date,attention_name,client_phone,client_email,delivery_location,delivery_term,payment_method,status,tax_rate,subtotal,tax_amount,total,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->execute([$number,$requestId,$issue,$expiry,trim((string)($_POST['attention_name']??$request['contact_name']))?:null,trim((string)($_POST['client_phone']??$request['client_phone']))?:null,trim((string)($_POST['client_email']??$request['client_email']))?:null,trim((string)($_POST['delivery_location']??''))?:null,trim((string)($_POST['delivery_term']??''))?:null,trim((string)($_POST['payment_method']??''))?:null,'preparation',19,$totals['subtotal'],$totals['tax'],$totals['total'],trim((string)($_POST['notes']??''))?:null,current_user()['id']]);
        $quoteId=(int)$pdo->lastInsertId();$s=$pdo->prepare('INSERT INTO quote_items (quote_id,description,quantity,unit,unit_price,discount_percent,discount_amount,line_subtotal,line_total) VALUES (?,?,?,?,?,?,?,?,?)');foreach($items as $item){$calc=calculate_item($item['quantity'],$item['unit_price'],$item['discount_percent']);$s->execute([$quoteId,$item['description'],$item['quantity'],$item['unit'],$item['unit_price'],$item['discount_percent'],$calc['discount'],$calc['subtotal'],$calc['subtotal']]);}
        add_history('quote',$quoteId,null,'preparation','Cotización creada.');$pdo->commit();flash('success','Cotización creada correctamente.');redirect('index.php?page=quote&id='.$quoteId);
    } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();exit('No se pudo guardar la cotización: '.e($e->getMessage()));}
}

