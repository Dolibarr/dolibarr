<?php
/* Copyright (C) 2004-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019 Nicolas ZABOURI	<info@inovea-conseil.com>
 * Copyright (C) 2023      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *
 * You can also make a direct call the page with parameter like this:
 * htdocs/modulebuilder/index.php?module=Inventory@/pathtodolibarr/htdocs/product
 */

/**
 *       \file       htdocs/modulebuilder/index.php
 *       \brief      Home page for module builder module
 *
 *       You can add parameter dirins=/home/ldestailleur/git/dolibarr/htdocs/mymodule to force generation of module
 *       into the dirins directory.
 */

if (!defined('NOSCANPOSTFORINJECTION')) {
	define('NOSCANPOSTFORINJECTION', '1'); // Do not check anti SQL+XSS injection attack test
}

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/modulebuilder.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "modulebuilder", "exports", "other", "cron", "errors"));

// GET Parameters
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel  = GETPOST('cancel', 'alpha');

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'aZ09');

$module = GETPOST('module', 'alpha');
$tab = GETPOST('tab', 'aZ09');
$tabobj = GETPOST('tabobj', 'alpha');
$tabdic = GETPOST('tabdic', 'alpha');
$propertykey = GETPOST('propertykey', 'alpha');
if (empty($module)) {
	$module = 'initmodule';
}
if (empty($tab)) {
	$tab = 'description';
}
if (empty($tabobj)) {
	$tabobj = 'newobjectifnoobj';
}
if (empty($tabdic)) {
	$tabdic = 'newdicifnodic';
}
$file = GETPOST('file', 'alpha');
$find = GETPOST('find', 'alpha');

$modulename = dol_sanitizeFileName(GETPOST('modulename', 'alpha'));
$objectname = dol_sanitizeFileName(GETPOST('objectname', 'alpha'));
$dicname = dol_sanitizeFileName(GETPOST('dicname', 'alpha'));
$editorname = GETPOST('editorname', 'alpha');
$editorurl = GETPOST('editorurl', 'alpha');
$version = GETPOST('version', 'alpha');
$family = GETPOST('family', 'alpha');
$picto = GETPOST('idpicto', 'alpha');
$idmodule = GETPOST('idmodule', 'alpha');
$format = '';  // Prevent undefined in css tab

// Security check
if (!isModEnabled('modulebuilder')) {
	accessforbidden('Module ModuleBuilder not enabled');
}
if (!$user->hasRight("modulebuilder", "run")) {
	accessforbidden('ModuleBuilderNotAllowed');
}

// Dir for custom dirs
$tmp = explode(',', $dolibarr_main_document_root_alt);
$dirins = $tmp[0];
$dirread = $dirins;
$forceddirread = 0;

$tmpdir = explode('@', $module);
if (!empty($tmpdir[1])) {
	$module = $tmpdir[0];
	$dirread = $tmpdir[1];
	$forceddirread = 1;
}
if (GETPOST('dirins', 'alpha')) {
	$dirread = $dirins = GETPOST('dirins', 'alpha');
	$forceddirread = 1;
}

$FILEFLAG = 'modulebuilder.txt';

$now = dol_now();
$newmask = 0;
if (empty($newmask) && getDolGlobalString('MAIN_UMASK')) {
	$newmask = getDolGlobalString('MAIN_UMASK');
}
if (empty($newmask)) {	// This should no happen
	$newmask = '0664';
}

$result = restrictedArea($user, 'modulebuilder', 0);

$error = 0;

$form = new Form($db);

// Define $listofmodules
$dirsrootforscan = array($dirread);

// Add also the core modules into the list of modules to show/edit
if ($dirread != DOL_DOCUMENT_ROOT && (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2 || getDolGlobalString('MODULEBUILDER_ADD_DOCUMENT_ROOT'))) {
	$dirsrootforscan[] = DOL_DOCUMENT_ROOT;
}

// Search modules to edit
$textforlistofdirs = '<!-- Directory scanned -->'."\n";
$listofmodules = array();
$i = 0;
foreach ($dirsrootforscan as $tmpdirread) {
	$moduletype = 'external';
	if ($tmpdirread == DOL_DOCUMENT_ROOT) {
		$moduletype = 'internal';
	}

	$dirsincustom = dol_dir_list($tmpdirread, 'directories');
	if (is_array($dirsincustom) && count($dirsincustom) > 0) {
		foreach ($dirsincustom as $dircustomcursor) {
			$fullname = $dircustomcursor['fullname'];
			if (dol_is_file($fullname.'/'.$FILEFLAG)) {
				// Get real name of module (MyModule instead of mymodule)
				$dirtoscanrel = basename($fullname).'/core/modules/';

				$descriptorfiles = dol_dir_list(dirname($fullname).'/'.$dirtoscanrel, 'files', 0, 'mod.*\.class\.php$');
				if (empty($descriptorfiles)) {	// If descriptor not found into module dir, we look into main module dir.
					$dirtoscanrel = 'core/modules/';
					$descriptorfiles = dol_dir_list($fullname.'/../'.$dirtoscanrel, 'files', 0, 'mod'.strtoupper(basename($fullname)).'\.class\.php$');
				}
				$modulenamewithcase = '';
				$moduledescriptorrelpath = '';
				$moduledescriptorfullpath = '';

				foreach ($descriptorfiles as $descriptorcursor) {
					$modulenamewithcase = preg_replace('/^mod/', '', $descriptorcursor['name']);
					$modulenamewithcase = preg_replace('/\.class\.php$/', '', $modulenamewithcase);
					$moduledescriptorrelpath = $dirtoscanrel.$descriptorcursor['name'];
					$moduledescriptorfullpath = $descriptorcursor['fullname'];
					//var_dump($descriptorcursor);
				}
				if ($modulenamewithcase) {
					$listofmodules[$dircustomcursor['name']] = array(
						'modulenamewithcase' => $modulenamewithcase,
						'moduledescriptorrelpath' => $moduledescriptorrelpath,
						'moduledescriptorfullpath' => $moduledescriptorfullpath,
						'moduledescriptorrootpath' => $tmpdirread,
						'moduletype' => $moduletype
					);
				}
				//var_dump($listofmodules);
			}
		}
	}

	if ($forceddirread && empty($listofmodules)) {    // $forceddirread is 1 if we forced dir to read with dirins=... or with module=...@mydir
		$listofmodules[strtolower($module)] = array(
			'modulenamewithcase' => $module,
			'moduledescriptorrelpath' => 'notyetimplemented',
			'moduledescriptorfullpath' => 'notyetimplemented',
			'moduledescriptorrootpath' => 'notyetimplemented',
		);
	}

	// Show description of content
	$newdircustom = $dirins;
	if (empty($newdircustom)) {
		$newdircustom = img_warning();
	}
	// If dirread was forced to somewhere else, by using URL
	// htdocs/modulebuilder/index.php?module=Inventory@/home/ldestailleur/git/dolibarr/htdocs/product
	if (empty($i)) {
		$textforlistofdirs .= $langs->trans("DirScanned").' : ';
	} else {
		$textforlistofdirs .= ', ';
	}
	$textforlistofdirs .= '<strong class="wordbreakimp">'.$tmpdirread.'</strong>';
	if ($tmpdirread == DOL_DOCUMENT_ROOT) {
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
			$textforlistofdirs .= $form->textwithpicto('', $langs->trans("ConstantIsOn", "MAIN_FEATURES_LEVEL"));
		}
		if (getDolGlobalString('MODULEBUILDER_ADD_DOCUMENT_ROOT')) {
			$textforlistofdirs .= $form->textwithpicto('', $langs->trans("ConstantIsOn", "MODULEBUILDER_ADD_DOCUMENT_ROOT"));
		}
	}
	$i++;
}

/**
 * Add management to catch fatal errors - shutdown handler
 *
 * @return	void
 */
function moduleBuilderShutdownFunction()
{
	$error = error_get_last();
	if ($error && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
		// Handle the fatal error
		echo "Fatal error occurred: {$error['message']} in {$error['file']} on line {$error['line']}";
		// If a header was already send, we suppose it is the llx_Header() so we call the llxFooter()
		if (headers_sent()) {
			llxFooter();
		}
	}
}
register_shutdown_function("moduleBuilderShutdownFunction");


/**
 * Produce copyright replacement string for user
 *
 * @param	User		$user	User to produce the copyright notice for.
 * @param	Translate	$langs	Translation object to use.
 * @param	int			$now	Date for which the copyright will be generated.
 *
 * @return	string	String to be used as replacement after Copyright (C)
 */
function getLicenceHeader($user, $langs, $now)
{
	$licInfo = $user->getFullName($langs);
	$emailTabs = str_repeat("\t", (int) (max(0, (31 - mb_strlen($licInfo)) / 4)));
	$licInfo .= ($user->email ? $emailTabs.'<'.$user->email.'>' : '');
	$licInfo = dol_print_date($now, '%Y')."\t\t".$licInfo;
	return $licInfo;
}

/*
 * Actions
 */

if ($dirins && $action == 'initmodule' && $modulename && $user->hasRight("modulebuilder", "run")) {
	$modulename = ucfirst($modulename); // Force first letter in uppercase
	$destdir = '/not_set/';

	if (preg_match('/[^a-z0-9_]/i', $modulename)) {
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
	}

	if (!$error) {
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$destdir = $dirins.'/'.strtolower($modulename);

		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename
		);
		$result = dolCopyDir($srcdir, $destdir, 0, 0, $arrayreplacement);
		//dol_mkdir($destfile);
		if ($result <= 0) {
			if ($result < 0) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFailToCopyDir", $srcdir, $destdir), null, 'errors');
			} else {
				// $result == 0
				setEventMessages($langs->trans("AllFilesDidAlreadyExist", $srcdir, $destdir), null, 'warnings');
			}
		}

		// Copy last 'html.formsetup.class.php' to backport folder
		if (getDolGlobalInt('MODULEBUILDER_SUPPORT_COMPATIBILITY_V16')) {
			$tryToCopyFromSetupClass = true;
			$backportDest = $destdir .'/backport/v16/core/class';
			$backportFileSrc = DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
			$backportFileDest = $backportDest.'/html.formsetup.class.php';
			$result = dol_mkdir($backportDest);

			if ($result < 0) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFailToCreateDir", $backportDest), null, 'errors');
				$tryToCopyFromSetupClass = false;
			}

			if ($tryToCopyFromSetupClass) {
				$result = dol_copy($backportFileSrc, $backportFileDest);
				if ($result <= 0) {
					if ($result < 0) {
						$error++;
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorFailToCopyFile", $backportFileSrc, $backportFileDest), null, 'errors');
					} else {
						setEventMessages($langs->trans("FileDidAlreadyExist", $backportFileDest), null, 'warnings');
					}
				}
			}
		}

		if (getDolGlobalString('MODULEBUILDER_USE_ABOUT')) {
			dol_delete_file($destdir.'/admin/about.php');
		}

		// Delete dir and files that can be generated in sub tabs later if we need them (we want a minimal module first)
		dol_delete_dir_recursive($destdir.'/ajax');
		dol_delete_dir_recursive($destdir.'/build/doxygen');
		dol_delete_dir_recursive($destdir.'/core/modules/mailings');
		dol_delete_dir_recursive($destdir.'/core/modules/'.strtolower($modulename));
		dol_delete_dir_recursive($destdir.'/core/tpl');
		dol_delete_dir_recursive($destdir.'/core/triggers');
		dol_delete_dir_recursive($destdir.'/doc');
		//dol_delete_dir_recursive($destdir.'/.tx');
		dol_delete_dir_recursive($destdir.'/core/boxes');

		dol_delete_file($destdir.'/admin/myobject_extrafields.php');

		dol_delete_file($destdir.'/class/actions_'.strtolower($modulename).'.class.php');
		dol_delete_file($destdir.'/class/api_'.strtolower($modulename).'.class.php');

		dol_delete_file($destdir.'/css/'.strtolower($modulename).'.css.php');

		dol_delete_file($destdir.'/js/'.strtolower($modulename).'.js.php');

		dol_delete_file($destdir.'/scripts/'.strtolower($modulename).'.php');

		dol_delete_file($destdir.'/sql/data.sql');
		dol_delete_file($destdir.'/sql/update_x.x.x-y.y.y.sql');

		// Delete some files related to Object (because the previous dolCopyDir has copied everything)
		dol_delete_file($destdir.'/myobject_card.php');
		dol_delete_file($destdir.'/myobject_contact.php');
		dol_delete_file($destdir.'/myobject_note.php');
		dol_delete_file($destdir.'/myobject_document.php');
		dol_delete_file($destdir.'/myobject_agenda.php');
		dol_delete_file($destdir.'/myobject_list.php');
		dol_delete_file($destdir.'/lib/'.strtolower($modulename).'_myobject.lib.php');
		dol_delete_file($destdir.'/test/phpunit/functional/'.$modulename.'FunctionalTest.php');
		dol_delete_file($destdir.'/test/phpunit/MyObjectTest.php');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject.sql');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject_extrafields.sql');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject.key.sql');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject_extrafields.key.sql');
		dol_delete_file($destdir.'/class/myobject.class.php');

		dol_delete_dir($destdir.'/class', 1);
		dol_delete_dir($destdir.'/css', 1);
		dol_delete_dir($destdir.'/js', 1);
		dol_delete_dir($destdir.'/scripts', 1);
		dol_delete_dir($destdir.'/sql', 1);
		dol_delete_dir($destdir.'/test/phpunit/functionnal', 1);
		dol_delete_dir($destdir.'/test/phpunit', 1);
		dol_delete_dir($destdir.'/test', 1);
	}

	// Edit PHP files
	if (!$error) {
		$listofphpfilestoedit = dol_dir_list($destdir, 'files', 1, '\.(php|MD|js|sql|txt|xml|lang)$', '', 'fullname', SORT_ASC, 0, 1);

		$licInfo = getLicenceHeader($user, $langs, $now);
		foreach ($listofphpfilestoedit as $phpfileval) {
			//var_dump($phpfileval['fullname']);
			$arrayreplacement = array(
				'mymodule' => strtolower($modulename),
				'MyModule' => $modulename,
				'MYMODULE' => strtoupper($modulename),
				'My module' => $modulename,
				'my module' => $modulename,
				'Mon module' => $modulename,
				'mon module' => $modulename,
				'htdocs/modulebuilder/template' => strtolower($modulename),
				'---Put here your own copyright and developer email---' => $licInfo,
				'---Replace with your own copyright and developer email---' => $licInfo,
				'Editor name' => $editorname,
				'https://www.example.com' => $editorurl,
				'$this->version = \'1.0\'' => '$this->version = \''.$version.'\'',
				'$this->picto = \'generic\';' => (empty($picto)) ? '$this->picto = \'generic\'' : '$this->picto = \''.$picto.'\';',
				"modulefamily" => $family,
				'500000' => $idmodule
			);

			if (getDolGlobalString('MODULEBUILDER_SPECIFIC_AUTHOR')) {
				$arrayreplacement['---Replace with your own copyright and developer email---'] = dol_print_date($now, '%Y')."\t\t" . getDolGlobalString('MODULEBUILDER_SPECIFIC_AUTHOR');
			}

			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$result = dolReplaceInFile($phpfileval['fullname'], $arrayreplacement);
			//var_dump($result);
			if ($result < 0) {
				setEventMessages($langs->trans("ErrorFailToMakeReplacementInto", $phpfileval['fullname']), null, 'errors');
			}
		}

		if (getDolGlobalString('MODULEBUILDER_SPECIFIC_README')) {
			setEventMessages($langs->trans("ContentOfREADMECustomized"), null, 'warnings');
			dol_delete_file($destdir.'/README.md');
			file_put_contents($destdir.'/README.md', $conf->global->MODULEBUILDER_SPECIFIC_README);
		}
		// for create file to add properties
		// file_put_contents($destdir.'/'.strtolower($modulename).'propertycard.php','');
		// $srcFileCard = DOL_DOCUMENT_ROOT.'/modulebuilder/card.php';
		// $destFileCard = $dirins.'/'.strtolower($modulename).'/template/card.php';
		// dol_copy($srcFileCard, $destdir.'/'.strtolower($modulename).'propertycard.php', 0,1, $arrayreplacement);
	}

	if (!$error) {
		setEventMessages('ModuleInitialized', null);
		$module = $modulename;

		clearstatcache(true);
		if (function_exists('opcache_invalidate')) {
			opcache_reset();	// remove the include cache hell !
		}

		header("Location: ".$_SERVER["PHP_SELF"].'?module='.$modulename);
		exit;
	}
}

$destdir = '/not_set/';  // Initialize (for static analysis)
$destfile = '/not_set/';  // Initialize (for static analysis)
$srcfile = '/not_set/';  // Initialize (for static analysis)

// init API, PHPUnit
if ($dirins && in_array($action, array('initapi', 'initphpunit', 'initpagecontact', 'initpagedocument', 'initpagenote', 'initpageagenda')) && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	$modulename = ucfirst($module); // Force first letter in uppercase
	$objectname = $tabobj;
	$varnametoupdate = '';
	$dirins = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$destdir = $dirins.'/'.strtolower($module);

	// Get list of existing objects
	$objects = dolGetListOfObjectClasses($destdir);


	if ($action == 'initapi') {					// Test on permission already done
		if (file_exists($dirins.'/'.strtolower($module).'/class/api_'.strtolower($module).'.class.php')) {
			$result = dol_copy(DOL_DOCUMENT_ROOT.'/modulebuilder/template/class/api_mymodule.class.php', $dirins.'/'.strtolower($module).'/class/api_'.strtolower($module).'.class.php', 0, 1);
		}
		dol_mkdir($dirins.'/'.strtolower($module).'/class');
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$srcfile = $srcdir.'/class/api_mymodule.class.php';
		$destfile = $dirins.'/'.strtolower($module).'/class/api_'.strtolower($module).'.class.php';
	} elseif ($action == 'initphpunit') {		// Test on permission already done
		dol_mkdir($dirins.'/'.strtolower($module).'/test/phpunit');
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$srcfile = $srcdir.'/test/phpunit/MyObjectTest.php';
		$destfile = $dirins.'/'.strtolower($module).'/test/phpunit/'.strtolower($objectname).'Test.php';
	} elseif ($action == 'initpagecontact') {	// Test on permission already done
		dol_mkdir($dirins.'/'.strtolower($module));
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$srcfile = $srcdir.'/myobject_contact.php';
		$destfile = $dirins.'/'.strtolower($module).'/'.strtolower($objectname).'_contact.php';
		$varnametoupdate = 'showtabofpagecontact';
	} elseif ($action == 'initpagedocument') {	// Test on permission already done
		dol_mkdir($dirins.'/'.strtolower($module));
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$srcfile = $srcdir.'/myobject_document.php';
		$destfile = $dirins.'/'.strtolower($module).'/'.strtolower($objectname).'_document.php';
		$varnametoupdate = 'showtabofpagedocument';
	} elseif ($action == 'initpagenote') {		// Test on permission already done
		dol_mkdir($dirins.'/'.strtolower($module));
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$srcfile = $srcdir.'/myobject_note.php';
		$destfile = $dirins.'/'.strtolower($module).'/'.strtolower($objectname).'_note.php';
		$varnametoupdate = 'showtabofpagenote';
	} elseif ($action == 'initpageagenda') {	// Test on permission already done
		dol_mkdir($dirins.'/'.strtolower($module));
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$srcfile = $srcdir.'/myobject_agenda.php';
		$destfile = $dirins.'/'.strtolower($module).'/'.strtolower($objectname).'_agenda.php';
		$varnametoupdate = 'showtabofpageagenda';
	}

	//var_dump($srcfile);
	//var_dump($destfile);
	if (!file_exists($destfile)) {
		$result = dol_copy($srcfile, $destfile, 0, 0);
	}

	if ($result > 0) {
		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'myobject' => strtolower($objectname),
			'MyObject' => $objectname,
			'MYOBJECT' => strtoupper($objectname),

			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		if ($action == 'initapi') {			// Test on permission already done
			if (count($objects) >= 1) {
				addObjectsToApiFile($srcfile, $destfile, $objects, $modulename);
			}
		} else {
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			dolReplaceInFile($destfile, $arrayreplacement);
		}

		if ($varnametoupdate) {
			// Now we update the object file to set $$varnametoupdate to 1
			$srcfile = $dirins.'/'.strtolower($module).'/lib/'.strtolower($module).'_'.strtolower($objectname).'.lib.php';
			$arrayreplacement = array('/\$'.preg_quote($varnametoupdate, '/').' = 0;/' => '$'.$varnametoupdate.' = 1;');
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			dolReplaceInFile($srcfile, $arrayreplacement, '', '0', 0, 1);
		}
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// init ExtraFields
if ($dirins && $action == 'initsqlextrafields' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	$modulename = ucfirst($module); // Force first letter in uppercase
	$objectname = $tabobj;

	dol_mkdir($dirins.'/'.strtolower($module).'/sql');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile1 = $srcdir.'/sql/llx_mymodule_myobject_extrafields.sql';
	$destfile1 = $dirins.'/'.strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.sql';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result1 = dol_copy($srcfile1, $destfile1, 0, 0);
	$srcfile2 = $srcdir.'/sql/llx_mymodule_myobject_extrafields.key.sql';
	$destfile2 = $dirins.'/'.strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.key.sql';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result2 = dol_copy($srcfile2, $destfile2, 0, 0);

	if ($result1 > 0 && $result2 > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'My Object' => $objectname,
			'MyObject' => $objectname,
			'my object' => strtolower($objectname),
			'myobject' => strtolower($objectname),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		dolReplaceInFile($destfile1, $arrayreplacement);
		dolReplaceInFile($destfile2, $arrayreplacement);
	} else {
		$langs->load("errors");
		if ($result1 <= 0) {
			setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile1), null, 'errors');
		}
		if ($result2 <= 0) {
			setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile2), null, 'errors');
		}
	}

	// Now we update the object file to set $this->isextrafieldmanaged to 1
	$srcfile = $dirins.'/'.strtolower($module).'/class/'.strtolower($objectname).'.class.php';
	$arrayreplacement = array('/\$this->isextrafieldmanaged = 0;/' => '$this->isextrafieldmanaged = 1;');
	dolReplaceInFile($srcfile, $arrayreplacement, '', '0', 0, 1);
}


