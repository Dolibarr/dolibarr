<?php
/* Copyright (C) 2016-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *     	\file       htdocs/public/website/javascript.js.php
 *		\ingroup    website
 *		\brief      Page to output style page. Called with <script async src="/javascript.js.php?websiteid=123"></script>
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1);
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

/**
 * Header empty
 *
 * @param 	string 			$head				Optional head lines
 * @param 	string 			$title				HTML title
 * @param	string			$help_url			Url links to help page
 * 		                            			Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
 *                                  			For other external page: http://server/url
 * @param	string			$target				Target to use on links
 * @param 	int    			$disablejs			More content into html header
 * @param 	int    			$disablehead		More content into html header
 * @param 	array|string  	$arrayofjs			Array of complementary js files
 * @param 	array|string  	$arrayofcss			Array of complementary css files
 * @param	string			$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @param   string  		$morecssonbody      More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
 * @param	string			$replacemainareaby	Replace call to main_area() by a print of this string
 * @param	int				$disablenofollow	Disable the "nofollow" on meta robot header
 * @param	int				$disablenoindex		Disable the "noindex" on meta robot header
 * @return	void
 */
function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0)
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


$error = 0;
$website = GETPOST('website', 'alpha');
$websiteid = GETPOSTINT('websiteid');
$pageid = GETPOST('page', 'alpha') ? GETPOST('page', 'alpha') : GETPOST('pageid', 'alpha');

$accessallowed = 1;
$type = '';


/*
 * View
 */

$appli = constant('DOL_APPLICATION_TITLE');
if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
	$appli = getDolGlobalString('MAIN_APPLICATION_TITLE');
}

//print 'Directory with '.$appli.' websites.<br>';

if (empty($pageid)) {
	require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
	require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

	$object = new Website($db);
	if ($websiteid) {
		$object->fetch($websiteid);
		$website = $object->ref;
	} else {
		$object->fetch(0, $website);
	}

	$objectpage = new WebsitePage($db);
	/* Not required for CSS file
	$array=$objectpage->fetchAll($object->id);

	if (is_array($array) && count($array) > 0)
	{
		$firstrep=reset($array);
		$pageid=$firstrep->id;
	}
	*/
}
/* Not required for CSS file
if (empty($pageid))
{
	$langs->load("website");
	print $langs->trans("PreviewOfSiteNotYetAvailable");
	exit;
}
*/

// Security: Delete string ../ into $original_file
global $dolibarr_main_data_root;

$original_file = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$website.'/javascript.js.php';

// Find the subdirectory name as the reference
$refname = basename(dirname($original_file)."/");

// Security:
// Limit access if permissions are insufficient
if (!$accessallowed) {
	accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
	dol_syslog("Refused to deliver file ".$original_file);
	$file = basename($original_file); // Do no show plain path of original_file in shown error message
	dol_print_error(null, $langs->trans("ErrorFileNameInvalid", $file));
	exit;
}

clearstatcache();

$filename = basename($original_file);

// Output file on browser
dol_syslog("javascript.js.css.php include $original_file $filename content-type=$type");
$original_file_osencoded = dol_osencode($original_file); // New file name encoded in OS encoding charset

// This test if file exists should be useless. We keep it to find bug more easily
if (!file_exists($original_file_osencoded)) {
	$langs->load("website");
	print $langs->trans("RequestedPageHasNoContentYet", $pageid);
	//dol_print_error(null,$langs->trans("ErrorFileDoesNotExists",$original_file));
	exit;
}


// Output page content
define('USEDOLIBARRSERVER', 1);
print '/* Page content '.$original_file.' : JS content that was saved into tpl dir */'."\n";
require_once $original_file_osencoded;


if (is_object($db)) {
	$db->close();
}
