<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
if ($conf->global->TAKEPOS_ORDER_PRINTERS) print '{"jsonrpc": "2.0", "id": 720774771, "result": [{"id": 1, "name": "Kitchen Printer", "proxy_ip": "localhost", "product_categories_ids": [1]}]}';
else print '{"result": [], "jsonrpc": "2.0", "id": 746035287}';
?>