<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"name": "All", "parent_id": false, "id": 1}, {"name": "Expenses", "parent_id": [1, "All"], "id": 3}, {"name": "Internal", "parent_id": [1, "All"], "id": 4}, {"name": "Saleable", "parent_id": [1, "All"], "id": 2}, {"name": "Physical", "parent_id": [2, "All / Saleable"], "id": 7}, {"name": "Services", "parent_id": [2, "All / Saleable"], "id": 5}, {"name": "Software", "parent_id": [2, "All / Saleable"], "id": 6}], "jsonrpc": "2.0", "id": 272066581}