<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2023	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2015		Raphaël Doursenaud		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018		Nicolas ZABOURI 		<info@inovea-conseil.com>
 * Copyright (C) 2021-2024  Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/admin/modules.php
 *  \brief      Page to activate/disable all modules
 */

if (!defined('CSRFCHECK_WITH_TOKEN') && (empty($_GET['action']) || $_GET['action'] != 'reset')) {	// We force security except to disable modules so we can do it if a problem occurs on a module
	define('CSRFCHECK_WITH_TOKEN', '1'); // Force use of CSRF protection with tokens even for GET
}

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/admin/dolistore/class/dolistore.class.php';

'
@phan-var-force string $dolibarr_main_url_root_alt
';

// Load translation files required by the page
$langs->loadLangs(array("errors", "admin", "modulebuilder"));

// if we set another view list mode, we keep it (till we change one more time)
if (GETPOSTISSET('mode')) {
	$mode = GETPOST('mode', 'alpha');
	if ($mode == 'common' || $mode == 'commonkanban') {
		dolibarr_set_const($db, "MAIN_MODULE_SETUP_ON_LIST_BY_DEFAULT", $mode, 'chaine', 0, '', $conf->entity);
	}
} else {
	$mode = (!getDolGlobalString('MAIN_MODULE_SETUP_ON_LIST_BY_DEFAULT') ? 'commonkanban' : $conf->global->MAIN_MODULE_SETUP_ON_LIST_BY_DEFAULT);
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$page_y = GETPOSTINT('page_y');
$search_keyword = GETPOST('search_keyword', 'alpha');
$search_status = GETPOST('search_status', 'alpha');
$search_nature = GETPOST('search_nature', 'alpha');
$search_version = GETPOST('search_version', 'alpha');


// For dolistore search
$options              = array();
$options['per_page']  = 20;
$options['categorie'] = ((int) (GETPOSTINT('categorie') ? GETPOSTINT('categorie') : 0));
$options['start']     = ((int) (GETPOSTINT('start') ? GETPOSTINT('start') : 0));
$options['end']       = ((int) (GETPOSTINT('end') ? GETPOSTINT('end') : 0));
$options['search']    = GETPOST('search_keyword', 'alpha');
$dolistore            = new Dolistore(false);


if (!$user->admin) {
	accessforbidden();
}

$familyinfo = array(
	'hr' => array('position' => '001', 'label' => $langs->trans("ModuleFamilyHr")),
	'crm' => array('position' => '006', 'label' => $langs->trans("ModuleFamilyCrm")),
	'srm' => array('position' => '007', 'label' => $langs->trans("ModuleFamilySrm")),
	'financial' => array('position' => '009', 'label' => $langs->trans("ModuleFamilyFinancial")),
	'products' => array('position' => '012', 'label' => $langs->trans("ModuleFamilyProducts")),
	'projects' => array('position' => '015', 'label' => $langs->trans("ModuleFamilyProjects")),
	'ecm' => array('position' => '018', 'label' => $langs->trans("ModuleFamilyECM")),
	'technic' => array('position' => '021', 'label' => $langs->trans("ModuleFamilyTechnic")),
	'portal' => array('position' => '040', 'label' => $langs->trans("ModuleFamilyPortal")),
	'interface' => array('position' => '050', 'label' => $langs->trans("ModuleFamilyInterface")),
	'base' => array('position' => '060', 'label' => $langs->trans("ModuleFamilyBase")),
	'other' => array('position' => '100', 'label' => $langs->trans("ModuleFamilyOther")),
);

$param = '';
if (!GETPOST('buttonreset', 'alpha')) {
	if ($search_keyword) {
		$param .= '&search_keyword='.urlencode($search_keyword);
	}
	if ($search_status && $search_status != '-1') {
		$param .= '&search_status='.urlencode($search_status);
	}
	if ($search_nature && $search_nature != '-1') {
		$param .= '&search_nature='.urlencode($search_nature);
	}
	if ($search_version && $search_version != '-1') {
		$param .= '&search_version='.urlencode($search_version);
	}
}

$dirins = DOL_DOCUMENT_ROOT.'/custom';
$urldolibarrmodules = 'https://www.dolistore.com/';

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('adminmodules', 'globaladmin'));

// Increase limit of time. Works only if we are not in safe mode
$max_execution_time_for_deploy = getDolGlobalInt('MODULE_UPLOAD_MAX_EXECUTION_TIME', 300); // 5mn if not defined
if (!empty($max_execution_time_for_deploy)) {
	$err = error_reporting();
	error_reporting(0); // Disable all errors
	//error_reporting(E_ALL);
	@set_time_limit($max_execution_time_for_deploy);
	error_reporting($err);
}
// Other method - TODO is this required ?
$max_time = @ini_get("max_execution_time");
if ($max_time && $max_time < $max_execution_time_for_deploy) {
	dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_deploy=".$max_execution_time_for_deploy.". We try to increase it dynamically.");
	@ini_set("max_execution_time", $max_execution_time_for_deploy); // This work only if safe mode is off. also web servers has timeout of 300
}


$dolibarrdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);
$allowonlineinstall = true;
$allowfromweb = 1;
if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) {
	$allowonlineinstall = false;
}


/*
 * Actions
 */

$formconfirm = '';

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (GETPOST('buttonreset', 'alpha')) {
	$search_keyword = '';
	$search_status = '';
	$search_nature = '';
	$search_version = '';
}

