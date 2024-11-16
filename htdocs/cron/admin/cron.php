<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       cron/admin/cron.php
 *		\ingroup    cron
 */

// Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/cron.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'cron'));

if (!$user->admin) {
	accessforbidden();
}

$actionsave = GETPOST("save", 'alphanohtml');

// Save parameters
if (!empty($actionsave)) {
	$i = 0;

	$db->begin();

	$i += dolibarr_set_const($db, 'CRON_KEY', GETPOST("CRON_KEY"), 'chaine', 0, '', 0);

	if ($i >= 1) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 *	View
 */

$help_url = '';
llxHeader('', '', $help_url);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CronSetup"), $linkback, 'title_setup');

// Configuration header
$head = cronadmin_prepare_head();

print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'setup', $langs->trans("Module2300Name"), -1, 'cron');

print '<span class="opacitymedium">'.$langs->trans('CronInfo').'</span><br>';

print "<br>\n";

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td></td>";
print "</tr>";

// Security key for cron (if CRON_DISABLE_KEY_CHANGE is 1: modification is not allowed, -1: no button refresh)
print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("KeyForCronAccess").'</td>';
$disabled = '';
if (getDolGlobalInt('CRON_DISABLE_KEY_CHANGE') > 0) {
	$disabled = ' disabled="disabled"';
}
print '<td>';
if (getDolGlobalString('CRON_DISABLE_KEY_CHANGE') != 1) {
	print '<input type="text" class="flat minwidth300 widthcentpercentminusx"'.$disabled.' id="CRON_KEY" name="CRON_KEY" value="'.(GETPOST('CRON_KEY') ? GETPOST('CRON_KEY') : getDolGlobalString('CRON_KEY')).'">';
	if (getDolGlobalString('CRON_DISABLE_KEY_CHANGE') == 0) {
		if (!empty($conf->use_javascript_ajax)) {
			print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
		}
	} elseif (getDolGlobalString('CRON_DISABLE_KEY_CHANGE') == -1) {
		$langs->load("errors");
		print '&nbsp;'.img_picto($langs->trans("WarningChangingThisMayBreakStopTaskScheduler"), 'info');
	}
} else {
	print getDolGlobalString('CRON_KEY');
	print '<input type="hidden" id="CRON_KEY" name="CRON_KEY" value="'.(GETPOST('CRON_KEY') ? GETPOST('CRON_KEY') : getDolGlobalString('CRON_KEY')).'">';
}
print '</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

if (!getDolGlobalString('CRON_DISABLE_KEY_CHANGE')) {
	print $form->buttonsSaveCancel("Save", '');
}

print '</form>';


print '<br><br><br>';

//print $langs->trans("UseMenuModuleToolsToAddCronJobs", dol_buildpath('/cron/list.php?leftmenu=admintools', 1)).'<br>';
if (getDolGlobalString('CRON_WARNING_DELAY_HOURS')) {
	print info_admin($langs->trans("WarningCronDelayed", getDolGlobalString('CRON_WARNING_DELAY_HOURS'))).'<br>';
}

print '<br>';

dol_print_cron_urls();


print '<br>';

$constname = 'CRON_KEY';

// Add button to autosuggest a key
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
print dolJSToSetRandomPassword($constname);

llxFooter();
$db->close();
