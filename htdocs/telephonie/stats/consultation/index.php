<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

if (!$user->rights->telephonie->lire) accessforbidden();
if (!$user->rights->telephonie->stats->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques - Consultations');

/*
 *
 *
 */

include "./onglets.php";
dol_fiche_head($head, $hselected, "Consultations");

print '<br /><table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="50%">';

$sql = "SELECT u.name, u.firstname, count(distinct(sc.fk_soc)) as dam";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= ",".MAIN_DB_PREFIX."societe_consult as sc";
$sql .= " WHERE sc.fk_user = u.rowid";
$sql .= " GROUP BY u.rowid";
$sql .= " ORDER BY dam DESC";
$resql = $db->query($sql);
print '<table class="border" width="100%">';
print '<tr class="liste_titre"><td width="70%">Utilisateur</td><td width="30%" align="center">Fiches client</td></tr>';
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      print '<tr><td>'.$row[1].' '.$row[0].'</td>';
      print '<td align="center">'.$row[2].'</td></tr>';
    }
}
print '</table><br />';

$sql = "SELECT u.name, u.firstname, count(distinct(sc.fk_contrat)) as dam";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= ",".MAIN_DB_PREFIX."telephonie_contrat_consult as sc";
$sql .= " WHERE sc.fk_user = u.rowid";
$sql .= " GROUP BY u.rowid";
$sql .= " ORDER BY dam DESC";
$resql = $db->query($sql);
print '<table class="border" width="100%">';
print '<tr class="liste_titre"><td width="70%">Utilisateur</td><td width="30%" align="center">Fiches contrat</td></tr>';
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      print '<tr><td>'.$row[1].' '.$row[0].'</td>';
      print '<td align="center">'.$row[2].'</td></tr>';
    }
}
print '</table><br />';

print '</td><td valign="top" width="50%">';



/*
 *
 *
 */

print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
