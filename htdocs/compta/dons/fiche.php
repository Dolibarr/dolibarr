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

$db = new Db();

if ($action == 'add') 
{

  if ($amount > 0)
    {

      $don = new Don($db);
      
      $don->prenom     = $prenom;
      $don->nom        = $nom;  
      $don->societe    = $societe;
      $don->adresse    = $adresse;
      $don->amount     = $amount;
      $don->cp         = $cp;
      $don->ville      = $ville;
      $don->email      = $email;
      $don->date       = mktime(12, 0 , 0, $remonth, $reday, $reyear);
      $don->note       = $note;
      $don->pays       = $pays;
      $don->public     = $public;
      $don->projetid   = $projetid;
      $don->modepaiementid = $modepaiement;
      
      if ($don->create($user->id) ) 
	{	  
	  Header("Location: index.php");
	}
    }
  else 
    {
      print "Erreur";
      $action = "create";
    }

} 

if ($action == 'delete')
{
  $don = new Don($db);
  $don->delete($rowid);
  Header("Location: liste.php?statut=0");
}
if ($action == 'commentaire')
{
  $don = new Don($db);
  $don->set_commentaire($rowid,$HTTP_POST_VARS["commentaire"]);
  $action = "edit";
}
if ($action == 'valid_promesse')
{
  $don = new Don($db);
  if ($don->valid_promesse($rowid, $user->id))
    {
      Header("Location: liste.php?statut=0");
    }
}
if ($action == 'set_paye')
{
  $don = new Don($db);
  if ($don->set_paye($rowid, $modepaiement)) 
    {
      Header("Location: liste.php?statut=1");
    }
}
if ($action == 'set_encaisse')
{
  $don = new Don($db);
  if ($don->set_encaisse($rowid))
    {
      Header("Location: liste.php?statut=2");
    }
}



llxHeader();

/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche don                                                   */
/*                                                                            */
/* ************************************************************************** */


