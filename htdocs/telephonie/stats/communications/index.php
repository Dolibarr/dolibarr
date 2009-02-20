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

llxHeader('','Telephonie - Statistiques');
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/index.php';
$head[$h][1] = "Global";
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/lastmonth.php';
$head[$h][1] = "Durée";
$h++;
$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/destmonth.php';
$head[$h][1] = "Destinations";
$h++;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/communications/analyse.php';
$head[$h][1] = "Analyse";
$h++;


dol_fiche_head($head, $hselected, "Communications");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">'."\n";

print '<tr><td width="50%" valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/duree.png" alt="Nb Minutes"><br /><br />'."\n";

print '</td><td valign="top" width="50%">'."\n";

_legend($db, "communications.duree");

print '</td></tr>';
print '<tr><td width="50%" valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/duree_loc.png" alt="Communications locales"><br /><br />'."\n";

print '</td><td valign="top" width="50%">'."\n";

print '</td></tr><tr><td width="50%" valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/duree_mob.png" alt="Communications Mobiles"><br /><br />'."\n";

print '</td><td valign="top" width="50%">'."\n";

_legend($db, "communications.duree_mobiles");
print '</td></tr>';

print '<tr><td width="50%" valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/duree_inter.png" alt="Duree Moyenne vers Mobiles"><br /><br />'."\n";

print '</td><td valign="top" width="50%">'."\n";

print '</td></tr>';



print '</table>';

$db->close();


function _legend($db, $graph)
{
  print '<table>';
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
      $num = $db->num_rows($resql);
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  $var = !$var;
	  print "<tr $bc[$var]>";
	  print '<td>'.$row[0].'</td><td align="right">'.ceil($row[1]).'</td></tr>';
	  
	  $i++;
	}
    }
  print '</table>';
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
