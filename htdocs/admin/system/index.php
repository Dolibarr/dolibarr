<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

// Choix du menu à garder fixe
// Ceci va servir pour garder le menu fixe quelquesoit les liens cliqué
// dans ce menu. Cela permet d'appeler des pages en dehors sans perdre
// le menu qui nous intéresse.
// ELDY: A finir
//session_start();
//$fix_top_menu="accueil";
//$fix_left_menu="system";
//session_register("fix_top_menu");
//session_register("fix_left_menu");

//include_once("../../allpre.inc.php");
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


llxHeader();

print_titre("Résumé des informations systèmes Dolibarr");

print "<br>\n";

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">Dolibar</td></tr>\n";
print "<tr $bc[1]><td width=\"140\">Version</td><td>" . DOL_VERSION . "</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">OS</td></tr>\n";
// Récupère la version de l'OS
ob_start(); 
phpinfo();
$chaine = ob_get_contents(); 
ob_end_clean(); 
eregi('System </td><td class="v">([^\/]*)</td>',$chaine,$reg);
print "<tr $bc[1]><td width=\"140\">Version</td><td>".$reg[1]."</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">Serveur Web</td></tr>\n";
print "<tr $bc[1]><td width=\"140\">Version</td><td>".$_SERVER["SERVER_SOFTWARE"]."</td></tr>\n";
print "<tr $bc[0]><td>document root</td><td>" . DOL_DOCUMENT_ROOT . "</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">PHP</td></tr>\n";
print "<tr $bc[1]><td width=\"140\">Version</td><td>".phpversion()."</td></tr>\n";
print "<tr $bc[0]><td>Liaison Web-PHP</td><td>".php_sapi_name()."</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">Base de données</td></tr>\n";
$sql = "SHOW VARIABLES LIKE 'version'";
$result = $db->query($sql);
if ($result)  
{
  $row = $db->fetch_row();
}
print "<tr $bc[0]><td>Version</td><td>" . $row[1] . "</td></tr>\n";
print "<tr $bc[1]><td width=\"140\">Type</td><td>" . $conf->db->type . "</td></tr>\n";
print "<tr $bc[0]><td>Host</td><td>" . $conf->db->host . "</td></tr>\n";
print "<tr $bc[1]><td>User</td><td>" . $conf->db->user . "&nbsp;</td></tr>\n";
print "<tr $bc[0]><td>Pass</td><td>" . $conf->db->pass . "&nbsp;</td></tr>\n";
print "<tr $bc[1]><td>Database name</td><td>" . $conf->db->name . "</td></tr>\n";



print '</table>';


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
