<?php
/* Copyright (C) 2005-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/blockedlog/admin/filecheck.php
 *  \brief      Page to check Dolibarr files integrity
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

$langs->load("admin");

$mode = GETPOST('mode', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * View
 */

@set_time_limit(300);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_filecheck');

print load_fiche_titre($langs->trans("FileCheckDolibarr"), '', 'title_setup');

print '<div class="opacitymedium hideonsmartphone justify">'.$langs->trans("FileCheckDesc");
if (isModEnabled('blockedlog')) {
	$s = $langs->trans("DataIntegrityDesc", '{s}');
	$s = str_replace('{s}', DOL_URL_ROOT.'/blockedlog/admin/blockedlog_list.php', $s);
	print '<br>'.$s;
}
print'</div><br><br>';

// Version
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Version").'</td><td></td></tr>'."\n";
$htmltooltip = '';
$htmltooltip .= $langs->trans("VersionLastInstall").': '.getDolGlobalString('MAIN_VERSION_LAST_INSTALL').'<br>'."\n";
$htmltooltip .= $langs->trans("VersionLastUpgrade").': '.getDolGlobalString('MAIN_VERSION_LAST_UPGRADE').'<br>'."\n";

print '<tr class="oddeven nohover"><td width="300">'.$langs->trans("VersionProgram").'</td><td>';
print '<span class="badge-text badge-secondary valignmiddle">'.DOL_VERSION.'</span>';
// If current version differs from last upgrade
if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')) {
	// Compare version with last install database version (upgrades never occurred)
	if (in_array(versioncompare(versiondolibarrarray(), preg_split('/[\-\.]/', getDolGlobalString('MAIN_VERSION_LAST_INSTALL'))), array(-2, -1, 1, 2))) {
		print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, getDolGlobalString('MAIN_VERSION_LAST_INSTALL')));
	}
} else {
	// Compare version with last upgrade database version
	if (in_array(versioncompare(versiondolibarrarray(), preg_split('/[\-\.]/', getDolGlobalString('MAIN_VERSION_LAST_UPGRADE'))), array(-2, -1, 1, 2))) {
		print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')));
	}
}
print ' '.$form->textwithpicto('', $htmltooltip);
print '</td></tr>'."\n";
print '</table>';
print '</div>';
print '<br>';


// Modified or missing files
$file_list = array('missing' => array(), 'updated' => array());

// Local file to compare to
$xmlshortfile = dol_sanitizeFileName(GETPOST('xmlshortfile', 'alpha') ? GETPOST('xmlshortfile', 'alpha') : 'filelist-'.DOL_VERSION.getDolGlobalString('MAIN_FILECHECK_LOCAL_SUFFIX').'.xml'.getDolGlobalString('MAIN_FILECHECK_LOCAL_EXT'));

$xmlfile = DOL_DOCUMENT_ROOT.'/install/'.$xmlshortfile;
if (!preg_match('/\.zip$/i', $xmlfile) && dol_is_file($xmlfile.'.zip')) {
	$xmlfile .= '.zip';
}

// Remote file to compare to
$xmlremote = GETPOST('xmlremote', 'alphanohtml');
if (empty($xmlremote) && getDolGlobalString('MAIN_FILECHECK_URL')) {
	$xmlremote = getDolGlobalString('MAIN_FILECHECK_URL');
}
$param = 'MAIN_FILECHECK_URL_'.DOL_VERSION;
if (empty($xmlremote) && getDolGlobalString($param)) {
	$xmlremote = getDolGlobalString($param);
}
if (empty($xmlremote)) {
	$xmlremote = 'https://www.dolibarr.org/files/stable/signatures/filelist-'.DOL_VERSION.'.xml';
}
if (!preg_match('/^https?:\/\//', $xmlremote)) {
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorURLMustStartWithHttp", $xmlremote), null, 'errors');
	$error++;
} elseif ($xmlremote && !preg_match('/\.xml$/', $xmlremote)) {
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorURLMustEndWith", $xmlremote, '.xml'), null, 'errors');
	$error++;
}

