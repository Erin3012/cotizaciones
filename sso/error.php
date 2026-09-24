<?php
declare(strict_types=1);
$message = ($_GET['code'] ?? '') === 'unlinked' ? 'Tu usuario central no tiene una cuenta activa en Cotizaciones.' : 'No se pudo validar el acceso central. Intenta nuevamente.';
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso no disponible</title><link rel="stylesheet" href="../assets/style.css"></head><body><main class="login-wrap"><section class="login-card"><h1>Acceso no disponible</h1><p class="alert error"><?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?></p><a href="../login.php">Ingresar con cuenta local</a></section></main></body></html>
