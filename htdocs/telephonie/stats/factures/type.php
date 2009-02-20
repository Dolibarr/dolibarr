<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];

if (!$user->rights->telephonie->lire)
  accessforbidden();

llxHeader('','Telephonie - Ligne');

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/index.php';
$head[$h][1] = "Global";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/marge.php';
$head[$h][1] = "Marge";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/type.php';
$head[$h][1] = "Méthode de paiement";
$hselected = $h;
$h++;

//$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/lastmonth.php';
//$head[$h][1] = "3 derniers mois";
//$h++;

dol_fiche_head($head, $hselected, "Satistiques Factures");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="50%">';

print '<img src="'.DOL_URL_ROOT.'/showgraph.php?graph='.DOL_DATA_ROOT.'/graph/telephonie/factures/ca_mensuel_preleve.png" alt="ca_mensuel">';

print '</td><td align="left" valign="top">';

_legend($db, "Prélèvement","factures.ca_mensuel_preleve");
print '</td><td align="left" valign="top">';
_legend($db, "Autres","factures.ca_mensuel_autre");

print '</td></tr>';



print '</table>';

$db->close();


function _legend($db, $legend, $graph)
{
  global $bc;
  print '<table class="noborder" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td colspan="2">'.$legend.'</td></tr>';
  $sql = "SELECT legend, valeur";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_stats";
  $sql .= " WHERE graph = '".$graph."'";
  $sql .= " ORDER BY ord DESC";
  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  $var = !$var;
	  print "<tr $bc[$var]>";
	  print '<td>mois '.$row[0].'</td><td align="right">'.ceil($row[1]).' euros HT</td></tr>';
	  
	  $i++;
	}
    }
  print '</table>';
}






llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
