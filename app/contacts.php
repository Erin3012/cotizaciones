<?php
declare(strict_types=1);

function contact_data(array $data): array {
    $result=[];
    foreach(['name'=>150,'area'=>150,'email'=>190,'phone'=>60] as $key=>$max){
        $value=trim((string)($data[$key]??''));
        if(strlen($value)>$max) throw new InvalidArgumentException('El campo '.$key.' es demasiado largo.');
        $result[$key]=$value;
    }
    if($result['name']===''||$result['area']==='') throw new InvalidArgumentException('Completa el nombre de la persona y el área.');
    if($result['email']!==''&&!filter_var($result['email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Correo de contacto inválido.');
    return $result;
}

function resolve_quote_contact(PDO $pdo,int $clientId,array $post,?array $original=null): array {
    $mode=(string)($post['contact_mode']??'');
    if($mode==='preserve' && $original!==null){
        return ['id'=>$original['contact_id'],'name'=>$original['attention_name'],'area'=>$original['contact_area'],'email'=>$original['client_email'],'phone'=>$original['client_phone']];
    }
    if($mode==='existing'){
        $s=$pdo->prepare('SELECT * FROM client_contacts WHERE id=? AND client_id=? AND active=1');
        $s->execute([(int)($post['contact_id']??0),$clientId]);$contact=$s->fetch();
        if(!$contact || trim((string)$contact['area'])==='') throw new InvalidArgumentException('Selecciona un contacto activo de esta empresa con su área completa.');
        return $contact;
    }
    if($mode!=='new') throw new InvalidArgumentException('Selecciona un contacto o agrega uno nuevo.');
    $contact=contact_data(['name'=>$post['attention_name']??'','area'=>$post['contact_area']??'','email'=>$post['client_email']??'','phone'=>$post['client_phone']??'']);
    $s=$pdo->prepare('INSERT INTO client_contacts (client_id,name,area,email,phone) VALUES (?,?,?,?,?)');
    $s->execute([$clientId,$contact['name'],$contact['area'],$contact['email'],$contact['phone']]);
    $contact['id']=(int)$pdo->lastInsertId();return $contact;
}

function save_quote_contact(PDO $pdo,int $quoteId,array $contact): void {
    $s=$pdo->prepare('UPDATE quotes SET contact_id=?,contact_area=?,attention_name=?,client_email=?,client_phone=? WHERE id=?');
    $s->execute([$contact['id'],$contact['area'],$contact['name'],$contact['email'],$contact['phone'],$quoteId]);
}
