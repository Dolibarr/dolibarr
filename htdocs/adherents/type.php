<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
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
require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");

if ($_POST["action"] == 'add' && $user->admin) 
{
    if ($_POST["button"] != "Annuler") {
        $adht = new AdherentType($db);
          
        $adht->libelle     = $_POST["libelle"];
        $adht->cotisation  = $_POST["cotisation"];
        $adht->commentaire = $_POST["comment"];
        $adht->mail_valid  = $_POST["mail_valid"];
        $adht->vote        = $_POST["vote"];

        if ($_POST["libelle"]) { $adht->create($user->id); }
    }
    Header("Location: type.php");
}

if ($_POST["action"] == 'update' && $user->admin) 
{
    if ($_POST["button"] != "Annuler") {
        $adht = new AdherentType($db);
        $adht->id          = $_POST["rowid"];;
        $adht->libelle     = $_POST["libelle"];
        $adht->cotisation  = $_POST["cotisation"];
        $adht->commentaire = $_POST["comment"];
        $adht->mail_valid  = $_POST["mail_valid"];
        $adht->vote        = $_POST["vote"];
        
        $adht->update($user->id);
    }	  
    Header("Location: type.php");
}

if ($_GET["action"] == 'delete')
{
  $adh = new Adherent($db);
  $adh->delete($rowid);
  Header("Location: liste.php");
}
if ($_GET["action"] == 'commentaire')
{
  $don = new Don($db);
  $don->set_commentaire($rowid,$_POST["commentaire"]);
  $action = "edit";
}



llxHeader();

print_titre("Configuration des types d'adhérents");
print '<br>';

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
  
  print "<table class=\"noborder\" cellspacing=\"0\" cellpadding=\"3\">";
  
  print '<tr class="liste_titre">';
  print "<td>Id</td>";
  print "<td>Libellé</td><td>Cotisation ?</td><td>Vote ?</td><td>&nbsp;</td>";
  print "</tr>\n";
  
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td>".$objp->rowid."</td>\n";
      print '<td>'.$objp->libelle.'</td>';
      print '<td align="center">'.$objp->cotisation.'</td>';
      print '<td align="center">'.$objp->vote.'</td>';
      print '<td><a href="type.php?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</td>';
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



    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';
    print "<a class=\"tabAction\" href=\"type.php?action=create\">Nouveau Type</a>";
    print "</div>";



/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche don                                                   */
/*                                                                            */
/* ************************************************************************** */


if ($_GET["action"] == 'create') {

  print_titre("Nouveau type");
  print '<br>';
  
  print "<form action=\"type.php\" method=\"post\">";
  print '<table cellspacing="0" class="border" width="100%" cellpadding="3">';
  
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

  print '<tr><td colspan="2" align="center"><input type="submit" name="button" value="Enregistrer"> &nbsp;';
  print '<input type="submit" name="button" value="Annuler"></td></tr>';

  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche                                                        */
/*                                                                            */
/* ************************************************************************** */
if ($_GET["rowid"] > 0 && $_GET["action"] == 'edit')
{

  $adht = new AdherentType($db);
  $adht->id = $_GET["rowid"];
  $adht->fetch($_GET["rowid"]);

  print_titre("Edition de la fiche");
  print '<br>';
  
  /*
   *
   *
   */
  print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$_GET["rowid"].'">';
  print '<input type="hidden" name="rowid" value="'.$_GET["rowid"].'">';
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

  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"> &nbsp;';
  print '<input type="submit" name="button" value="Annuler"></td></tr>';

  print '</table>';
  print "</form>";
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
