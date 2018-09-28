<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"amount_type": "percent", "id": 1, "amount": 15.0, "include_base_amount": false, "name": "Tax 15.00%", "price_include": false, "children_tax_ids": []}, {"amount_type": "percent", "id": 2, "amount": 15.0, "include_base_amount": false, "name": "Tax 15.00%", "price_include": false, "children_tax_ids": []}], "jsonrpc": "2.0", "id": 646537080}