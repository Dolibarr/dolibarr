<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002      Jean-Louis Bergamo   <jlb@j1b.org>
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

/*! \file htdocs/adherents/edit.php
        \ingroup    adherent
		\brief      Page d'edition d'un adherent
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];

/* 
 * Enregistrer les modifs
 */

if ($action == 'update')
{

  if ($_POST["bouton"] == $langs->trans("Save"))
    {

      $adh = new Adherent($db);

      $adh->id          = $_POST["rowid"];
      $adh->prenom      = $prenom;
      $adh->nom         = $nom;  
      $adh->societe     = $societe;
      $adh->adresse     = $adresse;
      $adh->amount      = $amount;
      $adh->cp          = $cp;
      $adh->ville       = $_POST["ville"];
      $adh->email       = $_POST["email"];
      $adh->login       = $_POST["login"];
      $adh->pass        = $_POST["pass"];
      $adh->naiss       = $_POST["naiss"];
      $adh->photo       = $_POST["photo"];
      $adh->date        = mktime(12, 0 , 0, $remonth, $reday, $reyear);
      $adh->note        = $_POST["note"];
      $adh->pays        = $_POST["pays"];
      $adh->typeid      = $_POST["type"];
      $adh->commentaire = $_POST["comment"];
      $adh->morphy      = $_POST["morphy"];
      // recuperation du statut et public
      $adh->statut      = $_POST["statut"];
      $adh->public      = $_POST["public"];
      
      foreach($_POST as $key => $value){
	if (ereg("^options_",$key)){
	  $adh->array_options[$key]=$_POST[$key];
	}
      }
      if ($adh->update($user->id) ) 
	{	  
	  Header("Location: fiche.php?rowid=$adh->id&action=edit");
	}
      else
	{
	  Header("Location: edit.php?rowid=$adh->id");
	}

    }
  else
    {
      Header("Location: fiche.php?rowid=$rowid&action=edit");
    }
}


llxHeader();


if ($rowid)
{

  $adho = new AdherentOptions($db);
  $adh = new Adherent($db);
  $adh->id = $rowid;
  $adh->fetch($rowid);
  // fetch optionals value
  $adh->fetch_optionals($rowid);
  // fetch optionals attributes and labels
  $adho->fetch_optionals();

  $sql = "SELECT s.nom,s.idp, f.amount, f.total, f.facnumber";
  $sql .= " FROM societe as s, ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object($result);

      $total = $obj->total;
    }
  }

  $adht = new AdherentType($db);

  print_titre("Edition de la fiche adhérent");

  print "<form action=\"edit.php\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
  print "<input type=\"hidden\" name=\"statut\" value=\"".$adh->statut."\">";
  print "<input type=\"hidden\" name=\"public\" value=\"".$adh->public."\">";

  $htmls = new Form($db);


  print '<tr><td>'.$langs->trans("Type").'</td><td>';
  $htmls->select_array("type",  $adht->liste_array(), $adh->typeid);
  print "</td>";

  print '<td valign="top" width="50%">'.$langs->trans("Comments").'</td></tr>';

  $morphys["phy"] = "Physique";
  $morphys["mor"] = "Morale";

  print "<tr><td>Personne</td><td>";
  $htmls->select_array("morphy",  $morphys, $adh->morphy);
  print "</td>";

  print '<td rowspan="15" valign="top">';
  print '<textarea name="comment" wrap="soft" cols="40" rows="15">'.$adh->commentaire.'</textarea></td></tr>';
  
  print '<tr><td width="15%">Prénom</td><td width="35%"><input type="text" name="prenom" size="40" value="'.$adh->prenom.'"></td></tr>';
  
  print '<tr><td>Nom</td><td><input type="text" name="nom" size="40" value="'.$adh->nom.'"></td></tr>';


  print '<tr><td>Societe</td><td><input type="text" name="societe" size="40" value="'.$adh->societe.'"></td></tr>';
  print '<tr><td>Adresse</td><td>';
  print '<textarea name="adresse" wrap="soft" cols="40" rows="3">'.$adh->adresse.'</textarea></td></tr>';
  print '<tr><td>CP Ville</td><td><input type="text" name="cp" size="6" value="'.$adh->cp.'"> <input type="text" name="ville" size="20" value="'.$adh->ville.'"></td></tr>';
  print '<tr><td>Pays</td><td><input type="text" name="pays" size="40" value="'.$adh->pays.'"></td></tr>';
  print '<tr><td>Email</td><td><input type="text" name="email" size="40" value="'.$adh->email.'"></td></tr>';
  print '<tr><td>Login</td><td><input type="text" name="login" size="40" value="'.$adh->login.'"></td></tr>';
  print '<tr><td>Password</td><td><input type="password" name="pass" size="40" value="'.$adh->pass.'"></td></tr>';
  print '<tr><td>Date de naissance<BR>Format AAAA-MM-JJ</td><td><input type="text" name="naiss" size="40" value="'.$adh->naiss.'"></td></tr>';
  print '<tr><td>URL photo</td><td><input type="text" name="photo" size="40" value="'.$adh->photo.'"></td></tr>';
  //  $myattr=$adho->fetch_name_optionals();
  foreach($adho->attribute_label as $key=>$value){
    //  foreach($myattr as $key){
    print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\" value=\"".$adh->array_options["options_$key"]."\"></td></tr>\n";
  }
  print '<tr><td colspan="2" align="center">';
  print '<input type="submit" name="bouton" value="'.$langs->trans("Save").'">&nbsp;';
  print '<input type="submit" value="'.$langs->trans("Cancel").'>';
  print '</td></tr>';
  print '</form>';
  print '</table>';
       
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
