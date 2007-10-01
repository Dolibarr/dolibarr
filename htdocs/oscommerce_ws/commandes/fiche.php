<?php
/*  Copyright (C) 2006      Jean Heimburger     <jean@tiaris.info>
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
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once("../includes/configure.php");
require_once("../clients/osc_customer.class.php");
require_once("../produits/osc_product.class.php");



llxHeader();

if ($action == '' && !$cancel) {

 if ($_GET["orderid"])
 {
  $osc_order = new Osc_order($db, $_GET["orderid"]);
  $result = $osc_order->fetch($_GET["orderid"]);

  if ( !$result)
    { 
    $osc_prod = new Osc_Product($db);
      print '<div class="titre">Fiche commande OSC : '.$osc_order->osc_orderid.'</div><br>';

      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr></tr><td width="20%">client OSC</td><td width="80%">'.$osc_order->osc_custid.'</td></tr>';
      print '<tr></tr><td width="20%">Nom client</td><td width="80%">'.$osc_order->osc_custname.'</td></tr>';
      print '<tr></tr><td width="20%">Montant</td><td width="80%">'.convert_price($osc_order->osc_ordertotal).'</td></tr>';
      print '<tr></tr><td width="20%">Date commande</td><td width="80%">'.$osc_order->osc_orderdate.'</td></tr>';
      print '<tr></tr><td width="20%">Méthode de paiement</td><td width="80%">'.$osc_order->osc_orderpaymet.'</td></tr>';
      print "</table>";
      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      // les articles 
      for ($l=0;$l < sizeof($osc_order->osc_lines); $l++)
      {
      	print '<tr><td>'.$osc_order->osc_lines[$l]["products_id"].'</td><td>'.$osc_prod->get_productid($osc_order->osc_lines[$l]["products_id"]).'</td><td>'.$osc_order->osc_lines[$l]["products_name"].'</td><td>'.convert_price($osc_order->osc_lines[$l]["products_price"]).'</td><td>'.$osc_order->osc_lines[$l]["quantity"].'</td></tr>';
      }	
      print "</table>";

	/* ************************************************************************** */
	/*                                                                            */ 
	/* Barre d'action                                                             */ 
	/*                                                                            */ 
	/* ************************************************************************** */
	print "\n<div class=\"tabsAction\">\n";

	  if ( $user->rights->commande->creer) {
        print '<a class="butAction" href="fiche.php?action=import&amp;orderid='.$osc_order->osc_orderid.'">'.$langs->trans("Import").'</a>';
    	}
  	  print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
	print "\n</div><br>\n";
