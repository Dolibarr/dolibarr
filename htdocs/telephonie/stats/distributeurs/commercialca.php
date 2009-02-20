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
$h++;

$year = strftime("%Y",time());
if (strftime("%m",time()) == 1)
{
  $year = $year -1;
}
if ($_GET["year"] > 0)
{
  $year = $_GET["year"];
}

if ($_GET["id"])
{
  $comm = new User($db, $_GET["id"]);
  $comm->fetch();

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/commercial.php?id='.$comm->id;
  $head[$h][1] = $comm->fullname;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/distributeurs/commercialca.php?id='.$comm->id;
  $head[$h][1] = "CA";
  $hselected = $h;
  $h++;

  dol_fiche_head($head, $hselected, "Distributeurs");
  stat_year_bar($year);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr><td valign="top">';

  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$comm->id;
  print '/gain.mensuel.'.$year.'.png" alt="Gain mensuel" title="Gain mensuel">'."\n";

  print "</td></tr>\n";
  print '<tr><td valign="top">';
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$comm->id;
  print '/ca.mensuel.'.$year.'.png" alt="Chiffre d\'affaire mensuel" title="Chiffre d\'affaire mensuel">'."\n";
  
  print '</td></tr>';
  print '</table>';
  
  $db->close();
 
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
