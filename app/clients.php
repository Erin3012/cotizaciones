<?php
declare(strict_types=1);

function create_client(PDO $pdo, array $data): int {
    $name=trim((string)($data['name']??''));
    $address=trim((string)($data['address']??''));
    $rut=trim((string)($data['rut']??''));
    if($name===''||strlen($name)>180||$address===''||strlen($address)>255){
        throw new InvalidArgumentException('Completa nombre o razón social y dirección válidas.');
    }
    if(strlen($rut)>20||!preg_match('/^[0-9.kK\- ]+$/D',$rut)||!valid_rut($rut)){
        throw new InvalidArgumentException('Ingresa un RUT válido, incluyendo su dígito verificador.');
    }
    $key=strtoupper(str_replace(['.','-',' '],'',$rut));
    $body=substr($key,0,-1);
    if(!preg_match('/^[1-9][0-9]{0,7}$/D',$body)){
        throw new InvalidArgumentException('Ingresa un RUT válido, sin ceros iniciales.');
    }
    $s=$pdo->prepare('SELECT id FROM clients WHERE rut_key=?');
    $s->execute([$key]);
    if($s->fetchColumn()) throw new InvalidArgumentException('Ya existe una empresa con este RUT. Búscala en Clientes para agregar sus contactos.');
    $rut=number_format((int)$body,0,',','.').'-'.substr($key,-1);
    try{
        $pdo->prepare('INSERT INTO clients (name,rut,email,phone,address,contact_name) VALUES (?,?,?,?,?,?)')->execute([$name,$rut,'','',$address,'']);
    }catch(PDOException $e){
        if((string)$e->getCode()==='23000') throw new InvalidArgumentException('Ya existe una empresa con este RUT. Búscala en Clientes para agregar sus contactos.');
        throw $e;
    }
    return (int)$pdo->lastInsertId();
}