// init Hook
if ($dirins && $action == 'inithook' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/class');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/class/actions_mymodule.class.php';
	$destfile = $dirins.'/'.strtolower($module).'/class/actions_'.strtolower($module).'.class.php';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// init Trigger
if ($dirins && $action == 'inittrigger' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/core/triggers');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/core/triggers/interface_99_modMyModule_MyModuleTriggers.class.php';
	$destfile = $dirins.'/'.strtolower($module).'/core/triggers/interface_99_mod'.$module.'_'.$module.'Triggers.class.php';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// init Widget
if ($dirins && $action == 'initwidget' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/core/boxes');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/core/boxes/mymodulewidget1.php';
	$destfile = $dirins.'/'.strtolower($module).'/core/boxes/'.strtolower($module).'widget1.php';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// init EmailSelector
if ($dirins && $action == 'initemailing' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/core/modules/mailings');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/core/modules/mailings/mailing_mymodule_selector1.modules.php';
	$destfile = $dirins.'/'.strtolower($module).'/core/modules/mailings/mailing_'.strtolower($module).'_selector1.modules.php';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// init CSS
if ($dirins && $action == 'initcss' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/css');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/css/mymodule.css.php';
	$destfile = $dirins.'/'.strtolower($module).'/css/'.strtolower($module).'.css.php';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);

		// Update descriptor file to uncomment file
		$srcfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		$arrayreplacement = array('/\/\/\s*\''.preg_quote('/'.strtolower($module).'/css/'.strtolower($module).'.css.php', '/').'\'/' => '\'/'.strtolower($module).'/css/'.strtolower($module).'.css.php\'');
		dolReplaceInFile($srcfile, $arrayreplacement, '', '0', 0, 1);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// init JS
if ($dirins && $action == 'initjs' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/js');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/js/mymodule.js.php';
	$destfile = $dirins.'/'.strtolower($module).'/js/'.strtolower($module).'.js.php';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);

		// Update descriptor file to uncomment file
		$srcfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		$arrayreplacement = array('/\/\/\s*\''.preg_quote('/'.strtolower($module).'/js/'.strtolower($module).'.js.php', '/').'\'/' => '\'/'.strtolower($module).'/js/'.strtolower($module).'.js.php\'');
		dolReplaceInFile($srcfile, $arrayreplacement, '', '0', 0, 1);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// init CLI
if ($dirins && $action == 'initcli' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/scripts');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/scripts/mymodule.php';
	$destfile = $dirins.'/'.strtolower($module).'/scripts/'.strtolower($module).'.php';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'__MYCOMPANY_NAME__' => $mysoc->name,
			'__KEYWORDS__' => $modulename,
			'__USER_FULLNAME__' => $user->getFullName($langs),
			'__USER_EMAIL__' => $user->email,
			'__YYYY-MM-DD__' => dol_print_date($now, 'dayrfc'),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


$moduledescriptorfile = '/not_set/';

// init Doc
if ($dirins && $action == 'initdoc' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	dol_mkdir($dirins.'/'.strtolower($module).'/doc');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/doc/Documentation.asciidoc';
	$destfile = $dirins.'/'.strtolower($module).'/doc/Documentation.asciidoc';
	//var_dump($srcfile);
	//var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase
		$modulelowercase = strtolower($module);

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'__MYCOMPANY_NAME__' => $mysoc->name,
			'__KEYWORDS__' => $modulename,
			'__USER_FULLNAME__' => $user->getFullName($langs),
			'__USER_EMAIL__' => $user->email,
			'__YYYY-MM-DD__' => dol_print_date($now, 'dayrfc'),
			'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
		);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		dolReplaceInFile($destfile, $arrayreplacement);

		// add table of properties
		$dirins = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
		$destdir = $dirins.'/'.strtolower($module);
		$objects = dolGetListOfObjectClasses($destdir);
		foreach ($objects as $path => $obj) {
			writePropsInAsciiDoc($path, $obj, $destfile);
		}

		// add table of permissions
		$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		writePermsInAsciiDoc($moduledescriptorfile, $destfile);

		// add api urls if file exist
		if (file_exists($dirins.'/'.strtolower($module).'/class/api_'.strtolower($module).'.class.php')) {
			$apiFile = $dirins.'/'.strtolower($module).'/class/api_'.strtolower($module).'.class.php';
			writeApiUrlsInDoc($apiFile, $destfile);
		}

		// add ChangeLog in Doc
		if (file_exists($dirins.'/'.strtolower($module).'/ChangeLog.md')) {
			$changeLog = $dirins.'/'.strtolower($module).'/ChangeLog.md';
			$string = file_get_contents($changeLog);

			$replace = explode("\n", $string);
			$strreplace = array();
			foreach ($replace as $line) {
				if ($line === '') {
					continue;
				}
				if (strpos($line, '##') !== false) {
					$strreplace[$line] = str_replace('##', '', $line);
				} else {
					$strreplace[$line] = $line;
				}
			}
			$stringLog = implode("\n", $strreplace);
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			dolReplaceInFile($destfile, array('//include::ChangeLog.md[]' => '','__CHANGELOG__' => $stringLog));
		}

		// Delete old documentation files
		$FILENAMEDOC = $modulelowercase.'.html';
		$FILENAMEDOCPDF = $modulelowercase.'.pdf';
		$outputfiledoc = dol_buildpath($modulelowercase, 0).'/doc/'.$FILENAMEDOC;
		$outputfiledocurl = dol_buildpath($modulelowercase, 1).'/doc/'.$FILENAMEDOC;
		$outputfiledocpdf = dol_buildpath($modulelowercase, 0).'/doc/'.$FILENAMEDOCPDF;
		$outputfiledocurlpdf = dol_buildpath($modulelowercase, 1).'/doc/'.$FILENAMEDOCPDF;

		dol_delete_file($outputfiledoc, 0, 0, 0, null, false, 0);
		dol_delete_file($outputfiledocpdf, 0, 0, 0, null, false, 0);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}


// add Language
if ($dirins && $action == 'addlanguage' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	$newlangcode = GETPOST('newlangcode', 'aZ09');

	if ($newlangcode) {
		$modulelowercase = strtolower($module);

		// Dir for module
		$diroflang = dol_buildpath($modulelowercase, 0);

		if ($diroflang == $dolibarr_main_document_root.'/'.$modulelowercase) {
			// This is not a custom module, we force diroflang to htdocs root
			$diroflang = $dolibarr_main_document_root;

			$srcfile = $diroflang.'/langs/en_US/'.$modulelowercase.'.lang';
			$destfile = $diroflang.'/langs/'.$newlangcode.'/'.$modulelowercase.'.lang';

			$result = dol_copy($srcfile, $destfile, 0, 0);
			if ($result < 0) {
				setEventMessages($langs->trans("ErrorFailToCopyFile", $srcfile, $destfile), null, 'errors');
			}
		} else {
			$srcdir = $diroflang.'/langs/en_US';
			$srcfile = $diroflang.'/langs/en_US/'.$modulelowercase.'.lang';
			$destdir = $diroflang.'/langs/'.$newlangcode;

			$arrayofreplacement = array();
			if (!dol_is_dir($srcfile) || !dol_is_file($srcfile)) {
				$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template/langs/en_US';
				$arrayofreplacement = array('mymodule' => $modulelowercase);
			}
			$result = dolCopyDir($srcdir, $destdir, 0, 0, $arrayofreplacement);
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Language")), null, 'errors');
	}
}


// Remove/delete File
if ($dirins && $action == 'confirm_removefile' && !empty($module) && $user->hasRight("modulebuilder", "run")) {
	$objectname = $tabobj;
	$dirins = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$destdir = $dirins.'/'.strtolower($module);

	$relativefilename = dol_sanitizePathName(GETPOST('file', 'restricthtml'));

	// Now we delete the file
	if ($relativefilename) {
		$dirnametodelete = dirname($relativefilename);
		$filetodelete = $dirins.'/'.$relativefilename;
		$dirtodelete  = $dirins.'/'.$dirnametodelete;

		// Get list of existing objects
		$objects = dolGetListOfObjectClasses($destdir);

		$keyofobjecttodelete = array_search($objectname, $objects);
		if ($keyofobjecttodelete !== false) {
			unset($objects[$keyofobjecttodelete]);
		}

		// Delete or modify the file
		if (strpos($relativefilename, 'api') !== false) {
			$file_api = $destdir.'/class/api_'.strtolower($module).'.class.php';

			$removeFile = removeObjectFromApiFile($file_api, $objects, $objectname);

			if (count($objects) == 0) {
				$result = dol_delete_file($filetodelete);
			}

			if ($removeFile) {
				setEventMessages($langs->trans("ApiObjectDeleted"), null);
			}
		} else {
			$result = dol_delete_file($filetodelete);
		}

		if (!$result) {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", basename($filetodelete)), null, 'errors');
		} else {
			// If we delete a .sql file, we delete also the other .sql file
			if (preg_match('/\.sql$/', $relativefilename)) {
				if (preg_match('/\.key\.sql$/', $relativefilename)) {
					$relativefilename = preg_replace('/\.key\.sql$/', '.sql', $relativefilename);
					$filetodelete = $dirins.'/'.$relativefilename;
					$result = dol_delete_file($filetodelete);
				} elseif (preg_match('/\.sql$/', $relativefilename)) {
					$relativefilename = preg_replace('/\.sql$/', '.key.sql', $relativefilename);
					$filetodelete = $dirins.'/'.$relativefilename;
					$result = dol_delete_file($filetodelete);
				}
			}

			if (dol_is_dir_empty($dirtodelete)) {
				dol_delete_dir($dirtodelete);
			}

			// Update descriptor file to comment file
			if (in_array($tab, array('css', 'js'))) {
				$srcfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
				$arrayreplacement = array('/^\s*\''.preg_quote('/'.$relativefilename, '/').'\',*/m' => '                // \'/'.$relativefilename.'\',');
				dolReplaceInFile($srcfile, $arrayreplacement, '', '0', 0, 1);
			}

			if (preg_match('/_extrafields/', $relativefilename)) {
				// Now we update the object file to set $isextrafieldmanaged to 0
				$srcfile = $dirins.'/'.strtolower($module).'/class/'.strtolower($objectname).'.class.php';
				$arrayreplacement = array('/\$isextrafieldmanaged = 1;/' => '$isextrafieldmanaged = 0;');
				dolReplaceInFile($srcfile, $arrayreplacement, '', '0', 0, 1);
			}

			// Now we update the lib file to set $showtabofpagexxx to 0
			$varnametoupdate = '';
			$reg = array();
			if (preg_match('/_([a-z]+)\.php$/', $relativefilename, $reg)) {
				$varnametoupdate = 'showtabofpage'.$reg[1];
			}
			if ($varnametoupdate) {
				$srcfile = $dirins.'/'.strtolower($module).'/lib/'.strtolower($module).'_'.strtolower($objectname).'.lib.php';
				$arrayreplacement = array('/\$'.preg_quote($varnametoupdate, '/').' = 1;/' => '$'.preg_quote($varnametoupdate, '/').' = 0;');
				dolReplaceInFile($srcfile, $arrayreplacement, '', '0', 0, 1);
			}
		}
	}
}

// Init an object
if ($dirins && $action == 'initobject' && $module && $objectname && $user->hasRight("modulebuilder", "run")) {
	$warning = 0;

	$objectname = ucfirst($objectname);

	$dirins = $dirread = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$moduletype = $listofmodules[strtolower($module)]['moduletype'];

	if (preg_match('/[^a-z0-9_]/i', $objectname)) {
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
		$tabobj = 'newobject';
	}
	if (class_exists($objectname)) {
		// TODO Add a more efficient detection. Scan disk ?
		$error++;
		setEventMessages($langs->trans("AnObjectWithThisClassNameAlreadyExists"), null, 'errors');
		$tabobj = 'newobject';
	}

	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$destdir = $dirins.'/'.strtolower($module);

	// The dir was not created by init
	dol_mkdir($destdir.'/class');
	dol_mkdir($destdir.'/img');
	dol_mkdir($destdir.'/lib');
	dol_mkdir($destdir.'/scripts');
	dol_mkdir($destdir.'/sql');

	// Scan dir class to find if an object with the same name already exists.
	if (!$error) {
		$dirlist = dol_dir_list($destdir.'/class', 'files', 0, '\.txt$');
		$alreadyfound = false;
		foreach ($dirlist as $key => $val) {
			$filefound = preg_replace('/\.txt$/', '', $val['name']);
			if (strtolower($objectname) == strtolower($filefound) && $objectname != $filefound) {
				$alreadyfound = true;
				$error++;
				setEventMessages($langs->trans("AnObjectAlreadyExistWithThisNameAndDiffCase"), null, 'errors');
				break;
			}
		}
	}

	// If we must reuse an existing table for properties, define $stringforproperties
	// Generate class file from the table
	$stringforproperties = '';
	$tablename = GETPOST('initfromtablename', 'alpha');
	if ($tablename) {
		$_results = $db->DDLDescTable($tablename);
		if (empty($_results)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorTableNotFound", $tablename), null, 'errors');
		} else {
			/**
			 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'ip', 'url', 'password')
			 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
			 *  'label' the translation key.
			 *  'picto' is code of a picto to show before value in forms
			 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or 'isModEnabled("multicurrency")' ...)
			 *  'position' is the sort order of field.
			 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
			 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
			 *  'noteditable' says if field is not editable (1 or 0)
			 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
			 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
			 *  'index' if we want an index in database.
			 *  'foreignkey' => 'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
			 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
			 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
			 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css' => 'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview' => 'wordbreak', 'csslist' => 'tdoverflowmax200'
			 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
			 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
			 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
			 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0" => "Draft","1" => "Active","-1" => "Cancel"). Note that type can be 'integer' or 'varchar'
			 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
			 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
			 *	'validate' is 1 if need to validate with $this->validateField()
			 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
			 */

			/*public $fields=array(
			 'rowid' => array('type' => 'integer',        'label' => 'TechnicalID',      'enabled' => 1, 'visible' => -2, 'notnull' => 1,  'index' => 1, 'position' => 1, 'comment' => 'Id'),
			 'ref' => array('type' => 'varchar(128)',   'label' => 'Ref',              'enabled' => 1, 'visible' => 1,  'notnull' => 1,  'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1, 'comment' => 'Reference of object'),
			 'entity' => array('type' => 'integer',        'label' => 'Entity',           'enabled' => 1, 'visible' => 0,  'default' => 1, 'notnull' => 1,  'index' => 1, 'position' => 20),
			 'label' => array('type' => 'varchar(255)',   'label' => 'Label',            'enabled' => 1, 'visible' => 1,  'position' => 30,  'searchall' => 1, 'css' => 'minwidth200', 'help' => 'Help text', 'alwayseditable' => '1'),
			 'amount' => array('type' => 'double(24,8)',   'label' => 'Amount',           'enabled' => 1, 'visible' => 1,  'default' => 'null', 'position' => 40,  'searchall' => 0, 'isameasure' => 1, 'help' => 'Help text'),
			 'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'visible' => 1, 'enabled' => 1, 'position' => 50, 'notnull' => -1, 'index' => 1, 'searchall' => 1, 'help' => 'LinkToThirdparty'),
			 'description' => array('type' => 'text',			'label' => 'Descrption',		 'enabled' => 1, 'visible' => 0,  'position' => 60),
			 'note_public' => array('type' => 'html',			'label' => 'NotePublic',		 'enabled' => 1, 'visible' => 0,  'position' => 61),
			 'note_private' => array('type' => 'html',			'label' => 'NotePrivate',		 'enabled' => 1, 'visible' => 0,  'position' => 62),
			 'date_creation' => array('type' => 'datetime',       'label' => 'DateCreation',     'enabled' => 1, 'visible' => -2, 'notnull' => 1,  'position' => 500),
			 'tms' => array('type' => 'timestamp',      'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1,  'position' => 501),
			 //'date_valid' => array('type' => 'datetime',       'label' => 'DateCreation',     'enabled' => 1, 'visible' => -2, 'position' => 502),
			 'fk_user_creat' => array('type' => 'integer',        'label' => 'UserAuthor',       'enabled' => 1, 'visible' => -2, 'notnull' => 1,  'position' => 510),
			 'fk_user_modif' => array('type' => 'integer',        'label' => 'UserModif',        'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 511),
			 //'fk_user_valid' => array('type' => 'integer',        'label' => 'UserValidation',   'enabled' => 1, 'visible' => -1, 'position' => 512),
			 'import_key' => array('type' => 'varchar(14)',    'label' => 'ImportId',         'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'index' => 0,  'position' => 1000),
			 'status' => array('type' => 'integer', 'label' => 'Status',           'enabled' => 1, 'visible' => 1,  'notnull' => 1,  'default' => 0, 'index' => 1,  'position' => 1000, 'arrayofkeyval' => array(0 => 'Draft', 1 => 'Active', -1 => 'Cancel')),
			 );*/

			$stringforproperties = '// BEGIN MODULEBUILDER PROPERTIES'."\n";
			$stringforproperties .= 'public $fields = array('."\n";
			$i = 10;
			while ($obj = $db->fetch_object($_results)) {
				// fieldname
				$fieldname = $obj->Field;
				// type
				$type = $obj->Type;
				if ($type == 'int(11)') {
					$type = 'integer';
				}
				if ($type == 'float') {
					$type = 'real';
				}
				if (strstr($type, 'tinyint')) {
					$type = 'integer';
				}
				if ($obj->Field == 'fk_soc') {
					$type = 'integer:Societe:societe/class/societe.class.php';
				}
				if (preg_match('/^fk_proj/', $obj->Field)) {
					$type = 'integer:Project:projet/class/project.class.php:1:fk_statut=1';
				}
				if (preg_match('/^fk_prod/', $obj->Field)) {
					$type = 'integer:Product:product/class/product.class.php:1';
				}
				if ($obj->Field == 'fk_warehouse') {
					$type = 'integer:Entrepot:product/stock/class/entrepot.class.php';
				}
				if (preg_match('/^(fk_user|fk_commercial)/', $obj->Field)) {
					$type = 'integer:User:user/class/user.class.php';
				}

				// notnull
				$notnull = ($obj->Null == 'YES' ? 0 : 1);
				if ($fieldname == 'fk_user_modif') {
					$notnull = -1;
				}
				// label
				$label = preg_replace('/_/', '', ucfirst($fieldname));
				if ($fieldname == 'rowid') {
					$label = 'TechnicalID';
				}
				if ($fieldname == 'import_key') {
					$label = 'ImportId';
				}
				if ($fieldname == 'fk_soc') {
					$label = 'ThirdParty';
				}
				if ($fieldname == 'tms') {
					$label = 'DateModification';
				}
				if ($fieldname == 'datec') {
					$label = 'DateCreation';
				}
				if ($fieldname == 'date_valid') {
					$label = 'DateValidation';
				}
				if ($fieldname == 'datev') {
					$label = 'DateValidation';
				}
				if ($fieldname == 'note_private') {
					$label = 'NotePublic';
				}
				if ($fieldname == 'note_public') {
					$label = 'NotePrivate';
				}
				if ($fieldname == 'fk_user_creat') {
					$label = 'UserAuthor';
				}
				if ($fieldname == 'fk_user_modif') {
					$label = 'UserModif';
				}
				if ($fieldname == 'fk_user_valid') {
					$label = 'UserValidation';
				}
				// visible
				$visible = -1;
				if (in_array($fieldname, array('ref', 'label'))) {
					$visible = 1;
				}
				if ($fieldname == 'entity') {
					$visible = -2;
				}
				if ($fieldname == 'entity') {
					$visible = -2;
				}
				if ($fieldname == 'import_key') {
					$visible = -2;
				}
				if ($fieldname == 'fk_user_creat') {
					$visible = -2;
				}
				if ($fieldname == 'fk_user_modif') {
					$visible = -2;
				}
				if (in_array($fieldname, array('ref_ext', 'model_pdf', 'note_public', 'note_private'))) {
					$visible = 0;
				}
				// enabled
				$enabled = 1;
				// default
				$default = '';
				if ($fieldname == 'entity') {
					$default = 1;
				}
				// position
				$position = $i;
				if (in_array($fieldname, array('status', 'statut', 'fk_status', 'fk_statut'))) {
					$position = 500;
				}
				if ($fieldname == 'import_key') {
					$position = 900;
				}
				// $alwayseditable
				if ($fieldname == 'label') {
					$alwayseditable = 1;
				} else {
					$alwayseditable = 0;
				}
				// index
				$index = 0;
				if ($fieldname == 'entity') {
					$index = 1;
				}
				// css, cssview, csslist
				$css = '';
				$cssview = '';
				$csslist = '';
				if (preg_match('/^fk_/', $fieldname)) {
					$css = 'maxwidth500 widthcentpercentminusxx';
				}
				if ($fieldname == 'label') {
					$css = 'minwidth300';
					$cssview = 'wordbreak';
				}
				if (in_array($fieldname, array('note_public', 'note_private'))) {
					$cssview = 'wordbreak';
				}
				if (in_array($fieldname, array('ref', 'label')) || preg_match('/integer:/', $type)) {
					$csslist = 'tdoverflowmax150';
				}

				// type
				$picto = $obj->Picto;
				if ($obj->Field == 'fk_soc') {
					$picto = 'company';
				}
				if (preg_match('/^fk_proj/', $obj->Field)) {
					$picto = 'project';
				}

				// Build the property string
				$stringforproperties .= "'".$obj->Field."' => array('type' => '".$type."', 'label' => '".$label."',";
				if ($default != '') {
					$stringforproperties .= " 'default' => ".$default.",";
				}
				$stringforproperties .= " 'enabled' => ".$enabled.",";
				$stringforproperties .= " 'visible' => ".$visible;
				if ($notnull) {
					$stringforproperties .= ", 'notnull' => ".$notnull;
				}
				if ($alwayseditable) {
					$stringforproperties .= ", 'alwayseditable' => 1";
				}
				if ($fieldname == 'ref' || $fieldname == 'code') {
					$stringforproperties .= ", 'showoncombobox' => 1";
				}
				$stringforproperties .= ", 'position' => ".$position;
				if ($index) {
					$stringforproperties .= ", 'index' => ".$index;
				}
				if ($picto) {
					$stringforproperties .= ", 'picto' => '".$picto."'";
				}
				if ($css) {
					$stringforproperties .= ", 'css' => '".$css."'";
				}
				if ($cssview) {
					$stringforproperties .= ", 'cssview' => '".$cssview."'";
				}
				if ($csslist) {
					$stringforproperties .= ", 'csslist' => '".$csslist."'";
				}
				$stringforproperties .= "),\n";
				$i += 5;
			}
			$stringforproperties .= ');'."\n";
			$stringforproperties .= '// END MODULEBUILDER PROPERTIES'."\n";
		}
	}

	$filetogenerate = array();  // For static analysis
	if (!$error) {
		// Copy some files
		$filetogenerate = array(
			'myobject_card.php' => strtolower($objectname).'_card.php',
			'myobject_note.php' => strtolower($objectname).'_note.php',
			'myobject_contact.php' => strtolower($objectname).'_contact.php',
			'myobject_document.php' => strtolower($objectname).'_document.php',
			'myobject_agenda.php' => strtolower($objectname).'_agenda.php',
			'myobject_list.php' => strtolower($objectname).'_list.php',
			'admin/myobject_extrafields.php' => 'admin/'.strtolower($objectname).'_extrafields.php',
			'lib/mymodule_myobject.lib.php' => 'lib/'.strtolower($module).'_'.strtolower($objectname).'.lib.php',
			//'test/phpunit/MyObjectTest.php' => 'test/phpunit/'.strtolower($objectname).'Test.php',
			'sql/llx_mymodule_myobject.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.sql',
			'sql/llx_mymodule_myobject.key.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.key.sql',
			'sql/llx_mymodule_myobject_extrafields.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.sql',
			'sql/llx_mymodule_myobject_extrafields.key.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.key.sql',
			//'scripts/mymodule.php' => 'scripts/'.strtolower($objectname).'.php',
			'class/myobject.class.php' => 'class/'.strtolower($objectname).'.class.php',
			//'class/api_mymodule.class.php' => 'class/api_'.strtolower($module).'.class.php',
		);

		if (GETPOST('includerefgeneration', 'aZ09')) {
			dol_mkdir($destdir.'/core/modules/'.strtolower($module));

			$filetogenerate += array(
				'core/modules/mymodule/mod_myobject_advanced.php' => 'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_advanced.php',
				'core/modules/mymodule/mod_myobject_standard.php' => 'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_standard.php',
				'core/modules/mymodule/modules_myobject.php' => 'core/modules/'.strtolower($module).'/modules_'.strtolower($objectname).'.php',
			);
		}
		if (GETPOST('includedocgeneration', 'aZ09')) {
			dol_mkdir($destdir.'/core/modules/'.strtolower($module));
			dol_mkdir($destdir.'/core/modules/'.strtolower($module).'/doc');

			$filetogenerate += array(
				'core/modules/mymodule/doc/doc_generic_myobject_odt.modules.php' => 'core/modules/'.strtolower($module).'/doc/doc_generic_'.strtolower($objectname).'_odt.modules.php',
				'core/modules/mymodule/doc/pdf_standard_myobject.modules.php' => 'core/modules/'.strtolower($module).'/doc/pdf_standard_'.strtolower($objectname).'.modules.php'
			);
		}
		if (GETPOST('generatepermissions', 'aZ09')) {
			$firstobjectname = 'myobject';
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
			dol_include_once($pathtofile);
			$class = 'mod'.$module;
			$moduleobj = null;
			if (class_exists($class)) {
				try {
					$moduleobj = new $class($db);
					'@phan-var-force DolibarrModules $moduleobj';
				} catch (Exception $e) {
					$error++;
					dol_print_error($db, $e->getMessage());
				}
			}
			if (is_object($moduleobj)) {
				$rights = $moduleobj->rights;
			} else {
				$rights = [];
			}
			$moduledescriptorfile = $destdir.'/core/modules/mod'.$module.'.class.php';
			$checkComment = checkExistComment($moduledescriptorfile, 1);
			if ($checkComment < 0) {
				setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Permissions"), "mod".$module."class.php"), null, 'warnings');
			} else {
				$generatePerms = reWriteAllPermissions($moduledescriptorfile, $rights, null, null, $objectname, $module, -2);
				if ($generatePerms < 0) {
					setEventMessages($langs->trans("WarningPermissionAlreadyExist", $langs->transnoentities($objectname)), null, 'warnings');
				}
			}
		}

		if (!$error) {
			foreach ($filetogenerate as $srcfile => $destfile) {
				$result = dol_copy($srcdir.'/'.$srcfile, $destdir.'/'.$destfile, $newmask, 0);
				if ($result <= 0) {
					if ($result < 0) {
						$warning++;
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorFailToCopyFile", $srcdir.'/'.$srcfile, $destdir.'/'.$destfile), null, 'errors');
					} else {
						// $result == 0
						setEventMessages($langs->trans("FileAlreadyExists", $destfile), null, 'warnings');
					}
				}
				$arrayreplacement = array(
					'/myobject\.class\.php/' => strtolower($objectname).'.class.php',
					'/myobject\.lib\.php/' => strtolower($objectname).'.lib.php',
				);

				dolReplaceInFile($destdir.'/'.$destfile, $arrayreplacement, '', '0', 0, 1);
			}
		}

		// Replace property section with $stringforproperties
		if (!$error && $stringforproperties) {
			//var_dump($stringforproperties);exit;
			$arrayreplacement = array(
				'/\/\/ BEGIN MODULEBUILDER PROPERTIES.*\/\/ END MODULEBUILDER PROPERTIES/ims' => $stringforproperties
			);

			dolReplaceInFile($destdir.'/class/'.strtolower($objectname).'.class.php', $arrayreplacement, '', '0', 0, 1);
		}

		// Edit the class 'class/'.strtolower($objectname).'.class.php'
		if (GETPOST('includerefgeneration', 'aZ09')) {
			// Replace 'visible' => 1, 'noteditable' => 0, 'default' => ''
			$arrayreplacement = array(
				'/\'visible\'s*=>s*1,\s*\'noteditable\'s*=>s*0,\s*\'default\'s*=>s*\'\'/' => "'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)'"
			);
			//var_dump($arrayreplacement);exit;
			//var_dump($destdir.'/class/'.strtolower($objectname).'.class.php');exit;
			dolReplaceInFile($destdir.'/class/'.strtolower($objectname).'.class.php', $arrayreplacement, '', '0', 0, 1);

			$arrayreplacement = array(
				'/\'models\' => 0,/' => '\'models\' => 1,'
			);
			dolReplaceInFile($destdir.'/core/modules/mod'.$module.'.class.php', $arrayreplacement, '', '0', 0, 1);
		}

		// Edit the setup file and the card page
		if (GETPOST('includedocgeneration', 'aZ09')) {
			// Replace some var init into some files
			$arrayreplacement = array(
				'/\$includedocgeneration = 0;/' => '$includedocgeneration = 1;'
			);
			dolReplaceInFile($destdir.'/class/'.strtolower($objectname).'.class.php', $arrayreplacement, '', '0', 0, 1);
			dolReplaceInFile($destdir.'/'.strtolower($objectname).'_card.php', $arrayreplacement, '', '0', 0, 1);

			$arrayreplacement = array(
				'/\'models\' => 0,/' => '\'models\' => 1,'
			);

			dolReplaceInFile($destdir.'/core/modules/mod'.$module.'.class.php', $arrayreplacement, '', '0', 0, 1);
		}

		// TODO Update entries '$myTmpObjects['MyObject'] = array('includerefgeneration' => 0, 'includedocgeneration' => 0);'


		// Scan for object class files
		$listofobject = dol_dir_list($destdir.'/class', 'files', 0, '\.class\.php$');

		$firstobjectname = '';
		foreach ($listofobject as $fileobj) {
			if (preg_match('/^api_/', $fileobj['name'])) {
				continue;
			}
			if (preg_match('/^actions_/', $fileobj['name'])) {
				continue;
			}

			$tmpcontent = file_get_contents($fileobj['fullname']);
			$reg = array();
			if (preg_match('/class\s+([^\s]*)\s+extends\s+CommonObject/ims', $tmpcontent, $reg)) {
				$objectnameloop = $reg[1];
				if (empty($firstobjectname)) {
					$firstobjectname = $objectnameloop;
				}
			}

			// Regenerate left menu entry in descriptor for $objectname
			$stringtoadd = "
		\$this->menu[\$r++] = array(
			'fk_menu' => 'fk_mainmenu=mymodule',
			'type' => 'left',
			'titre' => 'MyObject',
			'prefix' => img_picto('', \$this->picto, 'class=\"paddingright pictofixedwidth valignmiddle\"'),
			'mainmenu' => 'mymodule',
			'leftmenu' => 'myobject',
			'url' => '/mymodule/myobject_list.php',
			'langs' => 'mymodule@mymodule',
			'position' => 1000 + \$r,
			'enabled' => 'isModEnabled(\"mymodule\")',
			'perms' => '".(GETPOST('generatepermissions') ? '$user->hasRight("mymodule", "myobject", "read")' : '1')."',
			'target' => '',
			'user' => 2,
			'object' => 'MyObject'
		);
		\$this->menu[\$r++] = array(
			'fk_menu' => 'fk_mainmenu=mymodule,fk_leftmenu=myobject',
			'type' => 'left',
			'titre' => 'List MyObject',
			'mainmenu' => 'mymodule',
			'leftmenu' => 'mymodule_myobject_list',
			'url' => '/mymodule/myobject_list.php',
			'langs' => 'mymodule@mymodule',
			'position' => 1000 + \$r,
			'enabled' => 'isModEnabled(\"mymodule\")',
			'perms' => '".(GETPOST('generatepermissions') ? '$user->hasRight("mymodule", "myobject", "read")' : '1')."',
			'target' => '',
			'user' => 2,
			'object' => 'MyObject'
		);
		\$this->menu[\$r++] = array(
			'fk_menu' => 'fk_mainmenu=mymodule,fk_leftmenu=myobject',
			'type' => 'left',
			'titre' => 'New MyObject',
			'mainmenu' => 'mymodule',
			'leftmenu' => 'mymodule_myobject_new',
			'url' => '/mymodule/myobject_card.php?action=create',
			'langs' => 'mymodule@mymodule',
			'position' => 1000 + \$r,
			'enabled' => 'isModEnabled(\"mymodule\")',
			'perms' => '".(GETPOST('generatepermissions') ? '$user->hasRight("mymodule", "myobject", "write")' : '1')."',
			'target' => '',
			'user' => 2,
			'object' => 'MyObject'
		);";
			$stringtoadd = preg_replace('/MyObject/', $objectname, $stringtoadd);
			$stringtoadd = preg_replace('/mymodule/', strtolower($module), $stringtoadd);
			$stringtoadd = preg_replace('/myobject/', strtolower($objectname), $stringtoadd);

			$moduledescriptorfile = $destdir.'/core/modules/mod'.$module.'.class.php';
		}
		// TODO Allow a replace with regex using dolReplaceInFile with param arryreplacementisregex to 1
		// TODO Avoid duplicate addition

		// load class and check if menu exist with same object name
		$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
		dol_include_once($pathtofile);
		$class = 'mod'.$module;
		$moduleobj = null;
		if (class_exists($class)) {
			try {
				$moduleobj = new $class($db);
				'@phan-var-force DolibarrModules $moduleobj';
			} catch (Exception $e) {
				$error++;
				dol_print_error($db, $e->getMessage());
			}
		}
		if (is_object($moduleobj)) {
			$menus = $moduleobj->menu;
		} else {
			$menus = array();
		}
		$counter = 0 ;
		foreach ($menus as $menu) {
			if ($menu['leftmenu'] == strtolower($objectname)) {
				$counter++;
			}
		}
		if (!$counter) {
			$checkComment = checkExistComment($moduledescriptorfile, 0);
			if ($checkComment < 0) {
				$warning++;
				setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Menus"), basename($moduledescriptorfile)), null, 'warnings');
			} else {
				$arrayofreplacement = array('/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */' => '/* BEGIN MODULEBUILDER LEFTMENU '.strtoupper($objectname).' */'.$stringtoadd."\n\t\t".'/* END MODULEBUILDER LEFTMENU '.strtoupper($objectname).' */'."\n\t\t".'/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */');
				dolReplaceInFile($moduledescriptorfile, $arrayofreplacement);
			}
		}
		// Add module descriptor to list of files to replace "MyObject' string with real name of object.
		$filetogenerate[] = 'core/modules/mod'.$module.'.class.php';
	}

	if (!$error) {
		// Edit PHP files to make replacement
		foreach ($filetogenerate as $destfile) {
			$phpfileval['fullname'] = $destdir.'/'.$destfile;

			//var_dump($phpfileval['fullname']);
			$arrayreplacement = array(
				'mymodule' => strtolower($module),
				'MyModule' => $module,
				'MYMODULE' => strtoupper($module),
				'My module' => $module,
				'my module' => $module,
				'mon module' => $module,
				'Mon module' => $module,
				'htdocs/modulebuilder/template/' => strtolower($modulename),
				'myobject' => strtolower($objectname),
				'MyObject' => $objectname,
				//'MYOBJECT' => strtoupper($objectname),
				'---Replace with your own copyright and developer email---' => getLicenceHeader($user, $langs, $now)
			);

			if (getDolGlobalString('MODULEBUILDER_SPECIFIC_AUTHOR')) {
				$arrayreplacement['---Replace with your own copyright and developer email---'] = dol_print_date($now, '%Y').' ' . getDolGlobalString('MODULEBUILDER_SPECIFIC_AUTHOR');
			}

			$result = dolReplaceInFile($phpfileval['fullname'], $arrayreplacement);
			//var_dump($result);
			if ($result < 0) {
				setEventMessages($langs->trans("ErrorFailToMakeReplacementInto", $phpfileval['fullname']), null, 'errors');
			}
		}
	}

	if (!$error) {
		// Edit the class file to write properties
		$object = rebuildObjectClass($destdir, $module, $objectname, $newmask);

		if (is_numeric($object) && $object <= 0) {
			$pathoffiletoeditsrc = $destdir.'/class/'.strtolower($objectname).'.class.php';
			setEventMessages($langs->trans('ErrorFailToCreateFile', $pathoffiletoeditsrc), null, 'errors');
			$warning++;
		}
		// check if documentation was generate and add table of properties object
		$file = $destdir.'/class/'.strtolower($objectname).'.class.php';
		$destfile = $destdir.'/doc/Documentation.asciidoc';

		if (file_exists($destfile)) {
			writePropsInAsciiDoc($file, $objectname, $destfile);
		}
	}
	if (!$error) {
		// Edit sql with new properties
		$result = rebuildObjectSql($destdir, $module, $objectname, $newmask, '', $object);

		if ($result <= 0) {
			setEventMessages($langs->trans('ErrorFailToCreateFile', '.sql'), null);
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans('FilesForObjectInitialized', $objectname), null);
		$tabobj = $objectname;
	} else {
		$tabobj = 'newobject';
	}

	// check if module is enabled
	if (isModEnabled(strtolower($module))) {
		$result = unActivateModule(strtolower($module));
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
		if ($result) {
			setEventMessages($result, null, 'errors');
		}
		setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=objects&module='.$module);
		exit;
	}
}

// Add a dictionary
if ($dirins && $action == 'initdic' && $module && empty($cancel) && $user->hasRight("modulebuilder", "run")) {
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	$destdir = $dirins.'/'.strtolower($module);
	$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';

	if (!GETPOST('dicname')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Table")), null, 'errors');
	}
	if (!GETPOST('label')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	if (!$error) {
		$newdicname = $dicname;
		if (!preg_match('/^c_/', $newdicname)) {
			$newdicname = 'c_'.$dicname;
		}
		dol_include_once($pathtofile);
		$class = 'mod'.$module;

		if (class_exists($class)) {
			try {
				$moduleobj = new $class($db);
				'@phan-var-force DolibarrModules $moduleobj';
			} catch (Exception $e) {
				$error++;
				dol_print_error($db, $e->getMessage());
			}
		} else {
			$error++;
			$langs->load("errors");
			dol_print_error($db, $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
			exit;
		}
		$dictionaries = $moduleobj->dictionaries;
		$checkComment = checkExistComment($moduledescriptorfile, 2);
		if ($checkComment < 0) {
			setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Dictionaries"), "mod".$module."class.php"), null, 'warnings');
		} else {
			createNewDictionnary($module, $moduledescriptorfile, $newdicname, $dictionaries);
			if (function_exists('opcache_invalidate')) {
				opcache_reset();	// remove the include cache hell !
			}
			clearstatcache(true);
			header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : ''));
			exit;
		}
	}
}

// Delete a SQL table
if ($dirins && ($action == 'droptable' || $action == 'droptableextrafields') && !empty($module) && !empty($tabobj) && $user->hasRight("modulebuilder", "run")) {
	$objectname = $tabobj;

	$arrayoftables = array();
	if ($action == 'droptable') {	// Test on permission already done
		$arrayoftables[] = MAIN_DB_PREFIX.strtolower($module).'_'.strtolower($tabobj);
	}
	if ($action == 'droptableextrafields') {	// Test on permission already done
		$arrayoftables[] = MAIN_DB_PREFIX.strtolower($module).'_'.strtolower($tabobj).'_extrafields';
	}

	foreach ($arrayoftables as $tabletodrop) {
		$nb = -1;
		$sql = "SELECT COUNT(*) as nb FROM ".$tabletodrop;
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$nb = $obj->nb;
			}
		} else {
			if ($db->lasterrno() == 'DB_ERROR_NOSUCHTABLE') {
				setEventMessages($langs->trans("TableDoesNotExists", $tabletodrop), null, 'warnings');
			} else {
				dol_print_error($db);
			}
		}
		if ($nb == 0) {
			$resql = $db->DDLDropTable($tabletodrop);
			//var_dump($resql);
			setEventMessages($langs->trans("TableDropped", $tabletodrop), null, 'mesgs');
		} elseif ($nb > 0) {
			setEventMessages($langs->trans("TableNotEmptyDropCanceled", $tabletodrop), null, 'warnings');
		}
	}
}

if ($dirins && $action == 'addproperty' && empty($cancel) && !empty($module) && (!empty($tabobj) || !empty(GETPOST('obj'))) && $user->hasRight("modulebuilder", "run")) {
	$error = 0;

	$objectname = (GETPOST('obj') ? GETPOST('obj') : $tabobj);

	$dirins = $dirread = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$moduletype = $listofmodules[strtolower($module)]['moduletype'];

	$srcdir = $dirread.'/'.strtolower($module);
	$destdir = $dirins.'/'.strtolower($module);
	dol_mkdir($destdir);

	$objects = dolGetListOfObjectClasses($destdir);
	if (!in_array($objectname, array_values($objects))) {
		$error++;
		setEventMessages($langs->trans("ErrorObjectNotFound", $langs->transnoentities($objectname)), null, 'errors');
	}

	$addfieldentry = array();

	// We click on add property
	if (!GETPOST('regenerateclasssql') && !GETPOST('regeneratemissing')) {
		if (!GETPOST('propname', 'aZ09')) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Name")), null, 'errors');
		}
		if (!GETPOST('proplabel', 'alpha')) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
		}
		if (!GETPOST('proptype', 'alpha')) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Type")), null, 'errors');
		}


		if (!$error && !GETPOST('regenerateclasssql') && !GETPOST('regeneratemissing')) {
			$addfieldentry = array(
				'name' => GETPOST('propname', 'aZ09'),
				'label' => GETPOST('proplabel', 'alpha'),
				'type' => strtolower(GETPOST('proptype', 'alpha')),
				'arrayofkeyval' => GETPOST('proparrayofkeyval', 'alphawithlgt'), // Example json string '{"0":"Draft","1":"Active","-1":"Cancel"}'
				'visible' => GETPOST('propvisible', 'alphanohtml'),
				'enabled' => GETPOST('propenabled', 'alphanohtml'),
				'position' => GETPOSTINT('propposition'),
				'notnull' => GETPOSTINT('propnotnull'),
				'index' => GETPOSTINT('propindex'),
				'foreignkey' => GETPOST('propforeignkey', 'alpha'),
				'searchall' => GETPOSTINT('propsearchall'),
				'isameasure' => GETPOSTINT('propisameasure'),
				'comment' => GETPOST('propcomment', 'alpha'),
				'help' => GETPOST('prophelp', 'alpha'),
				'css' => GETPOST('propcss', 'alpha'),        // Can be 'maxwidth500 widthcentpercentminusxx' for example
				'cssview' => GETPOST('propcssview', 'alpha'),
				'csslist' => GETPOST('propcsslist', 'alpha'),
				'default' => GETPOST('propdefault', 'restricthtml'),
				'noteditable' => GETPOSTINT('propnoteditable'),
				//'alwayseditable' => GETPOSTINT('propalwayseditable'),
				'validate' => GETPOSTINT('propvalidate')
			);

			if (!empty($addfieldentry['arrayofkeyval']) && !is_array($addfieldentry['arrayofkeyval'])) {
				$tmpdecode = json_decode($addfieldentry['arrayofkeyval'], true);
				if ($tmpdecode) {	// If string is already a json
					$addfieldentry['arrayofkeyval'] = $tmpdecode;
				} else {			// If string is a list of lines with "key,value"
					$tmparray = dolExplodeIntoArray($addfieldentry['arrayofkeyval'], "\n", ",");
					$addfieldentry['arrayofkeyval'] = $tmparray;
				}
			}
		}
	}

	/*if (GETPOST('regeneratemissing'))
	{
		setEventMessages($langs->trans("FeatureNotYetAvailable"), null, 'warnings');
		$error++;
	}*/

	$moduletype = $listofmodules[strtolower($module)]['moduletype'];

	// Edit the class file to write properties
	if (!$error) {
		$object = rebuildObjectClass($destdir, $module, $objectname, $newmask, $srcdir, $addfieldentry, $moduletype);

		if (is_numeric($object) && $object <= 0) {
			$pathoffiletoeditsrc = $destdir.'/class/'.strtolower($objectname).'.class.php';
			setEventMessages($langs->trans('ErrorFailToCreateFile', $pathoffiletoeditsrc), null, 'errors');
			$error++;
		}
	}

	// Edit sql with new properties
	if (!$error) {
		$result = rebuildObjectSql($destdir, $module, $objectname, $newmask, $srcdir, $object, $moduletype);

		if ($result <= 0) {
			setEventMessages($langs->trans('ErrorFailToCreateFile', '.sql'), null, 'errors');
			$error++;
		}
	}

	if (!$error) {
		clearstatcache(true);

		setEventMessages($langs->trans('FilesForObjectUpdated', $objectname), null);

		setEventMessages($langs->trans('WarningDatabaseIsNotUpdated'), null);

		// Make a redirect to reload all data
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj='.$objectname.'&nocache='.time());
		exit;
	}
}

