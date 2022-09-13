<?php
/* Copyright (C) 2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2017	Regis Houssin			<regis.houssin@inodbox.com>
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
 *  \file       htdocs/admin/modulehelp.php
 *  \brief      Page to activate/disable all modules
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disabled because this page is into a popup on module search page and we want to avoid to have an Anti CSRF token error (done if MAIN_SECURITY_CSRF_WITH_TOKEN is on) when we make a second search after closing popup.
}


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('errors', 'admin', 'modulebuilder', 'exports'));

$mode = GETPOST('mode', 'alpha');
$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
if (empty($mode)) {
	$mode = 'desc';
}

if (!$user->admin) {
	accessforbidden();
}



/*
 * Actions
 */

// Nothing


/*
 * View
 */

$form = new Form($db);

$help_url = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $help_url);

print '<!-- Force style container -->'."\n".'<style>
.id-container {
    width: 100%;
}
#id-right {
	padding-left: unset;
}
</style>';

$arrayofnatures = array('core'=>$langs->transnoentitiesnoconv("Core"), 'external'=>$langs->transnoentitiesnoconv("External").' - '.$langs->trans("AllPublishers"));

// Search modules dirs
$modulesdir = dolGetModulesDirs();


$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
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
					if (!empty($modNameLoaded[$modName])) {
						$mesg = "Error: Module ".$modName." was found twice: Into ".$modNameLoaded[$modName]." and ".$dir.". You probably have an old file on your disk.<br>";
						setEventMessages($mesg, null, 'warnings');
						dol_syslog($mesg, LOG_ERR);
						continue;
					}

					try {
						$res = include_once $dir.$file;
						if (class_exists($modName)) {
							try {
								$objMod = new $modName($db);
								$modNameLoaded[$modName] = $dir;

								if (!$objMod->numero > 0 && $modName != 'modUser') {
									dol_syslog('The module descriptor '.$modName.' must have a numero property', LOG_ERR);
								}
								$j = $objMod->numero;

								$modulequalified = 1;

								// We discard modules according to features level (PS: if module is activated we always show it)
								$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));
								if ($objMod->version == 'development' && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 2))) {
									$modulequalified = 0;
								}
								if ($objMod->version == 'experimental' && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 1))) {
									$modulequalified = 0;
								}
								if (preg_match('/deprecated/', $objMod->version) && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL >= 0))) {
									$modulequalified = 0;
								}

								// We discard modules according to property disabled
								//if (! empty($objMod->hidden)) $modulequalified=0;

								if ($modulequalified > 0) {
									$publisher = dol_escape_htmltag($objMod->getPublisher());
									$external = ($objMod->isCoreOrExternalModule() == 'external');
									if ($external) {
										if ($publisher) {
											$arrayofnatures['external_'.$publisher] = $langs->trans("External").' - '.$publisher;
										} else {
											$arrayofnatures['external_'] = $langs->trans("External").' - '.$langs->trans("UnknownPublishers");
										}
									}
									ksort($arrayofnatures);
								}

								// Define array $categ with categ with at least one qualified module
								if ($modulequalified > 0) {
									$modules[$i] = $objMod;
									$filename[$i] = $modName;

									// Gives the possibility to the module, to provide his own family info and position of this family
									if (is_array($objMod->familyinfo) && !empty($objMod->familyinfo)) {
										if (!is_array($familyinfo)) {
											$familyinfo = array();
										}
										$familyinfo = array_merge($familyinfo, $objMod->familyinfo);
										$familykey = key($objMod->familyinfo);
									} else {
										$familykey = $objMod->family;
									}

									$moduleposition = ($objMod->module_position ? $objMod->module_position : '50');
									if ($moduleposition == '50' && ($objMod->isCoreOrExternalModule() == 'external')) {
										$moduleposition = '80'; // External modules at end by default
									}

									if (empty($familyinfo[$familykey]['position'])) {
										$familyinfo[$familykey]['position'] = '0';
									}

									$orders[$i] = $familyinfo[$familykey]['position']."_".$familykey."_".$moduleposition."_".$j; // Sort by family, then by module position then number
									$dirmod[$i] = $dir;
									//print $i.'-'.$dirmod[$i].'<br>';
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
							} catch (Exception $e) {
								 dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
							}
						} else {
							print "Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)<br>";
						}
					} catch (Exception $e) {
						 dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
					}
				}
			}
		}
		closedir($handle);
	} else {
		dol_syslog("htdocs/admin/modulehelp.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
	}
}

