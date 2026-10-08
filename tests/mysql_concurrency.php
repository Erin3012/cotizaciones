<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/numbering.php';
if(in_array('--worker',$argv,true)){
    $pdo=db();$pdo->beginTransaction();
    $number=allocate_number($pdo,'COT',9998);usleep(200000);$pdo->commit();echo $number;exit;
}
if(!in_array('--run',$argv,true))exit("Usar --run para probar MySQL con dos procesos, sin crear cotizaciones ni clientes.\n");
$pdo=db();
if($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)!=='mysql')throw new RuntimeException('Esta prueba requiere MySQL.');
$s=$pdo->query("SELECT COUNT(*) FROM number_counters WHERE prefix='COT' AND number_year=9998");
if((int)$s->fetchColumn()!==0)throw new RuntimeException('El contador de prueba ya existe; no se modificará.');
$workers=[];
try{
    for($i=0;$i<2;$i++){
        $process=proc_open([PHP_BINARY,__FILE__,'--worker'],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($process))throw new RuntimeException('No se pudo iniciar trabajador.');
        $workers[]=[$process,$pipes];
    }
    $numbers=[];
    foreach($workers as [$process,$pipes]){
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($process)!==0)throw new RuntimeException('Falló el trabajador de prueba.');
        $numbers[]=trim($out);
    }
    sort($numbers);
    if($numbers!==['COT-9998-0001','COT-9998-0002'])throw new RuntimeException('Los dos procesos no obtuvieron códigos únicos.');
    echo "OK: dos procesos simultáneos, dos folios distintos. Sin clientes ni cotizaciones de prueba.\n";
}finally{
    $pdo->exec("DELETE FROM number_counters WHERE prefix='COT' AND number_year=9998 AND `last_value`<=2");
}
