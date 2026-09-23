<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
$item=calculate_item(3,10000,10);
assert($item['discount']===3000.0);
assert($item['subtotal']===27000.0);
$totals=calculate_totals([['quantity'=>3,'unit_price'=>10000,'discount_percent'=>10]],19);
assert($totals['subtotal']===27000.0);
assert($totals['tax']===5130.0);
assert($totals['total']===32130.0);
assert(valid_rut('76.123.456-0')===true);
assert(valid_rut('76.123.456-8')===false);
echo 'OK'.PHP_EOL;
