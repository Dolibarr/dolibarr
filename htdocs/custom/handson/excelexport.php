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

if (version_compare(PHP_VERSION, '7.2.0', '>=')) {
    error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
}

$now = dol_now();
$langs->setDefaultLang('de_DE');
$langs->loadLangs(array("orders", 'companies'));

$presql = GETPOST('query', 'alpha');

if (isset($_SESSION['columns'])) {
    $param = unserialize($_SESSION['columns']);
} else {
    $param = [];
}
$keys = [];

foreach ($param as $key => $val) {
    if ($val['checked'] == 1) {
        switch ($key) {
            case "ef.location":
                $label = "region.label";
                break;
            case "ef.program":
                $label = "programm.ref";
                break;
            case "ef.season":
                $label = "saison.jahr";
                break;
            default:
                $label = $key;
                break;
        }
        array_push($keys, $key);
        $keystring .= ", " . $label;
    }
}

$aftsql = explode('FROM', $presql);
$from = preg_replace('/\sco/', ' country', $aftsql[1]);
$sql = "SELECT " . substr($keystring, 2) . " FROM " . $from;

$sql_a = explode("WHERE", $sql);
$sql_begin = $sql_a[0];

$sql_b = explode("LIMIT", $sql_a[1]);
$sql_end = $sql_b[0];

$sql = $sql_begin .
    "LEFT JOIN llx_handson_saison AS saison ON ef.season=saison.rowid " .
    "LEFT JOIN llx_handson_programm AS programm ON ef.program=programm.rowid " .
    "LEFT JOIN llx_handson_region AS region ON ef.location=region.rowid " .
    "WHERE " .
    $sql_end;


$objPHPExcel = new PHPExcel();
$objPHPExcel->setActiveSheetIndex(0);
$result = $db->query($sql);
$rows = $db->num_rows($result);

// Safe "Funktionen" in array $functions like [0] => {1, Vertrag; 2, Orga} ...
$result_functions = $db->query("SELECT param FROM llx_extrafields WHERE name='funktion' AND elementtype='socpeople'");
$functions_string = $db->fetch_array($result_functions);
$func = explode("{", $functions_string['param']);
$func = explode("i:", $func[2]);
$functions = [];
foreach ($func as $val) {
    $val = explode(";", $val);
    array_push($functions, $val);
}
array_shift($functions);
foreach ($functions as $key => $val) {
    array_pop($functions[$key]);
    $pos = strpos($functions[$key][1], "\"");
    $splitted = substr($functions[$key][1], $pos + 2);
    $splitted = trim($splitted, "\"");
    $functions[$key][1] = $splitted;
}

function searchForId($id, $array) {
    foreach ($array as $key => $val) {
        if ($val[0] === $id) {
            return $key;
        }
    }
    return null;
}

for ($i = 0; $i < $rows; $i++) {

    $arr = $db->fetch_object($result);
    $row = $i + 2;
    $coln = 65;
    foreach ($arr as $key=>$val) {
        if($key == "funktion") {
            $funcs = explode(",", $val);
            foreach ($funcs as $func) {
                $func_key = searchForId($func, $functions);
                $val = $functions[$func_key][1];
            }
        }
        $col = chr($coln++);
        $objPHPExcel->getActiveSheet()->SetCellValue($col . $row, $val);
        $color = ($row % 2 == 0) ? 'FFFFFF' : 'DCE6F1';
        $objPHPExcel->getActiveSheet()->getStyle($col . $row)->applyFromArray(
            array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => $color)
                ),
                'font' => array(
                    'color' => array('rgb' => '366092')
                ),
                'borders' => array(
                    'left' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array(
                            'rgb' => 'FFFFFF'
                        )
                    ),
                    'right' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array(
                            'rgb' => 'FFFFFF'
                        )
                    )
                )
            )
        );
        $lastcell = $col . $row;
    }
}

$coln = 65;
foreach ($keys as $key) {
    $col = chr($coln++);
    $objPHPExcel->getActiveSheet()->SetCellValue($col . '1', $langs->transnoentitiesnoconv($param[$key]['label']));
    $objPHPExcel->getActiveSheet()->getStyle($col . '1')->applyFromArray(
        array(
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'FFFFFF')
            ),
            'borders' => array(
                'top' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '4F81BD'
                    )
                ),
                'bottom' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array(
                        'rgb' => '4F81BD'
                    )
                )
            ),
            'font' => array(
                'bold' => true
            )
        )
    );
    $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

$objPHPExcel->getActiveSheet()->setAutoFilter('A1:' . $lastcell);

$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);

$filename = "DRAHTexport_" . date("Ymd_His");

//header('Content-Encoding: UTF-8'); // vilh, change to UTF-8!
//header('Content-Type: application/vnd.ms-excel; charset=utf-8'); //mime type
//header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"'); //tell browser what's the file name
//header('Cache-Control: max-age=0'); //no cache
//$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');


//$objWriter->save('php://output');
$objWriter->save('exports/' . $filename . '.xlsx');

echo '<a href="exports/' . $filename . '.xlsx">Hier</a> klicken, falls der Download nicht automatisch startet.';
echo "<script>window.location = 'exports/" . $filename . ".xlsx'</script>";

$db->close();
