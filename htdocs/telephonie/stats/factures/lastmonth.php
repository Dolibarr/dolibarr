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
$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/factures/lastmonth.php';
$head[$h][1] = "3 derniers mois";
$hselected = $h;
$h++;


dol_fiche_head($head, $hselected, "Satistiques Factures");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top">';

$now = time();
$year = strftime("%Y", $now);
$month = strftime("%m", $now);

for ($i = 1 ; $i < 4 ; $i++)
{
  $month = $month - 1;

  if ($month == 0)
    {
      $year = $year - 1;
      $month = 12;
    }


  print '<img src="./gain_repart-'.$year.substr("00".$month, -2).'.png"><br /><br />';

}
print '</td><td valign="top">';

$year = strftime("%Y", $now);
$month = strftime("%m", $now);

for ($i = 1 ; $i < 4 ; $i++)
{
  $month = $month - 1;

  if ($month == 0)
    {
      $year = $year - 1;
      $month = 12;
    }


  print '<img src="./montant_repart-'.$year.substr("00".$month, -2).'.png"><br /><br />';

}

print '</td></tr>';

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
