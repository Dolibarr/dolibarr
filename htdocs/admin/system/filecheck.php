<?php
/* Copyright (C) 2005-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2019  Frederic France         <frederic.france@netlogic.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

$langs->load("admin");

if (!$user->admin)
	accessforbidden();

$error = 0;


/*
 * View
 */

@set_time_limit(300);

llxHeader();

print load_fiche_titre($langs->trans("FileCheckDolibarr"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("FileCheckDesc").'</span><br><br>';

// Version
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Version").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("VersionLastInstall").'</td><td>'.$conf->global->MAIN_VERSION_LAST_INSTALL.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("VersionLastUpgrade").'</td><td>'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("VersionProgram").'</td><td>'.DOL_VERSION;
// If current version differs from last upgrade
if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE)) {
	// Compare version with last install database version (upgrades never occured)
	if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_INSTALL)
		print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, $conf->global->MAIN_VERSION_LAST_INSTALL));
} else {
	// Compare version with last upgrade database version
	if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_UPGRADE)
		print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired", DOL_VERSION, $conf->global->MAIN_VERSION_LAST_UPGRADE));
}
print '</td></tr>'."\n";
print '</table>';
print '</div>';
print '<br>';


// Modified or missing files
$file_list = array('missing' => array(), 'updated' => array());

// Local file to compare to
$xmlshortfile = GETPOST('xmlshortfile', 'alpha') ?GETPOST('xmlshortfile', 'alpha') : '/install/filelist-'.DOL_VERSION.(empty($conf->global->MAIN_FILECHECK_LOCAL_SUFFIX) ? '' : $conf->global->MAIN_FILECHECK_LOCAL_SUFFIX).'.xml'.(empty($conf->global->MAIN_FILECHECK_LOCAL_EXT) ? '' : $conf->global->MAIN_FILECHECK_LOCAL_EXT);
$xmlfile = DOL_DOCUMENT_ROOT.$xmlshortfile;
// Remote file to compare to
$xmlremote = GETPOST('xmlremote');
if (empty($xmlremote) && !empty($conf->global->MAIN_FILECHECK_URL)) $xmlremote = $conf->global->MAIN_FILECHECK_URL;
$param = 'MAIN_FILECHECK_URL_'.DOL_VERSION;
if (empty($xmlremote) && !empty($conf->global->$param)) $xmlremote = $conf->global->$param;
if (empty($xmlremote)) $xmlremote = 'https://www.dolibarr.org/files/stable/signatures/filelist-'.DOL_VERSION.'.xml';


// Test if remote test is ok
$enableremotecheck = true;
if (preg_match('/beta|alpha|rc/i', DOL_VERSION) || !empty($conf->global->MAIN_ALLOW_INTEGRITY_CHECK_ON_UNSTABLE)) $enableremotecheck = false;
$enableremotecheck = true;

print '<form name="check" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print $langs->trans("MakeIntegrityAnalysisFrom").':<br>';
print '<!-- for a local check target=local&xmlshortfile=... -->'."\n";
if (dol_is_file($xmlfile))
{
	print '<input type="radio" name="target" value="local"'.((!GETPOST('target') || GETPOST('target') == 'local') ? 'checked="checked"' : '').'"> '.$langs->trans("LocalSignature").' = ';
	print '<input name="xmlshortfile" class="flat minwidth400" value="'.dol_escape_htmltag($xmlshortfile).'">';
	print '<br>';
} else {
	print '<input type="radio" name="target" value="local"> '.$langs->trans("LocalSignature").' = ';
	print '<input name="xmlshortfile" class="flat minwidth400" value="'.dol_escape_htmltag($xmlshortfile).'">';
	print ' <span class="warning">('.$langs->trans("AvailableOnlyOnPackagedVersions").')</span>';
	print '<br>';
}
print '<!-- for a remote target=remote&xmlremote=... -->'."\n";
if ($enableremotecheck)
{
	print '<input type="radio" name="target" value="remote"'.(GETPOST('target') == 'remote' ? 'checked="checked"' : '').'> '.$langs->trans("RemoteSignature").' = ';
	print '<input name="xmlremote" class="flat minwidth400" value="'.dol_escape_htmltag($xmlremote).'"><br>';
} else {
	print '<input type="radio" name="target" value="remote" disabled="disabled"> '.$langs->trans("RemoteSignature").' = '.$xmlremote;
	if (!GETPOST('xmlremote')) print ' <span class="warning">('.$langs->trans("FeatureAvailableOnlyOnStable").')</span>';
	print '<br>';
}
print '<br><div class="center"><input type="submit" name="check" class="button" value="'.$langs->trans("Check").'"></div>';
print '</form>';
print '<br>';
print '<br>';

if (GETPOST('target') == 'local')
{
	if (dol_is_file($xmlfile))
	{
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
	} else {
		print $langs->trans('XmlNotFound').': '.$xmlfile;
		$error++;
	}
}
if (GETPOST('target') == 'remote')
{
	$xmlarray = getURLContent($xmlremote);

	// Return array('content'=>response,'curl_error_no'=>errno,'curl_error_msg'=>errmsg...)
	if (!$xmlarray['curl_error_no'] && $xmlarray['http_code'] != '400' && $xmlarray['http_code'] != '404')
	{
		$xmlfile = $xmlarray['content'];
		//print "xmlfilestart".$xmlfile."xmlfileend";
		$xml = simplexml_load_string($xmlfile);
	} else {
		$errormsg = $langs->trans('XmlNotFound').': '.$xmlremote.' - '.$xmlarray['http_code'].' '.$xmlarray['curl_error_no'].' '.$xmlarray['curl_error_msg'];
		setEventMessages($errormsg, null, 'errors');
		$error++;
	}
}


