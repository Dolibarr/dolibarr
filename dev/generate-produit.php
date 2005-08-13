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
        \file       htdocs/dev/generate-produit.php
		\brief      Page de génération de données aléatoires pour les produits
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

define (GEN_NUMBER_PRODUIT, 10);


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


print "Génère ".GEN_NUMBER_PRODUIT." produits\n";
for ($s = 0 ; $s < GEN_NUMBER_PRODUIT ; $s++)
{
    print "Produit $s\n";
    $produit = new Product($db);
    $produit->type = 1;
    $produit->envente = 1;
    $produit->ref = time() . "$f";
    $produit->libelle = $langs->trans("Label");
    $produit->description = $langs->trans("Description");
    $produit->price = rand(1,10000);
    $produit->tva_tx = "19.6";
    $produit->create($user);
}


?>