if ($action == 'install' && $allowonlineinstall) {
	$error = 0;

	// $original_file should match format module_modulename-x.y[.z].zip
	$original_file = basename($_FILES["fileinstall"]["name"]);
	$original_file = preg_replace('/\s*\(\d+\)\.zip$/i', '.zip', $original_file);
	$newfile = $conf->admin->dir_temp.'/'.$original_file.'/'.$original_file;

	if (!$original_file) {
		$langs->load("Error");
		setEventMessages($langs->trans("ErrorModuleFileRequired"), null, 'warnings');
		$error++;
	} else {
		if (!$error && !preg_match('/\.zip$/i', $original_file)) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFileMustBeADolibarrPackage", $original_file), null, 'errors');
			$error++;
		}
		if (!$error && !preg_match('/^(module[a-zA-Z0-9]*_|theme_|).*\-([0-9][0-9\.]*)(\s\(\d+\)\s)?\.zip$/i', $original_file)) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFilenameDosNotMatchDolibarrPackageRules", $original_file, 'modulename-x[.y.z].zip'), null, 'errors');
			$error++;
		}
		if (empty($_FILES['fileinstall']['tmp_name'])) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
			$error++;
		}
	}

	if (!$error) {
		if ($original_file) {
			@dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$original_file);
			dol_mkdir($conf->admin->dir_temp.'/'.$original_file);
		}

		$tmpdir = preg_replace('/\.zip$/i', '', $original_file).'.dir';
		if ($tmpdir) {
			@dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$tmpdir);
			dol_mkdir($conf->admin->dir_temp.'/'.$tmpdir);
		}

		$result = dol_move_uploaded_file($_FILES['fileinstall']['tmp_name'], $newfile, 1, 0, $_FILES['fileinstall']['error']);
		if ($result > 0) {
			$result = dol_uncompress($newfile, $conf->admin->dir_temp.'/'.$tmpdir);

			if (!empty($result['error'])) {
				$langs->load("errors");
				setEventMessages($langs->trans($result['error'], $original_file), null, 'errors');
				$error++;
			} else {
				// Now we move the dir of the module
				$modulename = preg_replace('/module_/', '', $original_file);
				$modulename = preg_replace('/\-([0-9][0-9\.]*)\.zip$/i', '', $modulename);
				// Search dir $modulename
				$modulenamedir = $conf->admin->dir_temp.'/'.$tmpdir.'/'.$modulename; // Example ./mymodule

				if (!dol_is_dir($modulenamedir)) {
					$modulenamedir = $conf->admin->dir_temp.'/'.$tmpdir.'/htdocs/'.$modulename; // Example ./htdocs/mymodule
					//var_dump($modulenamedir);
					if (!dol_is_dir($modulenamedir)) {
						setEventMessages($langs->trans("ErrorModuleFileSeemsToHaveAWrongFormat").'<br>'.$langs->trans("ErrorModuleFileSeemsToHaveAWrongFormat2", $modulename, 'htdocs/'.$modulename), null, 'errors');
						$error++;
					}
				}

				if (!$error) {
					// TODO Make more test
				}

				dol_syslog("Uncompress of module file is a success.");

				// We check if this is a metapackage
				$modulenamearrays = array();
				if (dol_is_file($modulenamedir.'/metapackage.conf')) {
					// This is a meta package
					$metafile = file_get_contents($modulenamedir.'/metapackage.conf');
					$modulenamearrays = explode("\n", $metafile);
				}
				$modulenamearrays[$modulename] = $modulename;
				//var_dump($modulenamearrays);exit;

				// Lop on each package of the metapackage
				foreach ($modulenamearrays as $modulenameval) {
					if (strpos($modulenameval, '#') === 0) {
						continue; // Discard comments
					}
					if (strpos($modulenameval, '//') === 0) {
						continue; // Discard comments
					}
					if (!trim($modulenameval)) {
						continue;
					}

					// Now we install the module
					if (!$error) {
						@dol_delete_dir_recursive($dirins.'/'.$modulenameval); // delete the target directory
						$submodulenamedir = $conf->admin->dir_temp.'/'.$tmpdir.'/'.$modulenameval;
						if (!dol_is_dir($submodulenamedir)) {
							$submodulenamedir = $conf->admin->dir_temp.'/'.$tmpdir.'/htdocs/'.$modulenameval;
						}
						dol_syslog("We copy now directory ".$submodulenamedir." into target dir ".$dirins.'/'.$modulenameval);
						$result = dolCopyDir($submodulenamedir, $dirins.'/'.$modulenameval, '0444', 1);
						if ($result <= 0) {
							dol_syslog('Failed to call dolCopyDir result='.$result." with param ".$submodulenamedir." and ".$dirins.'/'.$modulenameval, LOG_WARNING);
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorFailToCopyDir", $submodulenamedir, $dirins.'/'.$modulenameval), null, 'errors');
							$error++;
						}
					}
				}
			}
		} else {
			setEventMessages($langs->trans("ErrorFailToRenameFile", $_FILES['fileinstall']['tmp_name'], $newfile), null, 'errors');
			$error++;
		}
	}

	if (!$error) {
		$message = $langs->trans("SetupIsReadyForUse", DOL_URL_ROOT.'/admin/modules.php?mainmenu=home', $langs->transnoentitiesnoconv("Home").' - '.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Modules"));
		setEventMessages($message, null, 'warnings');
	}
} elseif ($action == 'install' && !$allowonlineinstall) {
	httponly_accessforbidden("You try to bypass the protection to disallow deployment of an external module. Hack attempt ?");
}

if ($action == 'set' && $user->admin) {
	// We made some check against evil eternal modules that try to low security options.
	$checkOldValue = getDolGlobalInt('CHECKLASTVERSION_EXTERNALMODULE');
	$csrfCheckOldValue = getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN');
	$resarray = activateModule($value);
	if ($checkOldValue != getDolGlobalInt('CHECKLASTVERSION_EXTERNALMODULE')) {
		setEventMessage($langs->trans('WarningModuleHasChangedLastVersionCheckParameter', $value), 'warnings');
	}
	if ($csrfCheckOldValue != getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN')) {
		setEventMessage($langs->trans('WarningModuleHasChangedSecurityCsrfParameter', $value), 'warnings');
	}

	dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
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
	header("Location: ".$_SERVER["PHP_SELF"]."?mode=".$mode.$param.($page_y ? '&page_y='.$page_y : ''));
	exit;
} elseif ($action == 'reset' && $user->admin && GETPOST('confirm') == 'yes') {
	$result = unActivateModule($value);
	dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
	if ($result) {
		setEventMessages($result, null, 'errors');
	}
	header("Location: ".$_SERVER["PHP_SELF"]."?mode=".$mode.$param.($page_y ? '&page_y='.$page_y : ''));
	exit;
} elseif (getDolGlobalInt("MAIN_FEATURES_LEVEL") > 1 && $action == 'reload' && $user->admin && GETPOST('confirm') == 'yes') {
	$result = unActivateModule($value, 0);
	dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
	if ($result) {
		setEventMessages($result, null, 'errors');
		header("Location: ".$_SERVER["PHP_SELF"]."?mode=".$mode.$param.($page_y ? '&page_y='.$page_y : ''));
	}
	$resarray = activateModule($value, 0, 1);
	dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", (getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1), 'chaine', 0, '', $conf->entity);
	if (!empty($resarray['errors'])) {
		setEventMessages('', $resarray['errors'], 'errors');
	} else {
		if ($resarray['nbperms'] > 0) {
			$tmpsql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."user WHERE admin <> 1";
			$resqltmp = $db->query($tmpsql);
			if ($resqltmp) {
				$obj = $db->fetch_object($resqltmp);
				if ($obj && $obj->nb > 1) {
					$msg = $langs->trans('ModuleEnabledAdminMustCheckRights');
					setEventMessages($msg, null, 'warnings');
				}
			} else {
				dol_print_error($db);
			}
		}
	}
	header("Location: ".$_SERVER["PHP_SELF"]."?mode=".$mode.$param.($page_y ? '&page_y='.$page_y : ''));
	exit;
}




/*
 * View
 */

$form = new Form($db);

$morejs = array();
$morecss = array("/admin/dolistore/css/dolistore.css");

// Set dir where external modules are installed
if (!dol_is_dir($dirins)) {
	dol_mkdir($dirins);
}
$dirins_ok = (dol_is_dir($dirins));

$help_url = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $help_url, '', 0, 0, $morejs, $morecss, '', 'mod-admin page-modules');


// Search modules dirs
$modulesdir = dolGetModulesDirs();

$arrayofnatures = array(
	'core' => array('label' => $langs->transnoentitiesnoconv("NativeModules")),
	'external' => array('label' => $langs->transnoentitiesnoconv("External").' - ['.$langs->trans("AllPublishers").']')
);
$arrayofwarnings = array(); // Array of warning each module want to show when activated
$arrayofwarningsext = array(); // Array of warning each module want to show when we activate an external module
$filename = array();
$modules = array();
$orders = array();
$categ = array();
$publisherlogoarray = array();

$i = 0; // is a sequencer of modules found
$j = 0; // j is module number. Automatically affected if module number not defined.
$modNameLoaded = array();

