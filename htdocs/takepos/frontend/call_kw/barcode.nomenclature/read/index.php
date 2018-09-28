<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"name": "Default Nomenclature", "upc_ean_conv": "always", "rule_ids": [9, 8, 2, 7, 6, 5, 3, 4, 1], "id": 1}], "jsonrpc": "2.0", "id": 934099331}