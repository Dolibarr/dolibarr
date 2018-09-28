<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

class Product {
    public $categ_id = "";
    public $barcode  = "";
    public $taxes_id = "";
	public $pos_categ_id = "";
	public $uom_id = "";
	public $list_price = "";
	public $standard_price = "";
	public $lst_price = "";
	public $default_code = "";
	public $id = "";
	public $description_sale = "";
	public $display_name = "";
	public $description = "";
	public $tracking = "";
	public $product_tmpl_id = "";
	public $to_weight = "";
}

$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'product as p,';
$sql.= ' ' . MAIN_DB_PREFIX . "categorie_product as c";
$sql.= ' WHERE p.entity IN ('.getEntity('product').')';
$sql.= ' AND c.fk_product = p.rowid';
$resql = $db->query($sql);
$rows = array();
$prodsjson=array();
while($row = $db->fetch_array ($resql)){
	$row['prettyprice']=price($row['price_ttc'], 1, '', 1, - 1, - 1, $conf->currency);
	$rows[] = $row;
	$prod = new Product();
	$prod->categ_id = $row['fk_categorie'];
    $prod->barcode  = false;
    $prod->taxes_id = "";
	$prod->pos_categ_id = $row['fk_categorie'];
	$prod->uom_id = array("1", "Unit(s)");
	$prod->list_price = $row['price_ttc'];
	$prod->standard_price = $row['price_ttc'];
	$prod->lst_price = $row['price_ttc'];
	$prod->default_code = "";
	$prod->id = $row['rowid'];
	$prod->description_sale = false;
	$prod->display_name = $row['label'];
	$prod->description = $row['label'];
	$prod->tracking = "none";
	$prod->product_tmpl_id = "";
	$prod->to_weight = false;
	$prods[]=$prod;
}
$prodsjson= json_encode($prods);
?>
{"result": <?php echo $prodsjson;?>, "jsonrpc": "2.0", "id": 672236527}
