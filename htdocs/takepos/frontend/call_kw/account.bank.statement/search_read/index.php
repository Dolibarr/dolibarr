<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
$langs->load("main");
$langs->load("bills");
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"id": 1, "account_id": [1, "Cash"], "state": "open", "pos_session_id": [1, "POS/2018/08/30/01"], "name": "POS/2018/08/30/01", "user_id": [1, "Administrator"], "currency_id": [3, "USD"], "journal_id": [1, "<?php echo $langs->trans("Cash"); ?>"]},{"id": 2, "account_id": [2, "Bank"], "state": "open", "pos_session_id": [1, "POS/2018/08/30/01"], "name": "POS/2018/08/30/01", "user_id": [1, "Administrator"], "currency_id": [3, "USD"], "journal_id": [2, "<?php echo $langs->trans("PaymentTypeCB"); ?>"]}], "jsonrpc": "2.0", "id": 391178768}