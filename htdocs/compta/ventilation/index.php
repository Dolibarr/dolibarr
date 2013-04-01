<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
 *   \file       htdocs/compta/ventilation/index.php
 *   \ingroup    compta
 *   \brief      Page accueil ventilation
 */

require '../../main.inc.php';

$langs->load("compta");
$langs->load("bills");

llxHeader('','Compta - Ventilation');

print_fiche_titre("Ventilation Comptable");

//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';

$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."facturedet as fd";
$sql.= " , ".MAIN_DB_PREFIX."facture as f";
$sql.= " WHERE fd.fk_code_ventilation = 0";
$sql.= " AND f.rowid = fd.fk_facture AND f.fk_statut = 1";

$result = $db->query($sql);
if ($result)
{
  $row = $db->fetch_row($result);
  $nbfac = $row[0];

  $db->free($result);
}

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Lines").'</tr>';
print '<tr class="liste_titre"><td>'.$langs->trans("Type").'</td><td align="right">'.$langs->trans("Nb").'</td></tr>';
$var=!$var;
print "<tr ".$bc[$var].">".'<td>'.$langs->trans("Invoices").'</td><td align="right">'.$nbfac.'</td></tr>';
$var=!$var;
print "</table>\n";


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Type").'</td><td align="center">'.$langs->trans("NbOfLines").'</td><td align="center">'.$langs->trans("AccountNumber").'</td><td align="center">'.$langs->trans("TransID").'</td></tr>';

$sql = "SELECT count(*), ccg.intitule, ccg.rowid,ccg.numero FROM ".MAIN_DB_PREFIX."facturedet as fd";
$sql.= " ,".MAIN_DB_PREFIX."compta_compte_generaux as ccg";
$sql.= " WHERE fd.fk_code_ventilation = ccg.rowid";
$sql.= " GROUP BY ccg.rowid";

$resql = $db->query($sql);
if ($resql)
{
    $i = 0;
    $num = $db->num_rows($resql);
    $var=true;

    while ($i < $num)
    {

        $row = $db->fetch_row($resql);
        $var=!$var;
        print '<tr '.$bc[$var].'><td>'.$row[1].'</td><td align="center">'.$row[0].'</td>';
        print '<td align="center">'.$row[3].'</td><td align="center">'.$row[2].'</td></tr>';
        $i++;
    }
    $db->free($resql);
}
print "</table>\n";

//print '</td></tr></table>';
print '</div></div></div>';


llxFooter();

$db->close();
?>
