<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * ATTENTION DE PAS EXECUTER CE SCRIPT SUR UNE INSTALLATION DE PRODUCTION
 */

/**
        \file       htdocs/dev/generate-societe.php
		\brief      Script de generation de donnees aleatoires pour les societes
		\version    $Id$
*/

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

// Recupere root dolibarr
$path=eregi_replace('generate-societe.php','',$_SERVER["PHP_SELF"]);
require ($path."../htdocs/master.inc.php");
include_once(DOL_DOCUMENT_ROOT."/societe.class.php");
include_once(DOL_DOCUMENT_ROOT."/contact.class.php");
include_once(DOL_DOCUMENT_ROOT."/facture.class.php");
include_once(DOL_DOCUMENT_ROOT."/product.class.php");
include_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
include_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$villes = array("Auray","Baden","Vannes","Pirouville","Haguenau","Souffelweiersheim","Illkirch-Graffenstaden","Lauterbourg","Picauville","Sainte-Mère Eglise","Le Bono");
$prenoms = array("Joe","Marc","Steve","Laurent","Nico","Isabelle","Dorothee","Saby","Brigitte","Karine","Jose-Anne","Celine","Virginie");

	
/*
 * Parametre
 */

define (GEN_NUMBER_SOCIETE, 10);


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product"; $productsid = array();
if ($db->query($sql)) {
  $num = $db->num_rows(); $i = 0;	
  while ($i < $num) {      $row = $db->fetch_row($i);      $productsid[$i] = $row[0];      $i++; } }

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe"; $societesid = array();
if ($db->query($sql)) { $num = $db->num_rows(); $i = 0;	
while ($i < $num) { $row = $db->fetch_row($i);      $societesid[$i] = $row[0];      $i++; } } else { print "err"; }

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande"; $commandesid = array();
if ($db->query($sql)) { $num = $db->num_rows(); $i = 0;	
while ($i < $num) { $row = $db->fetch_row($i);      $commandesid[$i] = $row[0];      $i++; } } else { print "err"; }



print "Generates ".GEN_NUMBER_SOCIETE." companies\n";
for ($s = 0 ; $s < GEN_NUMBER_SOCIETE ; $s++)
{
    print "Company $s\n";
    $soc = new Societe($db);
    $soc->nom = "Company num ".time()."$s";
    $soc->ville = $villes[rand(0,sizeof($villes)-1)];
    $soc->client = rand(1,2);		// Une societe sur 2 est prospect, l'autre client
    $soc->fournisseur = rand(0,1);	// Une societe sur 2 est fournisseur
    $soc->tva_assuj=1;
    $soc->pays_id=1;
    $soc->pays_code='FR';
	// Un client sur 10 a une remise de 5%
    $user_remise=rand(1,10); if ($user_remise==10) $soc->remise_client=5;
	print "> client=".$soc->client.", fournisseur=".$soc->fournisseur.", remise=".$soc->remise_client."\n";
	$soc->note='Fictional company created by the script generate-societe.php';
    $socid = $soc->create();

    if ($socid >= 0)
    {
        $rand = rand(1,4);
        print "> Generates $rand contact\n";
        for ($c = 0 ; $c < $rand ; $c++)
        {
            $contact = new Contact($db);
            $contact->socid = $soc->id;
            $contact->name = "NomFamille".$c;
            $contact->firstname = $prenoms[rand(0,sizeof($prenoms)-1)];
            if ( $contact->create($user) )
            {

            }
        }
    }
    else
    {
    	print "Error: ".$soc->error."\n";
    }
}


?>
