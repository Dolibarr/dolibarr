<?PHP
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

$mod = new modPrelevement($db);
$dir = $mod->data_directory;

llxHeader();

print_titre($langs->trans("Bons de prélèvements"));

print '<br>';
print '<table class="noborder" cellpadding="3" cellspacing="0">';
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

llxFooter();
?>
