<?PHP
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader('','Telephonie - Ligne');

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$year = strftime("%Y",time());
if (strftime("%m",time()) == 1)
{
  $year = $year -1;
}
if ($_GET["year"] > 0)
{
  $year = $_GET["year"];
}

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/index.php';
$head[$h][1] = "Global";
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/marge.php';
$head[$h][1] = "Marge";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/type.php';
$head[$h][1] = "Méthode de paiement";
$h++;

dol_fiche_head($head, $hselected, "Satistiques Factures");
stat_year_bar($year);

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="50%">';

print '<img src="'.DOL_URL_ROOT.'/showgraph.php?graph='.DOL_DATA_ROOT.'/graph/telephonie/factures/ca_mensuel.'.$year.'.png" alt="ca_mensuel">';

print '</td><td align="left" valign="top">';
_legend($db, "factures.ca_mensuel", "%11.2f");

print "</td></tr>\n";
print '<tr><td valign="top" width="50%">';

print '<img src="'.DOL_URL_ROOT.'/showgraph.php?graph='.DOL_DATA_ROOT.'/graph/telephonie/factures/facture_moyenne.'.$year.'.png" alt="facture_moyenne">';

print '</td><td align="left" valign="top">';
_legend($db, "factures.facture_moyenne","%01.1f");

print "</td></tr>\n";
print '<tr><td valign="top" width="50%">';

print '<img src="'.DOL_URL_ROOT.'/showgraph.php?graph='.DOL_DATA_ROOT.'/graph/telephonie/factures/nb_facture.'.$year.'.png" alt="nb_facture '.$year.'">';

print '</td><td align="left" valign="top">';
_legend($db, "factures.nb_mensuel","%01.0f");

print "</td></tr>\n";
print "</table>\n";

$db->close();

function _legend($db, $graph, $format)
{
  print '<table class="noborder" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td colspan="2">Légende</td></tr>';
  $sql = "SELECT legend, valeur";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_stats";
  $sql .= " WHERE graph = '".$graph."'";
  $sql .= " ORDER BY ord DESC";
  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      while ($row = $db->fetch_row($resql))
	{
	  $var = !$var;
	  print "<tr $bc[$var]>";
	  print '<td>'.$row[0].'</td><td align="right">';
	  print sprintf($format ,$row[1]);
	  print '</td></tr>';
	}
    }
  print '</table>';
}


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
