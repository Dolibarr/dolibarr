<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"jsonrpc": "2.0", "id": 380144123, "result": {"server_version": "11.0-20180916", "server_version_info": [11, 0, 0, "final", 0, ""], "server_serie": "11.0", "protocol_version": 1}}