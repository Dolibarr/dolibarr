<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");
require("./project.class.php3");
require("../../propal.class.php3");

if ($HTTP_POST_VARS["action"] == 'update')
{
  $projet = new Project($db);
  $projet->id = $id;
  $projet->ref = $HTTP_POST_VARS["ref"];
  $projet->title = $HTTP_POST_VARS["title"];
  $projet->update();
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
{
  $projet = new Project($db);
  $projet->id = $id;
  $projet->delete();
  Header("Location: index.php");
}

llxHeader("","../");

if ($action == 'delete')
{

  print '<form method="post" action="'.$PHP_SELF.'?id='.$id.'">';
  print '<input type="hidden" name="action" value="confirm_delete">';
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print '<tr><td colspan="3">Supprimer le projet</td></tr>';
  
  print '<tr><td class="delete">Etes-vous sur de vouloir supprimer ce projet ?</td><td class="delete">';
  $htmls = new Form($db);
  
  $htmls->selectyesno("confirm","no");
  
  print "</td>\n";
  print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
  print '</table>';
  print "</form>\n";  
}



if ($action == 'create')
{
  print_titre("Nouveau projet");

  print '<form action="index.php?socidp='.$socidp.'" method="post">';
  ?>
  <table border="1" cellpadding="4" cellspacing="0">
  <input type="hidden" name="action" value="create">
  <tr><td>Société</td><td>
  <?PHP 
  $societe = new Societe($db);
  $societe->get_nom($socidp); 
  print '<a href="../fiche.php3?socid='.$socidp.'">'.$societe->nom.'</a>'; 

  ?>
  </td></tr>
  <?PHP
  print '<tr><td>Créateur</td><td>'.$user->fullname.'</td></tr>';
  ?>
  <tr><td>Référence</td><td><input size="10" type="text" name="ref"></td></tr>
  <tr><td>Titre</td><td><input size="30" type="text" name="title"></td></tr>
  <tr><td colspan="2"><input type="submit" value="Enregistrer"></td></tr>
  </table>
  </form>
  <?PHP

} else {
  /*
   *
   *
   *
   */

  print_titre("Fiche projet");

  $propales = array();
  $projet = new Project($db);
  $projet->fetch($id);

  $projet->societe->fetch($projet->societe->id);
  
  if ($action == 'edit')
    {  
      print '<form method="post" action="fiche.php3?id='.$id.'">';
      print '<input type="hidden" name="action" value="update">';
      print '<table border="1" cellpadding="4" cellspacing="0">';
      print '<tr><td>Société</td><td>'.$projet->societe->nom.'</td></tr>';      
      print '<tr><td>Ref</td><td><input name="ref" value="'.$projet->ref.'"></td></tr>';
      print '<tr><td>Titre</td><td><input name="title" value="'.$projet->title.'"></td></tr>';
      print '</table><input type="submit" Value="Enregistrer"></form>';
    }
  else
    {
      print '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
      print '<tr><td>Société</td><td><a href="../fiche.php3?socid='.$projet->societe->id.'">'.$projet->societe->nom.'</a></td></tr>';
      
      print '<tr><td width="20%">Réf</td><td>'.$projet->ref.'</td></tr>';
      print '<tr><td width="20%">Titre</td><td>'.$projet->title.'</td></tr>';
      print '</table>';

      $propales = $projet->get_propal_list();

      if (sizeof($propales)>0 && is_array($propales))
	{

	  print_titre('Listes des propales associées au projet');
	  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print '<TR class="liste_titre">';
	  print "<TD>Réf</TD>";
	  print '<TD>Date</TD>';
	  print '<TD align="right">Prix</TD>';
	  print '<TD align="center">Statut</TD>';
	  print '</TR>';
	  
	  for ($i = 0; $i<sizeof($propales);$i++){
	    $propale = new Propal($db);
	    $propale->fetch($propales[$i]);
	    
	    $var=!$var;
	    print "<TR $bc[$var]>";
	    print "<TD><a href=\"../propal.php3?propalid=$propale->id\">$propale->ref</a></TD>\n";
	    
	    print '<TD>'.strftime("%d %B %Y",$propale->datep).'</a></TD>';
	    
	    print '<TD align="right">'.price($propale->price).'</TD>';
	    print '<TD align="center">'.$propale->statut.'</TD>';
	    print '</TR>';
	    
	    $total = $total + $propale->price;
	  }
	  
	  print '<tr><td>'.$i.' propales</td>';
	  print '<td colspan="2" align="right"><b>Total : '.price($total).'</b></td>';
	  print '<td align="left"><b>Euros HT</b></td></tr>';
	  print "</TABLE>";
	}
    }
  
  
  print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
  
  if ($action == "edit")
    {
      print "<td align=\"center\" width=\"20%\"><a href=\"fiche.php3?id=$id\">Annuler</a></td>";
    }
  else
    {
      print "<td align=\"center\" width=\"20%\"><a href=\"fiche.php3?id=$id&action=edit\">Editer</a></td>";
    }
  
  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">-</td>';
  print '<td align="center" width="20%">-</td>';
  
  print "<td align=\"center\" width=\"20%\"><a href=\"fiche.php3?id=$id&action=delete\">Supprimer</a></td>";

  print "</tr></table>";

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
