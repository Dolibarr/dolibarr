<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");
require("../../don.class.php");
require("../../paiement.class.php");

if ($action == 'update') 
{

  if ($amount > 0)
    {

      $don = new Don($db);

      $don->id          = $HTTP_POST_VARS["rowid"];
      $don->prenom      = $prenom;
      $don->nom         = $nom;  
      $don->statut      = $HTTP_POST_VARS["statutid"];  
      $don->societe     = $societe;
      $don->adresse     = $adresse;
      $don->amount      = $amount;
      $don->cp          = $cp;
      $don->ville       = $ville;
      $don->email       = $email;
      $don->date        = mktime(12, 0 , 0, $remonth, $reday, $reyear);
      $don->note        = $note;
      $don->pays        = $pays;
      $don->public      = $public;
      $don->projetid    = $projetid;
      $don->commentaire = $HTTP_POST_VARS["comment"];
      $don->modepaiementid = $modepaiement;
      
      if ($don->update($user->id) ) 
	{	  
	  Header("Location: fiche.php?rowid=$don->id&action=edit");
	}
    }
  else 
    {
      print "Erreur";
      $action = "create";
    }
}


llxHeader();


if ($rowid)
{

  $don = new Don($db);
  $don->id = $rowid;
  $don->fetch($rowid);

  $sql = "SELECT s.nom,s.idp, f.amount, f.total, f.facnumber";
  $sql .= " FROM societe as s, ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object( 0);

      $total = $obj->total;
    }
  }
  print_titre("Saisir un don");
  print "<form action=\"edit.php\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
  
  print "<tr><td>Date du don</td><td>";
  print_date_select($don->date);
  print "</td>";
  
  print '<td rowspan="13" valign="top">Commentaires :<br>';
  print '<textarea name="comment" wrap="soft" cols="40" rows="15">'.$don->commentaire.'</textarea></td></tr>';

  print "<tr><td>Statut du don</td><td>";

  $listst[0] = "Promesse à valider";
  $listst[1] = "Promesse validée";
  $listst[2] = "Don payé";
  $listst[3] = "Don encaissé";


  $sel = new Form($db);
  $sel->select_array("statutid",$listst,$don->statut);

  print "</td></tr>";

  print "<tr><td>Mode de paiement</td><td>\n";
  
  $paiement = new Paiement($db);

  $paiement->select("modepaiement","", $don->modepaiementid);

  print "</td></tr>\n";

  print "<tr><td>Projet</td><td>\n";

  $prj = new ProjetDon($db);
  $listeprj = $prj->liste_array();

  $sel = new Form($db);
  $sel->select_array("projetid",$listeprj,$don->projetid);
  

  print "<br>";
  print "</td></tr>\n";

  print "<tr><td>Don public</td><td><select name=\"public\">\n";
  if ($don->public) 
    {
      print '<option value="1" SELECTED>oui</option>';
      print '<option value="0">non</option>';
    }
  else
    {
      print '<option value="1">oui</option>';
      print '<option value="0" SELECTED>non</option>';
    }
  print "</select><br>";
  print "</td></tr>\n";

  print '<tr><td>Prénom</td><td><input type="text" name="prenom" size="40" value="'.$don->prenom.'"></td></tr>';
  print '<tr><td>Nom</td><td><input type="text" name="nom" size="40" value="'.$don->nom.'"></td></tr>';
  print '<tr><td>Societe</td><td><input type="text" name="societe" size="40" value="'.$don->societe.'"></td></tr>';
  print '<tr><td>Adresse</td><td>';
  print '<textarea name="adresse" wrap="soft" cols="40" rows="3">'.$don->adresse.'</textarea></td></tr>';
  print '<tr><td>CP Ville</td><td><input type="text" name="cp" size="8" value="'.$don->cp.'"> <input type="text" name="ville" size="40" value="'.$don->ville.'"></td></tr>';
  print '<tr><td>Pays</td><td><input type="text" name="pays" size="40" value="'.$don->pays.'"></td></tr>';
  print '<tr><td>Email</td><td><input type="text" name="email" size="40" value="'.$don->email.'"></td></tr>';
  print '<tr><td>Montant</td><td><input type="text" name="amount" size="10" value="'.$don->amount.'"> euros</td></tr>';
  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</form>\n";
  print "</table>\n";
       
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
