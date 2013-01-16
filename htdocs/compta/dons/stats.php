<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * 	    \file       htdocs/compta/dons/stats.php
 *      \ingroup    don
 *      \brief      Page des statistiques de dons
 */

require '../../main.inc.php';

$langs->load("donations");

if (!$user->rights->don->lire) accessforbidden();


/*
 * View
 */

llxHeader('',$langs->trans("Donations"),'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones');


print_fiche_titre($langs->trans("Statistics"));


$sql = "SELECT d.amount";
$sql .= " FROM ".MAIN_DB_PREFIX."don as d LEFT JOIN ".MAIN_DB_PREFIX."projet as p";
$sql .= " ON p.rowid = d.fk_don_projet";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);

    $var=true;
    $i=0;
    $total=0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);
        $total += $objp->amount;
        $i++;
    }

    print '<table class="noborder" width="50%">';

    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td align="right">'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print "<tr $bc[$var]>";
    print '<td>'.$langs->trans("DonationsNumber").'</td><td align="right">'.$num.'</td></tr>';
    $var=!$var;
    print "<tr $bc[$var]>".'<td>'.$langs->trans("AmountTotal").'</td><td align="right">'.price($total).'</td>';
    $var=!$var;
    print "<tr $bc[$var]>".'<td>'.$langs->trans("Average").'</td><td align="right">'.price($total / ($num?$num:1)).'</td>';
    print "</tr>";

    print "</table>";
}
else
{
    dol_print_error($db);
}


llxFooter();

$db->close();
?>
