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

/*!
        \file       htdocs/compta/bank/categ.php
        \ingroup    compta
		\brief      Page ajout de catégories banaires
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('compta');

if (!$user->admin && !$user->rights->compta->bank)
  accessforbidden();

llxHeader();


/*
 * Actions ajout catégorie
 */
if ($_POST["action"] == 'add')
{
    if ($_POST["label"]) {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_categ (label) VALUES ('".$_POST["label"]."')";
      $result = $db->query($sql);
    
      if (!$result)
        {
          dolibarr_print_error($db);
        }
    }
}


/*
 * Affichage liste des catégories
 */

print_titre($langs->trans("Categories"));
print '<form method="post" action="categ.php">';
print "<input type=\"hidden\" name=\"action\" value=\"add\">";
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref").'</td><td colspan="2">'.$langs->trans("Label").'</td>';
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
      $objp = $db->fetch_object($result);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td>$objp->rowid</td>";
      print "<td colspan=\"2\">$objp->label</td>";
      print "</tr>";
      $i++;
    }
  $db->free();
}

/*
 * Affichage ligne ajout de catégorie
 */
$var=!$var;
print "<tr $bc[$var]>";
print "<td>&nbsp;</td><td><input name=\"label\" type=\"text\" size=45></td>";
print "<td align=\"center\"><input type=\"submit\" value=\"".$langs->trans("Add")."\"</td></tr>";
print "</table></form>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
