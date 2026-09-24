<?php
declare(strict_types=1);
require_once __DIR__ . '/client.php';
if (current_user()) redirect('../index.php?page=quotes');
$state = bin2hex(random_bytes(32)); $_SESSION['sso_state'] = $state;
$portal = rtrim(env_value('PORTAL_SSO_URL', 'https://portal.metalrubber.cl'), '/');
redirect($portal . '/sso/authorize.php?client_id=quotations&state=' . rawurlencode($state));
