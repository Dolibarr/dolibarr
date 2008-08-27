<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
		\file 		htdocs/compta/charges.php
		\ingroup    compta
		\brief      Page liste des charges sociales
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("compta");
$langs->load("tax");


/*
 * Action ajout en bookmark
 */
if ($action == 'add_bookmark') {
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES (".$socid.", ".$db->idate(mktime()).",".$user->id.");";
  if (! $db->query($sql) ) {
    print $db->error();
  }
}

if ($action == 'del_bookmark') {
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE rowid=$bid";
  $result = $db->query($sql);
}



llxHeader();

print_titre($langs->trans("Charges"));

print '<table width="100%">';

print '<tr><td valign="top" width="30%">';


print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print "<td colspan=\"2\">Factures</td>";
print "</tr>\n";

$sql = "SELECT c.libelle as nom, sum(s.amount) as total";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql .= " WHERE s.fk_type = c.id AND s.paye = 1";
$sql .= " GROUP BY lower(c.libelle) ASC";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object();
    $var = !$var;
    print "<tr $bc[$var]>";
    print '<td>'.$obj->nom.'</td><td>'.price($obj->total).'</td>';
    print '</tr>';
    $i++;
  }
} else {
  dolibarr_print_error($db);
}


print "</table><br>";

print '</td></tr>';

print '</table>';

$db->close();
 
llxFooter('$Date$ - $Revision$');
?>
