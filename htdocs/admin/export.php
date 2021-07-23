<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand			<philippe.grand@atoo-net.com>
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

/**
 *	\file       htdocs/admin/export.php
 *	\ingroup    export
 *	\brief      config Page module Export
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'exports', 'other'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'save') {
	dolibarr_set_const($db, 'EXPORT_CSV_SEPARATOR_TO_USE', GETPOST('EXPORT_CSV_SEPARATOR_TO_USE', 'alphanohtml'));
}


/*
 * View
 */

$form = new Form($db);

$page_name = "ExportSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback);

//$head = export_admin_prepare_head();
$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/admin/export.php';
$head[$h][1] = $langs->trans("Setup");
$head[$h][2] = 'setup';
$h++;

print dol_get_fiche_head($head, 'setup', $langs->trans("ExportsArea"), -1, "technic");

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="center" width="100"></td>'."\n";
print '</tr>';

/* No more need for this, you can set that a profile is public when saving it.
print '<tr class="oddeven">';
print '<td>'.$langs->trans("EXPORTS_SHARE_MODELS").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="center" width="100">';
print ajax_constantonoff('EXPORTS_SHARE_MODELS');
print '</td></tr>';
*/

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExportCsvSeparator").'</td>';
print '<td width="60" align="center"><input class="flat width50" maxlength="3" type="text" name="EXPORT_CSV_SEPARATOR_TO_USE" value="'.(empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE) ? ',' : $conf->global->EXPORT_CSV_SEPARATOR_TO_USE).'"></td>';
print '<td class="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
print '</tr>';

print '</table>';

print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
