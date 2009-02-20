<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader('','Telephonie - Statistiques - Clients');


$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/index.php';
$head[$h][1] = "Global";
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/gain.php';
$head[$h][1] = "Gain";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/clients/alerte.php';
$head[$h][1] = "Alerte";
$h++;

dol_fiche_head($head, $hselected, "Clients");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="30%" valign="top">';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Statistiques</td><td valign="center">Nb</td>';
print "</tr>\n";

$sql = "SELECT distinct l.fk_client_comm ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " WHERE statut = 3";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  $nbclient = $num;

  $var=True;

  $row = $db->fetch_row(0);	

  print "<tr $bc[$var]>";
  print "<td>Nombre de clients</td>\n";
  print "<td>".$num."</td></tr>\n";

  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

$sql = "SELECT count(*) ";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " WHERE statut = 3";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;

  $var=False;

  $row = $db->fetch_row(0);	

  $nblignes = $row[0];

  print "<tr $bc[$var]>";
  print "<td>Nombre de lignes</td>\n";
  print "<td>".$row[0]."</td>\n";
  print "</tr>\n";


  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

print "<tr $bc[True]>";
print "<td>Nombre de lignes par clients</td>\n";
print "<td>".round($nblignes / $nbclient, 2)."</td>\n";
print "</tr>\n";

print "</table>";

/*
 *
 *
 */

print '</td><td>';

print '<img src="'.DOL_URL_ROOT.'/showgraph.php?graph='.DOL_DATA_ROOT.'/graph/telephonie/commercials/clients.hebdomadaire.png" alt="Nouveaux clients par semaines" title="Nouveaux clients par semaine"><br /><br />'."\n";

print '<img src="'.DOL_URL_ROOT.'/showgraph.php?graph='.DOL_DATA_ROOT.'/graph/telephonie/commercials/clients.mensuel.png" alt="Nouveaux clients par mois" title="Nouveaux clients par mois"><br /><br />'."\n";

print '</td></tr>';

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
