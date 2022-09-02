<?php
/* Copyright (C) 2009-2019	Laurent Destailleur	<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2013  Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/admin/geoipmaxmind.php
 *	\ingroup    geoipmaxmind
 *	\brief      Setup page for geoipmaxmind module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';

// Security check
if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors"));

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'set') {
	$error = 0;

	$gimcdf = GETPOST("GEOIPMAXMIND_COUNTRY_DATAFILE");

	if (!$error && $gimcdf && !preg_match('/\.(dat|mmdb)$/', $gimcdf)) {
		setEventMessages($langs->trans("ErrorFileMustHaveFormat", '.dat|.mmdb'), null, 'errors');
		$error++;
	}

	if (!$error && $gimcdf && !file_exists($gimcdf)) {
		setEventMessages($langs->trans("ErrorFileNotFound", $gimcdf), null, 'errors');
		$error++;
	}

	if (!$error) {
		$res1 = dolibarr_set_const($db, "GEOIP_VERSION", GETPOST('geoipversion', 'aZ09'), 'chaine', 0, '', $conf->entity);
		if (!($res1 > 0)) {
			$error++;
		}

		$res2 = dolibarr_set_const($db, "GEOIPMAXMIND_COUNTRY_DATAFILE", $gimcdf, 'chaine', 0, '', $conf->entity);
		if (!($res2 > 0)) {
			$error++;
		}

		if (!$error) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}

if (!isset($conf->global->GEOIP_VERSION)) {
	$conf->global->GEOIP_VERSION = '2';
}


/*
 * View
 */

$form = new Form($db);

llxHeader();

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("GeoIPMaxmindSetup"), $linkback, 'title_setup');
print '<br>';

$version = '';
$geoip = '';
if (!empty($conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE)) {
	$geoip = new DolGeoIP('country', $conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE);
}

// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
print '<td class="right"><input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></td>';
print "</tr>\n";

// Lib version
print '<tr class="oddeven"><td width="50%">'.$langs->trans("GeoIPLibVersion").'</td>';
print '<td colspan="2">';
$arrayofvalues = array('php' => 'Native PHP functions', '1' => 'Embedded GeoIP v1', '2' => 'Embedded GeoIP v2');
print $form->selectarray('geoipversion', $arrayofvalues, (isset($conf->global->GEOIP_VERSION) ? $conf->global->GEOIP_VERSION : '2'));
if ($conf->global->GEOIP_VERSION == 'php') {
	if ($geoip) {
		$version = $geoip->getVersion();
	}
	if ($version) {
		print '<br>'.$langs->trans("Version").': '.$version;
	}
}
print '</td></tr>';

// Path to database file
print '<tr class="oddeven"><td>'.$langs->trans("PathToGeoIPMaxmindCountryDataFile").'</td>';
print '<td colspan="2">';

if ($conf->global->GEOIP_VERSION == 'php') {
	print 'Using geoip PHP internal functions. Value must be '.geoip_db_filename(GEOIP_COUNTRY_EDITION).' or '.geoip_db_filename(GEOIP_CITY_EDITION_REV1).' or /pathtodatafile/GeoLite2-Country.mmdb<br>';
}
print '<input type="text" class="minwidth200" name="GEOIPMAXMIND_COUNTRY_DATAFILE" value="'.dol_escape_htmltag($conf->global->GEOIPMAXMIND_COUNTRY_DATAFILE).'">';
print '</td></tr>';

print '</table>';

print "</form>\n";

print '<br>';

print $langs->trans("NoteOnPathLocation").'<br>';

$url1 = 'http://www.maxmind.com/en/city?rId=awstats';
print $langs->trans("YouCanDownloadFreeDatFileTo", '<a href="'.$url1.'" target="_blank" rel="noopener noreferrer external">'.$url1.'</a>');

print '<br>';

$url2 = 'http://www.maxmind.com/en/city?rId=awstats';
print $langs->trans("YouCanDownloadAdvancedDatFileTo", '<a href="'.$url2.'" target="_blank" rel="noopener noreferrer external">'.$url2.'</a>');

if ($geoip) {
	print '<br><br>';
	print '<br><span class="opacitymedium">'.$langs->trans("TestGeoIPResult", $ip).':</span>';

	$ip = '24.24.24.24';
	print '<br>'.$ip.' -> ';
	$result = dol_print_ip($ip, 1);
	if ($result) {
		print $result;
	} else {
		print $langs->trans("Error");
	}

	$ip = '2a01:e0a:7e:4a60:429a:23ff:f7b8:dc8a'; // should be France
	print '<br>'.$ip.' -> ';
	$result = dol_print_ip($ip, 1);
	if ($result) {
		print $result;
	} else {
		print $langs->trans("Error");
	}


	/* We disable this test because dol_print_ip need an ip as input
	$ip='www.google.com';
	print '<br>'.$ip.' -> ';
	$result=dol_print_ip($ip,1);
	if ($result) print $result;
	else print $langs->trans("Error");
	*/
	//var_dump($_SERVER);
	$ip = getUserRemoteIP();
	//$ip='91.161.249.43';
	$isip = is_ip($ip);
	if ($isip == 1) {
		print '<br>'.$ip.' -> ';
		$result = dol_print_ip($ip, 1);
		if ($result) {
			print $result;
		} else {
			print $langs->trans("Error");
		}
	} else {
		print '<br>'.$ip.' -> ';
		$result = dol_print_ip($ip, 1);
		if ($result) {
			print $result;
		} else {
			print $langs->trans("NotAPublicIp");
		}
	}

	$geoip->close();
}

// End of page
llxFooter();
$db->close();