if ($dirins && $action == 'confirm_deleteproperty' && $propertykey && $user->hasRight("modulebuilder", "run")) {
	$objectname = $tabobj;

	$dirins = $dirread = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$moduletype = $listofmodules[strtolower($module)]['moduletype'];

	$srcdir = $dirread.'/'.strtolower($module);
	$destdir = $dirins.'/'.strtolower($module);
	dol_mkdir($destdir);

	// Edit the class file to write properties
	if (!$error) {
		$object = rebuildObjectClass($destdir, $module, $objectname, $newmask, $srcdir, array(), $propertykey);

		if (is_numeric($object) && $object <= 0) {
			$pathoffiletoeditsrc = $destdir.'/class/'.strtolower($objectname).'.class.php';
			setEventMessages($langs->trans('ErrorFailToCreateFile', $pathoffiletoeditsrc), null, 'errors');
			$error++;
		}
	}

	// Edit sql with new properties
	if (!$error) {
		$result = rebuildObjectSql($destdir, $module, $objectname, $newmask, $srcdir, $object);

		if ($result <= 0) {
			setEventMessages($langs->trans('ErrorFailToCreateFile', '.sql'), null, 'errors');
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans('FilesForObjectUpdated', $objectname), null);

		clearstatcache(true);

		// Make a redirect to reload all data
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj='.$objectname);
		exit;
	}
}

if ($dirins && $action == 'confirm_deletemodule' && $user->hasRight("modulebuilder", "run")) {
	if (preg_match('/[^a-z0-9_]/i', $module)) {
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
	}

	if (!$error) {
		$modulelowercase = strtolower($module);

		// Dir for module
		$dir = $dirins.'/'.$modulelowercase;

		$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

		// Dir for module
		$dir = dol_buildpath($modulelowercase, 0);

		// Zip file to build
		$FILENAMEZIP = '';

		// Load module
		dol_include_once($pathtofile);
		$class = 'mod'.$module;

		$moduleobj = null;

		if (class_exists($class)) {
			try {
				$moduleobj = new $class($db);
				'@phan-var-force DolibarrMOdules $moduleobj';
			} catch (Exception $e) {
				$error++;
				dol_print_error($db, $e->getMessage());
			}
		} else {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module), null, 'warnings');
		}

		if ($moduleobj) {
			$moduleobj->remove();
		}

		$result = dol_delete_dir_recursive($dir);

		if ($result > 0) {
			setEventMessages($langs->trans("DirWasRemoved", $modulelowercase), null);

			clearstatcache(true);
			if (function_exists('opcache_invalidate')) {
				opcache_reset();	// remove the include cache hell !
			}

			header("Location: ".$_SERVER["PHP_SELF"].'?module=deletemodule');
			exit;
		} else {
			setEventMessages($langs->trans("PurgeNothingToDelete"), null, 'warnings');
		}
	}

	$action = '';
	$module = 'deletemodule';
}

if ($dirins && $action == 'confirm_deleteobject' && $objectname && $user->hasRight("modulebuilder", "run")) {
	if (preg_match('/[^a-z0-9_]/i', $objectname)) {
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
	}

	if (!$error) {
		$modulelowercase = strtolower($module);
		$objectlowercase = strtolower($objectname);

		// Dir for module
		$dir = $dirins.'/'.$modulelowercase;

		// Delete some files
		$filetodelete = array(
			'myobject_card.php' => strtolower($objectname).'_card.php',
			'myobject_note.php' => strtolower($objectname).'_note.php',
			'myobject_contact.php' => strtolower($objectname).'_contact.php',
			'myobject_document.php' => strtolower($objectname).'_document.php',
			'myobject_agenda.php' => strtolower($objectname).'_agenda.php',
			'myobject_list.php' => strtolower($objectname).'_list.php',
			'admin/myobject_extrafields.php' => 'admin/'.strtolower($objectname).'_extrafields.php',
			'lib/mymodule_myobject.lib.php' => 'lib/'.strtolower($module).'_'.strtolower($objectname).'.lib.php',
			'test/phpunit/MyObjectTest.php' => 'test/phpunit/'.strtolower($objectname).'Test.php',
			'sql/llx_mymodule_myobject.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.sql',
			'sql/llx_mymodule_myobject_extrafields.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.sql',
			'sql/llx_mymodule_myobject.key.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.key.sql',
			'sql/llx_mymodule_myobject_extrafields.key.sql' => 'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.key.sql',
			'scripts/myobject.php' => 'scripts/'.strtolower($objectname).'.php',
			'class/myobject.class.php' => 'class/'.strtolower($objectname).'.class.php',
			'class/api_myobject.class.php' => 'class/api_'.strtolower($module).'.class.php',
			'core/modules/mymodule/mod_myobject_advanced.php' => 'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_advanced.php',
			'core/modules/mymodule/mod_myobject_standard.php' => 'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_standard.php',
			'core/modules/mymodule/modules_myobject.php' => 'core/modules/'.strtolower($module).'/modules_'.strtolower($objectname).'.php',
			'core/modules/mymodule/doc/doc_generic_myobject_odt.modules.php' => 'core/modules/'.strtolower($module).'/doc/doc_generic_'.strtolower($objectname).'_odt.modules.php',
			'core/modules/mymodule/doc/pdf_standard_myobject.modules.php' => 'core/modules/'.strtolower($module).'/doc/pdf_standard_'.strtolower($objectname).'.modules.php'
		);

		//menu for the object selected
		// load class and check if menu,permission,documentation exist for this object
		$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
		dol_include_once($pathtofile);
		$class = 'mod'.$module;
		$moduleobj =  null;
		if (class_exists($class)) {
			try {
				$moduleobj = new $class($db);
				'@phan-var-force DolibarrMOdules $moduleobj';
			} catch (Exception $e) {
				$error++;
				dol_print_error($db, $e->getMessage());
			}
		}
		$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';

		// delete menus linked to the object
		$menus = $moduleobj->menu;
		$rewriteMenu = checkExistComment($moduledescriptorfile, 0);

		if ($rewriteMenu < 0) {
			setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Menus"), "mod".$module."class.php"), null, 'warnings');
		} else {
			reWriteAllMenus($moduledescriptorfile, $menus, $objectname, null, -1);
		}

		// regenerate permissions and delete them
		$permissions = $moduleobj->rights;
		$rewritePerms = checkExistComment($moduledescriptorfile, 1);
		if ($rewritePerms < 0) {
			setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Permissions"), "mod".$module."class.php"), null, 'warnings');
		} else {
			reWriteAllPermissions($moduledescriptorfile, $permissions, null, null, $objectname, '', -1);
		}
		if ($rewritePerms && $rewriteMenu) {
			// check if documentation has been generated
			$file_doc = $dirins.'/'.strtolower($module).'/doc/Documentation.asciidoc';
			deletePropsAndPermsFromDoc($file_doc, $objectname);

			clearstatcache(true);
			if (function_exists('opcache_invalidate')) {
				opcache_reset();	// remove the include cache hell !
			}
			$resultko = 0;
			foreach ($filetodelete as $tmpfiletodelete) {
				$resulttmp = dol_delete_file($dir.'/'.$tmpfiletodelete, 0, 0, 1);
				$resulttmp = dol_delete_file($dir.'/'.$tmpfiletodelete.'.back', 0, 0, 1);
				if (!$resulttmp) {
					$resultko++;
				}
			}

			if ($resultko == 0) {
				setEventMessages($langs->trans("FilesDeleted"), null);
			} else {
				setEventMessages($langs->trans("ErrorSomeFilesCouldNotBeDeleted"), null, 'warnings');
			}
		}
	}

	$action = '';
	if (! $error) {
		$tabobj = 'newobject';
	} else {
		$tabobj = 'deleteobject';
	}

	// check if module is enabled
	if (isModEnabled(strtolower($module))) {
		$result = unActivateModule(strtolower($module));
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
		if ($result) {
			setEventMessages($result, null, 'errors');
		}
		setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=objects&tabobj=deleteobject&module='.urlencode($module));
		exit;
	}
}

if (($dirins && $action == 'confirm_deletedictionary' && $dicname) || ($dirins && $action == 'confirm_deletedictionary' && GETPOST('dictionnarykey')) && $user->hasRight("modulebuilder", "run")) {
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	$destdir = $dirins.'/'.strtolower($module);
	$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';

	if (preg_match('/[^a-z0-9_]/i', $dicname)) {
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
	}

	if (!empty($dicname)) {
		$newdicname = $dicname;
		if (!preg_match('/^c_/', $newdicname)) {
			$newdicname = 'c_'.strtolower($dicname);
		}
	} else {
		$newdicname = null;
	}

	dol_include_once($pathtofile);
	$class = 'mod'.$module;

	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrModules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	} else {
		$error++;
		$langs->load("errors");
		dol_print_error($db, $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
		exit;
	}

	$dicts = $moduleobj->dictionaries;
	$checkComment = checkExistComment($moduledescriptorfile, 2);
	if ($checkComment < 0) {
		$error++;
		setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Dictionaries"), "mod".$module."class.php"), null, 'warnings');
	}

	if (!empty(GETPOST('dictionnarykey'))) {
		$newdicname = $dicts['tabname'][GETPOSTINT('dictionnarykey') - 1];
	}

	// Lookup the table dicname
	$checkTable = false;
	if ($newdicname !== null) {
		$checkTable = $db->DDLDescTable(MAIN_DB_PREFIX.strtolower($newdicname));
	}

	if (is_bool($checkTable) || $db->num_rows($checkTable) <= 0) {	 // @phpstan-ignore-line
		$error++;
	}

	// search the key by name
	$keyToDelete = null;
	foreach ($dicts['tabname'] as $key => $table) {
		//var_dump($table."  ///////  ".$newdicname);exit;
		if (strtolower($table) === $newdicname) {
			$keyToDelete = $key;
			break;
		}
	}
	// delete all dicname's key values from the dictionary
	if ($keyToDelete !== null) {
		$keysToDelete = ['tabname', 'tablib', 'tabsql', 'tabsqlsort', 'tabfield', 'tabfieldvalue', 'tabfieldinsert', 'tabrowid', 'tabcond', 'tabhelp'];
		foreach ($keysToDelete as $key) {
			unset($dicts[$key][$keyToDelete]);
		}
	} else {
		$error++;
		setEventMessages($langs->trans("ErrorDictionaryNotFound", ucfirst($dicname)), null, 'errors');
	}
	if (!$error) {
		// delete table
		$_results = $db->DDLDropTable(MAIN_DB_PREFIX.strtolower($newdicname));
		if ($_results < 0) {
			dol_print_error($db);
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorTableNotFound", $newdicname), null, 'errors');
		}
		// rebuild file after update dictionaries
		$result = updateDictionaryInFile($module, $moduledescriptorfile, $dicts);
		if ($result > 0) {
			setEventMessages($langs->trans("DictionaryDeleted", ucfirst(substr($newdicname, 2))), null);
		}
		if (function_exists('opcache_invalidate')) {
			opcache_reset();	// remove the include cache hell !
		}
		clearstatcache(true);
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : ''));
		exit;
	}
}
if ($dirins && $action == 'updatedictionary' && GETPOST('dictionnarykey') && $user->hasRight("modulebuilder", "run")) {
	$keydict = GETPOSTINT('dictionnarykey') - 1 ;

	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	$destdir = $dirins.'/'.strtolower($module);
	$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
	dol_include_once($pathtofile);
	$class = 'mod'.$module;

	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrMOdules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	} else {
		$error++;
		$langs->load("errors");
		dol_print_error($db, $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
		exit;
	}

	$dicts = $moduleobj->dictionaries;
	if (!empty(GETPOST('tablib')) && GETPOST('tablib') !== $dicts['tablib'][$keydict]) {
		$dicts['tablib'][$keydict] = ucfirst(strtolower(GETPOST('tablib')));
		$checkComment = checkExistComment($moduledescriptorfile, 2);
		if ($checkComment < 0) {
			setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Dictionaries"), "mod".$module."class.php"), null, 'warnings');
		} else {
			$updateDict = updateDictionaryInFile($module, $moduledescriptorfile, $dicts);
			if ($updateDict > 0) {
				setEventMessages($langs->trans("DictionaryNameUpdated", ucfirst(GETPOST('tablib'))), null);
			}
			if (function_exists('opcache_invalidate')) {
				opcache_reset();	// remove the include cache hell !
			}
			clearstatcache(true);
			header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : ''));
			exit;
		}
	}
	//var_dump(GETPOST('tablib'));exit;
}
if ($dirins && $action == 'generatedoc' && $user->hasRight("modulebuilder", "run")) {
	$modulelowercase = strtolower($module);

	// Dir for module
	$dirofmodule = dol_buildpath($modulelowercase, 0).'/doc';

	$FILENAMEDOC = strtolower($module).'.html';

	$util = new Utils($db);
	$result = $util->generateDoc($module);

	if ($result > 0) {
		setEventMessages($langs->trans("DocFileGeneratedInto", $dirofmodule), null);
	} else {
		setEventMessages($util->error, $util->errors, 'errors');
	}
}

if ($dirins && $action == 'generatepackage' && $user->hasRight("modulebuilder", "run")) {
	$modulelowercase = strtolower($module);

	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

	// Dir for module
	$dir = dol_buildpath($modulelowercase, 0);

	// Zip file to build
	$FILENAMEZIP = '';

	// Load module
	dol_include_once($pathtofile);
	$class = 'mod'.$module;

	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrMOdules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	} else {
		$error++;
		$langs->load("errors");
		dol_print_error($db, $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
		exit;
	}

	$arrayversion = explode('.', $moduleobj->version, 3);
	if (count($arrayversion)) {
		$FILENAMEZIP = "module_".$modulelowercase.'-'.$arrayversion[0].(empty($arrayversion[1]) ? '.0' : '.'.$arrayversion[1]).(empty($arrayversion[2]) ? '' : '.'.$arrayversion[2]).'.zip';

		$dirofmodule = dol_buildpath($modulelowercase, 0).'/bin';
		$outputfilezip = $dirofmodule.'/'.$FILENAMEZIP;
		if ($dirofmodule) {
			if (!dol_is_dir($dirofmodule)) {
				dol_mkdir($dirofmodule);
			}
			// Note: We exclude /bin/ to not include the already generated zip
			$result = dol_compress_dir($dir, $outputfilezip, 'zip', '/\/bin\/|\.git|\.old|\.back|\.ssh/', $modulelowercase);
		} else {
			$result = -1;
		}

		if ($result > 0) {
			setEventMessages($langs->trans("ZipFileGeneratedInto", $outputfilezip), null);
		} else {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFailToGenerateFile", $outputfilezip), null, 'errors');
		}
	} else {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorCheckVersionIsDefined"), null, 'errors');
	}
}

