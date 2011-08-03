<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005 Simon TOSSER <simon@kornog-computing.com>
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
 *
 * $Id: index.php,v 1.3 2011/07/31 22:23:30 eldy Exp $
 */

/**
        \file       htdocs/compta/ventilation/fournisseur/index.php
        \ingroup    compta
		\brief      Page accueil ventilation
		\version    $Revision: 1.3 $
*/

require('../../../main.inc.php');
$langs->load("suppliers");


llxHeader('','Compta - Ventilation');

print_titre("Ventilation Comptable");

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';



$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."facturedet";
$sql .= " WHERE fk_export_compta = 0";
$result = $db->query($sql);
if ($result)
{
  $row = $db->fetch_row($result);
  $nbfac = $row[0];

  $db->free($result);
}

$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."paiement";
$sql .= " WHERE fk_export_compta = 0";

$result = $db->query($sql);
if ($result)
{
  $row = $db->fetch_row($result);
  $nbp = $row[0];

  $db->free($result);
}

$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."facture_fourn_det";
$sql .= " WHERE fk_export_compta = 0";
$result = $db->query($sql);
if ($result)
{
  $row = $db->fetch_row($result);
  $nbfacfourn = $row[0];

  $db->free($result);
}

/*$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."paiementfourn";
$sql .= " WHERE fk_export_compta = 0";

$result = $db->query($sql);
if ($result)
{
  $row = $db->fetch_row($result);
  $nbpfourn = $row[0];

  $db->free($result);
}*/

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">Lignes a ventiler</tr>';
print '<tr class="liste_titre"><td>Type</td><td align="center">Nb</td></tr>';
print '<tr><td>Factures clients</td><td align="center">'.$nbfac.'</td></tr>';
print '<tr><td>Paiements clients</td><td align="center">'.$nbp.'</td></tr>';
print '<tr><td>Factures fournisseurs</td><td align="center">'.$nbfacfourn.'</td></tr>';
//print '<tr><td>Paiements fournisseurs</td><td align="center">'.$nbpfourn.'</td></tr>';
print "</table>\n";

print '</td><td valign="top">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>Type</td><td align="center">Nb de lignes</td></tr>';

$sql = "SELECT count(*), ccg.intitule FROM ".MAIN_DB_PREFIX."facturedet as fd";
$sql .= " ,".MAIN_DB_PREFIX."compta_compte_generaux as ccg";
$sql .= " WHERE fd.fk_code_ventilation = ccg.rowid";
$sql .= " GROUP BY ccg.rowid";

$resql = $db->query($sql);
if ($resql)
{
  $i = 0;
  $num = $db->num_rows($resql);

  while ($i < $num)
    {

      $row = $db->fetch_row($resql);

      print '<tr><td>'.$row[1].'</td><td align="center">'.$row[0].'</td></tr>';
      $i++;
    }
  $db->free($resql);
}
print "</table>\n";

print '</td></tr></table>';

llxFooter("<em>Derni&egrave;re modification $Date: 2011/07/31 22:23:30 $ r&eacute;vision $Revision: 1.3 $</em>");

?>
