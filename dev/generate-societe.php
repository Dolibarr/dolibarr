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
        \file       htdocs/dev/generate-societe.php
		\brief      Page de génération de données aléatoires pour les societes
		\version    $Revision$
*/

if (! file_exists("../htdocs/master.inc.php"))
{
	print "Error: This script must be run from its directory.\n"; 
	exit -1;	
}
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

define (GEN_NUMBER_SOCIETE, 10);


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product"; $productsid = array();
if ($db->query($sql)) {
  $num = $db->num_rows(); $i = 0;	
  while ($i < $num) {      $row = $db->fetch_row($i);      $productsid[$i] = $row[0];      $i++; } }

$sql = "SELECT idp FROM ".MAIN_DB_PREFIX."societe"; $societesid = array();
if ($db->query($sql)) { $num = $db->num_rows(); $i = 0;	
while ($i < $num) { $row = $db->fetch_row($i);      $societesid[$i] = $row[0];      $i++; } } else { print "err"; }

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande"; $commandesid = array();
if ($db->query($sql)) { $num = $db->num_rows(); $i = 0;	
while ($i < $num) { $row = $db->fetch_row($i);      $commandesid[$i] = $row[0];      $i++; } } else { print "err"; }



print "Génère ".GEN_NUMBER_SOCIETE." sociétés\n";
for ($s = 0 ; $s < GEN_NUMBER_SOCIETE ; $s++)
{
    print "Société $s\n";
    $soc = new Societe($db);
    $soc->nom = "Société aléatoire num ".time()."$s";
    $villes = array("Auray","Baden","Vannes","Pirouville","Haguenau","Souffelweiersheim","Illkirch-Graffenstaden","Lauterbourg","Picauville","Sainte-Mère Eglise","Le Bono");
    $soc->ville = $villes[rand(0,sizeof($villes)-1)];
	// Une societe sur 2 est prospect, l'autre client
    $soc->client = rand(1,2);
    // Un client sur 10 a une remise de 5%
    $user_remise=rand(1,10); if ($user_remise==10) $soc->remise_client=5;
	print "(client=".$soc->client.", remise=".$soc->remise_client.")\n";
    $socid = $soc->create();

    if ($socid >= 0)
    {
        $rand = rand(1,4);
        print "-- Génère $rand contact\n";
        for ($c = 0 ; $c < $rand ; $c++)
        {
            $contact = new Contact($db);
            $contact->socid = $soc->id;
            $contact->nom = "Nom aléa ".time()."-$c";
		    $prenoms = array("Joe","Marc","Steve","Laurent","Nico");
            $contact->prenom = $prenoms[rand(0,sizeof($prenoms)-1)];
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
