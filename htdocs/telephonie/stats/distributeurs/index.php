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

llxHeader('','Telephonie - Statistiques - Distributeurs');

/*
 *
 *
 *
 */

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/index.php';
$head[$h][1] = "Prise d'ordre";
$hselected = $h;
$h++;
$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/resultats.php';
$head[$h][1] = "Résultats";
$h++;
dol_fiche_head($head, $hselected, "Distributeurs");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="50%" valign="top">';

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print '<td>Distributeur</td><td align="right">Prise d\'ordre</td></tr>';

$sql = "SELECT sum(p.montant), d.nom, d.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur as d";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";

$sql .= " WHERE p.fk_distributeur = d.rowid";
$sql .= " GROUP BY d.rowid";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  $total = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($i);	

      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td><a href="distributeur.php?id='.$row[2].'">'.$row[1].'</a></td>';

      print '<td align="right">'.price($row[0]).'</td></tr>';
      $i++;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print '</table><br />';


/*
 * Commerciaux
 *
 */

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print '<td>Distributeur</td><td>Commercial</td><td align="right">Prise d\'ordre</td></tr>';

$sql = "SELECT sum(p.montant), d.nom, u.firstname, u.name, u.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur as d";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat_priseordre as p";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";

$sql .= " WHERE p.fk_commercial = u.rowid";
$sql .= " AND dc.fk_user = u.rowid";
$sql .= " AND p.fk_distributeur = d.rowid";
$sql .= " GROUP BY u.rowid";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  $total = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row($i);	
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>'.$row[1].'</a></td>';
      print '<td><a href="commercial.php?id='.$row[4].'">'.$row[2]." ".$row[3].'</a></td>';
      print '<td align="right">'.price($row[0]).'</td></tr>';
      $i++;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}



print '</table><br />';


print '</td><td width="50%" valign="top">&nbsp;</td></tr>';
print '</table><br />';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