// Test if remote test is ok
$enableremotecheck = true;
if (preg_match('/beta|alpha|rc/i', DOL_VERSION) || getDolGlobalString('MAIN_ALLOW_INTEGRITY_CHECK_ON_UNSTABLE')) {
	$enableremotecheck = false;
}

print '<form name="check" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print img_picto('', 'search', 'class="pictofixedwidth"').$langs->trans("MakeIntegrityAnalysisFrom").'...<br><br>';

print '<div class="divsection">';
print '<!-- for a local check target=local&xmlshortfile=... -->'."\n";
if (dol_is_file($xmlfile)) {
	print '<input type="radio" name="target" id="checkboxlocal" value="local"'.((!GETPOST('target') || GETPOST('target') == 'local') ? 'checked="checked"' : '').'"> <label for="checkboxlocal">'.$langs->trans("LocalSignature").'</label> = ';
	print '<input name="xmlshortfile" class="flat minwidth400" value="'.dol_escape_htmltag($xmlshortfile).'" spellcheck="false">';
	print '<br>';
} else {
	print '<input type="radio" name="target" id="checkboxlocal" value="local"> <label for="checkboxlocal">'.$langs->trans("LocalSignature").' = ';
	print '<input name="xmlshortfile" class="flat minwidth400" value="'.dol_escape_htmltag($xmlshortfile).'" spellcheck="false">';
	print '<br><span class="warning paddingtop inline-block">'.$langs->trans("AvailableOnlyOnPackagedVersions").'</span></label>';
	print '<br>';
}

print '<br>';

print '<!-- for a remote target=remote&xmlremote=... -->'."\n";
if ($enableremotecheck) {
	print '<input type="radio" name="target" id="checkboxremote" value="remote"'.(GETPOST('target') == 'remote' ? 'checked="checked"' : '').'> <label for="checkboxremote">'.$langs->trans("RemoteSignature").'</label> = ';
	print '<input name="xmlremote" class="flat minwidth500" value="'.dol_escape_htmltag($xmlremote).'" spellcheck="false"><br>';
} else {
	print '<input type="radio" name="target" id="checkboxremote" value="remote" disabled="disabled"> '.$langs->trans("RemoteSignature").' = '.dol_escape_htmltag($xmlremote);
	if (!GETPOST('xmlremote')) {
		print ' <span class="warning">('.$langs->trans("FeatureAvailableOnlyOnStable").')</span>';
	}
	print '<br>';
}
print '</div>';

// Option unalterable log
print '<div class="center">';
if ($mysoc->country_code == 'FR') {
	print '<input type="checkbox" name="mode" id="mode" value="unalterable"'.($mode == 'unalterable' ? ' checked="checked"' : '').'>';
	print '<label for="mode" class="opacitymedium">'.$langs->trans("AnalyzeUnalterableScopeOnly", $langs->transnoentitiesnoconv("BlockedLog")).'</label><br>';
}
print '<input type="submit" name="check" class="button" value="'.$langs->trans("Check").'">';
print '</div>';
print '</form>';
print '<br>';
print '<br>';

