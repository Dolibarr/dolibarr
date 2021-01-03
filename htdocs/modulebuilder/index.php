<?php
/* Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019	   Nicolas ZABOURI	<info@inovea-conseil.com>
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

if (!defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1'); // Do not check anti SQL+XSS injection attack test

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/modulebuilder.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "modulebuilder", "other", "cron", "errors"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$module = GETPOST('module', 'alpha');
$tab = GETPOST('tab', 'aZ09');
$tabobj = GETPOST('tabobj', 'alpha');
$propertykey = GETPOST('propertykey', 'alpha');
if (empty($module)) $module = 'initmodule';
if (empty($tab)) $tab = 'description';
if (empty($tabobj)) $tabobj = 'newobjectifnoobj';
$file = GETPOST('file', 'alpha');

$modulename = dol_sanitizeFileName(GETPOST('modulename', 'alpha'));
$objectname = dol_sanitizeFileName(GETPOST('objectname', 'alpha'));

// Security check
if (empty($conf->modulebuilder->enabled)) accessforbidden();
if (!$user->admin && empty($conf->global->MODULEBUILDER_FOREVERYONE)) accessforbidden($langs->trans('ModuleBuilderNotAllowed'));


// Dir for custom dirs
$tmp = explode(',', $dolibarr_main_document_root_alt);
$dirins = $tmp[0];
$dirread = $dirins;
$forceddirread = 0;

$tmpdir = explode('@', $module);
if (!empty($tmpdir[1]))
{
	$module = $tmpdir[0];
	$dirread = $tmpdir[1];
	$forceddirread = 1;
}
if (GETPOST('dirins', 'alpha'))
{
	$dirread = $dirins = GETPOST('dirins', 'alpha');
	$forceddirread = 1;
}

$FILEFLAG = 'modulebuilder.txt';

$now = dol_now();
$newmask = 0;
if (empty($newmask) && !empty($conf->global->MAIN_UMASK)) $newmask = $conf->global->MAIN_UMASK;
if (empty($newmask))	// This should no happen
{
	$newmask = '0664';
}

$result = restrictedArea($user, 'modulebuilder', null);

$error = 0;

$form = new Form($db);

// Define $listofmodules
$dirsrootforscan = array($dirread);
// Add also the core modules into the list of modules to show/edit
if ($dirread != DOL_DOCUMENT_ROOT && ($conf->global->MAIN_FEATURES_LEVEL >= 2 || !empty($conf->global->MODULEBUILDER_ADD_DOCUMENT_ROOT))) { $dirsrootforscan[] = DOL_DOCUMENT_ROOT; }

// Search modules to edit
$textforlistofdirs = '<!-- Directory scanned -->'."\n";
$listofmodules = array();
$i = 0;
foreach ($dirsrootforscan as $dirread)
{
	$moduletype = 'external';
	if ($dirread == DOL_DOCUMENT_ROOT) {
		$moduletype = 'internal';
	}

	$dirsincustom = dol_dir_list($dirread, 'directories');
	if (is_array($dirsincustom) && count($dirsincustom) > 0) {
		foreach ($dirsincustom as $dircustomcursor) {
			$fullname = $dircustomcursor['fullname'];
			if (dol_is_file($fullname.'/'.$FILEFLAG))
			{
				// Get real name of module (MyModule instead of mymodule)
				$dirtoscanrel = basename($fullname).'/core/modules/';

				$descriptorfiles = dol_dir_list(dirname($fullname).'/'.$dirtoscanrel, 'files', 0, 'mod.*\.class\.php$');
				if (empty($descriptorfiles))	// If descriptor not found into module dir, we look into main module dir.
				{
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
				if ($modulenamewithcase)
				{
					$listofmodules[$dircustomcursor['name']] = array(
						'modulenamewithcase'=>$modulenamewithcase,
						'moduledescriptorrelpath'=> $moduledescriptorrelpath,
						'moduledescriptorfullpath'=>$moduledescriptorfullpath,
						'moduledescriptorrootpath'=>$dirread,
						'moduletype'=>$moduletype
					);
				}
				//var_dump($listofmodules);
			}
		}
	}

	if ($forceddirread && empty($listofmodules))    // $forceddirread is 1 if we forced dir to read with dirins=... or with module=...@mydir
	{
		$listofmodules[strtolower($module)] = array(
			'modulenamewithcase'=>$module,
			'moduledescriptorrelpath'=> 'notyetimplemented',
			'moduledescriptorfullpath'=> 'notyetimplemented',
			'moduledescriptorrootpath'=> 'notyetimplemented',
		);
	}

	// Show description of content
	$newdircustom = $dirins;
	if (empty($newdircustom)) $newdircustom = img_warning();
	// If dirread was forced to somewhere else, by using URL
	// htdocs/modulebuilder/index.php?module=Inventory@/home/ldestailleur/git/dolibarr/htdocs/product
	if (empty($i)) $textforlistofdirs .= $langs->trans("DirScanned").' : ';
	else $textforlistofdirs .= ', ';
	$textforlistofdirs .= '<strong class="wordbreakimp">'.$dirread.'</strong>';
	if ($dirread == DOL_DOCUMENT_ROOT) {
		if ($conf->global->MAIN_FEATURES_LEVEL >= 2) $textforlistofdirs .= $form->textwithpicto('', $langs->trans("ConstantIsOn", "MAIN_FEATURES_LEVEL"));
		if (!empty($conf->global->MODULEBUILDER_ADD_DOCUMENT_ROOT)) $textforlistofdirs .= $form->textwithpicto('', $langs->trans("ConstantIsOn", "MODULEBUILDER_ADD_DOCUMENT_ROOT"));
	}
	$i++;
}


/*
 * Actions
 */

if ($dirins && $action == 'initmodule' && $modulename)
{
	$modulename = ucfirst($modulename); // Force first letter in uppercase

	if (preg_match('/[^a-z0-9_]/i', $modulename))
	{
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
	}

	if (!$error)
	{
		$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
		$destdir = $dirins.'/'.strtolower($modulename);

		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename
		);

		$result = dolCopyDir($srcdir, $destdir, 0, 0, $arrayreplacement);
		//dol_mkdir($destfile);
		if ($result <= 0)
		{
			if ($result < 0)
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFailToCopyDir", $srcdir, $destdir), null, 'errors');
			} else {
				// $result == 0
				setEventMessages($langs->trans("AllFilesDidAlreadyExist", $srcdir, $destdir), null, 'warnings');
			}
		}

		if (!empty($conf->global->MODULEBUILDER_USE_ABOUT))
		{
			dol_delete_file($destdir.'/admin/about.php');
		}

		// Delete dir and files that can be generated in sub tabs later if we need them (we want a minimal module first)
		dol_delete_dir_recursive($destdir.'/build/doxygen');
		dol_delete_dir_recursive($destdir.'/core/modules/mailings');
		dol_delete_dir_recursive($destdir.'/core/modules/'.strtolower($modulename).'');
		dol_delete_dir_recursive($destdir.'/core/tpl');
		dol_delete_dir_recursive($destdir.'/core/triggers');
		dol_delete_dir_recursive($destdir.'/doc');
		dol_delete_dir_recursive($destdir.'/.tx');
		dol_delete_dir_recursive($destdir.'/core/boxes');

		dol_delete_file($destdir.'/admin/myobject_extrafields.php');

		dol_delete_file($destdir.'/sql/data.sql');
		dol_delete_file($destdir.'/sql/update_x.x.x-y.y.y.sql');

		dol_delete_file($destdir.'/class/actions_'.strtolower($modulename).'.class.php');
		dol_delete_file($destdir.'/class/api_'.strtolower($modulename).'.class.php');

		dol_delete_file($destdir.'/css/'.strtolower($modulename).'.css.php');

		dol_delete_file($destdir.'/js/'.strtolower($modulename).'.js.php');

		dol_delete_file($destdir.'/scripts/'.strtolower($modulename).'.php');

		dol_delete_file($destdir.'/test/phpunit/MyModuleFunctionnalTest.php');

		// Delete some files related to Object (because the previous dolCopyDir has copied everything)
		dol_delete_file($destdir.'/myobject_card.php');
		dol_delete_file($destdir.'/myobject_note.php');
		dol_delete_file($destdir.'/myobject_document.php');
		dol_delete_file($destdir.'/myobject_agenda.php');
		dol_delete_file($destdir.'/myobject_list.php');
		dol_delete_file($destdir.'/lib/'.strtolower($modulename).'_myobject.lib.php');
		dol_delete_file($destdir.'/test/phpunit/MyObjectTest.php');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject.sql');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject_extrafields.sql');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject.key.sql');
		dol_delete_file($destdir.'/sql/llx_'.strtolower($modulename).'_myobject_extrafields.key.sql');
		dol_delete_file($destdir.'/img/object_myobject.png');
		dol_delete_file($destdir.'/class/myobject.class.php');

		dol_delete_dir($destdir.'/class', 1);
		dol_delete_dir($destdir.'/sql', 1);
		dol_delete_dir($destdir.'/scripts', 1);
		dol_delete_dir($destdir.'/js', 1);
		dol_delete_dir($destdir.'/css', 1);
		dol_delete_dir($destdir.'/test/phpunit', 1);
		dol_delete_dir($destdir.'/test', 1);
	}

	// Edit PHP files
	if (!$error)
	{
		$listofphpfilestoedit = dol_dir_list($destdir, 'files', 1, '\.(php|MD|js|sql|txt|xml|lang)$', '', 'fullname', SORT_ASC, 0, 1);
		foreach ($listofphpfilestoedit as $phpfileval)
		{
			//var_dump($phpfileval['fullname']);
			$arrayreplacement = array(
				'mymodule'=>strtolower($modulename),
				'MyModule'=>$modulename,
				'MYMODULE'=>strtoupper($modulename),
				'My module'=>$modulename,
				'my module'=>$modulename,
				'Mon module'=>$modulename,
				'mon module'=>$modulename,
				'htdocs/modulebuilder/template'=>strtolower($modulename),
				'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
			);

			if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
				if (!empty($conf->global->MODULEBUILDER_SPECIFIC_EDITOR_NAME)) $arrayreplacement['Editor name'] = $conf->global->MODULEBUILDER_SPECIFIC_EDITOR_NAME;
				if (!empty($conf->global->MODULEBUILDER_SPECIFIC_EDITOR_URL)) $arrayreplacement['https://www.example.com'] = $conf->global->MODULEBUILDER_SPECIFIC_EDITOR_URL;
				if (!empty($conf->global->MODULEBUILDER_SPECIFIC_AUTHOR)) $arrayreplacement['---Put here your own copyright and developer email---'] = dol_print_date($now, '%Y').' '.$conf->global->MODULEBUILDER_SPECIFIC_AUTHOR;
				if (!empty($conf->global->MODULEBUILDER_SPECIFIC_VERSION)) $arrayreplacement['1.0'] = $conf->global->MODULEBUILDER_SPECIFIC_VERSION;
				if (!empty($conf->global->MODULEBUILDER_SPECIFIC_FAMILY)) $arrayreplacement['other'] = $conf->global->MODULEBUILDER_SPECIFIC_FAMILY;
			}

			$result = dolReplaceInFile($phpfileval['fullname'], $arrayreplacement);
			//var_dump($result);
			if ($result < 0)
			{
				setEventMessages($langs->trans("ErrorFailToMakeReplacementInto", $phpfileval['fullname']), null, 'errors');
			}
		}

		if (!empty($conf->global->MODULEBUILDER_SPECIFIC_README))
		{
			setEventMessages($langs->trans("ContentOfREADMECustomized"), null, 'warnings');
			dol_delete_file($destdir.'/README.md');
			file_put_contents($destdir.'/README.md', $conf->global->MODULEBUILDER_SPECIFIC_README);
		}
	}

	if (!$error)
	{
		setEventMessages('ModuleInitialized', null);
		$module = $modulename;
		$modulename = '';
	}
}

if ($dirins && $action == 'initapi' && !empty($module))
{
	$modulename = ucfirst($module); // Force first letter in uppercase
	$objectname = $tabobj;

	dol_mkdir($dirins.'/'.strtolower($module).'/class');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/class/api_mymodule.class.php';
	$destfile = $dirins.'/'.strtolower($module).'/class/api_'.strtolower($module).'.class.php';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0)
	{
		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'myobject'=>strtolower($objectname),
			'MyObject'=>$objectname,
			'MYOBJECT'=>strtoupper($objectname),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}
if ($dirins && $action == 'initphpunit' && !empty($module))
{
	$modulename = ucfirst($module); // Force first letter in uppercase
	$objectname = $tabobj;

	dol_mkdir($dirins.'/'.strtolower($module).'/class');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/test/phpunit/MyObjectTest.php';
	$destfile = $dirins.'/'.strtolower($module).'/test/phpunit/'.strtolower($objectname).'Test.php';
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0)
	{
		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'myobject'=>strtolower($objectname),
			'MyObject'=>$objectname,
			'MYOBJECT'=>strtoupper($objectname),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}
