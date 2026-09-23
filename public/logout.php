<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
logout_user();
redirect('index.php');
