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

/*!	\file htdocs/admin/system/gendata.php
		\brief      Page de génération de données aléatoires pour les commandes et expedition
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

$user->getrights('commande');
$user->getrights('expedition');

if (!$user->admin)
  accessforbidden();

llxHeader();

?>
<h2>Attention : Ceci est un générateur de données aléatoires, ne 
pas utiliser sur une base de données en production, les opérations ne sont pas réversibles</h2>

<a href="gendata.php">Home</a><br> 
<br>
<?php
include_once "../../societe.class.php";
include_once "../../contact.class.php";
include_once "../../facture.class.php";
include_once "../../product.class.php";
include_once "../../paiement.class.php";
include_once "../../contrat/contrat.class.php";

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

print '<table class="border"><tr>';
print '<td><a href="gendata.php?action=societe">Sociétés</a></td>';
print '<td><a href="gendata.php?action=product">Produits</a></td>';
print '<td><a href="gendata.php?action=facture">Factures</a></td>';
print '<td><a href="gendata.php?action=commande">Commandes</a></td>';

print '</tr><tr>';
print "<td>". sizeof($societesid) ."</td>";
print "<td>". sizeof($productsid) ."</td>";
print '<td>';
print "<td>". sizeof($commandesid) ."</td>";

print '</table>';
print "<p>";

if ($action == 'product')
{
  $randf = rand(1,200);

  print "Génère $randf produits<br>";
  for ($f = 0 ; $f < $randf ; $f++)
    {
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
}

if ($action == 'facture')
{
  $randf = rand(1,2);

  print "Génère $randf factures<br>";
  for ($f = 0 ; $f < $randf ; $f++)
    {
      $facture = new Facture($db, $societesid[rand(1, sizeof($societesid)-1)]);

      $facture->number         = time() . $f;
      $datef = time()*2;
      while($datef > time())
	$datef = mktime(12,0,0,rand(1,12),rand(1,31),rand(2002,2003));

      $facture->date           = $datef;
      $facture->note           = '';
      $facture->cond_reglement = 1;
      $facture->remise_percent = rand(0,50);

      $prand = rand(1,200);
      for ($p = 0 ; $p < $prand ; $p++)
	{
	  $pidrand = rand(1, sizeof($productsid)-1);
	  $facture->add_product($productsid[rand(1, sizeof($productsid)-1)],rand(1,11));
	  print "(AP ".$productsid[$pidrand].") ";
	}     

      $id = $facture->create($user);
      if ($id)
	{
	  print " - <b>facture $id ok";
	  $test = rand(0,1);
	  $test = 1;
	  if ($test > 0)
	    {
	      $facture->set_valid($id, $user);
	      print " - validée";
	    }

	  if($datef < (time() - (24*3600*30)))
	    {
	      $paiement = new Paiement($db);
	      $paiement->facid = $id;
	      $paiement->amount = $facture->total_ttc;
	      $paiement->paiementid = 1;
	      $paiement->datepaye = "now()";
	      $paiement->create($user);
	      $facture->set_payed($id);
	      print " - payée";
	    }

	  print "</b><br>";
	}

    }
}

if ($_GET["action"] == 'societe')
{

  $rands = rand(1,400);

  print "Génère $rands société<br>";
  for ($s = 0 ; $s < $rands ; $s++)
    {
      print "- société $s<br>";
      $soc = new Societe($db);
      $soc->nom = "Société aléatoire num ".time()."$s";
      $villes = array("Auray","Baden","Vannes","Pirouville","Haguenau","Souffelweiersheim","Illkirch-Graffenstaden","Lauterbourg","Picauville","Sainte-Mère Eglise","Le Bono");
      $soc->ville = $villes[rand(0,sizeof($villes)-1)];
      $soc->client = 1;
      $socid = $soc->create();
      
      if ($socid)
	{
	  $rand = rand(1,4);
	  print "-- génère $rand contact<br>";
	  for ($c = 0 ; $c < $rand ; $c++)
	    {
	      $contact = new Contact($db);
	      $contact->socid = $socid;
	      $contact->nom = "Nom aléa ".time()."-$c";
	      if ( $contact->create($user) )
		{

		}
	    }
	}
    }
}

if ($_GET["action"] == 'commande')
{
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
  
  require DOL_DOCUMENT_ROOT."/commande/commande.class.php";
  
  $com = new Commande($db);
  
  $com->soc_id         = 4;
  $com->date_commande  = $dates[rand(1, sizeof($dates)-1)];
  $com->note           = $_POST["note"];
  $com->source         = 1;
  $com->projetid       = 0;
  $com->remise_percent = 0;
  
  $pidrand = rand(1, sizeof($productsid)-1);
  $com->add_product($productsid[rand(1, sizeof($productsid)-1)],rand(1,11),rand(1,6),rand(0,20));
  $id = $com->create($user);
  print  " " . strftime("%d %B %Y",$com->date_commande);
  $com->fetch($id);
  print " " .  $com->valid($user);
}



llxFooter();
?>