if ($dirins && $action == 'initsqlextrafields' && !empty($module)) {
	$modulename = ucfirst($module); // Force first letter in uppercase
	$objectname = $tabobj;

	dol_mkdir($dirins.'/'.strtolower($module).'/sql');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile1 = $srcdir.'/sql/llx_mymodule_myobject_extrafields.sql';
	$destfile1 = $dirins.'/'.strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.sql';
	//var_dump($srcfile);var_dump($destfile);
	$result1 = dol_copy($srcfile1, $destfile1, 0, 0);
	$srcfile2 = $srcdir.'/sql/llx_mymodule_myobject_extrafields.key.sql';
	$destfile2 = $dirins.'/'.strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.key.sql';
	//var_dump($srcfile);var_dump($destfile);
	$result2 = dol_copy($srcfile2, $destfile2, 0, 0);

	if ($result1 > 0 && $result2 > 0)
	{
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'My Object'=>$objectname,
			'MyObject'=>$objectname,
			'my object'=>strtolower($objectname),
			'myobject'=>strtolower($objectname),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile1, $arrayreplacement);
		dolReplaceInFile($destfile2, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', ''), null, 'errors');
	}
	// TODO Enable in class the property $isextrafieldmanaged = 1
}
if ($dirins && $action == 'inithook' && !empty($module))
{
	dol_mkdir($dirins.'/'.strtolower($module).'/class');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/class/actions_mymodule.class.php';
	$destfile = $dirins.'/'.strtolower($module).'/class/actions_'.strtolower($module).'.class.php';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0)
	{
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}
if ($dirins && $action == 'inittrigger' && !empty($module))
{
	dol_mkdir($dirins.'/'.strtolower($module).'/core/triggers');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/core/triggers/interface_99_modMyModule_MyModuleTriggers.class.php';
	$destfile = $dirins.'/'.strtolower($module).'/core/triggers/interface_99_mod'.$module.'_'.$module.'Triggers.class.php';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0)
	{
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}
if ($dirins && $action == 'initwidget' && !empty($module))
{
	dol_mkdir($dirins.'/'.strtolower($module).'/core/boxes');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/core/boxes/mymodulewidget1.php';
	$destfile = $dirins.'/'.strtolower($module).'/core/boxes/'.strtolower($module).'widget1.php';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}
if ($dirins && $action == 'initcss' && !empty($module))
{
	dol_mkdir($dirins.'/'.strtolower($module).'/css');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/css/mymodule.css.php';
	$destfile = $dirins.'/'.strtolower($module).'/css/'.strtolower($module).'.css.php';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0)
	{
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : ''),
		);

		dolReplaceInFile($destfile, $arrayreplacement);

		// Update descriptor file to uncomment file
		$srcfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		$arrayreplacement = array('/\/\/\s*\''.preg_quote('/'.strtolower($module).'/css/'.strtolower($module).'.css.php', '/').'\'/' => '\'/'.strtolower($module).'/css/'.strtolower($module).'.css.php\'');
		dolReplaceInFile($srcfile, $arrayreplacement, '', 0, 0, 1);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}

if ($dirins && $action == 'initjs' && !empty($module))
{
	dol_mkdir($dirins.'/'.strtolower($module).'/js');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/js/mymodule.js.php';
	$destfile = $dirins.'/'.strtolower($module).'/js/'.strtolower($module).'.js.php';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0)
	{
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);

		// Update descriptor file to uncomment file
		$srcfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
		$arrayreplacement = array('/\/\/\s*\''.preg_quote('/'.strtolower($module).'/js/'.strtolower($module).'.js.php', '/').'\'/' => '\'/'.strtolower($module).'/js/'.strtolower($module).'.js.php\'');
		dolReplaceInFile($srcfile, $arrayreplacement, '', 0, 0, 1);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}
