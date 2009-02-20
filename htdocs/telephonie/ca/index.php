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

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];

if (!$user->rights->telephonie->ca->lire)
  accessforbidden();

llxHeader('','Telephonie');

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

/*
 * Mode Liste
 *
 *
 *
 */

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="50%" valign="top">';

$sql = "SELECT date, sum(cout_vente), sum(gain), count(ligne)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture";
$sql .= " GROUP BY date DESC";
$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  $ligne = new LigneTel($db);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Mois</td><td align="right">Chiffre d\'affaire</td>';
  print '<td align="right">Gain</td><td align="right">Marge</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $row = $db->fetch_row($resql);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".substr($row[0],5,2)." ".substr($row[0],0,4)."</td>\n";
      print '<td align="right">'.price($row[1])." HT</td>\n";
      print '<td align="right">'.price($row[2])." HT</td>\n";
      print '<td align="right">'.number_format(round(($row[2]/$row[1])*100,2),2)." %</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  dol_print_error($db);
}

print '</td><td valign="top" width="50%">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=ca/ca.mensuel.png" alt="CA Mensuel"><br /><br />';

print "\n</td></tr>\n";
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
