<?PHP
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
 */

/*!	\file htdocs/admin/system/index.php
		\brief      Page accueil infos système
		\version    $Revision$
*/

// \todo eldy Choix du menu à garder fixe à finir.
// Ceci va servir pour garder le menu fixe quelquesoit les liens cliqués
// dans ce menu. Cela permet d'appeler des pages en dehors sans perdre
// le menu qui nous intéresse.
//session_start();
//$fix_top_menu="accueil";
//$fix_left_menu="system";
//session_register("fix_top_menu");
//session_register("fix_left_menu");

require("./pre.inc.php");
//include_once("../../allpre.inc.php");

$langs->load("admin");
$langs->load("user");


if (!$user->admin)
  accessforbidden();

llxHeader();

print_titre($langs->trans("SummarySystem"));

print "<br>\n";

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">Dolibarr</td></tr>\n";
print "<tr $bc[1]><td width=\"240\">".$langs->trans("Version")."</td><td>" . DOL_VERSION . "</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("OS")."</td></tr>\n";
// Récupère la version de l'OS
ob_start(); 
phpinfo();
$chaine = ob_get_contents(); 
ob_end_clean(); 
eregi('System </td><td class="v">([^\/]*)</td>',$chaine,$reg);
print "<tr $bc[1]><td width=\"240\">".$langs->trans("Version")."</td><td>".$reg[1]."</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("WebServer")."</td></tr>\n";
print "<tr $bc[1]><td width=\"240\">".$langs->trans("Version")."</td><td>".$_SERVER["SERVER_SOFTWARE"]."</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("DocumentRootServer")."</td><td>" . DOL_DOCUMENT_ROOT . "</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Php")."</td></tr>\n";
print "<tr $bc[1]><td width=\"240\">".$langs->trans("Version")."</td><td>".phpversion()."</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("PhpWebLink")."</td><td>".php_sapi_name()."</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Database")."</td></tr>\n";
$sql = "SHOW VARIABLES LIKE 'version'";
$result = $db->query($sql);
if ($result)  
{
  $row = $db->fetch_row();
}
print "<tr $bc[0]><td>".$langs->trans("Version")."</td><td>" . $row[1] . "</td></tr>\n";
print "<tr $bc[1]><td width=\"240\">".$langs->trans("Type")."</td><td>" . $conf->db->type . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("Host")."</td><td>" . $conf->db->host . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("User")."</td><td>" . $conf->db->user . "&nbsp;</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("Password")."</td><td>" . $conf->db->pass . "&nbsp;</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DatabaseName")."</td><td>" . $conf->db->name . "</td></tr>\n";

print '</table>';

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
