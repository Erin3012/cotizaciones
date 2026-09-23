<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly'=>true,'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),'samesite'=>'Lax']);
    session_start();
}
if (function_exists('load_env')) {
    return;
}
function load_env(): void {
    static $loaded=false; if ($loaded) return; $loaded=true; $file=dirname(__DIR__).'/.env'; if (!is_readable($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
        $line=trim($line); if ($line===''||str_starts_with($line,'#')||!str_contains($line,'=')) continue;
        [$key,$value]=explode('=',$line,2); $value=trim($value);
        if (strlen($value)>1 && (($value[0]==='"'&&substr($value,-1)==='"')||($value[0]==="'"&&substr($value,-1)==="'"))) $value=substr($value,1,-1);
        $key=trim($key);
        $_ENV[$key]=$value;
        putenv($key.'='.$value);
    }
}
load_env();
function env_value(string $key,string $default=''): string { $value=getenv($key); if ($value===false||$value==='') $value=$_ENV[$key]??null; return ($value!==false&&$value!==null&&$value!=='')?(string)$value:$default; }
function db(): PDO {
    static $pdo; if ($pdo instanceof PDO) return $pdo;
    $dsn='mysql:host='.env_value('DB_HOST','127.0.0.1').';port='.env_value('DB_PORT','3306').';dbname='.env_value('DB_DATABASE','qlccl_cotizaciones').';charset=utf8mb4';
    return $pdo=new PDO($dsn,env_value('DB_USERNAME','root'),env_value('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
}
function e(mixed $value): string { if (is_array($value)) $value=implode(', ',array_map('strval',$value)); return htmlspecialchars((string)($value??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function csrf_token(): string { return $_SESSION['csrf_token']??=bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void { if (!hash_equals((string)($_SESSION['csrf_token']??''),(string)($_POST['csrf_token']??''))) { http_response_code(419); exit('Solicitud inválida. Recarga la página.'); } }
function flash(string $type,string $message): void { $_SESSION['flash'][]=['type'=>$type,'message'=>$message]; }
function consume_flash(): array { $items=$_SESSION['flash']??[]; unset($_SESSION['flash']); return $items; }
function app_url(string $path=''): string { return rtrim(env_value('APP_URL',''),'/').'/'.ltrim($path,'/'); }
function current_user(): ?array {
    static $user; if ($user!==null) return $user; if (empty($_SESSION['user_id'])) return null;
    $stmt=db()->prepare('SELECT id,name,email,role FROM users WHERE id=? AND active=1'); $stmt->execute([(int)$_SESSION['user_id']]); return $user=$stmt->fetch()?:null;
}
function require_login(): array { $user=current_user(); if (!$user) redirect('login.php?return='.rawurlencode($_SERVER['REQUEST_URI']??'index.php?page=dashboard')); return $user; }
function login_user(array $user): void { session_regenerate_id(true); $_SESSION['user_id']=(int)$user['id']; }
function logout_user(): void { $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),' ',time()-42000,$p['path'],$p['domain']??'',(bool)$p['secure'],(bool)$p['httponly']); } session_destroy(); }
function status_label(string $status): string { return ['received'=>'Solicitud recibida','review'=>'En revisión','preparation'=>'En preparación','sent'=>'Enviada','accepted'=>'Aceptada','rejected'=>'Rechazada','expired'=>'Vencida'][$status]??$status; }
function status_class(string $status): string { return 'status-'.preg_replace('/[^a-z]/','',$status); }
function money(float|int|string $amount): string { return '$'.number_format((float)$amount,0,',','.'); }
function valid_rut(string $rut): bool {
    $clean=strtoupper(preg_replace('/[^0-9K]/','',$rut)); if (strlen($clean)<2) return false; $body=substr($clean,0,-1); $dv=substr($clean,-1); $sum=0; $mult=2;
    for ($i=strlen($body)-1;$i>=0;$i--) {$sum+=(int)$body[$i]*$mult;$mult=$mult===7?2:$mult+1;} $calc=11-($sum%11); $expected=$calc===11?'0':($calc===10?'K':(string)$calc); return $dv===$expected;
}
function next_number(string $prefix,string $table,string $column): string {
    $year=date('Y'); $like=$prefix.'-'.$year.'-%'; $stmt=db()->prepare("SELECT MAX(CAST(SUBSTRING_INDEX($column, '-', -1) AS UNSIGNED)) FROM $table WHERE $column LIKE ?"); $stmt->execute([$like]); return sprintf('%s-%s-%04d',$prefix,$year,((int)$stmt->fetchColumn())+1);
}
function log_action(string $action,string $entity,?int $entityId,array $payload=[]): void { $u=current_user(); $s=db()->prepare('INSERT INTO audit_logs (action,entity_type,entity_id,user_id,payload_json) VALUES (?,?,?,?,?)'); $s->execute([$action,$entity,$entityId,$u['id']??null,$payload?json_encode($payload,JSON_UNESCAPED_UNICODE):null]); }
function add_history(string $type,int $id,?string $from,string $to,?string $comment=null): void { $u=current_user(); $s=db()->prepare('INSERT INTO workflow_history (entity_type,entity_id,from_status,to_status,comment,user_id) VALUES (?,?,?,?,?,?)'); $s->execute([$type,$id,$from,$to,$comment,$u['id']??null]); }
function request_statuses(): array { return ['received','review','preparation','sent','accepted','rejected','expired']; }
function calculate_item(float $qty,float $unitPrice,float $discountPercent): array { $gross=max(0,$qty)*max(0,$unitPrice); $discount=round($gross*min(100,max(0,$discountPercent))/100,2); return ['discount'=>$discount,'subtotal'=>round($gross-$discount,2)]; }
function calculate_totals(array $items,float $taxRate=19): array { $subtotal=0; foreach($items as $i) $subtotal+=calculate_item((float)$i['quantity'],(float)$i['unit_price'],(float)$i['discount_percent'])['subtotal']; $tax=round($subtotal*$taxRate/100,2); return ['subtotal'=>round($subtotal,2),'tax'=>$tax,'total'=>round($subtotal+$tax,2)]; }
function upload_files(int $requestId): array {
    if (empty($_FILES['attachments']['name'][0])) return []; $allowed=['pdf','jpg','jpeg','png','webp','doc','docx','xls','xlsx','dwg']; $max=(int)env_value('UPLOAD_MAX_BYTES','10485760'); $dir=dirname(__DIR__).'/public/uploads/'.$requestId; if (!is_dir($dir)) mkdir($dir,0750,true); $finfo=new finfo(FILEINFO_MIME_TYPE); $saved=[];
    foreach($_FILES['attachments']['name'] as $i=>$original) { if ($_FILES['attachments']['error'][$i]!==UPLOAD_ERR_OK) continue; if ((int)$_FILES['attachments']['size'][$i]>$max) throw new RuntimeException('Cada archivo debe pesar como máximo 10 MB.'); $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION)); if (!in_array($ext,$allowed,true)) throw new RuntimeException('Tipo de archivo no permitido: '.$ext); $tmp=$_FILES['attachments']['tmp_name'][$i]; $mime=$finfo->file($tmp)?:'application/octet-stream'; $stored=bin2hex(random_bytes(16)).'.'.$ext; $relative=$requestId.'/'.$stored; if (!move_uploaded_file($tmp,$dir.'/'.$stored)) throw new RuntimeException('No se pudo guardar un archivo adjunto.'); $s=db()->prepare('INSERT INTO request_attachments (request_id,original_name,stored_name,mime_type,file_size,relative_path) VALUES (?,?,?,?,?,?)'); $s->execute([$requestId,substr($original,0,255),$stored,$mime,(int)$_FILES['attachments']['size'][$i],$relative]); $saved[]=$relative; }
    return $saved;
}
