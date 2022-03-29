#!/usr/bin/env php
<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016 Juanjo Menent        <jmenent@2byte.es>
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
 *
 * WARNING, THIS WILL LOAD MASS DATA ON YOUR INSTANCE
 */

/**
 *      \file       dev/initdata/import-product.php
 * 		\brief      Script example to insert products from a csv file.
 *                  To purge data, you can have a look at purge-data.php
 */
// Test si mode batch
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Recupere root dolibarr
$path = preg_replace('/importdb-products.php/i', '', $_SERVER["PHP_SELF"]);
require $path . "../../htdocs/master.inc.php";
require $path . "includes/dbase.class.php";
include_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

//$delimiter = ',';
//$enclosure = '"';
//$linelength = 10000;
//$escape = '/';
// Global variables
$version = DOL_VERSION;
$confirmed = 1;
$error = 0;

$tvas = [
    '1' => "20.00",
    '2' => "5.50",
    '3' => "0.00",
    '4' => "20.60",
    '5' => "19.60",
];
$tvasD = [
    '1' => "20",
    '2' => "5.5",
    '3' => "0",
    '4' => "20",
    '5' => "20",
];

/*
 * Main
 */

@set_time_limit(0);
print "***** " . $script_file . " (" . $version . ") pid=" . dol_getmypid() . " *****\n";
dol_syslog($script_file . " launched with arg " . implode(',', $argv));

$table = $argv[1];

if (empty($argv[1])) {
    print "Error: Which table ?\n";
    print "\n";
    exit(-1);
}

$ret = $user->fetch('', 'admin');
if (!$ret > 0) {
    print 'A user with login "admin" and all permissions must be created to use this script.' . "\n";
    exit;
}

