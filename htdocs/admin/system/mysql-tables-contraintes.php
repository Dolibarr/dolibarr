<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */
require("./pre.inc.php");
include_once $dolibarr_main_document_root."/lib/${dolibarr_main_db_type}.lib.php";

if (!$user->admin)
  accessforbidden();


llxHeader();
if($dolibarr_main_db_type=="mysql")
{
print_titre("Tables Mysql - Contraintes");
$sql = "SHOW TABLE STATUS";
$base=1;
}
else
{
print_titre("Tables PostreSql - Contraintes");
$sql = "select conname,contype from  pg_constraint;";
$base=2;
}
print '<br>';
print '<table class="noborder" cellpadding="4" cellspacing="1">';
print '<tr class="liste_titre">';
if($base==1)
{
print '<td>Tables</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td>Contraintes</td>';
}
else
{
 print '<td>Contraintes</td>';
 print '<td>Type de la contrainte</td>';
}
print "</tr>\n";



$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $var=True;
  $i=0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $var=!$var;
      print "<tr $bc[$var]>";

      print '<td>'.$row[0].'</td>';
      print '<td>'.$row[1].'</td>';
      print '<td align="left">'.$row[14].'</td>';

      print '</tr>';
      $i++;
    }
}
print '</table>';

llxFooter();
?>
