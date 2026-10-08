<?php
declare(strict_types=1);
require_once __DIR__.'/quote_document.php';

function generate_quote_pdf(array $quote,array $items): string {
    $autoload=dirname(__DIR__).'/vendor/autoload.php';
    if(!is_file($autoload))throw new RuntimeException('Instala las dependencias de Composer para generar PDF.');
    require_once $autoload;
    $storage=dirname(__DIR__).'/storage/pdf';
    if(!is_dir($storage)&&!mkdir($storage,0700,true)&&!is_dir($storage))throw new RuntimeException('No se pudo preparar el almacenamiento privado de PDF.');
    $options=new Dompdf\Options();
    $options->set('isRemoteEnabled',false);
    $options->set('isPhpEnabled',false);
    $options->set('isJavascriptEnabled',false);
    $options->set('defaultMediaType','print');
    $options->set('tempDir',$storage);
    $options->set('fontCache',$storage);
    $options->set('chroot',__DIR__);
    $dompdf=new Dompdf\Dompdf($options);
    $dompdf->loadHtml(quote_document_html($quote,$items),'UTF-8');
    $dompdf->setPaper('A4');
    $dompdf->render();
    return $dompdf->output();
}

function render_quote_pdf(array $quote,array $items): void {
    try{$bytes=generate_quote_pdf($quote,$items);}
    catch(Throwable $e){
        error_log('No se pudo generar PDF de cotización '.(int)$quote['id']);
        http_response_code(503);
        echo '<p>No se pudo generar el PDF. Puedes usar la impresión como alternativa.</p><a href="quote_print.php?id='.(int)$quote['id'].'">Imprimir / Guardar PDF</a>';
        return;
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="'.quote_pdf_filename($quote['quote_number']).'"');
    header('Content-Length: '.strlen($bytes));
    header('Cache-Control: private, no-store');
    echo $bytes;
}
