<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */

require("./pre.inc.php3");
require("./project.class.php3");
require("../propal.class.php3");

llxHeader("","../");

$db = new Db();

if ($action == 'create') {
  print_titre("Nouveau projet");

  print '<form action="index.php3?socidp='.$socidp.'" method="post">';
  ?>
  <table border=1 cellpadding="1" cellspacing="0">
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

  print_titre("Projet");

  $propales = array();
  $projet = new Project($db);
  $projet->fetch($id);

  print '<table border=1 cellpadding="1" cellspacing="0">';
  print '<tr><td>Société</td><td></td></tr>';

  print '<tr><td>Ref</td><td>'.$projet->ref.'</td></tr>';
  print '<tr><td>Titre</td><td>'.$projet->title.'</td></tr>';
  print '</table>';
  
  $propales = $projet->get_propal_list();

  if (sizeof($propales)>0 && is_array($propales)) {

    print '<p>Listes des propales associées au projet';
    print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

    print "<TR bgcolor=\"#e0e0e0\">";
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
      print '<TD align="center">statut</TD>';
      print '</TR>';
  
      $total = $total + $propale->price;
    }

    print '<tr><td>'.$i.' propales</td>';
    print '<td colspan="2" align="right"><b>Total : '.price($total).'</b></td>';
    print '<td align="left"><b>Euros HT</b></td></tr>';
    print "</TABLE>";

    
  } else {
    print "pas de propales";
  }


}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
