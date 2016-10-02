<?php
/* Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/admin/syslog.php
 *	\ingroup    syslog
 *	\brief      Setup page for logs module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

global $conf;

if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");

$error=0;
$action = GETPOST("action");

$syslogModules = array();
$activeModules = array();

if (defined('SYSLOG_HANDLERS')) $activeModules = json_decode(constant('SYSLOG_HANDLERS'));

$dirsyslogs = array_merge(array('/core/modules/syslog/'), $conf->modules_parts['syslog']);
foreach ($dirsyslogs as $reldir) {
	$dir = dol_buildpath($reldir, 0);
	$newdir = dol_osencode($dir);
	if (is_dir($newdir)) {
		$handle = opendir($newdir);

		if (is_resource($handle)) {
			$var = true;

			while (($file = readdir($handle)) !== false) {
				if (substr($file, 0, 11) == 'mod_syslog_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);

					require_once $newdir . $file . '.php';

					$module = new $file;

					// Show modules according to features level
					if ($module->getVersion() == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) {
						continue;
					}
					if ($module->getVersion() == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) {
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
if ($action == 'set')
{
	$db->begin();

	$newActiveModules = array();
	$selectedModules = (isset($_POST['SYSLOG_HANDLERS']) ? $_POST['SYSLOG_HANDLERS'] : array());
	//var_dump($selectedModules);
	foreach ($syslogModules as $syslogHandler)
	{
		if (in_array($syslogHandler, $syslogModules))
		{
			$module = new $syslogHandler;

			if (in_array($syslogHandler, $selectedModules)) $newActiveModules[] = $syslogHandler;
			foreach ($module->configure() as $option)
			{
				if (isset($_POST[$option['constant']]))
				{
					$_POST[$option['constant']] = trim($_POST[$option['constant']]);
					dolibarr_del_const($db, $option['constant'], 0);
					dolibarr_set_const($db, $option['constant'], $_POST[$option['constant']], 'chaine',0, '', 0);
				}
			}
		}
	}

	$activeModules = $newActiveModules;
	dolibarr_set_const($db, 'SYSLOG_HANDLERS', json_encode($activeModules), 'chaine',0,'',0);

	// Check configuration
	foreach ($activeModules as $modulename) {
		/**
		 * @var LogHandler
		 */
		$module = new $modulename;
		$error = $module->checkConfiguration();
	}


	if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		$db->rollback();
		setEventMessages($error, $errors, 'errors');

	}

}

