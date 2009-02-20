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

llxHeader('','Telephonie - Statistiques - Commerciaux');

/*
 *
 *
 *
 */


$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/index.php';
$head[$h][1] = "Global";
$h++;

if ($_GET["commid"])
{
  $comm = new User($db, $_GET["commid"]);
  $comm->fetch();

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/commercial.php?commid='.$comm->id;
  $head[$h][1] = $comm->fullname;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/commercialca.php?commid='.$comm->id;
  $head[$h][1] = "CA";
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/stats/commerciaux/lignes.php?commid='.$comm->id;
  $head[$h][1] = "Lignes";
  $hselected = $h;
  $h++;

  dol_fiche_head($head, $hselected, "Commerciaux");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td valign="top">';
  
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$comm->id.'/lignes.statut.png" alt="Statuts des lignes" title="Statuts des lignes"><br /><br />'."\n";
  
  print '</td></tr>';
  print '</table>';
  
  $db->close();
 
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
