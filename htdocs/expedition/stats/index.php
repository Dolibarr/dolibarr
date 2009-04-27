<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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

/**
	    \file       htdocs/expedition/stats/index.php
        \ingroup    expedition
		\brief      Page des stats expeditions
		\version    $Id$
*/

require("./pre.inc.php");
require("../expedition.class.php");

$langs->load("sendings");


llxHeader();

print_fiche_titre($langs->trans("StatisticsOfSendings"), $mesg);
      
print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("Year").'</td>';
print '<td width="40%" align="center">'.$langs->trans("NbOfSendings").'</td></tr>';

$sql = "SELECT count(*), date_format(e.date_expedition,'%Y') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
$sql.= ", ".MAIN_DB_PREFIX."societe as s ";
$sql.= " WHERE e.fk_statut > 0";
$sql.= " AND e.fk_soc = s.rowid";
$sql.= " s.entity = ".$conf->entity;
$sql.= " GROUP BY dm DESC ";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $nbproduct = $row[0];
      $year = $row[1];
      print "<tr>";
      print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td><td align="center">'.$nbproduct.'</td></tr>';
      $i++;
    }
}
$db->free();

print '</table>';
print '<br>';
print '<i>'.$langs->trans("StatsOnShipmentsOnlyValidated").'</i>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
