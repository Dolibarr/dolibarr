<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/*!	\file htdocs/compta/dons/fiche.php
		\ingroup    don
		\brief      Page de fiche de don
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../don.class.php");
require("../../paiement.class.php");



$mesg="";

if ($_POST["action"] == 'update') 
{
    
    if ($_POST["amount"] > 0)
    {

      $don = new Don($db);
      $don->id = $_POST["rowid"];
      $don->fetch($_POST["rowid"]);

      $don->prenom      = $_POST["prenom"];
      $don->nom         = $_POST["nom"];
      $don->societe     = $_POST["societe"];
      $don->adresse     = $_POST["adresse"];
      $don->amount      = $_POST["amount"];
      $don->cp          = $_POST["cp"];
      $don->ville       = $_POST["ville"];
      $don->email       = $_POST["email"];
      $don->date        = mktime(12, 0 , 0, $remonth, $reday, $reyear);
      $don->note        = $_POST["note"];
      $don->pays        = $_POST["pays"];
      $don->public      = $_POST["public"];
      $don->projetid    = $_POST["projetid"];
      $don->commentaire = $_POST["comment"];
      $don->modepaiementid = $_POST["modepaiement"];
      
      if ($don->update($user->id) ) 
	{	  
	  Header("Location: index.php");
	}
    }
  else 
    {
      $mesg="Montant non défini";
    }
}

if ($_POST["action"] == 'add') 
{
  
    if ($_POST["amount"] > 0)
    {

      $don = new Don($db);
      
      $don->prenom      = $_POST["prenom"];
      $don->nom         = $_POST["nom"];
      $don->societe     = $_POST["societe"];
      $don->adresse     = $_POST["adresse"];
      $don->amount      = $_POST["amount"];
      $don->cp          = $_POST["cp"];
      $don->ville       = $_POST["ville"];
      $don->email       = $_POST["email"];
      $don->date        = mktime(12, 0 , 0, $remonth, $reday, $reyear);
      $don->note        = $_POST["note"];
      $don->pays        = $_POST["pays"];
      $don->public      = $_POST["public"];
      $don->projetid    = $_POST["projetid"];
      $don->commentaire = $_POST["comment"];
      $don->modepaiementid = $_POST["modepaiement"];
      
      if ($don->create($user->id) ) 
	{	  
	  Header("Location: index.php");
	}
    }
  else 
    {
      $mesg="Montant non défini";
      $_GET["action"] = "create";
    }
}

if ($_GET["action"] == 'delete')
{
  $don = new Don($db);
  $don->delete($_GET["rowid"]);
  Header("Location: liste.php");
}
if ($_POST["action"] == 'commentaire')
{
  $don = new Don($db);
  $don->set_commentaire($_POST["rowid"],$_POST["commentaire"]);
  $_GET["rowid"] = $_POST["rowid"];
}
if ($_GET["action"] == 'valid_promesse')
{
  $don = new Don($db);
  if ($don->valid_promesse($_GET["rowid"], $user->id))
    {
      Header("Location: liste.php");
    }
}
if ($_GET["action"] == 'set_payed')
{
  $don = new Don($db);
  if ($don->set_paye($_GET["rowid"], $modepaiement)) 
    {
      Header("Location: liste.php");
    }
}
if ($_GET["action"] == 'set_encaisse')
{
  $don = new Don($db);
  if ($don->set_encaisse($_GET["rowid"]))
    {
      Header("Location: liste.php");
    }
}



llxHeader();


/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche don                                                   */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create') {

  print_titre("Saisir un don");

  print '<form action="fiche.php" method="post">';
  print '<table class="border" width="100%">';
  
  print '<input type="hidden" name="action" value="add">';
  
  print "<tr $bc[1]>".'<td>Date du don</td><td>';
  print_date_select();
  print '</td>';
  
  print '<td rowspan="12" valign="top">'.$langs->trans("Comments").' :<br>';
  print "<textarea name=\"comment\" wrap=\"soft\" cols=\"40\" rows=\"15\"></textarea></td></tr>";
  print "<tr $bc[1]><td>Mode de paiement</td><td>\n";
  
  $paiement = new Paiement($db);

  $paiement->select("modepaiement","crédit");

  print "</td></tr>\n";

  print "<tr $bc[1]><td>".$langs->trans("Project")."</td><td><select name=\"projetid\">\n";
  $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."don_projet ORDER BY rowid";
  if ($db->query($sql))
    {
      $num = $db->num_rows();
      $i = 0; 
      while ($i < $num) 
	{
	  $objopt = $db->fetch_object( $i);
	  print "<option value=\"$objopt->rowid\">$objopt->libelle</option>\n";
	  $i++;
	}    
    }
  else
    {
      dolibarr_print_error($db);
    }
  print "</select><br>";
  print "</td></tr>\n";

  print "<tr $bc[1]><td>Don public</td><td><select name=\"public\">\n";
  
  print '<option value="1">oui</option>';
  print '<option value="0">non</option>';

  print "</select><br>";
  print "</td></tr>\n";

  $langs->load("companies");
  print "<tr $bc[1]>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="prenom" size="40"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("LastName").'</td><td><input type="text" name="nom" size="40"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Address").'</td><td>';
  print '<textarea name="adresse" wrap="soft" cols="40" rows="3"></textarea></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td><input type="text" name="cp" size="8"> <input type="text" name="ville" size="40"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" size="40"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10"> euros</td></tr>';
  print "<tr $bc[1]>".'<td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
  print "</table>\n";
  print "</form>\n";
      
} 