// Add permission
if ($dirins && $action == 'addright' && !empty($module) && empty($cancel) && $user->hasRight("modulebuilder", "run")) {
	$error = 0;

	// load class and check if right exist
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	dol_include_once($pathtofile);
	$class = 'mod'.$module;
	$moduleobj = null;
	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrModules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	}

	// verify information entered
	if (!GETPOST('label', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	if (!GETPOST('permissionObj', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Rights")), null, 'errors');
	}

	$id = GETPOST('id', 'alpha');
	$label = GETPOST('label', 'alpha');
	$objectForPerms = strtolower(GETPOST('permissionObj', 'alpha'));
	$crud = GETPOST('crud', 'alpha');

	//check existing object permission
	$counter = 0;
	$permsForObject = array();
	if (is_object($moduleobj)) {
		$permissions = $moduleobj->rights;
	} else {
		$permissions = array();
	}
	$allObject = array();

	$countPerms = count($permissions);

	for ($i = 0; $i < $countPerms; $i++) {
		if ($permissions[$i][4] == $objectForPerms) {
			$counter++;
			if (count($permsForObject) < 3) {
				$permsForObject[] = $permissions[$i];
			}
		}
		$allObject[] = $permissions[$i][4];
	}

	// check if label of object already exists
	$countPermsObj = count($permsForObject);
	for ($j = 0; $j < $countPermsObj; $j++) {
		if (in_array($crud, $permsForObject[$j])) {
			$error++;
			setEventMessages($langs->trans("ErrorExistingPermission", $langs->transnoentities($crud), $langs->transnoentities($objectForPerms)), null, 'errors');
		}
	}

	$rightToAdd = array();
	if (!$error) {
		$key = $countPerms + 1;
		//prepare right to add
		$rightToAdd = array(
			0 => $id,
			1 => $label,
			4 => $objectForPerms,
			5 => $crud
		);

		if (isModEnabled(strtolower($module))) {
			$result = unActivateModule(strtolower($module));
			dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
			if ($result) {
				setEventMessages($result, null, 'errors');
			}
			setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
		}
	}
	$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
	//rewriting all permissions after add a right
	$rewrite = checkExistComment($moduledescriptorfile, 1);
	if ($rewrite < 0) {
		setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Permissions"), "mod".$module."class.php"), null, 'warnings');
	} else {
		reWriteAllPermissions($moduledescriptorfile, $permissions, $key, $rightToAdd, '', '', 1);
		setEventMessages($langs->trans('PermissionAddedSuccesfuly'), null);

		clearstatcache(true);
		if (function_exists('opcache_invalidate')) {
			opcache_reset();	// remove the include cache hell !
		}
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=permissions&module='.$module);
		exit;
	}
}


// Update permission
if ($dirins && GETPOST('action') == 'update_right' && GETPOST('modifyright') && empty($cancel) && $user->hasRight("modulebuilder", "run")) {
	$error = 0;
	// load class and check if right exist
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	dol_include_once($pathtofile);
	$class = 'mod'.$module;
	$moduleobj = null;
	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrModules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	}
	// verify information entered
	if (!GETPOST('label', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	if (!GETPOST('permissionObj', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Rights")), null, 'errors');
	}

	$label = GETPOST('label', 'alpha');
	$objectForPerms = strtolower(GETPOST('permissionObj', 'alpha'));
	$crud = GETPOST('crud', 'alpha');


	if ($label == "Read objects of $module" && $crud != "read") {
		$crud = "read";
		// $label = "Read objects of $module";
	}
	if ($label == "Create/Update objects of $module" && $crud != "write") {
		$crud = "write";
		// $label = "Create/Update objects of $module";
	}
	if ($label == "Delete objects of $module" && $crud != "delete") {
		$crud = "delete";
		// $label = "Delete objects of $module";
	}

	if (is_object($moduleobj)) {
		$permissions = $moduleobj->rights;
	} else {
		$permissions = [];
	}
	$key = GETPOSTINT('counter') - 1;
	//get permission want to delete from permissions array
	if (array_key_exists($key, $permissions)) {
		$x1 = $permissions[$key][1];
		$x2 = $permissions[$key][4];
		$x3 = $permissions[$key][5];
	} else {
		$x1 = null;
		$x2 = null;
		$x3 = null;
	}
	//check existing object permission
	$counter = 0;
	$permsForObject = array();
	// $permissions = $moduleobj->rights;  // Already fetched above
	$firstRight = 0;
	$existRight = 0;
	$allObject = array();

	$countPerms = count($permissions);
	for ($i = 0; $i < $countPerms; $i++) {
		if ($permissions[$i][4] == $objectForPerms) {
			$counter++;
			if (count($permsForObject) < 3) {
				$permsForObject[] = $permissions[$i];
			}
		}
		$allObject[] = $permissions[$i][4];
	}

	if ($label != $x1 && $crud != $x3) {
		$countPermsObj = count($permsForObject);
		for ($j = 0; $j < $countPermsObj; $j++) {
			if (in_array($label, $permsForObject[$j])) {
				$error++;
				setEventMessages($langs->trans("ErrorExistingPermission", $langs->transnoentities($label), $langs->transnoentities($objectForPerms)), null, 'errors');
			}
		}
	}

	if (!$error) {
		if (isModEnabled(strtolower($module))) {
			$result = unActivateModule(strtolower($module));
			dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
			if ($result) {
				setEventMessages($result, null, 'errors');
			}
			setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
		}

		$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		// rewriting all permissions after update permission needed
		$rewrite = checkExistComment($moduledescriptorfile, 1);
		if ($rewrite < 0) {
			setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Permissions"), "mod".$module."class.php"), null, 'warnings');
		} else {
			$rightUpdated = null;  // I not set at this point
			reWriteAllPermissions($moduledescriptorfile, $permissions, $key, $rightUpdated, '', '', 2);
			setEventMessages($langs->trans('PermissionUpdatedSuccesfuly'), null);
			clearstatcache(true);
			if (function_exists('opcache_invalidate')) {
				opcache_reset();	// remove the include cache hell !
			}
			header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=permissions&module='.$module);
			exit;
		}
	}
}
// Delete permission
if ($dirins && $action == 'confirm_deleteright' && !empty($module) && GETPOSTINT('permskey') && $user->hasRight("modulebuilder", "run")) {
	$error = 0;
	// load class and check if right exist
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	dol_include_once($pathtofile);
	$class = 'mod'.$module;
	$moduleobj = null;
	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrMOdules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	}

	$permissions = $moduleobj->rights;
	$key = GETPOSTINT('permskey') - 1;

	if (!$error) {
		// check if module is enabled
		if (isModEnabled(strtolower($module))) {
			$result = unActivateModule(strtolower($module));
			dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
			if ($result) {
				setEventMessages($result, null, 'errors');
			}
			setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
			header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=permissions&module='.$module);
			exit;
		}

		// rewriting all permissions
		$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		$rewrite = checkExistComment($moduledescriptorfile, 1);
		if ($rewrite < 0) {
			setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Permissions"), "mod".$module."class.php"), null, 'warnings');
		} else {
			reWriteAllPermissions($moduledescriptorfile, $permissions, $key, null, '', '', 0);
			setEventMessages($langs->trans('PermissionDeletedSuccesfuly'), null);

			clearstatcache(true);
			if (function_exists('opcache_invalidate')) {
				opcache_reset();	// remove the include cache hell !
			}

			header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=permissions&module='.$module);
			exit;
		}
	}
}
// Save file
if ($action == 'savefile' && empty($cancel) && $user->hasRight("modulebuilder", "run")) {
	$relofcustom = basename($dirins);

	if ($relofcustom) {
		// Check that relative path ($file) start with name 'custom'
		if (!preg_match('/^'.$relofcustom.'/', $file)) {
			$file = $relofcustom.'/'.$file;
		}

		$pathoffile = dol_buildpath($file, 0);
		$pathoffilebackup = dol_buildpath($file.'.back', 0);

		// Save old version
		if (dol_is_file($pathoffile)) {
			dol_copy($pathoffile, $pathoffilebackup, 0, 1);
		}

		$check = 'restricthtml';
		$srclang = dol_mimetype($pathoffile, '', 3);
		if ($srclang == 'md') {
			$check = 'restricthtml';
		}
		if ($srclang == 'lang') {
			$check = 'restricthtml';
		}
		if ($srclang == 'php') {
			$check = 'none';
		}

		$content = GETPOST('editfilecontent', $check);

		// Save file on disk
		if ($content) {
			dol_delete_file($pathoffile);
			$result = file_put_contents($pathoffile, $content);
			if ($result) {
				dolChmod($pathoffile, $newmask);

				setEventMessages($langs->trans("FileSaved"), null);
			} else {
				setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
			}
		} else {
			setEventMessages($langs->trans("ContentCantBeEmpty"), null, 'errors');
			//$action='editfile';
			$error++;
		}
	}
}

// Enable module
if ($action == 'set' && $user->admin && $user->hasRight("modulebuilder", "run")) {
	$param = '';
	if ($module) {
		$param .= '&module='.urlencode($module);
	}
	if ($tab) {
		$param .= '&tab='.urlencode($tab);
	}
	if ($tabobj) {
		$param .= '&tabobj='.urlencode($tabobj);
	}

	$value = GETPOST('value', 'alpha');
	$resarray = activateModule($value);
	if (!empty($resarray['errors'])) {
		setEventMessages('', $resarray['errors'], 'errors');
	} else {
		//var_dump($resarray);exit;
		if ($resarray['nbperms'] > 0) {
			$tmpsql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."user WHERE admin <> 1";
			$resqltmp = $db->query($tmpsql);
			if ($resqltmp) {
				$obj = $db->fetch_object($resqltmp);
				//var_dump($obj->nb);exit;
				if ($obj && $obj->nb > 1) {
					$msg = $langs->trans('ModuleEnabledAdminMustCheckRights');
					setEventMessages($msg, null, 'warnings');
				}
			} else {
				dol_print_error($db);
			}
		}
	}
	header("Location: ".$_SERVER["PHP_SELF"]."?".$param);
	exit;
}

// Disable module
if ($action == 'reset' && $user->admin && $user->hasRight("modulebuilder", "run")) {
	$param = '';
	if ($module) {
		$param .= '&module='.urlencode($module);
	}
	if ($tab) {
		$param .= '&tab='.urlencode($tab);
	}
	if ($tabobj) {
		$param .= '&tabobj='.urlencode($tabobj);
	}

	$value = GETPOST('value', 'alpha');
	$result = unActivateModule($value);
	if ($result) {
		setEventMessages($result, null, 'errors');
	}
	header("Location: ".$_SERVER["PHP_SELF"]."?".$param);
	exit;
}

// delete menu
if ($dirins && $action == 'confirm_deletemenu' && GETPOSTINT('menukey') && $user->hasRight("modulebuilder", "run")) {
	// check if module is enabled
	if (isModEnabled(strtolower($module))) {
		$result = unActivateModule(strtolower($module));
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
		if ($result) {
			setEventMessages($result, null, 'errors');
		}
		setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=menus&module='.$module);
		exit;
	}
	// load class and check if menu exist
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	dol_include_once($pathtofile);
	$class = 'mod'.$module;
	$moduleobj = null;
	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrMOdules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	}
	// get all objects and convert value to lower case for compare
	$dir = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$destdir = $dir.'/'.strtolower($module);
	$objects = dolGetListOfObjectClasses($destdir);
	$result = array_map('strtolower', $objects);

	$menus = $moduleobj->menu;
	$key = GETPOSTINT('menukey');
	$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';

	$checkcomment = checkExistComment($moduledescriptorfile, 0);
	if ($checkcomment < 0) {
		setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Menus"), "mod".$module."class.php"), null, 'warnings');
	} else {
		if ($menus[$key]['fk_menu'] === 'fk_mainmenu='.strtolower($module)) {
			if (in_array(strtolower($menus[$key]['leftmenu']), $result)) {
				reWriteAllMenus($moduledescriptorfile, $menus, $menus[$key]['leftmenu'], $key, -1);
			} else {
				reWriteAllMenus($moduledescriptorfile, $menus, null, $key, 0);
			}
		} else {
			reWriteAllMenus($moduledescriptorfile, $menus, null, $key, 0);
		}

		clearstatcache(true);
		if (function_exists('opcache_invalidate')) {
			opcache_reset();	// remove the include cache hell !
		}

		setEventMessages($langs->trans('MenuDeletedSuccessfuly'), null);
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=menus&module='.$module);
		exit;
	}
}

// Add menu in module without initial object
if ($dirins && $action == 'addmenu' && empty($cancel) && $user->hasRight("modulebuilder", "run")) {
	// check if module is enabled
	if (isModEnabled(strtolower($module))) {
		$result = unActivateModule(strtolower($module));
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
		if ($result) {
			setEventMessages($result, null, 'errors');
		}
		setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=menus&module='.$module);
		exit;
	}
	$error = 0;

	// load class and check if right exist
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	dol_include_once($pathtofile);
	$class = 'mod'.$module;
	$moduleobj = null;
	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrMOdules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	}
	// get all menus
	$menus = $moduleobj->menu;

	//verify fields required
	if (!GETPOST('type', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Type")), null, 'errors');
	}
	if (!GETPOST('titre', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Title")), null, 'errors');
	}
	if (!GETPOST('user', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DetailUser")), null, 'errors');
	}
	if (!GETPOST('url', 'alpha')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Url")), null, 'errors');
	}
	if (!empty(GETPOST('target'))) {
		$targets = array('_blank','_self','_parent','_top','');
		if (!in_array(GETPOST('target'), $targets)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldValue", $langs->transnoentities("target")), null, 'errors');
		}
	}


	// check if title or url already exist in menus

	foreach ($menus as $menu) {
		if (!empty(GETPOST('url')) && GETPOST('url') == $menu['url']) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldExist", $langs->transnoentities("url")), null, 'errors');
			break;
		}
		if (strtolower(GETPOST('titre')) == strtolower($menu['titre'])) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldExist", $langs->transnoentities("titre")), null, 'errors');
			break;
		}
	}

	if (GETPOST('type', 'alpha') == 'left' && !empty(GETPOST('lefmenu', 'alpha'))) {
		if (!str_contains(GETPOST('leftmenu'), strtolower($module))) {
			$error++;
			setEventMessages($langs->trans("WarningFieldsMustContains", $langs->transnoentities("LeftmenuId")), null, 'errors');
		}
	}
	$dirins = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$destdir = $dirins.'/'.strtolower($module);
	$objects = dolGetListOfObjectClasses($destdir);

	if (GETPOST('type', 'alpha') == 'left') {
		if (empty(GETPOST('leftmenu')) && count($objects) > 0) {
			$error++;
			setEventMessages($langs->trans("ErrorCoherenceMenu", $langs->transnoentities("LeftmenuId"), $langs->transnoentities("type")), null, 'errors');
		}
	}
	if (GETPOST('type', 'alpha') == 'top') {
		$error++;
		setEventMessages($langs->trans("ErrorTypeMenu", $langs->transnoentities("type")), null, 'errors');
	}

	$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
	if (!$error) {
		//stock forms in array
		$menuToAdd = array(
			'fk_menu' => GETPOST('fk_menu', 'alpha'),
			'type'  => GETPOST('type', 'alpha'),
			'titre' => ucfirst(GETPOST('titre', 'alpha')),
			'prefix' => '',
			'mainmenu' => GETPOST('mainmenu', 'alpha'),
			'leftmenu' => GETPOST('leftmenu', 'alpha'),
			'url' => GETPOST('url', 'alpha'),
			'langs' => strtolower($module)."@".strtolower($module),
			'position' => '',
			'enabled' => GETPOST('enabled', 'alpha'),
			'perms' => '$user->hasRight("'.strtolower($module).'", "'.GETPOST('objects', 'alpha').'", "'.GETPOST('perms', 'alpha').'")',
			'target' => GETPOST('target', 'alpha'),
			'user' => GETPOST('user', 'alpha'),
		);

		if (GETPOST('type') == 'left') {
			unset($menuToAdd['prefix']);
			if (empty(GETPOST('fk_menu'))) {
				$menuToAdd['fk_menu'] = 'fk_mainmenu='.GETPOST('mainmenu', 'alpha');
			} else {
				$menuToAdd['fk_menu'] = 'fk_mainmenu='.GETPOST('mainmenu', 'alpha').',fk_leftmenu='.GETPOST('fk_menu');
			}
		}
		if (GETPOST('enabled') == '1') {
			$menuToAdd['enabled'] = 'isModEnabled("'.strtolower($module).'")';
		} else {
			$menuToAdd['enabled'] = "0";
		}
		if (empty(GETPOST('objects'))) {
			$menuToAdd['perms'] = '1';
		}

		$checkcomment = checkExistComment($moduledescriptorfile, 0);
		if ($checkcomment < 0) {
			setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Menus"), "mod".$module."class.php"), null, 'warnings');
		} else {
			// Write all menus
			$result = reWriteAllMenus($moduledescriptorfile, $menus, $menuToAdd, null, 1);

			clearstatcache(true);
			if (function_exists('opcache_invalidate')) {
				opcache_reset();
			}
			/*if ($result < 0) {
				setEventMessages($langs->trans('ErrorMenuExistValue'), null, 'errors');
				header("Location: ".$_SERVER["PHP_SELF"].'?action=editmenu&token='.newToken().'&menukey='.urlencode($key+1).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.($key+1));
				exit;
			}*/

			header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=menus&module='.$module);
			setEventMessages($langs->trans('MenuAddedSuccesfuly'), null);
			exit;
		}
	}
}

// Modify a menu entry
if ($dirins && $action == "update_menu" && GETPOSTINT('menukey') && GETPOST('tabobj') && $user->hasRight("modulebuilder", "run")) {
	$objectname =  GETPOST('tabobj');
	$dirins = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$destdir = $dirins.'/'.strtolower($module);
	$objects = dolGetListOfObjectClasses($destdir);

	if (empty($cancel)) {
		if (isModEnabled(strtolower($module))) {
			$result = unActivateModule(strtolower($module));
			dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
			if ($result) {
				setEventMessages($result, null, 'errors');
			}
			setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
			header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=menus&module='.$module);
			exit;
		}
		$error = 0;
		// for loading class and the menu wants to modify
		$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
		dol_include_once($pathtofile);
		$class = 'mod'.$module;
		$moduleobj = null;
		if (class_exists($class)) {
			try {
				$moduleobj = new $class($db);
				'@phan-var-force DolibarrMOdules $moduleobj';
			} catch (Exception $e) {
				$error++;
				dol_print_error($db, $e->getMessage());
			}
		}
		$menus = $moduleobj->menu;
		$key = GETPOSTINT('menukey') - 1;

		$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		//stock forms in array
		$menuModify = array(
				'fk_menu' => GETPOST('fk_menu', 'alpha'),
				'type'  => GETPOST('type', 'alpha'),
				'titre' => ucfirst(GETPOST('titre', 'alpha')),
				'mainmenu' => GETPOST('mainmenu', 'alpha'),
				'leftmenu' => $menus[$key]['leftmenu'],
				'url' => GETPOST('url', 'alpha'),
				'langs' => strtolower($module)."@".strtolower($module),
				'position' => '',
				'enabled' => GETPOST('enabled', 'alpha'),
				'perms' => GETPOST('perms', 'alpha'),
				'target' => GETPOST('target', 'alpha'),
				'user' => GETPOST('user', 'alpha'),
			);
		if (!empty(GETPOST('fk_menu')) && GETPOST('fk_menu') != $menus[$key]['fk_menu']) {
			$menuModify['fk_menu'] = 'fk_mainmenu='.GETPOST('mainmenu').',fk_leftmenu='.GETPOST('fk_menu');
		} elseif (GETPOST('fk_menu') == $menus[$key]['fk_menu']) {
			$menuModify['fk_menu'] = $menus[$key]['fk_menu'];
		} else {
			$menuModify['fk_menu'] = 'fk_mainmenu='.GETPOST('mainmenu');
		}
		if ($menuModify['enabled'] === '') {
			$menuModify['enabled'] = '1';
		}
		if ($menuModify['perms'] === '') {
			$menuModify['perms'] = '1';
		}

		if (GETPOST('type', 'alpha') == 'top') {
			$error++;
			setEventMessages($langs->trans("ErrorTypeMenu", $langs->transnoentities("type")), null, 'errors');
		}

		if (!$error) {
			//update menu
			$checkComment = checkExistComment($moduledescriptorfile, 0);

			if ($checkComment < 0) {
				setEventMessages($langs->trans("WarningCommentNotFound", $langs->trans("Menus"), "mod".$module."class.php"), null, 'warnings');
			} else {
				// Write all menus
				$result = reWriteAllMenus($moduledescriptorfile, $menus, $menuModify, $key, 2);

				clearstatcache(true);
				if (function_exists('opcache_invalidate')) {
					opcache_reset();
				}

				if ($result < 0) {
					setEventMessages($langs->trans('ErrorMenuExistValue'), null, 'errors');
					//var_dump($_SESSION);exit;
					header("Location: ".$_SERVER["PHP_SELF"].'?action=editmenu&token='.newToken().'&menukey='.urlencode((string) ($key + 1)).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)).'&tabobj='.($key + 1));
					exit;
				}

				setEventMessages($langs->trans('MenuUpdatedSuccessfuly'), null);
				header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=menus&module='.$module);
				exit;
			}
		}
	} else {
		$_POST['type'] = '';	// TODO Use a var here and later
		$_POST['titre'] = '';
		$_POST['fk_menu'] = '';
		$_POST['leftmenu'] = '';
		$_POST['url'] = '';
	}
}

// update properties description of module
if ($dirins && $action == "update_props_module" && !empty(GETPOST('keydescription', 'alpha')) && empty($cancel) && $user->hasRight("modulebuilder", "run")) {
	if (isModEnabled(strtolower($module))) {
		$result = unActivateModule(strtolower($module));
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
		if ($result) {
			setEventMessages($result, null, 'errors');
		}
		setEventMessages($langs->trans('WarningModuleNeedRefresh', $langs->transnoentities($module)), null, 'warnings');
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=menus&module='.$module);
		exit;
	}
	$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
	$moduledescriptorfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
	$modulelogfile = $dirins.'/'.strtolower($module).'/ChangeLog.md';

	dol_include_once($pathtofile);

	$class = 'mod'.$module;
	$moduleobj = null;
	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrMOdules $moduleobj';
		} catch (Exception $e) {
			$error++;
			dol_print_error($db, $e->getMessage());
		}
	}

	$keydescription = GETPOST('keydescription', 'alpha');
	switch ($keydescription) {
		case 'desc':
			$propertyToUpdate = 'description';
			break;
		case 'version':
		case 'family':
		case 'picto':
		case 'editor_name':
		case 'editor_url':
			$propertyToUpdate = $keydescription;
			break;
		default:
			$error = GETPOST('keydescription');
			break;
	}

	if (isset($propertyToUpdate) && !empty(GETPOST('propsmodule'))) {
		$newValue = GETPOST('propsmodule');
		$lineToReplace = "\t\t\$this->$propertyToUpdate = ";
		$newLine = "\t\t\$this->$propertyToUpdate = '$newValue';\n";

		//for change version in log file
		if ($propertyToUpdate === 'version') {
			dolReplaceInFile($modulelogfile, array("## ".$moduleobj->$propertyToUpdate => $newValue));
		}

		$fileLines = file($moduledescriptorfile);
		foreach ($fileLines as &$line) {
			if (strpos($line, $lineToReplace) === 0) {
				dolReplaceInFile($moduledescriptorfile, array($line => $newLine));
				break;
			}
		}

		clearstatcache(true);
		if (function_exists('opcache_invalidate')) {
			opcache_reset();
		}
		setEventMessages($langs->trans('PropertyModuleUpdated', $propertyToUpdate), null);
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=description&module='.$module);
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

// Set dir where external modules are installed
if (!dol_is_dir($dirins)) {
	dol_mkdir($dirins);
}
$dirins_ok = (dol_is_dir($dirins));

$help_url = '';
$morejs = array(
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	//'/includes/ace/src/ext-chromevox.js'
);
$morecss = array();

llxHeader('', $langs->trans("ModuleBuilder"), $help_url, '', 0, 0, $morejs, $morecss, '', 'classforhorizontalscrolloftabs');


$text = $langs->trans("ModuleBuilder");

print load_fiche_titre($text, '', 'title_setup');

print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ModuleBuilderDesc", 'https://wiki.dolibarr.org/index.php/Module_development#Create_your_module').'</span>';
print '<br class="hideonsmartphone">';

//print $textforlistofdirs;
//print '<br>';



$message = '';
if (!$dirins) {
	$message = info_admin($langs->trans("ConfFileMustContainCustom", DOL_DOCUMENT_ROOT.'/custom', DOL_DOCUMENT_ROOT));
	$allowfromweb = -1;
} else {
	if ($dirins_ok) {
		if (!is_writable(dol_osencode($dirins))) {
			$langs->load("errors");
			$message = info_admin($langs->trans("ErrorFailedToWriteInDir", $dirins));
			$allowfromweb = 0;
		}
	} else {
		$message = info_admin($langs->trans("NotExistsDirect", $dirins).$langs->trans("InfDirAlt").$langs->trans("InfDirExample"));
		$allowfromweb = 0;
	}
}
if ($message) {
	print $message;
}

//print $langs->trans("ModuleBuilderDesc3", count($listofmodules), $FILEFLAG).'<br>';
$infomodulesfound = '<div style="padding: 12px 9px 12px">'.$form->textwithpicto('', $langs->trans("ModuleBuilderDesc3", count($listofmodules)).'<br><br>'.$langs->trans("ModuleBuilderDesc4", $FILEFLAG).'<br>'.$textforlistofdirs).'</div>';



$dolibarrdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);
$allowonlineinstall = true;
if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) {
	$allowonlineinstall = false;
}
if (empty($allowonlineinstall)) {
	if (getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US')) {
		// Show clean message
		$message = info_admin($langs->trans('InstallModuleFromWebHasBeenDisabledContactUs'));
	} else {
		// Show technical message
		$message = info_admin($langs->trans("InstallModuleFromWebHasBeenDisabledByFile", $dolibarrdataroot.'/installmodules.lock'), 0, 0, 1, 'warning');
	}

	print $message;

	llxFooter();
	exit(0);
}


// Load module descriptor
$error = 0;
$moduleobj = null;


if (!empty($module) && $module != 'initmodule' && $module != 'deletemodule') {
	$modulelowercase = strtolower($module);
	$loadclasserrormessage = '';

	// Load module
	try {
		$fullpathdirtodescriptor = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

		//throw(new Exception());
		dol_include_once($fullpathdirtodescriptor);

		$class = 'mod'.$module;
	} catch (Throwable $e) {		// This is called in PHP 7 only (includes Error and Exception)
		$loadclasserrormessage = $e->getMessage()."<br>\n";
		$loadclasserrormessage .= 'File: '.$e->getFile()."<br>\n";
		$loadclasserrormessage .= 'Line: '.$e->getLine()."<br>\n";
	}

	$moduleobj = null;
	if (class_exists($class)) {
		try {
			$moduleobj = new $class($db);
			'@phan-var-force DolibarrMOdules $moduleobj';
		} catch (Exception $e) {
			$error++;
			print $e->getMessage();
		}
	} else {
		if (empty($forceddirread)) {
			$error++;
		}
		$langs->load("errors");
		print '<!-- ErrorFailedToLoadModuleDescriptorForXXX -->';
		print img_warning('').' '.$langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module).'<br>';
		print $loadclasserrormessage;
	}
}

print '<br>';


// Tabs for all modules
$head = array();
$h = 0;

$head[$h][0] = $_SERVER["PHP_SELF"].'?module=initmodule';
$head[$h][1] = '<span class="valignmiddle text-plus-circle">'.$langs->trans("NewModule").'</span><span class="fa fa-plus-circle valignmiddle paddingleft"></span>';
$head[$h][2] = 'initmodule';
$h++;

$linktoenabledisable = '';

if (is_array($listofmodules) && count($listofmodules) > 0) {
	// Define $linktoenabledisable
	$modulelowercase = strtolower($module);

	$param = '';
	if ($tab) {
		$param .= '&tab='.urlencode($tab);
	}
	if ($module) {
		$param .= '&module='.urlencode($module);
	}
	if ($tabobj) {
		$param .= '&tabobj='.urlencode($tabobj);
	}

	$urltomodulesetup = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword='.urlencode($module).'">'.$langs->trans('Home').'-'.$langs->trans("Setup").'-'.$langs->trans("Modules").'</a>';

	// Define $linktoenabledisable to show after module title
	if (isModEnabled($modulelowercase)) {	// If module is already activated
		$linktoenabledisable .= '<a class="reposition asetresetmodule valignmiddle" href="'.$_SERVER["PHP_SELF"].'?id='.$moduleobj->numero.'&action=reset&token='.newToken().'&value=mod'.$module.$param.'">';
		$linktoenabledisable .= img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', '', 1);
		$linktoenabledisable .= '</a>';

		$linktoenabledisable .= $form->textwithpicto('', $langs->trans("Warning").' : '.$langs->trans("ModuleIsLive"), -1, 'warning');

		$objMod = $moduleobj;
		$backtourlparam = '';
		$backtourlparam .= ($backtourlparam ? '&' : '?').'module='.$module; // No urlencode here, done later
		if ($tab) {
			$backtourlparam .= ($backtourlparam ? '&' : '?').'tab='.$tab; // No urlencode here, done later
		}
		$backtourl = $_SERVER["PHP_SELF"].$backtourlparam;

		$regs = array();
		if (is_array($objMod->config_page_url)) {
			$i = 0;
			foreach ($objMod->config_page_url as $page) {
				$urlpage = $page;
				if ($i++) {
					$linktoenabledisable .= ' <a href="'.$urlpage.'" title="'.$langs->trans($page).'">'.img_picto(ucfirst($page), "setup").'</a>';
					//    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
				} else {
					if (preg_match('/^([^@]+)@([^@]+)$/i', $urlpage, $regs)) {
						$urltouse = dol_buildpath('/'.$regs[2].'/admin/'.$regs[1], 1);
						$linktoenabledisable .= ' <a href="'.$urltouse.(preg_match('/\?/', $urltouse) ? '&' : '?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 8px"').'</a>';
					} else {
						// Case standard admin page (not a page provided by the
						// module but a page provided by dolibarr)
						$urltouse = DOL_URL_ROOT.'/admin/'.$urlpage;
						$linktoenabledisable .= ' <a href="'.$urltouse.(preg_match('/\?/', $urltouse) ? '&' : '?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 8px"').'</a>';
					}
				}
			}
		} elseif (preg_match('/^([^@]+)@([^@]+)$/i', $objMod->config_page_url, $regs)) {
			$linktoenabledisable .= ' &nbsp; <a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1], 1).'?save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 8px"').'</a>';
		}
	} else {
		if (is_object($moduleobj)) {
			$linktoenabledisable .= '<a class="reposition asetresetmodule valignmiddle" href="'.$_SERVER["PHP_SELF"].'?id='.$moduleobj->numero.'&action=set&token='.newToken().'&value=mod'.$module.$param.'">';
			$linktoenabledisable .= img_picto($langs->trans("ModuleIsNotActive", $urltomodulesetup), 'switch_off', 'style="padding-right: 8px"', false, 0, 0, '', 'classfortooltip', 1);
			$linktoenabledisable .= "</a>\n";
		}
	}

	// Loop to show tab of each module
	foreach ($listofmodules as $tmpmodule => $tmpmodulearray) {
		$head[$h][0] = $_SERVER["PHP_SELF"].'?module='.$tmpmodulearray['modulenamewithcase'].($forceddirread ? '@'.$dirread : '');
		$head[$h][1] = $tmpmodulearray['modulenamewithcase'];
		$head[$h][2] = $tmpmodulearray['modulenamewithcase'];

		if ($tmpmodulearray['modulenamewithcase'] == $module) {
			$head[$h][4] = '<span class="inline-block">'.$linktoenabledisable.'</span>';
		}

		$h++;
	}
}

$head[$h][0] = $_SERVER["PHP_SELF"].'?module=deletemodule';
$head[$h][1] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("DangerZone");
$head[$h][2] = 'deletemodule';
$h++;


print dol_get_fiche_head($head, $module, '', -1, '', 0, $infomodulesfound, '', 8); // Modules

