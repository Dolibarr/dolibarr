<?PHP
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


llxHeader();

print_titre("Serveur Web");

print "<br>\n";

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print "<tr class=\"liste_titre\"><td>".$langs->trans("Parameter")."</td><td>".$langs->trans("Value")."</td></tr>\n";
print "<tr $bc[1]><td width=\"140\">Version</td><td>".$_SERVER["SERVER_SOFTWARE"]."</td></tr>\n";
print "<tr $bc[0]><td>Nom du serveur virtuel</td><td>" . $_SERVER["SERVER_NAME"] . "</td></tr>\n";
print "<tr $bc[1]><td width=\"140\">IP</td><td>".$_SERVER["SERVER_ADDR"]."</td></tr>\n";
print "<tr $bc[0]><td>Port</td><td>" . $_SERVER["SERVER_PORT"] . "</td></tr>\n";
print "<tr $bc[1]><td width=\"140\">Racine du serveur</td><td>".$_SERVER["DOCUMENT_ROOT"]."</td></tr>\n";
print '</table>';

llxFooter();
?>
