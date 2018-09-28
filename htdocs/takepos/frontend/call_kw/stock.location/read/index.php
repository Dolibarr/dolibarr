<?php
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"valuation_in_account_id": false, "return_location": false, "write_date": "2018-09-02 12:00:28", "posy": 0, "write_uid": [1, "Administrator"], "company_id": [1, "Demo Company"], "create_date": "2018-09-02 12:00:28", "display_name": "WH/Stock", "location_id": [11, "WH"], "usage": "internal", "quant_ids": [25, 30, 36], "scrap_location": false, "partner_id": false, "barcode": false, "removal_strategy_id": false, "name": "Stock", "putaway_strategy_id": false, "comment": false, "valuation_out_account_id": false, "__last_update": "2018-09-02 12:00:28", "active": true, "posx": 0, "id": 15, "parent_path": "1/11/15/", "complete_name": "Physical Locations/WH/Stock", "child_ids": [19, 18], "create_uid": [1, "Administrator"], "posz": 0}], "jsonrpc": "2.0", "id": 5079015}