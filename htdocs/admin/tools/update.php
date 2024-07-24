<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
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
 *		\file 		htdocs/admin/tools/update.php
 *		\brief      Page to make a Dolibarr online upgrade
 */

if (! defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

if (GETPOST('msg', 'alpha')) {
	setEventMessages(GETPOST('msg', 'alpha'), null, 'errors');
}


$urldolibarr = 'https://www.dolibarr.org/downloads/';
$dolibarrroot = preg_replace('/([\\/]+)$/i', '', DOL_DOCUMENT_ROOT);
$dolibarrroot = preg_replace('/([^\\/]+)$/i', '', $dolibarrroot);
$dolibarrdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);

$sfurl = '';
$version = '0.0';


/*
 *	Actions
 */

if ($action == 'getlastversion') {
	$result = getURLContent('https://sourceforge.net/projects/dolibarr/rss');
	//var_dump($result['content']);
	if (function_exists('simplexml_load_string')) {
		if (LIBXML_VERSION < 20900) {
			// Avoid load of external entities (security problem).
			// Required only if LIBXML_VERSION < 20900
			libxml_disable_entity_loader(true);
		}

		$sfurl = simplexml_load_string($result['content'], 'SimpleXMLElement', LIBXML_NOCDATA|LIBXML_NONET);
	} else {
		$sfurl = 'xml_not_available';
	}
}


/*
 * View
 */

$wikihelp = 'EN:Installation_-_Upgrade|FR:Installation_-_Mise_à_jour|ES:Instalación_-_Actualización';
llxHeader('', $langs->trans("Upgrade"), $wikihelp);

print load_fiche_titre($langs->trans("Upgrade"), '', 'title_setup');

print '<br>';

print $langs->trans("CurrentVersion").' : <strong>'.DOL_VERSION.'</strong><br>';

if (function_exists('curl_init')) {
	$conf->global->MAIN_USE_RESPONSE_TIMEOUT = 10;

	if ($action == 'getlastversion') {
		if ($sfurl == 'xml_not_available') {
			$langs->load("errors");
			print $langs->trans("LastStableVersion").' : <b class="error">'.$langs->trans("ErrorFunctionNotAvailableInPHP", 'simplexml_load_string').'</b><br>';
		} elseif ($sfurl) {
			$i = 0;
			while (!empty($sfurl->channel[0]->item[$i]->title) && $i < 10000) {
				$title = $sfurl->channel[0]->item[$i]->title;
				if (preg_match('/([0-9]+\.([0-9\.]+))/', $title, $reg)) {
					$newversion = $reg[1];
					$newversionarray = explode('.', $newversion);
					$versionarray = explode('.', $version);
					//var_dump($newversionarray);var_dump($versionarray);
					if (versioncompare($newversionarray, $versionarray) > 0) {
						$version = $newversion;
					}
				}
				$i++;
			}

			// Show version
			print $langs->trans("LastStableVersion").' : <b>'.(($version != '0.0') ? $version : $langs->trans("Unknown")).'</b><br>';
		} else {
			print $langs->trans("LastStableVersion").' : <b>'.$langs->trans("UpdateServerOffline").'</b><br>';
		}
	} else {
		print $langs->trans("LastStableVersion").' : <a href="'.$_SERVER["PHP_SELF"].'?action=getlastversion&token='.newToken().'" class="button smallpaddingimp">'.$langs->trans("Check").'</a><br>';
	}
}

print '<br>';
print '<br>';

// Upgrade
print $langs->trans("Upgrade").'<br>';
print '<hr>';
print $langs->trans("ThisIsProcessToFollow").'<br>';
print '<b>'.$langs->trans("StepNb", 1).'</b>: ';
$fullurl = '<a href="'.$urldolibarr.'" target="_blank" rel="noopener noreferrer">'.$urldolibarr.'</a>';
print str_replace('{s}', $fullurl, $langs->trans("DownloadPackageFromWebSite", '{s}')).'<br>';
print '<b>'.$langs->trans("StepNb", 2).'</b>: ';
print str_replace('{s}', $dolibarrroot, $langs->trans("UnpackPackageInDolibarrRoot", '{s}')).'<br>';
print '<b>'.$langs->trans("StepNb", 3).'</b>: ';
print $langs->trans("RemoveLock", $dolibarrdataroot.'/install.lock').'<br>';
print '<b>'.$langs->trans("StepNb", 4).'</b>: ';
$fullurl = '<a href="'.DOL_URL_ROOT.'/install/" target="_blank" rel="noopener noreferrer">'.DOL_URL_ROOT.'/install/</a>';
print str_replace('{s}', $fullurl, $langs->trans("CallUpdatePage", '{s}')).'<br>';
print '<b>'.$langs->trans("StepNb", 5).'</b>: ';
print $langs->trans("RestoreLock", $dolibarrdataroot.'/install.lock').'<br>';

print '<br>';
print '<br>';





print $langs->trans("AddExtensionThemeModuleOrOther").'<br>';
print '<hr>';
$texttoshow = $langs->trans("GoModuleSetupArea", DOL_URL_ROOT.'/admin/modules.php?mode=deploy', '{s2}');
$texttoshow = str_replace('{s2}', img_picto('', 'tools', 'class="pictofixedwidth"').$langs->transnoentities("Home").' - '.$langs->transnoentities("Setup").' - '.$langs->transnoentities("Modules"), $texttoshow);
print $texttoshow;

// End of page
llxFooter();
$db->close();
