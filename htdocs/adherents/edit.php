<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *                         Jean-Louis Bergamo <jlb@j1b.org>
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
require("./pre.inc.php");
require("../adherent.class.php");
require("../adherent_type.class.php");
require($GLOBALS["DOCUMENT_ROOT"]."/adherents/adherent_options.class.php");

$db = new Db();

/* 
 * Enregistrer les modifs
 */

if ($action == 'update')
{

  if ($HTTP_POST_VARS["bouton"] == "Enregistrer")
    {

      $adh = new Adherent($db);

      $adh->id          = $HTTP_POST_VARS["rowid"];
      $adh->prenom      = $prenom;
      $adh->nom         = $nom;  
      $adh->societe     = $societe;
      $adh->adresse     = $adresse;
      $adh->amount      = $amount;
      $adh->cp          = $cp;
      $adh->ville       = $HTTP_POST_VARS["ville"];
      $adh->email       = $HTTP_POST_VARS["email"];
      $adh->login       = $HTTP_POST_VARS["login"];
      $adh->pass        = $HTTP_POST_VARS["pass"];
      $adh->naiss       = $HTTP_POST_VARS["naiss"];
      $adh->photo       = $HTTP_POST_VARS["photo"];
      $adh->date        = mktime(12, 0 , 0, $remonth, $reday, $reyear);
      $adh->note        = $HTTP_POST_VARS["note"];
      $adh->pays        = $HTTP_POST_VARS["pays"];
      $adh->typeid      = $HTTP_POST_VARS["type"];
      $adh->commentaire = $HTTP_POST_VARS["comment"];
      $adh->morphy      = $HTTP_POST_VARS["morphy"];
      // recuperation du statut et public
      $adh->statut      = $HTTP_POST_VARS["statut"];
      $adh->public      = $HTTP_POST_VARS["public"];
      
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
	  Header("Location: $PHP_SELF?rowid=$adh->id");
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

  $adh = new Adherent($db);
  $adh->id = $rowid;
  $adh->fetch($rowid);
  $adh->fetch_optionals($rowid);

  $sql = "SELECT s.nom,s.idp, f.amount, f.total, f.facnumber";
  $sql .= " FROM societe as s, llx_facture as f WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object( 0);

      $total = $obj->total;
    }
  }

  $adht = new AdherentType($db);

  print_titre("Edition de la fiche adhérent");


  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print "<tr><td>Type</td><td class=\"valeur\">$adh->type</td>";
  print '<td valign="top" width="50%">Commentaires</td></tr>';

  print '<tr><td>Personne</td><td class="valeur">'.$adh->morphy.'&nbsp;</td>';
  print '<td rowspan="15" valign="top" width="50%">';
  print nl2br($adh->commentaire).'&nbsp;</td></tr>';

  print '<tr><td width="15%">Prénom</td><td class="valeur" width="35%">'.$adh->prenom.'&nbsp;</td></tr>';

  print '<tr><td>Nom</td><td class="valeur">'.$adh->nom.'&nbsp;</td></tr>';
  
  print '<tr><td>Société</td><td class="valeur">'.$adh->societe.'&nbsp;</td></tr>';
  print '<tr><td>Adresse</td><td class="valeur">'.nl2br($adh->adresse).'&nbsp;</td></tr>';
  print '<tr><td>CP Ville</td><td class="valeur">'.$adh->cp.' '.$adh->ville.'&nbsp;</td></tr>';
  print '<tr><td>Pays</td><td class="valeur">'.$adh->pays.'&nbsp;</td></tr>';
  print '<tr><td>Email</td><td class="valeur">'.$adh->email.'&nbsp;</td></tr>';
  print '<tr><td>Login</td><td class="valeur">'.$adh->login.'&nbsp;</td></tr>';
  print '<tr><td>Password</td><td class="valeur">'.$adh->pass.'&nbsp;</td></tr>';
  print '<tr><td>Date de naissance<BR>Format AAAA-MM-JJ</td><td class="valeur">'.$adh->naiss.'&nbsp;</td></tr>';
  print '<tr><td>URL Photo</td><td class="valeur">'.$adh->photo.'&nbsp;</td></tr>';
  $myattr=$adh->fetch_name_optionals();
  foreach($myattr as $key){
    print "<tr><td>$key</td><td>".$adh->array_options["options_$key"]."&nbsp;</td></tr>\n";
  }

  print "</table>\n";

  print "<hr>";

  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
  print "<input type=\"hidden\" name=\"statut\" value=\"".$adh->statut."\">";
  print "<input type=\"hidden\" name=\"public\" value=\"".$adh->public."\">";

  $htmls = new Form($db);


  print "<tr><td>Type</td><td>";
  $htmls->select_array("type",  $adht->liste_array(), $adh->typeid);
  print "</td>";

  print '<td valign="top" width="50%">Commentaires</td></tr>';

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
  print '<tr><td>Password</td><td><input type="text" name="pass" size="40" value="'.$adh->pass.'"></td></tr>';
  print '<tr><td>Date de naissance<BR>Format AAAA-MM-JJ</td><td><input type="text" name="naiss" size="40" value="'.$adh->naiss.'"></td></tr>';
  print '<tr><td>URL photo</td><td><input type="text" name="photo" size="40" value="'.$adh->photo.'"></td></tr>';
  foreach($myattr as $key){
    print "<tr><td>$key</td><td><input type=\"text\" name=\"options_$key\" size=\"40\" value=\"".$adh->array_options["options_$key"]."\"></td></tr>\n";
  }
  print '<tr><td colspan="2" align="center">';
  print '<input type="submit" name="bouton" value="Enregistrer">&nbsp;';
  print '<input type="submit" value="Annuler">';
  print '</td></tr>';
  print '</form>';
  print '</table>';
       
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