if ($module == 'initmodule') {
	// New module
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="initmodule">';
	print '<input type="hidden" name="module" value="initmodule">';

	//print '<span class="opacitymedium">'.$langs->trans("ModuleBuilderDesc2", 'conf/conf.php', $newdircustom).'</span><br>';
	print '<br>';

	print '<div class="tagtable">';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium">'.$langs->trans("IdModule").'</span>';
	print '</div><div class="tagtd">';
	print '<input type="text" name="idmodule" class="width75" value="500000" placeholder="'.dol_escape_htmltag($langs->trans("IdModule")).'">';
	print '<span class="opacitymedium">';
	print ' &nbsp; (';
	print dolButtonToOpenUrlInDialogPopup('popup_modules_id', $langs->transnoentitiesnoconv("SeeIDsInUse"), $langs->transnoentitiesnoconv("SeeIDsInUse"), '/admin/system/modules.php?mainmenu=home&leftmenu=admintools_info', '', '');
	print ' - ';
	print '<a href="https://wiki.dolibarr.org/index.php/List_of_modules_id" target="_blank" rel="noopener noreferrer external">'.$langs->trans("SeeReservedIDsRangeHere").'</a>';
	print ')';
	print '</span>';
	print '</div></div>';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium fieldrequired">'.$langs->trans("ModuleName").'</span>';
	print '</div><div class="tagtd">';
	print '<input type="text" name="modulename" value="'.dol_escape_htmltag($modulename).'" autofocus>';
	print ' '.$form->textwithpicto('', $langs->trans("EnterNameOfModuleDesc"));
	print '</div></div>';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium">'.$langs->trans("Description").'</span>';
	print '</div><div class="tagtd">';
	print '<input type="text" name="description" value="" class="minwidth500"><br>';
	print '</div></div>';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium">'.$langs->trans("Version").'</span>';
	print '</div><div class="tagtd">';
	print '<input type="text" name="version" class="width75" value="'.(GETPOSTISSET('version') ? GETPOST('version') : getDolGlobalString('MODULEBUILDER_SPECIFIC_VERSION', '1.0')).'" placeholder="'.dol_escape_htmltag($langs->trans("Version")).'">';
	print '</div></div>';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium">'.$langs->trans("Family").'</span>';
	print '</div><div class="tagtd">';
	print '<select name="family" id="family" class="minwidth400">';
	$arrayoffamilies = array(
		'hr' => "ModuleFamilyHr",
		'crm' => "ModuleFamilyCrm",
		'srm' => "ModuleFamilySrm",
		'financial' => 'ModuleFamilyFinancial',
		'products' => 'ModuleFamilyProducts',
		'projects' => 'ModuleFamilyProjects',
		'ecm' => 'ModuleFamilyECM',
		'technic' => 'ModuleFamilyTechnic',
		'portal' => 'ModuleFamilyPortal',
		'interface' => 'ModuleFamilyInterface',
		'base' => 'ModuleFamilyBase',
		'other' => 'ModuleFamilyOther'
	);
	foreach ($arrayoffamilies as $key => $value) {
		print '<option value="hr"'.($key == getDolGlobalString('MODULEBUILDER_SPECIFIC_FAMILY', 'other') ? ' selected="selected"' : '').' data-html="'.dol_escape_htmltag($langs->trans($value).' <span class="opacitymedium">- '.$key.'</span>').'">'.$langs->trans($value).'</option>';
	}
	print '</select>';
	print ajax_combobox("family");
	print '</div></div>';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium">'.$langs->trans("Picto").'</span>';
	print '</div><div class="tagtd">';
	print '<input type="text" name="idpicto" value="'.(GETPOSTISSET('idpicto') ? GETPOST('idpicto') : getDolGlobalString('MODULEBUILDER_DEFAULTPICTO', 'fa-file-o')).'" placeholder="'.dol_escape_htmltag($langs->trans("Picto")).'">';
	print $form->textwithpicto('', $langs->trans("Example").': fa-file-o, fa-globe, ... any font awesome code.<br>Advanced syntax is fa-fakey[_faprefix[_facolor[_fasize]]]');
	print '</div></div>';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium">'.$langs->trans("EditorName").'</span>';
	print '</div><div class="tagtd">';
	print '<input type="text" name="editorname" value="'.(GETPOSTISSET('editorname') ? GETPOST('editorname') : getDolGlobalString('MODULEBUILDER_SPECIFIC_EDITOR_NAME', $mysoc->name)).'" placeholder="'.dol_escape_htmltag($langs->trans("EditorName")).'"><br>';
	print '</div></div>';

	print '<div class="tagtr"><div class="tagtd paddingright">';
	print '<span class="opacitymedium">'.$langs->trans("EditorUrl").'</span>';
	print '</div><div class="tagtd">';
	print '<input type="text" name="editorurl" value="'.(GETPOSTISSET('editorurl') ? GETPOST('editorurl') : getDolGlobalString('MODULEBUILDER_SPECIFIC_EDITOR_URL', $mysoc->url)).'" placeholder="'.dol_escape_htmltag($langs->trans("EditorUrl")).'"><br>';
	print '</div></div>';

	print '<br><input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("Create")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
	print '</form>';
} elseif ($module == 'deletemodule') {
	print '<!-- Form to init a module -->'."\n";
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="delete">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="confirm_deletemodule">';
	print '<input type="hidden" name="module" value="deletemodule">';

	print $langs->trans("EnterNameOfModuleToDeleteDesc").'<br><br>';

	print '<input type="text" name="module" placeholder="'.dol_escape_htmltag($langs->trans("ModuleKey")).'" value="" autofocus>';
	print '<input type="submit" class="button smallpaddingimp" value="'.$langs->trans("Delete").'"'.($dirins ? '' : ' disabled="disabled"').'>';
	print '</form>';
} elseif (!empty($module)) {
	// Tabs for module
	if (!$error) {
		$dirread = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
		$destdir = $dirread.'/'.strtolower($module);
		$objects = dolGetListOfObjectClasses($destdir);
		$diroflang = dol_buildpath($modulelowercase, 0)."/langs";
		$countLangs = countItemsInDirectory($diroflang, 2);
		$countDictionaries = (!empty($moduleobj->dictionaries) ? count($moduleobj->dictionaries['tabname']) : 0);
		$countRights = count($moduleobj->rights);
		$countMenus = count($moduleobj->menu);
		$countTriggers = countItemsInDirectory(dol_buildpath($modulelowercase, 0)."/core/triggers");
		$countWidgets = countItemsInDirectory(dol_buildpath($modulelowercase, 0)."/core/boxes");
		$countEmailingSelectors = countItemsInDirectory(dol_buildpath($modulelowercase, 0)."/core/modules/mailings");
		$countCss = countItemsInDirectory(dol_buildpath($modulelowercase, 0)."/css");
		$countJs = countItemsInDirectory(dol_buildpath($modulelowercase, 0)."/js");
		$countCLI = countItemsInDirectory(dol_buildpath($modulelowercase, 0)."/scripts");
		$hasDoc = countItemsInDirectory(dol_buildpath($modulelowercase, 0)."/doc");
		//var_dump($moduleobj->dictionaries);exit;
		$head2 = array();
		$h = 0;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=description&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Description");
		$head2[$h][2] = 'description';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ((!is_array($objects) || count($objects) <= 0) ? $langs->trans("Objects") : $langs->trans("Objects").'<span class="marginleftonlyshort badge">'.count($objects)."</span>");
		$head2[$h][2] = 'objects';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=languages&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countLangs <= 0 ? $langs->trans("Languages") : $langs->trans("Languages").'<span class="marginleftonlyshort badge">'.$countLangs."</span>");
		$head2[$h][2] = 'languages';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countDictionaries == 0 ? $langs->trans("Dictionaries") : $langs->trans('Dictionaries').'<span class="marginleftonlyshort badge">'.$countDictionaries."</span>");
		$head2[$h][2] = 'dictionaries';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=permissions&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countRights <= 0 ? $langs->trans("Permissions") : $langs->trans("Permissions").'<span class="marginleftonlyshort badge">'.$countRights."</span>");
		$head2[$h][2] = 'permissions';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=tabs&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Tabs");
		$head2[$h][2] = 'tabs';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=menus&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countMenus <= 0 ? $langs->trans("Menus") : $langs->trans("Menus").'<span class="marginleftonlyshort badge">'.$countMenus."</span>");
		$head2[$h][2] = 'menus';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=hooks&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Hooks");
		$head2[$h][2] = 'hooks';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=triggers&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countTriggers <= 0 ? $langs->trans("Triggers") : $langs->trans("Triggers").'<span class="marginleftonlyshort badge">'.$countTriggers."</span>");
		$head2[$h][2] = 'triggers';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=widgets&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countWidgets <= 0 ? $langs->trans("Widgets") : $langs->trans("Widgets").'<span class="marginleftonlyshort badge">'.$countWidgets."</span>");
		$head2[$h][2] = 'widgets';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=emailings&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countEmailingSelectors <= 0 ? $langs->trans("EmailingSelectors") : $langs->trans("EmailingSelectors").'<span class="marginleftonlyshort badge">'.$countEmailingSelectors."</span>");
		$head2[$h][2] = 'emailings';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=exportimport&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Export").'-'.$langs->trans("Import");
		$head2[$h][2] = 'exportimport';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=css&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countCss <= 0 ? $langs->trans("CSS") : $langs->trans("CSS")." (".$countCss.")");
		$head2[$h][2] = 'css';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=js&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countJs <= 0 ? $langs->trans("JS") : $langs->trans("JS").'<span class="marginleftonlyshort badge">'.$countJs."</span>");
		$head2[$h][2] = 'js';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=cli&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($countCLI <= 0 ? $langs->trans("CLI") : $langs->trans("CLI").'<span class="marginleftonlyshort badge">'.$countCLI."</span>");
		$head2[$h][2] = 'cli';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=cron&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("CronList");
		$head2[$h][2] = 'cron';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=specifications&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = ($hasDoc <= 0 ? $langs->trans("Documentation") : $langs->trans("Documentation").'<span class="paddingleft badge">'.$hasDoc."</span>");
		$head2[$h][2] = 'specifications';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=buildpackage&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("BuildPackage");
		$head2[$h][2] = 'buildpackage';
		$h++;

		$MAXTABFOROBJECT = 15;

		print '<!-- Section for a given module -->';

		// Note module is inside $dirread

		if ($tab == 'description') {
			print '<!-- tab=description -->'."\n";
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
			$pathtofilereadme = $modulelowercase.'/README.md';
			$pathtochangelog = $modulelowercase.'/ChangeLog.md';

			$realpathofmodule = realpath($dirread.'/'.$modulelowercase);

			if ($action != 'editfile' || empty($file)) {
				$morehtmlright = '';
				if ($realpathofmodule != $dirread.'/'.$modulelowercase) {
					$morehtmlright = '<div style="padding: 12px 9px 12px">'.$form->textwithpicto('', '<span class="opacitymedium">'.$langs->trans("RealPathOfModule").' :</span> <strong class="wordbreak">'.$realpathofmodule.'</strong>').'</div>';
				}

				print dol_get_fiche_head($head2, $tab, '', -1, '', 0, $morehtmlright, '', $MAXTABFOROBJECT, 'formodulesuffix'); // Description - level 2

				print '<span class="opacitymedium">'.$langs->trans("ModuleBuilderDesc".$tab).'</span>';
				print '<br><br>';

				print '<table>';

				print '<tr><td>';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=DESCRIPTION_FLAG">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				// List of setup pages
				$listofsetuppages = dol_dir_list($realpathofmodule.'/admin', 'files', 0, '\.php$');
				foreach ($listofsetuppages as $setuppage) {
					//var_dump($setuppage);
					print '<tr><td>';
					print '<span class="fa fa-file-o"></span> '.$langs->trans("SetupFile").' : ';
					print '<strong class="wordbreak bold"><a href="'.dol_buildpath($modulelowercase.'/admin/'.$setuppage['relativename'], 1).'" target="_test">'.$modulelowercase.'/admin/'.$setuppage['relativename'].'</a></strong>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($modulelowercase.'/admin/'.$setuppage['relativename']).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
					print '</td></tr>';
				}

				print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("ReadmeFile").' : <strong class="wordbreak">'.$pathtofilereadme.'</strong>';
				print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=markdown&file='.urlencode($pathtofilereadme).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("ChangeLog").' : <strong class="wordbreak">'.$pathtochangelog.'</strong>';
				print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=markdown&file='.urlencode($pathtochangelog).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				print '</table>';
				print '<br>';

				print load_fiche_titre($form->textwithpicto($langs->trans("DescriptorFile"), $langs->transnoentitiesnoconv("File").' '.$pathtofile), '', '');

				if (is_object($moduleobj)) {
					print '<div class="underbanner clearboth"></div>';
					print '<div class="fichecenter">';
					print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="update_props_module">';
					print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
					print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
					print '<input type="hidden" name="keydescription" value="'.dol_escape_htmltag(GETPOST('keydescription', 'alpha')).'">';
					print '<table class="border centpercent">';
					print '<tr class="liste_titre"><td class="titlefield">';
					print $langs->trans("Parameter");
					print '</td><td>';
					print $langs->trans("Value");
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("IdModule");
					print '</td><td>';
					print $moduleobj->numero;
					print '<span class="opacitymedium">';
					print ' &nbsp; (';
					print dolButtonToOpenUrlInDialogPopup('popup_modules_id', $langs->transnoentitiesnoconv("SeeIDsInUse"), $langs->transnoentitiesnoconv("SeeIDsInUse"), '/admin/system/modules.php?mainmenu=home&leftmenu=admintools_info', '', '');
					print ' - <a href="https://wiki.dolibarr.org/index.php/List_of_modules_id" target="_blank" rel="noopener noreferrer external">'.$langs->trans("SeeReservedIDsRangeHere").'</a>)';
					print '</span>';
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("ModuleName");
					print '</td><td>';
					print $moduleobj->getName();
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Description");
					print '</td><td>';
					if ($action == 'edit_moduledescription' && GETPOST('keydescription', 'alpha') === 'desc') {
						print '<input class="minwidth500" name="propsmodule" value="'.dol_escape_htmltag($moduleobj->description).'">';
						print '<input class="reposition button smallpaddingimp" type="submit" name="modifydesc" value="'.$langs->trans("Modify").'"/>';
						print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
					} else {
						print $moduleobj->getDesc();
						print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=edit_moduledescription&token='.newToken().'&tab='.urlencode($tab).'&module='.urlencode($module).'&keydescription=desc">'.img_edit().'</a>';
					}
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Version");
					print '</td><td>';
					if ($action == 'edit_moduledescription' && GETPOST('keydescription', 'alpha') === 'version') {
						print '<input name="propsmodule" value="'.dol_escape_htmltag($moduleobj->getVersion()).'">';
						print '<input class="reposition button smallpaddingimp" type="submit" name="modifyversion" value="'.$langs->trans("Modify").'"/>';
						print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
					} else {
						print $moduleobj->getVersion();
						print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=edit_moduledescription&token='.newToken().'&tab='.urlencode($tab).'&module='.urlencode($module).'&keydescription=version">'.img_edit().'</a>';
					}
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Family");
					//print "<br>'crm','financial','hr','projects','products','ecm','technic','interface','other'";
					print '</td><td>';
					if ($action == 'edit_moduledescription' && GETPOST('keydescription', 'alpha') === 'family') {
						print '<select name="propsmodule" id="family" class="minwidth400">';
						$arrayoffamilies = array(
							'hr' => "ModuleFamilyHr",
							'crm' => "ModuleFamilyCrm",
							'srm' => "ModuleFamilySrm",
							'financial' => 'ModuleFamilyFinancial',
							'products' => 'ModuleFamilyProducts',
							'projects' => 'ModuleFamilyProjects',
							'ecm' => 'ModuleFamilyECM',
							'technic' => 'ModuleFamilyTechnic',
							'portal' => 'ModuleFamilyPortal',
							'interface' => 'ModuleFamilyInterface',
							'base' => 'ModuleFamilyBase',
							'other' => 'ModuleFamilyOther'
						);
						print '<option value="'.$moduleobj->family.'" data-html="'.dol_escape_htmltag($langs->trans($arrayoffamilies[$moduleobj->family]).' <span class="opacitymedium">- '.$moduleobj->family.'</span>').'">'.$langs->trans($arrayoffamilies[$moduleobj->family]).'</option>';
						foreach ($arrayoffamilies as $key => $value) {
							if ($key != $moduleobj->family) {
								print '<option value="'.$key.'" data-html="'.dol_escape_htmltag($langs->trans($value).' <span class="opacitymedium">- '.$key.'</span>').'">'.$langs->trans($value).'</option>';
							}
						}
						print '</select>';
						print '<input class="reposition button smallpaddingimp" type="submit" name="modifyfamily" value="'.$langs->trans("Modify").'"/>';
						print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
					} else {
						print $moduleobj->family;
						print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=edit_moduledescription&token='.newToken().'&tab='.urlencode($tab).'&module='.urlencode($module).'&keydescription=family">'.img_edit().'</a>';
					}
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Picto");
					print '</td><td>';
					if ($action == 'edit_modulepicto' && GETPOST('keydescription', 'alpha') === 'picto') {
						print '<input class="minwidth500" name="propsmodule" value="'.dol_escape_htmltag($moduleobj->picto).'">';
						print '<input class="reposition button smallpaddingimp" type="submit" name="modifypicto" value="'.$langs->trans("Modify").'"/>';
						print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
					} else {
						print $moduleobj->picto;
						print ' &nbsp; '.img_picto('', $moduleobj->picto, 'class="valignmiddle pictomodule paddingrightonly"');
						print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=edit_modulepicto&token='.newToken().'&tab='.urlencode($tab).'&module='.urlencode($module).'&keydescription=picto">'.img_edit().'</a>';
					}
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("EditorName");
					print '</td><td>';
					if ($action == 'edit_moduledescription' && GETPOST('keydescription', 'alpha') === 'editor_name') {
						print '<input name="propsmodule" value="'.dol_escape_htmltag($moduleobj->editor_name).'">';
						print '<input class="reposition button smallpaddingimp" type="submit" name="modifyname" value="'.$langs->trans("Modify").'"/>';
						print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
					} else {
						print $moduleobj->editor_name;
						print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=edit_moduledescription&token='.newToken().'&tab='.urlencode($tab).'&module='.urlencode($module).'&keydescription=editor_name">'.img_edit().'</a>';
					}
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("EditorUrl");
					print '</td><td>';
					if ($action == 'edit_moduledescription' && GETPOST('keydescription', 'alpha') === 'editor_url') {
						print '<input name="propsmodule" value="'.dol_escape_htmltag($moduleobj->editor_url).'">';
						print '<input class="reposition button smallpaddingimp" type="submit" name="modifyeditorurl" value="'.$langs->trans("Modify").'"/>';
						print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
					} else {
						if (!empty($moduleobj->editor_url)) {
							print '<a href="'.$moduleobj->editor_url.'" target="_blank" rel="noopener">'.$moduleobj->editor_url.' '.img_picto('', 'globe').'</a>';
						}
						print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=edit_moduledescription&token='.newToken().'&tab='.urlencode($tab).'&module='.urlencode($module).'&keydescription=editor_url">'.img_edit().'</a>';
					}
					print '</td></tr>';

					print '</table>';
					print '</form>';
				} else {
					print $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module).'<br>';
				}

				if (!empty($moduleobj)) {
					print '<br><br>';

					// Readme file
					print load_fiche_titre($form->textwithpicto($langs->trans("ReadmeFile"), $langs->transnoentitiesnoconv("File").' '.$pathtofilereadme), '', '');

					print '<!-- readme file -->';
					if (dol_is_file($dirread.'/'.$pathtofilereadme)) {
						print '<div class="underbanner clearboth"></div><div class="fichecenter">'.$moduleobj->getDescLong().'</div>';
					} else {
						print '<span class="opacitymedium">'.$langs->trans("ErrorFileNotFound", $pathtofilereadme).'</span>';
					}

					print '<br><br>';

					// ChangeLog
					print load_fiche_titre($form->textwithpicto($langs->trans("ChangeLog"), $langs->transnoentitiesnoconv("File").' '.$pathtochangelog), '', '');

					print '<!-- changelog file -->';
					if (dol_is_file($dirread.'/'.$pathtochangelog)) {
						print '<div class="underbanner clearboth"></div><div class="fichecenter">'.$moduleobj->getChangeLog().'</div>';
					} else {
						print '<span class="opacitymedium">'.$langs->trans("ErrorFileNotFound", $pathtochangelog).'</span>';
					}
				}

				print dol_get_fiche_end();
			} else {	// Edit text file
				$fullpathoffile = dol_buildpath($file, 0, 1); // Description - level 2

				if ($fullpathoffile) {
					$content = file_get_contents($fullpathoffile);
				}

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				print dol_get_fiche_head($head2, $tab, '', -1, '', 0, '', '', 0, 'formodulesuffix');

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));

				print dol_get_fiche_end();

				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		} else {
			print dol_get_fiche_head($head2, $tab, '', -1, '', 0, '', '', $MAXTABFOROBJECT, 'formodulesuffix'); // Level 2
		}

		if ($tab == 'languages') {
			print '<!-- tab=languages -->'."\n";
			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("LanguageDefDesc").'</span><br>';
				print '<br>';


				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addlanguage">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';
				print $formadmin->select_language(getDolGlobalString('MAIN_LANG_DEFAULT'), 'newlangcode', 0, array(), 1, 0, 0, 'minwidth300', 1);
				print '<input type="submit" name="addlanguage" class="button smallpaddingimp" value="'.dol_escape_htmltag($langs->trans("AddLanguageFile")).'"><br>';
				print '</form>';

				print '<br>';
				print '<br>';

				$modulelowercase = strtolower($module);

				// Dir for module
				$diroflang = dol_buildpath($modulelowercase, 0);
				$diroflang .= '/langs';
				$langfiles = dol_dir_list($diroflang, 'files', 1, '\.lang$');

				if (!preg_match('/custom/', $dirread)) {
					// If this is not a module into custom
					$diroflang = $dirread;
					$diroflang .= '/langs';
					$langfiles = dol_dir_list($diroflang, 'files', 1, $modulelowercase.'\.lang$');
				}

				print '<table class="none">';
				foreach ($langfiles as $langfile) {
					$pathtofile = $modulelowercase.'/langs/'.$langfile['relativename'];
					if (!preg_match('/custom/', $dirread)) {	// If this is not a module into custom
						$pathtofile = 'langs/'.$langfile['relativename'];
					}
					print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("LanguageFile").' '.basename(dirname($pathtofile)).' : <strong class="wordbreak">'.$pathtofile.'</strong>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=ini&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
					print '</td>';
				}
				print '</table>';
			} else {
				// Edit text language file

				//print $langs->trans("UseAsciiDocFormat").'<br>';

				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'text'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'objects') {
			print '<!-- tab=objects -->'."\n";
			$head3 = array();
			$h = 0;

			// Dir for module
			$dir = $dirread.'/'.$modulelowercase.'/class';

			$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj=newobject';
			$head3[$h][1] = '<span class="valignmiddle text-plus-circle">'.$langs->trans("NewObjectInModulebuilder").'</span><span class="fa fa-plus-circle valignmiddle paddingleft"></span>';
			$head3[$h][2] = 'newobject';
			$h++;

			// Scan for object class files
			$listofobject = dol_dir_list($dir, 'files', 0, '\.class\.php$');

			$firstobjectname = '';
			foreach ($listofobject as $fileobj) {
				if (preg_match('/^api_/', $fileobj['name'])) {
					continue;
				}
				if (preg_match('/^actions_/', $fileobj['name'])) {
					continue;
				}

				$tmpcontent = file_get_contents($fileobj['fullname']);
				if (preg_match('/class\s+([^\s]*)\s+extends\s+CommonObject/ims', $tmpcontent, $reg)) {
					//$objectname = preg_replace('/\.txt$/', '', $fileobj['name']);
					$objectname = $reg[1];
					if (empty($firstobjectname)) {
						$firstobjectname = $objectname;
					}
					$pictoname = 'generic';
					if (preg_match('/\$picto\s*=\s*["\']([^"\']+)["\']/', $tmpcontent, $reg)) {
						$pictoname = $reg[1];
					}

					$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj='.$objectname;
					$head3[$h][1] = img_picto('', $pictoname, 'class="pictofixedwidth valignmiddle"').$objectname;
					$head3[$h][2] = $objectname;
					$h++;
				}
			}

			if ($h > 1) {
				$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj=deleteobject';
				$head3[$h][1] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("DangerZone");
				$head3[$h][2] = 'deleteobject';
				$h++;
			}

			// If tabobj was not defined, then we check if there is one obj. If yes, we force on it, if no, we will show tab to create new objects.
			if ($tabobj == 'newobjectifnoobj') {
				if ($firstobjectname) {
					$tabobj = $firstobjectname;
				} else {
					$tabobj = 'newobject';
				}
			}

			print dol_get_fiche_head($head3, $tabobj, '', -1, '', 0, '', '', 0, 'forobjectsuffix'); // Level 3


			if ($tabobj == 'newobject') {
				// New object tab
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="initobject">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

				print '<span class="opacitymedium">'.$langs->trans("EnterNameOfObjectDesc").'</span><br><br>';

				print '<div class="tagtable">';

				print '<div class="tagtr"><div class="tagtd">';
				print '<span class="opacitymedium">'.$langs->trans("ObjectKey").'</span> &nbsp; ';
				print '</div><div class="tagtd">';
				print '<input type="text" name="objectname" maxlength="64" value="'.dol_escape_htmltag(GETPOSTISSET('objectname') ? GETPOST('objectname', 'alpha') : $modulename).'" autofocus>';
				print $form->textwithpicto('', $langs->trans("Example").': MyObject, ACamelCaseName, ...');
				print '</div></div>';

				print '<div class="tagtr"><div class="tagtd">';
				print '<span class="opacitymedium">'.$langs->trans("Picto").'</span> &nbsp; ';
				print '</div><div class="tagtd">';
				print '<input type="text" name="idpicto" value="fa-file-o" placeholder="'.dol_escape_htmltag($langs->trans("Picto")).'">';
				print $form->textwithpicto('', $langs->trans("Example").': fa-file-o, fa-globe, ... any font awesome code.<br>Advanced syntax is fa-fakey[_faprefix[_facolor[_fasize]]]');
				print '</div></div>';

				print '<div class="tagtr"><div class="tagtd">';
				print '<span class="opacitymedium">'.$langs->trans("DefinePropertiesFromExistingTable").'</span> &nbsp; ';
				print '</div><div class="tagtd">';
				print '<input type="text" name="initfromtablename" value="'.GETPOST('initfromtablename').'" placeholder="'.$langs->trans("TableName").'">';
				print $form->textwithpicto('', $langs->trans("DefinePropertiesFromExistingTableDesc").'<br>'.$langs->trans("DefinePropertiesFromExistingTableDesc2"));
				print '</div></div>';

				print '</div>';

				print '<br>';
				print '<input type="checkbox" name="includerefgeneration" id="includerefgeneration" value="includerefgeneration"> <label class="margintoponly" for="includerefgeneration">'.$form->textwithpicto($langs->trans("IncludeRefGeneration"), $langs->trans("IncludeRefGenerationHelp")).'</label><br>';
				print '<input type="checkbox" name="includedocgeneration" id="includedocgeneration" value="includedocgeneration"> <label for="includedocgeneration">'.$form->textwithpicto($langs->trans("IncludeDocGeneration"), $langs->trans("IncludeDocGenerationHelp")).'</label><br>';
				print '<input type="checkbox" name="generatepermissions" id="generatepermissions" value="generatepermissions"> <label for="generatepermissions">'.$form->textwithpicto($langs->trans("GeneratePermissions"), $langs->trans("GeneratePermissionsHelp")).'</label><br>';
				print '<br>';
				print '<input type="submit" class="button small" name="create" value="'.dol_escape_htmltag($langs->trans("GenerateCode")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
				print '<br>';
				print '<br>';
				/*
				print '<br>';
				print '<span class="opacitymedium">'.$langs->trans("or").'</span>';
				print '<br>';
				print '<br>';
				//print '<input type="checkbox" name="initfromtablecheck"> ';
				print $langs->trans("InitStructureFromExistingTable");
				print '<input type="text" name="initfromtablename" value="" placeholder="'.$langs->trans("TableName").'">';
				print '<input type="submit" class="button smallpaddingimp" name="createtablearray" value="'.dol_escape_htmltag($langs->trans("GenerateCode")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
				print '<br>';
				*/

				print '</form>';
			} elseif ($tabobj == 'createproperty') {
				$attributesUnique = array(
					'proplabel' => $form->textwithpicto($langs->trans("Label"), $langs->trans("YouCanUseTranslationKey")),
					'propname' => $form->textwithpicto($langs->trans("Code"), $langs->trans("PropertyDesc"), 1, 'help', 'extracss', 0, 3, 'propertyhelp'),
					'proptype' => $form->textwithpicto($langs->trans("Type"), $langs->trans("TypeOfFieldsHelpIntro").'<br><br>'.$langs->trans("TypeOfFieldsHelp"), 1, 'help', 'extracss', 0, 3, 'typehelp'),
					'proparrayofkeyval' => $form->textwithpicto($langs->trans("ArrayOfKeyValues"), $langs->trans("ArrayOfKeyValuesDesc")),
					'propnotnull' => $form->textwithpicto($langs->trans("NotNull"), $langs->trans("NotNullDesc")),
					'propdefault' => $langs->trans("DefaultValue"),
					'propindex' => $langs->trans("DatabaseIndex"),
					'propforeignkey' => $form->textwithpicto($langs->trans("ForeignKey"), $langs->trans("ForeignKeyDesc"), 1, 'help', 'extracss', 0, 3, 'foreignkeyhelp'),
					'propposition' => $langs->trans("Position"),
					'propenabled' => $form->textwithpicto($langs->trans("Enabled"), $langs->trans("EnabledDesc"), 1, 'help', 'extracss', 0, 3, 'enabledhelp'),
					'propvisible' => $form->textwithpicto($langs->trans("Visibility"), $langs->trans("VisibleDesc").'<br><br>'.$langs->trans("ItCanBeAnExpression"), 1, 'help', 'extracss', 0, 3, 'visiblehelp'),
					'propnoteditable' => $langs->trans("NotEditable"),
					//'propalwayseditable' => $langs->trans("AlwaysEditable"),
					'propsearchall' => $form->textwithpicto($langs->trans("SearchAll"), $langs->trans("SearchAllDesc")),
					'propisameasure' => $form->textwithpicto($langs->trans("IsAMeasure"), $langs->trans("IsAMeasureDesc")),
					'propcss' => $langs->trans("CSSClass"),
					'propcssview' => $langs->trans("CSSViewClass"),
					'propcsslist' => $langs->trans("CSSListClass"),
					'prophelp' => $langs->trans("KeyForTooltip"),
					'propshowoncombobox' => $langs->trans("ShowOnCombobox"),
					//'propvalidate' => $form->textwithpicto($langs->trans("Validate"), $langs->trans("ValidateModBuilderDesc")),
					'propcomment' => $langs->trans("Comment"),
				);
				print '<form action="'.$_SERVER["PHP_SELF"].'?tab=objects&module='.urlencode($module).'&tabobj=createproperty&obj='.urlencode(GETPOST('obj')).'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addproperty">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="obj" value="'.dol_escape_htmltag(GETPOST('obj')).'">';

				print '<table class="border centpercent tableforfieldcreate">'."\n";
				$counter = 0;
				foreach ($attributesUnique as $key => $attribute) {
					if ($counter % 2 === 0) {
						print '<tr>';
					}
					if ($key == 'propname' || $key == 'proplabel') {
						print '<td class="titlefieldcreate fieldrequired">'.$attribute.'</td><td class="valuefieldcreate maxwidth50"><input class="maxwidth200" id="'.$key.'" type="text" name="'.$key.'" value="'.dol_escape_htmltag(GETPOST($key, 'alpha')).'"></td>';
					} elseif ($key == 'proptype') {
						print '<td class="titlefieldcreate fieldrequired">'.$attribute.'</td><td class="valuefieldcreate maxwidth50">';
						print '<input class="maxwidth200" id="'.$key.'" list="datalist'.$key.'" type="text" name="'.$key.'" value="'.dol_escape_htmltag(GETPOST($key, 'alpha')).'">';
						//print '<div id="suggestions"></div>';
						print '<datalist id="datalist'.$key.'">';
						print '<option>varchar(128)</option>';
						print '<option>email</option>';
						print '<option>phone</option>';
						print '<option>ip</option>';
						print '<option>url</option>';
						print '<option>password</option>';
						print '<option>text</option>';
						print '<option>html</option>';
						print '<option>date</option>';
						print '<option>datetime</option>';
						print '<option>integer</option>';
						print '<option>double(28,4)</option>';
						print '<option>real</option>';
						print '<option>integer:ClassName:RelativePath/To/ClassFile.class.php[:1[:FILTER]]</option>';
						// Combo with list of fields
						/*
						if (empty($formadmin)) {
							include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
							$formadmin = new FormAdmin($db);
						}
						print $formadmin->selectTypeOfFields($key, GETPOST($key, 'alpha'));
						*/
						print '</datalist>';
						print '</td>';
						//} elseif ($key == 'propvalidate') {
						//	print '<td class="titlefieldcreate">'.$attribute.'</td><td class="valuefieldcreate maxwidth50"><input type="number" step="1" min="0" max="1" class="text maxwidth100" value="'.dol_escape_htmltag(GETPOST($key, 'alpha')).'"></td>';
					} elseif ($key == 'propvisible') {
						print '<td class="titlefieldcreate">'.$attribute.'</td><td class="valuefieldcreate"><input class="maxwidth200" type="text" name="'.$key.'" value="'.dol_escape_htmltag(GETPOSTISSET($key) ? GETPOST($key, 'alpha') : "1").'"></td>';
					} elseif ($key == 'propenabled') {
						//$default = "isModEnabled('".strtolower($module)."')";
						$default = 1;
						print '<td class="titlefieldcreate">'.$attribute.'</td><td class="valuefieldcreate"><input class="maxwidth200" type="text" name="'.$key.'" value="'.dol_escape_htmltag(GETPOSTISSET($key) ? GETPOST($key, 'alpha') : $default).'"></td>';
					} elseif ($key == 'proparrayofkeyval') {
						print '<td class="titlefieldcreate tdproparrayofkeyval">'.$attribute.'</td><td class="valuefieldcreate"><textarea class="maxwidth200" name="'.$key.'">'.dol_escape_htmltag(GETPOSTISSET($key) ? GETPOST($key, 'alpha') : "").'</textarea></td>';
					} else {
						print '<td class="titlefieldcreate">'.$attribute.'</td><td class="valuefieldcreate"><input class="maxwidth200" type="text" name="'.$key.'" value="'.dol_escape_htmltag(GETPOSTISSET($key) ? GETPOST($key, 'alpha') : '').'"></td>';
					}
					$counter++;
					if ($counter % 2 === 0) {
						print '</tr>';
					}
				}
				if ($counter % 2 !== 0) {
					while ($counter % 2 !== 0) {
						print '<td></td>';
						$counter++;
					}
					print '</tr>';
				}
				print '</table><br>'."\n";
				print '<div class="center">';
				print '<input type="submit" class="button button-save" name="add" value="' . dol_escape_htmltag($langs->trans('Create')) . '">';
				print '<input type="button" class="button button-cancel" name="cancel" value="' . dol_escape_htmltag($langs->trans('Cancel')) . '" onclick="goBack()">';
				print '</div>';
				print '</form>';
				// javascript
				print '<script>
				function goBack() {
					var url = "'.$_SERVER["PHP_SELF"].'?tab=objects&module='.urlencode($module).'";
					window.location.href = url;
				}
				$(document).ready(function() {
					$("#proplabel").on("keyup", function() {
						console.log("key up on label");
						s = cleanString($("#proplabel").val());
						$("#propname").val(s);
					});

					function cleanString( stringtoclean )
					{
						// allow  "a-z", "A-Z", "0-9" and "_"
						stringtoclean = stringtoclean.replace(/[^a-z0-9_]+/ig, "");
						stringtoclean = stringtoclean.toLowerCase();
						if (!isNaN(stringtoclean)) {
						  return ""
						}
						while ( stringtoclean.length > 1 && !isNaN( stringtoclean.charAt(0))  ){
						  stringtoclean = stringtoclean.substr(1)
						}
						if (stringtoclean.length > 28) {
							stringtoclean = stringtoclean.substring(0, 27);
						}
						return stringtoclean;
					}

				  });';
				print '</script>';
			} elseif ($tabobj == 'deleteobject') {
				// Delete object tab
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="confirm_deleteobject">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="tabobj" value="deleteobject">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

				print $langs->trans("EnterNameOfObjectToDeleteDesc").'<br><br>';

				print '<input type="text" name="objectname" value="" placeholder="'.dol_escape_htmltag($langs->trans("ObjectKey")).'" autofocus>';
				print '<input type="submit" class="button smallpaddingimp" name="delete" value="'.dol_escape_htmltag($langs->trans("Delete")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
				print '</form>';
			} else {
				// tabobj = module
				if ($action == 'deleteproperty') {
					$formconfirm = $form->formconfirm(
						$_SERVER["PHP_SELF"].'?propertykey='.urlencode(GETPOST('propertykey', 'alpha')).'&objectname='.urlencode($objectname).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj),
						$langs->trans('Delete'),
						$langs->trans('ConfirmDeleteProperty', GETPOST('propertykey', 'alpha')),
						'confirm_deleteproperty',
						'',
						0,
						1
					);

					// Print form confirm
					print $formconfirm;
				}
				if ($action != 'editfile' || empty($file)) {
					try {
						//$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

						$pathtoclass    = strtolower($module).'/class/'.strtolower($tabobj).'.class.php';
						$pathtoapi      = strtolower($module).'/class/api_'.strtolower($module).'.class.php';
						$pathtoagenda   = strtolower($module).'/'.strtolower($tabobj).'_agenda.php';
						$pathtocard     = strtolower($module).'/'.strtolower($tabobj).'_card.php';
						$pathtodocument = strtolower($module).'/'.strtolower($tabobj).'_document.php';
						$pathtolist     = strtolower($module).'/'.strtolower($tabobj).'_list.php';
						$pathtonote     = strtolower($module).'/'.strtolower($tabobj).'_note.php';
						$pathtocontact  = strtolower($module).'/'.strtolower($tabobj).'_contact.php';
						$pathtophpunit  = strtolower($module).'/test/phpunit/'.strtolower($tabobj).'Test.php';

						// Try to load object class file
						clearstatcache(true);
						if (function_exists('opcache_invalidate')) {
							opcache_invalidate($dirread.'/'.$pathtoclass, true); // remove the include cache hell !
						}

						if (empty($forceddirread) && empty($dirread)) {
							$result = dol_include_once($pathtoclass);
							$stringofinclude = "dol_include_once(".$pathtoclass.")";
						} else {
							$result = include_once $dirread.'/'.$pathtoclass;
							$stringofinclude = "@include_once ".$dirread.'/'.$pathtoclass;
						}

						if (class_exists($tabobj)) {
							try {
								$tmpobject = @new $tabobj($db);
							} catch (Exception $e) {
								dol_syslog('Failed to load Constructor of class: '.$e->getMessage(), LOG_WARNING);
							}
						} else {
							print '<span class="warning">'.$langs->trans('Failed to find the class '.$tabobj.' despite the '.$stringofinclude).'</span><br><br>';
						}

						// Define path for sql file
						$pathtosql = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'-'.strtolower($module).'.sql';
						$result = dol_buildpath($pathtosql);
						if (! dol_is_file($result)) {
							$pathtosql = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'.sql';
							$result = dol_buildpath($pathtosql);
							if (! dol_is_file($result)) {
								$pathtosql = 'install/mysql/tables/llx_'.strtolower($module).'_'.strtolower($tabobj).'-'.strtolower($module).'.sql';
								$result = dol_buildpath($pathtosql);
								if (! dol_is_file($result)) {
									$pathtosql = 'install/mysql/tables/llx_'.strtolower($module).'-'.strtolower($module).'.sql';
									$result = dol_buildpath($pathtosql);
									if (! dol_is_file($result)) {
										$pathtosql = 'install/mysql/tables/llx_'.strtolower($module).'.sql';
										$pathtosqlextra = 'install/mysql/tables/llx_'.strtolower($module).'_extrafields.sql';
										$result = dol_buildpath($pathtosql);
									} else {
										$pathtosqlextra = 'install/mysql/tables/llx_'.strtolower($module).'_extrafields-'.strtolower($module).'.sql';
									}
								} else {
									$pathtosqlextra = 'install/mysql/tables/llx_'.strtolower($module).'_'.strtolower($tabobj).'_extrafields-'.strtolower($module).'.sql';
								}
							} else {
								$pathtosqlextra = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'_extrafields.sql';
							}
						} else {
							$pathtosqlextra = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'_extrafields-'.strtolower($module).'.sql';
						}
						$pathtosqlroot = preg_replace('/\/llx_.*$/', '', $pathtosql);

						$pathtosqlkey   = preg_replace('/\.sql$/', '.key.sql', $pathtosql);
						$pathtosqlextrakey   = preg_replace('/\.sql$/', '.key.sql', $pathtosqlextra);

						$pathtolib      = strtolower($module).'/lib/'.strtolower($module).'.lib.php';
						$pathtoobjlib   = strtolower($module).'/lib/'.strtolower($module).'_'.strtolower($tabobj).'.lib.php';

						$tmpobject = $tmpobject ?? null;  // @phan-suppress-current-line PhanPluginDuplicateExpressionAssignmentOperation
						if (is_object($tmpobject) && property_exists($tmpobject, 'picto')) {
							$pathtopicto = $tmpobject->picto;
							$realpathtopicto = '';
						} else {
							$pathtopicto = strtolower($module).'/img/object_'.strtolower($tabobj).'.png';
							$realpathtopicto = $dirread.'/'.$pathtopicto;
						}

						//var_dump($pathtoclass);
						//var_dump($dirread);
						$realpathtoclass    = $dirread.'/'.$pathtoclass;
						$realpathtoapi      = $dirread.'/'.$pathtoapi;
						$realpathtoagenda   = $dirread.'/'.$pathtoagenda;
						$realpathtocard     = $dirread.'/'.$pathtocard;
						$realpathtodocument = $dirread.'/'.$pathtodocument;
						$realpathtolist     = $dirread.'/'.$pathtolist;
						$realpathtonote     = $dirread.'/'.$pathtonote;
						$realpathtocontact  = $dirread.'/'.$pathtocontact;
						$realpathtophpunit  = $dirread.'/'.$pathtophpunit;
						$realpathtosql      = $dirread.'/'.$pathtosql;
						$realpathtosqlextra = $dirread.'/'.$pathtosqlextra;
						$realpathtosqlkey   = $dirread.'/'.$pathtosqlkey;
						$realpathtosqlextrakey = $dirread.'/'.$pathtosqlextrakey;
						$realpathtolib      = $dirread.'/'.$pathtolib;
						$realpathtoobjlib   = $dirread.'/'.$pathtoobjlib;

						if (empty($realpathtoapi)) { 	// For compatibility with some old modules
							$pathtoapi = strtolower($module).'/class/api_'.strtolower($module).'s.class.php';
							$realpathtoapi = $dirread.'/'.$pathtoapi;
						}

						$urloflist = dol_buildpath('/'.$pathtolist, 1);
						$urlofcard = dol_buildpath('/'.$pathtocard, 1);

						$objs = array();

						print '<!-- section for object -->';
						print '<div class="fichehalfleft smallxxx">';
						// Main DAO class file
						print '<span class="fa fa-file-o"></span> '.$langs->trans("ClassFile").' : <strong>'.(dol_is_file($realpathtoclass) ? '' : '<strike>').preg_replace('/^'.strtolower($module).'\//', '', $pathtoclass).(dol_is_file($realpathtoclass) ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtoclass).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						// Image
						if ($realpathtopicto && dol_is_file($realpathtopicto)) {
							print '<span class="fa fa-file-image-o"></span> '.$langs->trans("Image").' : <strong>'.(dol_is_file($realpathtopicto) ? '' : '<strike>').preg_replace('/^'.strtolower($module).'\//', '', $pathtopicto).(dol_is_file($realpathtopicto) ? '' : '</strike>').'</strong>';
							//print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtopicto).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
							print '<br>';
						} elseif (!empty($tmpobject)) {
							print '<span class="fa fa-file-image-o"></span> '.$langs->trans("Image").' : '.img_picto('', $tmpobject->picto, 'class="pictofixedwidth valignmiddle"').$tmpobject->picto;
							print '<br>';
						}

						// API file
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("ApiClassFile").' : <strong class="wordbreak">'.(dol_is_file($realpathtoapi) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtoapi).(dol_is_file($realpathtoapi) ? '' : '</span></strike>').'</strong>';
						if (dol_is_file($realpathtoapi)) {
							$file = file_get_contents($realpathtoapi);
							if (preg_match('/var '.$tabobj.'\s+([^\s]*)\s/ims', $file, $objs)) {
								print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtoapi).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
								print ' ';
								print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtoapi).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
								print $form->textwithpicto('', $langs->trans("InfoForApiFile"), 1, 'warning');
								print ' &nbsp; ';
								// Comparing to null (phan considers $modulelowercase can be null here)
								if ($modulelowercase !== null && !isModEnabled($modulelowercase)) {	// If module is not activated
									print '<a href="#" class="classfortooltip" target="apiexplorer" title="'.$langs->trans("ModuleMustBeEnabled", $module).'"><strike>'.$langs->trans("ApiExplorer").'</strike></a>';
								} else {
									print '<a href="'.DOL_URL_ROOT.'/api/index.php/explorer/" target="apiexplorer">'.$langs->trans("ApiExplorer").'</a>';
								}
							} else {
								print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initapi&token='.newToken().'&format=php&file='.urlencode($pathtoapi).'">'.img_picto($langs->trans('AddAPIsForThisObject'), 'generate', 'class="paddingleft"').'</a>';
							}
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initapi&token='.newToken().'&format=php&file='.urlencode($pathtoapi).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						// PHPUnit
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("TestClassFile").' : <strong class="wordbreak">'.(dol_is_file($realpathtophpunit) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtophpunit).(dol_is_file($realpathtophpunit) ? '' : '</span></strike>').'</strong>';
						if (dol_is_file($realpathtophpunit)) {
							print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtophpunit).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtophpunit).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initphpunit&token='.newToken().'&format=php&file='.urlencode($pathtophpunit).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						print '<br>';

						print '<br>';

						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForLib").' : <strong class="wordbreak">'.(dol_is_file($realpathtolib) ? '' : '<strike>').preg_replace('/^'.strtolower($module).'\//', '', $pathtolib).(dol_is_file($realpathtolib) ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtolib).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForObjLib").' : <strong class="wordbreak">'.(dol_is_file($realpathtoobjlib) ? '' : '<strike>').preg_replace('/^'.strtolower($module).'\//', '', $pathtoobjlib).(dol_is_file($realpathtoobjlib) ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtoobjlib).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';

						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFile").' : <strong class="wordbreak">'.(dol_is_file($realpathtosql) ? '' : '<strike>').preg_replace('/^'.strtolower($module).'\//', '', $pathtosql).(dol_is_file($realpathtosql) ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=sql&file='.urlencode($pathtosql).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=droptable&token='.newToken().'">'.$langs->trans("DropTableIfEmpty").'</a>';
						//print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("RunSql").'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFileKey").' : <strong class="wordbreak">'.(dol_is_file($realpathtosqlkey) ? '' : '<strike>').preg_replace('/^'.strtolower($module).'\//', '', $pathtosqlkey).(dol_is_file($realpathtosqlkey) ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=sql&file='.urlencode($pathtosqlkey).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						//print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("RunSql").'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFileExtraFields").' : <strong class="wordbreak">'.(dol_is_file($realpathtosqlextra) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtosqlextra).(dol_is_file($realpathtosqlextra) && dol_is_file($realpathtosqlextrakey) ? '' : '</span></strike>').'</strong>';
						if (dol_is_file($realpathtosqlextra) && dol_is_file($realpathtosqlextrakey)) {
							print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&file='.urlencode($pathtosqlextra).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtosqlextra).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
							print ' &nbsp; ';
							print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=droptableextrafields&token='.newToken().'">'.$langs->trans("DropTableIfEmpty").'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initsqlextrafields&token='.newToken().'&format=sql&file='.urlencode($pathtosqlextra).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						//print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("RunSql").'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFileKeyExtraFields").' : <strong class="wordbreak">'.(dol_is_file($realpathtosqlextrakey) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtosqlextrakey).(dol_is_file($realpathtosqlextra) && dol_is_file($realpathtosqlextrakey) ? '' : '</span></strike>').'</strong>';
						if (dol_is_file($realpathtosqlextra) && dol_is_file($realpathtosqlextrakey)) {
							print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=sql&file='.urlencode($pathtosqlextrakey).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtosqlextrakey).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initsqlextrafields&token='.newToken().'&format=sql&file='.urlencode($pathtosqlextra).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						print '<br>';
						print '</div>';

						print '<div class="fichehalfleft smallxxxx">';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForList").' : <strong class="wordbreak"><a href="'.$urloflist.'" target="_test">'.(dol_is_file($realpathtolist) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtolist).(dol_is_file($realpathtolist) ? '' : '</span></strike>').'</a></strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtolist).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForCreateEditView").' : <strong class="wordbreak"><a href="'.$urlofcard.'?action=create" target="_test">'.(dol_is_file($realpathtocard) ? '' : '<strike>').preg_replace('/^'.strtolower($module).'\//', '', $pathtocard).(dol_is_file($realpathtocard) ? '' : '</strike>').'?action=create</a></strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtocard).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						// Page contact
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForContactTab").' : <strong class="wordbreak">'.(dol_is_file($realpathtocontact) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtocontact).(dol_is_file($realpathtocontact) ? '' : '</span></strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtocontact).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtocontact)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtocontact).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initpagecontact&token='.newToken().'&format=php&file='.urlencode($pathtocontact).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						print '<br>';
						// Page document
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForDocumentTab").' : <strong class="wordbreak">'.(dol_is_file($realpathtodocument) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtodocument).(dol_is_file($realpathtodocument) ? '' : '</span></strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtodocument).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtodocument)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtodocument).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initpagedocument&token='.newToken().'&format=php&file='.urlencode($pathtocontact).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						print '<br>';
						// Page notes
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForNoteTab").' : <strong class="wordbreak">'.(dol_is_file($realpathtonote) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtonote).(dol_is_file($realpathtonote) ? '' : '</span></strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtonote).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtonote)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtonote).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initpagenote&token='.newToken().'&format=php&file='.urlencode($pathtocontact).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						print '<br>';
						// Page agenda
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForAgendaTab").' : <strong class="wordbreak">'.(dol_is_file($realpathtoagenda) ? '' : '<strike><span class="opacitymedium">').preg_replace('/^'.strtolower($module).'\//', '', $pathtoagenda).(dol_is_file($realpathtoagenda) ? '' : '</span></strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&token='.newToken().'&file='.urlencode($pathtoagenda).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtoagenda)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtoagenda).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initpageagenda&token='.newToken().'&format=php&file='.urlencode($pathtocontact).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
						}
						print '<br>';
						print '<br>';

						print '</div>';

						print '<br><br><br>';

						if (!empty($tmpobject)) {
							$reflector = new ReflectionClass($tabobj);
							$reflectorproperties = $reflector->getProperties(); // Can also use get_object_vars
							$reflectorpropdefault = $reflector->getDefaultProperties(); // Can also use get_object_vars
							//$propstat = $reflector->getStaticProperties();
							//var_dump($reflectorpropdefault);

							print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
							print '<input type="hidden" name="token" value="'.newToken().'">';
							print '<input type="hidden" name="action" value="addproperty">';
							print '<input type="hidden" name="tab" value="objects">';
							print '<input type="hidden" name="page_y" value="">';
							print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module.($forceddirread ? '@'.$dirread : '')).'">';
							print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

							print '<input class="button smallpaddingimp" type="submit" name="regenerateclasssql" value="'.$langs->trans("RegenerateClassAndSql").'">';
							print '<br><br>';

							$mod = strtolower($module);
							$obj = strtolower($tabobj);
							$newproperty = dolGetButtonTitle($langs->trans('NewProperty'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/modulebuilder/index.php?tab=objects&module='.urlencode($module).'&tabobj=createproperty&obj='.urlencode($tabobj));
							$nbOfProperties = count($reflectorpropdefault['fields']);

							print_barre_liste($langs->trans("ObjectProperties"), 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, $nbOfProperties, '', 0, $newproperty, '', 0, 0, 0, 1);

							//var_dump($reflectorpropdefault);exit;
							print '<!-- Table with properties of object -->'."\n";
							print '<div class="div-table-responsive">';
							print '<table class="noborder small">';
							print '<tr class="liste_titre">';
							print '<th class="tdsticky tdstickygray">';
							$htmltext = $langs->trans("PropertyDesc").'<br><br><a class="" href="https://wiki.dolibarr.org/index.php/Language_and_development_rules#Table_and_fields_structures" target="_blank" rel="noopener noreferrer external">'.$langs->trans("SeeExamples").'</a>';
							print $form->textwithpicto($langs->trans("Code"), $htmltext, 1, 'help', 'extracss', 0, 3, 'propertyhelp');
							print '</th>';
							print '<th>';
							print $form->textwithpicto($langs->trans("Label"), $langs->trans("YouCanUseTranslationKey"));
							print '</th>';
							print '<th>'.$form->textwithpicto($langs->trans("Type"), $langs->trans("TypeOfFieldsHelpIntro").'<br><br>'.$langs->trans("TypeOfFieldsHelp"), 1, 'help', 'extracss', 0, 3, 'typehelp').'</th>';
							print '<th>'.$form->textwithpicto($langs->trans("ArrayOfKeyValues"), $langs->trans("ArrayOfKeyValuesDesc")).'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("NotNull"), $langs->trans("NotNullDesc")).'</th>';
							print '<th class="center">'.$langs->trans("DefaultValue").'</th>';
							print '<th class="center">'.$langs->trans("DatabaseIndex").'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("ForeignKey"), $langs->trans("ForeignKeyDesc"), 1, 'help', 'extracss', 0, 3, 'foreignkeyhelp').'</th>';
							print '<th class="right">'.$langs->trans("Position").'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("Enabled"), $langs->trans("EnabledDesc"), 1, 'help', 'extracss', 0, 3, 'enabledhelp').'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("Visibility"), $langs->trans("VisibleDesc").'<br><br>'.$langs->trans("ItCanBeAnExpression"), 1, 'help', 'extracss', 0, 3, 'visiblehelp').'</th>';
							print '<th class="center">'.$langs->trans("NotEditable").'</th>';
							//print '<th class="center">'.$langs->trans("AlwaysEditable").'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("SearchAll"), $langs->trans("SearchAllDesc")).'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("IsAMeasure"), $langs->trans("IsAMeasureDesc")).'</th>';
							print '<th class="center">'.$langs->trans("CSSClass").'</th>';
							print '<th class="center">'.$langs->trans("CSSViewClass").'</th>';
							print '<th class="center">'.$langs->trans("CSSListClass").'</th>';
							print '<th>'.$langs->trans("KeyForTooltip").'</th>';
							print '<th class="center">'.$langs->trans("ShowOnCombobox").'</th>';
							//print '<th class="center">'.$langs->trans("Disabled").'</th>';
							print '<th>'.$form->textwithpicto($langs->trans("Validate"), $langs->trans("ValidateModBuilderDesc")).'</th>';
							print '<th>'.$langs->trans("Comment").'</th>';
							print '<th class="tdstickyright tdstickyghostwhite"></th>';
							print '</tr>';

							// We must use $reflectorpropdefault['fields'] to get list of fields because $tmpobject->fields may have been
							// modified during the constructor and we want value into head of class before constructor is called.
							//$properties = dol_sort_array($tmpobject->fields, 'position');
							$properties = dol_sort_array($reflectorpropdefault['fields'], 'position');
							if (!empty($properties)) {
								// List of existing properties
								foreach ($properties as $propkey => $propval) {
									/* If from Reflection
									 if ($propval->class == $tabobj)
									 {
									 $propname=$propval->getName();
									 $comment=$propval->getDocComment();
									 $type=gettype($tmpobject->$propname);
									 $default=$propdefault[$propname];
									 // Discard generic properties
									 if (in_array($propname, array('element', 'childtables', 'table_element', 'table_element_line', 'class_element_line', 'ismultientitymanaged'))) continue;

									 // Keep or not lines
									 if (in_array($propname, array('fk_element', 'lines'))) continue;
									 }*/

									$propname = $propkey;
									$proplabel = $propval['label'];
									$proptype = $propval['type'];
									$proparrayofkeyval = !empty($propval['arrayofkeyval']) ? $propval['arrayofkeyval'] : '';
									$propnotnull = !empty($propval['notnull']) ? $propval['notnull'] : '0';
									$propdefault = !empty($propval['default']) ? $propval['default'] : '';
									$propindex = !empty($propval['index']) ? $propval['index'] : '';
									$propforeignkey = !empty($propval['foreignkey']) ? $propval['foreignkey'] : '';
									$propposition = $propval['position'];
									$propenabled = $propval['enabled'];
									$propvisible = $propval['visible'];
									$propnoteditable = !empty($propval['noteditable']) ? $propval['noteditable'] : 0;
									//$propalwayseditable = !empty($propval['alwayseditable'])?$propval['alwayseditable']:0;
									$propsearchall = !empty($propval['searchall']) ? $propval['searchall'] : 0;
									$propisameasure = !empty($propval['isameasure']) ? $propval['isameasure'] : 0;
									$propcss = !empty($propval['css']) ? $propval['css'] : '';
									$propcssview = !empty($propval['cssview']) ? $propval['cssview'] : '';
									$propcsslist = !empty($propval['csslist']) ? $propval['csslist'] : '';
									$prophelp = !empty($propval['help']) ? $propval['help'] : '';
									$propshowoncombobox = !empty($propval['showoncombobox']) ? $propval['showoncombobox'] : 0;
									//$propdisabled=$propval['disabled'];
									$propvalidate = !empty($propval['validate']) ? $propval['validate'] : 0;
									$propcomment = !empty($propval['comment']) ? $propval['comment'] : '';

									print '<!-- line for object property -->'."\n";
									print '<tr class="oddeven">';

									print '<td class="tdsticky tdstickygray">';
									print dol_escape_htmltag($propname);
									print '</td>';
									if ($action == 'editproperty' && $propname == $propertykey) {
										print '<td>';
										print '<input type="hidden" name="propname" value="'.dol_escape_htmltag($propname).'">';
										print '<input name="proplabel" class="maxwidth125" value="'.dol_escape_htmltag($proplabel).'">';
										print '</td>';
										print '<td class="tdoverflowmax150">';
										print '<input name="proptype" class="maxwidth125" value="'.dol_escape_htmltag($proptype).'"></input>';
										print '</td>';
										print '<td class="tdoverflowmax200">';
										print '<textarea name="proparrayofkeyval">';
										if (isset($proparrayofkeyval)) {
											if (is_array($proparrayofkeyval) || $proparrayofkeyval != '') {
												print dol_escape_htmltag(json_encode($proparrayofkeyval, JSON_UNESCAPED_UNICODE));
											}
										}
										print '</textarea>';
										print '</td>';
										print '<td>';
										print '<input class="center width50" name="propnotnull" value="'.dol_escape_htmltag($propnotnull).'">';
										print '</td>';
										print '<td>';
										print '<input class="maxwidth50" name="propdefault" value="'.dol_escape_htmltag($propdefault).'">';
										print '</td>';
										print '<td class="center">';
										print '<input class="center maxwidth50" name="propindex" value="'.dol_escape_htmltag($propindex).'">';
										print '</td>';
										print '<td>';
										print '<input class="center maxwidth100" name="propforeignkey" value="'.dol_escape_htmltag($propforeignkey).'">';
										print '</td>';
										print '<td>';
										print '<input class="right width50" name="propposition" value="'.dol_escape_htmltag($propposition).'">';
										print '</td>';
										print '<td>';
										print '<input class="center width75" name="propenabled" value="'.dol_escape_htmltag($propenabled).'">';
										print '</td>';
										print '<td>';
										print '<input class="center width75" name="propvisible" value="'.dol_escape_htmltag($propvisible).'">';
										print '</td>';
										print '<td>';
										print '<input class="center width50" name="propnoteditable" size="2" value="'.dol_escape_htmltag($propnoteditable).'">';
										print '</td>';
										/*print '<td>';
										print '<input class="center" name="propalwayseditable" size="2" value="'.dol_escape_htmltag($propalwayseditable).'">';
										print '</td>';*/
										print '<td>';
										print '<input class="center width50" name="propsearchall" value="'.dol_escape_htmltag($propsearchall).'">';
										print '</td>';
										print '<td>';
										print '<input class="center width50" name="propisameasure" value="'.dol_escape_htmltag($propisameasure).'">';
										print '</td>';
										print '<td>';
										print '<input class="center maxwidth50" name="propcss" value="'.dol_escape_htmltag($propcss).'">';
										print '</td>';
										print '<td>';
										print '<input class="center maxwidth50" name="propcssview" value="'.dol_escape_htmltag($propcssview).'">';
										print '</td>';
										print '<td>';
										print '<input class="center maxwidth50" name="propcsslist" value="'.dol_escape_htmltag($propcsslist).'">';
										print '</td>';
										print '<td>';
										print '<input class="maxwidth100" name="prophelp" value="'.dol_escape_htmltag($prophelp).'">';
										print '</td>';
										print '<td>';
										print '<input class="center maxwidth50" name="propshowoncombobox" value="'.dol_escape_htmltag($propshowoncombobox).'">';
										print '</td>';
										print '<td>';
										print '<input type="number" step="1" min="0" max="1" class="text maxwidth100" name="propvalidate" value="'.dol_escape_htmltag($propvalidate).'">';
										print '</td>';
										print '<td>';
										print '<input class="maxwidth100" name="propcomment" value="'.dol_escape_htmltag($propcomment).'">';
										print '</td>';
										print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
										print '<input class="reposition button smallpaddingimp" type="submit" name="edit" value="'.$langs->trans("Save").'">';
										print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
										print '</td>';
									} else {
										print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($proplabel).'">';
										print dol_escape_htmltag($proplabel);
										print '</td>';
										print '<td class="tdoverflowmax200">';
										$pictoType = '';
										$matches = array();
										if (preg_match('/^varchar/', $proptype, $matches)) {
											$pictoType = 'varchar';
										} elseif (preg_match('/^integer:/', $proptype, $matches)) {
											$pictoType = 'link';
										} elseif (strpos($proptype, 'integer') === 0) {
											$pictoType = substr($proptype, 0, 3);
										} elseif (strpos($proptype, 'timestamp') === 0) {
											$pictoType = 'datetime';
										} elseif (strpos($proptype, 'real') === 0) {
											$pictoType = 'double';
										}
										print(!empty($pictoType) ? getPictoForType($pictoType) : getPictoForType($proptype)).'<span title="'.dol_escape_htmltag($proptype).'">'.dol_escape_htmltag($proptype).'</span>';
										print '</td>';
										print '<td class="tdoverflowmax200">';
										if ($proparrayofkeyval) {
											print '<span title="'.dol_escape_htmltag(json_encode($proparrayofkeyval, JSON_UNESCAPED_UNICODE)).'">';
											print dol_escape_htmltag(json_encode($proparrayofkeyval, JSON_UNESCAPED_UNICODE));
											print '</span>';
										}
										print '</td>';
										print '<td class="center">';
										print dol_escape_htmltag($propnotnull);
										print '</td>';
										print '<td>';
										print dol_escape_htmltag($propdefault);
										print '</td>';
										print '<td class="center">';
										print $propindex ? '1' : '';
										print '</td>';
										print '<td class="center">';
										print $propforeignkey ? dol_escape_htmltag($propforeignkey) : '';
										print '</td>';
										print '<td class="right">';
										print dol_escape_htmltag($propposition);
										print '</td>';
										print '<td class="center tdoverflowmax100" title="'.($propnoteditable ? dol_escape_htmltag($propnoteditable) : '').'">';
										print $propenabled ? dol_escape_htmltag($propenabled) : '';
										print '</td>';
										// Visibility
										print '<td class="center tdoverflowmax100" title="'.($propvisible ? dol_escape_htmltag($propvisible) : '0').'">';
										print $propvisible ? dol_escape_htmltag($propvisible) : '0';
										print '</td>';
										// Readonly
										print '<td class="center tdoverflowmax100" title="'.($propnoteditable ? dol_escape_htmltag($propnoteditable) : '').'">';
										print $propnoteditable ? dol_escape_htmltag($propnoteditable) : '';
										print '</td>';
										/*print '<td class="center">';
										print $propalwayseditable ? dol_escape_htmltag($propalwayseditable) : '';
										print '</td>';*/
										print '<td class="center">';
										print $propsearchall ? '1' : '';
										print '</td>';
										print '<td class="center">';
										print $propisameasure ? dol_escape_htmltag($propisameasure) : '';
										print '</td>';
										print '<td class="center tdoverflowmax100" title="'.($propcss ? dol_escape_htmltag($propcss) : '').'">';
										print $propcss ? dol_escape_htmltag($propcss) : '';
										print '</td>';
										print '<td class="center tdoverflowmax100" title="'.($propcssview ? dol_escape_htmltag($propcssview) : '').'">';
										print $propcssview ? dol_escape_htmltag($propcssview) : '';
										print '</td>';
										print '<td class="center tdoverflowmax100" title="'.($propcsslist ? dol_escape_htmltag($propcsslist) : '').'">';
										print $propcsslist ? dol_escape_htmltag($propcsslist) : '';
										print '</td>';
										// Key for tooltop
										print '<td class="tdoverflowmax150" title="'.($prophelp ? dol_escape_htmltag($prophelp) : '').'">';
										print $prophelp ? dol_escape_htmltag($prophelp) : '';
										print '</td>';
										print '<td class="center">';
										print $propshowoncombobox ? dol_escape_htmltag($propshowoncombobox) : '';
										print '</td>';
										/*print '<td class="center">';
										print $propdisabled?$propdisabled:'';
										print '</td>';*/
										print '<td class="center">';
										print $propvalidate ? dol_escape_htmltag($propvalidate) : '';
										print '</td>';
										print '<td class="tdoverflowmax200">';
										print '<span title="'.dol_escape_htmltag($propcomment).'">';
										print dol_escape_htmltag($propcomment);
										print '</span>';
										print '</td>';
										print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
										if ($propname != 'rowid') {
											print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=editproperty&token='.newToken().'&propertykey='.urlencode($propname).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj).'">'.img_edit().'</a>';
											print '<a class="reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=deleteproperty&token='.newToken().'&propertykey='.urlencode($propname).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj).'">'.img_delete().'</a>';
										}
										print '</td>';
									}
									print '</tr>';
								}
							} else {
								if ($tab == 'specifications') {
									if ($action != 'editfile' || empty($file)) {
										print '<span class="opacitymedium">'.$langs->trans("SpecDefDesc").'</span><br>';
										print '<br>';

										$specs = dol_dir_list(dol_buildpath($modulelowercase.'/doc', 0), 'files', 1, '(\.md|\.asciidoc)$', array('\/temp\/'));

										foreach ($specs as $spec) {
											$pathtofile = $modulelowercase.'/doc/'.$spec['relativename'];
											$format = 'asciidoc';
											if (preg_match('/\.md$/i', $spec['name'])) {
												$format = 'markdown';
											}
											print '<span class="fa fa-file-o"></span> '.$langs->trans("SpecificationFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
											print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
											print '<br>';
										}
									} else {
										// Use MD or asciidoc

										//print $langs->trans("UseAsciiDocFormat").'<br>';

										$fullpathoffile = dol_buildpath($file, 0);

										$content = file_get_contents($fullpathoffile);

										// New module
										print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
										print '<input type="hidden" name="token" value="'.newToken().'">';
										print '<input type="hidden" name="action" value="savefile">';
										print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
										print '<input type="hidden" name="tab" value="'.$tab.'">';
										print '<input type="hidden" name="module" value="'.$module.'">';

										$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
										print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
										print '<br>';
										print '<center>';
										print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
										print ' &nbsp; ';
										print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
										print '</center>';

										print '</form>';
									}
								}
								print '<tr><td><span class="warning">'.$langs->trans('Property %s not found in the class. The class was probably not generated by modulebuilder.', $field).'</warning></td></tr>';
							}
							print '</table>';
							print '</div>';

							print '</form>';
						} else {
							print '<span class="warning">'.$langs->trans('Failed to init the object with the new %s (%s)', $tabobj, (string) $db).'</warning>';
						}
					} catch (Exception $e) {
						print 'ee';
						print $e->getMessage();
						print 'ff';
					}
				} else {
					if (empty($forceddirread)) {
						$fullpathoffile = dol_buildpath($file, 0);
					} else {
						$fullpathoffile = $dirread.'/'.$file;
					}

					$content = file_get_contents($fullpathoffile);

					// New module
					print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="savefile">';
					print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
					print '<input type="hidden" name="tab" value="'.$tab.'">';
					print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';
					print '<input type="hidden" name="module" value="'.$module.($forceddirread ? '@'.$dirread : '').'">';

					$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
					print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
					print '<br>';
					print '<center>';
					print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
					print ' &nbsp; ';
					print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
					print '</center>';

					print '</form>';
				}
			}

			print dol_get_fiche_end(); // Level 3
		}

		if ($tab == 'dictionaries') {
			print '<!-- tab=dictionaries -->'."\n";
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$dicts = $moduleobj->dictionaries;

			if ($action == 'deletedict') {
				$formconfirm = $form->formconfirm(
					$_SERVER["PHP_SELF"].'?dictionnarykey='.urlencode((string) (GETPOSTINT('dictionnarykey'))).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)),
					$langs->trans('Delete'),
					$langs->trans('Confirm Delete Dictionnary', GETPOST('dictionnarykey', 'alpha')),
					'confirm_deletedictionary',
					'',
					0,
					1
				);
				print $formconfirm;
			}

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">';
				$htmlhelp = $langs->trans("DictionariesDefDescTooltip", '{s1}');
				$htmlhelp = str_replace('{s1}', '<a target="adminbis" class="nofocusvisible" href="'.DOL_URL_ROOT.'/admin/dict.php">'.$langs->trans('Setup').' - '.$langs->trans('Dictionaries').'</a>', $htmlhelp);
				print $form->textwithpicto($langs->trans("DictionariesDefDesc"), $htmlhelp, 1, 'help', '', 0, 2, 'helpondesc').'<br>';
				print '</span>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=DICTIONARIES">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';
				if (is_array($dicts) && !empty($dicts)) {
					print '<span class="fa fa-file-o"></span> '.$langs->trans("LanguageFile").' :</span> ';
					print '<strong class="wordbreak">'.$dicts['langs'].'</strong>';
					print '<br>';
				}

				$head3 = array();
				$h = 0;

				// Dir for module
				//$dir = $dirread.'/'.$modulelowercase.'/class';

				$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabdic=newdictionary';
				$head3[$h][1] = '<span class="valignmiddle text-plus-circle">'.$langs->trans("NewDictionary").'</span><span class="fa fa-plus-circle valignmiddle paddingleft"></span>';
				$head3[$h][2] = 'newdictionary';
				$h++;

				// Scan for object class files
				//$listofobject = dol_dir_list($dir, 'files', 0, '\.class\.php$');

				$firstdicname = '';
				// if (!empty($dicts['tabname'])) {
				// 	foreach ($dicts['tabname'] as $key => $dic) {
				// 		$dicname = $dic;
				// 		$diclabel = $dicts['tablib'][$key];

				// 		if (empty($firstdicname)) {
				// 			$firstdicname = $dicname;
				// 		}

				// 		$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabdic='.$dicname;
				// 		$head3[$h][1] = $diclabel;
				// 		$head3[$h][2] = $dicname;
				// 		$h++;
				// 	}
				// }

				// if ($h > 1) {
				// 	$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabdic=deletedictionary';
				// 	$head3[$h][1] = $langs->trans("DangerZone");
				// 	$head3[$h][2] = 'deletedictionary';
				// 	$h++;
				// }

				// If tabobj was not defined, then we check if there is one obj. If yes, we force on it, if no, we will show tab to create new objects.
				// if ($tabdic == 'newdicifnodic') {
				// 	if ($firstdicname) {
				// 		$tabdic = $firstdicname;
				// 	} else {
				// 		$tabdic = 'newdictionary';
				// 	}
				// }
				//print dol_get_fiche_head($head3, $tabdic, '', -1, ''); // Level 3


				$newdict = dolGetButtonTitle($langs->trans('NewDictionary'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/modulebuilder/index.php?tab=dictionaries&module='.urlencode($module).'&tabdic=newdictionary');
				print_barre_liste($langs->trans("ListOfDictionariesEntries"), '', $_SERVER["PHP_SELF"], '', '', '', '', 0, '', '', 0, $newdict, '', 0, 0, 0, 1);

				if ($tabdic != 'newdictionary') {
					print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="addDictionary">';
					print '<input type="hidden" name="tab" value="dictionaries">';
					print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
					print '<input type="hidden" name="tabdic" value="'.dol_escape_htmltag($tabdic).'">';

					print '<div class="div-table-responsive">';
					print '<table class="noborder">';

					print '<tr class="liste_titre">';
					print_liste_field_titre("#", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'thsticky thstickygrey ');
					print_liste_field_titre("Table", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("Label", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("SQL", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("SQLSort", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("FieldsView", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("FieldsEdit", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("FieldsInsert", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("Rowid", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("Condition", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print_liste_field_titre("", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
					print "</tr>\n";

					if (!empty($dicts) && is_array($dicts) && !empty($dicts['tabname']) && is_array($dicts['tabname'])) {
						$i = 0;
						$maxi = count($dicts['tabname']);
						while ($i < $maxi) {
							if ($action == 'editdict' && $i == GETPOSTINT('dictionnarykey') - 1) {
								print '<tr class="oddeven">';
								print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
								print '<input type="hidden" name="token" value="'.newToken().'">';
								print '<input type="hidden" name="tab" value="dictionaries">';
								print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
								print '<input type="hidden" name="action" value="updatedictionary">';
								print '<input type="hidden" name="dictionnarykey" value="'.($i + 1).'">';

								print '<td class="tdsticky tdstickygray">';
								print($i + 1);
								print '</td>';

								print '<td>';
								print '<input type="text" name="tabname" value="'.$dicts['tabname'][$i].'" readonly class="tdstickygray">';
								print '</td>';

								print '<td>';
								print '<input type="text" name="tablib" value="'.$dicts['tablib'][$i].'">';
								print '</td>';

								print '<td>';
								print '<input type="text" name="tabsql" value="'.$dicts['tabsql'][$i].'" readonly class="tdstickygray">';
								print '</td>';

								print '<td>';
								print '<select name="tabsqlsort">';
								print '<option value="'.dol_escape_htmltag($dicts['tabsqlsort'][$i]).'">'.$dicts['tabsqlsort'][$i].'</option>';
								print '</select>';
								print '</td>';

								print '<td><select  name="tabfield" >';
								print '<option value="'.dol_escape_htmltag($dicts['tabfield'][$i]).'">'.$dicts['tabfield'][$i].'</option>';
								print '</select></td>';

								print '<td><select  name="tabfieldvalue" >';
								print '<option value="'.dol_escape_htmltag($dicts['tabfieldvalue'][$i]).'">'.$dicts['tabfieldvalue'][$i].'</option>';
								print '</select></td>';

								print '<td><select  name="tabfieldinsert" >';
								print '<option value="'.dol_escape_htmltag($dicts['tabfieldinsert'][$i]).'">'.$dicts['tabfieldinsert'][$i].'</option>';
								print '</select></td>';

								print '<td>';
								print '<input type="text" name="tabrowid"  value="'.dol_escape_htmltag($dicts['tabrowid'][$i]).'" readonly class="tdstickygray">';
								print '</td>';

								print '<td>';
								print '<input type="text" name="tabcond"  value="'.dol_escape_htmltag((empty($dicts['tabcond'][$i]) ? 'disabled' : 'enabled')).'" readonly class="tdstickygray">';
								print '</td>';

								print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
								print '<input id ="updatedict" class="reposition button smallpaddingimp" type="submit" name="updatedict" value="'.$langs->trans("Modify").'"/>';
								print '<br>';
								print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
								print '</td>';

								print '</form>';
								print '</tr>';
							} else {
								print '<tr class="oddeven">';

								print '<td class="tdsticky tdstickygray">';
								print($i + 1);
								print '</td>';

								print '<td>';
								print $dicts['tabname'][$i];
								print '</td>';

								print '<td>';
								print $dicts['tablib'][$i];
								print '</td>';

								print '<td>';
								print $dicts['tabsql'][$i];
								print '</td>';

								print '<td>';
								print $dicts['tabsqlsort'][$i];
								print '</td>';

								print '<td>';
								print $dicts['tabfield'][$i];
								print '</td>';

								print '<td>';
								print $dicts['tabfieldvalue'][$i];
								print '</td>';

								print '<td>';
								print $dicts['tabfieldinsert'][$i];
								print '</td>';

								print '<td >';
								print $dicts['tabrowid'][$i];
								print '</td>';

								print '<td >';
								print $dicts['tabcond'][$i];
								print '</td>';

								print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
								print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=editdict&token='.newToken().'&dictionnarykey='.urlencode((string) ($i + 1)).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)).'">'.img_edit().'</a>';
								print '<a class="marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=deletedict&token='.newToken().'&dictionnarykey='.urlencode((string) ($i + 1)).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)).'">'.img_delete().'</a>';
								print '</td>';

								print '</tr>';
							}
							$i++;
						}
					} else {
						print '<tr><td colspan="11"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
					}

					print '</table>';
					print '</div>';

					print '</form>';
				}

				if ($tabdic == 'newdictionary') {
					// New dic tab
					print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="initdic">';
					print '<input type="hidden" name="tab" value="dictionaries">';
					print '<input type="hidden" name="tabdic" value="'.$tabdic.'">';

					print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

					print '<span class="opacitymedium">'.$langs->trans("EnterNameOfDictionaryDesc").'</span><br><br>';

					print dol_get_fiche_head();
					print '<table class="border centpercent">';
					print '<tbody>';
					print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Table").'</td><td><input type="text" name="dicname" maxlength="64" value="'.dol_escape_htmltag(GETPOST('dicname', 'alpha') ? GETPOST('dicname', 'alpha') : $modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("DicKey")).'" autofocus></td>';
					print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" value="'.dol_escape_htmltag(GETPOST('label', 'alpha')).'"></td></tr>';
					print '<tr><td class="titlefieldcreate">'.$langs->trans("SQL").'</td><td><input type="text" style="width:50%;" name="sql" value="'.dol_escape_htmltag(GETPOST('sql', 'alpha')).'"></td></tr>';
					print '<tr><td class="titlefieldcreate">'.$langs->trans("SQLSort").'</td><td><input type="text" name="sqlsort" value="'.dol_escape_htmltag(GETPOST('sqlsort', 'alpha')).'" readonly></td></tr>';
					print '<tr><td class="titlefieldcreate">'.$langs->trans("FieldsView").'</td><td><input type="text" name="field" value="'.dol_escape_htmltag(GETPOST('field', 'alpha')).'"></td></tr>';
					print '<tr><td class="titlefieldcreate">'.$langs->trans("FieldsEdit").'</td><td><input type="text" name="fieldvalue" value="'.dol_escape_htmltag(GETPOST('fieldvalue', 'alpha')).'"></td></tr>';
					print '<tr><td class="titlefieldcreate">'.$langs->trans("FieldsInsert").'</td><td><input type="text" name="fieldinsert" value="'.dol_escape_htmltag(GETPOST('fieldinsert', 'alpha')).'"></td></tr>';
					print '<tr><td class="titlefieldcreate">'.$langs->trans("Rowid").'</td><td><input type="text" name="rowid" value="'.dol_escape_htmltag(GETPOST('rowid', 'alpha')).'"></td></tr>';
					print '<tr></tr>';
					print '</tbody></table>';
					print '<input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("GenerateCode")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
					print '<input id="cancel" type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
					print dol_get_fiche_end();
					print '</form>';
					print '<script>
					$(document).ready(function() {
						$("input[name=\'dicname\']").on("blur", function() {
							if ($(this).val().length > 0) {
								$("input[name=\'label\']").val($(this).val());
								$("input[name=\'sql\']").val("SELECT f.rowid as rowid, f.code, f.label, f.active FROM llx_c_" + $(this).val() + " as f");
								$("input[name=\'sqlsort\']").val("label ASC");
								$("input[name=\'field\']").val("code,label");
								$("input[name=\'fieldvalue\']").val("code,label");
								$("input[name=\'fieldinsert\']").val("code,label");
								$("input[name=\'rowid\']").val("rowid");
							} else {
								$("input[name=\'label\']").val("");
								$("input[name=\'sql\']").val("");
								$("input[name=\'sqlsort\']").val("");
								$("input[name=\'field\']").val("");
								$("input[name=\'fieldvalue\']").val("");
								$("input[name=\'fieldinsert\']").val("");
								$("input[name=\'rowid\']").val("");
							}
						});
						$("input[id=\'cancel\']").click(function() {
							window.history.back();
						});
					});
					</script>';

					/*print '<br>';
					print '<br>';
					print '<br>';
					print '<span class="opacitymedium">'.$langs->trans("or").'</span>';
					print '<br>';
					print '<br>';
					//print '<input type="checkbox" name="initfromtablecheck"> ';
					print $langs->trans("InitStructureFromExistingTable");
					print '<input type="text" name="initfromtablename" value="" placeholder="'.$langs->trans("TableName").'">';
					print '<input type="submit" class="button smallpaddingimp" name="createtablearray" value="'.dol_escape_htmltag($langs->trans("GenerateCode")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
					print '<br>';
					*/
				} elseif ($tabdic == 'deletedictionary') {
					// Delete dic tab
					print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="confirm_deletedictionary">';
					print '<input type="hidden" name="tab" value="dictionaries">';
					print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

					print $langs->trans("EnterNameOfDictionnaryToDeleteDesc").'<br><br>';

					print '<input type="text" name="dicname" value="'.dol_escape_htmltag($modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("DicKey")).'">';
					print '<input type="submit" class="button smallpaddingimp" name="delete" value="'.dol_escape_htmltag($langs->trans("Delete")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
					print '</form>';
				}

				print dol_get_fiche_end();
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'menus') {
			print '<!-- tab=menus -->'."\n";
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
			$dirins = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
			$destdir = $dirins.'/'.strtolower($module);
			$listofobject = dol_dir_list($destdir.'/class', 'files', 0, '\.class\.php$');
			$objects = dolGetListOfObjectClasses($destdir);

			$leftmenus = array();

			$menus = $moduleobj->menu;

			$permissions = $moduleobj->rights;
			$crud = array('read' => 'CRUDRead', 'write' => 'CRUDCreateWrite', 'delete' => 'Delete');

			//grouped permissions
			$groupedRights = array();
			foreach ($permissions as $right) {
				$key = $right[4];
				if (!isset($groupedRights[$key])) {
					$groupedRights[$key] = array();
				}
				$groupedRights[$key][] = $right;
			}
			$groupedRights_json = json_encode($groupedRights);

			if ($action == 'deletemenu') {
				$formconfirms = $form->formconfirm(
					$_SERVER["PHP_SELF"].'?menukey='.urlencode((string) (GETPOSTINT('menukey'))).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)),
					$langs->trans('Delete'),
					($menus[GETPOST('menukey')]['fk_menu'] === 'fk_mainmenu='.strtolower($module) ? $langs->trans('Warning: you will delete all menus linked to this one.', GETPOSTINT('menukey')) : $langs->trans('Confirm Delete Menu', GETPOSTINT('menukey'))),
					'confirm_deletemenu',
					'',
					0,
					1
				);
				print $formconfirms;
			}
			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">';
				$htmlhelp = $langs->trans("MenusDefDescTooltip", '{s1}');
				$htmlhelp = str_replace('{s1}', '<a target="adminbis" class="nofocusvisible" href="'.DOL_URL_ROOT.'/admin/menus/index.php">'.$langs->trans('Setup').' - '.$langs->trans('Menus').'</a>', $htmlhelp);
				print $form->textwithpicto($langs->trans("MenusDefDesc"), $htmlhelp, 1, 'help', '', 0, 2, 'helpondesc').'<br>';
				print '</span>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=TOPMENU">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';

				print '<br>';
				print load_fiche_titre($langs->trans("ListOfMenusEntries"), '', '');

				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addmenu">';
				print '<input type="hidden" name="tab" value="menus">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

				print '<div class="div-table-responsive">';
				print '<table class="noborder small">';

				$htmltextenabled = '<u>'.$langs->trans("Examples").':</u><br>';
				$htmltextenabled .= '1 <span class="opacitymedium">(module always enabled)</span><br>';
				$htmltextenabled .= '0 <span class="opacitymedium">(module always disabled)</span><br>';
				$htmltextenabled .= 'isModEnabled(\''.dol_escape_htmltag(strtolower($module)).'\') <span class="opacitymedium">(enabled when module is enabled)</span>';
				$htmltextperms = '<u>'.$langs->trans("Examples").':</u><br>';
				$htmltextperms .= '1 <span class="opacitymedium">(access always allowed)</span><br>';
				$htmltextperms .= '$user->hasright(\''.dol_escape_htmltag(strtolower($module)).'\', \'myobject\', \'read\') <span class="opacitymedium">(access allowed if user has permission module->object->read)</span>';

				print '<tr class="liste_titre">';
				print_liste_field_titre("#", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'center tdsticky tdstickygray ');
				print_liste_field_titre("Position", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Title", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'center');
				print_liste_field_titre("LinkToParentMenu", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'minwidth100 ');
				print_liste_field_titre("mainmenu", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("leftmenu", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("URL", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, '', $langs->transnoentitiesnoconv('DetailUrl'));
				print_liste_field_titre("LanguageFile", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Position", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("Enabled", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'center ', $langs->trans('DetailEnabled').'<br><br>'.$htmltextenabled);
				print_liste_field_titre("Rights", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, '', $langs->trans('DetailRight').'<br><br>'.$htmltextperms);
				print_liste_field_titre("Target", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, '', $langs->trans('DetailTarget'));
				print_liste_field_titre("MenuForUsers", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'center minwidth100 ', $langs->trans('DetailUser'));
				print_liste_field_titre("", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'center ', $langs->trans(''));
				print "</tr>\n";

				$r = count($menus) + 1;
				// for adding menu on module
				print '<tr>';
				print '<td class="center tdsticky tdstickygray"><input type="hidden" readonly class="center maxwidth50" name="propenabled" value="#"></td>';
				print '<td class="center">';
				print '<select class="maxwidth50" name="type">';
				print '<option value="">'.$langs->trans("........").'</option><option value="'.dol_escape_htmltag("left").'">left</option><option value="'.dol_escape_htmltag("top").'">top</option>';
				print '</select></td>';
				print '<td class="left"><input type="text" class="left maxwidth100" name="titre" value="'.dol_escape_htmltag(GETPOST('titre', 'alpha')).'"></td>';
				print '<td class="left">';
				print '<select name="fk_menu">';
				print '<option value="">'.$langs->trans("........").'</option>';
				foreach ($menus as $obj) {
					if ($obj['type'] == 'left' && !empty($obj['leftmenu'])) {
						print "<option value=".strtolower($obj['leftmenu']).">".$obj['leftmenu']."</option>";
					}
				}
				print '</select>';
				print '</td>';
				print '<td class="left"><input type="text" class="left maxwidth50" name="mainmenu" value="'.(empty(GETPOST('mainmenu')) ? strtolower($module) : dol_escape_htmltag(GETPOST('mainmenu', 'alpha'))).'"></td>';
				print '<td class="center"><input id="leftmenu" type="text" class="left maxwidth50" name="leftmenu" value="'.dol_escape_htmltag(GETPOST('leftmenu', 'alpha')).'"></td>';
				// URL
				print '<td class="left"><input id="url" type="text" class="left maxwidth100" name="url" value="'.dol_escape_htmltag(GETPOST('url', 'alpha')).'"></td>';
				print '<td class="left"><input type="text" class="left maxwidth75" name="langs" value="'.strtolower($module).'@'.strtolower($module).'" readonly></td>';
				// Position
				print '<td class="center"><input type="text" class="center maxwidth50 tdstickygray" name="position" value="'.(1000 + $r).'" readonly></td>';
				// Enabled
				print '<td class="center">';
				print '<input type="enabled" class="maxwidth125" value="'.dol_escape_htmltag(GETPOSTISSET('enabled') ? GETPOST('enabled') : 'isModEnabled(\''.$module.'\')').'">';
				/*
				print '<select class="maxwidth" name="enabled">';
				print '<option value="1" selected>'.$langs->trans("Show").'</option>';
				print '<option value="0">'.$langs->trans("Hide").'</option>';
				print '</select>';
				*/
				print '</td>';
				// Perms
				print '<td class="left">';
				print '<select class="maxwidth" name="objects" id="objects">';
				print '<option value=""></option>';
				if (is_array($objects)) {
					foreach ($objects as $value) {
						print '<option value="'.strtolower($value).'">'.dol_escape_htmltag(strtolower($value)).'</option>';
					}
				}
				print '</select>';
				print '<select class="maxwidth hideobject" name="perms" id="perms">';
				print '</select>';
				print '</td>';
				print '<td class="center"><input type="text" class="center maxwidth50" name="target" value="'.dol_escape_htmltag(GETPOST('target', 'alpha')).'"></td>';
				print '<td class="center"><select class="maxwidth10" name="user"><option value="2">'.$langs->trans("AllMenus").'</option><option value="0">'.$langs->trans("Internal").'</option><option value="1">'.$langs->trans("External").'</option></select></td>';

				print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
				print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
				print '</td>';
				print '</tr>';
				// end form for add menu

				//var_dump($menus);

				// Loop on each menu entry
				if (count($menus)) {
					$i = 0;
					foreach ($menus as $menu) {
						$i++;
						//for get parent in menu
						$string = dol_escape_htmltag($menu['fk_menu']);
						$value = substr($string, strpos($string, 'fk_leftmenu=') + strlen('fk_leftmenu='));

						$propFk_menu = !empty($menu['fk_menu']) ? $menu['fk_menu'] : GETPOST('fk_menu');
						$propTitre = !empty($menu['titre']) ? $menu['titre'] : GETPOST('titre');
						$propMainmenu = !empty($menu['mainmenu']) ? $menu['mainmenu'] : GETPOST('mainmenu');
						$propLeftmenu = !empty($menu['leftmenu']) ? $menu['leftmenu'] : GETPOST('leftmenu');
						$propUrl = !empty($menu['url']) ? $menu['url'] : GETPOST('url', 'alpha');
						$propPerms = !empty($menu['perms']) ? $menu['perms'] : GETPOST('perms');
						$propUser = !empty($menu['user']) ? $menu['user'] : GETPOST('user');
						$propTarget = !empty($menu['target']) ? $menu['target'] : GETPOST('target');
						$propEnabled = !empty($menu['enabled']) ? $menu['enabled'] : GETPOST('enabled');

						$objPerms = (empty($arguments[1]) ? '' : trim($arguments[1]));
						$valPerms = (empty($arguments[2]) ? '' : trim($arguments[2]));

						//$tabobject = '';	// We can't know what is $tabobject in most cases

						if ($action == 'editmenu' && GETPOSTINT('menukey') == $i) {
							//var_dump($propPerms);exit;
							print '<tr class="oddeven">';
							print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
							print '<input type="hidden" name="token" value="'.newToken().'">';
							print '<input type="hidden" name="action" value="update_menu">';
							print '<input type="hidden" name="tab" value="menus">';
							print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
							print '<input type="hidden" name="menukey" value="'.$i.'"/>';
							//print '<input type="hidden" name="tabobject" value="'.dol_escape_htmltag($tabobject).'">';
							print '<td class="tdsticky tdstickygray">';
							print $i;
							print '</td>';
							// Position (top, left)
							print '<td class="center">
									<select class="center maxwidth50" name="type">
										<option value="'.dol_escape_htmltag($menu['type']).'">
										'.dol_escape_htmltag($menu['type']).'
										</option>';
							print '<option value="'.($menu['type'] == 'left' ? 'top' : 'left').'">';
							if ($menu['type'] == 'left') {
								print 'top';
							} else {
								print 'left';
							}
							print '</option></select></td>';
							// Title
							print '<td><input type="text" class="left maxwidth100" name="titre" value="'.dol_escape_htmltag($propTitre).'"></td>';
							// Parent menu
							print '<td>';
							/*print '<select name="fk_menu" class="left maxwidth">';
							print '<option value="'.dol_escape_htmltag($propFk_menu).'">'.dol_escape_htmltag($value).'</option>';
							foreach ($menus as $obj) {
								if ($obj['type'] == 'left' && $obj['leftmenu'] != $value && $obj['leftmenu'] != $menu['leftmenu']) {
									print "<option value=".strtolower($obj['leftmenu']).">".$obj['leftmenu']."</option>";
								}
							}
							print '</select>';*/
							print '<input type="text" name="fk_menu" class="maxwidth150" value="'.dol_escape_htmltag($propFk_menu).'">';
							print '</td>';
							print '<td><input type="text" class="left maxwidth50" name="mainmenu" value="'.dol_escape_htmltag($propMainmenu).'" readonly></td>';
							print '<td><input type="text" class="left maxwidth50" name="leftmenu" value="'.dol_escape_htmltag($propLeftmenu).'" readonly></td>';
							// URL
							print '<td><input type="text" class="left maxwidth250" name="url" value="'.dol_escape_htmltag($propUrl).'"></td>';
							print '<td><input type="text" class="left maxwidth50" name="langs" value="'.strtolower($module).'@'.strtolower($module).'" readonly></td>';
							// Position
							print '<td class="center"><input type="text" class="center maxwidth50 tdstickygray" name="position" value="'.($menu['position']).'" readonly></td>';
							// Enabled
							print '<td class="nowraponall">';
							print '<input type="text" class="maxwidth125" name="enabled" value="'.dol_escape_htmltag($propEnabled != '' ? $propEnabled : "isModEnabled('".dol_escape_htmltag($module)."')").'">';
							$htmltext = '<u>'.$langs->trans("Examples").':</u><br>';
							$htmltext .= '1 <span class="opacitymedium">(always enabled)</span><br>';
							$htmltext .= '0 <span class="opacitymedium">(always disabled)</span><br>';
							$htmltext .= 'isModEnabled(\''.dol_escape_htmltag($module).'\') <span class="opacitymedium">(enabled when module is enabled)</span><br>';
							print $form->textwithpicto('', $htmltext);
							/*
							print '<select class="maxwidth50" name="enabledselect">';
							print '<option value="1">1 (always enabled)</option>';
							print '<option value="0">0 (always disabled)</option>';
							print '<option value="isModEnabled(\''.dol_escape_htmltag($module).'\')" >isModEnabled(\''.dol_escape_htmltag($module).'\')</option>';
							print '</select>';
							*/
							print '</td>';
							// Permissions
							print '<td class="nowraponall">';
							print '<input type="text" name="perms" value="'.dol_escape_htmltag($propPerms).'">';
							/*
							if (!empty($objPerms)) {
								print '<input type="hidden" name="objects" value="'.$objPerms.'" />';
								print '<select class="center maxwidth50" name="perms">';
								if (!empty($valPerms)) {
									print '<option selected value="'.dol_escape_htmltag($valPerms).'">'.dol_escape_htmltag($langs->trans($crud[$valPerms])).'</option>';
									foreach ($crud as $key => $val) {
										if ($valPerms != $key) {
											print '<option value="'.dol_escape_htmltag($key).'">'.dol_escape_htmltag($langs->trans($val)).'</option>';
										}
									}
								}
								print '</select>';
							} else {
								print '<select class="center maxwidth50" name="objects">';
								print '<option></option>';
								foreach ($objects as $obj) {
									print '<option value="'.dol_escape_htmltag(strtolower($obj)).'">'.dol_escape_htmltag($obj).'</option>';
								}
								print '</select>';
								print '<select class="center maxwidth50" name="perms">';
								foreach ($crud as $key => $val) {
									print '<option value="'.dol_escape_htmltag($key).'">'.dol_escape_htmltag($key).'</option>';
								}
								print '</select>';
							}*/
							print '</td>';
							// Target
							print '<td class="center"><input type="text" class="center maxwidth50" name="target" value="'.dol_escape_htmltag($propTarget).'"></td>';
							print '<td class="center"><select class="center maxwidth10" name="user"><option value="2">'.$langs->trans("AllMenus").'</option><option value="0">'.$langs->trans("Internal").'</option><option value="1">'.$langs->trans("External").'</option></select></td>';
							print '<td class="center minwidth75 tdstickyright tdstickyghostwhite maxwidth75">';
							print '<input class="reposition button smallpaddingimp" type="submit" name="edit" value="'.$langs->trans("Modify").'">';
							print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
							print '</td>';
							print '</form>';
							print '</tr>';
						} else {
							print '<tr class="oddeven">';

							print '<td class="tdsticky tdstickygray">';
							print $i;
							print '</td>';

							print '<td class="center">';
							print dol_escape_htmltag($menu['type']);
							print '</td>';

							// Title
							print '<td>';
							print dol_escape_htmltag($menu['titre']);
							print '</td>';

							// Parent menu
							print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($menu['fk_menu']).'">';
							print dol_escape_htmltag($menu['fk_menu']);
							print '</td>';

							print '<td>';
							print dol_escape_htmltag($menu['mainmenu']);
							print '</td>';

							print '<td>';
							print dol_escape_htmltag($menu['leftmenu']);
							print '</td>';

							print '<td class="tdoverflowmax250" title="'.dol_escape_htmltag($menu['url']).'">';
							print dol_escape_htmltag($menu['url']);
							print '</td>';

							print '<td>';
							print dol_escape_htmltag($menu['langs']);
							print '</td>';

							// Position
							print '<td class="center">';
							print dol_escape_htmltag($menu['position']);
							print '</td>';

							// Enabled
							print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($menu['enabled']).'">';
							print dol_escape_htmltag($menu['enabled']);
							print '</td>';

							// Perms
							print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($menu['perms']).'">';
							print dol_escape_htmltag($langs->trans($menu['perms']));
							print '</td>';

							// Target
							print '<td class="center tdoverflowmax200" title="'.dol_escape_htmltag($menu['target']).'">';
							print dol_escape_htmltag($menu['target']);
							print '</td>';

							print '<td class="center">';
							if ($menu['user'] == 2) {
								print $langs->trans("AllMenus");
							} elseif ($menu['user'] == 0) {
								print $langs->trans('Internal');
							} elseif ($menu['user'] == 1) {
								print $langs->trans('External');
							} else {
								print $menu['user']; // should not happen
							}
							print '</td>';
							print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
							if ($menu['titre'] != 'Module'.$module.'Name') {
								print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=editmenu&token='.newToken().'&menukey='.urlencode((string) ($i)).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)).'&tabobj='.urlencode((string) ($tabobj)).'">'.img_edit().'</a>';
								print '<a class="deletefielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=deletemenu&token='.newToken().'&menukey='.urlencode((string) ($i - 1)).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)).'">'.img_delete().'</a>';
							}
							print '</td>';
						}
						print '</tr>';
					}
				} else {
					print '<tr><td colspan="14"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
				}

				print '</table>';
				print '</div>';
				print '</form>';


				print '<script>
				$(document).ready(function() {
					//for fill in auto url
					$("#leftmenu").on("input", function() {
						var inputLeftMenu = $("#leftmenu").val();
						if (inputLeftMenu !== \'\') {
							var url = \''.dol_escape_js(strtolower($module)).'\' + inputLeftMenu + \'.php\';
							$("#url").val(url);
						}else {
							$("#url").val("");
						}
					  });

					var groupedRights = ' . $groupedRights_json . ';
					var objectsSelect = $("select[id=\'objects\']");
					var permsSelect = $("select[id=\'perms\']");

					objectsSelect.change(function() {
						var selectedObject = $(this).val();

						permsSelect.empty();

						var rights = groupedRights[selectedObject];

						if (rights) {
							for (var i = 0; i < rights.length; i++) {
								var right = rights[i];
								var option = $("<option></option>").attr("value", right[5]).text(right[5]);
								permsSelect.append(option);
							}
						} else {
							var option = $("<option></option>").attr("value", "read").text("read");
								permsSelect.append(option);
						}

						if (selectedObject !== "" && selectedObject !== null && rights) {
							permsSelect.show();
						} else {
							permsSelect.hide();
						}
						if (objectsSelect.val() === "" || objectsSelect.val() === null) {
							permsSelect.hide();
						}
					});
				});
				</script>';

				// display permissions for each object
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'permissions') {
			print '<!-- tab=permissions -->'."\n";
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$perms = $moduleobj->rights;

			// Get list of existing objects
			$dir = $dirread.'/'.$modulelowercase.'/class';
			$listofobject = dol_dir_list($dir, 'files', 0, '\.class\.php$');
			$objects = array('myobject');
			$reg = array();
			foreach ($listofobject as $fileobj) {
				$tmpcontent = file_get_contents($fileobj['fullname']);
				if (preg_match('/class\s+([^\s]*)\s+extends\s+CommonObject/ims', $tmpcontent, $reg)) {
					$objects[$fileobj['fullname']] = $reg[1];
				}
			}

			// declared select list for actions and labels permissions
			$crud = array('read' => 'CRUDRead', 'write' => 'CRUDCreateWrite', 'delete' => 'Delete');
			$labels = array("Read objects of ".$module, "Create/Update objects of ".$module, "Delete objects of ".$module);

			$action = GETPOST('action', 'alpha');

			if ($action == 'deleteright') {
				$formconfirm = $form->formconfirm(
					$_SERVER["PHP_SELF"].'?permskey='.urlencode((string) (GETPOSTINT('permskey'))).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)).'&tabobj='.urlencode((string) ($tabobj)),
					$langs->trans('Delete'),
					$langs->trans('Confirm Delete Right', GETPOST('permskey', 'alpha')),
					'confirm_deleteright',
					'',
					0,
					1
				);
				print $formconfirm;
			}

			if ($action != 'editfile' || empty($file)) {
				print '<!-- Tab to manage permissions -->'."\n";
				print '<span class="opacitymedium">';
				$htmlhelp = $langs->trans("PermissionsDefDescTooltip", '{s1}');
				$htmlhelp = str_replace('{s1}', '<a target="adminbis" class="nofocusvisible" href="'.DOL_URL_ROOT.'/admin/perms.php">'.$langs->trans('DefaultRights').'</a>', $htmlhelp);
				print $form->textwithpicto($langs->trans("PermissionsDefDesc"), $htmlhelp, 1, 'help', '', 0, 2, 'helpondesc').'<br>';
				print '</span>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=PERMISSIONS">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';

				print '<br>';
				print load_fiche_titre($langs->trans("ListOfPermissionsDefined"), '', '');

				print '<!-- form to add permissions -->'."\n";
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addright">';
				print '<input type="hidden" name="tab" value="permissions">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

				print '<div class="div-table-responsive">';
				print '<table class="noborder">';

				print '<tr class="liste_titre">';
				print_liste_field_titre("ID", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, "center");
				print_liste_field_titre("Object", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, "center");
				print_liste_field_titre("CRUD", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, "center");
				print_liste_field_titre("Label", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, "center");
				print_liste_field_titre("", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, "center");
				print "</tr>\n";

				//form for add new right
				print '<tr class="small">';
				print '<td><input type="hidden" readonly name="id" class="width75" value="0"></td>';

				print '<td><select class="minwidth100" name="permissionObj" id="permissionObj">';
				print '<option value=""></option>';
				foreach ($objects as $obj) {
					if ($obj != 'myobject') {
						print '<option value="'.$obj.'">'.$obj.'</option>';
					}
				}
				print '</select></td>';

				print '<td><select class="maxwidth75" name="crud" id="crud">';
				print '<option value=""></option>';
				foreach ($crud as $key => $val) {
					print '<option value="'.$key.'">'.$langs->trans($val).'</option>';
				}
				print '</td>';

				print '<td >';
				print '<input type="text" name="label" id="label" class="minwidth200">';
				print '</td>';

				print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
				print '<input type="submit" class="button" name="add" value="'.$langs->trans("Add").'">';
				print '</td>';
				print '</tr>';

				if (count($perms)) {
					$i = 0;
					foreach ($perms as $perm) {
						$i++;

						// section for editing right
						if ($action == 'edit_right' && $perm[0] == GETPOSTINT('permskey')) {
							print '<tr class="oddeven">';
							print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="modifPerms">';
							print '<input type="hidden" name="token" value="'.newToken().'">';
							print '<input type="hidden" name="tab" value="permissions">';
							print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
							print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';
							print '<input type="hidden" name="action" value="update_right">';
							print '<input type="hidden" name="counter" value="'.$i.'">';

							print '<input type="hidden" name="permskey" value="'.$perm[0].'">';

							print '<td class="tdsticky tdstickygray">';
							print '<input class="width75" type="text" readonly  value="'.dol_escape_htmltag($perm[0]).'"/>';
							print '</td>';

							print '<td>';
							print '<select name="crud">';
							print '<option value="'.dol_escape_htmltag($perm[5]).'">'.$langs->trans($perm[5]).'</option>';
							foreach ($crud as $i => $x) {
								if ($perm[5] != $i) {
									print '<option value="'.$i.'">'.$langs->trans(ucfirst($x)).'</option>';
								}
							}
							print '</select>';
							print '</td>';

							print '<td><select  name="permissionObj" >';
							print '<option value="'.dol_escape_htmltag($perm[4]).'">'.ucfirst($perm[4]).'</option>';
							print '</select></td>';

							print '<td>';
							print '<input type="text" name="label"  value="'.dol_escape_htmltag($perm[1]).'">';
							print '</td>';

							print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
							print '<input id ="modifyPerm" class="reposition button smallpaddingimp" type="submit" name="modifyright" value="'.$langs->trans("Modify").'"/>';
							print '<br>';
							print '<input class="reposition button button-cancel smallpaddingimp" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"/>';
							print '</td>';

							print '</form>';
							print '</tr>';
						} else {
							// $perm can be  module->object->crud or module->crud
							print '<tr class="oddeven">';

							print '<td>';
							print dol_escape_htmltag($perm[0]);
							print '</td>';

							print '<td>';
							if (in_array($perm[5], array('lire', 'read', 'creer', 'write', 'effacer', 'delete'))) {
								print dol_escape_htmltag(ucfirst($perm[4]));
							} else {
								print '';	// No particular object
							}
							print '</td>';

							print '<td>';
							if (in_array($perm[5], array('lire', 'read', 'creer', 'write', 'effacer', 'delete'))) {
								print ucfirst($langs->trans($perm[5]));
							} else {
								print ucfirst($langs->trans($perm[4]));
							}
							print '</td>';

							print '<td>';
							print $langs->trans($perm[1]);
							print '</td>';

							print '<td class="center minwidth75 tdstickyright tdstickyghostwhite">';
							print '<a class="editfielda reposition marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=edit_right&token='.newToken().'&permskey='.urlencode($perm[0]).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj).'">'.img_edit().'</a>';
							print '<a class="marginleftonly marginrighttonly paddingright paddingleft" href="'.$_SERVER["PHP_SELF"].'?action=deleteright&token='.newToken().'&permskey='.urlencode((string) ($i)).'&tab='.urlencode((string) ($tab)).'&module='.urlencode((string) ($module)).'&tabobj='.urlencode((string) ($tabobj)).'">'.img_delete().'</a>';

							print '</td>';

							print '</tr>';
						}
					}
				} else {
					print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
				}

				print '</table>';
				print '</div>';

				print '</form>';
				print '<script>
				function updateInputField() {
					value1 = $("#crud").val();
					value2 = $("#permissionObj").val();

					// Vérifie si les deux sélections sont faites
					if (value1 && value2) {
						switch(value1.toLowerCase()){
							case "read":
								$("#label").val("Read "+value2+" object of '.ucfirst($module).'")
								break;
							case "write":
								$("#label").val("Create/Update "+value2+" object of '.ucfirst($module).'")
								break;
							case "delete":
								$("#label").val("Delete "+value2+" object of '.ucfirst($module).'")
								break;
							default:
								$("#label").val("")
						}
					}
				}

				$("#crud, #permissionObj").change(function(){
					console.log("We change selection");
					updateInputField();
				});

				</script>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'hooks') {
			print '<!-- tab=hooks -->'."\n";
			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("HooksDefDesc").'</span><br>';
				print '<br>';

				print '<table>';

				$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
				print '<tr><td>';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print '</td><td>';
				print '<a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=HOOKSCONTEXTS">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				print '<tr><td>';
				$pathtohook = strtolower($module).'/class/actions_'.strtolower($module).'.class.php';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("HooksFile").' : ';
				if (dol_is_file($dirins.'/'.$pathtohook)) {
					print '<strong class="wordbreak">'.$pathtohook.'</strong>';
					print '</td>';
					print '<td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Edit"), 'edit').'</a> ';
					print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
				} else {
					print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '<a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=inithook&format=php&file='.urlencode($pathtohook).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</td>';
					print '<td></td>';
				}
				print '</tr>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'triggers') {
			print '<!-- tab=triggers -->'."\n";
			require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

			$interfaces = new Interfaces($db);
			$triggers = $interfaces->getTriggersList(array('/'.strtolower($module).'/core/triggers'));

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("TriggerDefDesc").'</span><br>';
				print '<br>';

				print '<table>';

				$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
				print '<tr><td>';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print '</td><td>';
				print '<a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=module_parts">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				if (!empty($triggers)) {
					foreach ($triggers as $trigger) {
						$pathtofile = $trigger['relpath'];

						print '<tr><td>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("TriggersFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
						print '<td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td>';
					print '<span class="fa fa-file-o"></span> '.$langs->trans("TriggersFile");
					print ' : <span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '<a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=inittrigger&format=php">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a></td>';
					print '<td></td>';
					print '</tr>';
				}

				print '</table>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'css') {
			print '<!-- tab=css -->'."\n";
			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("CSSDesc").'</span><br>';
				print '<br>';

				print '<table>';

				print '<tr><td>';
				$pathtohook = strtolower($module).'/css/'.strtolower($module).'.css.php';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("CSSFile").' : ';
				if (dol_is_file($dirins.'/'.$pathtohook)) {
					print '<strong class="wordbreak">'.$pathtohook.'</strong>';
					print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&format='.$format.'&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
				} else {
					print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initcss&format=php&file='.urlencode($pathtohook).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a></td>';
				}
				print '</tr>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'js') {
			print '<!-- tab=js -->'."\n";
			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("JSDesc").'</span><br>';
				print '<br>';

				print '<table>';

				print '<tr><td>';
				$pathtohook = strtolower($module).'/js/'.strtolower($module).'.js.php';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("JSFile").' : ';
				if (dol_is_file($dirins.'/'.$pathtohook)) {
					print '<strong class="wordbreak">'.$pathtohook.'</strong>';
					print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
				} else {
					print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initjs&token='.newToken().'&format=php&file='.urlencode($pathtohook).'">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a></td>';
				}
				print '</tr>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'widgets') {
			print '<!-- tab=widgets -->'."\n";
			require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

			$widgets = ModeleBoxes::getWidgetsList(array('/'.strtolower($module).'/core/boxes'));

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("WidgetDesc").'</span><br>';
				print '<br>';

				print '<table>';
				if (!empty($widgets)) {
					foreach ($widgets as $widget) {
						$pathtofile = $widget['relpath'];

						print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("WidgetFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("WidgetFile").' : <span class="opacitymedium">'.$langs->trans("NoWidget").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initwidget&token='.newToken().'&format=php">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
					print '</td></tr>';
				}
				print '</table>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'emailings') {
			print '<!-- tab=emailings -->'."\n";
			require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';

			$emailingselectors = MailingTargets::getEmailingSelectorsList(array('/'.strtolower($module).'/core/modules/mailings'));

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("EmailingSelectorDesc").'</span><br>';
				print '<br>';

				print '<table>';
				if (!empty($emailingselectors)) {
					foreach ($emailingselectors as $emailingselector) {
						$pathtofile = $emailingselector['relpath'];

						print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("EmailingSelectorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("EmailingSelectorFile").' : <span class="opacitymedium">'.$langs->trans("NoEmailingSelector").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initemailing&token='.newToken().'&format=php">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
					print '</td></tr>';
				}
				print '</table>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'exportimport') {
			print '<!-- tab=exportimport -->'."\n";
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$exportlist = $moduleobj->export_label;
			$importlist = $moduleobj->import_label;

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->transnoentities('ImportExportProfiles').'</span><br>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' ('.$langs->trans("ExportsArea").') : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=EXPORT">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' ('.$langs->trans("ImportArea").') : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=IMPORT">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'cli') {
			print '<!-- tab=cli -->'."\n";
			$clifiles = array();
			$i = 0;

			$dircli = array('/'.strtolower($module).'/scripts');

			foreach ($dircli as $reldir) {
				$dir = dol_buildpath($reldir, 0);
				$newdir = dol_osencode($dir);

				// Check if directory exists (we do not use dol_is_dir to avoid loading files.lib.php at each call)
				if (!is_dir($newdir)) {
					continue;
				}

				$handle = opendir($newdir);

				if (is_resource($handle)) {
					while (($tmpfile = readdir($handle)) !== false) {
						if (is_readable($newdir.'/'.$tmpfile) && preg_match('/^(.+)\.php/', $tmpfile, $reg)) {
							if (preg_match('/\.back$/', $tmpfile)) {
								continue;
							}

							$clifiles[$i]['relpath'] = preg_replace('/^\//', '', $reldir).'/'.$tmpfile;

							$i++;
						}
					}
					closedir($handle);
				}
			}

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("CLIDesc").'</span><br>';
				print '<br>';

				print '<table>';
				if (!empty($clifiles)) {
					foreach ($clifiles as $clifile) {
						$pathtofile = $clifile['relpath'];

						print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("CLIFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
						print '<td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("CLIFile").' : <span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initcli&token='.newToken().'&format=php">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a>';
					print '</td></tr>';
				}
				print '</table>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'cron') {
			print '<!-- tab=cron -->'."\n";
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$cronjobs = $moduleobj->cronjobs;

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.str_replace('{s1}', '<a target="adminbis" class="nofocusvisible" href="'.DOL_URL_ROOT.'/cron/list.php">'.$langs->transnoentities('CronList').'</a>', $langs->trans("CronJobDefDesc", '{s1}')).'</span><br>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format=php&file='.urlencode($pathtofile).'&find=CRON">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';

				print '<br>';
				print load_fiche_titre($langs->trans("CronJobProfiles"), '', '');

				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addproperty">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

				print '<div class="div-table-responsive">';
				print '<table class="noborder">';

				print '<tr class="liste_titre">';
				print_liste_field_titre("CronLabel", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("CronTask", '', '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("CronFrequency", '', "", "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("StatusAtInstall", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Comment", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder);
				print "</tr>\n";

				if (count($cronjobs)) {
					foreach ($cronjobs as $cron) {
						print '<tr class="oddeven">';

						print '<td>';
						print $cron['label'];
						print '</td>';

						print '<td>';
						$texttoshow = null;
						if ($cron['jobtype'] == 'method') {
							$text = $langs->trans("CronClass");
							$texttoshow = $langs->trans('CronModule').': '.$module.'<br>';
							$texttoshow .= $langs->trans('CronClass').': '.$cron['class'].'<br>';
							$texttoshow .= $langs->trans('CronObject').': '.$cron['objectname'].'<br>';
							$texttoshow .= $langs->trans('CronMethod').': '.$cron['method'];
							$texttoshow .= '<br>'.$langs->trans('CronArgs').': '.$cron['parameters'];
							$texttoshow .= '<br>'.$langs->trans('Comment').': '.$langs->trans($cron['comment']);
						} elseif ($cron['jobtype'] == 'command') {
							$text = $langs->trans('CronCommand');
							$texttoshow = $langs->trans('CronCommand').': '.dol_trunc($cron['command']);
							$texttoshow .= '<br>'.$langs->trans('CronArgs').': '.$cron['parameters'];
							$texttoshow .= '<br>'.$langs->trans('Comment').': '.$langs->trans($cron['comment']);
						}
						print $form->textwithpicto($text, $texttoshow, 1);
						print '</td>';

						print '<td>';
						if ($cron['unitfrequency'] == "60") {
							print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Minutes');
						}
						if ($cron['unitfrequency'] == "3600") {
							print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Hours');
						}
						if ($cron['unitfrequency'] == "86400") {
							print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Days');
						}
						if ($cron['unitfrequency'] == "604800") {
							print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Weeks');
						}
						print '</td>';

						print '<td>';
						print $cron['status'];
						print '</td>';

						print '<td>';
						if (!empty($cron['comment'])) {
							print $cron['comment'];
						}
						print '</td>';

						print '</tr>';
					}
				} else {
					print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
				}

				print '</table>';
				print '</div>';

				print '</form>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'specifications') {
			print '<!-- tab=specifications -->'."\n";
			$specs = dol_dir_list(dol_buildpath($modulelowercase.'/doc', 0), 'files', 1, '(\.md|\.asciidoc)$', array('\/temp\/'));

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">'.$langs->trans("SpecDefDesc").'</span><br>';
				print '<br>';

				print '<table>';
				if (is_array($specs) && !empty($specs)) {
					foreach ($specs as $spec) {
						$pathtofile = $modulelowercase.'/doc/'.$spec['relativename'];
						$format = 'asciidoc';
						if (preg_match('/\.md$/i', $spec['name'])) {
							$format = 'markdown';
						}
						print '<tr><td>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SpecificationFile").' : <strong class="wordbreak">'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&token='.newToken().'&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
						print '<td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td>';
					print '<span class="fa fa-file-o"></span> '.$langs->trans("SpecificationFile").' : <span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initdoc&token='.newToken().'&format=php">'.img_picto('Generate', 'generate', 'class="paddingleft"').'</a></td>';
					print '</tr>';
				}
				print '</table>';
			} else {
				// Use MD or asciidoc

				//print $langs->trans("UseAsciiDocFormat").'<br>';

				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}

			print '<br><br><br>';

			$FILENAMEDOC = $modulelowercase.'.html';
			$FILENAMEDOCPDF = $modulelowercase.'.pdf';
			$outputfiledoc = dol_buildpath($modulelowercase, 0).'/doc/'.$FILENAMEDOC;
			$outputfiledocurl = dol_buildpath($modulelowercase, 1).'/doc/'.$FILENAMEDOC;
			$outputfiledocrel = $modulelowercase.'/doc/'.$FILENAMEDOC;
			$outputfiledocpdf = dol_buildpath($modulelowercase, 0).'/doc/'.$FILENAMEDOCPDF;
			$outputfiledocurlpdf = dol_buildpath($modulelowercase, 1).'/doc/'.$FILENAMEDOCPDF;
			$outputfiledocrelpdf = $modulelowercase.'/doc/'.$FILENAMEDOCPDF;

			// HTML
			print '<span class="fa fa-file-o"></span> '.$langs->trans("PathToModuleDocumentation", "HTML").' : ';
			if (!dol_is_file($outputfiledoc)) {
				print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
			} else {
				print '<strong>';
				print '<a href="'.$outputfiledocurl.'" target="_blank" rel="noopener noreferrer">';
				print $outputfiledoc;
				print '</a>';
				print '</strong>';
				print ' <span class="opacitymedium">('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfiledoc), 'dayhour').')</span>';
				print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&format='.$format.'&file='.urlencode($outputfiledocrel).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
			}
			print '</strong><br>';

			// PDF
			print '<span class="fa fa-file-o"></span> '.$langs->trans("PathToModuleDocumentation", "PDF").' : ';
			if (!dol_is_file($outputfiledocpdf)) {
				print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
			} else {
				print '<strong>';
				print '<a href="'.$outputfiledocurlpdf.'" target="_blank" rel="noopener noreferrer">';
				print $outputfiledocpdf;
				print '</a>';
				print '</strong>';
				print ' <span class="opacitymedium">('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfiledocpdf), 'dayhour').')</span>';
				print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&format='.$format.'&file='.urlencode($outputfiledocrelpdf).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
			}
			print '</strong><br>';

			print '<br>';

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="generatedoc">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="generatedoc">';
			print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
			print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
			print '<input type="submit" class="button" name="generatedoc" value="'.$langs->trans("BuildDocumentation").'"';
			if (!is_array($specs) || empty($specs)) {
				print ' disabled="disabled"';
			}
			print '>';
			print '</form>';
		}

		if ($tab == 'buildpackage') {
			print '<!-- tab=buildpackage -->'."\n";
			print '<span class="opacitymedium">'.$langs->trans("BuildPackageDesc").'</span>';
			print '<br>';

			if (!class_exists('ZipArchive') && !defined('ODTPHP_PATHTOPCLZIP')) {
				print img_warning().' '.$langs->trans("ErrNoZipEngine");
				print '<br>';
			}

			$modulelowercase = strtolower($module);

			// Zip file to build
			$FILENAMEZIP = '';

			// Load module
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
			dol_include_once($pathtofile);
			$class = 'mod'.$module;
			$moduleobj = null;
			if (class_exists($class)) {
				try {
					$moduleobj = new $class($db);
					'@phan-var-force DolibarrMOdules $moduleobj';
				} catch (Exception $e) {
					$error++;
					dol_print_error($db, $e->getMessage());
				}
			} else {
				$error++;
				$langs->load("errors");
				dol_print_error($db, $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
				exit;
			}

			$arrayversion = explode('.', $moduleobj->version, 3);
			if (count($arrayversion)) {
				$FILENAMEZIP = "module_".$modulelowercase.'-'.$arrayversion[0].(empty($arrayversion[1]) ? '.0' : '.'.$arrayversion[1]).(empty($arrayversion[2]) ? '' : ".".$arrayversion[2]).".zip";
				$outputfilezip = dol_buildpath($modulelowercase, 0).'/bin/'.$FILENAMEZIP;
			}

			print '<br>';

			print '<span class="fa fa-file-o"></span> '.$langs->trans("PathToModulePackage").' : ';
			if (!dol_is_file($outputfilezip)) {
				print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
			} else {
				$relativepath = $modulelowercase.'/bin/'.$FILENAMEZIP;
				print '<strong><a href="'.DOL_URL_ROOT.'/document.php?modulepart=packages&file='.urlencode($relativepath).'">'.$outputfilezip.'</a></strong>';
				print ' <span class="opacitymedium">('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfilezip), 'dayhour').')</span>';
				print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&token='.newToken().'&file='.urlencode($relativepath).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
			}
			print '</strong>';

			print '<br>';

			print '<br>';

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="generatepackage">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="generatepackage">';
			print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
			print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
			print '<input type="submit" class="button" name="generatepackage" value="'.$langs->trans("BuildPackage").'">';
			print '</form>';
		}

		if ($tab == 'tabs') {
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$tabs = $moduleobj->tabs;

			if ($action != 'editfile' || empty($file)) {
				print '<span class="opacitymedium">';
				$htmlhelp = $langs->trans("TabsDefDescTooltip", '{s1}');
				$htmlhelp = str_replace('{s1}', '<a target="adminbis" class="nofocusvisible" href="'.DOL_URL_ROOT.'/admin/menus/index.php">'.$langs->trans('Setup').' - '.$langs->trans('Tabs').'</a>', $htmlhelp);
				print $form->textwithpicto($langs->trans("TabsDefDesc"), $htmlhelp, 1, 'help', '', 0, 2, 'helpondesc').'<br>';
				print '</span>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.urlencode($tab).'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'&find=TABS">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';

				print '<br>';
				print load_fiche_titre($langs->trans("ListOfTabsEntries"), '', '');

				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addproperty">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

				print '<div class="div-table-responsive">';
				print '<table class="noborder small">';

				print '<tr class="liste_titre">';
				print_liste_field_titre("ObjectType", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Tab", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Title", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("LangFile", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Condition", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Path", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print "</tr>\n";

				if (count($tabs)) {
					foreach ($tabs as $tab) {
						$parts = explode(':', $tab['data']);

						$objectType = $parts[0];
						$tabName = $parts[1];
						$tabTitle = isset($parts[2]) ? $parts[2] : '';
						$langFile = isset($parts[3]) ? $parts[3] : '';
						$condition = isset($parts[4]) ? $parts[4] : '';
						$path = isset($parts[5]) ? $parts[5] : '';

						// If we want to remove the tab, then the format is 'objecttype:tabname:optionalcondition'
						// See: https://wiki.dolibarr.org/index.php?title=Tabs_system#To_remove_an_existing_tab
						if ($tabName[0] === '-') {
							$tabTitle = '';
							$condition = isset($parts[2]) ? $parts[2] : '';
						}

						print '<tr class="oddeven">';

						print '<td>';
						print dol_escape_htmltag($parts[0]);
						print '</td>';

						print '<td>';
						if ($tabName[0] === "+") {
							print '<span class="badge badge-status4 badge-status">' . dol_escape_htmltag($tabName) . '</span>';
						} else {
							print '<span class="badge badge-status8 badge-status">' . dol_escape_htmltag($tabName) . '</span>';
						}
						print '</td>';

						print '<td>';
						print dol_escape_htmltag($tabTitle);
						print '</td>';

						print '<td>';
						print dol_escape_htmltag($langFile);
						print '</td>';

						print '<td>';
						print dol_escape_htmltag($condition);
						print '</td>';

						print '<td>';
						print dol_escape_htmltag($path);
						print '</td>';

						print '</tr>';
					}
				} else {
					print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
				}

				print '</table>';
				print '</div>';

				print '</form>';
			} else {
				$fullpathoffile = dol_buildpath($file, 0);

				$content = file_get_contents($fullpathoffile);

				// New module
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="savefile">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';

				$posCursor = (empty($find)) ? array() : array('find' => $find);
				$doleditor = new DolEditor('editfilecontent', $content, '', 300, 'Full', 'In', true, false, 'ace', 0, '99%', 0, $posCursor);
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ? GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab != 'description') {
			print dol_get_fiche_end();
		}
	}
}

print dol_get_fiche_end(); // End modules


// End of page
llxFooter();
$db->close();
