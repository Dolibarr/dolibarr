<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!
	    \file       htdocs/admin/modules.php
        \brief      Page de configuration et activation des modules
		\version    $Revision$
*/

require("./pre.inc.php");

require_once DOL_DOCUMENT_ROOT."/includes/modules/modPrelevement.class.php";

$user->getrights('banque');


llxHeader();

print_titre($langs->trans("Bons de prélèvements"));

print '<br>';
print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Date").'</td>';
print '<td>&nbsp;</td>';
print "</tr>\n";


$dir = $conf->prelevement->dir_output;
$handle=opendir($dir."/bon");

while (($file = readdir($handle))!==false)
{
    $relativepath="/bon/".$file;
    if (is_readable($dir."/".$relativepath) && is_file($dir."/".$relativepath))
    {
      print '<tr><td><a href="'.DOL_URL_ROOT.'/document.php?modulepart=prelevement&file='.urlencode($relativepath).'&amp;type=text/plain">'.$file.'</a><td>';
      print '</tr>';
    }
}


print "</table>";

llxFooter();
?>
