<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Charlene Benke	        <charlie@patas-monkey.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/custom/handson/excelexport.php
 *    \ingroup    handson
 *    \brief      Page to list orders
 */


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/handson/PHPExcel-1.8/Classes/PHPExcel.php';

// Security check
$id = (GETPOST('orderid') ? GETPOST('orderid', 'int') : GETPOST('id', 'int'));
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'commande', $id, '');

$now = dol_now();
$langs->setDefaultLang('de_DE');
$langs->loadLangs(array("orders", 'companies'));

// Get function 'Lieferaddresse'
$res = $db->query("SELECT param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype='socpeople' AND label='Funktion'");
$func_arr = unserialize($db->fetch_array($res)[0])['options'];
$function = array_search(" Lieferadresse", $func_arr);

// Get Saison 2021-22
$res = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."handson_saison WHERE ref='2021-22'");
$saison = intval($db->fetch_array($res)[0]);

// Get Programm Explore
$res = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."handson_programm WHERE ref='Explore'");
$programm = intval($db->fetch_array($res)[0]);

$presql = GETPOST('query', 'alpha');

$aftsql = explode('FROM', $presql);
$context = GETPOST('context');
if($context == 'klaziOrders') {
    $sql = "SELECT c.ref, socpex.institution, s.nom, socpv.firstname, socpv.lastname, socpv.address, socpv.zip, socpv.town, country.code FROM " . $aftsql[1];
}
elseif($context == 'contactlist') {
    $sql = "SELECT s.nom, p.firstname, p.lastname, p.address, p.zip, p.town, co.code FROM " . $aftsql[1];
}

$sql_a = explode("LIMIT", $sql);
$sql = $sql_a[0];

/*
$sql = "SELECT ";
$sql .= "t.nom, s.firstname, s.lastname, s.address, s.zip, s.town, c.code";
$sql .= " FROM " . MAIN_DB_PREFIX."socpeople AS s";
$sql .= " JOIN ". MAIN_DB_PREFIX."socpeople_extrafields AS e ON s.rowid=e.fk_object";
$sql .= " JOIN ". MAIN_DB_PREFIX."c_country AS c ON s.fk_pays=c.rowid";
$sql .= " JOIN ". MAIN_DB_PREFIX."societe AS t ON s.fk_soc=t.rowid";
$sql .= " WHERE e.funktion LIKE '%".$function."%' AND e.season LIKE '%".$saison."%' AND e.program LIKE '%".$programm."%'";
*/
$res = $db->query($sql);
$rows = $db->num_rows($result);

$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);

for ($i = 0; $i < $rows; $i++) {
	$arr = $db->fetch_object($result);
	$row = $i + 2;
	$coln = 65;

	$addr_tmp = explode(";", $arr->address);
	$arr->strasse = $addr_tmp[0];
	$arr->nr = $addr_tmp[1];
	$arr->addr2 = $addr_tmp[2];
	$arr->addr3 = $addr_tmp[3];
	unset($arr->address);

	foreach ($arr as $key => $val) {
		if($key == "firstname") {
			$name_tmp = $val;
		}
		else {
			if(isset($name_tmp)) {
				$val = $name_tmp . " " . $val;
				unset($name_tmp);
			}
			$col = chr($coln++);
			$val = str_replace("\r\n", ', ', $val);
			$val = str_replace(PHP_EOL, ', ', $val);
			$objPHPExcel->getActiveSheet()->SetCellValue($col . $row, $langs->transnoentitiesnoconv($val));
			$color = ($row % 2 == 0) ? 'FCD5B4' : 'FDE9D9';
			$lastcell = $col . $row;


		}
	}
}

$coln = 65;
if($context == 'klaziOrders') {
    $spalten = ["Bestellnummer (Empfänger)", "Institution (Empfänger)", "Geschäftspartner (Empfänger)", "Name (Empfänger)", "PLZ (Empfänger)", "Stadt (Empfänger)", "Land (Empfänger)", "Straße (Empfänger)", "Hausnummer (Empfänger)", "Zusatz 1 (Empfänger)", "Zusatz 2 (Empfänger)"];
}
elseif($context == 'contactlist') {
    $spalten = ["Geschäftspartner (Empfänger)", "Name (Empfänger)", "PLZ (Empfänger)", "Stadt (Empfänger)", "Land (Empfänger)", "Straße (Empfänger)", "Hausnummer (Empfänger)", "Zusatz 1 (Empfänger)", "Zusatz 2 (Empfänger)"];
}

foreach ($spalten as $val) {
        $col = chr($coln++);
        $objPHPExcel->getActiveSheet()->SetCellValue($col . '1', $langs->transnoentitiesnoconv($val));
        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

$filename = "DRAHTexport_".date("Ymd_His");

/*header('Content-Encoding: UTF-8'); // vilh, change to UTF-8!
header('Content-Type: application/vnd.ms-excel; charset=utf-8'); //mime type
header('Content-Disposition: attachment;filename="'.$filename.'.csv"'); //tell browser what's the file name
header('Cache-Control: max-age=0'); //no cache*/
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
$objWriter->setDelimiter(';');
$objWriter->setUseBOM(false);
$objWriter->setLineEnding("\r\n");

//$objWriter->save('php://output');
$objWriter->save('exports/'.$filename.'.csv');

echo '<a href="exports/'.$filename.'.csv">Hier</a> klicken, falls der Download nicht automatisch startet.';
echo "<script>window.location = 'exports/".$filename.".csv'</script>";

$db->close();

