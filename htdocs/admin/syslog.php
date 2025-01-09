<?php
/* Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/admin/syslog.php
 *	\ingroup    syslog
 *	\brief      Setup page for logs module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "other"));

$error = 0;
$action = GETPOST('action', 'aZ09');

$syslogModules = array();
$activeModules = array();

if (getDolGlobalString('SYSLOG_HANDLERS')) {
	$activeModules = json_decode($conf->global->SYSLOG_HANDLERS);
	if (!is_array($activeModules)) {
		$activeModules = array();
	}
}

$dirsyslogs = array_merge(array('/core/modules/syslog/'), $conf->modules_parts['syslog']);
foreach ($dirsyslogs as $reldir) {
	$dir = dol_buildpath($reldir, 0);
	$newdir = dol_osencode($dir);
	if (is_dir($newdir)) {
		$handle = opendir($newdir);

		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (substr($file, 0, 11) == 'mod_syslog_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $newdir.$file.'.php';

					$module = new $file();
					'@phan-var-force LogHandler $module';

					// Show modules according to features level
					if ($module->getVersion() == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
						continue;
					}
					if ($module->getVersion() == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
						continue;
					}

					$syslogModules[] = $file;
				}
			}
			closedir($handle);
		}
	}
}


/*
 * Actions
 */

// Set modes
if ($action == 'set') {
	$db->begin();

	$newActiveModules = array();
	$selectedModules = (GETPOSTISSET('SYSLOG_HANDLERS') ? GETPOST('SYSLOG_HANDLERS') : array());

	// Save options of handler
	foreach ($syslogModules as $syslogHandler) {
		if (in_array($syslogHandler, $syslogModules)) {
			$module = new $syslogHandler();
			'@phan-var-force LogHandler $module';

			if (in_array($syslogHandler, $selectedModules)) {
				$newActiveModules[] = $syslogHandler;
			}
			foreach ($module->configure() as $option) {
				if (GETPOSTISSET($option['constant'])) {
					dolibarr_del_const($db, $option['constant'], -1);
					dolibarr_set_const($db, $option['constant'], trim(GETPOST($option['constant'])), 'chaine', 0, '', 0);
				}
			}
		}
	}

	$activeModules = $newActiveModules;

	dolibarr_del_const($db, 'SYSLOG_HANDLERS', -1); // To be sure there is not a setup into another entity
	dolibarr_set_const($db, 'SYSLOG_HANDLERS', json_encode($activeModules), 'chaine', 0, '', 0);
	$error = 0;
	$errors = [];
	// Check configuration
	foreach ($activeModules as $modulename) {
		$module = new $modulename();
		'@phan-var-force LogHandler $module';
		$res = $module->checkConfiguration();
		if (!$res) {
			$error++;
			$errors = array_merge($errors, $module->errors);
		}
	}


	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages('', $errors, 'errors');
	}
}

// Set level
if ($action == 'setlevel') {
	$level = GETPOST("level");
	$res = dolibarr_set_const($db, "SYSLOG_LEVEL", $level, 'chaine', 0, '', 0);
	dol_syslog("admin/syslog: level ".$level);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		$file_saves = GETPOST("file_saves");
		$res = dolibarr_set_const($db, "SYSLOG_FILE_SAVES", $file_saves, 'chaine', 0, '', 0);
		dol_syslog("admin/syslog: file saves  ".$file_saves);

		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("SyslogSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-syslog');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SyslogSetup"), $linkback, 'title_setup');
print '<br>';

$syslogfacility = $defaultsyslogfacility = dolibarr_get_const($db, "SYSLOG_FACILITY", 0);
$syslogfile = $defaultsyslogfile = dolibarr_get_const($db, "SYSLOG_FILE", 0);

if (!$defaultsyslogfacility) {
	$defaultsyslogfacility = 'LOG_USER';
}
if (!$defaultsyslogfile) {
	$defaultsyslogfile = 'dolibarr.log';
}
$optionmc = '';
if (isModEnabled('multicompany') && $user->entity) {
	print '<div class="error">'.$langs->trans("ContactSuperAdminForChange").'</div>';
	$optionmc = 'disabled';
}


// Output mode

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';

print load_fiche_titre($langs->trans("SyslogOutput"), '', '');

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Type").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td class="center width150"><input type="submit" class="button small" '.$optionmc.' value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

