<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/admin/system/os.php
		\brief      Page des infos système de l'OS
		\version    $Revision$
*/

require("./pre.inc.php");


$langs->load("admin");

if (!$user->admin)
  accessforbidden();


llxHeader();

print_fiche_titre("OS",'','setup');

print "<br>\n";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
print "\n";

// Récupère l'OS au sens PHP
print "<tr $bc[0]><td width=\"240\">".$langs->trans("PHP_OS")."</td><td>".PHP_OS."</td></tr>\n";

// Récupère la version de l'OS
ob_start(); 
phpinfo();
$chaine = ob_get_contents(); 
ob_end_clean(); 
eregi('System </td><td class="v">([^\/]*)</td>',$chaine,$reg);
print "<tr $bc[1]><td width=\"240\">".$langs->trans("Version")."</td><td>".$reg[1]."</td></tr>\n";
print '</table>';


llxFooter('$Date$ - $Revision$');
?>
