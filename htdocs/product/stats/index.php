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

require("./pre.inc.php");
require("../../propal.class.php3");
require("../../graph.class.php");

llxHeader();

$db = new Db();
$mesg = '';

/*
 *
 *
 */
$sql = "SELECT count(*) FROM llx_product";
if ($db->query($sql))
{
  $row = $db->fetch_row(0);
  $nbproduct = $row[0];
}

print_fiche_titre('Statistiques produit', $mesg);
      
print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
print "<tr>";
print '<td width="20%">Nb de produit dans le catalogue</td>';
print '<td>'.$nbproduct.'</td></tr>';

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
