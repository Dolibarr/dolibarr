<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
?>
{"result": [{"vat": false, "phone": "<?php echo $mysoc->phone; ?>", "id": 1, "tax_calculation_rounding_method": "round_per_line", "partner_id": [1, "<?php echo $mysoc->name; ?>"], "country_id": [233, "United States"], "name": "<?php echo $mysoc->name; ?>", "currency_id": [3, "USD"], "email": "info@yourcompany.example.com", "website": "http://www.example.com", "company_registry": false}], "jsonrpc": "2.0", "id": 409393882}