if ($dirins && $action == 'initcli' && !empty($module))
{
	dol_mkdir($dirins.'/'.strtolower($module).'/scripts');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/scripts/mymodule.php';
	$destfile = $dirins.'/'.strtolower($module).'/scripts/'.strtolower($module).'.php';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0)
	{
		$modulename = ucfirst($module); // Force first letter in uppercase

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'__MYCOMPANY_NAME__'=>$mysoc->name,
			'__KEYWORDS__'=>$modulename,
			'__USER_FULLNAME__'=>$user->getFullName($langs),
			'__USER_EMAIL__'=>$user->email,
			'__YYYY-MM-DD__'=>dol_print_date($now, 'dayrfc'),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, 'dayrfc').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);
	} else {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailToCreateFile', $destfile), null, 'errors');
	}
}
if ($dirins && $action == 'initdoc' && !empty($module)) {
	dol_mkdir($dirins.'/'.strtolower($module).'/doc');
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$srcfile = $srcdir.'/doc/Documentation.asciidoc';
	$destfile = $dirins.'/'.strtolower($module).'/doc/Documentation.asciidoc';
	//var_dump($srcfile);var_dump($destfile);
	$result = dol_copy($srcfile, $destfile, 0, 0);

	if ($result > 0) {
		$modulename = ucfirst($module); // Force first letter in uppercase
		$modulelowercase = strtolower($module);

		//var_dump($phpfileval['fullname']);
		$arrayreplacement = array(
			'mymodule'=>strtolower($modulename),
			'MyModule'=>$modulename,
			'MYMODULE'=>strtoupper($modulename),
			'My module'=>$modulename,
			'my module'=>$modulename,
			'Mon module'=>$modulename,
			'mon module'=>$modulename,
			'htdocs/modulebuilder/template'=>strtolower($modulename),
			'__MYCOMPANY_NAME__'=>$mysoc->name,
			'__KEYWORDS__'=>$modulename,
			'__USER_FULLNAME__'=>$user->getFullName($langs),
			'__USER_EMAIL__'=>$user->email,
			'__YYYY-MM-DD__'=>dol_print_date($now, 'dayrfc'),
			'---Put here your own copyright and developer email---'=>dol_print_date($now, 'dayrfc').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);

		dolReplaceInFile($destfile, $arrayreplacement);

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

if ($dirins && $action == 'addlanguage' && !empty($module))
{
	$newlangcode = GETPOST('newlangcode', 'aZ09');
	$srcfile = $dirins.'/'.strtolower($module).'/langs/en_US';
	$destfile = $dirins.'/'.strtolower($module).'/langs/'.$newlangcode;
	$result = dolCopyDir($srcfile, $destfile, 0, 0);
}

if ($dirins && $action == 'confirm_removefile' && !empty($module))
{
	$relativefilename = dol_sanitizePathName(GETPOST('file', 'none'));
	if ($relativefilename)
	{
		$dirnametodelete = dirname($relativefilename);
		$filetodelete = $dirins.'/'.$relativefilename;
		$dirtodelete  = $dirins.'/'.$dirnametodelete;

		$result = dol_delete_file($filetodelete);
		if (!$result) {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", basename($filetodelete)), null, 'errors');
		} else {
			if (dol_is_dir_empty($dirtodelete)) dol_delete_dir($dirtodelete);

			// Update descriptor file to comment file
			if (in_array($tab, array('css', 'js')))
			{
				$srcfile = $dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
				$arrayreplacement = array('/^\s*\''.preg_quote('/'.$relativefilename, '/').'\',*/m'=>'                // \'/'.$relativefilename.'\',');
				dolReplaceInFile($srcfile, $arrayreplacement, '', 0, 0, 1);
			}
		}
	}
}

// Build the $fields array from SQL table (initfromtablename)
if ($dirins && $action == 'initobject' && $module && GETPOST('createtablearray', 'alpha'))
{
	$tablename = GETPOST('initfromtablename', 'alpha');
	$_results = $db->DDLDescTable($tablename);
	if (empty($_results))
	{
		setEventMessages($langs->trans("ErrorTableNotFound", $tablename), null, 'errors');
	} else {
		/**
		 *  'type' if the field format ('integer', 'integer:Class:pathtoclass', 'varchar(x)', 'double(24,8)', 'text', 'html', 'datetime', 'timestamp', 'float')
		 *  'label' the translation key.
		 *  'enabled' is a condition when the field must be managed.
		 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
		 *  'noteditable' says if field is not editable (1 or 0)
		 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
		 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
		 *  'index' if we want an index in database.
		 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
		 *  'position' is the sort order of field.
		 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
		 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
		 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
		 *  'help' is a string visible as a tooltip on field
		 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
		 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
		 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
		 */

		/*public $fields=array(
		 'rowid'         =>array('type'=>'integer',      'label'=>'TechnicalID',      'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'index'=>1, 'position'=>1, 'comment'=>'Id'),
		 'ref'           =>array('type'=>'varchar(128)', 'label'=>'Ref',              'enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Reference of object'),
		 'entity'        =>array('type'=>'integer',      'label'=>'Entity',           'enabled'=>1, 'visible'=>0,  'default'=>1, 'notnull'=>1,  'index'=>1, 'position'=>20),
		 'label'         =>array('type'=>'varchar(255)', 'label'=>'Label',            'enabled'=>1, 'visible'=>1,  'position'=>30,  'searchall'=>1, 'css'=>'minwidth200', 'help'=>'Help text'),
		 'amount'        =>array('type'=>'double(24,8)', 'label'=>'Amount',           'enabled'=>1, 'visible'=>1,  'default'=>'null', 'position'=>40,  'searchall'=>0, 'isameasure'=>1, 'help'=>'Help text'),
		 'fk_soc' 		 =>array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'visible'=>1, 'enabled'=>1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'searchall'=>1, 'help'=>'LinkToThirparty'),
		 'description'   =>array('type'=>'text',			'label'=>'Descrption',		 'enabled'=>1, 'visible'=>0,  'position'=>60),
		 'note_public'   =>array('type'=>'html',			'label'=>'NotePublic',		 'enabled'=>1, 'visible'=>0,  'position'=>61),
		 'note_private'  =>array('type'=>'html',			'label'=>'NotePrivate',		 'enabled'=>1, 'visible'=>0,  'position'=>62),
		 'date_creation' =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>500),
		 'tms'           =>array('type'=>'timestamp',    'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>501),
		 //'date_valid'    =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'position'=>502),
		 'fk_user_creat' =>array('type'=>'integer',      'label'=>'UserAuthor',       'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>510),
		 'fk_user_modif' =>array('type'=>'integer',      'label'=>'UserModif',        'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>511),
		 //'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		 'import_key'    =>array('type'=>'varchar(14)',  'label'=>'ImportId',         'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'index'=>0,  'position'=>1000),
		 'status'        =>array('type'=>'integer',      'label'=>'Status',           'enabled'=>1, 'visible'=>1,  'notnull'=>1, 'default'=>0, 'index'=>1,  'position'=>1000, 'arrayofkeyval'=>array(0=>'Draft', 1=>'Active', -1=>'Cancel')),
		 );*/

		$string = 'public $fields=array('."\n";
		$string .= "<br>";
		$i = 10;
		while ($obj = $db->fetch_object($_results))
		{
			// fieldname
			$fieldname = $obj->Field;
			// type
			$type = $obj->Type;
			if ($type == 'int(11)') $type = 'integer';
			if ($type == 'float') $type = 'real';
			if (strstr($type, 'tinyint')) $type = 'integer';
			if ($obj->Field == 'fk_soc') $type = 'integer:Societe:societe/class/societe.class.php';
			if (preg_match('/^fk_proj/', $obj->Field)) $type = 'integer:Project:projet/class/project.class.php:1:fk_statut=1';
			if (preg_match('/^fk_prod/', $obj->Field)) $type = 'integer:Product:product/class/product.class.php:1';
			if ($obj->Field == 'fk_warehouse') $type = 'integer:Entrepot:product/stock/class/entrepot.class.php';
			if (preg_match('/^(fk_user|fk_commercial)/', $obj->Field)) $type = 'integer:User:user/class/user.class.php';

			// notnull
			$notnull = ($obj->Null == 'YES' ? 0 : 1);
			if ($fieldname == 'fk_user_modif') $notnull = -1;
			// label
			$label = preg_replace('/_/', '', ucfirst($fieldname));
			if ($fieldname == 'rowid') $label = 'TechnicalID';
			if ($fieldname == 'import_key') $label = 'ImportId';
			if ($fieldname == 'fk_soc') $label = 'ThirdParty';
			if ($fieldname == 'tms') $label = 'DateModification';
			if ($fieldname == 'datec') $label = 'DateCreation';
			if ($fieldname == 'date_valid') $label = 'DateValidation';
			if ($fieldname == 'datev') $label = 'DateValidation';
			if ($fieldname == 'note_private') $label = 'NotePublic';
			if ($fieldname == 'note_public') $label = 'NotePrivate';
			if ($fieldname == 'fk_user_creat') $label = 'UserAuthor';
			if ($fieldname == 'fk_user_modif') $label = 'UserModif';
			if ($fieldname == 'fk_user_valid') $label = 'UserValidation';
			// visible
			$visible = -1;
			if ($fieldname == 'entity') $visible = -2;
			if ($fieldname == 'import_key') $visible = -2;
			if ($fieldname == 'fk_user_creat') $visible = -2;
			if ($fieldname == 'fk_user_modif') $visible = -2;
			if (in_array($fieldname, array('ref_ext', 'model_pdf', 'note_public', 'note_private'))) $visible = 0;
			// enabled
			$enabled = 1;
			// default
			$default = '';
			if ($fieldname == 'entity') $default = 1;
			// position
			$position = $i;
			if (in_array($fieldname, array('status', 'statut', 'fk_status', 'fk_statut'))) $position = 500;
			if ($fieldname == 'import_key') $position = 900;
			// index
			$index = 0;
			if ($fieldname == 'entity') $index = 1;

			$string .= "'".$obj->Field."' =>array('type'=>'".$type."', 'label'=>'".$label."',";
			if ($default != '') $string .= " 'default'=>".$default.",";
			$string .= " 'enabled'=>".$enabled.",";
			$string .= " 'visible'=>".$visible;
			if ($notnull) $string .= ", 'notnull'=>".$notnull;
			if ($fieldname == 'ref') $string .= ", 'showoncombobox'=>1";
			$string .= ", 'position'=>".$position;
			if ($index) $string .= ", 'index'=>".$index;
			$string .= "),\n";
			$string .= "<br>";
			$i += 5;
		}
		$string .= ');'."\n";
		$string .= "<br>";
		print $string;
		exit;
	}
}

if ($dirins && $action == 'initobject' && $module && $objectname)
{
	$objectname = ucfirst($objectname);

	$dirins = $dirread = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$moduletype = $listofmodules[strtolower($module)]['moduletype'];

	if (preg_match('/[^a-z0-9_]/i', $objectname))
	{
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

	// Scan dir class to find if an object with same name already exists.
	if (!$error)
	{
		$dirlist = dol_dir_list($destdir.'/class', 'files', 0, '\.txt$');
		$alreadyfound = false;
		foreach ($dirlist as $key => $val)
		{
			$filefound = preg_replace('/\.txt$/', '', $val['name']);
			if (strtolower($objectname) == strtolower($filefound) && $objectname != $filefound)
			{
				$alreadyfound = true;
				$error++;
				setEventMessages($langs->trans("AnObjectAlreadyExistWithThisNameAndDiffCase"), null, 'errors');
				break;
			}
		}
	}

	if (!$error)
	{
		// Copy some files
		$filetogenerate = array(
			'myobject_card.php'=>strtolower($objectname).'_card.php',
			'myobject_note.php'=>strtolower($objectname).'_note.php',
			'myobject_contact.php'=>strtolower($objectname).'_contact.php',
			'myobject_document.php'=>strtolower($objectname).'_document.php',
			'myobject_agenda.php'=>strtolower($objectname).'_agenda.php',
			'myobject_list.php'=>strtolower($objectname).'_list.php',
			'admin/myobject_extrafields.php'=>'admin/'.strtolower($objectname).'_extrafields.php',
			'lib/mymodule_myobject.lib.php'=>'lib/'.strtolower($module).'_'.strtolower($objectname).'.lib.php',
			//'test/phpunit/MyObjectTest.php'=>'test/phpunit/'.strtolower($objectname).'Test.php',
			'sql/llx_mymodule_myobject.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.sql',
			'sql/llx_mymodule_myobject.key.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.key.sql',
			'sql/llx_mymodule_myobject_extrafields.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.sql',
			'sql/llx_mymodule_myobject_extrafields.key.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.key.sql',
			//'scripts/mymodule.php'=>'scripts/'.strtolower($objectname).'.php',
			'img/object_myobject.png'=>'img/object_'.strtolower($objectname).'.png',
			'class/myobject.class.php'=>'class/'.strtolower($objectname).'.class.php',
			//'class/api_mymodule.class.php'=>'class/api_'.strtolower($module).'.class.php',
		);

		if (GETPOST('includerefgeneration', 'aZ09'))
		{
			dol_mkdir($destdir.'/core/modules/'.strtolower($module));

			$filetogenerate += array(
				'core/modules/mymodule/mod_myobject_advanced.php'=>'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_advanced.php',
				'core/modules/mymodule/mod_myobject_standard.php'=>'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_standard.php',
				'core/modules/mymodule/modules_myobject.php'=>'core/modules/'.strtolower($module).'/modules_'.strtolower($objectname).'.php',
			);
		}
		if (GETPOST('includedocgeneration', 'aZ09'))
		{
			dol_mkdir($destdir.'/core/modules/'.strtolower($module));
			dol_mkdir($destdir.'/core/modules/'.strtolower($module).'/doc');

			$filetogenerate += array(
				'core/modules/mymodule/doc/doc_generic_myobject_odt.modules.php'=>'core/modules/'.strtolower($module).'/doc/doc_generic_'.strtolower($objectname).'_odt.modules.php',
				'core/modules/mymodule/doc/pdf_standard_myobject.modules.php'=>'core/modules/'.strtolower($module).'/doc/pdf_standard_'.strtolower($objectname).'.modules.php'
			);
		}

		foreach ($filetogenerate as $srcfile => $destfile)
		{
			$result = dol_copy($srcdir.'/'.$srcfile, $destdir.'/'.$destfile, $newmask, 0);
			if ($result <= 0)
			{
				if ($result < 0)
				{
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorFailToCopyFile", $srcdir.'/'.$srcfile, $destdir.'/'.$destfile), null, 'errors');
				} else {
					// $result == 0
					setEventMessages($langs->trans("FileAlreadyExists", $destfile), null, 'warnings');
				}
			}
		}

		// Edit the class 'class/'.strtolower($objectname).'.class.php'
		if (GETPOST('includerefgeneration', 'aZ09')) {
			// Replace 'visible'=>1,  'noteditable'=>0, 'default'=>''
			$arrayreplacement = array(
				'/\'visible\'=>1,\s*\'noteditable\'=>0,\s*\'default\'=>\'\'/' => "'visible'=>4, 'noteditable'=>1, 'default'=>'(PROV)'"
			);
			//var_dump($arrayreplacement);exit;
			//var_dump($destdir.'/class/'.strtolower($objectname).'.class.php');exit;
			dolReplaceInFile($destdir.'/class/'.strtolower($objectname).'.class.php', $arrayreplacement, '', 0, 0, 1);

			$arrayreplacement = array(
				'/\'models\' => 0,/' => '\'models\' => 1,'
			);
			dolReplaceInFile($destdir.'/core/modules/mod'.$module.'.class.php', $arrayreplacement, '', 0, 0, 1);
		}

		// Edit the setup file and the card page
		if (GETPOST('includedocgeneration', 'aZ09')) {
			// Replace some var init into some files
			$arrayreplacement = array(
				'/\$includedocgeneration = 0;/' => '$includedocgeneration = 1;'
			);
			dolReplaceInFile($destdir.'/class/'.strtolower($objectname).'.class.php', $arrayreplacement, '', 0, 0, 1);
			dolReplaceInFile($destdir.'/'.strtolower($objectname).'_card.php', $arrayreplacement, '', 0, 0, 1);

			$arrayreplacement = array(
				'/\'models\' => 0,/' => '\'models\' => 1,'
			);

			dolReplaceInFile($destdir.'/core/modules/mod'.$module.'.class.php', $arrayreplacement, '', 0, 0, 1);
		}

		// TODO Update entries '$myTmpObjects['MyObject']=array('includerefgeneration'=>0, 'includedocgeneration'=>0);'


		// Scan for object class files
		$listofobject = dol_dir_list($destdir.'/class', 'files', 0, '\.class\.php$');

		$firstobjectname = '';
		foreach ($listofobject as $fileobj)
		{
			if (preg_match('/^api_/', $fileobj['name'])) continue;
			if (preg_match('/^actions_/', $fileobj['name'])) continue;

			$tmpcontent = file_get_contents($fileobj['fullname']);
			$reg = array();
			if (preg_match('/class\s+([^\s]*)\s+extends\s+CommonObject/ims', $tmpcontent, $reg))
			{
				$objectnameloop = $reg[1];
				if (empty($firstobjectname)) $firstobjectname = $objectnameloop;
			}

			// Regenerate left menu entry in descriptor for $objectname
			$stringtoadd = "
        \$this->menu[\$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=mymodule',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'List MyObject',
            'mainmenu'=>'mymodule',
            'leftmenu'=>'mymodule_myobject',
            'url'=>'/mymodule/myobject_list.php',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'mymodule@mymodule',
            'position'=>1100+\$r,
            // Define condition to show or hide menu entry. Use '\$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '\$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'\$conf->mymodule->enabled',
            // Use 'perms'=>'\$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2,
        );
        \$this->menu[\$r++]=array(
            // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'fk_menu'=>'fk_mainmenu=mymodule,fk_leftmenu=mymodule_myobject',
            // This is a Left menu entry
            'type'=>'left',
            'titre'=>'New MyObject',
            'mainmenu'=>'mymodule',
            'leftmenu'=>'mymodule_myobject',
            'url'=>'/mymodule/myobject_card.php?action=create',
            // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'langs'=>'mymodule@mymodule',
            'position'=>1100+\$r,
            // Define condition to show or hide menu entry. Use '\$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '\$leftmenu==\'system\'' to show if leftmenu system is selected.
            'enabled'=>'\$conf->mymodule->enabled',
            // Use 'perms'=>'\$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'perms'=>'1',
            'target'=>'',
            // 0=Menu for internal users, 1=external users, 2=both
            'user'=>2
        );\n";
			$stringtoadd = preg_replace('/MyObject/', $objectnameloop, $stringtoadd);
			$stringtoadd = preg_replace('/mymodule/', strtolower($module), $stringtoadd);
			$stringtoadd = preg_replace('/myobject/', strtolower($objectnameloop), $stringtoadd);

			$moduledescriptorfile = $destdir.'/core/modules/mod'.$module.'.class.php';

			// TODO Allow a replace with regex using dolReplaceInFile with param arryreplacementisregex to 1
			// TODO Avoid duplicate addition

			dolReplaceInFile($moduledescriptorfile, array('END MODULEBUILDER LEFTMENU MYOBJECT */' => '*/'."\n".$stringtoadd."\n\t\t/* END MODULEBUILDER LEFTMENU MYOBJECT */"));

			// Add module descriptor to list of files to replace "MyObject' string with real name of object.
			$filetogenerate[] = 'core/modules/mod'.$module.'.class.php';
		}
	}

	if (!$error)
	{
		// Edit PHP files to make replacement
		foreach ($filetogenerate as $destfile)
		{
			$phpfileval['fullname'] = $destdir.'/'.$destfile;

			//var_dump($phpfileval['fullname']);
			$arrayreplacement = array(
				'mymodule'=>strtolower($module),
				'MyModule'=>$module,
				'MYMODULE'=>strtoupper($module),
				'My module'=>$module,
				'my module'=>$module,
				'mon module'=>$module,
				'Mon module'=>$module,
				'htdocs/modulebuilder/template/'=>strtolower($modulename),
				'myobject'=>strtolower($objectname),
				'MyObject'=>$objectname,
				'MYOBJECT'=>strtoupper($objectname)
			);

			$result = dolReplaceInFile($phpfileval['fullname'], $arrayreplacement);
			//var_dump($result);
			if ($result < 0)
			{
				setEventMessages($langs->trans("ErrorFailToMakeReplacementInto", $phpfileval['fullname']), null, 'errors');
			}
		}
	}

	if (!$error)
	{
		// Edit the class file to write properties
		$object = rebuildObjectClass($destdir, $module, $objectname, $newmask);
		if (is_numeric($object) && $object < 0) $error++;
	}
	if (!$error)
	{
		// Edit sql with new properties
		$result = rebuildObjectSql($destdir, $module, $objectname, $newmask, '', $object);
		if ($result < 0) $error++;
	}

	if (!$error)
	{
		setEventMessages($langs->trans('FilesForObjectInitialized', $objectname), null);
		$tabobj = $objectname;
	}
}

if ($dirins && ($action == 'droptable' || $action == 'droptableextrafields') && !empty($module) && !empty($tabobj))
{
	$objectname = $tabobj;

	$arrayoftables = array();
	if ($action == 'droptable') $arrayoftables[] = MAIN_DB_PREFIX.strtolower($module).'_'.strtolower($tabobj);
	if ($action == 'droptableextrafields') $arrayoftables[] = MAIN_DB_PREFIX.strtolower($module).'_'.strtolower($tabobj).'_extrafields';

	foreach ($arrayoftables as $tabletodrop)
	{
		$nb = -1;
		$sql = "SELECT COUNT(*) as nb FROM ".$tabletodrop;
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				$nb = $obj->nb;
			}
		} else {
			if ($db->lasterrno() == 'DB_ERROR_NOSUCHTABLE')
			{
				setEventMessages($langs->trans("TableDoesNotExists", $tabletodrop), null, 'warnings');
			} else {
				dol_print_error($db);
			}
		}
		if ($nb == 0)
		{
			$resql = $db->DDLDropTable($tabletodrop);
			//var_dump($resql);
			setEventMessages($langs->trans("TableDropped", $tabletodrop), null, 'mesgs');
		} elseif ($nb > 0)
		{
			setEventMessages($langs->trans("TableNotEmptyDropCanceled", $tabletodrop), null, 'warnings');
		}
	}
}

if ($dirins && $action == 'addproperty' && !empty($module) && !empty($tabobj))
{
	$error = 0;

	$objectname = $tabobj;

	$dirins = $dirread = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];
	$moduletype = $listofmodules[strtolower($module)]['moduletype'];

	$srcdir = $dirread.'/'.strtolower($module);
	$destdir = $dirins.'/'.strtolower($module);
	dol_mkdir($destdir);

	// We click on add property
	if (!GETPOST('regenerateclasssql') && !GETPOST('regeneratemissing'))
	{
		if (!GETPOST('propname', 'aZ09'))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Name")), null, 'errors');
		}
		if (!GETPOST('proplabel', 'alpha'))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
		}
		if (!GETPOST('proptype', 'alpha'))
		{
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Type")), null, 'errors');
		}

		if (!$error)
		{
			$addfieldentry = array(
				'name'=>GETPOST('propname', 'aZ09'), 'label'=>GETPOST('proplabel', 'alpha'), 'type'=>GETPOST('proptype', 'alpha'),
				'arrayofkeyval'=>GETPOST('proparrayofkeyval', 'restricthtml'), // Example json string '{"0":"Draft","1":"Active","-1":"Cancel"}'
				'visible'=>GETPOST('propvisible', 'int'), 'enabled'=>GETPOST('propenabled', 'int'),
				'position'=>GETPOST('propposition', 'int'), 'notnull'=>GETPOST('propnotnull', 'int'), 'index'=>GETPOST('propindex', 'int'), 'searchall'=>GETPOST('propsearchall', 'int'),
				'isameasure'=>GETPOST('propisameasure', 'int'), 'comment'=>GETPOST('propcomment', 'alpha'), 'help'=>GETPOST('prophelp', 'alpha')
			);

			if (!empty($addfieldentry['arrayofkeyval']) && !is_array($addfieldentry['arrayofkeyval']))
			{
				$addfieldentry['arrayofkeyval'] = json_decode($addfieldentry['arrayofkeyval'], true);
			}
		}
	}

	/*if (GETPOST('regeneratemissing'))
	{
		setEventMessages($langs->trans("FeatureNotYetAvailable"), null, 'warnings');
		$error++;
	}*/

	// Edit the class file to write properties
	if (!$error)
	{
		$moduletype = 'external';

		$object = rebuildObjectClass($destdir, $module, $objectname, $newmask, $srcdir, $addfieldentry, $moduletype);
		if (is_numeric($object) && $object <= 0)
		{
			$error++;
		}
	}

	// Edit sql with new properties
	if (!$error)
	{
		$moduletype = 'external';

		$result = rebuildObjectSql($destdir, $module, $objectname, $newmask, $srcdir, $object, $moduletype);
		if ($result <= 0)
		{
			$error++;
		}
	}

	if (!$error)
	{
		setEventMessages($langs->trans('FilesForObjectUpdated', $objectname), null);

		clearstatcache(true);

		// Make a redirect to reload all data
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj='.$objectname.'&nocache='.time());

		exit;
	}
}

