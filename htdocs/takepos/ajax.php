<?php
/* Copyright (C) 2001-2004	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019	JC Prieto			<jcprieto@virtual20.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/ajax.php
 *	\brief      Ajax search component for TakePos. It search products of a category.
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

require_once DOL_DOCUMENT_ROOT.'/takepos/lib/takepos.lib.php';	//V20

$category = GETPOST('category', 'alpha');
$action = GETPOST('action', 'alpha');
$term = GETPOST('term', 'alpha');
$place = GETPOST('place', 'int');	//V20
$facid= GETPOST('facid', 'int');	//V20


/*
 * View
 */

if ($action=="getProducts"){
	$object = new Categorie($db);
	$result=$object->fetch($category);
	$prods = $object->getObjectsInCateg("product");
	//V20: Only product for sell
	$i=0;
	$prodstosell=array();
	foreach ($prods as $item){
		if($item->status==1){
			
			$lab=mb_convert_case(substr($item->label,0,22),MB_CASE_TITLE);	//V20: for improve label to show.
			$item->label=$lab;
			$prodstosell[]=$item;
		}
	}
	
	echo json_encode($prodstosell);
}

if ($action=="search"){
	$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'product';
	$sql.= ' WHERE entity IN ('.getEntity('product').')';
	$sql .= natural_search(array('label','barcode'), $term);
	$resql = $db->query($sql);
	$rows = array();
	while($row = $db->fetch_array ($resql)){
		$rows[] = $row;
	}
	echo json_encode($rows);
}
if ($action=="customerphone"){	//V20
	$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."societe where phone='".$term."'";
	$resql = $db->query($sql);
	$row= $db->fetch_array ($resql);
	$term=$row[0];
	$action='customer';
}
if ($action=="customerVAT"){	//V20
	$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."societe where siren LIKE '%".$term."%'";
	$resql = $db->query($sql);
	$row= $db->fetch_array ($resql);
	$term=$row[0];
	$action='customer';
	
}
if ($action=="customer"){	//V20
	if($facid)	$sql="UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".$term." where rowid=".$facid;
	else		$sql="UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".$term." where facnumber='(PROV-POS-".$place.")'";
    $resql = $db->query($sql);
    load_ticket($place,$facid);
    echo json_encode($term);
    //exit;
}
if ($action=="ticket"){	//V20
	$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."facture where rowid=".$term;
    $resql = $db->query($sql);
    $row = $db->fetch_array ($resql);
    echo json_encode($row);
    //exit;
    
}
if ($action=="diners"){	//V20
	if($facid)	$sql="UPDATE ".MAIN_DB_PREFIX."facture_extrafields set diner=".$term." where fk_object=".$facid;
    $resql = $db->query($sql);
    echo json_encode($term);
    //exit;
}
if ($action=="changeuser"){	//V20
	if($term>0){
		//V20: TODO: See index.php
		$user->fetch($term);
		$user->getRights();
		$_SESSION["dol_login"]=$user->login;
	}
    echo json_encode($user->login);
    //exit;
}