asort($orders);
//var_dump($orders);
//var_dump($categ);
//var_dump($modules);


unset($objMod);
$i = 0;
foreach ($orders as $tmpkey => $tmpvalue) {
	$tmpMod = $modules[$tmpkey];
	if ($tmpMod->numero == $id) {
		$key = $i;
		$modName = $filename[$tmpkey];
		$dirofmodule = $dirmod[$tmpkey];
		$objMod = $tmpMod;
		break;
	}
	$i++;
}
$value = $orders[$key];
$tab = explode('_', $value);
$familyposition = $tab[0]; $familykey = $tab[1]; $module_position = $tab[2]; $numero = $tab[3];



$head = modulehelp_prepare_head($objMod);

// Check filters
$modulename = $objMod->getName();
$moduledesc = $objMod->getDesc();
$moduleauthor = $objMod->getPublisher();
$moduledir = strtolower(preg_replace('/^mod/i', '', get_class($objMod)));

$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));

$text = '<span class="opacitymedium">'.$langs->trans("LastActivationDate").':</span> ';
if (!empty($conf->global->$const_name)) {
	$text .= dol_print_date($objMod->getLastActivationDate(), 'dayhour');
} else {
	$text .= $langs->trans("Disabled");
}
$tmp = $objMod->getLastActivationInfo();
$authorid = (empty($tmp['authorid']) ? '' : $tmp['authorid']);
if ($authorid > 0) {
	$tmpuser = new User($db);
	$tmpuser->fetch($authorid);
	$text .= '<br><span class="opacitymedium">'.$langs->trans("LastActivationAuthor").':</span> ';
	$text .= $tmpuser->getNomUrl(1);
}
$ip = (empty($tmp['ip']) ? '' : $tmp['ip']);
if ($ip) {
	$text .= '<br><span class="opacitymedium">'.$langs->trans("LastActivationIP").':</span> ';
	$text .= $ip;
}
$lastactivationversion = (empty($tmp['lastactivationversion']) ? '' : $tmp['lastactivationversion']);
if ($lastactivationversion) {
	$text .= '<br><span class="opacitymedium">'.$langs->trans("LastActivationVersion").':</span> ';
	$text .= $lastactivationversion;
}

$moreinfo = $text;

$title = ($modulename ? $modulename : $moduledesc);

print '<div class="centpercent">';

$picto = 'object_'.$objMod->picto;

print load_fiche_titre(($modulename ? $modulename : $moduledesc), $moreinfo, $picto, 0, '', 'titlemodulehelp');
print '<br>';

print dol_get_fiche_head($head, $mode, '', -1);

if (!$modulename) {
	dol_syslog("Error for module ".$key." - Property name of module looks empty", LOG_WARNING);
}

// Load all lang files of module
if (isset($objMod->langfiles) && is_array($objMod->langfiles)) {
	foreach ($objMod->langfiles as $domain) {
		$langs->load($domain);
	}
}




// Version (with picto warning or not)
$version = $objMod->getVersion(0);
$versiontrans = '';
if (preg_match('/development/i', $version)) {
	$versiontrans .= img_warning($langs->trans("Development"), 'style="float: left"');
}
if (preg_match('/experimental/i', $version)) {
	$versiontrans .= img_warning($langs->trans("Experimental"), 'style="float: left"');
}
if (preg_match('/deprecated/i', $version)) {
	$versiontrans .= img_warning($langs->trans("Deprecated"), 'style="float: left"');
}
$versiontrans .= $objMod->getVersion(1);

// Define imginfo
$imginfo = "info";
if ($objMod->isCoreOrExternalModule() == 'external') {
	$imginfo = "info_black";
}

// Define text of description of module
$text = '';

