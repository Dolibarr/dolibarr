<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"config_id": [1, "Main (Administrator)"], "login_number": 2, "id": 1, "name": "POS/2018/08/30/01", "stop_at": false, "start_at": "2018-08-30 15:30:25", "user_id": [1, "Administrator"], "sequence_number": 1, "journal_ids": [6]}], "jsonrpc": "2.0", "id": 544843058}