// seule action importer
     
	}
      else
	{
	  print "\n<div class=\"tabsAction\">\n";
		  print "<p>ERROR 1c</p>\n";
		  dolibarr_print_error('',"erreur webservice ".$osc_order->error);
		  print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
	  print "\n</div><br>\n";
	}
 }
 else
 {
	  print "\n<div class=\"tabsAction\">\n";
		  print "<p>ERROR 1b</p>\n";
		  print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
	  print "\n</div><br>\n";
 }
}
/* action Import création de l'objet commande de dolibarr 
*
*/
 if (($_GET["action"] == 'import' ) && ( $_GET["orderid"] != '' ) && $user->rights->commande->creer)
    {
		  $osc_order = new osc_order($db);
  		  $result = $osc_order->fetch($_GET["orderid"]);
	  if ( !$result )
	  {
			$commande = $osc_order->osc2dolibarr($_GET["orderid"]);
	  }

/* utilisation de la table de transco*/
		if ($osc_order->get_orderid($osc_order->osc_orderid)>0)
		{
			print '<p>Cette commande existe déjà</p>';
		}
		else {
// vérifier que la société est renseignée, sinon importer le client d'abord
			if ( ! $commande->socid) 
			{
				$osc_cust = new Osc_customer($db, $osc_order->osc_custid);
  		  		$result = $osc_cust->fetch($osc_order->osc_custid);
			  if ( !$result )
	  		  {
				$societe = new Societe($db);
	    		if ($_error == 1)
	    		{
				  	print "\n<div class=\"tabsAction\">\n";
		    		print '<br>erreur 1</br>';
		    		print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
					print "\n</div><br>\n";
		    	}
	    	/* initialisation */
		    	$societe->nom = $osc_cust->osc_custsoc.' '.$osc_cust->osc_custlastname;
		    	$societe->adresse = $osc_cust->osc_cutstreet;
		    	$societe->cp = $osc_cust->osc_custpostcode;
		    	$societe->ville = $osc_cust->osc_custcity;
		    	$societe->departement_id = 0;
		    	$societe->pays_code = $osc_cust->osc_custcodecountry;
		    	$societe->tel = $osc_cust->osc_custtel; 
		    	$societe->fax = $osc_cust->osc_custfax; 
		    	$societe->email = $osc_cust->osc_custmail; 
		/* on force */
				$societe->url = '';
				$societe->siren = '';
				$societe->siret = '';
				$societe->ape = '';
				$societe->client = 1; // mettre 0 si prospect

				$cl = $societe->create($user);
			   if ($cl == 0)
			    {
					$commande->socid = $societe->id;
		    	  	print '<p>création réussie nouveau client/prospect : '.$societe->nom;
			    	$res = $osc_cust->transcode($osc_cust->osc_custid,$societe->id);
					print ' : Id Dolibarr '.$societe->id.' , Id osc : '.$osc_cust->osc_custid.'</p>';
			    }
			    else
			    {
			    	print '<p>création impossible client : '. $osc_cust->osc_custid .'</p>';
			    	exit;
			    }
				}
			}
// vérifier l'existence des produits commandés
			$osc_product = new Osc_Product($db);
			$err = 0;

			for ($lig = 0; $lig < sizeof($commande->lines); $lig++)
			{
//				print "<p>traitement de ".$commande->lines[$lig]->fk_product."</p>";
				if (! $commande->lines[$lig]->fk_product) 
				{
					print "<p>Article non trouvé ".$commande->lines[$lig]->libelle." : ".$commande->lines[$lig]->desc."</p>";
					$err ++;
				}
			}			
			if ($err > 0) {
				print ("<p> Des produits de la commande sont inexistants</p>");
				$id =-9;
			}
			else $id = $commande->create($user);

		    if ($id > 0)
		    {
				  print "\n<div class=\"tabsAction\">\n";
		       	  print '<br>création réussie nouvelle commande '.$id;
   			     $res = $osc_order->transcode($osc_order->osc_orderid,$id);
					  print 'pour la commande osc : '.$osc_order->osc_orderid.'</p>';
					  print '<p><a class="butAction" href="index.php">'.$langs->trans("Retour").'</a></p>';
				  print "\n</div><br>\n";

				if ($id > 0)  exit;
		    }
		    else
		    {
		        if ($id == -3)
		        {
						print ("<p>$id = -3 ".$commande->error."</p>");
		            $_error = 1;
		            $_GET["action"] = "create";
		            $_GET["type"] = $_POST["type"];
		        }
				if ($id == -2)
				{
				/* la référence existe on fait un update */
				 $societe_control = new Societe($db);
				 if ($_error == 1)
		    	 {
		       		print '<br>erreur 1</br>';
					exit;
		    	 }
			     $id = $societe_control->fetch($ref = $osc_order->osc_ref);
					
					if ($id > 0) 
					{ 
						$id = $societe->update($id, $user);
						if ($id < 0) print '<br>Erreur update '.$id.'</br>';
					}
					else print '<br>update impossible $id : '.$id.' </br>';
				}
			  print '<p><a class="butAction" href="index.php">'.$langs->trans("Retour").'</a></p>';
		    }
		 }
 
    }

llxFooter('$Date$ - $Revision$');
?>
