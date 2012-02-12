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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     \file       htdocs/expedition/stats/index.php
 *     \ingroup    expedition
 *     \brief      Page des stats expeditions
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");

$langs->load("sendings");


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("StatisticsOfSendings"), $mesg);

// TODO USe code similar to commande/stats/index.php instead of this one.

print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("Year").'</td>';
print '<td width="40%" align="center">'.$langs->trans("NbOfSendings").'</td></tr>';

$sql = "SELECT count(*) as nb, date_format(date_expedition,'%Y') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition";
$sql.= " WHERE fk_statut > 0";
$sql.= " AND entity = ".$conf->entity;
$sql.= " GROUP BY dm DESC";

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {
        $row = $db->fetch_row($resql);
        $nbproduct = $row[0];
        $year = $row[1];
        print "<tr>";
        print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td><td align="center">'.$nbproduct.'</td></tr>';
        $i++;
    }
}
$db->free($resql);

print '</table>';
print '<br>';
print '<i>'.$langs->trans("StatsOnShipmentsOnlyValidated").'</i>';

llxFooter();

$db->close();
?>
