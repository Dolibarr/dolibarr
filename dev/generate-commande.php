<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/dev/generate-commande.php
		\brief      Page de génération de données aléatoires pour les commandes
		\version    $Revision$
*/

require ("../htdocs/master.inc.php");
include_once(DOL_DOCUMENT_ROOT."/societe.class.php");
include_once(DOL_DOCUMENT_ROOT."/contact.class.php");
include_once(DOL_DOCUMENT_ROOT."/facture.class.php");
include_once(DOL_DOCUMENT_ROOT."/product.class.php");
include_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
include_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");


/*
 * Parametre
 */

define (GEN_NUMBER_COMMANDE, 10);


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe"; $societesid = array();
if ($db->query($sql)) { $num = $db->num_rows(); $i = 0;
	while ($i < $num) {
		$row = $db->fetch_row($i);      $societesid[$i] = $row[0];      $i++;
	}
}
else { print "err"; }

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande"; $commandesid = array();
if ($db->query($sql)) {
	$num = $db->num_rows(); $i = 0;
	while ($i < $num) {
		$row = $db->fetch_row($i);      $commandesid[$i] = $row[0];      $i++;
	}
}
else { print "err"; }


$prodids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE envente=1";
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

require(DOL_DOCUMENT_ROOT."/commande/commande.class.php");


print "Génère ".GEN_NUMBER_COMMANDE." commandes\n";
for ($s = 0 ; $s < GEN_NUMBER_COMMANDE ; $s++)
{
    print "Commande $s";

    $com = new Commande($db);
    
    $com->socid         = 4;
    $com->date_commande  = $dates[rand(1, sizeof($dates)-1)];
    $com->note           = $_POST["note"];
    $com->source         = 1;
    $com->projetid       = 0;
    $com->remise_percent = 0;
    
	$nbp = rand(2, 5);
	$xnbp = 0;
	while ($xnbp < $nbp)
	{
	    // \TODO Utiliser addline plutot que add_product
		$prodid = rand(1, $num_prods);
	    $com->add_product($prodids[$prodid],rand(1,11),rand(1,6),rand(0,20));
	}
	
    $com->create($user);
	$com->valid($user);

	print "\n";
}

?>
