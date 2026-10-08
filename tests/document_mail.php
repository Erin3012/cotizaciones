<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/webmail.php';
function assert_document(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
function reject_mail(array $data):void{try{quote_mail_input($data);}catch(InvalidArgumentException $e){return;}throw new RuntimeException('Debió rechazar cabeceras inválidas.');}
$q=['id'=>1,'quote_number'=>'PRUEBA-2026-0001','issue_date'=>'2026-10-08','expiry_date'=>'2026-10-15','client_name'=>'Empresa de prueba (NO ENVIAR)','attention_name'=>'Persona de prueba','contact_area'=>'Ingeniería','rut'=>'76.271.277-6','address'=>'Dirección de prueba, Hualpén','client_phone'=>'','phone'=>'','client_email'=>'prueba@example.test','email'=>'','delivery_location'=>'Según acuerdo','delivery_term'=>'5 días hábiles','payment_method'=>'Orden de compra','subtotal'=>100000,'tax_amount'=>19000,'total'=>119000,'notes'=>'Documento sintético para verificar PDF. No corresponde a una cotización comercial.'];
$items=[['unit'=>'c/u','quantity'=>'2.000','description'=>'Membrana de teflón y goma esponja. Verificación de acentos y descripción técnica.','unit_price'=>50000,'discount_percent'=>0,'line_total'=>100000]];
$html=quote_document_html($q,$items);
assert_document(str_contains($html,'Valor unitario')&&!str_contains($html,'Valor Unit.'),'Etiqueta incorrecta.');
assert_document(str_contains($html,'08/10/2026')&&str_contains($html,'15/10/2026'),'Fechas incorrectas.');
$hash=quote_document_hash($q,$items);$changed=$q;$changed['contact_area']='Ventas';assert_document($hash!==quote_document_hash($changed,$items),'Debe detectar cambios del documento.');
$mail=quote_mail_input(['to'=>'prueba@example.test','subject'=>'Cotización de prueba — NO ENVIAR','body'=>'Texto de prueba con acentos.']);
reject_mail(['to'=>"x@example.test\r\nBcc: intruso@example.test",'subject'=>'Prueba','body'=>'Prueba']);
reject_mail(['to'=>'x@example.test','subject'=>"Prueba\nOtra cabecera",'body'=>'Prueba']);
reject_mail(['to'=>'','subject'=>'Prueba','body'=>'Prueba']);
$pdf=generate_quote_pdf($q,$items);assert_document(str_starts_with($pdf,'%PDF-'),'PDF inválido.');
$mime=quote_draft_mime($q,$mail,'<prueba-sin-envio@metalrubber.cl>',$pdf);
assert_document(str_contains($mime,'carlos.pedreros@metalrubber.cl'),'Remitente incorrecto.');
assert_document(str_contains($mime,'application/pdf')&&str_contains($mime,'PRUEBA-2026-0001.pdf'),'Adjunto faltante.');
assert_document(str_contains($mime,base64_encode(substr($pdf,0,30))),'Contenido PDF no adjunto.');
$eml=quote_outlook_eml($q,$mail,$pdf);
assert_document(str_contains($eml,'X-Unsent: 1'),'Archivo editable solicitado.');
assert_document(str_contains($eml,'multipart/alternative')&&str_contains($eml,'text/html'),'Alternativas HTML y texto.');
assert_document(str_contains($eml,'application/pdf')&&str_contains($eml,'PRUEBA-2026-0001.pdf'),'PDF incluido en EML.');
assert_document(str_contains(str_replace(["\r","\n"],'',$eml),base64_encode($pdf)),'PDF adjunto conserva bytes completos.');
assert_document(str_contains($eml,'carlos.pedreros@metalrubber.cl'),'EML conserva remitente.');
try{quote_outlook_eml($q,$mail,'archivo inválido');throw new RuntimeException('Aceptó PDF inválido');}catch(InvalidArgumentException $e){}
if(in_array('--render',$argv,true)){
    $dir=dirname(__DIR__).'/tmp/pdfs';if(!is_dir($dir))mkdir($dir,0700,true);
    file_put_contents($dir.'/quote-single.pdf',$pdf);
    $long=[];for($i=0;$i<32;$i++){$item=$items[0];$item['description']='Ítem '.($i+1).': '.str_repeat('Reparación técnica de piezas metálicas y productos de caucho. ',4);$long[]=$item;}
    file_put_contents($dir.'/quote-multiple.pdf',generate_quote_pdf($q,$long));
}
echo "OK: PDF, fechas, etiqueta, adjunto MIME, remitente, hash y cabeceras seguras. Ningún correo enviado.\n";
