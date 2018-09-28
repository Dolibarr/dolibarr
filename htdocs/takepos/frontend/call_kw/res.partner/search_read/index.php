<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

class Customer {
    public $zip = "";
    public $state_id  = "";
    public $barcode = "";
	public $street = "";
	public $name = "";
	public $vat = "";
	public $property_account_position_id = "";
	public $property_product_pricelist = "";
	public $city = "";
	public $id="";
	public $country_id = "";
	public $mobile = "";
	public $phone = "";
	public $email = "";
	public $write_date = "";
}

$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'societe';
$resql = $db->query($sql);
$rows = array();
$prodsjson=array();
while($row = $db->fetch_array ($resql)){
	$customer = new Customer();
	$customer->zip = $row['zip'];
    $customer->state_id  = "";
    $customer->barcode = false;
	$customer->street = $row['address'];
	$customer->name = $row['nom'];
	$customer->vat = true;
	$customer->property_account_position_id = "false";
	$customer->property_product_pricelist = "";
	$customer->city = $row['town'];
	$customer->id = $row['rowid'];
	$customer->country_id = "";
	$customer->mobile = false;
	$customer->phone = false;
	$customer->email = false;
	$customer->write_date = "";
	$customers[]=$customer;
}
$prodsjson= json_encode($customers);
?>
{"result": <?php echo $prodsjson;?>, "jsonrpc": "2.0", "id": 672236527}