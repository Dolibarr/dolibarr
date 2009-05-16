<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */
 
/**	    \file       htdocs/admin/energie.php
	    \ingroup    energie
	    \brief      Page d'administration/configuration du module de gestion de l'energie
	    \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
	dolibarr_set_const($db, "JPGRAPH_DIR",$_POST["url"],'chaine',0,'',$conf->entity);
}


/*
 *
 */
llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("Energy"),$linkback,'setup');

print '<br>';
print '<form method="post" action="energie.php">';
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table class="border">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("NewValue").'</td><td>'.$langs->trans("CurrentValue").'</td>';
print "</tr>\n";
print '<tr><td>';
print $langs->trans("Emplacement de la librairie JpGraph").'</td><td>';
print '<input size="45" type="text" name="url" value="'.$conf->global->JPGRAPH_DIR.'">';
print '</td><td>';
print $conf->global->JPGRAPH_DIR;
print '</td></tr>';

print '<tr><td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

$db->close();

llxFooter();
?>
