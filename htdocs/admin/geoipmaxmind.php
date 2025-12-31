<?php
/* Copyright (C) 2009-2019	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2013  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgeoip.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Security check
if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors"));

$action = GETPOST('action', 'aZ09');

if (!isset($conf->global->GEOIP_VERSION)) {
	$conf->global->GEOIP_VERSION = '2';
}


/*
 * Actions
 */

if ($action == 'set') {
	$error = 0;

	$res1 = dolibarr_set_const($db, "GEOIP_VERSION", GETPOST('geoipversion', 'aZ09'), 'chaine', 0, '', $conf->entity);
	if (!($res1 > 0)) {
		$error++;
	}

	if (getDolGlobalString('GEOIP_VERSION') == 'php') {
		$gimcdf = GETPOST("GEOIPMAXMIND_COUNTRY_DATAFILE");
		if ($gimcdf) {
			if (!preg_match('/\.(dat|mmdb)$/', $gimcdf)) {
				setEventMessages($langs->trans("ErrorFileMustHaveFormat", '.dat|.mmdb'), null, 'errors');
				$error++;
			}

			if (!$error) {
				$res2 = dolibarr_set_const($db, "GEOIPMAXMIND_COUNTRY_DATAFILE", $gimcdf, 'chaine', 0, '', $conf->entity);
				if (!($res2 > 0)) {
					$error++;
				}
			}
		}
	} else {
		$gimcdf = GETPOST("GEOIPMAXMIND_COUNTRY_DATAFILE_EMBEDDED");
		if ($gimcdf && !preg_match('/\.(dat|mmdb)$/', $gimcdf)) {
			setEventMessages($langs->trans("ErrorFileMustHaveFormat", '.dat|.mmdb'), null, 'errors');
			$error++;
		}

		if (!$error) {
			$varname = 'GEOIPMAXMIND_COUNTRY_DATAFILE_EMBEDDED';
			if (isset($_FILES[$varname]) && $_FILES[$varname]["name"]) {
				$diroffile = getMultidirOutput(null, 'geoipmaxmind');
				if ($diroffile) {
					$dirforterms = $diroffile.'/';
					$original_file = $_FILES[$varname]["name"];
					$result = dol_move_uploaded_file($_FILES[$varname]["tmp_name"], $dirforterms.$original_file, 1, 0, $_FILES[$varname]['error']);
					if ((int) $result > 0) {
						dolibarr_set_const($db, $varname, $original_file, 'chaine', 0, '', $conf->entity);
					} else {
						$error++;
						setEventMessages($langs->trans("Error").' '.$langs->transnoentitiesnoconv((string) $result), null, 'errors');
					}
				}
			}
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		//setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

$documenturl = getDolGlobalString('DOL_URL_ROOT_DOCUMENT_PHP', DOL_URL_ROOT.'/document.php');


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-geoipmaxmind');

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("GeoIPMaxmindSetup"), $linkback, 'title_setup');
print '<br>';

$version = '';
$geoip = '';
if (getDolGlobalString('GEOIP_VERSION') == 'php') {
	$datafile = getDolGlobalString('GEOIPMAXMIND_COUNTRY_DATAFILE');
} else {
	$diroffile = getMultidirOutput(null, 'geoipmaxmind');
	$datafile = $diroffile . '/' . getDolGlobalString('GEOIPMAXMIND_COUNTRY_DATAFILE_EMBEDDED');
}
if ($datafile) {
	$geoip = new DolGeoIP('country', $datafile);
}

// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td><td></td>';
print '<td class="right"></td>';
print "</tr>\n";

// Lib version
print '<tr class="oddeven"><td>'.$langs->trans("GeoIPLibVersion").'</td>';
print '<td>';
$arrayofvalues = array('php' => 'Native PHP functions', '1' => 'Embedded GeoIP v1', '2' => 'Embedded GeoIP v2');
print $form->selectarray('geoipversion', $arrayofvalues, getDolGlobalString('GEOIP_VERSION', '2'));
if (getDolGlobalString('GEOIP_VERSION') == 'php') {
	if ($geoip) {
		$version = $geoip->getVersion();
	}
	if ($version) {
		print '<br>'.$langs->trans("Version").': '.$version;
	}
}
print '</td>';
print '<td>';
print '</td></tr>';

// Path to database file
print '<tr class="oddeven"><td>'.$langs->trans("PathToGeoIPMaxmindCountryDataFile").'</td>';
print '<td>';
if (getDolGlobalString('GEOIP_VERSION') == 'php') {
	$gimcdf = getDolGlobalString('GEOIPMAXMIND_COUNTRY_DATAFILE');

	if (function_exists('geoip_db_filename')) {
		print 'Using geoip PHP internal functions. Value must be '.geoip_db_filename(GEOIP_COUNTRY_EDITION).' or '.geoip_db_filename(GEOIP_CITY_EDITION_REV1).' or /pathtodatafile/GeoLite2-Country.mmdb<br>';
	}
	print '<input type="text" class="minwidth200" name="GEOIPMAXMIND_COUNTRY_DATAFILE" value="'.dol_escape_htmltag(getDolGlobalString('GEOIPMAXMIND_COUNTRY_DATAFILE')).'">';
	if (!file_exists(str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $gimcdf))) {
		print '<div class="error">'.$langs->trans("ErrorFileNotFound", $gimcdf).'</div>';
	}
} else {
	$modulepart = 'geoipmaxmind';
	print '<div class="inline-block nobordernopadding valignmiddle "><div class="inline-block marginrightonly">';
	$maxfilesizearray = getMaxFileSizeArray();
	$maxmin = $maxfilesizearray['maxmin'];
	if ($maxmin > 0) {
		print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
	}
	print '<input type="file" class="flat minwidth100 maxwidthinputfileonsmartphone" name="GEOIPMAXMIND_COUNTRY_DATAFILE_EMBEDDED" id="GEOIPMAXMIND_COUNTRY_DATAFILE_EMBEDDED">';

	// TODO Move this into a function $out = getHelpOnUploadMax();
	$out = '';
	if (getDolGlobalString('MAIN_UPLOAD_DOC')) {
		$max = getDolGlobalString('MAIN_UPLOAD_DOC'); // In Kb
		$maxphp = @ini_get('upload_max_filesize'); // In unknown
		if (preg_match('/k$/i', $maxphp)) {
			$maxphp = preg_replace('/k$/i', '', $maxphp);
			$maxphp = (int) $maxphp * 1;
		}
		if (preg_match('/m$/i', $maxphp)) {
			$maxphp = preg_replace('/m$/i', '', $maxphp);
			$maxphp = (int) $maxphp * 1024;
		}
		if (preg_match('/g$/i', $maxphp)) {
			$maxphp = preg_replace('/g$/i', '', $maxphp);
			$maxphp = (int) $maxphp * 1024 * 1024;
		}
		if (preg_match('/t$/i', $maxphp)) {
			$maxphp = preg_replace('/t$/i', '', $maxphp);
			$maxphp = (int) $maxphp * 1024 * 1024 * 1024;
		}
		$maxphp2 = @ini_get('post_max_size'); // In unknown
		if (preg_match('/k$/i', $maxphp2)) {
			$maxphp2 = preg_replace('/k$/i', '', $maxphp2);
			$maxphp2 = (int) $maxphp2 * 1;
		}
		if (preg_match('/m$/i', $maxphp2)) {
			$maxphp2 = preg_replace('/m$/i', '', $maxphp2);
			$maxphp2 = (int) $maxphp2 * 1024;
		}
		if (preg_match('/g$/i', $maxphp2)) {
			$maxphp2 = preg_replace('/g$/i', '', $maxphp2);
			$maxphp2 = (int) $maxphp2 * 1024 * 1024;
		}
		if (preg_match('/t$/i', $maxphp2)) {
			$maxphp2 = preg_replace('/t$/i', '', $maxphp2);
			$maxphp2 = (int) $maxphp2 * 1024 * 1024 * 1024;
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

		$langs->load('other');
		$out .= ' ';
		$out .= info_admin($langs->trans("ThisLimitIsDefinedInSetup", $max, $maxphptoshow), 1);
	} else {
		$out .= ' ('.$langs->trans("UploadDisabled").')';
	}

	print $out;

	print '</div>';
	if (getDolGlobalString("GEOIPMAXMIND_COUNTRY_DATAFILE_EMBEDDED")) {
		$geoipfile = getDolGlobalString("GEOIPMAXMIND_COUNTRY_DATAFILE_EMBEDDED");
		$diroffile = getMultidirOutput(null, 'geoipmaxmind');
		if (file_exists($diroffile.'/'.$geoipfile)) {
			$file = dol_dir_list($diroffile, 'files', 0, $geoipfile);
			print ' ';
			print '<div class="inline-block valignmiddle marginrightonly"><a href="'.$documenturl.'?modulepart='.$modulepart.'&file='.urlencode($geoipfile).'">'.$geoipfile.'</a>'.$formfile->showPreview($file[0], $modulepart, $geoipfile, 0, '');
			print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removetermsofsale&modulepart='.$modulepart.'&token='.newToken().'">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
		}
	}

	print '</div>';
}

print '</td><td>';
if (getDolGlobalString('GEOIP_VERSION') == 'php') {
	print '<span class="opacitymedium">';
	print $langs->trans("Example").'<br>';
	print '/usr/local/share/GeoIP/GeoIP.dat<br>
/usr/share/GeoIP/GeoIP.dat<br>
/usr/share/GeoIP/GeoLite2-Country.mmdb';
	print '</span>';
}
print '</td></tr>';

print '</table>';
print '</div>';

print '<center>';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Save").'">';
print '</center>';

print "</form>\n";

print '<br>';

print '<div class="hideonsmartphone info">';
if (getDolGlobalString('GEOIP_VERSION') == 'php') {
	print $langs->trans("NoteOnPathLocation").'<br>';
}

$url1 = 'http://www.maxmind.com/en/city?rId=awstats';
$textoshow = $langs->trans("YouCanDownloadFreeDatFileTo", '{s1}');
$textoshow = str_replace('{s1}', '<a href="'.$url1.'" target="_blank" rel="noopener noreferrer external">'.$url1.'</a>', $textoshow);
print $textoshow;

print '<br>';

$url2 = 'http://www.maxmind.com/en/city?rId=awstats';
$textoshow = $langs->trans("YouCanDownloadAdvancedDatFileTo", '{s1}');
$textoshow = str_replace('{s1}', '<a href="'.$url2.'" target="_blank" rel="noopener noreferrer external">'.$url2.'</a>', $textoshow);
print $textoshow;

print '</div>';

if ($geoip) {
	print '<br>';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$ip = '24.24.24.24';

	print load_fiche_titre($langs->trans("TestGeoIPResult", $ip));

	print $ip.' -> ';
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
		print '<br>'.$langs->trans("CurrentIP").': '.$ip.' -> ';
		$result = dol_print_ip($ip, 1);
		if ($result) {
			print $result;
		} else {
			print $langs->trans("Error");
		}
	} else {
		print '<br>'.($isip == 2 ? $langs->trans("CurrentIP").': ' : '').$ip.' -> ';
		$result = dol_print_ip($ip, 1);
		if ($result) {
			print $result;
		} else {
			print $langs->trans("NotAPublicIp");
		}
	}

	$ip = GETPOST("iptotest");
	print '<br><input type="text class="width100" name="iptotest" id="iptotest" placeholder="'.dol_escape_htmltag($langs->trans("EnterAnIP")).'" value="'.$ip.'">';
	print '<input type="submit" class="width40 button small smallpaddingimp" value=" -> ">';
	if ($ip) {
		$result = dol_print_ip($ip, 1);
		if ($result) {
			print $result;
		} else {
			print $langs->trans("Error");
		}
	}

	print '</form>';

	$geoip->close();
}

// End of page
llxFooter();
$db->close();
