<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader('','Telephonie - Statistiques');

/*
 *
 */
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="50%" valign="top">';

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/clients.hebdomadaire.png" alt="Nouveaux clients par semaines" title="Nouveaux clients par semaine"><br /><br />'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=commercials/clientsmoyenne.hebdomadaire.png" alt="Nouveaux clients par semaines" title="Nouveaux clients par semaine"><br /><br />'."\n";

print '</td>';

print '</td><td valign="top" width="50%" rowspan="3">';

print '<img src="'.DOL_URL_ROOT.'/showgraph.php?graph='.DOL_DATA_ROOT.'/graph/telephonie/commercials/clients.mensuel.png" alt="Nouveaux clients par mois" title="Nouveaux clients par mois"><br /><br />'."\n";

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=lignes/commandes.mensuels.png" alt="Commandes de ligne par mois" title="Lignes Actives"><br /><br />'."\n";

print '</td></tr>';

print '</table>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
