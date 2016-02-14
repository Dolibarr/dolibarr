#!/usr/bin/env php
<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * ATTENTION DE PAS EXECUTER CE SCRIPT SUR UNE INSTALLATION DE PRODUCTION
 */

/**
 *	    \file       dev/initdata/generate-proposal.php
 *		\brief      Script example to inject random proposals (for load tests)
 */

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
	exit;
}

// Recupere root dolibarr
$path=preg_replace('/generate-propale.php/i','',$_SERVER["PHP_SELF"]);
require ($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

/*
 * Parameters
 */

define(GEN_NUMBER_PROPAL, 5);


$ret=$user->fetch('','admin');
if (! $ret > 0)
{
	print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
	exit;
}
$user->getrights();


$socids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe WHERE client=1";
$resql = $db->query($sql);
if ($resql)
{
	$num_socs = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_socs)
	{
		$i++;

		$row = $db->fetch_row($resql);
		$socids[$i] = $row[0];
	}
}

$contids = array();
$sql = "SELECT rowid, fk_soc FROM ".MAIN_DB_PREFIX."socpeople";
$resql = $db->query($sql);
if ($resql)
{
	$num_conts = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_conts)
	{
		$i++;

		$row = $db->fetch_row($resql);
		$contids[$row[1]][0] = $row[0]; // A ameliorer
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

$user->rights->propal->creer=1;
$user->rights->propal->propal_advance->validate=1;


if (! empty($conf->global->PROPALE_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/propale/".$conf->global->PROPALE_ADDON.".php"))
{
	require_once(DOL_DOCUMENT_ROOT ."/core/modules/propale/".$conf->global->PROPALE_ADDON.".php");
}

$i=0;
$result=0;
while ($i < GEN_NUMBER_PROPAL && $result >= 0)
{
	$i++;
	$socid = rand(1, $num_socs);
	print "Proposal ".$i." for socid ".$socid;

	$soc = new Societe($db);


	$propal = new Propal($db);

	$obj = $conf->global->PROPALE_ADDON;
	$modPropale = new $obj;
	$numpr = $modPropale->getNextValue($soc,$propal);

	$propal->ref = $numpr;
	$propal->contactid = $contids[$socids[$socid]][0];
	$propal->socid = $socids[$socid];
	$propal->datep = time();
	$propal->cond_reglement_id = 3;
	$propal->mode_reglement_id = 3;
	$propal->author = $user->id;

	$result=$propal->create($user);
	if ($result >= 0)
	{
		$nbp = rand(2, 5);
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$prodid = rand(1, $num_prods);
			$product=new Product($db);
			$result=$product->fetch($prodids[$prodid]);
			$result=$propal->addline($product->description, $product->price, rand(1,5), 0, 0, 0, $prodids[$prodid], 0);
			if ($result < 0)
			{
				dol_print_error($db,$propal->error);
			}
			$xnbp++;
		}
		print " OK with ref ".$propal->ref."\n";
	}
	else
	{
		dol_print_error($db,$propal->error);
	}

}

