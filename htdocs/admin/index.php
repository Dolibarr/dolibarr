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

print_titre("Configuration Dolibarr");

print '<table border="1" cellpadding="3" cellspacing="0">';

print '<tr><td>css</td><td>' . $conf->css . '</td></tr>';
print '<tr><td>theme</td><td>' . $conf->theme . '</td></tr>';
print '<tr><td>document root</td><td>' . $DOCUMENT_ROOT . '</td></tr>';


print '<tr><td bgcolor="#e0e0e0" colspan="2">Database</td></tr>';
print '<tr><td>type</td><td>' . $conf->db->type . '</td></tr>';
print '<tr><td>host</td><td>' . $conf->db->host . '</td></tr>';
print '<tr><td>user</td><td>' . $conf->db->user . '&nbsp;</td></tr>';
print '<tr><td>pass</td><td>' . $conf->db->pass . '&nbsp;</td></tr>';
print '<tr><td>Database name</td><td>' . $conf->db->name . '</td></tr>';

print '<tr class="list_sep"><td bgcolor="#e0e0e0" colspan="2">Propale</td></tr>';
print '<tr><td>templates</td><td>' . $conf->propal->templatesdir . '</td></tr>';
print '<tr><td>output dir</td><td>' . $conf->propal->outputdir . '</td></tr>';
print '<tr><td>output url</td><td>' . $conf->propal->outputurl . '</td></tr>';

print '<tr class="list_sep"><td bgcolor="#e0e0e0" colspan="2">Facture</td></tr>';
print '<tr><td>templates</td><td>' . $conf->facture->templatesdir . '</td></tr>';
print '<tr><td>output dir</td><td>' . $conf->facture->outputdir . '</td></tr>';
print '<tr><td>output url</td><td>' . $conf->facture->outputurl . '</td></tr>';


if ($conf->fichinter->enabled) {
  print '<tr><td bgcolor="#e0e0e0" colspan="2">Fiche d\'intervention</td></tr>';
  print '<tr><td>templates</td><td>' . $conf->fichinter->templatesdir . '</td></tr>';
  print '<tr><td>output dir</td><td>' . $conf->fichinter->outputdir . '</td></tr>';
  print '<tr><td>output url</td><td>' . $conf->fichinter->outputurl . '</td></tr>';
}

if ($conf->don->enabled) {
  print '<tr><td bgcolor="#e0e0e0" colspan="2">Dons</td></tr>';
  print '<tr><td>Paiement en ligne</td><td>' . $conf->don->onlinepayment . '</td></tr>';
  print '<tr><td>Don minimum</td><td>' . $conf->don->minimum . '</td></tr>';
  print '<tr><td>Email Moderateurs</td><td>'.$conf->don->email_moderator.'</td></tr>';
}


print '<tr><td  bgcolor="#e0e0e0" colspan="2">Webcal</td></tr>';
print '<tr><td>type</td><td>' . $conf->webcal->db->type . '</td></tr>';
print '<tr><td>host</td><td>' . $conf->webcal->db->host . '</td></tr>';
print '<tr><td>user</td><td>' . $conf->webcal->db->user . '&nbsp;</td></tr>';
print '<tr><td>pass</td><td>' . $conf->webcal->db->pass . '&nbsp;</td></tr>';
print '<tr><td>Database name</td><td>' . $conf->webcal->db->name . '</td></tr>';

print '</table>';

llxFooter();
?>