// Set level
if ($action == 'setlevel')
{
	$level = GETPOST("level");
	$res = dolibarr_set_const($db,"SYSLOG_LEVEL",$level,'chaine',0,'',0);
	dol_syslog("admin/syslog: level ".$level);

	if (! $res > 0) $error++;
	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */

llxHeader();

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SyslogSetup"),$linkback,'title_setup');
print '<br>';

$def = array();

$syslogfacility=$defaultsyslogfacility=dolibarr_get_const($db,"SYSLOG_FACILITY",0);
$syslogfile=$defaultsyslogfile=dolibarr_get_const($db,"SYSLOG_FILE",0);

if (! $defaultsyslogfacility) $defaultsyslogfacility='LOG_USER';
if (! $defaultsyslogfile) $defaultsyslogfile='dolibarr.log';

if ($conf->global->MAIN_MODULE_MULTICOMPANY && $user->entity)
{
	print '<div class="error">'.$langs->trans("ContactSuperAdminForChange").'</div>';
	$option = 'disabled';
}


//print "conf->global->MAIN_FEATURES_LEVEL = ".$conf->global->MAIN_FEATURES_LEVEL."<br><br>\n";

// Output mode
print load_fiche_titre($langs->trans("SyslogOutput"));

// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Type").'</td><td>'.$langs->trans("Value").'</td>';
print '<td align="right" colspan="2"><input type="submit" class="button" '.$option.' value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=true;

foreach ($syslogModules as $moduleName)
{
	$module = new $moduleName;

	$moduleactive=(int) $module->isActive();
	//print $moduleName." = ".$moduleactive." - ".$module->getName()." ".($moduleactive == -1)."<br>\n";
	if (($moduleactive == -1) && empty($conf->global->MAIN_FEATURES_LEVEL)) continue;		// Some modules are hidden if not activable and not into debug mode (end user must not see them)

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td width="140">';
	print '<input '.$bc[$var].' type="checkbox" name="SYSLOG_HANDLERS[]" value="'.$moduleName.'" '.(in_array($moduleName, $activeModules) ? 'checked' : '').($moduleactive <= 0 ? 'disabled' : '').'> ';
	print $module->getName();
	print '</td>';

	print '<td class="nowrap">';
	$setuparray=$module->configure();
	if ($setuparray)
	{
		foreach ($setuparray as $option)
		{
			if (isset($_POST[$option['constant']])) $value=$_POST[$option['constant']];
			else if (defined($option['constant'])) $value = constant($option['constant']);
			else $value = (isset($option['default']) ? $option['default'] : '');

			print $option['name'].': <input type="text" class="flat" name="'.$option['constant'].'" value="'.$value.'"'.(isset($option['attr']) ? ' '.$option['attr'] : '').'>';
			if (! empty($option['example'])) print '<br>'.$langs->trans("Example").': '.$option['example'];
		}
	}
	print '</td>';

	print '<td align="left">';
	if ($module->getInfo())
	{
		print $form->textwithpicto('', $module->getInfo(), 1, 'help');
	}
	if ($module->getWarning())
	{
		print $form->textwithpicto('', $module->getWarning(), 1, 'warning');
	}
	print '</td>';
	print "</tr>\n";
}

print "</table>\n";
print "</form>\n";

print '<br>'."\n\n";

print load_fiche_titre($langs->trans("SyslogLevel"));

// Level
print '<form action="syslog.php" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setlevel">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
print '<td align="right"><input type="submit" class="button" '.$option.' value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";
$var=true;
$var=!$var;
print '<tr '.$bc[$var].'><td width="140">'.$langs->trans("SyslogLevel").'</td>';
print '<td colspan="2"><select class="flat" name="level" '.$option.'>';
print '<option value="'.LOG_EMERG.'" '.($conf->global->SYSLOG_LEVEL==LOG_EMERG?'SELECTED':'').'>LOG_EMERG ('.LOG_EMERG.')</option>';
print '<option value="'.LOG_ALERT.'" '.($conf->global->SYSLOG_LEVEL==LOG_ALERT?'SELECTED':'').'>LOG_ALERT ('.LOG_ALERT.')</option>';
print '<option value="'.LOG_CRIT.'" '.($conf->global->SYSLOG_LEVEL==LOG_CRIT?'SELECTED':'').'>LOG_CRIT ('.LOG_CRIT.')</option>';
print '<option value="'.LOG_ERR.'" '.($conf->global->SYSLOG_LEVEL==LOG_ERR?'SELECTED':'').'>LOG_ERR ('.LOG_ERR.')</option>';
print '<option value="'.LOG_WARNING.'" '.($conf->global->SYSLOG_LEVEL==LOG_WARNING?'SELECTED':'').'>LOG_WARNING ('.LOG_WARNING.')</option>';
print '<option value="'.LOG_NOTICE.'" '.($conf->global->SYSLOG_LEVEL==LOG_NOTICE?'SELECTED':'').'>LOG_NOTICE ('.LOG_NOTICE.')</option>';
print '<option value="'.LOG_INFO.'" '.($conf->global->SYSLOG_LEVEL==LOG_INFO?'SELECTED':'').'>LOG_INFO ('.LOG_INFO.')</option>';
print '<option value="'.LOG_DEBUG.'" '.($conf->global->SYSLOG_LEVEL>=LOG_DEBUG?'SELECTED':'').'>LOG_DEBUG ('.LOG_DEBUG.')</option>';
print '</select>';
print '</td></tr>';
print '</table>';
print "</form>\n";

llxFooter();

$db->close();
