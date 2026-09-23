<?php
declare(strict_types=1);

// Cargador voluntario de datos DEMO. Ejecutar solo desde CLI y nunca como URL web.
if(PHP_SAPI!=='cli'){http_response_code(403);exit("Solo CLI\n");}
require_once dirname(__DIR__).'/app/bootstrap.php';

$pdo=db();
$existing=(int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE quote_number LIKE 'DEMO-%'")->fetchColumn();
if($existing>0){exit("Ya existen {$existing} cotizaciones DEMO. No se agregaron duplicados.\n");}
$admin=(int)($pdo->query("SELECT id FROM users WHERE active=1 ORDER BY id LIMIT 1")->fetchColumn()?:0);
$companyNames=['Ingenieria FerroSur SpA','Mantenciones Industriales del BioBio Ltda.','Transportes Carga Andina SpA','Servicios Metalurgicos del Pacifico Ltda.','Ferrocarriles del Valle SpA','Maestranza Los Aromos Ltda.','Operaciones Portuarias del Sur SpA','Cauchos Tecnicos del Pacifico Ltda.','Estructuras y Montajes Coronel SpA','Logistica Industrial Arauco Ltda.','Mecanizados Gran Concepcion SpA','Servicios Mineros Cordillera Sur Ltda.','Talleres Industriales Hualpen SpA','Insumos Ferroviarios del Sur Ltda.','Construcciones Metalicas BioBio SpA','Mantencion Planta Costa Sur Ltda.','Proyectos Industriales Andalién SpA','Suministros Tecnicos del Biobio Ltda.','Maestranza y Montajes del Itata SpA','Soluciones Industriales Viento Sur Ltda.'];
$rutBodies=[76900101,76900113,76900127,76900139,76900145,76900158,76900162,76900176,76900180,76900194,76900208,76900211,76900225,76900237,76900241,76900256,76900263,76900279,76900284,76900298];
$contacts=['Mauricio Valdes','Carolina Saavedra','Rodrigo Paredes','Daniela Munoz','Felipe Contreras','Paola Riquelme','Andres Sepulveda','Marcela Aravena','Cristian Neira','Veronica Salgado','Jorge Cisternas','Natalia Herrera','Patricio Molina','Claudia Bustos','Sergio Figueroa','Alejandra Parra','Gonzalo Carrasco','Lorena Fuentes','Hector Sanhueza','Tamara Espinoza'];
$addresses=['Parque Industrial Escuadron, Coronel','Av. Gran Bretana, Talcahuano','Camino a Nonguen, Concepcion','Sector El Arenal, Hualpen','Av. Cristobal Colon, Talcahuano','Parque Industrial Michaihue, San Pedro de la Paz','Ruta Interportuaria, Talcahuano','Av. Jorge Alessandri, Concepcion','Camino a Lota, Coronel','Sector Industrial El Manzano, Hualpen'];
$companies=[];
for($i=1;$i<=20;$i++){
    $rutBody=$rutBodies[$i-1];
    $body=(string)$rutBody;$sum=0;$mult=2;
    for($j=strlen($body)-1;$j>=0;$j--){$sum+=(int)$body[$j]*$mult;$mult=$mult===7?2:$mult+1;}
    $dv=11-($sum%11);$dv=$dv===11?'0':($dv===10?'K':(string)$dv);
    $companies[]=['name'=>$companyNames[$i-1].' (DEMO)','rut'=>number_format($rutBody,0,',','.').'-'.$dv,'email'=>'contacto'.$i.'@demo-metalrubber.example','phone'=>'+56 41 317 '.str_pad((string)(7100+$i),4,'0',STR_PAD_LEFT),'address'=>$addresses[($i-1)%count($addresses)],'contact_name'=>$contacts[$i-1]];
}
$templates=[
    ['service'=>'Reparacion de boguies ferroviarios','description'=>'Reparacion, ajuste y revision de boguies para segunda quincena de agosto.','material'=>'Acero estructural','unit'=>'servicio','min'=>450000,'max'=>1800000],
    ['service'=>'Suministro de gas FEPASA','description'=>'Suministro y servicio asociado a requerimiento de gas industrial.','material'=>'Gas industrial','unit'=>'lote','min'=>120000,'max'=>950000],
    ['service'=>'Membrana de teflon y goma esponja','description'=>'Fabricacion y suministro de membrana de teflon con goma esponja a medida.','material'=>'PTFE y goma esponja','unit'=>'un','min'=>85000,'max'=>650000],
];
mt_srand(20260923);
$pdo->beginTransaction();
try{
    $clientStmt=$pdo->prepare('INSERT INTO clients (name,rut,email,phone,address,contact_name) VALUES (?,?,?,?,?,?)');
    $clientIds=[];
    foreach($companies as $client){$clientStmt->execute(array_values($client));$clientIds[]=(int)$pdo->lastInsertId();}
    $requestStmt=$pdo->prepare('INSERT INTO quote_requests (request_number,client_id,service_product,technical_description,material,quantity,unit,measurements,observations,status) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $quoteStmt=$pdo->prepare('INSERT INTO quotes (quote_number,request_id,issue_date,expiry_date,attention_name,client_phone,client_email,delivery_location,delivery_term,payment_method,status,tax_rate,subtotal,tax_amount,total,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $itemStmt=$pdo->prepare('INSERT INTO quote_items (quote_id,description,quantity,unit,unit_price,discount_percent,discount_amount,line_subtotal,line_total) VALUES (?,?,?,?,?,?,?,?,?)');
    for($i=1;$i<=100;$i++){
        $clientIndex=mt_rand(0,count($clientIds)-1);$client=$companies[$clientIndex];$type=$templates[($i-1)%count($templates)];$quantity=mt_rand(1,12);$unitPrice=mt_rand((int)($type['min']/1000),(int)($type['max']/1000))*1000;$subtotal=$quantity*$unitPrice;$tax=round($subtotal*0.19,2);$total=$subtotal+$tax;$issue=(new DateTimeImmutable('2025-01-01'))->modify('+'.mt_rand(0,630).' days')->format('Y-m-d');$expiry=(new DateTimeImmutable($issue))->modify('+30 days')->format('Y-m-d');$requestNumber=sprintf('DEMOSOL-2026-%04d',$i);$quoteNumber=sprintf('DEMO-2026-%04d',$i);
        $requestStmt->execute([$requestNumber,$clientIds[$clientIndex],$type['service'],$type['description'],$type['material'],$quantity,$type['unit'],'Medidas segun plano o muestra DEMO', 'Registro de demostracion','preparation']);$requestId=(int)$pdo->lastInsertId();
        $quoteStmt->execute([$quoteNumber,$requestId,$issue,$expiry,$client['contact_name'],$client['phone'],$client['email'],'Planta Metalrubber, Hualpen','A coordinar','Orden de compra','preparation',19,$subtotal,$tax,$total,'Cotizacion de demostracion basada en ejemplos de trabajos metalmecanicos y caucho.',$admin?:null]);$quoteId=(int)$pdo->lastInsertId();
        $itemStmt->execute([$quoteId,$type['description'],$quantity,$type['unit'],$unitPrice,0,0,$subtotal,$subtotal]);
    }
    $pdo->commit();echo "Carga DEMO completada: 20 clientes y 100 cotizaciones.\n";
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,"No se pudo completar la carga DEMO: ".$e->getMessage()."\n");exit(1);}
