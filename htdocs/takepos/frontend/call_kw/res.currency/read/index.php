<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"position": "after", "rounding": 0.01, "id": 3, "symbol": "<?php echo $langs->getCurrencySymbol($conf->currency);?>", "name": "<?php echo currency_name($conf->currency,1);?>", "rate": 1.5289}, {"position": "before", "rounding": 0.01, "id": 3, "symbol": "$", "name": "USD", "rate": 1.5289}], "jsonrpc": "2.0", "id": 394782647}