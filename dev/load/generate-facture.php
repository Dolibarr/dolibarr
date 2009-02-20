<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * ATTENTION DE PAS EXECUTER CE SCRIPT SUR UNE INSTALLATION DE PRODUCTION
 */

/**
	    \file       htdocs/dev/generate-facture.php
		\brief      Script de génération de données aléatoires pour les factures
		\version    $Revision$
*/

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

// Recupere root dolibarr
$path=eregi_replace('generate-facture.php','',$_SERVER["PHP_SELF"]);
require ($path."../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");


/*
 * Parameters
 */

define (GEN_NUMBER_FACTURE, 5);


$sql = "SELECT min(rowid) FROM ".MAIN_DB_PREFIX."user";
$resql = $db->query($sql);
if ($resql) 
{
  $row = $db->fetch_row($resql);
  $user = new User($db, $row[0]);
}

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

$i=0;
$result=0;
while ($i < GEN_NUMBER_FACTURE && $result >= 0)
{
	$i++;
	$socid = rand(1, $num_socs);

	print "Invoice ".$i." for socid ".$socid;
	
	$facture = new Facture($db, $socids[$socid]);
	$facture->date = time();
	$facture->cond_reglement_id = 3;
	$facture->mode_reglement_id = 3;
	
	$nbp = rand(2, 5);
	$xnbp = 0;
	while ($xnbp < $nbp)
	{
	    // \TODO Utiliser addline plutot que add_product
		$prodid = rand(1, $num_prods);
		$facture->add_product($prodids[$prodid], rand(1,5), 0);
		$xnbp++;
	}
	
	$result=$facture->create($user);
	if ($result >= 0)
	{
		$result=$facture->set_valid($user,$socid);
		if ($result) print " OK";
		else
		{
			dol_print_error($db,$facture->error);
		}
	}
	else
	{
		dol_print_error($db,$facture->error);
	}
	
	print "\n";
}


?>
