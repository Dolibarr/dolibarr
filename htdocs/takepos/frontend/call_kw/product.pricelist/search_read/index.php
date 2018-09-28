<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"name": "Public Pricelist", "id": 1, "display_name": "Public Pricelist (USD)"}], "jsonrpc": "2.0", "id": 368603459}