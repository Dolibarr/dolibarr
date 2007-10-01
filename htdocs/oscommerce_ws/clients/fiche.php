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
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
require_once("../includes/configure.php");



llxHeader();

if ($action == '' && !$cancel) {

 if ($_GET['custid'])
 {
  $osc_cust = new Osc_customer($db, $_GET['custid']);
  $result = $osc_cust->fetch($_GET['custid']);

  if ( !$result)
    { 
      print '<div class="titre">Fiche client OSC : '.$osc_cust->osc_custfirstname.'  '.$osc_cust->osc_custlastname.'</div><br>';

      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr></tr><td width="20%">Ville</td><td width="80%">'.$osc_cust->osc_custcity.'</td></tr>';
      print '<tr></tr><td width="20%">Pays</td><td width="80%">'.$osc_cust->osc_custcountry.'</td></tr>';
      print '<tr></tr><td width="20%">Id OSC</td><td width="80%">'.$osc_cust->osc_custid.'</td></tr>';
      print '<tr></tr><td width="20%">Téléphone</td><td width="80%">'.$osc_cust->osc_custtel.'</td></tr>';
      print '<tr></tr><td width="20%">E-mail</td><td width="80%">'.$osc_cust->osc_custmail.'</td></tr>';
      print "</table>";

	/* ************************************************************************** */
	/*                                                                            */ 
	/* Barre d'action                                                             */ 
	/*                                                                            */ 
	/* ************************************************************************** */
	print "\n<div class=\"tabsAction\">\n";

	  if ( $user->rights->societe->creer) {
        print '<a class="butAction" href="fiche.php?action=import&amp;custid='.$osc_cust->osc_custid.'">'.$langs->trans("Import").'</a>';
    	}
    print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
	print "\n</div><br>\n";
// seule action importer
     
	}
      else
	{
	  	print "\n<div class=\"tabsAction\">\n";
		  print "<p>ERROR 1c</p>\n";
	  	  dolibarr_print_error('',"erreur webservice ".$osc_cust->error);
    	  print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
		print "\n</div><br>\n";
	}
 }
 else
 {
  	print "\n<div class=\"tabsAction\">\n";
	   print "<p>ERROR 1b</p>\n";
	   print "Error";
	   print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
	print "\n</div><br>\n";
 }
}
/* action Import création de l'objet product de dolibarr 
*
*/
 if (($_GET["action"] == 'import' ) && ( $_GET["custid"] != '' ) && $user->rights->produit->creer)
    {
		  $osc_cust = new Osc_customer($db, $_GET['custid']);
  		  $result = $osc_cust->fetch($_GET['custid']);
	
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
		 } 

/* utilisation de la table de transco*/
		if ($osc_cust->get_clientid($osc_cust->osc_custid)>0)
		{
			print "\n<div class=\"tabsAction\">\n";
			print '<p>Ce client existe déjà mise à jour à prévoir</p>';
			print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
			print "\n</div><br>\n";
		}
		else {
			$id = $societe->create($user);
	       
		    if ($id == 0)
		    {
		    	print "\n<div class=\"tabsAction\">\n";
			    	print '<p>création réussie nouveau client/prospect : '.$societe->nom;
			    	$res = $osc_cust->transcode($osc_cust->osc_custid,$societe->id);
					print ' : Id Dolibarr '.$societe->id.' , Id osc : '.$osc_cust->osc_custid.'</p>';
		    		print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
				print "\n</div><br>\n";
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
			    	print "\n<div class=\"tabsAction\">\n";
		       		print '<br>erreur 1</br>';
			    		print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
					print "\n</div><br>\n";
		    	 }
			     $idp = $societe_control->fetch($socid = $osc_cust->osc_ref);
					
					if ($idp > 0) 
					{ 
						$res = $societe->update($idp, $user);
						if ($res < 0) print '<br>Erreur update '.$idp.'</br>';
						$res = $osc_cust->transcode($osc_cust->custid,$idp );
						if ($res < 0) print '<br>Erreur update '.$idp.'</br>';
					}
					else print '<br>update impossible $id : '.$idp.' </br>';
				}
		    }
		 }
 
    }

llxFooter('$Date$ - $Revision$');
?>
