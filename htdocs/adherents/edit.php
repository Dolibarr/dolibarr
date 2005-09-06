<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/adherents/edit.php
        \ingroup    adherent
		\brief      Page d'edition d'un adherent
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");

$langs->load("members");


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
      $adh->prenom      = $_POST["prenom"];
      $adh->nom         = $_POST["nom"];
      $adh->societe     = $_POST["societe"];
      $adh->adresse     = $_POST["adresse"];
      $adh->amount      = $_POST["amount"];
      $adh->cp          = $_POST["cp"];
      $adh->ville       = $_POST["ville"];
      $adh->email       = $_POST["email"];
      $adh->login       = $_POST["login"];
      $adh->pass        = $_POST["pass"];
      $adh->naiss       = $_POST["naiss"];
      $adh->photo       = $_POST["photo"];
      $adh->date        = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
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


    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$rowid;
    $head[$h][1] = $langs->trans("MemberCard");
    $hselected=$h;
    $h++;

    dolibarr_fiche_head($head, $hselected, $adh->fullname);


  print "<form action=\"edit.php\" method=\"post\">";
  print '<table class="border" width="100%">';
  
  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
  print "<input type=\"hidden\" name=\"statut\" value=\"".$adh->statut."\">";
  print "<input type=\"hidden\" name=\"public\" value=\"".$adh->public."\">";

  $htmls = new Form($db);


  print '<tr><td>'.$langs->trans("Type").'</td><td>';
  $htmls->select_array("type",  $adht->liste_array(), $adh->typeid);
  print "</td>";

  print '<td valign="top" width="50%">'.$langs->trans("Comments").'</td></tr>';

  $morphys["phy"] = $langs->trans("Physical");
  $morphys["mor"] = $langs->trans("Morale");

  print "<tr><td>".$langs->trans("Person")."</td><td>";
  $htmls->select_array("morphy",  $morphys, $adh->morphy);
  print "</td>";

  print '<td rowspan="15" valign="top">';
  print '<textarea name="comment" wrap="soft" cols="40" rows="15">'.$adh->commentaire.'</textarea></td></tr>';
  
  print '<tr><td width="15%">'.$langs->trans("Firstname").'</td><td width="35%"><input type="text" name="prenom" size="40" value="'.$adh->prenom.'"></td></tr>';
  
  print '<tr><td>'.$langs->trans("Name").'</td><td><input type="text" name="nom" size="40" value="'.$adh->nom.'"></td></tr>';


  print '<tr><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$adh->societe.'"></td></tr>';
  print '<tr><td>'.$langs->trans("Address").'</td><td>';
  print '<textarea name="adresse" wrap="soft" cols="40" rows="2">'.$adh->adresse.'</textarea></td></tr>';
  print '<tr><td>'.$langs->trans("Zip").'/'.$langs->trans("Town").'</td><td><input type="text" name="cp" size="6" value="'.$adh->cp.'"> <input type="text" name="ville" size="20" value="'.$adh->ville.'"></td></tr>';
  print '<tr><td>'.$langs->trans("Country").'</td><td>';
  $htmls->select_pays($adh->pays_id?$adh->pays_id:MAIN_INFO_SOCIETE_PAYS,'pays');
  print '</td></tr>';
  print '<tr><td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40" value="'.$adh->email.'"></td></tr>';
  print '<tr><td>'.$langs->trans("Login").'</td><td><input type="text" name="login" size="40" value="'.$adh->login.'"></td></tr>';
  print '<tr><td>'.$langs->trans("Password").'</td><td><input type="password" name="pass" size="40" value="'.$adh->pass.'"></td></tr>';
  print '<tr><td>'.$langs->trans("Birthday").'</td><td><input type="text" name="naiss" size="10" value="'.$adh->naiss.'"> ('.$langs->trans("DateFormatYYYYMMDD").')</td></tr>';
  print '<tr><td>URL photo</td><td><input type="text" name="photo" size="40" value="'.$adh->photo.'"></td></tr>';
  //  $myattr=$adho->fetch_name_optionals();
  foreach($adho->attribute_label as $key=>$value){
    //  foreach($myattr as $key){
    print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\" value=\"".$adh->array_options["options_$key"]."\"></td></tr>\n";
  }
  print '<tr><td colspan="2" align="center">';
  print '<input type="submit" class="button" name="bouton" value="'.$langs->trans("Save").'">&nbsp;';
  print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'">';
  print '</td></tr>';
  print '</table>';
  print '</form>';

  print '</div>';       
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