/* ************************************************************ */
/*                                                              */
/* Fiche don en mode edition                                    */
/*                                                              */
/* ************************************************************ */

if ($_GET["rowid"] && $_GET["action"] == 'edit')
{

  $don = new Don($db);
  $don->id = $_GET["rowid"];
  $don->fetch($_GET["rowid"]);

  print_titre("Traitement du don");

  print '<form action="fiche.php" method="post">';
  print '<table class="border" width="100%">';
  
  print '<input type="hidden" name="action" value="update">';
  print '<input type="hidden" name="rowid" value="'.$don->id.'">';
  
  print "<tr $bc[1]>".'<td>Date du don</td><td>';
  print_date_select($don->date);
  print '</td>';
  
  print '<td rowspan="12" valign="top">'.$langs->trans("Comments").' :<br>';
  print "<textarea name=\"comment\" wrap=\"soft\" cols=\"40\" rows=\"15\">$don->commentaire</textarea></td></tr>";

  print "<tr $bc[1]><td>".$langs->trans("Project")."</td><td><select name=\"projetid\">\n";
  $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."don_projet ORDER BY rowid";
  if ($db->query($sql))
    {
      $num = $db->num_rows();
      $i = 0; 
      while ($i < $num) 
	{
	  $objopt = $db->fetch_object( $i);
	  print "<option value=\"$objopt->rowid\">$objopt->libelle</option>\n";
	  $i++;
	}    
    }
  else
    {
      dolibarr_print_error($db);
    }
  print "</select><br>";
  print "</td></tr>\n";

  print "<tr $bc[1]><td>Don public</td><td><select name=\"public\">\n";
  print '<option value="1">oui</option>';
  print '<option value="0">non</option>';
  print "</select><br>";
  print "</td></tr>\n";

  $langs->load("companies");
  print "<tr $bc[1]>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$don->societe.'"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="prenom" size="40" value="'.$don->prenom.'"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("LastName").'</td><td><input type="text" name="nom" size="40" value="'.$don->nom.'"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Address").'</td><td>';
  print '<textarea name="adresse" wrap="soft" cols="40" rows="3">'.$don->adresse.'</textarea></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td><input type="text" name="cp" size="8" value="'.$don->cp.'"> <input type="text" name="ville" size="40" value="'.$don->ville.'"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" size="40" value="'.$don->pays.'"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40" value="'.$don->email.'"></td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10" value="'.$don->amount.'"> euros</td></tr>';

  print "<tr $bc[1]><td>Mode de paiement</td><td>\n";
  $paiement = new Paiement($db);
  $paiement->select("modepaiement","crédit");
  print "</td></tr>\n";

  print "<tr $bc[1]>".'<td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';

  print "</table>\n";
  print "</form>\n";

}



