<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/quote_document.php';
require_login();
try{[$q,$items]=load_quote_document(db(),(int)($_GET['id']??0));}
catch(InvalidArgumentException $e){http_response_code(404);exit(e($e->getMessage()));}
$printToolbar=true;
require dirname(__DIR__).'/app/views/quote_document.php';
