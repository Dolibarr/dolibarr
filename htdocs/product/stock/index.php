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

/*
 *
 *
 */

llxHeader("","","Stocks");

print_titre("Stocks");

print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';
print '<tr><td valign="top" width="30%">';

$sql = "SELECT e.label, e.rowid FROM llx_entrepot as e";
$sql .= " ORDER BY e.label DESC ";
$sql .= $db->plimit(15 ,0);
$result = $db->query($sql) ;

if ($result)
{
  $num = $db->num_rows();

  $i = 0;
  
  if ($num > 0)
    {
      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

      print '<tr class="liste_titre"><td colspan="2">Entrepôts</td></tr>';
    
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD><a href=\"fiche.php?id=$objp->rowid\">$objp->label</a></TD>\n";
	  print "</tr>\n";
	  $i++;
	}
      $db->free();

      print "</table>";
    }
}
else
{
  print $db->error() . "<br>" .$sql;
}

print '</td><td valign="top" width="70%">';


print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