if ($dirins && $action == 'confirm_deleteproperty' && $propertykey)
{
	$objectname = $tabobj;

	$srcdir = $dirread.'/'.strtolower($module);
	$destdir = $dirins.'/'.strtolower($module);
	dol_mkdir($destdir);

	// Edit the class file to write properties
	if (!$error)
	{
		$object = rebuildObjectClass($destdir, $module, $objectname, $newmask, $srcdir, array(), $propertykey);
		if (is_numeric($object) && $object <= 0) $error++;
	}

	// Edit sql with new properties
	if (!$error)
	{
		$result = rebuildObjectSql($destdir, $module, $objectname, $newmask, $srcdir, $object);
		if ($result <= 0) $error++;
	}

	if (!$error)
	{
		setEventMessages($langs->trans('FilesForObjectUpdated', $objectname), null);

		clearstatcache(true);

		// Make a redirect to reload all data
		header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj='.$objectname);

		exit;
	}
}

if ($dirins && $action == 'confirm_deletemodule')
{
	if (preg_match('/[^a-z0-9_]/i', $module))
	{
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
	}

	if (!$error)
	{
		$modulelowercase = strtolower($module);

		// Dir for module
		$dir = $dirins.'/'.$modulelowercase;

		$result = dol_delete_dir_recursive($dir);

		if ($result > 0)
		{
			setEventMessages($langs->trans("DirWasRemoved", $modulelowercase), null);
		} else {
			setEventMessages($langs->trans("PurgeNothingToDelete"), null, 'warnings');
		}
	}

	$action = '';
	$module = 'deletemodule';
}

if ($dirins && $action == 'confirm_deleteobject' && $objectname)
{
	if (preg_match('/[^a-z0-9_]/i', $objectname))
	{
		$error++;
		setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
	}

	if (!$error)
	{
		$modulelowercase = strtolower($module);
		$objectlowercase = strtolower($objectname);

		// Dir for module
		$dir = $dirins.'/'.$modulelowercase;

		// Delete some files
		$filetodelete = array(
			'myobject_card.php'=>strtolower($objectname).'_card.php',
			'myobject_note.php'=>strtolower($objectname).'_note.php',
			'myobject_contact.php'=>strtolower($objectname).'_contact.php',
			'myobject_document.php'=>strtolower($objectname).'_document.php',
			'myobject_agenda.php'=>strtolower($objectname).'_agenda.php',
			'myobject_list.php'=>strtolower($objectname).'_list.php',
			'admin/myobject_extrafields.php'=>'admin/'.strtolower($objectname).'_extrafields.php',
			'lib/mymodule_myobject.lib.php'=>'lib/'.strtolower($module).'_'.strtolower($objectname).'.lib.php',
			'test/phpunit/MyObjectTest.php'=>'test/phpunit/'.strtolower($objectname).'Test.php',
			'sql/llx_mymodule_myobject.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.sql',
			'sql/llx_mymodule_myobject_extrafields.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.sql',
			'sql/llx_mymodule_myobject.key.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.key.sql',
			'sql/llx_mymodule_myobject_extrafields.key.sql'=>'sql/llx_'.strtolower($module).'_'.strtolower($objectname).'_extrafields.key.sql',
			'scripts/myobject.php'=>'scripts/'.strtolower($objectname).'.php',
			'img/object_myobject.png'=>'img/object_'.strtolower($objectname).'.png',
			'class/myobject.class.php'=>'class/'.strtolower($objectname).'.class.php',
			'class/api_myobject.class.php'=>'class/api_'.strtolower($module).'.class.php',
			'core/modules/mymodule/mod_myobject_advanced.php'=>'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_advanced.php',
			'core/modules/mymodule/mod_myobject_standard.php'=>'core/modules/'.strtolower($module).'/mod_'.strtolower($objectname).'_standard.php',
			'core/modules/mymodule/modules_myobject.php'=>'core/modules/'.strtolower($module).'/modules_'.strtolower($objectname).'.php',
			'core/modules/mymodule/doc/doc_generic_myobject_odt.modules.php'=>'core/modules/'.strtolower($module).'/doc/doc_generic_'.strtolower($objectname).'_odt.modules.php',
			'core/modules/mymodule/doc/pdf_standard_myobject.modules.php'=>'core/modules/'.strtolower($module).'/doc/pdf_standard_'.strtolower($objectname).'.modules.php'
		);

		$resultko = 0;
		foreach ($filetodelete as $filetodelete)
		{
			$resulttmp = dol_delete_file($dir.'/'.$filetodelete, 0, 0, 1);
			$resulttmp = dol_delete_file($dir.'/'.$filetodelete.'.back', 0, 0, 1);
			if (!$resulttmp) $resultko++;
		}

		if ($resultko == 0)
		{
			setEventMessages($langs->trans("FilesDeleted"), null);
		} else {
			setEventMessages($langs->trans("ErrorSomeFilesCouldNotBeDeleted"), null, 'warnings');
		}
	}

	$action = '';
	$tabobj = 'deleteobject';
}


