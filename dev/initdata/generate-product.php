#!/usr/bin/env php
<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       dev/initdata/generate-product.php
 *		\brief      Script example to inject random products (for load tests)
 */

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

// Recupere root dolibarr
//$path=preg_replace('/generate-produit.php/i','',$_SERVER["PHP_SELF"]);
require __DIR__. '/../../htdocs/master.inc.php';
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';


/*
 * Parameters
 */

define(GEN_NUMBER_PRODUIT, 100000);


$ret=$user->fetch('', 'admin');
if (! $ret > 0)
{
	print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
	exit;
}
$user->getrights();


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product"; $productsid = array();
$resql=$db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql); $i = 0;
    while ($i < $num) {      $row = $db->fetch_row($resql);      $productsid[$i] = $row[0];      $i++; }
}

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe"; $societesid = array();
$resql=$db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql); $i = 0;
    while ($i < $num) { $row = $db->fetch_row($resql);      $societesid[$i] = $row[0];      $i++; }
} else { print "err"; }

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande"; $commandesid = array();
$resql=$db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql); $i = 0;
    while ($i < $num) { $row = $db->fetch_row($resql);      $commandesid[$i] = $row[0];      $i++; }
} else { print "err"; }


print "Generates ".GEN_NUMBER_PRODUIT." products\n";
for ($s = 0 ; $s < GEN_NUMBER_PRODUIT ; $s++)
{
    print "Product ".$s;
    $produit = new Product($db);
    $produit->type = mt_rand(0, 1);
    $produit->status = 1;
    $produit->ref = ($produit->type?'S':'P').time().$s;
    $produit->label = 'Label '.time().$s;
    $produit->description = 'Description '.time().$s;
    $produit->price = mt_rand(1, 1000);
    $produit->tva_tx = "19.6";
    $ret=$produit->create($user);
    if ($ret < 0) print "Error $ret - ".$produit->error."\n";
	else print " OK with ref ".$produit->ref."\n";
}
