<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

$mesg = '';

/*
 *
 *
 */
$sql = array(
	     array("Société","SELECT count(*) FROM ".MAIN_DB_PREFIX."societe"),
	     array("Contacts","SELECT count(*) FROM ".MAIN_DB_PREFIX."socpeople"),
	     array("Facture","SELECT count(*) FROM ".MAIN_DB_PREFIX."facture"),
	     array("Proposition commerciales","SELECT count(*) FROM ".MAIN_DB_PREFIX."propal")
);


print_fiche_titre('Statistiques produits et services', $mesg);
      
print '<table class="liste" width="100%">';

foreach ($sql as $key => $value)
{
  $titre = $sql[$key][0];

  if ($db->query($sql[$key][1]))
    {
      $row = $db->fetch_row(0);
      $nbhv = $row[0];
      $db->free();

      print "<tr $bc[1]>";
      print '<td width="40%">'.$titre.'</td>';
      print '<td>'.$nbhv.'</td></tr>';

    }


}

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
