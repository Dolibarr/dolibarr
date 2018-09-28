<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"sequence": 10, "type": "cash", "id": 1},{"sequence": 10, "type": "bank", "id": 2}], "jsonrpc": "2.0", "id": 335044910}