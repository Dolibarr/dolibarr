#!/usr/bin/env php
<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	    \file       dev/initdata/generate-proposal.php
 *		\brief      Script example to inject random proposals (for load tests)
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
//$path=preg_replace('/generate-propale.php/i','',$_SERVER["PHP_SELF"]);
require __DIR__. '/../../htdocs/master.inc.php';
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php";
require_once DOL_DOCUMENT_ROOT."/commande/class/commande.class.php";
require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";

/*
 * Parameters
 */

define('GEN_NUMBER_PROPAL', $argv[1] ?? 10);
$year = 2016;
$dates = array(mktime(12, 0, 0, 1, 3, $year),
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
if (! $ret > 0) {
	print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
	exit;
}
$user->loadRights();


$socids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client in (1,3)";
$resql = $db->query($sql);
if ($resql) {
	$num_thirdparties = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_thirdparties) {
		$i++;
		$row = $db->fetch_row($resql);
		$socids[$i] = $row[0];
	}
}

$contids = array();
$sql = "SELECT rowid, fk_soc FROM ".MAIN_DB_PREFIX."socpeople";
$resql = $db->query($sql);
if ($resql) {
	$num_conts = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_conts) {
		$i++;
		$row = $db->fetch_row($resql);
		$contids[$row[1]][0] = $row[0]; // A ameliorer
	}
}

$prodids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE tosell=1";
$resql = $db->query($sql);
if ($resql) {
	$num_prods = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_prods) {
		$i++;
		$row = $db->fetch_row($resql);
		$prodids[$i] = $row[0];
	}
}

$user->rights->propal->creer=1;
$user->rights->propal->propal_advance->validate=1;


if (getDolGlobalString('PROPALE_ADDON') && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/propale/" . getDolGlobalString('PROPALE_ADDON').".php")) {
	require_once DOL_DOCUMENT_ROOT ."/core/modules/propale/" . getDolGlobalString('PROPALE_ADDON').".php";
}

$i=0;
$result=0;
while ($i < GEN_NUMBER_PROPAL && $result >= 0) {
	$i++;
	$socid = mt_rand(1, $num_thirdparties);
	print "Proposal ".$i." for socid ".$socid;

	$soc = new Societe($db);


	$object = new Propal($db);

	$fuser = new User($db);
	$fuser->fetch(mt_rand(1, 2));
	$fuser->getRights();

	$object->contactid = $contids[$socids[$socid]][0];
	$object->socid = $socids[$socid];
	$object->datep = $dates[mt_rand(1, count($dates)-1)];
	$object->cond_reglement_id = 3;
	$object->mode_reglement_id = 3;

	$result=$object->create($fuser);
	if ($result >= 0) {
		$nbp = mt_rand(2, 5);
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$prodid = mt_rand(1, $num_prods);
			$product=new Product($db);
			$result=$product->fetch($prodids[$prodid]);
			$result=$object->addline($product->description, $product->price, mt_rand(1, 5), 0, 0, 0, $prodids[$prodid], 0);
			if ($result < 0) {
				dol_print_error($db, $object->error);
			}
			$xnbp++;
		}

		$result=$object->valid($fuser);
		if ($result > 0) {
			$db->commit();
			print " OK with ref ".$object->ref."\n";
		} else {
			print " KO\n";
			$db->rollback();
			dol_print_error($db, $object->error);
		}
	} else {
		dol_print_error($db, $object->error);
	}
}
