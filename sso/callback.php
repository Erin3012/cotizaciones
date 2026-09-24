<?php
declare(strict_types=1);
require_once __DIR__ . '/client.php';
if (current_user()) redirect('../index.php?page=quotes');
$state = (string)($_GET['state'] ?? ''); $expected = (string)($_SESSION['sso_state'] ?? ''); unset($_SESSION['sso_state']); $code = (string)($_GET['code'] ?? '');
if ($expected === '' || $state === '' || !hash_equals($expected, $state) || !preg_match('/^[A-Za-z0-9_-]{40,64}$/', $code)) redirect('error.php?code=invalid');
try {
    $identity = sso_exchange($code, 'quotations'); $stmt = db()->prepare('SELECT * FROM users WHERE email=? AND active=1 LIMIT 1'); $stmt->execute([strtolower(trim((string)$identity['email']))]); $local = $stmt->fetch();
    if (!$local) redirect('error.php?code=unlinked');
    login_user($local); redirect('../index.php?page=quotes');
} catch (Throwable $e) { error_log($e->getMessage()); redirect('error.php?code=invalid'); }