if ($action == 'create') {

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
  print_titre("Saisir un don");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<input type=\"hidden\" name=\"facid\" value=\"$facid\">";
  print "<input type=\"hidden\" name=\"facnumber\" value=\"$obj->facnumber\">";
  print "<input type=\"hidden\" name=\"socid\" value=\"$obj->idp\">";
  print "<input type=\"hidden\" name=\"societe\" value=\"$obj->nom\">";
  
  print "<tr><td>Date du don :</td><td>";
  print_date_select();
  print "</td>";
  
  print '<td rowspan="11" valign="top">Commentaires :<br>';
  print "<textarea name=\"comment\" wrap=\"soft\" cols=\"40\" rows=\"15\"></textarea></td></tr>";
  print "<tr><td>Type :</td><td>\n";
  
  $paiement = new Paiement($db);

  $paiement->select("modepaiement","crédit");

  print "</td></tr>\n";

  print "<tr><td>Projet :</td><td><select name=\"projetid\">\n";
  
  $sql = "SELECT rowid, libelle FROM llx_don_projet ORDER BY rowid";
  
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
      print $db->error();
    }
  print "</select><br>";
  print "</td></tr>\n";

  print "<tr><td>Don public :</td><td><select name=\"public\">\n";
  
  print '<option value="1">oui</option>';
  print '<option value="0">non</option>';

  print "</select><br>";
  print "</td></tr>\n";

  print '<tr><td>Prénom</td><td><input type="text" name="prenom" size="40"></td></tr>';
  print '<tr><td>Nom</td><td><input type="text" name="nom" size="40"></td></tr>';
  print '<tr><td>Societe</td><td><input type="text" name="societe" size="40"></td></tr>';
  print '<tr><td>Adresse</td><td>';
  print '<textarea name="adresse" wrap="soft" cols="40" rows="3"></textarea></td></tr>';
  print '<tr><td>CP Ville</td><td><input type="text" name="cp" size="8"> <input type="text" name="ville" size="40"></td></tr>';
  print '<tr><td>Pays</td><td><input type="text" name="pays" size="40"></td></tr>';
  print '<tr><td>Email</td><td><input type="text" name="email" size="40"></td></tr>';
  print '<tr><td>Montant</td><td><input type="text" name="amount" size="10"> euros</td></tr>';
  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche don                                                    */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0 && $action == 'edit')
{

  $don = new Don($db);
  $don->id = $rowid;
  $don->fetch($rowid);

  print_titre("Traitement du don");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print "<tr><td>Date du don :</td><td>";
  print strftime("%d %B %Y",$don->date);
  print "</td>";
  
  print '<td rowspan="11" valign="top" width="50%">Commentaires :<br>';
  print nl2br($don->commentaire).'</td></tr>';

  if ($don->statut == 1)
    {
      print "<tr><td>Type :</td><td>";
      $paiement = new Paiement($db);
      $paiement->select("modepaiement","crédit", $don->modepaiementid);
      print "</td></tr>\n";
    }

  if ($don->statut > 1)
    {
      print "<tr><td>Type :</td><td>";
      print $don->modepaiement;
      print "</td></tr>\n";
    }

  print '<tr><td>Projet :</td><td>'.$don->projet.'</td></tr>';

  print "<tr><td>Don public :</td><td>";

  print $yn[$don->public];
  print "</td></tr>\n";


  print '<tr><td>Prénom</td><td>'.$don->prenom.'&nbsp;</td></tr>';
  print '<tr><td>Nom</td><td>'.$don->nom.'&nbsp;</td></tr>';
  print '<tr><td>Société</td><td>'.$don->societe.'&nbsp;</td></tr>';
  print '<tr><td>Adresse</td><td>'.nl2br($don->adresse).'&nbsp;</td></tr>';
  print '<tr><td>CP Ville</td><td>'.$don->cp.' '.$don->ville.'&nbsp;</td></tr>';
  print '<tr><td>Pays</td><td>'.$don->pays.'&nbsp;</td></tr>';
  print '<tr><td>Email</td><td>'.$don->email.'&nbsp;</td></tr>';
  print '<tr><td>Montant</td><td>'.price($don->amount).' euros</td></tr>';

  print "</table>\n";

  
  print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
  
  /*
   * Case 1
   */

  print "<td align=\"center\" width=\"25%\">-</td>";
	
  /*
   * Case 2
   */
  
  if ($don->statut == 1 && $resteapayer > 0) 
    {
      print "<td align=\"center\" width=\"25%\">[<a href=\"paiement.php3?facid=$facid&action=create\">Emettre un paiement</a>]</td>";
    }
  elseif ($don->statut == 0)
    {
      print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?rowid=$don->id&action=valid_promesse\">Valider la promesse</a>]</td>";
    }
  else
    {
      print "<td align=\"center\" width=\"25%\">-</td>";
    }
  /*
   * Case 3
   */
  if ($don->statut == 1 && abs($resteapayer == 0) && $don->paye == 0) 
    {
      print "<td align=\"center\" width=\"25%\">";

      print '<input type="hidden" name="action" value="set_paye">';
      print '<input type="hidden" name="rowid" value="'.$don->id.'">';

      print '<input type="submit" value="Classer Payé">';

      print "</td>";
    }
  else
    {
      print "<td align=\"center\" width=\"25%\">-</td>";
    }
  
  if ($don->statut == 0) 
    {
      print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?rowid=$don->id&action=delete\">Supprimer</a>]</td>";
    }
  elseif ($don->statut == 2)
    {
      print "<td align=\"center\" width=\"25%\"><a href=\"$PHP_SELF?rowid=$don->id&action=set_encaisse\">Encaissé</a></td>";
    }
  else
    {
      print "<td align=\"center\" width=\"25%\">-</td>";
    }

  print "</tr></table></form><p>";
/* ************************************************************************** */
/*                                                                            */
/* Commentaire                                                                */
/*                                                                            */
/* ************************************************************************** */

  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<input type="hidden" name="action" value="commentaire">';
  print '<input type="hidden" name="rowid" value="'.$don->id.'">';
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  print '<tr><td align="center">Commentaires</td></tr>';
  print '<tr><td><textarea cols="60" rows="20" name="commentaire">'.$don->commentaire.'</textarea></td></tr>';
  print '<tr><td align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print '</table></form>';
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
