<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

print_titre("Configuration Dolibarr (version ".DOL_VERSION.")");

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td><td>Valeur</td><td>Action</td>';
print "</TR>\n";

print "<tr $bc[1]><td>Version</td><td>" . DOL_VERSION . '</td><td>&nbsp;</td></tr>';
print "<tr $bc[0]><td>css</td><td>" . $conf->css . '</td><td>&nbsp;</td></tr>';
print "<tr $bc[1]><td>theme</td><td>" . $conf->theme . '</td><td>&nbsp;</td></tr>';
print "<tr $bc[0]><td>document root</td><td>" . DOL_DOCUMENT_ROOT . '</td><td>&nbsp;</td></tr>';


print '<tr class="liste_titre"><td colspan="3">Database</td></tr>';
print "<tr $bc[1]><td>type</td><td>" . $conf->db->type . '</td><td>&nbsp;</td></tr>';
print "<tr $bc[0]><td>host</td><td>" . $conf->db->host . '</td><td>&nbsp;</td></tr>';
print "<tr $bc[1]><td>user</td><td>" . $conf->db->user . '&nbsp;</td><td>&nbsp;</td></tr>';
print "<tr $bc[0]><td>pass</td><td>" . $conf->db->pass . '&nbsp;</td><td>&nbsp;</td></tr>';
print "<tr $bc[1]><td>Database name</td><td>" . $conf->db->name . '</td><td>&nbsp;</td></tr>';

print '</table>';

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
