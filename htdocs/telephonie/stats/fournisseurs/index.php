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

llxHeader('','Telephonie - Statistiques - Fournisseurs');
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


dol_fiche_head($head, $hselected, "Fournisseurs");

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">'."\n";

print '<tr><td width="50%" valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/duree.png" alt="Duree Moyenne"><br /><br />'."\n";

print '</td></tr>';

print '</table>';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">'."\n";

print '<tr><td valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/fourn_natio.png" alt="Communications nationales"><br /><br />'."\n";

print '</td></tr>';

print '<tr><td valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/fourn_mobil.png" alt="Communications mobiles"><br /><br />'."\n";

print '</td></tr>';
print '<tr><td valign="top">'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=communications/fourn_inter.png" alt="Communications internationales"><br /><br />'."\n";

print '</td></tr>';

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
