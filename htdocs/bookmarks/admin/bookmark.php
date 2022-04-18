<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012 Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**	    \file       htdocs/bookmarks/admin/bookmark.php
 *		\ingroup    bookmark
 *		\brief      Page to setup bookmark module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

if ($action == 'setvalue') {
	$showmenu = GETPOST('BOOKMARKS_SHOW_IN_MENU', 'alpha');
	$res = dolibarr_set_const($db, "BOOKMARKS_SHOW_IN_MENU", $showmenu, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

llxHeader();

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BookmarkSetup"), $linkback, 'title_setup');

print $langs->trans("BookmarkDesc")."<br>\n";

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';

print '<table summary="bookmarklist" class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans("NbOfBoomarkToShow").'</td><td>';
print '<input size="3" type="text" name="BOOKMARKS_SHOW_IN_MENU" value="'.$conf->global->BOOKMARKS_SHOW_IN_MENU.'">';
print '</td></tr>';
print '</table><br><div class="center"><input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></div></form>';

// End of page
llxFooter();
$db->close();
