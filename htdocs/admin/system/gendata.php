<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

llxHeader();
?>
<h1>Attention</h1>
<h2>Ceci est un générateur de données aléatoires, ne 
pas utiliser sur une base de données en production, les opérations ne sont pas réversibles</h2>
<br>
<a href="gendata.php">Home</a> | 
<a href="gendata.php?action=societe">Sociétés</a> | 
<a href="gendata.php?action=product">Produits</a> | 
<a href="gendata.php?action=facture">Factures</a>
<br>
<?PHP
include_once "../../societe.class.php";
include_once "../../contact.class.php";
include_once "../../facture.class.php";
include_once "../../product.class.php";
include_once "../../paiement.class.php";
include_once "../../contrat/contrat.class.php";

$sql = "SELECT rowid FROM llx_product"; $productsid = array();
if ($db->query($sql)) {
  $num = $db->num_rows(); $i = 0;	
  while ($i < $num) {      $row = $db->fetch_row($i);      $productsid[$i] = $row[0];      $i++; } }

$sql = "SELECT idp FROM llx_societe"; $societesid = array();
if ($db->query($sql)) { $num = $db->num_rows(); $i = 0;	
while ($i < $num) { $row = $db->fetch_row($i);      $societesid[$i] = $row[0];      $i++; } } else { print "err"; }


print "". sizeof($societesid) ." sociétés ";
print " - ". sizeof($productsid) ." produits ";
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
      $produit->libelle = "Libelle";
      $produit->description = "Description";
      $produit->price = rand(1,10000);
      $produit->tva_tx = "19.6";
      $produit->create($user);
    }
}

if ($action == 'facture')
{
  $randf = rand(1,200);

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

      $prand = rand(1,20);
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

if ($action == 'societe')
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

llxFooter();
?>
