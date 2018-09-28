<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);
echo '{"result": [';
$floors = explode(",", $conf->global->TAKEPOS_FLOORS);
$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'takepos_floor_tables';
$resql = $db->query($sql);
$comma="";
$floors = explode(",", $conf->global->TAKEPOS_FLOORS);
while($row = $db->fetch_array ($resql)){
	//$prod->categ_id = $row['fk_categorie'];
	echo $comma;
	$comma=",";
	echo '{"position_h": '.$row['leftpos'].', "color": "rgb(53,211,116)", "id": '.$row['rowid'].', "shape": "square", "name": "'.$row['label'].'", "seats": 4, "width": 100.0, "position_v": '.$row['toppos'].', "height": 100.0, "floor_id": ['.$row['floor'].', ""]}';
}
?>
], "jsonrpc": "2.0", "id": 504237853}