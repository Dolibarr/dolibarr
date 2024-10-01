<?php
/* Copyright (C) 2017	Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/admin/agenda_reminder.php
 *      \ingroup    agenda
 *      \brief      Page to setup agenda reminder options
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/cron/class/cronjob.class.php';

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "agenda"));

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$param = GETPOST('param', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$scandir = GETPOST('scandir', 'alpha');
$type = 'action';


/*
 *	Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}
if ($action == 'set') {
	dolibarr_set_const($db, 'AGENDA_USE_EVENT_TYPE_DEFAULT', GETPOST('AGENDA_USE_EVENT_TYPE_DEFAULT'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_TYPE', GETPOST('AGENDA_DEFAULT_FILTER_TYPE'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_STATUS', GETPOST('AGENDA_DEFAULT_FILTER_STATUS'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_VIEW', GETPOST('AGENDA_DEFAULT_VIEW'), 'chaine', 0, '', $conf->entity);
} elseif ($action == 'specimen') {  // For orders
	$modele = GETPOST('module', 'alpha');

	$commande = new CommandeFournisseur($db);
	$commande->initAsSpecimen();
	$specimenthirdparty = new Societe($db);
	$specimenthirdparty->initAsSpecimen();
	$commande->thirdparty = $specimenthirdparty;

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/action/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db, $commande);
		'@phan-var-force pdf_standard_actions $module';

		if ($module->write_file($commande, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=action&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmodel') {
	// Activate a model
	//print "sssd".$value;
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if ($conf->global->ACTION_EVENT_ADDON_PDF == "$value") {
			dolibarr_del_const($db, 'ACTION_EVENT_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "ACTION_EVENT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that has been read in front of the new set
		// is therefore passed through a variable to have a coherent display
		$conf->global->ACTION_EVENT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}


/**
 * View
 */

$formactions = new FormActions($db);
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-agenda_reminder');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"), $linkback, 'title_setup');


$head = agenda_prepare_head();

print dol_get_fiche_head($head, 'reminders', $langs->trans("Agenda"), -1, 'action');

print '<form action="'.$_SERVER["PHP_SELF"].'" name="agenda">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder allwidth">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";

// AGENDA REMINDER BROWSER
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans('AGENDA_REMINDER_BROWSER').'</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right nowraponall">'."\n";

if (!getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
	if (!isHTTPS()) {
		$langs->load("errors");
		print img_warning($langs->trans("WarningAvailableOnlyForHTTPSServers"), '', 'valignmiddle size15x').' ';
	}
	print '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_BROWSER&token='.newToken().'">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
	print '</td></tr>'."\n";
} else {
	if (!isHTTPS()) {
		$langs->load("errors");
		print img_warning($langs->trans("WarningAvailableOnlyForHTTPSServers"), '', 'valignmiddle size15x').' ';
	}
	print '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_BROWSER&token='.newToken().'">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
	print '</td></tr>'."\n";

	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans('AGENDA_REMINDER_BROWSER_SOUND').'</td>'."\n";
	print '<td class="center">&nbsp;</td>'."\n";
	print '<td class="right">'."\n";

	if (!getDolGlobalString('AGENDA_REMINDER_BROWSER_SOUND')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_BROWSER_SOUND&token='.newToken().'">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_BROWSER_SOUND&token='.newToken().'">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
	}

	print '</td></tr>'."\n";
}

$job = new Cronjob($db);
$job->fetch(0, 'ActionComm', 'sendEmailsReminder');

// AGENDA REMINDER EMAIL
print '<tr class="oddeven">'."\n";
print '<td>'.$langs->trans('AGENDA_REMINDER_EMAIL', $langs->transnoentities("Module2300Name"));
if (isModEnabled('cron')) {
	if (getDolGlobalString('AGENDA_REMINDER_EMAIL')) {
		if ($job->id > 0) {
			if ($job->status == $job::STATUS_ENABLED) {
				print '<br><span class="opacitymedium">'.$langs->trans("AGENDA_REMINDER_EMAIL_NOTE", $langs->transnoentitiesnoconv("sendEmailsReminder")).'</span>';
			}
		}
	}
}
print '</td>'."\n";
print '<td class="center">&nbsp;</td>'."\n";
print '<td class="right nowraponall">'."\n";

if (!isModEnabled('cron')) {
	print '<span class="opacitymedium">'.$langs->trans("WarningModuleNotActive", $langs->transnoentitiesnoconv("Module2300Name")).'</span>';
} else {
	if (!getDolGlobalString('AGENDA_REMINDER_EMAIL')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_REMINDER_EMAIL&token='.newToken().'">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
	} else {
		// Get the max frequency of reminder
		if ($job->id > 0) {
			if ($job->status != $job::STATUS_ENABLED) {
				$langs->load("cron");
				print '<span class="opacitymedium warning">'.$langs->trans("JobXMustBeEnabled", $langs->transnoentitiesnoconv("sendEmailsReminder")).'</span>';
			} else {
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_REMINDER_EMAIL&token='.newToken().'">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
			}
		} else {
			$langs->load("cron");
			print '<span class="opacitymedium warning">'.$langs->trans("JobNotFound", $langs->transnoentitiesnoconv("sendEmailsReminder")).'</span>';
		}
	}
}
print '</td></tr>'."\n";

print '</table>';

print dol_get_fiche_end();

//print '<div class="center"><input class="button button-save" type="submit" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'"></div>';

print '</form>';

print "<br>";

// End of page
llxFooter();
$db->close();
