<?php
$res=@include("../../../../../main.inc.php");
if (! $res) $res=@include("../../../../../../main.inc.php");
header('Content-Type: application/json');
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

if ($json_obj->params->args[0]->name==false) $sql="DELETE FROM ".MAIN_DB_PREFIX."takepos_floor_tables where rowid=".$json_obj->params->args[0]->id;
else if ($json_obj->params->args[0]->id>0) $sql="UPDATE ".MAIN_DB_PREFIX."takepos_floor_tables set label='".$json_obj->params->args[0]->name."', leftpos=".$json_obj->params->args[0]->position_h.", toppos=".$json_obj->params->args[0]->position_v." where rowid=".$json_obj->params->args[0]->id;
else $sql="INSERT INTO ".MAIN_DB_PREFIX."takepos_floor_tables values ('' , 0, '".$json_obj->params->args[0]->name."', ".$json_obj->params->args[0]->position_h.", ".$json_obj->params->args[0]->position_v.", 1)";
$db->query($sql);
?>
{"result": 5, "jsonrpc": "2.0", "id": 452746803}