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

llxHeader();


$db = new Db();

if ($action == "create") {

  print "Nouveau service<br>";
  print "<form action=\"$PHP_SELF?rowid=$rowid\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
  print '<table border="1" cellpadding="3" cellspacing="0">';

  print '<tr><td valign="top">Référence</td>';
  print '<td><input size="12" type="text" name="label"</td></tr>';
  
  print '<tr><td valign="top">Libelle</td>';
  print '<td><input size="30" type="text" name="label"</td></tr>';
  
  print '<tr><td valign="top">Prix</td>';
  print '<td><input size="8" type="text" name="price"</td></tr>';
  
  print '<tr><td valign="top">Description</td><td>';
  print "<textarea name=\"desc\" rows=\"12\" cols=\"40\">";
  print "</textarea></td></tr>";
  print '<tr><td align="center" colspan="2"><input type="submit"></td></tr>';
  print '</form>';
  print '</table>';
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
