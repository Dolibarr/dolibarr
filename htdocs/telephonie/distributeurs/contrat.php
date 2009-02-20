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

if ($user->distributeur_id && $user->responsable_distributeur_id == 0)
{
  accessforbidden();
}

llxHeader('','Telephonie - Statistiques - Distributeurs');

/*
 *
 *
 *
 */

$did = $_GET["id"];

if ($user->distributeur_id)
{
  $did = $user->distributeur_id;
}

$h = 0;

  $distri = new DistributeurTelephonie($db);
  $distri->fetch($did);

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/contrat.php?id='.$distri->id;
  $head[$h][1] = "Contrat";
  $hselected = $h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/remuneration.php?id='.$distri->id;
  $head[$h][1] = "Rémunérations";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/po.php?id='.$distri->id;
  $head[$h][1] = "Prises d'ordre";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/stats.php?id='.$distri->id;
  $head[$h][1] = "Statistiques";
  $h++;


dol_fiche_head($head, $hselected, "Distributeurs");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="50%" valign="top">';

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print '<td>Distributeur</td><td>Durée avan.</td><td>% Avance</td><td>% Prélèv</td><td>% Autre</td></tr>';

$sql = "SELECT d.nom, d.rowid, d.avance_pourcent, d.rem_pour_prev, d.rem_pour_autr, d.avance_duree";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur as d";
$sql .= " WHERE d.rowid = ".$did;
$sql .= " ORDER BY d.rowid ASC";

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

      print '<td><a href="distributeur.php?id='.$row[1].'">'.$row[0].'</a></td>';
      print '<td>'.$row[5].'</td>';
      print '<td>'.$row[2].'</td>';
      print '<td>'.$row[3].'</td>';
      print '<td>'.$row[4].'</td>';
      print '</tr>';
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
print '</table></div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