if ($mode == 'desc') {
	if ($moduledesc) {
		$text .= '<br>'.$moduledesc.'<br><br><br>';
	}

	$text .= '<span class="opacitymedium">'.$langs->trans("Version").':</span> '.$version;

	$moduledescriptorfile = get_class($objMod).'.class.php';
	$text .= '<br><span class="opacitymedium">'.$langs->trans("DescriptorFile").':</span> '.$moduledescriptorfile;

	$textexternal = '';
	if ($objMod->isCoreOrExternalModule() == 'external') {
		$tmpdirofmoduletoshow = preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT, '/').'/', '', $dirofmodule);
		$textexternal .= '<br><span class="opacitymedium">'.$langs->trans("Origin").':</span> '.$langs->trans("ExternalModule").' - '.$langs->trans("InstalledInto", $tmpdirofmoduletoshow);

		global $dolibarr_allow_download_external_modules;
		if (!empty($dolibarr_allow_download_external_modules) && preg_match('/\/custom\//', $dirofmodule)) {
			// Add a link to download a zip of the module
			$textexternal .= ' <a href="'.DOL_URL_ROOT.'/admin/tools/export_files.php?export_type=externalmodule&what='.urlencode($moduledir).'&compression=zip&zipfilename_template=module_'.$moduledir.'-'.$version.'.notorig" target="_blank" rel="noopener">'.img_picto('', 'download').'</a>';
		}

		if ($objMod->editor_name != 'dolibarr') {
			$textexternal .= '<br><span class="opacitymedium">'.$langs->trans("Publisher").':</span> '.(empty($objMod->editor_name) ? $langs->trans("Unknown") : $objMod->editor_name);
		}
		$editor_url = $objMod->editor_url;
		if (!preg_match('/^http/', $editor_url)) {
			$editor_url = 'http://'.$editor_url;
		}
		if (!empty($objMod->editor_url) && !preg_match('/dolibarr\.org/i', $objMod->editor_url)) {
			$textexternal .= ($objMod->editor_name != 'dolibarr' ? ' - ' : '').img_picto('', 'globe').' <a href="'.$editor_url.'" target="_blank" rel="noopener noreferrer external">'.$objMod->editor_url.'</a>';
		}
		$text .= $textexternal;
		$text .= '<br>';
	} else {
		$text .= '<br><span class="opacitymedium">'.$langs->trans("Origin").':</span> '.$langs->trans("Core").'<br>';
	}

	$moduledesclong = $objMod->getDescLong();
	if ($moduledesclong) {
		$text .= '<br><hr><div class="moduledesclong">'.$moduledesclong.'<div>';
	}
}

