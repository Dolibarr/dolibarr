<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  \file       htdocs/compta/export/index.php
  \ingroup    compta
  \brief      Page accueil zone export compta
  \version    $Revision$
*/

require("./pre.inc.php");

require("./ComptaJournalPaiement.class.php");
require("./ComptaJournalVente.class.php");

if ($_GET["action"] == 'export')
{
  include_once DOL_DOCUMENT_ROOT.'/compta/export/modules/compta.export.class.php';

  $exc = new ComptaExport($db, $user, 'Poivre');
  $exc->Export();

  print $exc->error_message;

  /* Génération du journal des Paiements */

  $jp= new ComptaJournalPaiement($db);
  $jp->GeneratePdf($user, $exc->id, $exc->ref);

  /* Génération du journal des Ventes */

  $jp= new ComptaJournalVente($db);
  $jp->GeneratePdf($user, $exc->id, $exc->ref);
}

llxHeader('','Compta - Export');

print_titre("Export Comptable");

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

print '<br><a href="index.php?action=export">Nouvel Export</a><br>';

$dir = DOL_DATA_ROOT."/compta/export/";

print '<br>';
print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Date").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (is_readable($dir.$file) && is_file($dir.$file))
    {
      print '<tr><td><a href="'.DOL_URL_ROOT.'/document.php?file='.$dir.$file.'&amp;type=text/plain">'.$file.'</a><td>';

      print '</tr>';
    }
}

print "</table>";

print '</td><td valign="top">';

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

print '<table class="noborder">';
print '<tr class="liste_titre"><td>Type</td><td>Nb</td></tr>';
print '<tr><td>Factures</td><td align="right">'.$nbfac.'</td></tr>';
print '<tr><td>Paiements</td><td align="right">'.$nbp.'</td></tr>';
print "</table>\n";

print '</td></tr></table>';


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
