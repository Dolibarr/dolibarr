<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$user->getrights('compta');

if (!$user->admin && !$user->rights->compta->bank)
  accessforbidden();

llxHeader();

if ($action == 'add')
{
  if ($credit > 0)
    {
      $amount = $credit ;
    }
  else 
    {
      $amount = - $debit ;
    }

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_categ (label) VALUES ('$label')";
  $result = $db->query($sql);

  if (!$result)
    {
      print $db->error();
      print "<p>$sql";
    }
}

print_titre("Catégories");
print '<form method="post" action="categ.php">';
print "<input type=\"hidden\" name=\"action\" value=\"add\">";
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
print '<tr class="liste_titre">';
print '<td>Num</td><td colspan="2">'.$langs->trans("Description").'</td>';
print "</tr>\n";

$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ ORDER BY label";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0; $total = 0;
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td>$objp->rowid</td>";
      print "<td colspan=\"2\">$objp->label</td>";
      print "</tr>";
      $i++;
    }
  $db->free();
}
print "<tr>";
print "<td>&nbsp;</td><td><input name=\"label\" type=\"text\" size=45></td>";
print "<td align=\"center\"><input type=\"submit\" value=\"".$langs->trans("Add")."\"</td></tr>";
print "</table></form>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
