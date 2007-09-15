<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

require("./pre.inc.php");


/*
 * Affichage
 */
 
llxHeader();


$sql = "SELECT label ";
$sql .= " FROM ".MAIN_DB_PREFIX."domain ORDER BY label ASC";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);

  print_barre_liste($langs->trans("DomainNames"), $page, "liste.php","",$sortfield,$sortorder,"",$num);

  print "<form method=\"post\" action=\"index.php?viewall=$viewall&vline=$vline&account=$account\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<table class=\"border\" width=\"100%\">";
  print "<tr class=\"liste_titre\">";
  print '<td>Date</td><td>'.$langs->trans("Description").'</td>';
  print '<td align="right">'.$langs->trans("Amount").'</td>';
  print "<td align=\"right\">Réduction</td>";
  print "</tr>\n";
  

  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;

  $sep = 0;

  while ($i < $num) {
    $objp = $db->fetch_object($result);
    $total = $total + $objp->amount;
    $time = time();

    $var=!$var;

    print "<tr $bc[$var]>";

    print "<td>$objp->label</td>";

    print '<td><a href="http://www.'.$objp->label.'/">www.'.$objp->label.'</a></td>';

    print "</tr>";


    $i++;
  }
  $db->free();

  print "</table></form>";

} else {
  dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
