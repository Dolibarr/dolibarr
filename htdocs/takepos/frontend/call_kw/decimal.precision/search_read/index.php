<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"name": "Product Price", "digits": 2, "id": 1}, {"name": "Discount", "digits": 2, "id": 2}, {"name": "Stock Weight", "digits": 2, "id": 3}, {"name": "Product Unit of Measure", "digits": 3, "id": 4}, {"name": "Payment Terms", "digits": 6, "id": 5}, {"name": "Payroll", "digits": 2, "id": 6}, {"name": "Payroll Rate", "digits": 4, "id": 7}], "jsonrpc": "2.0", "id": 405415942}