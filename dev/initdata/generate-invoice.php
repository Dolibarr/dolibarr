#!/usr/bin/env php
<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * ATTENTION DE PAS EXECUTER CE SCRIPT SUR UNE INSTALLATION DE PRODUCTION
 */

/**
 *      \file       dev/initdata/generate-invoice.php
 *		\brief      Script example to inject random customer invoices (for load tests)
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Recupere root dolibarr
//$path=preg_replace('/generate-produit.php/i','',$_SERVER["PHP_SELF"]);
require __DIR__. '/../../htdocs/master.inc.php';
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";


/*
 * Parameters
 */

define(GEN_NUMBER_FACTURE, 1);
$year = 2016;
$dates = array (mktime(12, 0, 0, 1, 3, $year),
    mktime(12, 0, 0, 1, 9, $year),
    mktime(12, 0, 0, 2, 13, $year),
    mktime(12, 0, 0, 2, 23, $year),
    mktime(12, 0, 0, 3, 30, $year),
    mktime(12, 0, 0, 4, 3, $year),
    mktime(12, 0, 0, 4, 3, $year),
    mktime(12, 0, 0, 5, 9, $year),
    mktime(12, 0, 0, 5, 1, $year),
    mktime(12, 0, 0, 5, 13, $year),
    mktime(12, 0, 0, 5, 19, $year),
    mktime(12, 0, 0, 5, 23, $year),
    mktime(12, 0, 0, 6, 3, $year),
    mktime(12, 0, 0, 6, 19, $year),
    mktime(12, 0, 0, 6, 24, $year),
    mktime(12, 0, 0, 7, 3, $year),
    mktime(12, 0, 0, 7, 9, $year),
    mktime(12, 0, 0, 7, 23, $year),
    mktime(12, 0, 0, 7, 30, $year),
    mktime(12, 0, 0, 8, 9, $year),
    mktime(12, 0, 0, 9, 23, $year),
    mktime(12, 0, 0, 10, 3, $year),
    mktime(12, 0, 0, 11, 12, $year),
    mktime(12, 0, 0, 11, 13, $year),
    mktime(12, 0, 0, 1, 3, ($year - 1)),
    mktime(12, 0, 0, 1, 9, ($year - 1)),
    mktime(12, 0, 0, 2, 13, ($year - 1)),
    mktime(12, 0, 0, 2, 23, ($year - 1)),
    mktime(12, 0, 0, 3, 30, ($year - 1)),
    mktime(12, 0, 0, 4, 3, ($year - 1)),
    mktime(12, 0, 0, 4, 3, ($year - 1)),
    mktime(12, 0, 0, 5, 9, ($year - 1)),
    mktime(12, 0, 0, 5, 1, ($year - 1)),
    mktime(12, 0, 0, 5, 13, ($year - 1)),
    mktime(12, 0, 0, 5, 19, ($year - 1)),
    mktime(12, 0, 0, 5, 23, ($year - 1)),
    mktime(12, 0, 0, 6, 3, ($year - 1)),
    mktime(12, 0, 0, 6, 19, ($year - 1)),
    mktime(12, 0, 0, 6, 24, ($year - 1)),
    mktime(12, 0, 0, 7, 3, ($year - 1)),
    mktime(12, 0, 0, 7, 9, ($year - 1)),
    mktime(12, 0, 0, 7, 23, ($year - 1)),
    mktime(12, 0, 0, 7, 30, ($year - 1)),
    mktime(12, 0, 0, 8, 9, ($year - 1)),
    mktime(12, 0, 0, 9, 23, ($year - 1)),
    mktime(12, 0, 0, 10, 3, ($year - 1)),
    mktime(12, 0, 0, 11, 12, $year),
    mktime(12, 0, 0, 11, 13, $year),
    mktime(12, 0, 0, 12, 12, $year),
    mktime(12, 0, 0, 12, 13, $year),
);

$ret=$user->fetch('', 'admin');
if (! $ret > 0)
{
	print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
	exit;
}
$user->getrights();


$socids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client in (1, 3)";
$resql = $db->query($sql);
if ($resql)
{
	$num_thirdparties = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_thirdparties)
	{
		$i++;
		$row = $db->fetch_row($resql);
		$socids[$i] = $row[0];
	}
}

$prodids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE tosell=1";
$resql = $db->query($sql);
if ($resql)
{
	$num_prods = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_prods)
	{
		$i++;
		$row = $db->fetch_row($resql);
		$prodids[$i] = $row[0];
	}
}

$i=0;
$result=0;
while ($i < GEN_NUMBER_FACTURE && $result >= 0)
{
	$i++;
	$socid = mt_rand(1, $num_thirdparties);

	print "Invoice ".$i." for socid ".$socid;

	$object = new Facture($db);
	$object->socid = $socids[$socid];
	$object->date = $dates[mt_rand(1, count($dates)-1)];
	$object->cond_reglement_id = 3;
	$object->mode_reglement_id = 3;

    $fuser = new User($db);
    $fuser->fetch(mt_rand(1, 2));
    $fuser->getRights();

	$result=$object->create($fuser);
	if ($result >= 0)
	{
		$nbp = mt_rand(2, 5);
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$prodid = mt_rand(1, $num_prods);
			$product=new Product($db);
			$result=$product->fetch($prodids[$prodid]);
			$result=$object->addline($product->description, $product->price, mt_rand(1, 5), 0, 0, 0, $prodids[$prodid], 0, '', '', 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type);
		    if ($result < 0)
            {
                dol_print_error($db, $propal->error);
            }
            $xnbp++;
		}

	    $result=$object->validate($fuser);
		if ($result)
		{
			print " OK with ref ".$object->ref."\n";;
		} else {
			dol_print_error($db, $object->error);
		}
	} else {
		dol_print_error($db, $object->error);
	}
}