if ($dirins && $action == 'generatepackage')
{
	$modulelowercase = strtolower($module);

	// Dir for module
	$dir = $dirins.'/'.$modulelowercase;
	// Zip file to build
	$FILENAMEZIP = '';

	// Load module
	dol_include_once($modulelowercase.'/core/modules/mod'.$module.'.class.php');
	$class = 'mod'.$module;

	if (class_exists($class))
	{
		try {
			$moduleobj = new $class($db);
		} catch (Exception $e)
		{
			$error++;
			dol_print_error($e->getMessage());
		}
	} else {
		$error++;
		$langs->load("errors");
		dol_print_error($langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
		exit;
	}

	$arrayversion = explode('.', $moduleobj->version, 3);
	if (count($arrayversion))
	{
		$FILENAMEZIP = "module_".$modulelowercase.'-'.$arrayversion[0].'.'.$arrayversion[1].($arrayversion[2] ? ".".$arrayversion[2] : "").".zip";

		$dirofmodule = dol_buildpath($modulelowercase, 0).'/bin';
		$outputfilezip = $dirofmodule.'/'.$FILENAMEZIP;
		if ($dirofmodule)
		{
			if (!dol_is_dir($dirofmodule)) dol_mkdir($dirofmodule);
			$result = dol_compress_dir($dir, $outputfilezip, 'zip', '', $modulelowercase);
		} else {
			$result = -1;
		}

		if ($result > 0)
		{
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

if ($dirins && $action == 'generatedoc')
{
	$FILENAMEDOC = strtolower($module).'.html';
	$dirofmodule = dol_buildpath(strtolower($module), 0).'/doc';

	$util = new Utils($db);
	$result = $util->generateDoc($module);

	if ($result > 0)
	{
		setEventMessages($langs->trans("DocFileGeneratedInto", $dirofmodule), null);
	} else {
		setEventMessages($util->error, $util->errors, 'errors');
	}
}


// Save file
if ($action == 'savefile' && empty($cancel))
{
	$relofcustom = basename($dirins);

	if ($relofcustom)
	{
		// Check that relative path ($file) start with name 'custom'
		if (!preg_match('/^'.$relofcustom.'/', $file)) $file = $relofcustom.'/'.$file;

		$pathoffile = dol_buildpath($file, 0);
		$pathoffilebackup = dol_buildpath($file.'.back', 0);

		// Save old version
		if (dol_is_file($pathoffile))
		{
			dol_copy($pathoffile, $pathoffilebackup, 0, 1);
		}

		$check = 'restricthtml';
		$srclang = dol_mimetype($pathoffile, '', 3);
		if ($srclang == 'md') $check = 'restricthtml';
		if ($srclang == 'lang') $check = 'restricthtml';
		if ($srclang == 'php') $check = 'none';

		$content = GETPOST('editfilecontent', $check);

		// Save file on disk
		if ($content)
		{
			dol_delete_file($pathoffile);
			$result = file_put_contents($pathoffile, $content);
			if ($result)
			{
				@chmod($pathoffile, octdec($newmask));

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
if ($action == 'set' && $user->admin)
{
	$param = '';
	if ($module) $param .= '&module='.urlencode($module);
	if ($tab)    $param .= '&tab='.urlencode($tab);
	if ($tabobj) $param .= '&tabobj='.urlencode($tabobj);

	$value = GETPOST('value', 'alpha');
	$resarray = activateModule($value);
	if (!empty($resarray['errors'])) setEventMessages('', $resarray['errors'], 'errors');
	else {
		//var_dump($resarray);exit;
		if ($resarray['nbperms'] > 0)
		{
			$tmpsql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."user WHERE admin <> 1";
			$resqltmp = $db->query($tmpsql);
			if ($resqltmp)
			{
				$obj = $db->fetch_object($resqltmp);
				//var_dump($obj->nb);exit;
				if ($obj && $obj->nb > 1)
				{
					$msg = $langs->trans('ModuleEnabledAdminMustCheckRights');
					setEventMessages($msg, null, 'warnings');
				}
			} else dol_print_error($db);
		}
	}
	header("Location: ".$_SERVER["PHP_SELF"]."?".$param);
	exit;
}

// Disable module
if ($action == 'reset' && $user->admin)
{
	$param = '';
	if ($module) $param .= '&module='.urlencode($module);
	if ($tab)    $param .= '&tab='.urlencode($tab);
	if ($tabobj) $param .= '&tabobj='.urlencode($tabobj);

	$value = GETPOST('value', 'alpha');
	$result = unActivateModule($value);
	if ($result) setEventMessages($result, null, 'errors');
	header("Location: ".$_SERVER["PHP_SELF"]."?".$param);
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

// Set dir where external modules are installed
if (!dol_is_dir($dirins))
{
	dol_mkdir($dirins);
}
$dirins_ok = (dol_is_dir($dirins));

llxHeader('', $langs->trans("ModuleBuilder"), '', '', 0, 0,
	array(
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	//'/includes/ace/src/ext-chromevox.js'
	), array(), '', 'classforhorizontalscrolloftabs');


$text = $langs->trans("ModuleBuilder");

print load_fiche_titre($text, '', 'title_setup');

print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ModuleBuilderDesc", 'https://wiki.dolibarr.org/index.php/Module_development#Create_your_module').'</span><br>';

print $textforlistofdirs;
print '<br>';
//var_dump($listofmodules);


$message = '';
if (!$dirins)
{
	$message = info_admin($langs->trans("ConfFileMustContainCustom", DOL_DOCUMENT_ROOT.'/custom', DOL_DOCUMENT_ROOT));
	$allowfromweb = -1;
} else {
	if ($dirins_ok)
	{
		if (!is_writable(dol_osencode($dirins)))
		{
			$langs->load("errors");
			$message = info_admin($langs->trans("ErrorFailedToWriteInDir", $dirins));
			$allowfromweb = 0;
		}
	} else {
		$message = info_admin($langs->trans("NotExistsDirect", $dirins).$langs->trans("InfDirAlt").$langs->trans("InfDirExample"));
		$allowfromweb = 0;
	}
}
if ($message)
{
	print $message;
}

//print $langs->trans("ModuleBuilderDesc3", count($listofmodules), $FILEFLAG).'<br>';
$infomodulesfound = '<div style="padding: 12px 9px 12px">'.$form->textwithpicto('<span class="opacitymedium">'.$langs->trans("ModuleBuilderDesc3", count($listofmodules)).'</span>', $langs->trans("ModuleBuilderDesc4", $FILEFLAG)).'</div>';


// Load module descriptor
$error = 0;
$moduleobj = null;


if (!empty($module) && $module != 'initmodule' && $module != 'deletemodule')
{
	$modulelowercase = strtolower($module);
	$loadclasserrormessage = '';

	// Load module
	try {
		$fullpathdirtodescriptor = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

		//throw(new Exception());
		dol_include_once($fullpathdirtodescriptor);

		$class = 'mod'.$module;
	} catch (Throwable $e) {		// This is called in PHP 7 only. Never called with PHP 5.6
		$loadclasserrormessage = $e->getMessage()."<br>\n";
		$loadclasserrormessage .= 'File: '.$e->getFile()."<br>\n";
		$loadclasserrormessage .= 'Line: '.$e->getLine()."<br>\n";
	}

	if (class_exists($class))
	{
		try {
			$moduleobj = new $class($db);
		} catch (Exception $e) {
			$error++;
			print $e->getMessage();
		}
	} else {
		if (empty($forceddirread)) $error++;
		$langs->load("errors");
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
$modulestatusinfo = '';

if (is_array($listofmodules) && count($listofmodules) > 0) {
	// Define $linktoenabledisable and $modulestatusinfo
	$modulelowercase = strtolower($module);
	$const_name = 'MAIN_MODULE_'.strtoupper($module);

	$param = '';
	if ($tab)    $param .= '&tab='.urlencode($tab);
	if ($module) $param .= '&module='.urlencode($module);
	if ($tabobj) $param .= '&tabobj='.urlencode($tabobj);

	$urltomodulesetup = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword='.urlencode($module).'">'.$langs->trans('Home').'-'.$langs->trans("Setup").'-'.$langs->trans("Modules").'</a>';
	if (!empty($conf->global->$const_name))	// If module is already activated
	{
		$linktoenabledisable .= '<a class="reposition asetresetmodule valignmiddle" href="'.$_SERVER["PHP_SELF"].'?id='.$moduleobj->numero.'&action=reset&value=mod'.$module.$param.'">';
		$linktoenabledisable .= img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', '', 1);
		$linktoenabledisable .= '</a>';

		$objMod = $moduleobj;
		$backtourlparam = '';
		$backtourlparam .= ($backtourlparam ? '&' : '?').'module='.$module; // No urlencode here, done later
		if ($tab) $backtourlparam .= ($backtourlparam ? '&' : '?').'tab='.$tab; // No urlencode here, done later
		$backtourl = $_SERVER["PHP_SELF"].$backtourlparam;

		$regs = array();
		if (is_array($objMod->config_page_url))
		{
			$i = 0;
			foreach ($objMod->config_page_url as $page)
			{
				$urlpage = $page;
				if ($i++)
				{
					$linktoenabledisable .= ' <a href="'.$urlpage.'" title="'.$langs->trans($page).'">'.img_picto(ucfirst($page), "setup").'</a>';
					//    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
				} else {
					if (preg_match('/^([^@]+)@([^@]+)$/i', $urlpage, $regs))
					{
						$urltouse = dol_buildpath('/'.$regs[2].'/admin/'.$regs[1], 1);
						$linktoenabledisable .= ' &nbsp; <a href="'.$urltouse.(preg_match('/\?/', $urltouse) ? '&' : '?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 6px"').'</a>';
					} else {
						$urltouse = $urlpage;
						$linktoenabledisable .= ' &nbsp; <a href="'.$urltouse.(preg_match('/\?/', $urltouse) ? '&' : '?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 6px"').'</a>';
					}
				}
			}
		} elseif (preg_match('/^([^@]+)@([^@]+)$/i', $objMod->config_page_url, $regs)) {
			$linktoenabledisable .= ' &nbsp; <a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1], 1).'?save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 6px"').'</a>';
		}
	} else {
		$linktoenabledisable .= '<a class="reposition asetresetmodule valignmiddle" href="'.$_SERVER["PHP_SELF"].'?id='.$moduleobj->numero.'&action=set&token='.newToken().'&value=mod'.$module.$param.'">';
		$linktoenabledisable .= img_picto($langs->trans("ModuleIsNotActive", $urltomodulesetup), 'switch_off', '', false, 0, 0, '', 'classfortooltip', 1);
		$linktoenabledisable .= "</a>\n";
	}

	if (!empty($conf->$modulelowercase->enabled))
	{
		$modulestatusinfo = $form->textwithpicto('', $langs->trans("Warning").' : '.$langs->trans("ModuleIsLive"), -1, 'warning');
	}

	// Loop to show tab of each module
	foreach ($listofmodules as $tmpmodule => $tmpmodulearray)
	{
		$head[$h][0] = $_SERVER["PHP_SELF"].'?module='.$tmpmodulearray['modulenamewithcase'].($forceddirread ? '@'.$dirread : '');
		$head[$h][1] = $tmpmodulearray['modulenamewithcase'];
		$head[$h][2] = $tmpmodulearray['modulenamewithcase'];

		/*if ($tmpmodule == $modulelowercase) {
			$head[$h][1] .= ' '.$modulestatusinfo;
			$head[$h][1] .= ' '.$linktoenabledisable;
		}*/

		$h++;
	}
}

$head[$h][0] = $_SERVER["PHP_SELF"].'?module=deletemodule';
$head[$h][1] = $langs->trans("DangerZone");
$head[$h][2] = 'deletemodule';
$h++;

print dol_get_fiche_head($head, $module, '', -1, '', 0, $infomodulesfound, '', 8); // Modules

if ($module == 'initmodule')
{
	// New module
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="initmodule">';
	print '<input type="hidden" name="module" value="initmodule">';

	//print '<span class="opacitymedium">'.$langs->trans("ModuleBuilderDesc2", 'conf/conf.php', $newdircustom).'</span><br>';
	print $langs->trans("EnterNameOfModuleDesc").'<br>';
	print '<br>';

	print '<input type="text" name="modulename" value="'.dol_escape_htmltag($modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("ModuleKey")).'"><br>';

	print '<br><input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("Create")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
	print '</form>';
} elseif ($module == 'deletemodule') {
	print '<!-- Form to init a module -->'."\n";
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="delete">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="confirm_deletemodule">';
	print '<input type="hidden" name="module" value="deletemodule">';

	print $langs->trans("EnterNameOfModuleToDeleteDesc").'<br><br>';

	print '<input type="text" name="module" placeholder="'.dol_escape_htmltag($langs->trans("ModuleKey")).'" value="">';
	print '<input type="submit" class="button smallpaddingimp" value="'.$langs->trans("Delete").'"'.($dirins ? '' : ' disabled="disabled"').'>';
	print '</form>';
} elseif (!empty($module)) {
	// Tabs for module
	if (!$error)
	{
		$dirread = $listofmodules[strtolower($module)]['moduledescriptorrootpath'];

		$head2 = array();
		$h = 0;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=description&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Description");
		$head2[$h][2] = 'description';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=languages&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Languages");
		$head2[$h][2] = 'languages';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=dictionaries&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Dictionaries");
		$head2[$h][2] = 'dictionaries';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Objects");
		$head2[$h][2] = 'objects';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=permissions&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Permissions");
		$head2[$h][2] = 'permissions';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=menus&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Menus");
		$head2[$h][2] = 'menus';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=hooks&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Hooks");
		$head2[$h][2] = 'hooks';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=triggers&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Triggers");
		$head2[$h][2] = 'triggers';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=widgets&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Widgets");
		$head2[$h][2] = 'widgets';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=css&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("CSS");
		$head2[$h][2] = 'css';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=js&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("JS");
		$head2[$h][2] = 'js';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=cli&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("CLI");
		$head2[$h][2] = 'cli';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=cron&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("CronList");
		$head2[$h][2] = 'cron';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=specifications&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("Documentation");
		$head2[$h][2] = 'specifications';
		$h++;

		$head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=buildpackage&module='.$module.($forceddirread ? '@'.$dirread : '');
		$head2[$h][1] = $langs->trans("BuildPackage");
		$head2[$h][2] = 'buildpackage';
		$h++;

		// Link to enable / disable
		print '<div class="center">'.$modulestatusinfo;
		print ' '.$linktoenabledisable.'</div>';

		print '<br>';

		// Note module is inside $dirread

		if ($tab == 'description')
		{
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
			$pathtofilereadme = $modulelowercase.'/README.md';
			$pathtochangelog = $modulelowercase.'/ChangeLog.md';

			if ($action != 'editfile' || empty($file))
			{
				print dol_get_fiche_head($head2, $tab, '', -1, '', 0, '', '', 0, 'formodulesuffix'); // Description - level 2

				print '<span class="opacitymedium">'.$langs->trans("ModuleBuilderDesc".$tab).'</span>';
				$infoonmodulepath = '';
				if (realpath($dirread.'/'.$modulelowercase) != $dirread.'/'.$modulelowercase)
				{
					$infoonmodulepath = '<span class="opacitymedium">'.$langs->trans("RealPathOfModule").' :</span> <strong>'.realpath($dirread.'/'.$modulelowercase).'</strong><br>';
					print ' '.$infoonmodulepath;
				}
				print '<br>';

				print '<table>';

				print '<tr><td>';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
				print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("ReadmeFile").' : <strong>'.$pathtofilereadme.'</strong>';
				print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=markdown&file='.urlencode($pathtofilereadme).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("ChangeLog").' : <strong>'.$pathtochangelog.'</strong>';
				print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=markdown&file='.urlencode($pathtochangelog).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				print '</table>';
				print '<br>';

				print load_fiche_titre($langs->trans("DescriptorFile"), '', '');

				if (!empty($moduleobj))
				{
					print '<div class="underbanner clearboth"></div>';
					print '<div class="fichecenter">';

					print '<table class="border centpercent">';
					print '<tr class="liste_titre"><td class="titlefield">';
					print $langs->trans("Parameter");
					print '</td><td>';
					print $langs->trans("Value");
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Numero");
					print '</td><td>';
					print $moduleobj->numero;
					print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/admin/system/modules.php?mainmenu=home&leftmenu=admintools_info" target="_blank">'.$langs->trans("SeeIDsInUse").'</a>';
					print ' - <a href="https://wiki.dolibarr.org/index.php/List_of_modules_id" target="_blank">'.$langs->trans("SeeReservedIDsRangeHere").'</a>)';
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Name");
					print '</td><td>';
					print $moduleobj->getName();
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Version");
					print '</td><td>';
					print $moduleobj->getVersion();
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Family");
					//print "<br>'crm','financial','hr','projects','products','ecm','technic','interface','other'";
					print '</td><td>';
					print $moduleobj->family;
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("EditorName");
					print '</td><td>';
					print $moduleobj->editor_name;
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("EditorUrl");
					print '</td><td>';
					print $moduleobj->editor_url;
					print '</td></tr>';

					print '<tr><td>';
					print $langs->trans("Description");
					print '</td><td>';
					print $moduleobj->getDesc();
					print '</td></tr>';

					print '</table>';
				} else {
					print $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module).'<br>';
				}

				if (!empty($moduleobj))
				{
					print '<br><br>';

					// Readme file
					print load_fiche_titre($langs->trans("ReadmeFile"), '', '');

					print '<!-- readme file -->';
					if (dol_is_file($dirread.'/'.$pathtofilereadme)) print '<div class="underbanner clearboth"></div><div class="fichecenter">'.$moduleobj->getDescLong().'</div>';
					else print '<span class="opacitymedium">'.$langs->trans("ErrorFileNotFound", $pathtofilereadme).'</span>';

					print '<br><br>';

					// ChangeLog
					print load_fiche_titre($langs->trans("ChangeLog"), '', '');

					print '<!-- changelog file -->';
					if (dol_is_file($dirread.'/'.$pathtochangelog)) print '<div class="underbanner clearboth"></div><div class="fichecenter">'.$moduleobj->getChangeLog().'</div>';
					else print '<span class="opacitymedium">'.$langs->trans("ErrorFileNotFound", $pathtochangelog).'</span>';
				}

				print dol_get_fiche_end();
			} else {	// Edit text file
				$fullpathoffile = dol_buildpath($file, 0, 1); // Description - level 2

				if ($fullpathoffile)
				{
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%', '');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));

				print dol_get_fiche_end();

				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		} else {
			print dol_get_fiche_head($head2, $tab, '', -1, '', 0, '', '', 0, 'formodulesuffix'); // Level 2
		}

		if ($tab == 'languages')
		{
			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("LanguageDefDesc").'</span><br>';
				print '<br>';


				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addlanguage">';
				print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
				print '<input type="hidden" name="tab" value="'.$tab.'">';
				print '<input type="hidden" name="module" value="'.$module.'">';
				print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT, 'newlangcode', 0, 0, 1, 0, 0, 'minwidth300', 1);
				print '<input type="submit" name="addlanguage" class="button" value="'.dol_escape_htmltag($langs->trans("AddLanguageFile")).'"><br>';
				print '</form>';

				print '<br>';
				print '<br>';

				$langfiles = dol_dir_list(dol_buildpath($modulelowercase.'/langs', 0), 'files', 1, '\.lang$');

				print '<table class="none">';
				foreach ($langfiles as $langfile)
				{
					$pathtofile = $modulelowercase.'/langs/'.$langfile['relativename'];
					print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("LanguageFile").' '.basename(dirname($pathtofile)).' : <strong>'.$pathtofile.'</strong>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=txt&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'text'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'dictionaries')
		{
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$dicts = $moduleobj->dictionaries;

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">';
				$htmlhelp = $langs->trans("DictionariesDefDescTooltip", '<a href="'.DOL_URL_ROOT.'/admin/dict.php">'.$langs->trans('Setup').' - '.$langs->trans('Dictionaries').'</a>');
				print $form->textwithpicto($langs->trans("DictionariesDefDesc"), $htmlhelp, 1, 'help', '', 0, 2, 'helpondesc').'<br>';
				print '</span>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';
				if (is_array($dicts) && !empty($dicts)) {
					print '<span class="fa fa-file-o"></span> '.$langs->trans("LanguageFile").' :</span> ';
					print '<strong>'.$dicts['langs'].'</strong>';
					print '<br>';
				}

				print load_fiche_titre($langs->trans("ListOfDictionariesEntries"), '', '');

				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addproperty">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

				print '<div class="div-table-responsive">';
				print '<table class="noborder">';

				print '<tr class="liste_titre">';
				print_liste_field_titre("#", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Table", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Label", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("SQL", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("SQLSort", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("FieldsView", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("FieldsEdit", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("FieldsInsert", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Rowid", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Condition", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print "</tr>\n";

				if (is_array($dicts) && is_array($dicts['tabname']))
				{
					$i = 0;
					$maxi = count($dicts['tabname']);
					while ($i < $maxi)
					{
						print '<tr class="oddeven">';

						print '<td>';
						print ($i + 1);
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

						print '<td class="right">';
						print $dicts['tabrowid'][$i];
						print '</td>';

						print '<td class="right">';
						print $dicts['tabcond'][$i];
						print '</td>';

						print '</tr>';
						$i++;
					}
				} else {
					print '<tr><td class="opacitymedium" colspan="5">'.$langs->trans("None").'</td></tr>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'objects')
		{
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
			foreach ($listofobject as $fileobj)
			{
				if (preg_match('/^api_/', $fileobj['name'])) continue;
				if (preg_match('/^actions_/', $fileobj['name'])) continue;

				$tmpcontent = file_get_contents($fileobj['fullname']);
				if (preg_match('/class\s+([^\s]*)\s+extends\s+CommonObject/ims', $tmpcontent, $reg))
				{
					//$objectname = preg_replace('/\.txt$/', '', $fileobj['name']);
					$objectname = $reg[1];
					if (empty($firstobjectname)) $firstobjectname = $objectname;

					$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj='.$objectname;
					$head3[$h][1] = $objectname;
					$head3[$h][2] = $objectname;
					$h++;
				}
			}

			$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread ? '@'.$dirread : '').'&tabobj=deleteobject';
			$head3[$h][1] = $langs->trans("DangerZone");
			$head3[$h][2] = 'deleteobject';
			$h++;

			// If tabobj was not defined, then we check if there is one obj. If yes, we force on it, if no, we will show tab to create new objects.
			if ($tabobj == 'newobjectifnoobj')
			{
				if ($firstobjectname) $tabobj = $firstobjectname;
				else $tabobj = 'newobject';
			}

			print dol_get_fiche_head($head3, $tabobj, '', -1, ''); // Level 3

			if ($tabobj == 'newobject')
			{
				// New object tab
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="initobject">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

				print '<span class="opacitymedium">'.$langs->trans("EnterNameOfObjectDesc").'</span><br><br>';

				print '<input type="text" name="objectname" maxlength="64" value="'.dol_escape_htmltag(GETPOST('objectname', 'alpha') ? GETPOST('objectname', 'alpha') : $modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("ObjectKey")).'"><br>';
				print '<input type="checkbox" name="includerefgeneration" value="includerefgeneration"> '.$form->textwithpicto($langs->trans("IncludeRefGeneration"), $langs->trans("IncludeRefGenerationHelp")).'<br>';
				print '<input type="checkbox" name="includedocgeneration" value="includedocgeneration"> '.$form->textwithpicto($langs->trans("IncludeDocGeneration"), $langs->trans("IncludeDocGenerationHelp")).'<br>';
				print '<input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("Generate")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
				print '<br>';
				print '<br>';
				print '<br>';
				print $langs->trans("or");
				print '<br>';
				print '<br>';
				//print '<input type="checkbox" name="initfromtablecheck"> ';
				print $langs->trans("InitStructureFromExistingTable");
				print '<input type="text" name="initfromtablename" value="" placeholder="'.$langs->trans("TableName").'">';
				print '<input type="submit" class="button" name="createtablearray" value="'.dol_escape_htmltag($langs->trans("Generate")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
				print '<br>';

				print '</form>';
			} elseif ($tabobj == 'deleteobject') {
				// Delete object tab
				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="confirm_deleteobject">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

				print $langs->trans("EnterNameOfObjectToDeleteDesc").'<br><br>';

				print '<input type="text" name="objectname" value="'.dol_escape_htmltag($modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("ObjectKey")).'">';
				print '<input type="submit" class="button smallpaddingimp" name="delete" value="'.dol_escape_htmltag($langs->trans("Delete")).'"'.($dirins ? '' : ' disabled="disabled"').'>';
				print '</form>';
			} else {
				// tabobj = module
				if ($action == 'deleteproperty')
				{
					$formconfirm = $form->formconfirm(
						$_SERVER["PHP_SELF"].'?propertykey='.urlencode(GETPOST('propertykey', 'alpha')).'&objectname='.urlencode($objectname).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj),
						$langs->trans('Delete'), $langs->trans('ConfirmDeleteProperty', GETPOST('propertykey', 'alpha')), 'confirm_deleteproperty', '', 0, 1
						);

					// Print form confirm
					print $formconfirm;
				}

				if ($action != 'editfile' || empty($file))
				{
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
						$pathtosql      = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'.sql';
						$pathtosqlextra = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'_extrafields.sql';
						$pathtosqlkey   = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'.key.sql';
						$pathtosqlextrakey = strtolower($module).'/sql/llx_'.strtolower($module).'_'.strtolower($tabobj).'_extrafields.key.sql';
						$pathtolib      = strtolower($module).'/lib/'.strtolower($module).'.lib.php';
						$pathtoobjlib   = strtolower($module).'/lib/'.strtolower($module).'_'.strtolower($tabobj).'.lib.php';
						$pathtopicto    = strtolower($module).'/img/object_'.strtolower($tabobj).'.png';
						$pathtoscript   = strtolower($module).'/scripts/'.strtolower($tabobj).'.php';

						//var_dump($pathtoclass); var_dump($dirread);
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
						$realpathtopicto    = $dirread.'/'.$pathtopicto;
						$realpathtoscript   = $dirread.'/'.$pathtoscript;

						if (empty($realpathtoapi)) 	// For compatibility with some old modules
						{
							$pathtoapi = strtolower($module).'/class/api_'.strtolower($module).'s.class.php';
							$realpathtoapi = $dirread.'/'.$pathtoapi;
						}
						$urloflist = $dirread.'/'.$pathtolist;
						$urlofcard = $dirread.'/'.$pathtocard;

						print '<div class="fichehalfleft">';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("ClassFile").' : <strong>'.($realpathtoclass ? '' : '<strike>').$pathtoclass.($realpathtoclass ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtoclass).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("ApiClassFile").' : <strong>'.($realpathtoapi ? '' : '<strike>').$pathtoapi.($realpathtoapi ? '' : '</strike>').'</strong>';
						if (dol_is_file($realpathtoapi)) {
							print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtoapi).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
		   					print ' ';
		   					print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtoapi).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
		   					print ' &nbsp; ';
		   					if (empty($conf->global->$const_name))	// If module is not activated
		   					{
		   						print '<a href="#" class="classfortooltip" target="apiexplorer" title="'.$langs->trans("ModuleMustBeEnabled", $module).'"><strike>'.$langs->trans("GoToApiExplorer").'</strike></a>';
		   					} else {
		   						print '<a href="'.DOL_URL_ROOT.'/api/index.php/explorer/" target="apiexplorer">'.$langs->trans("GoToApiExplorer").'</a>';
		   					}
						} else {
							//print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span> ';
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initapi&format=php&file='.urlencode($pathtoapi).'"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a>';
						}
						// PHPUnit
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("TestClassFile").' : <strong>'.($realpathtophpunit ? '' : '<strike>').$pathtophpunit.($realpathtophpunit ? '' : '</strike>').'</strong>';
						if (dol_is_file($realpathtophpunit)) {
							print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtophpunit).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtophpunit).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							//print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span> ';
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initphpunit&format=php&file='.urlencode($pathtophpunit).'"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a>';
						}
						print '<br>';

						print '<br>';

						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForLib").' : <strong>'.($realpathtolib ? '' : '<strike>').$pathtolib.($realpathtolib ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtolib).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForObjLib").' : <strong>'.($realpathtoobjlib ? '' : '<strike>').$pathtoobjlib.($realpathtoobjlib ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtoobjlib).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						print '<span class="fa fa-file-image-o"></span> '.$langs->trans("Image").' : <strong>'.($realpathtopicto ? '' : '<strike>').$pathtopicto.($realpathtopicto ? '' : '</strike>').'</strong>';
						//print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtopicto).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';

						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFile").' : <strong>'.($realpathtosql ? '' : '<strike>').$pathtosql.($realpathtosql ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=sql&file='.urlencode($pathtosql).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=droptable">'.$langs->trans("DropTableIfEmpty").'</a>';
						//print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("RunSql").'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFileKey").' : <strong>'.($realpathtosqlkey ? '' : '<strike>').$pathtosqlkey.($realpathtosqlkey ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=sql&file='.urlencode($pathtosqlkey).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						//print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("RunSql").'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFileExtraFields").' : <strong>'.($realpathtosqlextra ? '' : '<strike>').$pathtosqlextra.($realpathtosqlextra ? '' : '</strike>').'</strong>';
						if (dol_is_file($realpathtosqlextra) && dol_is_file($realpathtosqlextrakey)) {
							print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&file='.urlencode($pathtosqlextra).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtosqlextra).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
							print ' &nbsp; ';
							print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=droptableextrafields">'.$langs->trans("DropTableIfEmpty").'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initsqlextrafields&format=sql&file='.urlencode($pathtosqlextra).'"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a>';
						}
						//print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("RunSql").'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SqlFileKeyExtraFields").' : <strong>'.($realpathtosqlextrakey ? '' : '<strike>').$pathtosqlextrakey.($realpathtosqlextrakey ? '' : '</strike>').'</strong>';
						if (dol_is_file($realpathtosqlextra) && dol_is_file($realpathtosqlextrakey)) {
							print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=sql&file='.urlencode($pathtosqlextrakey).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtosqlextrakey).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						} else {
							print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initsqlextrafields&format=sql&file='.urlencode($pathtosqlextra).'"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a>';
						}
						//print ' &nbsp; <a href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("RunSql").'</a>';
						print '<br>';

						print '<br>';
						print '</div>';

						print '<div class="fichehalfleft">';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForList").' : <strong><a href="'.$urloflist.'" target="_test">'.($realpathtolist ? '' : '<strike>').$pathtolist.($realpathtolist ? '' : '</strike>').'</a></strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtolist).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForCreateEditView").' : <strong><a href="'.$urlofcard.'?action=create" target="_test">'.($realpathtocard ? '' : '<strike>').$pathtocard.($realpathtocard ? '' : '</strike>').'?action=create</a></strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtocard).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForContactTab").' : <strong>'.($realpathtocontact ? '' : '<strike>').$pathtocontact.($realpathtocontact ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtocontact).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtocontact)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtocontact).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						}
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForDocumentTab").' : <strong>'.($realpathtodocument ? '' : '<strike>').$pathtodocument.($realpathtodocument ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtodocument).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtodocument)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtodocument).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						}
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForNoteTab").' : <strong>'.($realpathtonote ? '' : '<strike>').$pathtonote.($realpathtonote ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtonote).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtonote)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtonote).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						}
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("PageForAgendaTab").' : <strong>'.($realpathtoagenda ? '' : '<strike>').$pathtoagenda.($realpathtoagenda ? '' : '</strike>').'</strong>';
						print ' <a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtoagenda).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						if (dol_is_file($realpathtoagenda)) {
							print ' ';
							print '<a class="reposition editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&file='.urlencode($pathtoagenda).'">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
						}
						print '<br>';

						/* This is already on Tab CLI
						print '<br>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("ScriptFile").' : <strong>'.($realpathtoscript?'':'<strike>').$pathtoscript.($realpathtoscript?'':'</strike>').'</strong>';
						print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&tabobj='.$tabobj.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtoscript).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '<br>';*/

						print '<br>';

						print '</div>';

						print '<br><br><br>';

						if (function_exists('opcache_invalidate')) opcache_invalidate($dirread.'/'.$pathtoclass, true); // remove the include cache hell !

						if (empty($forceddirread) && empty($dirread))
						{
							$result = dol_include_once($pathtoclass);
						} else {
							$result = @include_once $dirread.'/'.$pathtoclass;
						}
						if (class_exists($tabobj))
						{
							try {
								$tmpobjet = @new $tabobj($db);
							} catch (Exception $e)
							{
								dol_syslog('Failed to load Constructor of class: '.$e->getMessage(), LOG_WARNING);
							}
						}

						if (!empty($tmpobjet))
						{
							$reflector = new ReflectionClass($tabobj);
							$reflectorproperties = $reflector->getProperties(); // Can also use get_object_vars
							$reflectorpropdefault = $reflector->getDefaultProperties(); // Can also use get_object_vars
							//$propstat = $reflector->getStaticProperties();
							//var_dump($reflectorpropdefault);

							print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
							print '<input type="hidden" name="token" value="'.newToken().'">';
							print '<input type="hidden" name="action" value="addproperty">';
							print '<input type="hidden" name="tab" value="objects">';
							print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module.($forceddirread ? '@'.$dirread : '')).'">';
							print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

							print '<input class="button smallpaddingimp" type="submit" name="regenerateclasssql" value="'.$langs->trans("RegenerateClassAndSql").'">';
							print '<br><br>';

							print load_fiche_titre($langs->trans("ObjectProperties"), '', '');

							print '<!-- Table with properties of object -->'."\n";
							print '<div class="div-table-responsive">';
							print '<table class="noborder">';
							print '<tr class="liste_titre">';
							print '<th>'.$langs->trans("Property");
							print ' (<a class="" href="https://wiki.dolibarr.org/index.php/Language_and_development_rules#Table_and_fields_structures" target="_blank">'.$langs->trans("SeeExamples").'</a>)';
							print '</th>';
							print '<th>';
							print $form->textwithpicto($langs->trans("Label"), $langs->trans("YouCanUseTranslationKey"));
							print '</th>';
							print '<th>'.$form->textwithpicto($langs->trans("Type"), $langs->trans("TypeOfFieldsHelp")).'</th>';
							print '<th>'.$form->textwithpicto($langs->trans("ArrayOfKeyValues"), $langs->trans("ArrayOfKeyValuesDesc")).'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("NotNull"), $langs->trans("NotNullDesc")).'</th>';
							print '<th class="center">'.$langs->trans("DefaultValue").'</th>';
							print '<th class="center">'.$langs->trans("DatabaseIndex").'</th>';
							print '<th class="center">'.$langs->trans("ForeignKey").'</th>';
							print '<th class="right">'.$langs->trans("Position").'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("Enabled"), $langs->trans("EnabledDesc")).'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("Visible"), $langs->trans("VisibleDesc")).'</th>';
							print '<th class="center">'.$langs->trans("NotEditable").'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("SearchAll"), $langs->trans("SearchAllDesc")).'</th>';
							print '<th class="center">'.$form->textwithpicto($langs->trans("IsAMeasure"), $langs->trans("IsAMeasureDesc")).'</th>';
							print '<th class="center">'.$langs->trans("CSSClass").'</th>';
							print '<th class="center">'.$langs->trans("CSSViewClass").'</th>';
							print '<th class="center">'.$langs->trans("KeyForTooltip").'</th>';
							print '<th class="center">'.$langs->trans("ShowOnCombobox").'</th>';
							//print '<th class="center">'.$langs->trans("Disabled").'</th>';
							print '<th>'.$langs->trans("Comment").'</th>';
							print '<th></th>';
							print '</tr>';

							// We must use $reflectorpropdefault['fields'] to get list of fields because $tmpobjet->fields may have been
							// modified during the constructor and we want value into head of class before constructor is called.
							//$properties = dol_sort_array($tmpobjet->fields, 'position');
							$properties = dol_sort_array($reflectorpropdefault['fields'], 'position');

							if (!empty($properties))
							{
								// Line to add a property
								print '<tr>';
								print '<td><input class="text maxwidth75" name="propname" value="'.dol_escape_htmltag(GETPOST('propname', 'alpha')).'"></td>';
								print '<td><input class="text maxwidth75" name="proplabel" value="'.dol_escape_htmltag(GETPOST('proplabel', 'alpha')).'"></td>';
								print '<td><input class="text maxwidth75" name="proptype" value="'.dol_escape_htmltag(GETPOST('proptype', 'alpha')).'"></td>';
								print '<td><input class="text maxwidth75" name="proparrayofkeyval" value="'.dol_escape_htmltag(GETPOST('proparrayofkeyval', 'restricthtml')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propnotnull" value="'.dol_escape_htmltag(GETPOST('propnotnull', 'alpha')).'"></td>';
								print '<td><input class="text maxwidth50" name="propdefault" value="'.dol_escape_htmltag(GETPOST('propdefault', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propindex" value="'.dol_escape_htmltag(GETPOST('propindex', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propforeignkey" value="'.dol_escape_htmltag(GETPOST('propforeignkey', 'alpha')).'"></td>';
								print '<td class="right"><input class="text right" size="2" name="propposition" value="'.dol_escape_htmltag(GETPOST('propposition', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propenabled" value="'.dol_escape_htmltag(GETPOST('propenabled', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propvisible" value="'.dol_escape_htmltag(GETPOST('propvisible', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propnoteditable" value="'.dol_escape_htmltag(GETPOST('propnoteditable', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propsearchall" value="'.dol_escape_htmltag(GETPOST('propsearchall', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propisameasure" value="'.dol_escape_htmltag(GETPOST('propisameasure', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propcss" value="'.dol_escape_htmltag(GETPOST('propcss', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propcssview" value="'.dol_escape_htmltag(GETPOST('propcssview', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="prophelp" value="'.dol_escape_htmltag(GETPOST('prophelp', 'alpha')).'"></td>';
								print '<td class="center"><input class="text" size="2" name="propshowoncombobox" value="'.dol_escape_htmltag(GETPOST('propshowoncombobox', 'alpha')).'"></td>';
								//print '<td class="center"><input class="text" size="2" name="propdisabled" value="'.dol_escape_htmltag(GETPOST('propdisabled', 'alpha')).'"></td>';
								print '<td><input class="text maxwidth100" name="propcomment" value="'.dol_escape_htmltag(GETPOST('propcomment', 'alpha')).'"></td>';
								print '<td class="center">';
								print '<input class="button" type="submit" name="add" value="'.$langs->trans("Add").'">';
								print '</td></tr>';

								// List of existing properties
								foreach ($properties as $propkey => $propval)
								{
									/* If from Reflection
									 if ($propval->class == $tabobj)
									 {
									 $propname=$propval->getName();
									 $comment=$propval->getDocComment();
									 $type=gettype($tmpobjet->$propname);
									 $default=$propdefault[$propname];
									 // Discard generic properties
									 if (in_array($propname, array('element', 'childtables', 'table_element', 'table_element_line', 'class_element_line', 'ismultientitymanaged'))) continue;

									 // Keep or not lines
									 if (in_array($propname, array('fk_element', 'lines'))) continue;
									 }*/

									$propname = $propkey;
									$proplabel = $propval['label'];
									$proptype = $propval['type'];
									$proparrayofkeyval = $propval['arrayofkeyval'];
									$propnotnull = $propval['notnull'];
									$propdefault = $propval['default'];
									$propindex = $propval['index'];
									$propforeignkey = $propval['foreignkey'];
									$propposition = $propval['position'];
									$propenabled = $propval['enabled'];
									$propvisible = $propval['visible'];
									$propnoteditable = $propval['noteditable'];
									$propsearchall = $propval['searchall'];
									$propisameasure = $propval['isameasure'];
									$propcss = $propval['css'];
									$propcssview = $propval['cssview'];
									$prophelp = $propval['help'];
									$propshowoncombobox = $propval['showoncombobox'];
									//$propdisabled=$propval['disabled'];
									$propcomment = $propval['comment'];

									print '<tr class="oddeven">';

									print '<td>';
									print dol_escape_htmltag($propname);
									print '</td>';
									print '<td>';
									print dol_escape_htmltag($proplabel);
									print '</td>';
									print '<td class="tdoverflowmax200">';
									print '<span title="'.dol_escape_htmltag($proptype).'">'.dol_escape_htmltag($proptype).'</span>';
									print '</td>';
									print '<td class="tdoverflowmax200">';
									if ($proparrayofkeyval) {
										print '<span title="'.dol_escape_htmltag(json_encode($proparrayofkeyval)).'">';
										print dol_escape_htmltag(json_encode($proparrayofkeyval));
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
									print '<td class="center">';
									print $propenabled ? dol_escape_htmltag($propenabled) : '';
									print '</td>';
									print '<td class="center">';
									print $propvisible ? dol_escape_htmltag($propvisible) : '0';
									print '</td>';
									print '<td class="center">';
									print $propnoteditable ? dol_escape_htmltag($propnoteditable) : '';
									print '</td>';
									print '<td class="center">';
									print $propsearchall ? '1' : '';
									print '</td>';
									print '<td class="center">';
									print $propisameasure ? dol_escape_htmltag($propisameasure) : '';
									print '</td>';
									print '<td class="center">';
									print $propcss ? dol_escape_htmltag($propcss) : '';
									print '</td>';
									print '<td class="center">';
									print $propcssview ? dol_escape_htmltag($propcssview) : '';
									print '</td>';
									print '<td class="tdoverflowmax200">';
									print $prophelp ? dol_escape_htmltag($prophelp) : '';
									print '</td>';
									print '<td class="center">';
									print $propshowoncombobox ? dol_escape_htmltag($propshowoncombobox) : '';
									print '</td>';
									/*print '<td class="center">';
									print $propdisabled?$propdisabled:'';
									print '</td>';*/
									print '<td class="tdoverflowmax200">';
									print '<span title="'.dol_escape_htmltag($propcomment).'">';
									print dol_escape_htmltag($propcomment);
									print '</span>';
									print '</td>';
									print '<td class="center">';
									if ($propname != 'rowid')
									{
										print '<a href="'.$_SERVER["PHP_SELF"].'?action=deleteproperty&token='.newToken().'&propertykey='.urlencode($propname).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj).'">'.img_delete().'</a>';
									}
									print '</td>';

									print '</tr>';
								}
							} else {
								if ($tab == 'specifications')
								{
									if ($action != 'editfile' || empty($file))
									{
										print '<span class="opacitymedium">'.$langs->trans("SpecDefDesc").'</span><br>';
										print '<br>';

										$specs = dol_dir_list(dol_buildpath($modulelowercase.'/doc', 0), 'files', 1, '(\.md|\.asciidoc)$', array('\/temp\/'));

										foreach ($specs as $spec)
										{
											$pathtofile = $modulelowercase.'/doc/'.$spec['relativename'];
											$format = 'asciidoc';
											if (preg_match('/\.md$/i', $spec['name'])) $format = 'markdown';
											print '<span class="fa fa-file-o"></span> '.$langs->trans("SpecificationFile").' : <strong>'.$pathtofile.'</strong>';
											print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
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

										$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
										print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
										print '<br>';
										print '<center>';
										print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
										print ' &nbsp; ';
										print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
										print '</center>';

										print '</form>';
									}
								}
								print '<tr><td><span class="warning">'.$langs->trans('Property $field not found into the class. The class was probably not generated by modulebuilder.').'</warning></td></tr>';
							}
							print '</table>';
							print '</div>';

							print '</form>';
						} else {
							print '<tr><td><span class="warning">'.$langs->trans('Failed to init the object with the new.').'</warning></td></tr>';
						}
					} catch (Exception $e)
					{
						print $e->getMessage();
					}
				} else {
					if (empty($forceddirread))
					{
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

					$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
					print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
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

		if ($tab == 'menus')
		{
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$menus = $moduleobj->menu;

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">';
				$htmlhelp = $langs->trans("MenusDefDescTooltip", '<a href="'.DOL_URL_ROOT.'/admin/menus/index.php">'.$langs->trans('Setup').' - '.$langs->trans('Menus').'</a>');
				print $form->textwithpicto($langs->trans("MenusDefDesc"), $htmlhelp, 1, 'help', '', 0, 2, 'helpondesc').'<br>';
				print '</span>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';

				print '<br>';
				print load_fiche_titre($langs->trans("ListOfMenusEntries"), '', '');

				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addproperty">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

				print '<div class="div-table-responsive">';
				print '<table class="noborder">';

				print '<tr class="liste_titre">';
				print_liste_field_titre("Type", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("fk_menu", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Title", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("mainmenu", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("leftmenu", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("URL", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("LanguageFile", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Position", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Enabled", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Permission", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Target", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("UserType", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder, 'right ');
				print "</tr>\n";

				if (count($menus))
				{
					foreach ($menus as $menu)
					{
						print '<tr class="oddeven">';

						print '<td>';
						print $menu['type'];
						print '</td>';

						print '<td>';
						print $menu['fk_menu'];
						print '</td>';

						print '<td>';
						print $menu['titre'];
						print '</td>';

						print '<td>';
						print $menu['mainmenu'];
						print '</td>';

						print '<td>';
						print $menu['leftmenu'];
						print '</td>';

						print '<td>';
						print $menu['url'];
						print '</td>';

						print '<td>';
						print $menu['langs'];
						print '</td>';

						print '<td>';
						print $menu['position'];
						print '</td>';

						print '<td>';
						print $menu['enabled'];
						print '</td>';

						print '<td>';
						print $menu['perms'];
						print '</td>';

						print '<td>';
						print $menu['target'];
						print '</td>';

						print '<td class="right">';
						print $menu['user'];
						print '</td>';

						print '</tr>';
					}
				} else {
				 	print '<tr><td class="opacitymedium" colspan="5">'.$langs->trans("None").'</td></tr>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'permissions')
		{
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$perms = $moduleobj->rights;

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">';
				$htmlhelp = $langs->trans("PermissionsDefDescTooltip", '<a href="'.DOL_URL_ROOT.'/admin/perms.php">'.$langs->trans('DefaultPermissions').'</a>');
				print $form->textwithpicto($langs->trans("PermissionsDefDesc"), $htmlhelp, 1, 'help', '', 0, 2, 'helpondesc').'<br>';
				print '</span>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '<br>';

				print '<br>';
				print load_fiche_titre($langs->trans("ListOfPermissionsDefined"), '', '');

				print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="addproperty">';
				print '<input type="hidden" name="tab" value="objects">';
				print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
				print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

				print '<div class="div-table-responsive">';
				print '<table class="noborder">';

				print '<tr class="liste_titre">';
				print_liste_field_titre("ID", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Label", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("Permission", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print_liste_field_titre("", $_SERVER["PHP_SELF"], '', "", $param, '', $sortfield, $sortorder);
				print "</tr>\n";

				if (count($perms))
				{
					foreach ($perms as $perm)
					{
						print '<tr class="oddeven">';

						print '<td>';
						print $perm[0];
						print '</td>';

						print '<td>';
						print $perm[1];
						print '</td>';

						print '<td>';
						print $perm[4];
						print '</td>';

						print '<td>';
						print $perm[5];
						print '</td>';

						print '</tr>';
					}
				} else {
					print '<tr><td class="opacitymedium" colspan="4">'.$langs->trans("None").'</td></tr>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'hooks')
		{
			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("HooksDefDesc").'</span><br>';
				print '<br>';

				print '<table>';

				$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
				print '<tr><td>';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="">'.$pathtofile.'</strong>';
				print '</td><td>';
				print '<a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				print '<tr><td>';
				$pathtohook = strtolower($module).'/class/actions_'.strtolower($module).'.class.php';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("HooksFile").' : ';
				if (dol_is_file($dirins.'/'.$pathtohook))
				{
					print '<strong>'.$pathtohook.'</strong>';
					print '</td>';
					print '<td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Edit"), 'edit').'</a> ';
					print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&format='.$format.'&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
				} else {
					print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '<a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=inithook&format=php&file='.urlencode($pathtohook).'"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a></td>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'triggers')
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

			$interfaces = new Interfaces($db);
			$triggers = $interfaces->getTriggersList(array('/'.strtolower($module).'/core/triggers'));

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("TriggerDefDesc").'</span><br>';
				print '<br>';

				print '<table>';

				$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];
				print '<tr><td>';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong class="">'.$pathtofile.'</strong>';
				print '</td><td>';
				print '<a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
				print '</td></tr>';

				if (!empty($triggers))
				{
					foreach ($triggers as $trigger)
					{
						$pathtofile = $trigger['relpath'];

						print '<tr><td>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("TriggersFile").' : <strong>'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
						print '<td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td>';
					print '<span class="fa fa-file-o"></span> '.$langs->trans("NoTrigger");
					print '<a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=inittrigger&format=php"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a></td>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'css')
		{
			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("CSSDesc").'</span><br>';
				print '<br>';

				print '<table>';

				print '<tr><td>';
				$pathtohook = strtolower($module).'/css/'.strtolower($module).'.css.php';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("CSSFile").' : ';
				if (dol_is_file($dirins.'/'.$pathtohook))
				{
					print '<strong>'.$pathtohook.'</strong>';
					print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&format='.$format.'&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
				} else {
					print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initcss&format=php&file='.urlencode($pathtohook).'"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a></td>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'js')
		{
			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("JSDesc").'</span><br>';
				print '<br>';

				print '<table>';

				print '<tr><td>';
				$pathtohook = strtolower($module).'/js/'.strtolower($module).'.js.php';
				print '<span class="fa fa-file-o"></span> '.$langs->trans("JSFile").' : ';
				if (dol_is_file($dirins.'/'.$pathtohook))
				{
					print '<strong>'.$pathtohook.'</strong>';
					print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
					print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&format='.$format.'&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
				} else {
					print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initjs&format=php&file='.urlencode($pathtohook).'"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a></td>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'widgets')
		{
			require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

			$widgets = ModeleBoxes::getWidgetsList(array('/'.strtolower($module).'/core/boxes'));

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("WidgetDesc").'</span><br>';
				print '<br>';

				print '<table>';
				if (!empty($widgets))
				{
					foreach ($widgets as $widget)
					{
						$pathtofile = $widget['relpath'];

						print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("WidgetFile").' : <strong>'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
						print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("NoWidget");
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initwidget&format=php"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'cli')
		{
			$clifiles = array();
			$i = 0;

			$dircli = array('/'.strtolower($module).'/scripts');

			foreach ($dircli as $reldir)
			{
				$dir = dol_buildpath($reldir, 0);
				$newdir = dol_osencode($dir);

				// Check if directory exists (we do not use dol_is_dir to avoid loading files.lib.php at each call)
				if (!is_dir($newdir)) continue;

				$handle = opendir($newdir);
				if (is_resource($handle))
				{
					while (($tmpfile = readdir($handle)) !== false)
					{
						if (is_readable($newdir.'/'.$file) && preg_match('/^(.+)\.php/', $tmpfile, $reg))
						{
							if (preg_match('/\.back$/', $tmpfile)) continue;

							$clifiles[$i]['relpath'] = preg_replace('/^\//', '', $reldir).'/'.$tmpfile;

							$i++;
						}
					}
					closedir($handle);
				}
			}

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("CLIDesc").'</span><br>';
				print '<br>';

				print '<table>';
				if (!empty($clifiles))
				{
					foreach ($clifiles as $clifile)
					{
						$pathtofile = $clifile['relpath'];

						print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("CLIFile").' : <strong>'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
						print '<td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td><span class="fa fa-file-o"></span> '.$langs->trans("NoCLIFile");
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initcli&format=php"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'cron')
		{
			$pathtofile = $listofmodules[strtolower($module)]['moduledescriptorrelpath'];

			$cronjobs = $moduleobj->cronjobs;

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.str_replace('{s1}', '<a href="'.DOL_URL_ROOT.'/cron/list.php">'.$langs->transnoentities('CronList').'</a>', $langs->trans("CronJobDefDesc", '{s1}')).'</span><br>';
				print '<br>';

				print '<span class="fa fa-file-o"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
				print ' <a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
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

				if (count($cronjobs))
				{
					foreach ($cronjobs as $cron)
					{
						print '<tr class="oddeven">';

						print '<td>';
						print $cron['label'];
						print '</td>';

						print '<td>';
						if ($cron['jobtype'] == 'method')
						{
							$text = $langs->trans("CronClass");
							$texttoshow = $langs->trans('CronModule').': '.$module.'<br>';
							$texttoshow .= $langs->trans('CronClass').': '.$cron['class'].'<br>';
							$texttoshow .= $langs->trans('CronObject').': '.$cron['objectname'].'<br>';
							$texttoshow .= $langs->trans('CronMethod').': '.$cron['method'];
							$texttoshow .= '<br>'.$langs->trans('CronArgs').': '.$cron['parameters'];
							$texttoshow .= '<br>'.$langs->trans('Comment').': '.$langs->trans($cron['comment']);
						} elseif ($cron['jobtype'] == 'command')
						{
							$text = $langs->trans('CronCommand');
							$texttoshow = $langs->trans('CronCommand').': '.dol_trunc($cron['command']);
							$texttoshow .= '<br>'.$langs->trans('CronArgs').': '.$cron['parameters'];
							$texttoshow .= '<br>'.$langs->trans('Comment').': '.$langs->trans($cron['comment']);
						}
						print $form->textwithpicto($text, $texttoshow, 1);
						print '</td>';

						print '<td>';
						if ($cron['unitfrequency'] == "60") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Minutes');
						if ($cron['unitfrequency'] == "3600") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Hours');
						if ($cron['unitfrequency'] == "86400") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Days');
						if ($cron['unitfrequency'] == "604800") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Weeks');
						print '</td>';

						print '<td>';
						print $cron['status'];
						print '</td>';

						print '<td>';
						if (!empty($cron['comment'])) {print $cron['comment']; }
						print '</td>';

						print '</tr>';
					}
				} else {
					print '<tr><td class="opacitymedium" colspan="5">'.$langs->trans("None").'</td></tr>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
				print '<br>';
				print '<center>';
				print '<input type="submit" class="button buttonforacesave button-save" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
				print '</center>';

				print '</form>';
			}
		}

		if ($tab == 'specifications')
		{
			$specs = dol_dir_list(dol_buildpath($modulelowercase.'/doc', 0), 'files', 1, '(\.md|\.asciidoc)$', array('\/temp\/'));

			if ($action != 'editfile' || empty($file))
			{
				print '<span class="opacitymedium">'.$langs->trans("SpecDefDesc").'</span><br>';
				print '<br>';

				print '<table>';
				if (is_array($specs) && !empty($specs))
				{
					foreach ($specs as $spec)
					{
						$pathtofile = $modulelowercase.'/doc/'.$spec['relativename'];
						$format = 'asciidoc';
						if (preg_match('/\.md$/i', $spec['name'])) $format = 'markdown';
						print '<tr><td>';
						print '<span class="fa fa-file-o"></span> '.$langs->trans("SpecificationFile").' : <strong>'.$pathtofile.'</strong>';
						print '</td><td><a class="editfielda paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=editfile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a></td>';
						print '<td><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=confirm_removefile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Delete"), 'delete').'</a></td>';
						print '</tr>';
					}
				} else {
					print '<tr><td>';
					print '<span class="fa fa-file-o"></span> '.$langs->trans("FileNotYetGenerated");
					print '</td><td><a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread ? '@'.$dirread : '').'&action=initdoc&format=php"><input type="button" class="button smallpaddingimp" value="'.$langs->trans("Generate").'"></a></td>';
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

				$doleditor = new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
				print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format', 'aZ09') ?GETPOST('format', 'aZ09') : 'html'));
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
			$outputfiledocpdf = dol_buildpath($modulelowercase, 0).'/doc/'.$FILENAMEDOCPDF;
			$outputfiledocurlpdf = dol_buildpath($modulelowercase, 1).'/doc/'.$FILENAMEDOCPDF;

			// HTML
			print '<span class="fa fa-file-o"></span> '.$langs->trans("PathToModuleDocumentation", "HTML").' : ';
			if (!dol_is_file($outputfiledoc)) print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
			else {
				print '<strong>';
				print '<a href="'.$outputfiledocurl.'" target="_blank">';
				print $outputfiledoc;
				print '</a>';
				print '</strong>';
				print ' ('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfiledoc), 'dayhour').')';
			}
			print '</strong><br>';

			// PDF
			print '<span class="fa fa-file-o"></span> '.$langs->trans("PathToModuleDocumentation", "PDF").' : ';
			if (!dol_is_file($outputfiledocpdf)) print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
			else {
				print '<strong>';
				print '<a href="'.$outputfiledocurlpdf.'" target="_blank">';
				print $outputfiledocpdf;
				print '</a>';
				print '</strong>';
				print ' ('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfiledocpdf), 'dayhour').')';
			}
			print '</strong><br>';

			print '<br>';

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="generatedoc">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="generatedoc">';
			print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
			print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
			print '<input type="submit" class="button" name="generatedoc" value="'.$langs->trans("BuildDocumentation").'"';
			if (!is_array($specs) || empty($specs)) print ' disabled="disabled"';
			print '>';
			print '</form>';
		}

		if ($tab == 'buildpackage')
		{
			print '<span class="opacitymedium">'.$langs->trans("BuildPackageDesc").'</span>';
			print '<br>';

			if (!class_exists('ZipArchive') && !defined('ODTPHP_PATHTOPCLZIP'))
			{
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

			if (class_exists($class))
			{
				try {
					$moduleobj = new $class($db);
				} catch (Exception $e) {
					$error++;
					dol_print_error($e->getMessage());
				}
			} else {
				$error++;
				$langs->load("errors");
				dol_print_error($langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
				exit;
			}

			$arrayversion = explode('.', $moduleobj->version, 3);
			if (count($arrayversion))
			{
				$FILENAMEZIP = "module_".$modulelowercase.'-'.$arrayversion[0].'.'.$arrayversion[1].($arrayversion[2] ? ".".$arrayversion[2] : "").".zip";
				$outputfilezip = dol_buildpath($modulelowercase, 0).'/bin/'.$FILENAMEZIP;
			}

			print '<br>';

			print '<span class="fa fa-file-o"></span> '.$langs->trans("PathToModulePackage").' : ';
			if (!dol_is_file($outputfilezip)) print '<span class="opacitymedium">'.$langs->trans("FileNotYetGenerated").'</span>';
			else {
				$relativepath = $modulelowercase.'/bin/'.$FILENAMEZIP;
				print '<strong><a href="'.DOL_URL_ROOT.'/document.php?modulepart=packages&file='.urlencode($relativepath).'">'.$outputfilezip.'</a></strong>';
				print ' ('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfilezip), 'dayhour').')';
			}
			print '</strong><br>';

			print '<br>';

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="generatepackage">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="generatepackage">';
			print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
			print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
			print '<input type="submit" class="button" name="generatepackage" value="'.$langs->trans("BuildPackage").'">';
			print '</form>';
		}

		if ($tab != 'description')
		{
			print dol_get_fiche_end();
		}
	}
}

print dol_get_fiche_end(); // End modules

// End of page
llxFooter();
$db->close();
