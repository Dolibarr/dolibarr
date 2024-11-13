<?php
/* Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

// BEGIN PHP File wrapper.php used to download rss, logo, shared files - DO NOT MODIFY - It is just a copy of file website/samples/wrapper.php
$websitekey = basename(__DIR__);
if (strpos($_SERVER["PHP_SELF"], 'website/samples/wrapper.php')) {
	die("Sample file for website module. Can't be called directly.");
}
if (!defined('USEDOLIBARRSERVER') && !defined('USEDOLIBARREDITOR')) {
	require_once './master.inc.php';
} // Load master if not already loaded
include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

$encoding = '';

// Parameters to download files
$hashp = GETPOST('hashp', 'aZ09');
$extname = GETPOST('extname', 'alpha', 1);
$modulepart = GETPOST('modulepart', 'aZ09');
$entity = GETPOSTINT('entity') ? GETPOSTINT('entity') : $conf->entity;
$original_file = GETPOST("file", "alpha");
$l = GETPOST('l', 'aZ09');
$limit = GETPOSTINT('limit');

// Parameters for RSS
$rss = GETPOST('rss', 'aZ09');
if ($rss) {
	$original_file = 'blog.rss';
}

// If we have a hash public (hashp), we guess the original_file.
if (!empty($hashp)) {
	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$ecmfile = new EcmFiles($db);
	$result = $ecmfile->fetch(0, '', '', '', $hashp);
	if ($result > 0) {
		$tmp = explode('/', $ecmfile->filepath, 2); // $ecmfile->filepath is relative to document directory
		// filepath can be 'users/X' or 'X/propale/PR11111'
		if (is_numeric($tmp[0])) { // If first tmp is numeric, it is subdir of company for multicompany, we take next part.
			$tmp = explode('/', $tmp[1], 2);
		}
		$moduleparttocheck = $tmp[0]; // moduleparttocheck is first part of path

		if ($modulepart) {	// Not required, so often not defined, for link using public hashp parameter.
			if ($moduleparttocheck == $modulepart) {
				// We remove first level of directory
				$original_file = (($tmp[1] ? $tmp[1].'/' : '').$ecmfile->filename); // this is relative to module dir
				//var_dump($original_file); exit;
			} else {
				print 'Bad link. File is from another module part.';
			}
		} else {
			$modulepart = $moduleparttocheck;
			$original_file = (($tmp[1] ? $tmp[1].'/' : '').$ecmfile->filename); // this is relative to module dir
		}

		if ($extname) {
			$original_file = getImageFileNameForSize($original_file, $extname);
		}
	} else {
		print "ErrorFileNotFoundWithSharedLink";
		exit;
	}
}

// Define attachment (attachment=true to force choice popup 'open'/'save as')
$attachment = true;
if (preg_match('/\.(html|htm)$/i', $original_file)) {
	$attachment = false;
}
if (GETPOSTISSET("attachment")) {
	$attachment = (GETPOST("attachment", 'alphanohtml') ? true : false);
}
if (getDolGlobalString('MAIN_DISABLE_FORCE_SAVEAS_WEBSITE')) {
	$attachment = false;
}

// Define mime type
$type = 'application/octet-stream';
if (GETPOSTISSET('type')) {
	$type = GETPOST('type', 'alpha');
} else {
	$type = dol_mimetype($original_file);
}

// Security: Delete string ../ into $original_file
$original_file = str_replace("../", "/", $original_file);

// Cache or not
if (GETPOST("cache", 'aZ09') || image_format_supported($original_file) >= 0) {
	// Important: Following code is to avoid page request by browser and PHP CPU at
	// each Dolibarr page access.
	header('Cache-Control: max-age=3600, public, must-revalidate');
	header('Pragma: cache'); // This is to avoid having Pragma: no-cache
}

$refname = basename(dirname($original_file)."/");

// Get RSS news
if ($rss) {
	$format = 'rss';
	$type = '';
	$cachedelay = 0;
	$filename = $original_file;
	$dir_temp = $conf->website->dir_temp;

	include_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
	include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
	$website = new Website($db);
	$websitepage = new WebsitePage($db);

	$website->fetch('', $websitekey);

	$filters = array('type_container'=>'blogpost', 'status'=>1);
	if ($l) {
		$filters['lang'] = $l;
	}

	$MAXNEWS = ($limit ? $limit : 20);
	$arrayofblogs = $websitepage->fetchAll($website->id, 'DESC', 'date_creation', $MAXNEWS, 0, $filters);
	$eventarray = array();
	if (is_array($arrayofblogs)) {
		foreach ($arrayofblogs as $blog) {
			$blog->fullpageurl = $website->virtualhost.'/'.$blog->pageurl.'.php';
			$blog->image = preg_replace('/__WEBSITE_KEY__/', $websitekey, $blog->image);

			$eventarray[] = $blog;
		}
	}

	require_once DOL_DOCUMENT_ROOT."/core/lib/xcal.lib.php";
	require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
	require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

	dol_syslog("build_exportfile Build export file format=".$format.", type=".$type.", cachedelay=".$cachedelay.", filename=".$filename.", filters size=".count($filters), LOG_DEBUG);

	// Clean parameters
	if (!$filename) {
		$extension = 'rss';
		$filename = $format.'.'.$extension;
	}

	// Create dir and define output file (definitive and temporary)
	$result = dol_mkdir($dir_temp);
	$outputfile = $dir_temp.'/'.$filename;

	$result = 0;

	$buildfile = true;

	if ($cachedelay) {
		$nowgmt = dol_now();
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		if (dol_filemtime($outputfile) > ($nowgmt - $cachedelay)) {
			dol_syslog("build_exportfile file ".$outputfile." is not older than now - cachedelay (".$nowgmt." - ".$cachedelay."). Build is canceled");
			$buildfile = false;
		}
	}

	if ($buildfile) {
		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang($l);
		$outputlangs->loadLangs(array("main", "other"));
		$title = $outputlangs->transnoentities('LatestBlogPosts').' - '.$website->virtualhost;
		$desc = $title.($l ? ' ('.$l.')' : '');

		// Create temp file
		$outputfiletmp = tempnam($dir_temp, 'tmp'); // Temporary file (allow call of function by different threads
		dolChmod($outputfiletmp);

		// Write file
		$result = build_rssfile($format, $title, $desc, $eventarray, $outputfiletmp, '', $website->virtualhost.'/wrapper.php?rss=1'.($l ? '&l='.$l : ''), $l);

		if ($result >= 0) {
			if (dol_move($outputfiletmp, $outputfile, '0', 1, 0, 0)) {
				$result = 1;
			} else {
				$error = 'Failed to rename '.$outputfiletmp.' into '.$outputfile;
				dol_syslog("build_exportfile ".$error, LOG_ERR);
				dol_delete_file($outputfiletmp, 0, 1);
				print $error;
				exit(-1);
			}
		} else {
			dol_syslog("build_exportfile build_xxxfile function fails to for format=".$format." outputfiletmp=".$outputfile, LOG_ERR);
			dol_delete_file($outputfiletmp, 0, 1);
			$langs->load("errors");
			print $langs->trans("ErrorFailToCreateFile", $outputfile);
			exit(-1);
		}
	}

	if ($result >= 0) {
		$attachment = false;
		if (GETPOSTISSET("attachment")) {
			$attachment = GETPOST("attachment");
		}
		//$attachment = false;
		$contenttype = 'application/rss+xml';
		if (GETPOSTISSET("contenttype")) {
			$contenttype = GETPOST("contenttype");
		}
		//$contenttype='text/plain';
		$outputencoding = 'UTF-8';

		if ($contenttype) {
			header('Content-Type: '.$contenttype.($outputencoding ? '; charset='.$outputencoding : ''));
		}
		if ($attachment) {
			header('Content-Disposition: attachment; filename="'.$filename.'"');
		}

		// Ajout directives pour resoudre bug IE
		//header('Cache-Control: Public, must-revalidate');
		//header('Pragma: public');
		if ($cachedelay) {
			header('Cache-Control: max-age='.$cachedelay.', private, must-revalidate');
		} else {
			header('Cache-Control: private, must-revalidate');
		}

		// Clean parameters
		$outputfile = $dir_temp.'/'.$filename;
		$result = readfile($outputfile);
		if (!$result) {
			print 'File '.$outputfile.' was empty.';
		}

		// header("Location: ".DOL_URL_ROOT.'/document.php?modulepart=agenda&file='.urlencode($filename));
		exit;
	}
} elseif ($modulepart == "mycompany" && preg_match('/^\/?logos\//', $original_file)) {
	// Get logos
	readfile(dol_osencode($conf->mycompany->dir_output."/".$original_file));
} else {
	// Find the subdirectory name as the reference
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$check_access = dol_check_secure_access_document($modulepart, $original_file, $entity, null, $refname);
	$accessallowed              = empty($check_access['accessallowed']) ? '' : $check_access['accessallowed'];
	$sqlprotectagainstexternals = empty($check_access['sqlprotectagainstexternals']) ? '' : $check_access['sqlprotectagainstexternals'];
	$fullpath_original_file     = empty($check_access['original_file']) ? '' : $check_access['original_file']; // $fullpath_original_file is now a full path name
	if ($hashp) {
		$accessallowed = 1; // When using hashp, link is public so we force $accessallowed
		$sqlprotectagainstexternals = '';
	}

	// Security:
	// Limit access if permissions are wrong
	if (!$accessallowed) {
		print 'Access forbidden';
		exit;
	}

	clearstatcache();

	$filename = basename($fullpath_original_file);

	// Output file on browser
	dol_syslog("wrapper.php download $fullpath_original_file filename=$filename content-type=$type");
	$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file); // New file name encoded in OS encoding charset

	// This test if file exists should be useless. We keep it to find bug more easily
	if (!file_exists($fullpath_original_file_osencoded)) {
		print "ErrorFileDoesNotExists: ".dol_escape_htmltag($original_file);
		exit;
	}

	// Permissions are ok and file found, so we return it
	//top_httphead($type);
	header('Content-Type: '.$type);
	header('Content-Description: File Transfer');
	if ($encoding) {
		header('Content-Encoding: '.$encoding);
	}
	// Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)
	if ($attachment) {
		header('Content-Disposition: attachment; filename="'.$filename.'"');
	} else {
		header('Content-Disposition: inline; filename="'.$filename.'"');
	}
	header('Content-Length: '.dol_filesize($fullpath_original_file));

	readfile($fullpath_original_file_osencoded);
}
if (is_object($db)) {
	$db->close();
}
// END PHP