if (!$error && $xml)
{
	$checksumconcat = array();
	$file_list = array();
	$out = '';

	// Forced constants
	if (is_object($xml->dolibarr_constants[0]))
	{
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
		foreach ($xml->dolibarr_constants[0]->constant as $constant)    // $constant is a simpleXMLElement
		{
			$constname = $constant['name'];
			$constvalue = (string) $constant;
			$constvalue = (empty($constvalue) ? '0' : $constvalue);
			// Value found
			$value = '';
			if ($constname && $conf->global->$constname != '') $value = $conf->global->$constname;
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

		if ($i == 0)
		{
			$out .= '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';

		$out .= '<br>';
	}

	// Scan htdocs
	if (is_object($xml->dolibarr_htdocs_dir[0]))
	{
		//var_dump($xml->dolibarr_htdocs_dir[0]['includecustom']);exit;
		$includecustom = (empty($xml->dolibarr_htdocs_dir[0]['includecustom']) ? 0 : $xml->dolibarr_htdocs_dir[0]['includecustom']);

		// Defined qualified files (must be same than into generate_filelist_xml.php)
		$regextoinclude = '\.(php|php3|php4|php5|phtml|phps|phar|inc|css|scss|html|xml|js|json|tpl|jpg|jpeg|png|gif|ico|sql|lang|txt|yml|md|mp3|mp4|wav|mkv|z|gz|zip|rar|tar|less|svg|eot|woff|woff2|ttf|manifest)$';
		$regextoexclude = '('.($includecustom ? '' : 'custom|').'documents|conf|install|public\/test|Shared\/PCLZip|nusoap\/lib\/Mail|php\/example|php\/test|geoip\/sample.*\.php|ckeditor\/samples|ckeditor\/adapters)$'; // Exclude dirs
		$scanfiles = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, $regextoinclude, $regextoexclude);

		// Fill file_list with files in signature, new files, modified files
		$ret = getFilesUpdated($file_list, $xml->dolibarr_htdocs_dir[0], '', DOL_DOCUMENT_ROOT, $checksumconcat); // Fill array $file_list
		// Complete with list of new files
		foreach ($scanfiles as $keyfile => $valfile)
		{
			$tmprelativefilename = preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT, '/').'/', '', $valfile['fullname']);
			if (!in_array($tmprelativefilename, $file_list['insignature']))
			{
				$md5newfile = @md5_file($valfile['fullname']); // Can fails if we don't have permission to open/read file
				$file_list['added'][] = array('filename'=>$tmprelativefilename, 'md5'=>$md5newfile);
			}
		}

		// Files missings
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
		if (is_array($tmpfilelist) && count($tmpfilelist))
		{
			$i = 0;
			foreach ($tmpfilelist as $file)
			{
				$i++;
				$out .= '<tr class="oddeven">';
				$out .= '<td>'.$i.'</td>'."\n";
				$out .= '<td>'.dol_escape_htmltag($file['filename']).'</td>'."\n";
				$out .= '<td class="right">';
				if (!empty($file['expectedsize'])) $out .= dol_print_size($file['expectedsize']);
				$out .= '</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['expectedmd5']).'</td>'."\n";
				$out .= "</tr>\n";
			}
		} else {
			$out .= '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
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
		if (is_array($tmpfilelist2) && count($tmpfilelist2))
		{
			$i = 0;
			foreach ($tmpfilelist2 as $file)
			{
				$i++;
				$out .= '<tr class="oddeven">';
				$out .= '<td>'.$i.'</td>'."\n";
				$out .= '<td>'.dol_escape_htmltag($file['filename']).'</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['expectedmd5']).'</td>'."\n";
				$out .= '<td class="center">'.dol_escape_htmltag($file['md5']).'</td>'."\n";
				$out .= '<td class="right">';
				if ($file['expectedsize']) $out .= dol_print_size($file['expectedsize']);
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
			$out .= '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
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
		if (is_array($tmpfilelist3) && count($tmpfilelist3))
		{
			$i = 0;
			foreach ($tmpfilelist3 as $file)
			{
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
			$out .= '<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		$out .= '</table>';
		$out .= '</div>';


		// Show warning
		if (empty($tmpfilelist) && empty($tmpfilelist2) && empty($tmpfilelist3))
		{
			setEventMessages($langs->trans("FileIntegrityIsStrictlyConformedWithReference"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("FileIntegritySomeFilesWereRemovedOrModified"), null, 'warnings');
		}
	} else {
		print 'Error: Failed to found dolibarr_htdocs_dir into XML file '.$xmlfile;
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
	$checksumget = md5(join(',', $checksumconcat));
	$checksumtoget = trim((string) $xml->dolibarr_htdocs_dir_checksum);

	/*var_dump(count($file_list['added']));
    var_dump($checksumget);
    var_dump($checksumtoget);
    var_dump($checksumget == $checksumtoget);*/

	$outexpectedchecksum = ($checksumtoget ? $checksumtoget : $langs->trans("Unknown"));
	if ($checksumget == $checksumtoget)
	{
		if (count($file_list['added']))
		{
			$resultcode = 'warning';
			$resultcomment = 'FileIntegrityIsOkButFilesWereAdded';
			$outcurrentchecksum = $checksumget.' - <span class="'.$resultcode.'">'.$langs->trans("FileIntegrityIsOkButFilesWereAdded").'</span>';
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

	print load_fiche_titre($langs->trans("GlobalChecksum")).'<br>';
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
