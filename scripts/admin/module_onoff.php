#!/usr/bin/env php
<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    scripts/admin/module_onoff.php
 * \ingroup core
 * \brief   Enable, disable or show the status of modules from the command line
 */

if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

// Read arguments: options (--entity=N) can be anywhere, other arguments are positional
$entity = 1;
$badoption = '';
$args = [];
foreach (array_slice($argv, 1) as $arg) {
	$reg = [];
	if (preg_match('/^--entity=([1-9][0-9]*)$/', $arg, $reg)) {
		$entity = (int) $reg[1];
	} elseif (substr($arg, 0, 1) == '-') {
		$badoption = $arg;
	} else {
		$args[] = $arg;
	}
}

$mode = isset($args[0]) ? strtolower($args[0]) : '';
$modulelist = isset($args[1]) ? $args[1] : '';

if ($badoption !== '' || !in_array($mode, ['enable', 'disable', 'status'], true) || ($mode != 'status' && $modulelist === '')) {
	if ($badoption !== '') {
		print "Error: bad option ".$badoption."\n";
	}
	print "Usage: ".$script_file." [--entity=N] enable|disable module1[,module2...]\n";
	print "       ".$script_file." [--entity=N] status [module1[,module2...]]\n";
	print "\n";
	print "A module can be given as modAdherent, Adherent or MAIN_MODULE_ADHERENT (case insensitive).\n";
	print "- enable also enables the modules the module depends on and disables the conflicting ones.\n";
	print "- disable also disables the modules that require the module.\n";
	print "- --entity=N works on the entity N (multicompany). Default is 1.\n";
	print "Return code is 0 if OK, 1 if at least one module failed.\n";
	exit(1);
}

// The entity must be known before master.inc.php loads the configuration
if (!defined('DOLENTITY')) {
	define('DOLENTITY', $entity);
}

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */

$langs->loadLangs(['admin', 'errors']);

// Refuse an entity that has no setup at all, to avoid creating orphan constants
if ($entity > 1) {
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."const WHERE entity = ".((int) $entity)." LIMIT 1";
	$resql = $db->query($sql);
	if (!$resql) {
		print "Error: ".$db->lasterror()."\n";
		exit(1);
	}
	if ($db->num_rows($resql) == 0) {
		print "Error: entity ".$entity." not found (no constant defined for it)\n";
		exit(1);
	}
	$db->free($resql);
}

@set_time_limit(0);
print "***** ".$script_file." (".DOL_VERSION.") pid=".dol_getmypid()." entity=".$conf->entity." *****\n";
dol_syslog($script_file." launched with arg ".implode(',', $argv));


// Index all module descriptors found on disk: lowercase short name => [class name, file]
$descriptors = [];
foreach (dolGetModulesDirs() as $dir) {
	$files = @scandir($dir);
	if (!is_array($files)) {
		continue;
	}
	foreach ($files as $file) {
		$reg = [];
		if (preg_match('/^(mod(.+))\.class\.php$/', $file, $reg) && !isset($descriptors[strtolower($reg[2])])) {
			$descriptors[strtolower($reg[2])] = ['class' => $reg[1], 'file' => $dir.$file];
		}
	}
}
ksort($descriptors);

// Build the list of modules to process
$requested = [];
foreach (explode(',', $modulelist) as $name) {
	$name = trim($name);
	if ($name !== '') {
		$requested[] = $name;
	}
}
if ($mode == 'status' && empty($requested)) {
	$requested = array_keys($descriptors);
}

$error = 0;

foreach ($requested as $name) {
	$key = strtolower(preg_replace('/^(MAIN_MODULE_|mod)/i', '', $name));

	if (!isset($descriptors[$key])) {
		print $name.": ERROR, no module descriptor found\n";
		$error++;
		continue;
	}

	$classname = $descriptors[$key]['class'];
	$constname = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', $classname));
	$enabled = getDolGlobalString($constname) ? 1 : 0;

	if ($mode == 'status') {
		print $classname.": ".($enabled ? 'enabled' : 'disabled')."\n";
		continue;
	}

	if ($mode == 'enable') {
		if ($enabled) {
			print $classname.": already enabled\n";
			continue;
		}

		$result = activateModule($classname, 1);
		if (!empty($result['errors'])) {
			print $classname.": ERROR, ".dol_string_nohtmltag(implode(', ', $result['errors']))."\n";
			$error++;
		} else {
			print $classname.": enabled (".$result['nbmodules']." module(s), ".$result['nbperms']." permission(s))\n";
		}
	} else {
		if (!$enabled) {
			print $classname.": already disabled\n";
			continue;
		}

		$result = unActivateModule($classname, 1);
		if ($result) {
			print $classname.": ERROR, ".dol_string_nohtmltag($langs->trans($result))."\n";
			$error++;
		} else {
			print $classname.": disabled\n";
		}
	}
}

$db->close();

exit($error ? 1 : 0);