/* ************************************************************ */
/*                                                              */
/* Fiche don en mode visu                                       */
/*                                                              */
/* ************************************************************ */
if ($_GET["rowid"] && $_GET["action"] != 'edit')
{

  $don = new Don($db);
  $don->id = $_GET["rowid"];
  $don->fetch($_GET["rowid"]);

  print_titre("Traitement du don");
  print "<form action=\"fiche.php\" method=\"post\">";
  print '<table class="border" width="100%">';
  
  print "<tr $bc[1]><td>Date du don</td><td>";
  print strftime("%d %B %Y",$don->date);
  print "</td>";
  
  print '<td rowspan="12" valign="top" width="50%">'.$langs->trans("Comments").' :<br>';
  print nl2br($don->commentaire).'</td></tr>';

  print "<tr $bc[1]>".'<td>Projet</td><td>'.$don->projet.'</td></tr>';

  print "<tr $bc[1]><td>Don public</td><td>";

  print $yn[$don->public];
  print "</td></tr>\n";


  print "<tr $bc[1]>".'<td>'.$langs->trans("Company").'</td><td>'.$don->societe.'</td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Firstname").'</td><td>'.$don->prenom.'</td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("LastName").'</td><td>'.$don->nom.'</td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Address").'</td><td>'.nl2br($don->adresse).'</td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>'.$don->cp.' '.$don->ville.'</td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Country").'</td><td>'.$don->pays.'</td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("EMail").'</td><td>'.$don->email.'</td></tr>';
  print "<tr $bc[1]>".'<td>'.$langs->trans("Amount").'</td><td>'.price($don->amount).' euros</td></tr>';
  if ($don->statut == 1)
    {
      print "<tr $bc[1]><td>Mode de paiement</td><td>";
      $paiement = new Paiement($db);
      $paiement->select("modepaiement","crédit", $don->modepaiementid);
      print "</td></tr>\n";
    }
  else 
    {
      print "<tr $bc[1]><td>Mode de paiement</td><td>";
      print $don->modepaiement;
      print "</td></tr>\n";
    }

  print "</table>\n";
  print "</form>\n";
  

    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    print '<a class="tabAction" href="fiche.php?action=edit&rowid='.$don->id.'">'.$langs->trans('Edit').'</a>';
	
    if ($don->statut == 1 && $resteapayer > 0) 
    {
      print "<a class=\"tabAction\" href=\"paiement.php?facid=$facid&action=create\">Emettre un paiement</a>";
    }

    if ($don->statut == 0)
    {
      print "<a class=\"tabAction\" href=\"fiche.php?rowid=$don->id&action=valid_promesse\">Valider la promesse</a>";
    }
 
    if ($don->statut == 3)
    {
      print "<a class=\"tabAction\" href=\"formulaire/".DONS_FORM."?rowid=$don->id\">Formulaire</a>";
    }

    if ($don->statut == 1 && abs($resteapayer == 0) && $don->paye == 0) 
    {
      print "<a class=\"tabAction\" href=\"fiche.php?rowid=$don->id&action=set_payed\">Classé payé</a>";
    }

    if ($don->statut == 0) 
    {
      print "<a class=\"tabAction\" href=\"fiche.php?rowid=$don->id&action=delete\">".$langs->trans("Delete")."</a>";
    }
    if ($don->statut == 2)
    {
      print "<a class=\"tabAction\" href=\"fiche.php?rowid=$don->id&action=set_encaisse\">Encaisser</a>";
    }

    print "</div><br>";

}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
