<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php3");

llxHeader();
print '<table border="1" cellpadding="3" cellspacing="0">';

print '<tr><td colspan="2">Database</td></tr>';

print '<tr><td>host</td><td>' . $conf->db->host . '</td></tr>';
print '<tr><td>user</td><td>' . $conf->db->user . '</td></tr>';
print '<tr><td>pass</td><td>' . $conf->db->pass . '</td></tr>';
print '<tr><td>Database name</td><td>' . $conf->db->name . '</td></tr>';

print '</table>';



$db = new Db();

llxFooter();
?>
