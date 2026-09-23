<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/quote_actions.php';
require_login();
$next=next_number('COT','quotes','quote_number');
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nueva cotización · Metalrubber</title><link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="shell">
<aside class="side"><a class="brand" href="index.php?page=dashboard"><span class="brand-mark">M</span><span><strong>METALRUBBER</strong><small>Cotizaciones</small></span></a><nav class="nav"><a href="index.php?page=dashboard">Panel principal</a><a class="active" href="quote_new.php">Nueva cotización</a><a href="index.php?page=quotes">Cotizaciones</a><a href="index.php?page=clients">Clientes</a></nav></aside>
<div class="main"><header class="top"><h1>Nueva cotización</h1><div class="user"><?=e(current_user()['name'])?> · <a href="logout.php">Salir</a></div></header>
<main class="content">
<div class="actions"><a class="btn btn-outline" href="index.php?page=dashboard">← Panel</a></div>
<form method="post" action="index.php">
<input type="hidden" name="action" value="save_quote"><input type="hidden" name="request_id" value="0"><?=csrf_field()?>
<section class="panel"><div class="panel-head"><h2>Cliente</h2></div><div class="panel-body"><div class="form-grid">
<div class="field"><label>Nombre o razón social *</label><input class="input" name="client_name" required></div>
<div class="field"><label>RUT *</label><input class="input" name="client_rut" placeholder="12.345.678-9" required></div>
<div class="field"><label>Correo</label><input class="input" type="email" name="client_email"><small>Opcional si el cliente no lo informó.</small></div>
<div class="field"><label>Teléfono</label><input class="input" name="client_phone"></div>
<div class="field"><label>Dirección *</label><input class="input" name="client_address" required></div>
<div class="field"><label>Atención / contacto *</label><input class="input" name="attention_name" required></div>
</div></div></section>
<section class="panel"><div class="panel-head"><h2>Datos de la cotización</h2></div><div class="panel-body"><div class="form-grid">
<div class="field"><label>Folio / código</label><input class="input" name="quote_number" value="<?=e($next)?>" required><small>Editable para conservar folios como 27-261370909.</small></div>
<div class="field"><label>Fecha de emisión</label><input class="input" type="date" name="issue_date" value="<?=date('Y-m-d')?>" required></div>
<div class="field"><label>Fecha de vencimiento *</label><input class="input" type="date" name="expiry_date" required></div>
</div></div></section>
<section class="panel"><div class="panel-head"><h2>Detalle de trabajo</h2></div><div class="panel-body table-wrap"><table class="table"><tr><th>Ítem</th><th>Unidad</th><th>Cantidad</th><th>Descripción</th><th>Valor unitario neto</th><th>Descuento %</th></tr>
<?php for($i=0;$i<8;$i++): ?><tr><td><?=($i+1)?></td><td><input class="input" name="unit[]" value="c/u"></td><td><input class="input" name="quantity[]" type="number" min="0" step=".001"></td><td><input class="input" name="description[]"></td><td><input class="input" name="unit_price[]" type="number" min="0" step=".01"></td><td><input class="input" name="discount_percent[]" type="number" min="0" max="100" step=".01" value="0"></td></tr><?php endfor; ?>
</table></div></section>
<section class="panel"><div class="panel-head"><h2>Entrega y pago</h2></div><div class="panel-body"><div class="form-grid">
<div class="field"><label>Lugar de entrega</label><input class="input" name="delivery_location"></div>
<div class="field"><label>Plazo de entrega</label><input class="input" name="delivery_term" placeholder="Ej.: 5 días hábiles"></div>
<div class="field"><label>Forma de pago</label><input class="input" name="payment_method" placeholder="Ej.: Orden de compra"></div>
<div class="field full"><label>Observaciones</label><textarea class="textarea" name="notes"></textarea></div>
</div></div></section>
<div class="actions"><button class="btn btn-primary">Guardar cotización</button></div>
</form>
</main></div></div>
</body></html>
