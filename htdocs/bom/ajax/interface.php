<?php
require '../../main.inc.php';

$action = GETPOST('action', 'alphanohtml');
$select_product_val = GETPOST('select_product_val', 'int');
$current_bom_id = GETPOST('current_bom_id', 'int');

global $db;

switch ($action) {
	case 'select_BOM':
		//Selection of nomenclatures corresponding to the selected product
		$sql = 'SELECT b.rowid, b.ref, b.label, b.fk_product, p.label AS product_label FROM '.MAIN_DB_PREFIX.'bom_bom AS b ';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'product AS p ON b.fk_product=p.rowid';
		$sql.= ' WHERE fk_product='.(int)$select_product_val.' AND b.rowid<>'. (int)$current_bom_id;
		$resql = $db->query($sql);
		if ($resql && $db->num_rows($resql) > 0) {
			$options = array();
			$cpt=0;
			while ($obj = $db->fetch_object($resql)) {
				$options[$obj->rowid] = $obj->ref.' - '.$obj->label;
				$cpt++;
			}
			print json_encode($options);
		}

	break;
}
