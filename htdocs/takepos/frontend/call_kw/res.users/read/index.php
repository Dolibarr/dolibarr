<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"name": "Administrator", "id": 1, "company_id": [1, "Demo Company"]}], "jsonrpc": "2.0", "id": 123456}