foreach ($modulesdir as $dir) {
	// Load modules attributes in arrays (name, numero, orders) from dir directory
	//print $dir."\n<br>";
	dol_syslog("Scan directory ".$dir." for module descriptor files (modXXX.class.php)");
	$handle = @opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			//print "$i ".$file."\n<br>";
			if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
				$modName = substr($file, 0, dol_strlen($file) - 10);

				if ($modName) {
					if (!empty($modNameLoaded[$modName])) {   // In cache of already loaded modules ?
						$mesg = "Error: Module ".$modName." was found twice: Into ".$modNameLoaded[$modName]." and ".$dir.". You probably have an old file on your disk.<br>";
						setEventMessages($mesg, null, 'warnings');
						dol_syslog($mesg, LOG_ERR);
						continue;
					}

					try {
						$res = include_once $dir.$file; // A class already exists in a different file will send a non catchable fatal error.
						if (class_exists($modName)) {
							$objMod = new $modName($db);
							'@phan-var-force DolibarrModules $objMod';
							$modNameLoaded[$modName] = $dir;
							if (!$objMod->numero > 0 && $modName != 'modUser') {
								dol_syslog('The module descriptor '.$modName.' must have a numero property', LOG_ERR);
							}
							$j = $objMod->numero;

							$modulequalified = 1;

							// We discard modules according to features level (PS: if module is activated we always show it)
							$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));
							if ($objMod->version == 'development' && (!getDolGlobalString($const_name) && (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2))) {
								$modulequalified = 0;
							}
							if ($objMod->version == 'experimental' && (!getDolGlobalString($const_name) && (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1))) {
								$modulequalified = 0;
							}
							if (preg_match('/deprecated/', $objMod->version) && (!getDolGlobalString($const_name) && (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 0))) {
								$modulequalified = 0;
							}

							// We discard modules according to property ->hidden
							if (!empty($objMod->hidden)) {
								$modulequalified = 0;
							}

							if ($modulequalified > 0) {
								$publisher = dol_escape_htmltag($objMod->getPublisher());
								$external = ($objMod->isCoreOrExternalModule() == 'external');
								if ($external) {
									if ($publisher) {
										// Check if there is a logo forpublisher
										/* Do not show the company logo in combo. Make combo list dirty.
										if (!empty($objMod->editor_squarred_logo)) {
											$publisherlogoarray['external_'.$publisher] = img_picto('', $objMod->editor_squarred_logo, 'class="publisherlogoinline"');
										}
										$publisherlogo = empty($publisherlogoarray['external_'.$publisher]) ? '' : $publisherlogoarray['external_'.$publisher];
										*/
										$arrayofnatures['external_'.$publisher] = array('label' => $langs->trans("External").' - '.$publisher, 'data-html' => $langs->trans("External").' - <span class="opacitymedium inine-block valignmiddle">'.$publisher.'</span>');
									} else {
										$arrayofnatures['external_'] = array('label' => $langs->trans("External").' - ['.$langs->trans("UnknownPublishers").']');
									}
								}
								ksort($arrayofnatures);

								// Define an array $categ with categ with at least one qualified module
								$filename[$i] = $modName;
								$modules[$modName] = $objMod;

								// Gives the possibility to the module, to provide his own family info and position of this family
								if (is_array($objMod->familyinfo) && !empty($objMod->familyinfo)) {
									$familyinfo = array_merge($familyinfo, $objMod->familyinfo);
									$familykey = key($objMod->familyinfo);
								} else {
									$familykey = $objMod->family;
								}
								'@phan-var-force string $familykey';  // if not, phan considers $familykey may be null

								$moduleposition = ($objMod->module_position ? $objMod->module_position : '50');
								if ($objMod->isCoreOrExternalModule() == 'external' && $moduleposition < 100000) {
									// an external module should never return a value lower than '80'.
									$moduleposition = '80'; // External modules at end by default
								}

								// Add list of warnings to show into arrayofwarnings and arrayofwarningsext
								if (!empty($objMod->warnings_activation)) {
									$arrayofwarnings[$modName] = $objMod->warnings_activation;
								}
								if (!empty($objMod->warnings_activation_ext)) {
									$arrayofwarningsext[$modName] = $objMod->warnings_activation_ext;
								}

								$familyposition = (empty($familyinfo[$familykey]['position']) ? '0' : $familyinfo[$familykey]['position']);
								$listOfOfficialModuleGroups = array('hr', 'technic', 'interface', 'technic', 'portal', 'financial', 'crm', 'base', 'products', 'srm', 'ecm', 'projects', 'other');
								if ($external && !in_array($familykey, $listOfOfficialModuleGroups)) {
									// If module is extern and into a custom group (not into an official predefined one), it must appear at end (custom groups should not be before official groups).
									if (is_numeric($familyposition)) {
										$familyposition = sprintf("%03d", (int) $familyposition + 100);
									}
								}

								$orders[$i] = $familyposition."_".$familykey."_".$moduleposition."_".$j; // Sort by family, then by module position then number

								// Set categ[$i]
								$specialstring = 'unknown';
								if ($objMod->version == 'development' || $objMod->version == 'experimental') {
									$specialstring = 'expdev';
								}
								if (isset($categ[$specialstring])) {
									$categ[$specialstring]++; // Array of all different modules categories
								} else {
									$categ[$specialstring] = 1;
								}
								$j++;
								$i++;
							} else {
								dol_syslog("Module ".get_class($objMod)." not qualified");
							}
						} else {
							print info_admin("admin/modules.php Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)", 0, 0, '1', 'warning');
						}
					} catch (Exception $e) {
						dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
					}
				}
			}
		}
		closedir($handle);
	} else {
		dol_syslog("htdocs/admin/modules.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
	}
}

'@phan-var-force array<string,DolibarrModules> $modules';

if ($action == 'reset_confirm' && $user->admin) {
	if (!empty($modules[$value])) {
		$objMod = $modules[$value];

		if (!empty($objMod->langfiles)) {
			$langs->loadLangs($objMod->langfiles);
		}

		$form = new Form($db);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?value='.$value.'&mode='.$mode.$param, $langs->trans('ConfirmUnactivation'), $langs->trans(GETPOST('confirm_message_code')), 'reset', '', 'no', 1);
	}
}

if ($action == 'reload_confirm' && $user->admin) {
	if (!empty($modules[$value])) {
		$objMod = $modules[$value];

		if (!empty($objMod->langfiles)) {
			$langs->loadLangs($objMod->langfiles);
		}

		$form = new Form($db);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?value='.$value.'&mode='.$mode.$param, $langs->trans('ConfirmReload'), $langs->trans(GETPOST('confirm_message_code')), 'reload', '', 'no', 1);
	}
}

print $formconfirm;

asort($orders);
//var_dump($orders);
//var_dump($categ);
//var_dump($modules);

$nbofactivatedmodules = count($conf->modules);

// Define $nbmodulesnotautoenabled - TODO This code is at different places
$nbmodulesnotautoenabled = count($conf->modules);
$listofmodulesautoenabled = array('agenda', 'fckeditor', 'export', 'import');
foreach ($listofmodulesautoenabled as $moduleautoenable) {
	if (in_array($moduleautoenable, $conf->modules)) {
		$nbmodulesnotautoenabled--;
	}
}

print load_fiche_titre($langs->trans("ModulesSetup"), '', 'title_setup');

// Start to show page
$deschelp  = '';
if ($mode == 'common' || $mode == 'commonkanban') {
	$desc = $langs->trans("ModulesDesc", '{picto}');
	$desc .= ' '.$langs->trans("ModulesDesc2", '{picto2}');
	$desc = str_replace('{picto}', img_picto('', 'switch_off', 'class="size15x"'), $desc);
	$desc = str_replace('{picto2}', img_picto('', 'setup', 'class="size15x"'), $desc);
	if ($nbmodulesnotautoenabled <= getDolGlobalInt('MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING', 1)) {	// If only minimal initial modules enabled
		$deschelp .= '<div class="info hideonsmartphone">'.$desc."<br></div>\n";
	}
	if (getDolGlobalString('MAIN_SETUP_MODULES_INFO')) {	// Show a custom message
		$deschelp .= '<div class="info">'.$langs->trans(getDolGlobalString('MAIN_SETUP_MODULES_INFO'))."<br></div>\n";
	}
	if ($deschelp) {
		$deschelp .= '<br>';
	}
}
//if ($mode == 'marketplace') {
//	$deschelp = '<div class="info hideonsmartphone">'.$langs->trans("ModulesMarketPlaceDesc")."<br></div><br>\n";
//}
if ($mode == 'deploy') {
	$deschelp = '<div class="info hideonsmartphone">'.$langs->trans("ModulesDeployDesc", $langs->transnoentitiesnoconv("AvailableModules"))."<br></div><br>\n";
}
if ($mode == 'develop') {
	$deschelp = '<div class="info hideonsmartphone">'.$langs->trans("ModulesDevelopDesc")."<br></div><br>\n";
}

