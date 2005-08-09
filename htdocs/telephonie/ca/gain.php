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

/*
 * Sécurité accés client
 */

if (!$user->rights->telephonie->lire) accessforbidden();
if ($user->societe_id > 0) accessforbidden();

llxHeader('','Telephonie - CA par client');

/*
 *
 *
 *
 */
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="50%" valign="top">';

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="ca";

$sql = "SELECT nom, ca, gain, cout, marge";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_client_stats";
$sql .= " , " .MAIN_DB_PREFIX."societe";
$sql .= " WHERE idp = fk_client_comm";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;

  print_barre_liste("CA cumulé par client", $page, "gain.php", $urladd, $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Client</td><td align="right">Chiffre d\'affaire</td>';
  print '<td align="right">Gain</td>';

  print_liste_field_titre("Marge","gain.php","marge",'','','align="right"');
  print "<td>&nbsp;</td></tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $row = $db->fetch_row($i);	
      $var=!$var;

      $marge = $row[4];

      print "<tr $bc[$var]>";
      print "<td>".$row[0]."</td>\n";
      print '<td align="right">'.price($row[1])." HT</td>\n";
      print '<td align="right">'.price($row[2])." HT</td>\n";
      print '<td align="right">'.number_format(round($marge), 2, '.', ' ')." %</td>\n";
      if ($marge < 0)
	{
	  print '<td align="center">'.img_picto('','alerte').'</td>';
	}
      else
	{
	  print '<td>&nbsp;</td>';
	}
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

//print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=ca/gain_moyen_par_client.png" alt="Gain moyen par client"><br /><br />';

print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
