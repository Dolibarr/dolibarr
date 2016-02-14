#!/usr/bin/env php
<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 	    \file       dev/initdata/generate-order.php
 * 		\brief      Script example to inject random orders (for load tests)
 */

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

// Recupere root dolibarr
$path=preg_replace('/generate-commande.php/i','',$_SERVER["PHP_SELF"]);
require ($path."../../htdocs/master.inc.php");
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';


/*
 * Parametre
 */

define(GEN_NUMBER_COMMANDE, 10);


$ret=$user->fetch('','admin');
if ($ret <= 0)
{
    print 'A user with login "admin" and all permissions must be created to use this script.'."\n";
    exit;
}
$user->getrights();


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe"; $societesid = array();
$resql=$db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$row = $db->fetch_row($resql);
		$societesid[$i] = $row[0];
		$i++;
	}
}
else { print "err"; }

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande"; $commandesid = array();
$resql=$db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$row = $db->fetch_row($resql);
		$commandesid[$i] = $row[0];
		$i++;
	}
}
else { print "err"; }


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


$dates = array (mktime(12,0,0,1,3,2003),
	  mktime(12,0,0,1,9,2003),
	  mktime(12,0,0,2,13,2003),
	  mktime(12,0,0,2,23,2003),
	  mktime(12,0,0,3,30,2003),
	  mktime(12,0,0,4,3,2003),
	  mktime(12,0,0,4,3,2003),
	  mktime(12,0,0,5,9,2003),
	  mktime(12,0,0,5,1,2003),
	  mktime(12,0,0,5,13,2003),
	  mktime(12,0,0,5,19,2003),
	  mktime(12,0,0,5,23,2003),
	  mktime(12,0,0,6,3,2003),
	  mktime(12,0,0,6,19,2003),
	  mktime(12,0,0,6,24,2003),
	  mktime(12,0,0,7,3,2003),
	  mktime(12,0,0,7,9,2003),
	  mktime(12,0,0,7,23,2003),
	  mktime(12,0,0,7,30,2003),
	  mktime(12,0,0,8,9,2003),
	  mktime(12,0,0,9,23,2003),
	  mktime(12,0,0,10,3,2003),
	  mktime(12,0,0,11,12,2003),
	  mktime(12,0,0,11,13,2003),
	  mktime(12,0,0,1,3,2002),
	  mktime(12,0,0,1,9,2002),
	  mktime(12,0,0,2,13,2002),
	  mktime(12,0,0,2,23,2002),
	  mktime(12,0,0,3,30,2002),
	  mktime(12,0,0,4,3,2002),
	  mktime(12,0,0,4,3,2002),
	  mktime(12,0,0,5,9,2002),
	  mktime(12,0,0,5,1,2002),
	  mktime(12,0,0,5,13,2002),
	  mktime(12,0,0,5,19,2002),
	  mktime(12,0,0,5,23,2002),
	  mktime(12,0,0,6,3,2002),
	  mktime(12,0,0,6,19,2002),
	  mktime(12,0,0,6,24,2002),
	  mktime(12,0,0,7,3,2002),
	  mktime(12,0,0,7,9,2002),
	  mktime(12,0,0,7,23,2002),
	  mktime(12,0,0,7,30,2002),
	  mktime(12,0,0,8,9,2002),
	  mktime(12,0,0,9,23,2002),
	  mktime(12,0,0,10,3,2002),
	  mktime(12,0,0,11,12,2003),
	  mktime(12,0,0,11,13,2003),
	  mktime(12,0,0,12,12,2003),
	  mktime(12,0,0,12,13,2003),
	  );

require(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");


print "Build ".GEN_NUMBER_COMMANDE." orders\n";
for ($s = 0 ; $s < GEN_NUMBER_COMMANDE ; $s++)
{
    print "Process order ".$s."\n";

    $com = new Commande($db);

    $com->socid         = 4;
    $com->date_commande  = $dates[rand(1, count($dates)-1)];
    $com->note           = 'A comment';
    $com->source         = 1;
    $com->fk_project     = 0;
    $com->remise_percent = 0;

    $db->begin();

    $result=$com->create($user);
	if ($result >= 0)
	{
		$result=$com->valid($user);
		if ($result > 0)
		{
            $nbp = rand(2, 5);
            $xnbp = 0;
            while ($xnbp < $nbp)
            {
                $prodid = rand(1, $num_prods);
                $product=new Product($db);
                $result=$product->fetch($prodids[$prodid]);
                $result=$com->addline($product->description, $product->price, rand(1,5), 0, 0, 0, $prodids[$prodid], 0, 0, 0,  $product->price_base_type, $product->price_ttc, '', '', $product->type);
                if ($result < 0)
                {
                    dol_print_error($db,$propal->error);
                }
                $xnbp++;
            }

            $db->commit();
            print " OK with ref ".$com->ref."\n";
		}
		else
		{
            print " KO\n";
		    $db->rollback();
		    dol_print_error($db,$com->error);
		}
	}
	else
	{
        print " KO\n";
	    $db->rollback();
	    dol_print_error($db,$com->error);
	}
}