$head = modules_prepare_head($nbofactivatedmodules, count($modules), $nbmodulesnotautoenabled);


if ($mode == 'common' || $mode == 'commonkanban') {
	dol_set_focus('#search_keyword');

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	if (isset($optioncss) && $optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	if (isset($sortfield) && $sortfield != '') {
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	}
	if (isset($sortorder) && $sortorder != '') {
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	}
	if (isset($page) && $page != '') {
		print '<input type="hidden" name="page" value="'.$page.'">';
	}
	print '<input type="hidden" name="mode" value="'.$mode.'">';

	print dol_get_fiche_head($head, 'modules', '', -1);

	print $deschelp;

	$moreforfilter = '<div class="valignmiddle">';

	$moreforfilter .= '<div class="floatright right pagination paddingtop --module-list"><ul><li>';
	$moreforfilter .= dolGetButtonTitle($langs->trans('CheckForModuleUpdate'), $langs->trans('CheckForModuleUpdate').'<br>'.$langs->trans('CheckForModuleUpdateHelp'), 'fa fa-sync', $_SERVER["PHP_SELF"].'?action=checklastversion&token='.newToken().'&mode='.$mode.$param, '', 1, array('morecss' => 'reposition'));
	$moreforfilter .= dolGetButtonTitleSeparator();
	$moreforfilter .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.$param, '', ($mode == 'common' ? 2 : 1), array('morecss' => 'reposition'));
	$moreforfilter .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=commonkanban'.$param, '', ($mode == 'commonkanban' ? 2 : 1), array('morecss' => 'reposition'));
	$moreforfilter .= '</li></ul></div>';

	$moreforfilter .= '<div class="divfilteralone colorbacktimesheet float valignmiddle">';
	$moreforfilter .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block">';
	$moreforfilter .= img_picto($langs->trans("Filter"), 'filter', 'class="paddingright opacityhigh hideonsmartphone"').'<input type="text" id="search_keyword" name="search_keyword" class="maxwidth125" value="'.dol_escape_htmltag($search_keyword).'" placeholder="'.dol_escape_htmltag($langs->trans('Keyword')).'">';
	$moreforfilter .= '</div>';
	$moreforfilter .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block">';
	$moreforfilter .= $form->selectarray('search_nature', $arrayofnatures, dol_escape_htmltag($search_nature), $langs->trans('Origin'), 0, 0, '', 0, 0, 0, '', 'maxwidth250', 1);
	$moreforfilter .= '</div>';

	if (getDolGlobalInt('MAIN_FEATURES_LEVEL')) {
		$array_version = array('stable' => $langs->transnoentitiesnoconv("Stable"));
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 0) {
			$array_version['deprecated'] = $langs->trans("Deprecated");
		}
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') > 0) {
			$array_version['experimental'] = $langs->trans("Experimental");
		}
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') > 1) {
			$array_version['development'] = $langs->trans("Development");
		}
		$moreforfilter .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block">';
		$moreforfilter .= $form->selectarray('search_version', $array_version, $search_version, $langs->transnoentitiesnoconv('Version'), 0, 0, '', 0, 0, 0, '', 'maxwidth150', 1);
		$moreforfilter .= '</div>';
	}
	$array_status = array('active' => $langs->transnoentitiesnoconv("Enabled"), 'disabled' => $langs->transnoentitiesnoconv("Disabled"));
	$moreforfilter .= '<div class="divsearchfield paddingtop paddingbottom valignmiddle inline-block">';
	$moreforfilter .= $form->selectarray('search_status', $array_status, $search_status, $langs->transnoentitiesnoconv('Status'), 0, 0, '', 0, 0, 0, '', 'maxwidth150', 1);
	$moreforfilter .= '</div>';
	$moreforfilter .= ' ';
	$moreforfilter .= '<div class="divsearchfield valignmiddle inline-block">';
	$moreforfilter .= '<input type="submit" name="buttonsubmit" class="button small nomarginleft" value="'.dol_escape_htmltag($langs->trans("Refresh")).'">';
	if ($search_keyword || ($search_nature && $search_nature != '-1') || ($search_version && $search_version != '-1') || ($search_status && $search_status != '-1')) {
		$moreforfilter .= ' ';
		$moreforfilter .= '<input type="submit" name="buttonreset" class="buttonreset noborderbottom" value="'.dol_escape_htmltag($langs->trans("Reset")).'">';
	}
	$moreforfilter .= '</div>';
	$moreforfilter .= '</div>';

	$moreforfilter .= '</div>';

	if (!empty($moreforfilter)) {
		print $moreforfilter;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
	}

	$moreforfilter = '';

	print '<div class="clearboth"></div><br>';

	$object = new stdClass();
	$parameters = array();
	$reshook = $hookmanager->executeHooks('insertExtraHeader', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	$disabled_modules = array();
	if (!empty($_SESSION["disablemodules"])) {
		$disabled_modules = explode(',', $_SESSION["disablemodules"]);
	}

	// Show list of modules
	$oldfamily = '';
	$foundoneexternalmodulewithupdate = 0;
	$linenum = 0;
	$atleastonequalified = 0;
	$atleastoneforfamily = 0;

	foreach ($orders as $key => $value) {
		$linenum++;
		$tab = explode('_', $value);
		$familykey = $tab[1];
		$module_position = $tab[2];

		$modName = $filename[$key];

		/** @var DolibarrModules $objMod */
		$objMod = $modules[$modName];

		if (!is_object($objMod)) {
			continue;
		}

		//print $objMod->name." - ".$key." - ".$objMod->version."<br>";
		if ($mode == 'expdev' && $objMod->version != 'development' && $objMod->version != 'experimental') {
			continue; // Discard if not for current tab
		}

		if (!$objMod->getName()) {
			dol_syslog("Error for module ".$key." - Property name of module looks empty", LOG_WARNING);
			continue;
		}

		$modulenameshort = strtolower(preg_replace('/^mod/i', '', get_class($objMod)));
		$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));

		// Check filters
		$modulename = $objMod->getName();
		$moduletechnicalname = $objMod->name;
		$moduledesc = $objMod->getDesc();
		$moduledesclong = $objMod->getDescLong();
		$moduleauthor = $objMod->getPublisher();

		// We discard showing according to filters
		if ($search_keyword) {
			$qualified = 0;
			if (preg_match('/'.preg_quote($search_keyword, '/').'/i', $modulename)
				|| preg_match('/'.preg_quote($search_keyword, '/').'/i', $moduletechnicalname)
				|| ($moduledesc && preg_match('/'.preg_quote($search_keyword, '/').'/i', $moduledesc))
				|| ($moduledesclong && preg_match('/'.preg_quote($search_keyword, '/').'/i', $moduledesclong))
				|| ($moduleauthor && preg_match('/'.preg_quote($search_keyword, '/').'/i', $moduleauthor))
			) {
				$qualified = 1;
			}
			if (!$qualified) {
				continue;
			}
		}
		if ($search_status) {
			if ($search_status == 'active' && !getDolGlobalString($const_name)) {
				continue;
			}
			if ($search_status == 'disabled' && getDolGlobalString($const_name)) {
				continue;
			}
		}
		if ($search_nature) {
			if (preg_match('/^external/', $search_nature) && $objMod->isCoreOrExternalModule() != 'external') {
				continue;
			}
			$reg = array();
			if (preg_match('/^external_(.*)$/', $search_nature, $reg)) {
				//print $reg[1].'-'.dol_escape_htmltag($objMod->getPublisher());
				$publisher = dol_escape_htmltag($objMod->getPublisher());
				if ($reg[1] && dol_escape_htmltag($reg[1]) != $publisher) {
					continue;
				}
				if (!$reg[1] && !empty($publisher)) {
					continue;
				}
			}
			if ($search_nature == 'core' && $objMod->isCoreOrExternalModule() == 'external') {
				continue;
			}
		}
		if ($search_version) {
			if (($objMod->version == 'development' || $objMod->version == 'experimental' || preg_match('/deprecated/', $objMod->version)) && $search_version == 'stable') {
				continue;
			}
			if ($objMod->version != 'development' && ($search_version == 'development')) {
				continue;
			}
			if ($objMod->version != 'experimental' && ($search_version == 'experimental')) {
				continue;
			}
			if (!preg_match('/deprecated/', $objMod->version) && ($search_version == 'deprecated')) {
				continue;
			}
		}

		$atleastonequalified++;

		// Load all language files of the qualified module
		if (isset($objMod->langfiles) && is_array($objMod->langfiles)) {
			foreach ($objMod->langfiles as $domain) {
				$langs->load($domain);
			}
		}

		// Print a separator if we change family
		if ($familykey != $oldfamily) {
			if ($oldfamily) {
				print '</table></div><br>';
			}

			$familytext = empty($familyinfo[$familykey]['label']) ? $familykey : $familyinfo[$familykey]['label'];

			print load_fiche_titre($familytext, '', '', 0, '', 'modulefamilygroup');

			if ($mode == 'commonkanban') {
				print '<div class="box-flex-container kanban">';
			} else {
				print '<div class="div-table-responsive">';
				print '<table class="tagtable liste" summary="list_of_modules">'."\n";
			}

			$atleastoneforfamily = 0;
		}

		$atleastoneforfamily++;

		if ($familykey != $oldfamily) {
			$familytext = empty($familyinfo[$familykey]['label']) ? $familykey : $familyinfo[$familykey]['label'];
			$oldfamily = $familykey;
		}

		// Version (with picto warning or not)
		$version = $objMod->getVersion(0);
		$versiontrans = '';
		$warningstring = '';
		if (preg_match('/development/i', $version)) {
			$warningstring = $langs->trans("Development");
		}
		if (preg_match('/experimental/i', $version)) {
			$warningstring = $langs->trans("Experimental");
		}
		if (preg_match('/deprecated/i', $version)) {
			$warningstring = $langs->trans("Deprecated");
		}

		if ($objMod->isCoreOrExternalModule() == 'external' || preg_match('/development|experimental|deprecated/i', $version)) {
			$versiontrans .= $objMod->getVersion(1);
		}

		if ($objMod->isCoreOrExternalModule() == 'external' && ($action == 'checklastversion' || getDolGlobalString('CHECKLASTVERSION_EXTERNALMODULE'))) {
			// Setting CHECKLASTVERSION_EXTERNALMODULE to on is a bad practice to activate a check on an external access during the building of the admin page.
			// 1 external module can hang the application.
			// Adding a cron job could be a good idea: see DolibarrModules::checkForUpdate()
			$checkRes = $objMod->checkForUpdate();
			if ($checkRes > 0) {
				setEventMessages($objMod->getName().' : '.preg_replace('/[^a-z0-9_\.\-\s]/i', '', $versiontrans).' -> '.preg_replace('/[^a-z0-9_\.\-\s]/i', '', $objMod->lastVersion), null, 'warnings');
			} elseif ($checkRes < 0) {
				setEventMessages($objMod->getName().' '.$langs->trans('CheckVersionFail'), null, 'errors');
			}
		}

		// Define imginfo
		$imginfo = "info";
		if ($objMod->isCoreOrExternalModule() == 'external') {
			$imginfo = "info_black";
		}

		$codeenabledisable = '';
		$codetoconfig = '';

		// Force disable of module disabled into session (for demo for example)
		if (in_array($modulenameshort, $disabled_modules)) {
			$objMod->disabled = true;
		}

		// Activate/Disable and Setup (2 columns)
		if (getDolGlobalString($const_name)) {	// If module is already activated
			// Set $codeenabledisable
			$disableSetup = 0;
			if (!empty($arrayofwarnings[$modName])) {
				$codeenabledisable .= '<!-- This module has a warning to show when we activate it (note: your country is '.$mysoc->country_code.') -->'."\n";
			}

			if (!empty($objMod->disabled)) {
				$codeenabledisable .= $langs->trans("Disabled");
			} elseif (is_object($objMod)
				&& (!empty($objMod->always_enabled) || ((isModEnabled('multicompany') && $objMod->core_enabled) && ($user->entity || $conf->entity != 1)))) {
				// @phan-suppress-next-line PhanUndeclaredMethod
				if (method_exists($objMod, 'alreadyUsed') && $objMod->alreadyUsed()) {
					$codeenabledisable .= $langs->trans("Used");
				} else {
					$codeenabledisable .= img_picto($langs->trans("Required"), 'switch_on', '', 0, 0, 0, '', 'opacitymedium valignmiddle');
					//print $langs->trans("Required");
				}
				if (isModEnabled('multicompany') && $user->entity) {
					$disableSetup++;
				}
			} else {
				// @phan-suppress-next-line PhanUndeclaredMethod
				if (is_object($objMod) && !empty($objMod->warnings_unactivation[$mysoc->country_code]) && method_exists($objMod, 'alreadyUsed') && $objMod->alreadyUsed()) {
					$codeenabledisable .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&amp;token='.newToken().'&amp;module_position='.$module_position.'&amp;action=reset_confirm&amp;confirm_message_code='.urlencode($objMod->warnings_unactivation[$mysoc->country_code]).'&amp;value='.$modName.'&amp;mode='.$mode.$param.'">';
					$codeenabledisable .= img_picto($langs->trans("Activated").($warningstring ? ' '.$warningstring : ''), 'switch_on');
					$codeenabledisable .= '</a>';
					if (getDolGlobalInt("MAIN_FEATURES_LEVEL") > 1) {
						$codeenabledisable .= '&nbsp;';
						$codeenabledisable .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&amp;token='.newToken().'&amp;module_position='.$module_position.'&amp;action=reload_confirm&amp;value='.$modName.'&amp;mode='.$mode.'&amp;confirm=yes'.$param.'">';
						$codeenabledisable .= img_picto($langs->trans("Reload"), 'refresh', 'class="opacitymedium"');
						$codeenabledisable .= '</a>';
					}
				} else {
					$codeenabledisable .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&amp;token='.newToken().'&amp;module_position='.$module_position.'&amp;action=reset&amp;value='.$modName.'&amp;mode='.$mode.'&amp;confirm=yes'.$param.'">';
					$codeenabledisable .= img_picto($langs->trans("Activated").($warningstring ? ' '.$warningstring : ''), 'switch_on');
					$codeenabledisable .= '</a>';
					if (getDolGlobalInt("MAIN_FEATURES_LEVEL") > 1) {
						$codeenabledisable .= '&nbsp;';
						$codeenabledisable .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&amp;token='.newToken().'&amp;module_position='.$module_position.'&amp;action=reload&amp;value='.$modName.'&amp;mode='.$mode.'&amp;confirm=yes'.$param.'">';
						$codeenabledisable .= img_picto($langs->trans("Reload"), 'refresh', 'class="opacitymedium"');
						$codeenabledisable .= '</a>';
					}
				}
			}

			// Set $codetoconfig
			if (!empty($objMod->config_page_url) && !$disableSetup) {
				$backtourlparam = '';
				if ($search_keyword != '') {
					$backtourlparam .= ($backtourlparam ? '&' : '?').'search_keyword='.urlencode($search_keyword); // No urlencode here, done later
				}
				if ($search_nature > -1) {
					$backtourlparam .= ($backtourlparam ? '&' : '?').'search_nature='.urlencode($search_nature); // No urlencode here, done later
				}
				if ($search_version > -1) {
					$backtourlparam .= ($backtourlparam ? '&' : '?').'search_version='.urlencode($search_version); // No urlencode here, done later
				}
				if ($search_status > -1) {
					$backtourlparam .= ($backtourlparam ? '&' : '?').'search_status='.urlencode($search_status); // No urlencode here, done later
				}
				$backtourl = $_SERVER["PHP_SELF"].$backtourlparam;

				$regs = array();
				if (is_array($objMod->config_page_url)) {
					$i = 0;
					foreach ($objMod->config_page_url as $page) {
						$urlpage = $page;
						if ($i++) {
							$codetoconfig .= '<a href="'.$urlpage.'" title="'.$langs->trans($page).'">'.img_picto(ucfirst($page), "setup").'</a>';
							//    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
						} else {
							if (preg_match('/^([^@]+)@([^@]+)$/i', $urlpage, $regs)) {
								$urltouse = dol_buildpath('/'.$regs[2].'/admin/'.$regs[1], 1);
								$codetoconfig .= '<a href="'.$urltouse.(preg_match('/\?/', $urltouse) ? '&' : '?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 6px"', 0, 0, 0, '', 'fa-15').'</a>';
							} else {
								$urltouse = $urlpage;
								$codetoconfig .= '<a href="'.$urltouse.(preg_match('/\?/', $urltouse) ? '&' : '?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 6px"', 0, 0, 0, '', 'fa-15').'</a>';
							}
						}
					}
				} elseif (preg_match('/^([^@]+)@([^@]+)$/i', (string) $objMod->config_page_url, $regs)) {
					$codetoconfig .= '<a class="valignmiddle" href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1], 1).'?save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 6px"', 0, 0, 0, '', 'fa-15').'</a>';
				} else {
					$codetoconfig .= '<a class="valignmiddle" href="'.((string) $objMod->config_page_url).'?save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"), "setup", 'style="padding-right: 6px"', 0, 0, 0, '', 'fa-15').'</a>';
				}
			} else {
				$codetoconfig .= img_picto($langs->trans("NothingToSetup"), "setup", 'class="opacitytransp" style="padding-right: 6px"', 0, 0, 0, '', 'fa-15');
			}
		} else { // Module not yet activated
			// Set $codeenabledisable
			if (!empty($objMod->always_enabled)) {
				// A 'always_enabled' module should not never be disabled. If this happen, we keep a link to re-enable it.
				$codeenabledisable .= '<!-- Message to show: an always_enabled module has been disabled -->'."\n";
				$codeenabledisable .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&token='.newToken().'&module_position='.$module_position.'&action=set&token='.newToken().'&value='.$modName.'&mode='.$mode.$param.'"';
				$codeenabledisable .= '>';
				$codeenabledisable .= img_picto($langs->trans("Disabled"), 'switch_off');
				$codeenabledisable .= "</a>\n";
			} elseif (!empty($objMod->disabled)) {
				$codeenabledisable .= $langs->trans("Disabled");
			} else {
				// Module qualified for activation
				$warningmessage = '';
				if (!empty($arrayofwarnings[$modName])) {
					$codeenabledisable .= '<!-- This module is a core module and it may have a warning to show when we activate it (note: your country is '.$mysoc->country_code.') -->'."\n";
					foreach ($arrayofwarnings[$modName] as $keycountry => $cursorwarningmessage) {
						if (preg_match('/^always/', $keycountry) || ($mysoc->country_code && preg_match('/^'.$mysoc->country_code.'/', $keycountry))) {
							$warningmessage .= ($warningmessage ? "\n" : "").$langs->trans($cursorwarningmessage, $objMod->getName(), $mysoc->country_code);
						}
					}
				}
				if ($objMod->isCoreOrExternalModule() == 'external' && !empty($arrayofwarningsext)) {
					$codeenabledisable .= '<!-- This module is an external module and it may have a warning to show (note: your country is '.$mysoc->country_code.') -->'."\n";
					foreach ($arrayofwarningsext as $keymodule => $arrayofwarningsextbycountry) {
						$keymodulelowercase = strtolower(preg_replace('/^mod/', '', $keymodule));
						if (in_array($keymodulelowercase, $conf->modules)) {    // If module that request warning is on
							foreach ($arrayofwarningsextbycountry as $keycountry => $cursorwarningmessage) {
								if (preg_match('/^always/', $keycountry) || ($mysoc->country_code && preg_match('/^'.$mysoc->country_code.'/', $keycountry))) {
									$warningmessage .= ($warningmessage ? "\n" : "").$langs->trans($cursorwarningmessage, $objMod->getName(), $mysoc->country_code, $modules[$keymodule]->getName());
									$warningmessage .= ($warningmessage ? "\n" : "").($warningmessage ? "\n" : "").$langs->trans("Module").' : '.$objMod->getName();
									if (!empty($objMod->editor_name)) {
										$warningmessage .= ($warningmessage ? "\n" : "").$langs->trans("Publisher").' : '.$objMod->editor_name;
									}
									if (!empty($objMod->editor_name)) {
										$warningmessage .= ($warningmessage ? "\n" : "").$langs->trans("ModuleTriggeringThisWarning").' : '.$modules[$keymodule]->getName();
									}
								}
							}
						}
					}
				}
				$codeenabledisable .= '<!-- Message to show: '.$warningmessage.' -->'."\n";
				$codeenabledisable .= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&token='.newToken().'&module_position='.$module_position.'&action=set&token='.newToken().'&value='.$modName.'&mode='.$mode.$param.'"';
				if ($warningmessage) {
					$codeenabledisable .= ' onclick="return confirm(\''.dol_escape_js($warningmessage).'\');"';
				}
				$codeenabledisable .= '>';
				$codeenabledisable .= img_picto($langs->trans("Disabled"), 'switch_off');
				$codeenabledisable .= "</a>\n";
			}

			// Set $codetoconfig
			$codetoconfig .= img_picto($langs->trans("NothingToSetup"), "setup", 'class="opacitytransp" style="padding-right: 6px"');
		}

		if ($mode == 'commonkanban') {
			// Output Kanban
			print $objMod->getKanbanView($codeenabledisable, $codetoconfig);
		} else {
			print '<tr class="oddeven'.($warningstring ? ' info-box-content-warning' : '').'">'."\n";
			if (getDolGlobalString('MAIN_MODULES_SHOW_LINENUMBERS')) {
				print '<td class="width50">'.$linenum.'</td>';
			}

			// Picto + Name of module
			print '  <td class="tdoverflowmax200 minwidth200imp" title="'.dol_escape_htmltag($objMod->getName()).'">';
			$alttext = '';
			//if (is_array($objMod->need_dolibarr_version)) $alttext.=($alttext?' - ':'').'Dolibarr >= '.join('.',$objMod->need_dolibarr_version);
			//if (is_array($objMod->phpmin)) $alttext.=($alttext?' - ':'').'PHP >= '.join('.',$objMod->phpmin);
			if (!empty($objMod->picto)) {
				if (preg_match('/^\//i', $objMod->picto)) {
					print img_picto($alttext, $objMod->picto, 'class="valignmiddle pictomodule paddingrightonly"', 1);
				} else {
					print img_object($alttext, $objMod->picto, 'class="valignmiddle pictomodule paddingrightonly"');
				}
			} else {
				print img_object($alttext, 'generic', 'class="valignmiddle paddingrightonly"');
			}
			print ' <span class="valignmiddle">'.$objMod->getName().'</span>';
			print "</td>\n";

			// Desc
			print '<td class="valignmiddle tdoverflowmax300 minwidth200imp">';
			print nl2br($objMod->getDesc());
			print "</td>\n";

			// Help
			print '<td class="center nowrap" style="width: 82px;">';
			//print $form->textwithpicto('', $text, 1, $imginfo, 'minheight20', 0, 2, 1);
			print '<a href="javascript:document_preview(\''.DOL_URL_ROOT.'/admin/modulehelp.php?id='.((int) $objMod->numero).'\',\'text/html\',\''.dol_escape_js($langs->trans("Module")).'\')">'.img_picto(($objMod->isCoreOrExternalModule() == 'external' ? $langs->trans("ExternalModule").' - ' : '').$langs->trans("ClickToShowDescription"), $imginfo).'</a>';
			print '</td>';

			// Version
			print '<td class="center nowrap width150" title="'.dol_escape_htmltag(dol_string_nohtmltag($versiontrans)).'">';
			if ($objMod->needUpdate) {
				$versionTitle = $langs->trans('ModuleUpdateAvailable').' : '.$objMod->lastVersion;
				print '<span class="badge badge-warning classfortooltip" title="'.dol_escape_htmltag($versionTitle).'">'.$versiontrans.'</span>';
			} else {
				print $versiontrans;
			}
			print "</td>\n";

			// Link enable/disable
			print '<td class="center valignmiddle left nowraponall" width="60px">';
			print $codeenabledisable;
			print "</td>\n";

			// Link config
			print '<td class="tdsetuppicto right valignmiddle" width="60px">';
			print $codetoconfig;
			print '</td>';

			print "</tr>\n";
		}
		if ($objMod->needUpdate) {
			$foundoneexternalmodulewithupdate++;
		}
	}

	if ($action == 'checklastversion') {
		if ($foundoneexternalmodulewithupdate) {
			setEventMessages($langs->trans("ModuleUpdateAvailable"), null, 'warnings');
		} else {
			setEventMessages($langs->trans("NoExternalModuleWithUpdate"), null, 'mesgs');
		}
	}

	if ($oldfamily) {
		if ($mode == 'commonkanban') {
			print '</div>';
		} else {
			print "</table>\n";
			print '</div>';
		}
	}

	if (!$atleastonequalified) {
		print '<br><span class="opacitymedium">'.$langs->trans("NoDeployedModulesFoundWithThisSearchCriteria").'</span><br><br>';
	}

	print dol_get_fiche_end();

	print '<br>';

	// Show warning about external users
	print info_admin(showModulesExludedForExternal($modules))."\n";

	print '</form>';
}

