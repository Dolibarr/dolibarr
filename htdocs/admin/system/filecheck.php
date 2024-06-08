<?php
/* Copyright (C) 2005-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2019  Frederic France         <frederic.france@netlogic.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
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
 *  \file       htdocs/admin/system/filecheck.php
 *  \brief      Page to check Dolibarr files integrity
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

$langs->load("admin");

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

print '<span class="opacitymedium">'.$langs->trans("FileCheckDesc").'</span><br><br>';

// Version
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Version").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("VersionLastInstall").'</td><td>'.getDolGlobalString('MAIN_VERSION_LAST_INSTALL').'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("VersionLastUpgrade").'</td><td>'.getDolGlobalString('MAIN_VERSION_LAST_UPGRADE').'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("VersionProgram").'</td><td>'.DOL_VERSION;
// If current version differs from last upgrade
if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')) {
	// Compare version with last install database version (upgrades never occurred)
	if (DOL_VERSION != getDolGlobalString('MAIN_VERSION_LAST_INSTALL')) {
		print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, getDolGlobalString('MAIN_VERSION_LAST_INSTALL')));
	}
} else {
	// Compare version with last upgrade database version
	if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_UPGRADE) {
		print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')));
	}
}
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
if ($xmlremote && !preg_match('/^https?:\/\//', $xmlremote)) {
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
$enableremotecheck = true;

print '<form name="check" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print $langs->trans("MakeIntegrityAnalysisFrom").':<br>';
print '<!-- for a local check target=local&xmlshortfile=... -->'."\n";
if (dol_is_file($xmlfile)) {
	print '<input type="radio" name="target" id="checkboxlocal" value="local"'.((!GETPOST('target') || GETPOST('target') == 'local') ? 'checked="checked"' : '').'"> <label for="checkboxlocal">'.$langs->trans("LocalSignature").'</label> = ';
	print '<input name="xmlshortfile" class="flat minwidth400" value="'.dol_escape_htmltag($xmlshortfile).'">';
	print '<br>';
} else {
	print '<input type="radio" name="target" id="checkboxlocal" value="local"> <label for="checkboxlocal">'.$langs->trans("LocalSignature").' = ';
	print '<input name="xmlshortfile" class="flat minwidth400" value="'.dol_escape_htmltag($xmlshortfile).'">';
	print ' <span class="warning">('.$langs->trans("AvailableOnlyOnPackagedVersions").')</span></label>';
	print '<br>';
}
print '<!-- for a remote target=remote&xmlremote=... -->'."\n";
if ($enableremotecheck) {
	print '<input type="radio" name="target" id="checkboxremote" value="remote"'.(GETPOST('target') == 'remote' ? 'checked="checked"' : '').'> <label for="checkboxremote">'.$langs->trans("RemoteSignature").'</label> = ';
	print '<input name="xmlremote" class="flat minwidth500" value="'.dol_escape_htmltag($xmlremote).'"><br>';
} else {
	print '<input type="radio" name="target" id="checkboxremote" value="remote" disabled="disabled"> '.$langs->trans("RemoteSignature").' = '.dol_escape_htmltag($xmlremote);
	if (!GETPOST('xmlremote')) {
		print ' <span class="warning">('.$langs->trans("FeatureAvailableOnlyOnStable").')</span>';
	}
	print '<br>';
}
print '<br><div class="center"><input type="submit" name="check" class="button" value="'.$langs->trans("Check").'"></div>';
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
	if (!$xmlarray['curl_error_no'] && $xmlarray['http_code'] != '400' && $xmlarray['http_code'] != '404') {
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

	// Forced constants
	if (is_object($xml->dolibarr_constants[0])) {
		$out .= load_fiche_titre($langs->trans("ForcedConstants"));

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder">';
		$out .= '<tr class="liste_titre">';
		$out .= '<td>#</td>';
		$out .= '<td>'.$langs->trans("Constant").'</td>';
		$out .= '<td class="center">'.$langs->trans("ExpectedValue").'</td>';
		$out .= '<td class="center">'.$langs->trans("Value").'</td>';
		$out .= '</tr>'."\n";

		$i = 0;
		foreach ($xml->dolibarr_constants[0]->constant as $constant) {    // $constant is a simpleXMLElement
			$constname = $constant['name'];
			$constvalue = (string) $constant;
			$constvalue = (empty($constvalue) ? '0' : $constvalue);
			// Value found
			$value = '';
			if ($constname && getDolGlobalString($constname) != '') {
				$value = getDolGlobalString($constname);
			}
			$valueforchecksum = (empty($value) ? '0' : $value);

			$checksumconcat[] = $valueforchecksum;

			$i++;
			$out .= '<tr class="oddeven">';
			$out .= '<td>'.$i.'</td>'."\n";
			$out .= '<td>'.dol_escape_htmltag($constname).'</td>'."\n";
			$out .= '<td class="center">'.dol_escape_htmltag($constvalue).'</td>'."\n";
			$out .= '<td class="center">'.dol_escape_htmltag($valueforchecksum).'</td>'."\n";
			$out .= "</tr>\n";
		}

		if ($i == 0) {
			$out .= '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';

		$out .= '<br>';
	}

	// Scan htdocs
	if (is_object($xml->dolibarr_htdocs_dir[0])) {
		//var_dump($xml->dolibarr_htdocs_dir[0]['includecustom']);exit;
		$includecustom = (empty($xml->dolibarr_htdocs_dir[0]['includecustom']) ? 0 : $xml->dolibarr_htdocs_dir[0]['includecustom']);

		// Define qualified files (must be same than into generate_filelist_xml.php and in api_setup.class.php)
		$regextoinclude = '\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|bak|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
		$regextoexclude = '('.($includecustom ? '' : 'custom|').'documents|conf|install|dejavu-fonts-ttf-.*|public\/test|sabre\/sabre\/.*\/tests|Shared\/PCLZip|nusoap\/lib\/Mail|php\/example|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$'; // Exclude dirs
		$scanfiles = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude);

		// Fill file_list with files in signature, new files, modified files
		$ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0], '', DOL_DOCUMENT_ROOT, $checksumconcat); // Fill array $file_list
		// Complete with list of new files
		foreach ($scanfiles as $keyfile => $valfile) {
			$tmprelativefilename = preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT, '/').'/', '', $valfile['fullname']);
			if (!in_array($tmprelativefilename, $file_list['insignature'])) {
				$md5newfile = @md5_file($valfile['fullname']); // Can fails if we don't have permission to open/read file
				$file_list['added'][] = array('filename' => $tmprelativefilename, 'md5' => $md5newfile);
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
					$out .= dol_print_size($file['expectedsize']);
				}
				$out .= '</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['expectedmd5']).'</td>'."\n";
				$out .= "</tr>\n";
			}
		} else {
			$out .= '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
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
				$out .= '<td class="center">'.dol_escape_htmltag($file['expectedmd5']).'</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['md5']).'</td>'."\n";
				$out .= '<td class="right">';
				if ($file['expectedsize']) {
					$out .= dol_print_size($file['expectedsize']);
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
			$out .= '<tr class="oddeven"><td colspan="7" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';

		$out .= '<br>';

		// Files added
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
				$out .= '<td class="center">'.dol_escape_htmltag($file['expectedmd5']).'</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['md5']).'</td>'."\n";
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
			$out .= '<td class="right">'.dol_print_size($totalsize).'</td>'."\n";
			$out .= '<td class="right"></td>'."\n";
			$out .= "</tr>\n";
		} else {
			$out .= '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';
	} else {
		print '<div class="error">';
		print 'Error: Failed to found <b>dolibarr_htdocs_dir</b> into content of XML file:<br>'.dol_escape_htmltag(dol_trunc($xmlfile, 500));
		print '</div><br>';
		$error++;
	}


	// Scan scripts
	/*
	if (is_object($xml->dolibarr_script_dir[0]))
	{
		$file_list = array();
		$ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0], '', ???, $checksumconcat);		// Fill array $file_list
	}*/


	asort($checksumconcat); // Sort list of checksum
	//var_dump($checksumconcat);
	$checksumget = md5(implode(',', $checksumconcat));
	$checksumtoget = trim((string) $xml->dolibarr_htdocs_dir_checksum);

	//var_dump(count($file_list['added']));
	//var_dump($checksumget);
	//var_dump($checksumtoget);
	//var_dump($checksumget == $checksumtoget);

	$resultcomment = '';

	$outexpectedchecksum = ($checksumtoget ? $checksumtoget : $langs->trans("Unknown"));
	if ($checksumget == $checksumtoget) {
		if (is_array($file_list['added']) && count($file_list['added'])) {
			$resultcode = 'warning';
			$resultcomment = 'FileIntegrityIsOkButFilesWereAdded';
			$outcurrentchecksum = $checksumget.' - <span class="'.$resultcode.'">'.$langs->trans($resultcomment).'</span>';
		} else {
			$resultcode = 'ok';
			$resultcomment = 'Success';
			$outcurrentchecksum = '<span class="'.$resultcode.'">'.$checksumget.'</span>';
		}
	} else {
		$resultcode = 'error';
		$resultcomment = 'Error';
		$outcurrentchecksum = '<span class="'.$resultcode.'">'.$checksumget.'</span>';
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

	print load_fiche_titre($langs->trans("GlobalChecksum"));
	print $langs->trans("ExpectedChecksum").' = '.$outexpectedchecksum.'<br>';
	print $langs->trans("CurrentChecksum").' = '.$outcurrentchecksum;

	print '<br>';
	print '<br>';

	// Output detail
	print $out;
}

// End of page
llxFooter();
$db->close();

exit($error);
