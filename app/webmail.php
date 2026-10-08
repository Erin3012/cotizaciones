<?php
declare(strict_types=1);
require_once __DIR__.'/pdf.php';

const QUOTE_MAIL_SENDER='carlos.pedreros@metalrubber.cl';
const QUOTE_WEBMAIL_URL='https://metalrubber.cl:2096';

function quote_mail_configured(): bool {
    // This module no longer writes messages to the mailbox.
    return false;
}

function quote_webmail_compose_url(array $input): string {
    $mail=quote_mail_input($input);
    // Roundcube treats '<' as HTML: escape text before adding line breaks.
    $body='<p>'.nl2br(htmlspecialchars($mail['body'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),false).'</p>';
    $url=QUOTE_WEBMAIL_URL.'/3rdparty/roundcube/?'.http_build_query([
        '_task'=>'mail','_action'=>'compose','_to'=>$mail['to'],
        '_subject'=>$mail['subject'],'_body'=>$body,
    ],'', '&',PHP_QUERY_RFC3986);
    if(strlen($url)>2000)throw new LengthException('El mensaje es demasiado largo para abrirlo mediante un enlace. Abre webmail y copia los campos con los botones de abajo.');
    return $url;
}

function quote_mail_input(array $data): array {
    $to=trim((string)($data['to']??''));$subject=trim((string)($data['subject']??''));$body=trim((string)($data['body']??''));
    if(strlen($to)>190||!filter_var($to,FILTER_VALIDATE_EMAIL)||preg_match('/[\r\n]/',$to))throw new InvalidArgumentException('Ingresa un correo destinatario válido.');
    if($subject===''||strlen($subject)>250||preg_match('/[\r\n]/',$subject))throw new InvalidArgumentException('Ingresa un asunto válido, sin saltos de línea.');
    if($body===''||strlen($body)>20000)throw new InvalidArgumentException('Completa el mensaje (máximo 20.000 bytes).');
    return ['to'=>$to,'subject'=>$subject,'body'=>$body];
}

function quote_document_hash(array $q,array $items): string {
    return hash('sha256',quote_document_html($q,$items));
}

function quote_draft_mime(array $q,array $mail,string $messageId,string $pdf): string {
    require_once dirname(__DIR__).'/vendor/autoload.php';
    $message=new PHPMailer\PHPMailer\PHPMailer(true);
    $message->CharSet='UTF-8';
    $message->setFrom(QUOTE_MAIL_SENDER,'Metalrubber Ltda.');
    $message->addAddress($mail['to']);$message->Subject=$mail['subject'];$message->Body=$mail['body'];
    $message->MessageID=$messageId;
    $message->addCustomHeader('X-Unsent','1');
    $message->addStringAttachment($pdf,quote_pdf_filename($q['quote_number']),'base64','application/pdf');
    // preSend only builds MIME; never call send() or postSend().
    $message->preSend();
    return $message->getSentMIMEMessage();
}

function imap_quote_endpoint(string $folder): string {
    $host=env_value('QUOTE_IMAP_HOST');$port=(int)env_value('QUOTE_IMAP_PORT','993');
    if(!preg_match('/^[a-zA-Z0-9.-]+$/D',$host)||$port<1||$port>65535||!preg_match('/^[a-zA-Z0-9._ -]+$/D',$folder))throw new RuntimeException('Configuración IMAP inválida.');
    return '{'.$host.':'.$port.'/imap/ssl/validate-cert}'.$folder;
}

interface QuoteDraftMailbox {
    public function find(string $messageId,bool $sent=false): array;
    public function append(string $mime): bool;
    public function close(): void;
}

final class NativeQuoteDraftMailbox implements QuoteDraftMailbox {
    private $connection;
    public function __construct(){
        throw new RuntimeException('La creación de borradores está deshabilitada. Abre la redacción en webmail.');
    }
    public function find(string $messageId,bool $sent=false):array{
        $box=env_value($sent?'QUOTE_IMAP_SENT':'QUOTE_IMAP_DRAFTS');
        if(!@imap_reopen($this->connection,imap_quote_endpoint($box)))throw new RuntimeException('No se pudo verificar la carpeta del buzón.');
        $ids=@imap_search($this->connection,'HEADER Message-ID "'.$messageId.'"',SE_UID);
        return $ids?:[];
    }
    public function append(string $mime):bool{return @imap_append($this->connection,imap_quote_endpoint(env_value('QUOTE_IMAP_DRAFTS')),$mime,'\\Draft');}
    public function close():void{if($this->connection)@imap_close($this->connection);imap_errors();imap_alerts();$this->connection=null;}
}

function prepare_quote_draft(PDO $pdo,array $q,array $items,array $input,int $userId,bool $confirmNew,?QuoteDraftMailbox $mailbox=null,?callable $pdfFactory=null): array {
    if($mailbox===null&&!quote_mail_configured())throw new RuntimeException('El correo no está habilitado. Configura el buzón en el servidor.');
    $mail=quote_mail_input($input);$quoteHash=quote_document_hash($q,$items);
    $hash=hash('sha256',$quoteHash.json_encode($mail,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    $lock='quote-mail-'.substr(hash('sha256',(string)$q['id']),0,48);
    $s=$pdo->prepare('SELECT GET_LOCK(?,0)');$s->execute([$lock]);
    if((int)$s->fetchColumn()!==1)throw new RuntimeException('Otro usuario está preparando el correo de esta cotización. Espera y vuelve a consultar.');
    try{
        $s=$pdo->prepare('SELECT * FROM quote_email_drafts WHERE quote_id=? AND content_hash=?');$s->execute([$q['id'],$hash]);$draft=$s->fetch();
        if($draft&&$draft['completed'])return ['id'=>(int)$draft['id'],'reused'=>true];
        if(!$draft){
            $s=$pdo->prepare('SELECT COUNT(*) FROM quote_email_drafts WHERE quote_id=?');$s->execute([$q['id']]);
            if((int)$s->fetchColumn()>0&&!$confirmNew)throw new InvalidArgumentException('Ya se preparó otro correo para esta cotización. Confirma la creación de un nuevo borrador.');
            $messageId='<cotizacion-'.bin2hex(random_bytes(20)).'@metalrubber.cl>';
            $s=$pdo->prepare('INSERT INTO quote_email_drafts(quote_id,quote_number,content_hash,quote_hash,message_id,created_by) VALUES(?,?,?,?,?,?)');
            $s->execute([$q['id'],$q['quote_number'],$hash,$quoteHash,$messageId,$userId]);
            $draft=['id'=>(int)$pdo->lastInsertId(),'message_id'=>$messageId,'attempted'=>0];
        }
        $mailbox??=new NativeQuoteDraftMailbox();
        $ids=$mailbox->find($draft['message_id']);
        if(!$ids&&$draft['attempted'])$ids=$mailbox->find($draft['message_id'],true);
        if(!$ids&&$draft['attempted'])throw new RuntimeException('El resultado del intento anterior es incierto. Revisa Borradores y Enviados en webmail; no se creará otro mensaje para evitar duplicados.');
        if(!$ids){
            $bytes=$pdfFactory?$pdfFactory($q,$items):generate_quote_pdf($q,$items);
            if(!str_starts_with($bytes,'%PDF-'))throw new RuntimeException('No se pudo generar un PDF válido.');
            $mime=quote_draft_mime($q,$mail,$draft['message_id'],$bytes);
            $pdo->prepare('UPDATE quote_email_drafts SET attempted=1 WHERE id=?')->execute([$draft['id']]);
            if(!$mailbox->append($mime))throw new RuntimeException('No se pudo confirmar el guardado del borrador. Revisa webmail antes de reintentar.');
            $ids=$mailbox->find($draft['message_id']);
            if(!$ids)throw new RuntimeException('El buzón no confirmó el identificador del borrador. Revisa webmail antes de reintentar.');
        }
        $pdo->beginTransaction();
        try{
            $pdo->prepare('UPDATE quote_email_drafts SET completed=1,mailbox_uid=? WHERE id=?')->execute([(int)$ids[0],$draft['id']]);
            $pdo->prepare('INSERT INTO audit_logs(action,entity_type,entity_id,user_id,payload_json) VALUES(?,?,?,?,?)')->execute(['draft_prepared','quote',$q['id'],$userId,json_encode(['draft_id'=>$draft['id'],'message_id'=>$draft['message_id']])]);
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        return ['id'=>(int)$draft['id'],'reused'=>false];
    }finally{
        if($mailbox)$mailbox->close();
        $s=$pdo->prepare('SELECT RELEASE_LOCK(?)');$s->execute([$lock]);
    }
}
