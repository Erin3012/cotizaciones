<?php
declare(strict_types=1);
require_once __DIR__.'/document_mail.php';

final class FakeQuoteMailbox implements QuoteDraftMailbox {
    public array $messages=[];
    public bool $disconnect=false;
    public bool $cutOnFind=false;
    public bool $deny=false;
    public function find(string $messageId,bool $sent=false):array{
        if($this->deny)throw new RuntimeException('Acceso denegado de prueba.');
        if($this->cutOnFind){$this->cutOnFind=false;throw new RuntimeException('Desconexión de prueba.');}
        return isset($this->messages[$messageId])?[$this->messages[$messageId]]:[];
    }
    public function append(string $mime):bool{
        preg_match('/^Message-ID:\s*(.+)$/mi',$mime,$m);$id=trim($m[1]);
        $this->messages[$id]=count($this->messages)+1;$this->cutOnFind=$this->disconnect;$this->disconnect=false;return true;
    }
    public function close():void{}
}
function rejects_draft(callable $work):void{try{$work();}catch(RuntimeException|InvalidArgumentException $e){return;}throw new Exception('Se esperaba rechazo del borrador.');}
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$lockGranted=1;
$pdo->sqliteCreateFunction('GET_LOCK',function()use(&$lockGranted){return $lockGranted;},2);
$pdo->sqliteCreateFunction('RELEASE_LOCK',fn()=>1,1);
$pdo->exec('CREATE TABLE quote_email_drafts(id INTEGER PRIMARY KEY AUTOINCREMENT,quote_id INTEGER,quote_number TEXT,content_hash TEXT,quote_hash TEXT,message_id TEXT UNIQUE,mailbox_uid INTEGER,completed INTEGER DEFAULT 0,attempted INTEGER DEFAULT 0,created_by INTEGER,UNIQUE(quote_id,content_hash))');
$pdo->exec('CREATE TABLE audit_logs(id INTEGER PRIMARY KEY,action TEXT,entity_type TEXT,entity_id INTEGER,user_id INTEGER,payload_json TEXT)');
$box=new FakeQuoteMailbox();$factory=fn()=>'%PDF-test-only';
$first=prepare_quote_draft($pdo,$q,$items,$mail,1,false,$box,$factory);
$second=prepare_quote_draft($pdo,$q,$items,$mail,2,false,$box,$factory);
assert_document($first['id']===$second['id']&&count($box->messages)===1,'El doble clic debe reutilizar el mensaje.');
$changed=$q;$changed['notes']='Cotización modificada';
rejects_draft(fn()=>prepare_quote_draft($pdo,$changed,$items,$mail,2,false,$box,$factory));
$box->disconnect=true;
rejects_draft(fn()=>prepare_quote_draft($pdo,$changed,$items,$mail,2,true,$box,$factory));
assert_document(count($box->messages)===2,'El mensaje debe haberse creado antes de la desconexión.');
$recovered=prepare_quote_draft($pdo,$changed,$items,$mail,2,true,$box,$factory);
assert_document(count($box->messages)===2,'Recuperar por Message-ID no debe duplicar.');
$lockGranted=0;rejects_draft(fn()=>prepare_quote_draft($pdo,$q,$items,$mail,1,false,$box,$factory));$lockGranted=1;
$changed['notes']='Tercer cambio';$box->deny=true;
rejects_draft(fn()=>prepare_quote_draft($pdo,$changed,$items,$mail,1,true,$box,$factory));
assert_document(count($box->messages)===2,'La denegación no debe crear ni enviar mensajes.');$box->deny=false;
prepare_quote_draft($pdo,$changed,$items,$mail,1,true,$box,$factory);
assert_document(count($box->messages)===3,'Debe poder reintentar una conexión fallida antes del APPEND.');
$changed['notes']='Resultado incierto';$box->disconnect=true;
rejects_draft(fn()=>prepare_quote_draft($pdo,$changed,$items,$mail,1,true,$box,$factory));
$box->messages=[];
rejects_draft(fn()=>prepare_quote_draft($pdo,$changed,$items,$mail,1,true,$box,$factory));
assert_document(count($box->messages)===0,'Un resultado incierto nunca debe provocar otro APPEND ciego.');
echo "OK: doble clic, cambios confirmados, Message-ID, desconexión, concurrencia y conexión fallida. Transporte simulado; ningún correo enviado.\n";