foreach ($syslogModules as $moduleName) {
	$module = new $moduleName();
	'@phan-var-force LogHandler $module';

	$moduleactive = (int) $module->isActive();
	//print $moduleName." = ".$moduleactive." - ".$module->getName()." ".($moduleactive == -1)."<br>\n";
	if (($moduleactive == -1) && getDolGlobalInt('MAIN_FEATURES_LEVEL') == 0) {
		continue; // Some modules are hidden if not activable and not into debug mode (end user must not see them)
	}


	print '<tr class="oddeven">';
	print '<td class="nowraponall" width="140">';
	print '<input class="oddeven" type="checkbox" id="syslog_handler_'.$moduleName.'" name="SYSLOG_HANDLERS[]" value="'.$moduleName.'" '.(in_array($moduleName, $activeModules) ? 'checked' : '').($moduleactive <= 0 ? 'disabled' : '').'> ';
	print '<label for="syslog_handler_'.$moduleName.'">'.$module->getName().'</label>';
	if ($moduleName == 'mod_syslog_syslog') {
		if (!$module->isActive()) {
			$langs->load("errors");
			print $form->textwithpicto('', $langs->trans("ErrorPHPNeedModule", 'SysLog'));
		}
	}
	print '</td>';

	print '<td class="nowrap">';
	$setuparray = $module->configure();

	if ($setuparray) {
		foreach ($setuparray as $option) {
			$tmpoption = $option['constant'];
			$value = '';
			if (!empty($tmpoption)) {
				if (GETPOSTISSET($tmpoption)) {
					$value = GETPOST($tmpoption);
				} else {
					$value = getDolGlobalString($tmpoption);
				}
			} else {
				$value = (isset($option['default']) ? $option['default'] : '');
			}

			print '<span class="hideonsmartphone opacitymedium">'.$option['name'].': </span><input type="text" class="flat'.(empty($option['css']) ? '' : ' '.$option['css']).'" name="'.dol_escape_htmltag($option['constant']).'" value="'.$value.'"'.(isset($option['attr']) ? ' '.$option['attr'] : '').'>';
			if (!empty($option['example'])) {
				print '<br>'.$langs->trans("Example").': '.dol_escape_htmltag($option['example']);
			}

			if ($option['constant'] == 'SYSLOG_FILE' && preg_match('/^DOL_DATA_ROOT\/[^\/]*$/', $value)) {
				$filelogparam = ' &nbsp; &nbsp; <a href="'.DOL_URL_ROOT.'/document.php?modulepart=logs&file='.basename($value).'">';
				$filelogparam .= $langs->trans('Download');
				$filelogparam .= img_picto($langs->trans('Download').' '.basename($value), 'download', 'class="paddingleft"');
				$filelogparam .= '</a>';
				print $filelogparam;
			}
		}
	}
	print '</td>';

	print '<td class="center">';
	if ($module->getInfo()) {
		print $form->textwithpicto('', $module->getInfo(), 1, 'help');
	}
	if ($module->getWarning()) {
		print $form->textwithpicto('', $module->getWarning(), 1, 'warning');
	}
	print '</td>';
	print "</tr>\n";
}

print "</table>\n";
print "</div>\n";

print "</form>\n";


print '<br>'."\n\n";


// Level

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

print load_fiche_titre($langs->trans("SyslogLevel"), '', '');

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setlevel">';

print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
print '<td class="center width150"><input type="submit" class="button small" '.$optionmc.' value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

print '<tr class="oddeven"><td>'.$langs->trans("SyslogLevel").'</td>';
print '<td colspan="2"><select class="flat minwidth400" id="level" name="level" '.$optionmc.'>';
print '<option value="'.LOG_EMERG.'" '.($conf->global->SYSLOG_LEVEL == LOG_EMERG ? 'SELECTED' : '').'>LOG_EMERG ('.LOG_EMERG.')</option>';
print '<option value="'.LOG_ALERT.'" '.($conf->global->SYSLOG_LEVEL == LOG_ALERT ? 'SELECTED' : '').'>LOG_ALERT ('.LOG_ALERT.')</option>';
print '<option value="'.LOG_CRIT.'" '.($conf->global->SYSLOG_LEVEL == LOG_CRIT ? 'SELECTED' : '').'>LOG_CRIT ('.LOG_CRIT.')</option>';
print '<option value="'.LOG_ERR.'" '.($conf->global->SYSLOG_LEVEL == LOG_ERR ? 'SELECTED' : '').'>LOG_ERR ('.LOG_ERR.')</option>';
print '<option value="'.LOG_WARNING.'" '.($conf->global->SYSLOG_LEVEL == LOG_WARNING ? 'SELECTED' : '').'">LOG_WARNING ('.LOG_WARNING.')</option>';
print '<option value="'.LOG_NOTICE.'" '.($conf->global->SYSLOG_LEVEL == LOG_NOTICE ? 'SELECTED' : '').' data-html="'.dol_escape_htmltag('LOG_NOTICE ('.LOG_NOTICE.') - <span class="opacitymedium">'.$langs->trans("RecommendedForProduction").'</span>').'">LOG_NOTICE ('.LOG_NOTICE.')</option>';
print '<option value="'.LOG_INFO.'" '.($conf->global->SYSLOG_LEVEL == LOG_INFO ? 'SELECTED' : '').'>LOG_INFO ('.LOG_INFO.')</option>';
print '<option value="'.LOG_DEBUG.'" '.($conf->global->SYSLOG_LEVEL >= LOG_DEBUG ? 'SELECTED' : '').' data-html="'.dol_escape_htmltag('LOG_DEBUG ('.LOG_DEBUG.') - <span class="opacitymedium">'.$langs->trans("RecommendedForDebug").'</span>').'">LOG_DEBUG ('.LOG_DEBUG.')</option>';
print '</select>';

print ajax_combobox("level");
print '</td></tr>';

if (!empty($conf->loghandlers['mod_syslog_file']) && isModEnabled('cron')) {
	print '<tr class="oddeven"><td>'.$langs->trans("SyslogFileNumberOfSaves").'</td>';
	print '<td colspan="2"><input class="width50" type="number" name="file_saves" placeholder="14" min="0" step="1" value="'.getDolGlobalString('SYSLOG_FILE_SAVES').'" />';
	print ' &nbsp; (<a href="'.dol_buildpath('/cron/list.php', 1).'?search_label=CompressSyslogs&status=-1">'.$langs->trans('ConfigureCleaningCronjobToSetFrequencyOfSaves').'</a>)</td></tr>';
}

print '</table>';
print "</div>\n";

print "</form>\n";

// End of page
llxFooter();
$db->close();