if ($mode == 'marketplace') {
	print dol_get_fiche_head($head, $mode, '', -1);

	print $deschelp;

	print '<br>';

	// Marketplace
	print '<div class="div-table-responsive-no-min">';
	print '<table summary="list_of_modules" class="noborder centpercent">'."\n";
	print '<tr class="liste_titre">'."\n";
	print '<td class="hideonsmartphone">'.$form->textwithpicto($langs->trans("Provider"), $langs->trans("WebSiteDesc")).'</td>';
	print '<td></td>';
	print '<td>'.$langs->trans("URL").'</td>';
	print '</tr>';

	print '<tr class="oddeven">'."\n";
	$url = 'https://www.dolistore.com';
	print '<td class="hideonsmartphone"><a href="'.$url.'" target="_blank" rel="noopener noreferrer external"><img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a></td>';
	print '<td><span class="opacitymedium">'.$langs->trans("DoliStoreDesc").'</span></td>';
	print '<td><a href="'.$url.'" target="_blank" rel="noopener noreferrer external">'.$url.'</a></td>';
	print '</tr>';

	print "</table>\n";
	print '</div>';

	print dol_get_fiche_end();

	print '<br>';

	if (!getDolGlobalString('MAIN_DISABLE_DOLISTORE_SEARCH') && getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1) {
		// $options is array with filter criteria
		//var_dump($options);
		$dolistore->getRemoteCategories();
		$dolistore->getRemoteProducts($options);

		print '<span class="opacitymedium">'.$langs->trans('DOLISTOREdescriptionLong').'</span><br><br>';

		$previouslink = $dolistore->get_previous_link();
		$nextlink = $dolistore->get_next_link();

		print '<div class="liste_titre liste_titre_bydiv centpercent"><div class="divsearchfield">';

		print '<form method="POST" class="centpercent" id="searchFormList" action="'.$dolistore->url.'">'; ?>
					<input type="hidden" name="token" value="<?php echo newToken(); ?>">
					<input type="hidden" name="mode" value="marketplace">
					<div class="divsearchfield">
						<input name="search_keyword" placeholder="<?php echo $langs->trans('Keyword') ?>" id="search_keyword" type="text" class="minwidth200" value="<?php echo dol_escape_htmltag($options['search']) ?>"><br>
					</div>
					<div class="divsearchfield">
						<input class="button buttongen" value="<?php echo $langs->trans('Rechercher') ?>" type="submit">
						<a class="buttonreset" href="<?php echo urlencode($dolistore->url) ?>"><?php echo $langs->trans('Reset') ?></a>

						&nbsp;
					</div>
		<?php
		print $previouslink;
		print $nextlink;
		print '</form>';

		print '</div></div>';
		print '<div class="clearboth"></div>';
		?>

			<div id="category-tree-left">
				<ul class="tree">
					<?php
					echo $dolistore->get_categories();	// Do not use dol_escape_htmltag here, it is already a structured content?>
				</ul>
			</div>
			<div id="listing-content">
				<table summary="list_of_modules" id="list_of_modules" class="productlist centpercent">
					<tbody id="listOfModules">
						<?php echo $dolistore->get_products(); ?>
					</tbody>
				</table>
			</div>
		<?php
	}
}


