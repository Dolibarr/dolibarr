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
require("../service.class.php3");

llxHeader();

$db = new Db();

if ($action == 'add') {
  $service = new Service($db);

  $service->ref = $ref;
  $service->libelle = $label;
  $service->price = $price;
  $service->description = $desc;

  $id = $service->create($user->id);

  if ($comm_now && $id) {
    $service->start_comm($id, $user->id);
  }

}

if ($action == 'set_datedeb') {
  $service = new Service($db);
  $service->start_comm($id, $user->id, $datedeb);
}
if ($action == 'set_datefin') {
  $service = new Service($db);
  $service->stop_comm($id, $user->id, $datefin);
}

if ($action == 'create') {

  print "Nouveau service<br>";
  print '<form action="'.$PHP_SELF.'" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<table border="1" cellpadding="3" cellspacing="0">';

  print '<tr><td valign="top">Référence</td>';
  print '<td><input size="12" type="text" name="ref"</td></tr>';
  
  print '<tr><td valign="top">Libelle</td>';
  print '<td><input size="30" type="text" name="label"</td></tr>';
  
  print '<tr><td valign="top">Prix</td>';
  print '<td><input size="8" type="text" name="price"</td></tr>';
  
  print '<tr><td valign="top">Description</td><td>';
  print "<textarea name=\"desc\" rows=\"12\" cols=\"40\">";
  print "</textarea></td></tr>";

  print '<tr><td valign="top">Commercialisé</td>';
  print '<td><select name="comm_now">';
  print '<option value="1">oui';
  print '<option value="0">non';
  print '</td></tr>';

  print '<tr><td align="center" colspan="2"><input type="submit"></td></tr>';
  print '</form>';
  print '</table>';
} else {

  if ($id) {

    print "<table width=\"100%\" border=\"0\" cellspacing=\"1\">\n";
    print '<tr><td><a href="index.php3">Liste</a></td>';

    print '<td width="20%" bgcolor="#e0E0E0" align="center">[<a href="fiche.php3?action=datedeb&id='.$id.'">Date de debut</a>]</td>';
    print '<td width="20%">&nbsp;</td>';
    print '<td width="20%" bgcolor="#e0E0E0" align="center">[<a href="fiche.php3?action=datefin&id='.$id.'">Date de fin</a>]</td>';
    print '<td></td>';
    print '<td></td>';
    print '</table><br>';
    

    $service = new Service($db);
    $service->fetch($id);

    print '<table width="100%" border="1" cellpadding="3" cellspacing="0">';

    print '<tr><td valign="top">Référence</td>';
    print '<td bgcolor="#e0e0e0">'.$service->ref.'</td>';
    print '<td valign="top">Créé le</td>';
    print '<td>'.$service->tms.'</td></tr>';
    
    print '<tr><td valign="top">Libelle</td>';
    print '<td bgcolor="#e0e0e0">'.$service->libelle.'</td>';
    print '<td valign="top">Début comm</td>';
    print '<td>'.$service->debut.'</td></tr>';
  
    print '<tr><td valign="top">Prix</td>';
    print '<td>'.price($service->price).'</td>';

    print '<td valign="top">Fin comm</td>';

    if ($service->fin_epoch < time()) {
      print '<td bgcolor="#99ffff"><b>'.$service->fin.'&nbsp;</b></td></tr>';
    } else {
      print '<td>'.$service->fin.'&nbsp;</td></tr>';
    }

    print '<tr><td valign="top">Description</td><td colspan="3">';
    print "<textarea name=\"desc\" rows=\"12\" cols=\"40\">";
    print "</textarea></td></tr>";
    print '</table>';

    /*
     *
     *
     *
     */
    if ($action == 'datedeb') {
      print '<p><b>Affectation de la date de début de commercialisation</b></p>';
      print '<form action="'.$PHP_SELF.'?id='.$id.'" method="post">';
      print '<input type="hidden" name="action" value="set_datedeb">';
      print '<table width="100%" border="1" cellpadding="3" cellspacing="0">';
      print '<tr><td>Date de debut de commercialisation</td>';
      print '<td><input size="10" type="text" name="datedeb" value="'.strftime("%Y-%m-%d", time()).'"></td>';
      print '<td><input type="submit"></td></tr>';
      print '</table></form><br>';
    }
    /*
     *
     *
     *
     */
    if ($action == 'datefin') {
      print '<p><b>Affectation de la date de fin de commercialisation</b></p>';
      print '<form action="'.$PHP_SELF.'?id='.$id.'" method="post">';
      print '<input type="hidden" name="action" value="set_datefin">';
      print '<table width="100%" border="1" cellpadding="3" cellspacing="0">';
      print '<tr><td>Date de fin de commercialisation</td>';
      print '<td><input size="10" type="text" name="datefin" value="'.strftime("%Y-%m-%d", time()).'"></td>';
      print '<td><input type="submit"></td></tr>';
      print '</table></form><br>';
    }

  }

}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
