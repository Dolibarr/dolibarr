<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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
require(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");

if ($HTTP_POST_VARS["action"] == 'add' && $user->admin) 
{

  $adht = new AdherentType($db);
      
  $adht->libelle     = $HTTP_POST_VARS["libelle"];
  $adht->cotisation  = $HTTP_POST_VARS["cotisation"];
  $adht->commentaire = $HTTP_POST_VARS["comment"];
  $adht->mail_valid  = $HTTP_POST_VARS["mail_valid"];
  $adht->vote        = $HTTP_POST_VARS["vote"];

  if ($adht->create($user->id) ) 
    {	  
      Header("Location: type.php");
    }
}

if ($HTTP_POST_VARS["action"] == 'update' && $user->admin) 
{

  $adht = new AdherentType($db);
  $adht->id          = $rowid;
  $adht->libelle     = $HTTP_POST_VARS["libelle"];
  $adht->cotisation  = $HTTP_POST_VARS["cotisation"];
  $adht->commentaire = $HTTP_POST_VARS["comment"];
  $adht->mail_valid  = $HTTP_POST_VARS["mail_valid"];
  $adht->vote        = $HTTP_POST_VARS["vote"];

  if ($adht->update($user->id) ) 
    {	  
      Header("Location: type.php");
    }
}

if ($action == 'delete')
{
  $adh = new Adherent($db);
  $adh->delete($rowid);
  Header("Location: liste.php");
}
if ($action == 'commentaire')
{
  $don = new Don($db);
  $don->set_commentaire($rowid,$HTTP_POST_VARS["commentaire"]);
  $action = "edit";
}



llxHeader();

print_titre("Configuration");

/* ************************************************************************** */
/*                                                                            */
/*                                                                            */
/*                                                                            */
/* ************************************************************************** */

$sql = "SELECT d.rowid, d.libelle, d.cotisation, d.vote";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print "<TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"4\">";
  
  print '<TR class="liste_titre">';
  print "<td>Id</td>";
  print "<td>Libellé</td><td>Cotisation ?</td><td>Vote ?</td><td>&nbsp;</td>";
  print "</TR>\n";
  
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD>".$objp->rowid."</td>\n";
      print '<TD>'.$objp->libelle.'</TD>';
      print '<TD>'.$objp->cotisation.'</TD>';
      print '<TD>'.$objp->vote.'</TD>';
      print '<TD><a href="type.php?action=edit&rowid='.$objp->rowid.'">Editer</TD>';
      print "</tr>";
      $i++;
    }
  print "</table>";
}
else
{
  print $sql;
  print $db->error();
}


print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr class=\"barreBouton\">";

/*
 * Case 1
 */

print '<td align="center" width="25%" class=\"bouton\">[<a href="type.php?action=create">Nouveau Type</a>]</td>';

/*
 * Case 2
 */

print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>";

/*
 * Case 3
 */
print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>";

/*
 * Case 4
 */

print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>";

print "</tr></table></form><p>";



/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche don                                                   */
/*                                                                            */
/* ************************************************************************** */


if ($action == 'create') {

  /*
   * $sql = "SELECT s.nom,s.idp, f.amount, f.total, f.facnumber";
   *  $sql .= " FROM societe as s, ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.idp";
   *  $sql .= " AND f.rowid = $facid";

   *  $result = $db->query($sql);
   *  if ($result) {
   *    $num = $db->num_rows();
   *    if ($num) {
   *      $obj = $db->fetch_object( 0);
   *
   *      $total = $obj->total;
   *    }
   *  }

  */
  
  print_titre("Nouveau type");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print '<input type="hidden" name="action" value="add">';

  print '<tr><td>Libellé</td><td><input type="text" name="libelle" size="40"></td></tr>';  

  print '<tr><td>Soumis à cotisation</td><td>';

  print '<select name="cotisation"><option value="yes">oui</option>';
  print '<option value="no">non</option></select>';
  
  print '<tr><td>Droit de vote</td><td>';

  print '<select name="vote"><option value="yes">oui</option>';
  print '<option value="no">non</option></select>';

  print '<tr><td valign="top">Commentaires :</td><td>';
  print "<textarea name=\"comment\" wrap=\"soft\" cols=\"60\" rows=\"3\"></textarea></td></tr>";

  print '<tr><td valign="top">Mail d\'accueil :</td><td>';
  print "<textarea name=\"mail_valid\" wrap=\"soft\" cols=\"60\" rows=\"15\"></textarea></td></tr>";

  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche                                                        */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0 && $action == 'edit')
{

  $adht = new AdherentType($db);
  $adht->id = $rowid;
  $adht->fetch($rowid);

  print_titre("Edition de la fiche");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print '<tr><td>Libellé</td><td class="valeur">'.$adht->libelle.'&nbsp;</td></tr>';
  print '<tr><td>Soumis à cotisation</td><td class="valeur">'.$adht->cotisation.'&nbsp;</td></tr>';

  print '<tr><td>Droit de vote</td><td class="valeur">'.$adht->vote.'&nbsp;</td></tr>';

  print '<tr><td valign="top">Commentaires</td>';
  print '<td valign="top" width="75%" class="valeur">';
  print nl2br($adht->commentaire).'&nbsp;</td></tr>';

  print '<tr><td valign="top">Mail d\'accueil</td>';

  print '<td valign="top" width="75%" class="valeur">';
  print nl2br($adht->mail_valid).'&nbsp;</td></tr>';

  print "</table>\n";

  
  /*
   *
   *
   *
   */
  print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
  print '<input type="hidden" name="rowid" value="'.$rowid.'">';
  print '<input type="hidden" name="action" value="update">';
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print '<tr><td>Libellé</td><td><input type="text" name="libelle" size="40" value="'.$adht->libelle.'"></td></tr>';  

  print '<tr><td>Soumis à cotisation</td><td>';

  $htmls = new Form($db);

  $htmls->selectyesno("cotisation",$adht->cotisation);
  print '</tr>';

  print '<tr><td>Droit de vote</td><td>';
  $htmls->selectyesno("vote",$adht->vote);
  print '</tr>';

  print '<tr><td valign="top">Commentaires :</td><td>';
  print "<textarea name=\"comment\" wrap=\"soft\" cols=\"60\" rows=\"3\">".$adht->commentaire."</textarea></td></tr>";

  print '<tr><td valign="top">Mail d\'accueil :</td><td>';
  print "<textarea name=\"mail_valid\" wrap=\"soft\" cols=\"60\" rows=\"15\">".$adht->mail_valid."</textarea></td></tr>";

  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"</td></tr>';
  print '</table>';
  print "</form>";
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