// Install external module

if ($mode == 'deploy') {
	print dol_get_fiche_head($head, $mode, '', -1);

	$fullurl = '<a href="'.$urldolibarrmodules.'" target="_blank" rel="noopener noreferrer">'.$urldolibarrmodules.'</a>';
	$message = '';
	if ($allowonlineinstall) {
		if (!in_array('/custom', explode(',', $dolibarr_main_url_root_alt))) {
			$message = info_admin($langs->trans("ConfFileMustContainCustom", DOL_DOCUMENT_ROOT.'/custom', DOL_DOCUMENT_ROOT));
			$allowfromweb = -1;
		} else {
			if ($dirins_ok) {
				if (!is_writable(dol_osencode($dirins))) {
					$langs->load("errors");
					$message = info_admin($langs->trans("ErrorFailedToWriteInDir", $dirins), 0, 0, '1', 'warning');
					$allowfromweb = 0;
				}
			} else {
				$message = info_admin($langs->trans("NotExistsDirect", $dirins).$langs->trans("InfDirAlt").$langs->trans("InfDirExample"));
				$allowfromweb = 0;
			}
		}
	} else {
		if (getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US')) {
			// Show clean message
			if (!is_numeric(getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US'))) {
				$message = info_admin($langs->trans(getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US')), 0, 0, 'warning');
			} else {
				$message = info_admin($langs->trans('InstallModuleFromWebHasBeenDisabledContactUs'), 0, 0, 'warning');
			}
		} else {
			// Show technical message
			$message = info_admin($langs->trans("InstallModuleFromWebHasBeenDisabledByFile", $dolibarrdataroot.'/installmodules.lock'), 0, 0, 'warning');
		}
		$allowfromweb = 0;
	}

	print $deschelp;

	if ($allowfromweb < 1) {
		print $langs->trans("SomethingMakeInstallFromWebNotPossible");
		print $message;
		//print $langs->trans("SomethingMakeInstallFromWebNotPossible2");
		print '<br>';
	}

	print '<br>';

	// $allowfromweb = -1 if installation or setup not correct, 0 if not allowed, 1 if allowed
	if ($allowfromweb >= 0) {
		if ($allowfromweb == 1) {
			//print $langs->trans("ThisIsProcessToFollow").'<br>';
		} else {
			print $langs->trans("ThisIsAlternativeProcessToFollow").'<br>';
			print '<b>'.$langs->trans("StepNb", 1).'</b>: ';
			print str_replace('{s1}', $fullurl, $langs->trans("FindPackageFromWebSite", '{s1}')).'<br>';
			print '<b>'.$langs->trans("StepNb", 2).'</b>: ';
			print str_replace('{s1}', $fullurl, $langs->trans("DownloadPackageFromWebSite", '{s1}')).'<br>';
			print '<b>'.$langs->trans("StepNb", 3).'</b>: ';
		}

		if ($allowfromweb == 1) {
			print '<form enctype="multipart/form-data" method="POST" class="noborder" action="'.$_SERVER["PHP_SELF"].'" name="forminstall">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="install">';
			print '<input type="hidden" name="mode" value="deploy">';

			print $langs->trans("YouCanSubmitFile").'<br><br>';

			$max = getDolGlobalString('MAIN_UPLOAD_DOC'); // In Kb
			$maxphp = @ini_get('upload_max_filesize'); // In unknown
			if (preg_match('/k$/i', $maxphp)) {
				$maxphp = preg_replace('/k$/i', '', $maxphp);
				$maxphp *= 1;
			}
			if (preg_match('/m$/i', $maxphp)) {
				$maxphp = preg_replace('/m$/i', '', $maxphp);
				$maxphp *= 1024;
			}
			if (preg_match('/g$/i', $maxphp)) {
				$maxphp = preg_replace('/g$/i', '', $maxphp);
				$maxphp *= 1024 * 1024;
			}
			if (preg_match('/t$/i', $maxphp)) {
				$maxphp = preg_replace('/t$/i', '', $maxphp);
				$maxphp *= 1024 * 1024 * 1024;
			}
			$maxphp2 = @ini_get('post_max_size'); // In unknown
			if (preg_match('/k$/i', $maxphp2)) {
				$maxphp2 = preg_replace('/k$/i', '', $maxphp2);
				$maxphp2 *= 1;
			}
			if (preg_match('/m$/i', $maxphp2)) {
				$maxphp2 = preg_replace('/m$/i', '', $maxphp2);
				$maxphp2 *= 1024;
			}
			if (preg_match('/g$/i', $maxphp2)) {
				$maxphp2 = preg_replace('/g$/i', '', $maxphp2);
				$maxphp2 *= 1024 * 1024;
			}
			if (preg_match('/t$/i', $maxphp2)) {
				$maxphp2 = preg_replace('/t$/i', '', $maxphp2);
				$maxphp2 *= 1024 * 1024 * 1024;
			}
			// Now $max and $maxphp and $maxphp2 are in Kb
			$maxmin = $max;
			$maxphptoshow = $maxphptoshowparam = '';
			if ($maxphp > 0) {
				$maxmin = min($max, $maxphp);
				$maxphptoshow = $maxphp;
				$maxphptoshowparam = 'upload_max_filesize';
			}
			if ($maxphp2 > 0) {
				$maxmin = min($max, $maxphp2);
				if ($maxphp2 < $maxphp) {
					$maxphptoshow = $maxphp2;
					$maxphptoshowparam = 'post_max_size';
				}
			}

			if ($maxmin > 0) {
				print '<script type="text/javascript">
				$(document).ready(function() {
					jQuery("#fileinstall").on("change", function() {
						if(this.files[0].size > '.($maxmin * 1024).') {
							alert("'.dol_escape_js($langs->trans("ErrorFileSizeTooLarge")).'");
							this.value = "";
						}
					});
				});
				</script>'."\n";
				// MAX_FILE_SIZE doit précéder le champ input de type file
				print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';
			}

			print '<input class="flat minwidth400" type="file" name="fileinstall" id="fileinstall"> ';

			print '<input type="submit" name="send" value="'.dol_escape_htmltag($langs->trans("Upload")).'" class="button small">';

			if (getDolGlobalString('MAIN_UPLOAD_DOC')) {
				if ($user->admin) {
					$langs->load('other');
					print ' ';
					print info_admin($langs->trans("ThisLimitIsDefinedInSetup", $max, $maxphptoshow, $maxphptoshowparam), 1);
				}
			} else {
				print ' ('.$langs->trans("UploadDisabled").')';
			}

			print '</form>';

			print '<br>';
			print '<br>';

			print '<div class="center"><div class="logo_setup"></div></div>';
		} else {
			print $langs->trans("UnpackPackageInModulesRoot", $dirins).'<br>';
			print '<b>'.$langs->trans("StepNb", 4).'</b>: ';
			print $langs->trans("SetupIsReadyForUse", DOL_URL_ROOT.'/admin/modules.php?mainmenu=home', $langs->transnoentitiesnoconv("Home").' - '.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Modules")).'<br>';
		}
	}

	if (!empty($result['return'])) {
		print '<br>';

		foreach ($result['return'] as $value) {
			echo $value.'<br>';
		}
	}

	print dol_get_fiche_end();
}

if ($mode == 'develop') {
	print dol_get_fiche_head($head, $mode, '', -1);

	print $deschelp;

	print '<br>';

	// Marketplace
	print '<table summary="list_of_modules" class="noborder centpercent">'."\n";
	print '<tr class="liste_titre">'."\n";
	//print '<td>'.$langs->trans("Logo").'</td>';
	print '<td colspan="2">'.$langs->trans("DevelopYourModuleDesc").'</td>';
	print '<td>'.$langs->trans("URL").'</td>';
	print '</tr>';

	print '<tr class="oddeven" height="80">'."\n";
	print '<td class="center">';
	print '<div class="imgmaxheight50 logo_setup"></div>';
	print '</td>';
	print '<td>'.$langs->trans("TryToUseTheModuleBuilder", $langs->transnoentitiesnoconv("ModuleBuilder")).'</td>';
	print '<td class="maxwidth300">';
	if (isModEnabled('modulebuilder')) {
		print $langs->trans("SeeTopRightMenu");
	} else {
		print '<span class="opacitymedium">'.$langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("ModuleBuilder")).'</span>';
	}
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven" height="80">'."\n";
	$url = 'https://partners.dolibarr.org';
	print '<td class="center">';
	print'<a href="'.$url.'" target="_blank" rel="noopener noreferrer external"><img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolibarr_preferred_partner.png"></a>';
	print '</td>';
	print '<td>'.$langs->trans("DoliPartnersDesc").'</td>';
	print '<td><a href="'.$url.'" target="_blank" rel="noopener noreferrer external">';
	print img_picto('', 'url', 'class="pictofixedwidth"');
	print $url.'</a></td>';
	print '</tr>';

	print "</table>\n";

	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