if ($mode == 'feature') {
	$text .= '<br><strong>'.$langs->trans("DependsOn").':</strong> ';
	if (count($objMod->depends)) {
		$text .= join(',', $objMod->depends);
	} else {
		$text .= $langs->trans("None");
	}
	$text .= '<br><strong>'.$langs->trans("RequiredBy").':</strong> ';
	if (count($objMod->requiredby)) {
		$text .= join(',', $objMod->requiredby);
	} else {
		$text .= $langs->trans("None");
	}

	$text .= '<br><br>';

	$text .= '<br><strong>'.$langs->trans("AddDataTables").':</strong> ';
	$listofsqlfiles1 = dol_dir_list(DOL_DOCUMENT_ROOT.'/install/mysql/tables/', 'files', 0, 'llx.*-'.$moduledir.'\.sql', array('\.key\.sql', '\.sql\.back'));
	$listofsqlfiles2 = dol_dir_list(dol_buildpath($moduledir.'/sql/'), 'files', 0, 'llx.*\.sql', array('\.key\.sql', '\.sql\.back'));
	$sqlfiles = array_merge($listofsqlfiles1, $listofsqlfiles2);

	if (count($sqlfiles) > 0) {
		$i = 0;
		foreach ($sqlfiles as $val) {
			$text .= ($i ? ', ' : '').preg_replace('/\-'.$moduledir.'$/', '', preg_replace('/\.sql$/', '', preg_replace('/llx_/', '', $val['name'])));
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddDictionaries").':</strong> ';
	if (isset($objMod->dictionaries) && isset($objMod->dictionaries['tablib']) && is_array($objMod->dictionaries['tablib']) && count($objMod->dictionaries['tablib'])) {
		$i = 0;
		foreach ($objMod->dictionaries['tablib'] as $val) {
			$text .= ($i ? ', ' : '').$val;
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddData").':</strong> ';
	$filedata = dol_buildpath($moduledir.'/sql/data.sql');
	if (dol_is_file($filedata)) {
		$text .= $langs->trans("Yes").' <span class="opacitymedium">('.$moduledir.'/sql/data.sql)</span>';
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddRemoveTabs").':</strong> ';
	if (isset($objMod->tabs) && is_array($objMod->tabs) && count($objMod->tabs)) {
		$i = 0;
		foreach ($objMod->tabs as $val) {
			if (is_array($val)) {
				$val = $val['data'];
			}
			if (is_string($val)) {
				$tmp = explode(':', $val, 3);
				$text .= ($i ? ', ' : '').$tmp[0].':'.$tmp[1];
				$i++;
			}
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddModels").':</strong> ';
	if (isset($objMod->module_parts) && isset($objMod->module_parts['models']) && $objMod->module_parts['models']) {
		$text .= $langs->trans("Yes");
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddSubstitutions").':</strong> ';
	if (isset($objMod->module_parts) && isset($objMod->module_parts['substitutions']) && $objMod->module_parts['substitutions']) {
		$text .= $langs->trans("Yes");
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddSheduledJobs").':</strong> ';
	if (isset($objMod->cronjobs) && is_array($objMod->cronjobs) && count($objMod->cronjobs)) {
		$i = 0;
		foreach ($objMod->cronjobs as $val) {
			$text .= ($i ? ', ' : '').($val['label']);
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddTriggers").':</strong> ';
	$moreinfoontriggerfile = '';
	if (isset($objMod->module_parts) && isset($objMod->module_parts['triggers']) && $objMod->module_parts['triggers']) {
		$yesno = 'Yes';
	} else {
		$yesno = 'No';
	}
	require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
	$interfaces = new Interfaces($db);
	$triggers = $interfaces->getTriggersList(array((($objMod->isCoreOrExternalModule() == 'external') ? '/'.$moduledir : '').'/core/triggers'));
	foreach ($triggers as $triggercursor) {
		if ($triggercursor['module'] == $moduledir) {
			$yesno = 'Yes';
			$moreinfoontriggerfile = ' ('.$triggercursor['relpath'].')';
		}
	}

	$text .= $langs->trans($yesno).$moreinfoontriggerfile;

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddBoxes").':</strong> ';
	if (isset($objMod->boxes) && is_array($objMod->boxes) && count($objMod->boxes)) {
		$i = 0;
		foreach ($objMod->boxes as $val) {
			$text .= ($i ? ', ' : '').($val['file'] ? $val['file'] : $val[0]);
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddHooks").':</strong> ';
	if (isset($objMod->module_parts) && is_array($objMod->module_parts['hooks']) && count($objMod->module_parts['hooks'])) {
		$i = 0;
		foreach ($objMod->module_parts['hooks'] as $key => $val) {
			if ($key === 'entity') {
				continue;
			}

			// For special values
			if ($key === 'data') {
				if (is_array($val)) {
					foreach ($val as $value) {
						$text .= ($i ? ', ' : '').($value);
						$i++;
					}

					continue;
				}
			}

			$text .= ($i ? ', ' : '').($val);
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddPermissions").':</strong> ';
	if (isset($objMod->rights) && is_array($objMod->rights) && count($objMod->rights)) {
		$i = 0;
		foreach ($objMod->rights as $val) {
			$text .= ($i ? ', ' : '').($val[1]);
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddMenus").':</strong> ';
	if (isset($objMod->menu) && !empty($objMod->menu)) { // objMod can be an array or just an int 1
		$text .= $langs->trans("Yes");
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddExportProfiles").':</strong> ';
	if (isset($objMod->export_label) && is_array($objMod->export_label) && count($objMod->export_label)) {
		$i = 0;
		foreach ($objMod->export_label as $val) {
			$text .= ($i ? ', ' : '').($val);
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddImportProfiles").':</strong> ';
	if (isset($objMod->import_label) && is_array($objMod->import_label) && count($objMod->import_label)) {
		$i = 0;
		foreach ($objMod->import_label as $val) {
			$text .= ($i ? ', ' : '').($val);
			$i++;
		}
	} else {
		$text .= $langs->trans("No");
	}

	$text .= '<br>';

	$text .= '<br><strong>'.$langs->trans("AddOtherPagesOrServices").':</strong> ';
	$text .= $langs->trans("DetectionNotPossible");
}


if ($mode == 'changelog') {
	$changelog = $objMod->getChangeLog();
	if ($changelog) {
		$text .= '<div class="moduledesclong">'.$changelog.'<div>';
	} else {
		$text .= '<div class="moduledesclong">'.$langs->trans("NotAvailable").'</div>';
	}
}

print $text;


print dol_get_fiche_end();

print '</div>';

// End of page
llxFooter();
$db->close();