$sql = "SELECT * FROM `$table` WHERE 1";
$resql = $db->query($sql);
if ($resql)
while ($fields = $db->fetch_array($resql)) {
	$errorrecord = 0;
	if ($fields === false)
		continue;
	$nboflines++;

	$produit = new Product($db);
	$produit->type = 0;
	$produit->status = 1;
	$produit->ref = trim($fields['REF']);
	if ($produit->ref == '')
		continue;
	print "Process line nb " . $j . ", ref " . $produit->ref;
	$produit->label = trim($fields['LIBELLE']);
	if ($produit->label == '')
		$produit->label = $produit->ref;
	if (empty($produit->label))
		continue;
	//$produit->description = trim($fields[4] . "\n" . ($fields[5] ? $fields[5] . ' x ' . $fields[6] . ' x ' . $fields[7] : ''));
	//        $produit->volume = price2num($fields[8]);
	//        $produit->volume_unit = 0;
	$produit->weight = price2num($fields['MASSE']);
	$produit->weight_units = 0;          // -3 = g
	//$produit->customcode = $fields[10];
	$produit->barcode = str_pad($fields['CODE'], 12, "0", STR_PAD_LEFT);
	$produit->barcode_type = '2';
	$produit->import_key = $fields['CODE'];

	$produit->status = 1;
	$produit->status_buy = 1;

	$produit->finished = 1;

	//        $produit->multiprices[0] = price2num($fields['TARIF0']);
	//        $produit->multiprices[1] = price2num($fields['TARIF1']);
	//        $produit->multiprices[2] = price2num($fields['TARIF2']);
	//        $produit->multiprices[3] = price2num($fields['TARIF3']);
	//        $produit->multiprices[4] = price2num($fields['TARIF4']);
	//        $produit->multiprices[5] = price2num($fields['TARIF5']);
	//        $produit->multiprices[6] = price2num($fields['TARIF6']);
	//        $produit->multiprices[7] = price2num($fields['TARIF7']);
	//        $produit->multiprices[8] = price2num($fields['TARIF8']);
	//        $produit->multiprices[9] = price2num($fields['TARIF9']);
	//        $produit->price_min = null;
	//        $produit->price_min_ttc = null;
	//        $produit->price = price2num($fields[11]);
	//        $produit->price_ttc = price2num($fields[12]);
	//        $produit->price_base_type = 'TTC';
	//        $produit->tva_tx = price2num($fields[13]);
	$produit->tva_tx = (int) ($tvas[$fields['CODTVA']]);
	$produit->tva_npr = 0;
	//        $produit->cost_price = price2num($fields[16]);
	//compta

	$produit->accountancy_code_buy = trim($fields['COMACH']);
	$produit->accountancy_code_sell = trim($fields['COMVEN']);
	//        $produit->accountancy_code_sell_intra=trim($fields['COMVEN']);
	//        $produit->accountancy_code_sell_export=trim($fields['COMVEN']);
	// Extrafields
	//        $produit->array_options['options_ecotaxdeee'] = price2num($fields[17]);

	$produit->seuil_stock_alerte = $fields['STALERTE'];
	$ret = $produit->create($user, 0);
	if ($ret < 0) {
		print " - Error in create result code = " . $ret . " - " . $produit->errorsToString();
		$errorrecord++;
	} else {
		print " - Creation OK with ref " . $produit->ref . " - id = " . $ret;
	}

	dol_syslog("Add prices");

	// If we use price level, insert price for each level
	if (!$errorrecord && 1) {
		//$ret1 = $produit->updatePrice($produit->price_ttc, $produit->price_base_type, $user, $produit->tva_tx, $produit->price_min, 1, $produit->tva_npr, 0, 0, array());
		$ret1 = false;
		for ($i = 0; $i < 10; $i++) {
			if ($fields['TARIF' . ($i)] == 0)
				continue;
			$ret1 = $ret1 || $produit->updatePrice(price2num($fields['TARIF' . ($i)]), 'HT', $user, $produit->tva_tx, $produit->price_min, $i + 1, $produit->tva_npr, 0, 0, array()) < 0;
		}
		if ($ret1) {
			print " - Error in updatePrice result " . $produit->errorsToString();
			$errorrecord++;
		} else {
			print " - updatePrice OK";
		}
	}


	//        dol_syslog("Add multilangs");
	// Add alternative languages
	//        if (!$errorrecord && 1) {
	//            $produit->multilangs['fr_FR'] = array('label' => $produit->label, 'description' => $produit->description, 'note' => $produit->note_private);
	//            $produit->multilangs['en_US'] = array('label' => $fields[3], 'description' => $produit->description, 'note' => $produit->note_private);
	//
	//            $ret = $produit->setMultiLangs($user);
	//            if ($ret < 0) {
	//                print " - Error in setMultiLangs result code = " . $ret . " - " . $produit->errorsToString();
	//                $errorrecord++;
	//            } else {
	//                print " - setMultiLangs OK";
	//            }
	//        }


	dol_syslog("Add stocks");
	// stocks
	if (!$errorrecord && $fields['STOCK'] != 0) {
		$rets = $produit->correct_stock($user, 1, $fields['STOCK'], 0, 'Stock importé');
		if ($rets < 0) {
			print " - Error in correct_stock result " . $produit->errorsToString();
			$errorrecord++;
		} else {
			print " - correct_stock OK";
		}
	}

	//update date créa
	if (!$errorrecord) {
		$date = substr($fields['DATCREA'], 0, 4) . '-' . substr($fields['DATCREA'], 4, 2) . '-' . substr($fields['DATCREA'], 6, 2);
		$retd = $db->query("UPDATE `llx_product` SET `datec` = '$date 00:00:00' WHERE `llx_product`.`rowid` = $produit->id");
		if ($retd < 1) {
			print " - Error in update date créa result " . $produit->errorsToString();
			$errorrecord++;
		} else {
			print " - update date créa OK";
		}
	}
	print "\n";

	if ($errorrecord) {
		print( 'Error on record nb ' . $i . " - " . $produit->errorsToString() . "\n");
		var_dump($db);
		die();
		$error++;    // $errorrecord will be reset
	}
	$j++;
} else die("error : $sql");




// commit or rollback
print "Nb of lines qualified: " . $nboflines . "\n";
print "Nb of errors: " . $error . "\n";
$db->close();

exit($error);
