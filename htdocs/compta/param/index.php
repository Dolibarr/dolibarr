<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
        \file       htdocs/compta/index.php
        \ingroup    compta
		\brief      Page acceuil zone comptabilité
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('banque');

$langs->load("compta");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader("","Accueil Compta");

/*
 * Affichage page
 *
 */
print_titre("Paramétrage comptabilité");

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

/*
 * Zone recherche facture
 */
print '<form method="post" action="facture.php">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">Rechercher une facture</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").' : <input type="text" name="sf_ref">&nbsp;<input type="submit" value="'.$langs->trans("Search").'" class="flat"></td></tr>';
print "</table></form><br>";



print '</td><td valign="top" width="70%">';



print '</td></tr>';

print '</table>';

$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
