<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

$categorie = new Categorie($db);
$categories = $categorie->get_full_arbo('product');
class Category {
    public $name = "";
    public $parent_id  = "";
    public $id = "";
	public $child_id = "";
}
$cats=array(); 
foreach($categories as $key => $val)
{
	$cat = new Category();
	$cat->name = $val['label'];
	$cat->parent_id  = false;
	$cat->id  = $val['id'];
	$cats[]=$cat;    
}
$catsjson= json_encode($cats);
?>
{"result": <?php echo $catsjson;?>, "jsonrpc": "2.0", "id": 672236527}