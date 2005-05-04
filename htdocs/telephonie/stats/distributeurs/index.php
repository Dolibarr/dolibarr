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

llxHeader('','Telephonie - Statistiques - Distributeurs');

/*
 *
 *
 *
 */

$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/index.php';
$head[$h][1] = "Global";
$hselected = $h;
$h++;

dolibarr_fiche_head($head, $hselected, "Distributeurs");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="30%" valign="top">';

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td valign="top">Nom</td></tr>';

$sql = "SELECT rowid, nom";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur";
$sql .= " ORDER BY nom ASC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($resql);

      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td width="50%" valign="top">';
      print '<a href="distributeur.php?id='.$row[0];
      print '">'.$row[1].'</a></td></tr>';

      $i++;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print '</table><br />';

/*                */

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td colspan="3">Lignes suivies</td></tr>';
print '<tr class="liste_titre"><td width="50%" valign="top">Nom</td><td align="center">Nb Lignes</td><td>&nbsp;</td></tr>';

$sql = "SELECT count(*) as cc , c.name, c.firstname, c.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."user as c";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";
$sql .= " WHERE c.rowid = l.fk_commercial_suiv";
$sql .= " AND c.rowid = dc.fk_user";
$sql .= " AND l.statut <> 7";
$sql .= " GROUP BY c.name ORDER BY cc DESC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  $datas = array();
  $legends = array();

  while ($i < $num)
    {
      $row = $db->fetch_row($i);	

      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td width="50%" valign="top">';
      print '<a href="commercial.php?commid='.$row[3];
      print '">'.$row[2]." ". $row[1].'</a></td><td align="center">'.$row[0].'</td>';
      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php?commercial_suiv='.$row[3].'">Voir</a></td></tr>';
      $i++;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print '</table><br />';

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td colspan="3">Lignes signées</td></tr>';
print '<tr class="liste_titre"><td width="50%" valign="top">Nom</td><td align="center">Nb Lignes</td><td>&nbsp;</td></tr>';

$sql = "SELECT count(*) as cc , c.name, c.firstname, c.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."user as c";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";
$sql .= " WHERE c.rowid = l.fk_commercial_sign";
$sql .= " AND c.rowid = dc.fk_user";
$sql .= " AND l.statut <> 7";
$sql .= " GROUP BY c.name ORDER BY cc DESC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  $datas = array();
  $legends = array();

  while ($i < $num)
    {
      $row = $db->fetch_row($i);	

      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td width="50%" valign="top">';
      print '<a href="commercial.php?commid='.$row[3];
      print '">'.$row[2]." ". $row[1].'</a></td><td align="center">'.$row[0].'</td>';
      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php?commercial_sign='.$row[3].'">Voir</a></td></tr>';
      $i++;
    }
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
print '</table>';

print '</td>';

print '</td><td valign="top" width="70%">';


print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