if (GETPOST('target') == 'local') {
	if (dol_is_file($xmlfile)) {
		// If file is a zip file (.../filelist-x.y.z.xml.zip), we uncompress it before
		if (preg_match('/\.zip$/i', $xmlfile)) {
			dol_mkdir($conf->admin->dir_temp);
			$xmlfilenew = preg_replace('/\.zip$/i', '', $xmlfile);
			$result = dol_uncompress($xmlfile, $conf->admin->dir_temp);
			if (empty($result['error'])) {
				$xmlfile = $conf->admin->dir_temp.'/'.basename($xmlfilenew);
			} else {
				print $langs->trans('FailedToUncompressFile').': '.$xmlfile;
				$error++;
			}
		}
		$xml = simplexml_load_file($xmlfile);
		if ($xml === false) {
			print '<div class="warning">'.$langs->trans('XmlCorrupted').': '.$xmlfile.'</span>';
			$error++;
		}
	} else {
		print '<div class="warning">'.$langs->trans('XmlNotFound').': '.$xmlfile.'</span>';
		$error++;
	}
}
if (GETPOST('target') == 'remote') {
	$xmlarray = getURLContent($xmlremote, 'GET', '', 1, array(), array('http', 'https'), 0);	// Accept http or https links on external remote server only. Same is used into api_setup.class.php.

	// Return array('content'=>response,'curl_error_no'=>errno,'curl_error_msg'=>errmsg...)
	if (!$xmlarray['curl_error_no'] && $xmlarray['http_code'] != 400 && $xmlarray['http_code'] != 404) {
		$xmlfile = $xmlarray['content'];
		//print "xmlfilestart".$xmlfile."xmlfileend";
		if (LIBXML_VERSION < 20900) {
			// Avoid load of external entities (security problem).
			// Required only if LIBXML_VERSION < 20900
			// @phan-suppress-next-line PhanDeprecatedFunctionInternal
			libxml_disable_entity_loader(true);
		}

		$xml = simplexml_load_string($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
	} else {
		$errormsg = $langs->trans('XmlNotFound').': '.$xmlremote.' - '.$xmlarray['http_code'].(($xmlarray['http_code'] == 400 && $xmlarray['content']) ? ' '.$xmlarray['content'] : '').' '.$xmlarray['curl_error_no'].' '.$xmlarray['curl_error_msg'];
		setEventMessages($errormsg, null, 'errors');
		$error++;
	}
}


if (empty($error) && !empty($xml)) {
	$checksumconcat = array();
	$file_list = array();
	$out = '';

	//$algo = 'md5';		// For v22-
	$algo = 'sha256';		// For v23+

	// Forced constants
	if (is_object($xml->dolibarr_constants[0]) || $mode == 'unalterable') {
		$out .= load_fiche_titre($langs->trans("ForcedConstants"));

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder">';
		$out .= '<tr class="liste_titre">';
		$out .= '<td>#</td>';
		$out .= '<td>'.$langs->trans("Parameter").'</td>';
		$out .= '<td class="center">'.$langs->trans("ExpectedValue").'</td>';
		$out .= '<td class="center">'.$langs->trans("CurrentValue").'</td>';
		$out .= '</tr>'."\n";

		$i = 0;

		if ($mode == 'unalterable') {
			$i++;

			$out .= '<tr class="oddeven">';
			$out .= '<td>'.$i.'</td>'."\n";
			$out .= '<td>'.$langs->trans("Country").'</td>'."\n";
			$out .= '<td class="center"><span class="opacitymedium">'.$langs->trans("YourCountryCode").'</span></td>'."\n";
			$out .= '<td class="center">'.$mysoc->country_code.'</td>'."\n";
			$out .= "</tr>\n";

			$i++;

			$out .= '<tr class="oddeven">';
			$out .= '<td>'.$i.'</td>'."\n";
			$out .= '<td>'.$langs->trans("StatusOfModule", $langs->transnoentitiesnoconv("BlockedLog")).'</td>'."\n";
			$out .= '<td class="center">'.$langs->trans("Enabled").'</td>'."\n";
			$out .= '<td class="center">';
			$out .= isModEnabled('blockedlog') ? '<span class="ok">'.$langs->trans("Enabled").'</span>' : '<span class="warning">'.$langs->trans("Disabled").'</span>';

			include_once DOL_DOCUMENT_ROOT.'/core/modules/modBlockedLog.class.php';
			$objMod = new modBlockedLog($db);
			/*$modulename = $objMod->getName();
			$moduledesc = $objMod->getDesc();
			$moduleauthor = $objMod->getPublisher();
			$moduledir = strtolower(preg_replace('/^mod/i', '', get_class($objMod)));*/
			$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($objMod)));

			$htmltooltip = '<span class="opacitymedium">'.$langs->trans("LastActivationDate").':</span> ';
			if (getDolGlobalString($const_name)) {
				$htmltooltip .= dol_print_date($objMod->getLastActivationDate(), 'dayhour');
			} else {
				$htmltooltip .= $langs->trans("Disabled");
			}
			$tmp = $objMod->getLastActivationInfo();
			$authorid = (empty($tmp['authorid']) ? '' : $tmp['authorid']);
			if ($authorid > 0) {
				$tmpuser = new User($db);
				$tmpuser->fetch($authorid);
				$htmltooltip .= '<br><span class="opacitymedium">'.$langs->trans("LastActivationAuthor").':</span> ';
				$htmltooltip .= $tmpuser->getNomUrl(0, 'nolink', -1, 1);
			}
			$ip = (empty($tmp['ip']) ? '' : $tmp['ip']);
			if ($ip) {
				$htmltooltip .= '<br><span class="opacitymedium">'.$langs->trans("LastActivationIP").':</span> ';
				$htmltooltip .= $ip;
			}
			$lastactivationversion = (empty($tmp['lastactivationversion']) ? '' : $tmp['lastactivationversion']);
			if ($lastactivationversion && $lastactivationversion != 'dolibarr') {
				$htmltooltip .= '<br><span class="opacitymedium">'.$langs->trans("LastActivationVersion").':</span> ';
				$htmltooltip .= $lastactivationversion;
			}

			$out .= $form->textwithpicto('', $htmltooltip);

			$out .= "</td>\n";
			$out .= "</tr>\n";
		}

		foreach ($xml->dolibarr_constants[0]->constant as $constant) {    // $constant is a simpleXMLElement
			$constname = (string) $constant['name'];
			$constvalue = (string) $constant;

			$constvalue = (empty($constvalue) ? '0' : $constvalue);
			// Value found
			$value = '';
			if ($constname && getDolGlobalString($constname) != '') {
				$value = getDolGlobalString($constname);
			}
			$valueforchecksum = (empty($value) ? '0' : $value);

			$checksumconcat[$constname] = $valueforchecksum;

			$i++;

			$out .= '<tr class="oddeven">';
			$out .= '<td>'.$i.'</td>'."\n";
			$out .= '<td>'.dol_escape_htmltag($constname).'</td>'."\n";
			$out .= '<td class="center">'.dol_escape_htmltag($constvalue).'</td>'."\n";
			$out .= '<td class="center">'.dol_escape_htmltag($valueforchecksum).'</td>'."\n";
			$out .= "</tr>\n";
		}

		if ($i == 0 && $mode != 'unalterable') {
			$out .= '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';

		$out .= '<br>';
	}


	$onlymodifiedorremoved = 0;
	if ($mode == 'unalterable') {
		$listoffilestoanalyze = $xml->dolibarr_unalterable_files[0];
		$onlymodifiedorremoved = 1;
	} else {
		$listoffilestoanalyze = $xml->dolibarr_htdocs_dir[0];
		$onlymodifiedorremoved = 0;
	}


	// Scan htdocs
	if (is_object($listoffilestoanalyze)) {
		// @phan-suppress-next-line PhanTypeArraySuspicious
		$includecustom = (empty($listoffilestoanalyze['includecustom']) ? 0 : $listoffilestoanalyze['includecustom']);

		// Define qualified files (must be same than into generate_filelist_xml.php and in api_setup.class.php)
		$regextoinclude = '\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|bak|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
		$regextoexclude = '('.($includecustom ? '' : 'custom|').'documents|conf|install|dejavu-fonts-ttf-.*|public\/test|sabre\/sabre\/.*\/tests|Shared\/PCLZip|nusoap\/lib\/Mail|php\/example|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$'; // Exclude dirs
		$scanfiles = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude);

		// Fill file_list with files in signature, new files, modified files
		getFilesUpdated($file_list, $listoffilestoanalyze, '', DOL_DOCUMENT_ROOT, $checksumconcat); // Fill array $file_list
		'@phan-var-force array{insignature:string[],missing?:array<array{filename:string,expectedhash:string,expectedsize:string,algo:string}>,updated:array<array{filename:string,expectedhash:string,expectedsize:string,hash:string,algo:string}>} $file_list';

		// Complete with list of new files into $file_list['added']
		if (empty($onlymodifiedorremoved)) {
			foreach ($scanfiles as $valfile) {
				$tmprelativefilename = preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT, '/').'/', '', $valfile['fullname']);
				if (!in_array($tmprelativefilename, $file_list['insignature'])) {
					$hashnewfile = @hash_file($algo, $valfile['fullname']); // Can fails if we don't have permission to open/read file
					$file_list['added'][] = array('filename' => $tmprelativefilename, 'hash' => $hashnewfile, 'algo' => $algo);
				}
			}
		}

		// Files missing
		$out .= load_fiche_titre($langs->trans("FilesMissing"));

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder">';
		$out .= '<tr class="liste_titre">';
		$out .= '<td>#</td>';
		$out .= '<td>'.$langs->trans("Filename").'</td>';
		$out .= '<td class="right">'.$langs->trans("ExpectedSize").'</td>';
		$out .= '<td class="center">'.$langs->trans("ExpectedChecksum").'</td>';
		$out .= '</tr>'."\n";
		$tmpfilelist = dol_sort_array($file_list['missing'], 'filename');
		if (is_array($tmpfilelist) && count($tmpfilelist)) {
			$i = 0;
			foreach ($tmpfilelist as $file) {
				$i++;
				$out .= '<tr class="oddeven">';
				$out .= '<td>'.$i.'</td>'."\n";
				$out .= '<td>'.dol_escape_htmltag($file['filename']).'</td>'."\n";
				$out .= '<td class="right">';
				if (!empty($file['expectedsize'])) {
					$out .= dol_print_size((int) $file['expectedsize']);
				}
				$out .= '</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['expectedhash']).'</td>'."\n";
				$out .= "</tr>\n";
			}
		} else {
			$out .= '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';

		$out .= '<br>';

		// Files modified
		$out .= load_fiche_titre($langs->trans("FilesModified"));

		$totalsize = 0;
		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder">';
		$out .= '<tr class="liste_titre">';
		$out .= '<td>#</td>';
		$out .= '<td>'.$langs->trans("Filename").'</td>';
		$out .= '<td class="center">'.$langs->trans("ExpectedChecksum").'</td>';
		$out .= '<td class="center">'.$langs->trans("CurrentChecksum").'</td>';
		$out .= '<td class="right">'.$langs->trans("ExpectedSize").'</td>';
		$out .= '<td class="right">'.$langs->trans("CurrentSize").'</td>';
		$out .= '<td class="right">'.$langs->trans("DateModification").'</td>';
		$out .= '</tr>'."\n";
		$tmpfilelist2 = dol_sort_array($file_list['updated'], 'filename');
		if (is_array($tmpfilelist2) && count($tmpfilelist2)) {
			$i = 0;
			foreach ($tmpfilelist2 as $file) {
				$i++;
				$out .= '<tr class="oddeven">';
				$out .= '<td>'.$i.'</td>'."\n";
				$out .= '<td>'.dol_escape_htmltag($file['filename']).'</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['expectedhash']).'</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['hash']).'</td>'."\n";
				$out .= '<td class="right">';
				if ($file['expectedsize']) {
					$out .= dol_print_size((int) $file['expectedsize']);
				}
				$out .= '</td>'."\n";
				$size = dol_filesize(DOL_DOCUMENT_ROOT.'/'.$file['filename']);
				$totalsize += $size;
				$out .= '<td class="right">'.dol_print_size($size).'</td>'."\n";
				$out .= '<td class="right">'.dol_print_date(dol_filemtime(DOL_DOCUMENT_ROOT.'/'.$file['filename']), 'dayhour').'</td>'."\n";
				$out .= "</tr>\n";
			}
			$out .= '<tr class="liste_total">';
			$out .= '<td></td>'."\n";
			$out .= '<td>'.$langs->trans("Total").'</td>'."\n";
			$out .= '<td class="center"></td>'."\n";
			$out .= '<td class="center"></td>'."\n";
			$out .= '<td class="center"></td>'."\n";
			$out .= '<td class="right">'.dol_print_size($totalsize).'</td>'."\n";
			$out .= '<td class="right"></td>'."\n";
			$out .= "</tr>\n";
		} else {
			$out .= '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';

		$out .= '<br>';

		// Files added
		if (empty($onlymodifiedorremoved)) {
			$out .= load_fiche_titre($langs->trans("FilesAdded"));

			$totalsize = 0;
			$out .= '<div class="div-table-responsive-no-min">';
			$out .= '<table class="noborder">';
			$out .= '<tr class="liste_titre">';
			$out .= '<td>#</td>';
			$out .= '<td>'.$langs->trans("Filename").'</td>';
			$out .= '<td class="center">'.$langs->trans("ExpectedChecksum").'</td>';
			$out .= '<td class="center">'.$langs->trans("CurrentChecksum").'</td>';
			$out .= '<td class="right">'.$langs->trans("Size").'</td>';
			$out .= '<td class="right">'.$langs->trans("DateModification").'</td>';
			$out .= '</tr>'."\n";
			$tmpfilelist3 = dol_sort_array($file_list['added'], 'filename');
			if (is_array($tmpfilelist3) && count($tmpfilelist3)) {
				$i = 0;
				foreach ($tmpfilelist3 as $file) {
					$i++;
					$out .= '<tr class="oddeven">';
					$out .= '<td>'.$i.'</td>'."\n";
					$out .= '<td>'.dol_escape_htmltag($file['filename']);
					if (!preg_match('/^win/i', PHP_OS)) {
						$htmltext = $langs->trans("YouCanDeleteFileOnServerWith", 'rm '.DOL_DOCUMENT_ROOT.$file['filename']); // The slash is included int file['filename']
						$out .= ' '.$form->textwithpicto('', $htmltext, 1, 'help', '', 0, 2, 'helprm'.$i);
					}
					$out .= '</td>'."\n";
					$out .= '<td class="center">'.dol_escape_htmltag((string) $file['expectedhash']).'</td>'."\n";  // @phan-suppress-current-line PhanTypeInvalidDimOffset
					$out .= '<td class="center">'.dol_escape_htmltag($file['hash']).'</td>'."\n";
					$size = dol_filesize(DOL_DOCUMENT_ROOT.'/'.$file['filename']);
					$totalsize += $size;
					$out .= '<td class="right">'.dol_print_size($size).'</td>'."\n";
					$out .= '<td class="right nowraponall">'.dol_print_date(dol_filemtime(DOL_DOCUMENT_ROOT.'/'.$file['filename']), 'dayhour').'</td>'."\n";
					$out .= "</tr>\n";
				}
				$out .= '<tr class="liste_total">';
				$out .= '<td></td>'."\n";
				$out .= '<td>'.$langs->trans("Total").'</td>'."\n";
				$out .= '<td class="center"></td>'."\n";
				$out .= '<td class="center"></td>'."\n";
				$out .= '<td class="right">'.dol_print_size($totalsize).'</td>'."\n";
				$out .= '<td class="right"></td>'."\n";
				$out .= "</tr>\n";
			} else {
				$out .= '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}
			$out .= '</table>';
			$out .= '</div>';
		}
	} else {
		print '<div class="error">';
		print 'Error: Failed to found <b>dolibarr_htdocs_dir</b> into content of XML file:<br>'.dol_escape_htmltag(dol_trunc($xmlfile, 500));
		print '</div><br>';
		$error++;
	}


	// Scan scripts
	/*
	if (is_object($xml->dolibarr_scripts_dir[0])) {
		$file_list = array();
		$ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0], '', ???, $checksumconcat);		// Fill array $file_list
		'@phan-var-force array{insignature:string[],missing?:array<array{filename:string,expectedhash:string,expectedsize:string,algo:string}>,updated:array<array{filename:string,expectedhash:string,expectedsize:string,hash:string,algo:string}>} $file_list';
	}*/


	// Section Globalchecksum
	asort($checksumconcat); // Sort list of checksum

	$checksumget = hash($algo, implode(',', $checksumconcat));

	if ($mode == 'unalterable') {
		$nameofsection = 'dolibarr_unalterable_files_checksum';
		$checksumtoget = trim((string) $xml->dolibarr_unalterable_files_checksum);
	} else {
		$nameofsection = 'dolibarr_htdocs_dir_checksum';
		$checksumtoget = trim((string) $xml->dolibarr_htdocs_dir_checksum);
	}

	$resultcomment = '';

	$outexpectedchecksum = ($checksumtoget ? $checksumtoget : $langs->trans("Unknown"));
	$outcurrentchecksumtext = '';
	if ($checksumget == $checksumtoget) {
		if (empty($onlymodifiedorremoved) && !empty($file_list['added'])) {
			$resultcode = 'warning';
			$resultcomment = 'FileIntegrityIsOkButFilesWereAdded';
			$outcurrentchecksum = $checksumget;
			$outcurrentchecksumtext .= img_picto('', 'tick').' <span class="'.$resultcode.'">'.$langs->trans($resultcomment).'</span>';
		} else {
			$resultcode = 'ok';
			$resultcomment = 'Success';
			$outcurrentchecksum = '<span class="'.$resultcode.'" title="Checksum of all current checksums concatenated separated by a comma">'.$checksumget.'</span>';
			$outcurrentchecksumtext.= img_picto('', 'tick').' <span class="badge badge-status4 badge-status '.$resultcode.'">'.$langs->trans($resultcomment).'</span>';
		}
	} else {
		$resultcode = 'error';
		$resultcomment = 'FileIntegrityIsKO';
		$outcurrentchecksum = '<span class="'.$resultcode.'" title="Checksum of all current checksums concatenated separated by a comma">'.$checksumget.'</span>';
		$outcurrentchecksumtext .= img_picto('', 'error').' <span class="'.$resultcode.'">'.$langs->trans($resultcomment).'</span>';
	}

	// Show warning
	if (empty($tmpfilelist) && empty($tmpfilelist2) && empty($tmpfilelist3) && $resultcode == 'ok') {
		setEventMessages($langs->trans("FileIntegrityIsStrictlyConformedWithReference"), null, 'mesgs');
	} else {
		if ($resultcode == 'warning') {
			setEventMessages($langs->trans($resultcomment), null, 'warnings');
		} else {
			setEventMessages($langs->trans("FileIntegritySomeFilesWereRemovedOrModified"), null, 'errors');
		}
	}

	$outforlistoffiles = '';
	if ($mode == 'unalterable') {
		print load_fiche_titre($langs->trans("UnalterableFilesChecksum"));

		// Print list of files
		$outforlistoffiles = '<a href="#" onclick="console.log(\'Click\'); jQuery(\'#listofunalterablefiles\').toggle(); return false;">'.$langs->trans("ShowListOfFiles").'</a><br>';
		$outforlistoffiles .= '<textarea id="listofunalterablefiles" class="hideobject quatrevingtpercent" rows="12">';
		$i = 0;
		foreach ($listoffilestoanalyze as $dirtoanalyze) {
			$entry = array();
			if (!empty($dirtoanalyze->md5file)) {
				$entry = $dirtoanalyze->md5file;
				$algo = 'md5';
			} elseif (!empty($dirtoanalyze->sha256file)) {
				$entry = $dirtoanalyze->sha256file;
				$algo = 'sha256';
			}

			foreach ($entry as $filetoanalyze) {
				if ($i) {
					$outforlistoffiles .= "\n";
				}
				$outforlistoffiles .= (string) $dirtoanalyze['name'];
				$outforlistoffiles .= '/';
				$outforlistoffiles .= (string) $filetoanalyze['name'];
				$i++;
			}
		}
		$outforlistoffiles .= '</textarea>';
		$outforlistoffiles .= '<br>';
	} else {
		print load_fiche_titre($langs->trans("GlobalChecksum"));
	}


	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ExpectedChecksum").'</td>';
	print '<td>'.$langs->trans("CurrentChecksum").'</td>';
	print '</tr>'."\n";

	print '<tr><td>';
	print '<span title="Checksum of all checksums in file separated by a comma and saved into '.$nameofsection.'">';
	print $outexpectedchecksum;
	print '</span>';
	print '</td><td>';
	print $outcurrentchecksum;
	print '</td>';
	print '</tr>';
	print '</table>';
	print $outcurrentchecksumtext.'<br>';

	print '<br>';
	print $outforlistoffiles;
	print '<br><br>';


	// Output detail
	print $out;
}

// End of page
llxFooter();
$db->close();

exit($error);
