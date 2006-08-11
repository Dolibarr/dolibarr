<?php
/*  Copyright (C) 2006      Jean Heimburger     <jean@tiaris.info>
 *
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
 * $Source$
 *
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once("../includes/configure.php");

llxHeader();

if ($action == '' && !$cancel) {

 if ($_GET["orderid"])
 {
  $osc_order = new Osc_order($_GET["orderid"]);
  $result = $osc_order->fetch($_GET["orderid"]);

  if ( !$result)
    { 
      print '<div class="titre">Fiche commande OSC : '.$osc_order->osc_orderid.'</div><br>';

      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr></tr><td width="20%">client OSC</td><td width="80%">'.$osc_order->osc_custid.'</td></tr>';
      print '<tr></tr><td width="20%">Nom client</td><td width="80%">'.$osc_order->osc_custname.'</td></tr>';
      print '<tr></tr><td width="20%">Montant</td><td width="80%">'.$osc_order->osc_ordertotal.'</td></tr>';
      print '<tr></tr><td width="20%">Date commande</td><td width="80%">'.$osc_order->osc_orderdate.'</td></tr>';
      print '<tr></tr><td width="20%">Méthode de paiement</td><td width="80%">'.$osc_order->osc_orderpaymet.'</td></tr>';
      print "</table>";

	/* ************************************************************************** */
	/*                                                                            */ 
	/* Barre d'action                                                             */ 
	/*                                                                            */ 
	/* ************************************************************************** */
	print "\n<div class=\"tabsAction\">\n";

	  if ( $user->rights->commande->creer) {
        print '<a class="tabAction" href="fiche.php?action=import&amp;orderid='.$osc_order->osc_orderid.'">'.$langs->trans("Import").'</a>';
    	}
	print "\n</div><br>\n";
// seule action importer
     
	}
      else
	{
	  print "<p>ERROR 1c</p>\n";
	  dolibarr_print_error('',"erreur webservice ".$osc_order->error);
	}
 }
 else
 {
   print "<p>ERROR 1b</p>\n";
   print "Error";
 }
}
/* action Import création de l'objet product de dolibarr 
*
*/
 if (($_GET["action"] == 'import' ) && ( $_GET["orderid"] != '' ) && $user->rights->commande->creer)
    {
		  $osc_order = new osc_order();
  		  $result = $osc_order->fetch($_GET["orderid"]);
		print '<br>on passe 1</br>';  
	  if ( !$result )
	  {
			$commande = new Commande($db);
	    	if ($_error == 1)
	    	{
	       		print '<br>erreur 1</br>';
				exit;
	    	}
		print '<br>on passe 2</br>';
	    	/* initialisation */
	    	$societe->nom = $osc_order->osc_ordersoc.' '.$osc_order->osc_orderlastname;
	    	$societe->adresse = $osc_order->osc_cutstreet;
	    	$societe->cp = $osc_order->osc_orderpostcode;
	    	$societe->ville = $osc_order->osc_ordercity;
	    	$societe->departement_id = 0;
	    	$societe->pays_code = $osc_order->osc_ordercodecountry;
	    	$societe->tel = $osc_order->osc_ordertel; 
	    	$societe->fax = $osc_order->osc_orderfax; 
	    	$societe->email = $osc_order->osc_ordermail; 
	/* on force */
			$societe->url = '';
			$societe->siren = '';
			$societe->siret = '';
			$societe->ape = '';
			$societe->client = 1; // mettre 0 si prospect
		 } 
		print '<br>on passe 3</br>';
			$id = $societe->create($user);
	       
		    if ($id > 0)
		    {
		       	print '<br>création réussie nouveau client/prospect '.$id.' nom : '.$societe->nom.'</br>';
				if ($id > 0)  exit;
		    }
		    else
		    {
		        if ($id == -3)
		        {
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
		    }
 
    }

llxFooter('$Date$ - $Revision$');
?>
