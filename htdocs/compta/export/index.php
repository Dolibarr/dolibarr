<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

print "Export Comptable";

print '<br><a href="index.php?action=export">Export</a><br>';

if ($_GET["action"] == 'export')
{
  include_once DOL_DOCUMENT_ROOT.'/compta/export/modules/compta.export.class.php';

  $exc = new ComptaExport($db, $user, 'Poivre');
  $exc->Export();

  print $exc->error_message;
}


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



llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
