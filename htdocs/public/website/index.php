<?php
/* Copyright (C) 2016-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     	\file       htdocs/public/website/index.php
 *		\ingroup    website
 *		\brief      Wrapper to output pages when website is powered by Dolibarr instead of a native web server
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOLOGIN'))        define("NOLOGIN", 1);
if (! defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1);	// We accept to go on this page from external web site.
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

/**
 * Header empty
 *
 * @return	void
 */
function llxHeader()
{
}
/**
 * Footer empty
 *
 * @return	void
 */
function llxFooter()
{
}

require '../../master.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


$error=0;
$websitekey=GETPOST('website', 'alpha');
$pageid=GETPOST('page', 'alpha')?GETPOST('page', 'alpha'):GETPOST('pageid', 'alpha');
$pageref=GETPOST('pageref', 'aZ09')?GETPOST('pageref', 'aZ09'):'';

$accessallowed = 1;
$type='';


if (empty($pageid))
{
	require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
	require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

	$object=new Website($db);
	$object->fetch(0, $websitekey);

	if (empty($object->id))
	{
		if (empty($pageid))
		{
			// Return header 404
			header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);

			include DOL_DOCUMENT_ROOT.'/public/error-404.php';
			exit;
		}
	}

	$objectpage=new WebsitePage($db);

	if ($pageref)
	{
		$result=$objectpage->fetch(0, $object->id, $pageref);
		if ($result > 0)
		{
			$pageid = $objectpage->id;
		}
		elseif($result == 0)
		{
			// Page not found from ref=pageurl, we try using alternative alias
			$result=$objectpage->fetch(0, $object->id, null, $pageref);
			if ($result > 0)
			{
				$pageid = $objectpage->id;
			}
		}
	}
	else
	{
		if ($object->fk_default_home > 0)
		{
			$result=$objectpage->fetch($object->fk_default_home);
			if ($result > 0)
			{
				$pageid = $objectpage->id;
			}
		}

		if (empty($pageid))
		{
			$array=$objectpage->fetchAll($object->id);
			if (is_array($array) && count($array) > 0)
			{
				$firstrep=reset($array);
				$pageid=$firstrep->id;
			}
		}
	}
}
if (empty($pageid))
{
	// Return header 404
	header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);

	$langs->load("website");

	if (! GETPOSTISSET('pageref')) print $langs->trans("PreviewOfSiteNotYetAvailable", $websitekey);

	include DOL_DOCUMENT_ROOT.'/public/error-404.php';
	exit;
}

$appli=constant('DOL_APPLICATION_TITLE');
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;



/*
 * View
 */

//print 'Directory with '.$appli.' websites.<br>';


// Security: Delete string ../ into $original_file
global $dolibarr_main_data_root;

if ($pageid == 'css')   // No more used ?
{
    header('Content-type: text/css');
    // Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
    //if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
    //else
    header('Cache-Control: no-cache');
    $original_file=$dolibarr_main_data_root.'/website/'.$websitekey.'/styles.css.php';
}
else
{
    $original_file=$dolibarr_main_data_root.'/website/'.$websitekey.'/page'.$pageid.'.tpl.php';
}

// Find the subdirectory name as the reference
$refname=basename(dirname($original_file)."/");

// Security:
// Limite acces si droits non corrects
if (! $accessallowed)
{
    accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file))
{
    dol_syslog("Refused to deliver file ".$original_file);
    $file=basename($original_file);		// Do no show plain path of original_file in shown error message
    dol_print_error(0, $langs->trans("ErrorFileNameInvalid", $file));
    exit;
}

clearstatcache();

$filename = basename($original_file);

// Output file on browser
dol_syslog("index.php include $original_file $filename content-type=$type");
$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

// This test if file exists should be useless. We keep it to find bug more easily
if (! file_exists($original_file_osencoded))
{
    // Return header 404
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);

    $langs->load("website");
    print $langs->trans("RequestedPageHasNoContentYet", $pageid);

    include DOL_DOCUMENT_ROOT.'/public/error-404.php';
    exit;
}


// Output page content
define('USEDOLIBARRSERVER', 1);
print '<!-- Page content '.$original_file.' rendered with DOLIBARR SERVER : Html with CSS link and html header + Body that was saved into tpl dir -->'."\n";
include_once $original_file_osencoded;		// Note: The pageXXX.tpl.php showed here contains a formatage with dolWebsiteOutput() at end of page.

if (is_object($db)) $db->close();
