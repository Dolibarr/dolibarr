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
  $_GET["id"] = $user->id;
}

if ($user->responsable_distributeur_id > 0)
{
  if (!in_array($_GET["id"], $user->responsable_distributeur_commerciaux))
    {
      accessforbidden();
    }
}

$year = strftime("%Y",time());

llxHeader('','Telephonie - Distributeur - Commercial');

/*
 *
 */
$h = 0;

$year = strftime("%Y",time());
if (strftime("%m",time()) == 1)
{
  $year = $year -1;
}
if ($_GET["year"] > 0)
{
  $year = $_GET["year"];
}

if ($_GET["id"] && $_GET["did"])
{
  $commercial = new User($db, $_GET["id"]);
  $commercial->fetch();

  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["did"]);

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/comm/commercial.php?id='.$_GET["id"].'&amp;did='.$_GET["did"];
  $head[$h][1] = $commercial->prenom ." ". $commercial->nom;
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/comm/ca.php?id='.$commercial->id.'&amp;did='.$_GET["did"];
  $head[$h][1] = "Chiffre d'affaire";
  $hselected = $h;
  $h++;
  /*
  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/stats.php?id='.$distri->id;
  $head[$h][1] = "Statistiques";
  $h++;
  */
  dol_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="50%" valign="top">';
  
  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/'.$commercial->id.'/ca.mensuel.'.$year.'.png" alt="CA" title="CA"><br /><br />'."\n";

  print '</td><td width="50%" valign="top"><br />';

  print '</td></tr>';
  print '</table></div>';

  $db->close();
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
