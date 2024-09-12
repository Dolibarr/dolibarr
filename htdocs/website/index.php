<?php
/* Copyright (C) 2016-2023  Laurent Destailleur  		<eldy@users.sourceforge.net>
 * Copyright (C) 2020 	    Nicolas ZABOURI				<info@inovea-conseil.com>
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
 *   	\file       htdocs/website/index.php
 *		\ingroup    website
 *		\brief      Page to website view/edit
 */

/** @phan-file-suppress PhanPluginSuspiciousParamPosition */

// We allow POST of rich content with js and style, but only for this php file and if into some given POST variable
define('NOSCANPOSTFORINJECTION', array('PAGE_CONTENT', 'WEBSITE_CSS_INLINE', 'WEBSITE_JS_INLINE', 'WEBSITE_HTML_HEADER', 'htmlheader'));

define('USEDOLIBARREDITOR', 1);
define('FORCE_CKEDITOR', 1); // We need CKEditor, even if module is off.
if (!defined('DISABLE_JS_GRAHP')) {
	define('DISABLE_JS_GRAPH', 1);
}

//header('X-XSS-Protection:0');	// Disable XSS filtering protection of some browsers (note: use of Content-Security-Policy is more efficient). Disabled as deprecated.

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formwebsite.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';


// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "website", "errors"));

// Security check
if (!$user->hasRight('website', 'read')) {
	accessforbidden();
}

$conf->dol_hide_leftmenu = 1; // Force hide of left menu.

$error = 0;
$virtualurl = '';
$dataroot = '';
$websiteid = GETPOSTINT('websiteid');
$websitekey = GETPOST('website', 'alpha');
$page = GETPOST('page', 'alpha');
$pageid = GETPOSTINT('pageid');
$pageref = GETPOST('pageref', 'alphanohtml');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'websitelist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$dol_hide_topmenu = GETPOSTINT('dol_hide_topmenu');
$dol_hide_leftmenu = GETPOSTINT('dol_hide_leftmenu');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

$type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha');
$section_dir = GETPOST('section_dir', 'alpha');
$file_manager = GETPOST('file_manager', 'alpha');
$replacesite = GETPOST('replacesite', 'alpha');
$mode = GETPOST('mode', 'alpha');

if (GETPOST('deletesite', 'alpha')) {
	$action = 'deletesite';
}
if (GETPOST('delete', 'alpha')) {
	$action = 'delete';
}
if (GETPOST('preview', 'alpha')) {
	$action = 'preview';
}
if (GETPOST('createsite', 'alpha')) {
	$action = 'createsite';
}
if (GETPOST('createcontainer', 'alpha')) {
	$action = 'createcontainer';
}
if (GETPOST('editcss', 'alpha')) {
	$action = 'editcss';
}
if (GETPOST('editmenu', 'alpha')) {
	$action = 'editmenu';
}
if (GETPOST('setashome', 'alpha')) {
	$action = 'setashome';
}
if (GETPOST('editmeta', 'alpha')) {
	$action = 'editmeta';
}
if (GETPOST('editsource', 'alpha')) {
	$action = 'editsource';
}
if (GETPOST('editcontent', 'alpha')) {
	$action = 'editcontent';
}
if (GETPOST('exportsite', 'alpha')) {
	$action = 'exportsite';
}
if (GETPOST('importsite', 'alpha')) {
	$action = 'importsite';
}
if (GETPOST('createfromclone', 'alpha')) {
	$action = 'createfromclone';
}
if (GETPOST('createpagefromclone', 'alpha')) {
	$action = 'createpagefromclone';
}
if (empty($action) && $file_manager) {
	$action = 'file_manager';
}
if ($action == 'replacesite' || (empty($action) && $replacesite)) {		// Test on permission not required
	$mode = 'replacesite';
}
if (GETPOST('refreshsite') || GETPOST('refreshsite_x') || GETPOST('refreshsite.x')) {
	$pageid = 0;
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield='name';
//if (! $sortorder) $sortorder='ASC';

if (empty($action)) {
	$action = 'preview';
}

$object = new Website($db);
$objectpage = new WebsitePage($db);

$listofwebsites = $object->fetchAll('ASC', 'position'); // Init list of websites

// If website not defined, we take first found
if (!($websiteid > 0) && empty($websitekey) && $action != 'createsite') {
	foreach ($listofwebsites as $key => $valwebsite) {
		$websitekey = $valwebsite->ref;
		break;
	}
}
if ($websiteid > 0 || $websitekey) {
	$res = $object->fetch($websiteid, $websitekey);
	$websitekey = $object->ref;
}

$website = $object;

// Check pageid received as parameter
if ($pageid < 0) {
	$pageid = 0;
}
if (($pageid > 0 || $pageref) && $action != 'addcontainer') {
	$res = $objectpage->fetch($pageid, ($object->id > 0 ? $object->id : null), $pageref);
	// @phan-suppress
	if ($res == 0) {
		$res = $objectpage->fetch($pageid, ($object->id > 0 ? $object->id : null), null, $pageref);
	}

	// Check if pageid is inside the new website, if not we reset param pageid
	if ($res >= 0 && $object->id > 0) {
		if ($objectpage->fk_website != $object->id) {	// We have a bad page that does not belong to web site
			if ($object->fk_default_home > 0) {
				$res = $objectpage->fetch($object->fk_default_home, $object->id, ''); // We search first page of web site
				if ($res > 0) {
					$pageid = $object->fk_default_home;
				}
			} else {
				$res = $objectpage->fetch(0, $object->id, ''); // We search first page of web site
				if ($res == 0) {	// Page was not found, we reset it
					$objectpage = new WebsitePage($db);
				} else { // We found a page, we set pageid to it.
					$pageid = $objectpage->id;
				}
			}
		} else { // We have a valid page. We force pageid for the case we got the page with a fetch on ref.
			$pageid = $objectpage->id;
		}
	}
}

// Define pageid if pageid and pageref not received as parameter or was wrong
if (empty($pageid) && empty($pageref) && $object->id > 0 && $action != 'createcontainer') {
	$pageid = $object->fk_default_home;
	if (empty($pageid)) {
		$array = $objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
		if (!is_array($array) && $array < 0) {
			dol_print_error(null, $objectpage->error, $objectpage->errors);
		}
		$atleastonepage = (is_array($array) && count($array) > 0);

		$firstpageid = 0;
		$homepageid = 0;
		foreach ($array as $key => $valpage) {
			if (empty($firstpageid)) {
				$firstpageid = $valpage->id;
			}
			if ($object->fk_default_home && $key == $object->fk_default_home) {
				$homepageid = $valpage->id;
			}
		}
		$pageid = ($homepageid ? $homepageid : $firstpageid); // We choose home page and if not defined yet, we take first page
	}
}


global $dolibarr_main_data_root;
$pathofwebsite = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$websitekey;
$filehtmlheader = $pathofwebsite.'/htmlheader.html';
$filecss = $pathofwebsite.'/styles.css.php';
$filejs = $pathofwebsite.'/javascript.js.php';
$filerobot = $pathofwebsite.'/robots.txt';
$filehtaccess = $pathofwebsite.'/.htaccess';
$filetpl = $pathofwebsite.'/page'.$pageid.'.tpl.php';
$fileindex = $pathofwebsite.'/index.php';
$filewrapper = $pathofwebsite.'/wrapper.php';
$filemanifestjson = $pathofwebsite.'/manifest.json.php';
$filereadme = $pathofwebsite.'/README.md';
$filelicense = $pathofwebsite.'/LICENSE';
$filemaster = $pathofwebsite.'/master.inc.php';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


$permtouploadfile = $user->hasRight('website', 'write');
$diroutput = $conf->medias->multidir_output[$conf->entity];

$relativepath = $section_dir;
$upload_dir = preg_replace('/\/$/', '', $diroutput).'/'.preg_replace('/^\//', '', $relativepath);

$htmlheadercontentdefault = '';
$htmlheadercontentdefault .= '<link rel="stylesheet" id="google-fonts-css"  href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700" />'."\n";
$htmlheadercontentdefault .= '<link rel="stylesheet" id="font-wasesome-css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />'."\n";
$htmlheadercontentdefault .= '<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>'."\n";
$htmlheadercontentdefault .= '<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>'."\n";
$htmlheadercontentdefault .= '<!--'."\n";
$htmlheadercontentdefault .= '<script src="/document.php?modulepart=medias&file=css/myfile.css"></script>'."\n";
$htmlheadercontentdefault .= '<script src="/document.php?modulepart=medias&file=js/myfile.js"></script>'."\n";
$htmlheadercontentdefault .= '-->'."\n";

$manifestjsoncontentdefault = '';
$manifestjsoncontentdefault .= '{
	"name": "MyWebsite",
	"short_name": "MyWebsite",
	"start_url": "/",
	"lang": "en-US",
	"display": "standalone",
	"background_color": "#fff",
	"description": "A simple Web app.",
	"icons": [{
	"src": "images/'.urlencode($website->ref).'/homescreen48.png",
	"sizes": "48x48",
	"type": "image/png"
	}, {
		"src": "image/'.urlencode($website->ref).'/homescreen72.png",
		"sizes": "72x72",
		"type": "image/png"
	}, {
		"src": "image/'.urlencode($website->ref).'/homescreen96.png",
		"sizes": "96x96",
		"type": "image/png"
	}, {
		"src": "image/'.urlencode($website->ref).'/homescreen144.png",
		"sizes": "144x144",
		"type": "image/png"
	}, {
		"src": "image/'.urlencode($website->ref).'/homescreen168.png",
		"sizes": "168x168",
		"type": "image/png"
	}, {
		"src": "image/'.urlencode($website->ref).'/homescreen192.png",
		"sizes": "192x192",
		"type": "image/png"
	}],
	"related_applications": [{
		"platform": "play",
		"url": "https://play.google.com/store/apps/details?id=com.nltechno.dolidroidpro"
	}]
}';

$listofpages = array();

$algo = '';
if (GETPOST('optionmeta')) {
	$algo .= 'meta';
}
if (GETPOST('optioncontent')) {
	$algo .= 'content';
}
if (GETPOST('optionsitefiles')) {
	$algo .= 'sitefiles';
}

if (empty($sortfield)) {
	if ($action == 'file_manager') {	// Test on permission not required
		$sortfield = 'name';
		$sortorder = 'ASC';
	} else {
		$sortfield = 'pageurl';
		$sortorder = 'ASC';
	}
}

$searchkey = GETPOST('searchstring', 'restricthtml');

if ($action == 'replacesite' || $mode == 'replacesite') {	// Test on permission not required
	$containertype = GETPOST('optioncontainertype', 'aZ09') != '-1' ? GETPOST('optioncontainertype', 'aZ09') : '';
	$langcode = GETPOST('optionlanguage', 'aZ09');
	$otherfilters = array();
	if (GETPOSTINT('optioncategory') > 0) {
		$otherfilters['category'] = GETPOSTINT('optioncategory');
	}

	$listofpages = getPagesFromSearchCriterias($containertype, $algo, $searchkey, 1000, $sortfield, $sortorder, $langcode, $otherfilters, -1);
}

$usercanedit = $user->hasRight('website', 'write');
$permissiontoadd = $user->hasRight('website', 'write');	// Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles
$permissiontodelete = $user->hasRight('website', 'delete');


/*
 * Actions
 */

// Protections
if (GETPOST('refreshsite') || GETPOST('refreshsite_x') || GETPOST('refreshsite.x') || GETPOST('refreshpage') || GETPOST('refreshpage_x') || GETPOST('refreshpage.x')) {
	$action = 'preview'; // To avoid to make an action on another page or another site when we click on button to select another site or page.
}
if (GETPOST('refreshsite', 'alpha') || GETPOST('refreshsite.x', 'alpha') || GETPOST('refreshsite_x', 'alpha')) {		// If we change the site, we reset the pageid and cancel addsite action.
	if ($action == 'addsite') {
		$action = 'preview';
	}
	if ($action == 'updatesource') {
		$action = 'preview';
	}

	$pageid = $object->fk_default_home;
	if (empty($pageid)) {
		$array = $objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
		if (!is_array($array) && $array < 0) {
			dol_print_error(null, $objectpage->error, $objectpage->errors);
		}
		$atleastonepage = (is_array($array) && count($array) > 0);

		$firstpageid = 0;
		$homepageid = 0;
		foreach ($array as $key => $valpage) {
			if (empty($firstpageid)) {
				$firstpageid = $valpage->id;
			}
			if ($object->fk_default_home && $key == $object->fk_default_home) {
				$homepageid = $valpage->id;
			}
		}
		$pageid = ($homepageid ? $homepageid : $firstpageid); // We choose home page and if not defined yet, we take first page
	}
}
if (GETPOST('refreshpage', 'alpha') && !in_array($action, array('updatecss'))) {
	$action = 'preview';
}

if ($cancel && $action == 'renamefile') {
	$cancel = '';
}

// Cancel
if ($cancel) {
	$action = 'preview';
	$mode = '';
	if ($backtopage) {
		header("Location: ".$backtopage);
		exit;
	}
}

$savbacktopage = $backtopage;
$backtopage = $_SERVER["PHP_SELF"].'?file_manager=1&website='.urlencode($websitekey).'&pageid='.urlencode($pageid).(GETPOST('section_dir', 'alpha') ? '&section_dir='.urlencode(GETPOST('section_dir', 'alpha')) : ''); // used after a confirm_deletefile into actions_linkedfiles.inc.php
if ($sortfield) {
	$backtopage .= '&sortfield='.urlencode($sortfield);
}
if ($sortorder) {
	$backtopage .= '&sortorder='.urlencode($sortorder);
}
include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';	// This manage 'sendit', 'confirm_deletefile', 'renamefile' action when submitting new file.

$backtopage = $savbacktopage;
//var_dump($backtopage);
//var_dump($action);

if ($action == 'renamefile') {	// Must be after include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php'; If action were renamefile, we set it to 'file_manager'
	$action = 'file_manager';
}

if ($action == 'setwebsiteonline' && $usercanedit) {
	$website->setStatut($website::STATUS_VALIDATED, null, '', 'WEBSITE_MODIFY', 'status');

	header("Location: ".$_SERVER["PHP_SELF"].'?website='.urlencode(GETPOST('website')).'&pageid='.GETPOSTINT('websitepage'));
	exit;
}
if ($action == 'setwebsiteoffline' && $usercanedit) {
	$result = $website->setStatut($website::STATUS_DRAFT, null, '', 'WEBSITE_MODIFY', 'status');

	header("Location: ".$_SERVER["PHP_SELF"].'?website='.urlencode(GETPOST('website')).'&pageid='.GETPOSTINT('websitepage'));
	exit;
}
if ($action == 'seteditinline') {	// No need of write permission
	dolibarr_set_const($db, 'WEBSITE_EDITINLINE', 1);
	setEventMessages($langs->trans("FeatureNotYetAvailable"), null, 'warnings');
	//dolibarr_set_const($db, 'WEBSITE_SUBCONTAINERSINLINE', 0); // Force disable of 'Include dynamic content'
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.urlencode(GETPOST('website')).'&pageid='.GETPOSTINT('pageid'));
	exit;
}
if ($action == 'unseteditinline') {	// No need of write permission
	dolibarr_del_const($db, 'WEBSITE_EDITINLINE');
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.urlencode(GETPOST('website')).'&pageid='.GETPOSTINT('pageid'));
	exit;
}
if ($action == 'setshowsubcontainers') {	// No need of write permission
	dolibarr_set_const($db, 'WEBSITE_SUBCONTAINERSINLINE', 1);
	//dolibarr_set_const($db, 'WEBSITE_EDITINLINE', 0); // Force disable of edit inline
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.urlencode(GETPOST('website')).'&pageid='.GETPOSTINT('pageid'));
	exit;
}
if ($action == 'unsetshowsubcontainers') {	// No need of write permission
	dolibarr_del_const($db, 'WEBSITE_SUBCONTAINERSINLINE');
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.urlencode(GETPOST('website')).'&pageid='.GETPOSTINT('pageid'));
	exit;
}

if ($massaction == 'replace' && GETPOST('confirmmassaction', 'alpha') && !$searchkey && $usercanedit) {
	$mode = 'replacesite';
	$action = 'replacesite';
	$massaction = '';
}

if ($action == 'deletetemplate' && $usercanedit) {
	$dirthemes = array('/doctemplates/websites');
	if (!empty($conf->modules_parts['websitetemplates'])) {		// Using this feature slow down application
		foreach ($conf->modules_parts['websitetemplates'] as $reldir) {
			$dirthemes = array_merge($dirthemes, (array) ($reldir.'doctemplates/websites'));
		}
	}
	$dirthemes = array_unique($dirthemes);


	// Delete template files and dir
	$mode = 'importsite';
	$action = 'importsite';

	if (count($dirthemes)) {
		$i = 0;
		foreach ($dirthemes as $dir) {
			//print $dirroot.$dir;exit;
			$dirtheme = DOL_DATA_ROOT.$dir; // This include loop on $conf->file->dol_document_root
			if (is_dir($dirtheme)) {
				$templateuserfile = GETPOST('templateuserfile');
				$imguserfile = preg_replace('/\.zip$/', '', $templateuserfile).'.jpg';
				dol_delete_file($dirtheme.'/'.$templateuserfile);
				dol_delete_file($dirtheme.'/'.$imguserfile);
			}
		}
	}
}

// Set category
if ($massaction == 'setcategory' && GETPOST('confirmmassaction', 'alpha') && $usercanedit) {
	$error = 0;
	$nbupdate = 0;

	$db->begin();

	$categoryid = GETPOSTINT('setcategory');
	if ($categoryid > 0) {
		$tmpwebsitepage = new WebsitePage($db);
		$category = new Categorie($db);
		$category->fetch($categoryid);

		foreach ($toselect as $tmpid) {
			$tmpwebsitepage->id = $tmpid;
			$result = $category->add_type($tmpwebsitepage, 'website_page');
			if ($result < 0 && $result != -3) {
				$error++;
				setEventMessages($category->error, $category->errors, 'errors');
				break;
			} else {
				$nbupdate++;
			}
		}
	}

	if ($error) {
		$db->rollback();
	} else {
		if ($nbupdate) {
			setEventMessages($langs->trans("RecordsModified", $nbupdate), null, 'mesgs');
		}

		$db->commit();
	}
	// Now we reload list
	$listofpages = getPagesFromSearchCriterias($containertype, $algo, $searchkey, 1000, $sortfield, $sortorder, $langcode, $otherfilters, -1);
}

// Del category
if ($massaction == 'delcategory' && GETPOST('confirmmassaction', 'alpha') && $usercanedit) {
	$error = 0;
	$nbupdate = 0;

	$db->begin();

	$categoryid = GETPOSTINT('setcategory');
	if ($categoryid > 0) {
		$tmpwebsitepage = new WebsitePage($db);
		$category = new Categorie($db);
		$category->fetch($categoryid);

		foreach ($toselect as $tmpid) {
			$tmpwebsitepage->id = $tmpid;
			$result = $category->del_type($tmpwebsitepage, 'website_page');
			if ($result < 0 && $result != -3) {
				$error++;
				setEventMessages($category->error, $category->errors, 'errors');
				break;
			} else {
				$nbupdate++;
			}
		}
	}

	if ($error) {
		$db->rollback();
	} else {
		if ($nbupdate) {
			setEventMessages($langs->trans("RecordsModified", $nbupdate), null, 'mesgs');
		}

		$db->commit();
	}
	// Now we reload list
	$listofpages = getPagesFromSearchCriterias($containertype, $algo, $searchkey, 1000, $sortfield, $sortorder, $langcode, $otherfilters, -1);
}

// Replacement of string into pages
if ($massaction == 'replace' && GETPOST('confirmmassaction', 'alpha') && $usercanedit) {
	$replacestring = GETPOST('replacestring', 'none');

	$dolibarrdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);
	$allowimportsite = true;
	if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) {
		$allowimportsite = false;
	}

	if (!$allowimportsite) {
		// Blocked by installmodules.lock
		if (getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US')) {
			// Show clean corporate message
			$message = $langs->trans('InstallModuleFromWebHasBeenDisabledContactUs');
		} else {
			// Show technical generic message
			$message = $langs->trans("InstallModuleFromWebHasBeenDisabledByFile", $dolibarrdataroot.'/installmodules.lock');
		}
		setEventMessages($message, null, 'errors');
	} elseif (!$user->hasRight('website', 'writephp')) {
		setEventMessages("NotAllowedToAddDynamicContent", null, 'errors');
	} elseif (!$replacestring) {
		setEventMessages("ErrorReplaceStringEmpty", null, 'errors');
	} else {
		$nbreplacement = 0;

		foreach ($toselect as $keyselected) {
			$objectpage = $listofpages['list'][$keyselected];
			if ($objectpage->pageurl) {
				dol_syslog("Replace string into page ".$objectpage->pageurl);

				if (GETPOST('optioncontent', 'aZ09')) {
					$objectpage->content = str_replace($searchkey, $replacestring, $objectpage->content);
				}
				if (GETPOST('optionmeta', 'aZ09')) {
					$objectpage->title = str_replace($searchkey, $replacestring, $objectpage->title);
					$objectpage->description = str_replace($searchkey, $replacestring, $objectpage->description);
					$objectpage->keywords = str_replace($searchkey, $replacestring, $objectpage->keywords);
				}

				$filealias = $pathofwebsite.'/'.$objectpage->pageurl.'.php';
				$filetpl = $pathofwebsite.'/page'.$objectpage->id.'.tpl.php';

				// Save page alias
				$result = dolSavePageAlias($filealias, $object, $objectpage);
				if (!$result) {
					setEventMessages('Failed to write file '.basename($filealias), null, 'errors');
				}

				// Save page of content
				$result = dolSavePageContent($filetpl, $object, $objectpage, 1);
				if ($result) {
					$nbreplacement++;
					//var_dump($objectpage->content);exit;
					$objectpage->update($user);
				} else {
					$error++;
					setEventMessages('Failed to write file '.$filetpl, null, 'errors');
					$action = 'createcontainer';
					break;
				}
			}
		}

		if ($nbreplacement > 0) {
			setEventMessages($langs->trans("ReplacementDoneInXPages", $nbreplacement), null, 'mesgs');
		}

		$containertype = GETPOST('optioncontainertype', 'aZ09') != '-1' ? GETPOST('optioncontainertype', 'aZ09') : '';
		$langcode = GETPOST('optionlanguage', 'aZ09');
		$otherfilters = array();
		if (GETPOSTINT('optioncategory') > 0) {
			$otherfilters['category'] = GETPOSTINT('optioncategory');
		}

		// Now we reload list
		$listofpages = getPagesFromSearchCriterias($containertype, $algo, $searchkey, 1000, $sortfield, $sortorder, $langcode, $otherfilters);
	}
}


// Add directory
/*
if ($action == 'adddir' && $permtouploadfile)
{
	$ecmdir->ref                = 'NOTUSEDYET';
	$ecmdir->label              = GETPOST("label");
	$ecmdir->description        = GETPOST("desc");

	//$id = $ecmdir->create($user);
	if ($id > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		setEventMessages('Error '.$langs->trans($ecmdir->error), null, 'errors');
		$action = "createcontainer";
	}

	clearstatcache();
}
*/

// Add a website
if ($action == 'addsite' && $usercanedit) {
	$db->begin();

	if (GETPOST('virtualhost', 'alpha') && !preg_match('/^http/', GETPOST('virtualhost', 'alpha'))) {
		$error++;
		setEventMessages($langs->trans('ErrorURLMustStartWithHttp', $langs->transnoentitiesnoconv("VirtualHost")), null, 'errors');
	}

	if (!$error && !GETPOST('WEBSITE_REF', 'alpha')) {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities("WebsiteName")), null, 'errors');
	}
	if (!$error && !preg_match('/^[a-z0-9_\-\.]+$/i', GETPOST('WEBSITE_REF', 'alpha'))) {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("Ref")), null, 'errors');
	}

	if (!$error) {
		$arrayotherlang = explode(',', GETPOST('WEBSITE_OTHERLANG', 'alphanohtml'));
		foreach ($arrayotherlang as $key => $val) {
			// It possible we have empty val here if postparam WEBSITE_OTHERLANG is empty or set like this : 'en,,sv' or 'en,sv,'
			if (empty(trim($val))) {
				continue;
			}
			$arrayotherlang[$key] = substr(trim($val), 0, 2); // Kept short language code only
		}

		$tmpobject = new Website($db);
		$tmpobject->ref = GETPOST('WEBSITE_REF', 'alpha');
		$tmpobject->description = GETPOST('WEBSITE_DESCRIPTION', 'alphanohtml');
		$tmpobject->lang = GETPOST('WEBSITE_LANG', 'aZ09');
		$tmpobject->otherlang = implode(',', $arrayotherlang);
		$tmpobject->virtualhost = GETPOST('virtualhost', 'alpha');

		$result = $tmpobject->create($user);
		if ($result == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorLabelAlreadyExists"), null, 'errors');
		} elseif ($result < 0) {
			$error++;
			setEventMessages($tmpobject->error, $tmpobject->errors, 'errors');
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SiteAdded", $object->ref), null, 'mesgs');
		$action = '';

		header("Location: ".$_SERVER["PHP_SELF"].'?website='.$tmpobject->ref);
		exit;
	} else {
		$db->rollback();
		$action = 'createsite';
	}

	if (!$error) {
		$action = 'preview';
		$id = $object->id;
	}
}

'@phan-var-force int $error';

// Add page/container
if ($action == 'addcontainer' && $usercanedit) {
	dol_mkdir($pathofwebsite);

	$db->begin();

	$objectpage->fk_website = $object->id;

	if (GETPOSTISSET('fetchexternalurl')) {	// Fetch from external url
		$urltograb = GETPOST('externalurl', 'alpha');
		$grabimages = GETPOST('grabimages', 'alpha');
		$grabimagesinto = GETPOST('grabimagesinto', 'alpha');

		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
		// The include seems to break typing on variables

		if (empty($urltograb)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("URL")), null, 'errors');
			$action = 'createcontainer';
		} elseif (!preg_match('/^http/', $urltograb)) {
			$error++;
			$langs->load("errors");
			setEventMessages('Error URL must start with http:// or https://', null, 'errors');
			$action = 'createcontainer';
		}

		if (!$error) {
			// Clean url to grab, so url can be
			// http://www.example.com/ or http://www.example.com/dir1/ or http://www.example.com/dir1/aaa
			$urltograbwithoutdomainandparam = preg_replace('/^https?:\/\/[^\/]+\/?/i', '', $urltograb);
			//$urltograbwithoutdomainandparam = preg_replace('/^file:\/\/[^\/]+\/?/i', '', $urltograb);
			$urltograbwithoutdomainandparam = preg_replace('/\?.*$/', '', $urltograbwithoutdomainandparam);
			if (empty($urltograbwithoutdomainandparam) && !preg_match('/\/$/', $urltograb)) {
				$urltograb .= '/';
			}
			$pageurl = dol_sanitizeFileName(preg_replace('/[\/\.]/', '-', preg_replace('/\/+$/', '', $urltograbwithoutdomainandparam)));

			$urltograbdirwithoutslash = dirname($urltograb.'.');
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograbdirwithoutslash);
			// Example, now $urltograbdirwithoutslash is https://www.dolimed.com/screenshots
			// and $urltograbdirrootwithoutslash is https://www.dolimed.com
		}

		// Check pageurl is not already used
		if ($pageurl) {
			$tmpwebsitepage = new WebsitePage($db);
			$result = $tmpwebsitepage->fetch(0, $object->id, $pageurl);
			if ($result > 0) {
				setEventMessages($langs->trans("AliasPageAlreadyExists", $pageurl), null, 'errors');
				$error++;
				$action = 'createcontainer';
			}
		}

		if (!$error) {
			$tmp = getURLContent($urltograb, 'GET', '', 1, array(), array('http', 'https'), 0);

			// Test charset of result and convert it into UTF-8 if not in this encoding charset
			if (!empty($tmp['content_type']) && preg_match('/ISO-8859-1/', $tmp['content_type'])) {
				if (function_exists('mb_check_encoding')) {
					if (mb_check_encoding($tmp['content'], 'ISO-8859-1')) {
						// This is a ISO-8829-1 encoding string
						$tmp['content'] = mb_convert_encoding($tmp['content'], 'ISO-8859-1', 'UTF-8');
					} else {
						$error++;
						setEventMessages('Error getting '.$urltograb.': content seems non valid ISO-8859-1', null, 'errors');
						$action = 'createcontainer';
					}
				} else {
					$error++;
					setEventMessages('Error getting '.$urltograb.': content seems ISO-8859-1 but functions to convert into UTF-8 are not available in your PHP', null, 'errors');
					$action = 'createcontainer';
				}
			}
			if (empty($tmp['content_type']) || (!empty($tmp['content_type']) && preg_match('/UTF-8/', $tmp['content_type']))) {
				if (function_exists('mb_check_encoding')) {
					if (mb_check_encoding($tmp['content'], 'UTF-8')) {
						// This is a UTF8 or ASCII compatible string
					} else {
						$error++;
						setEventMessages('Error getting '.$urltograb.': content seems not a valid UTF-8', null, 'errors');
						$action = 'createcontainer';
					}
				}
			}

			if ($tmp['curl_error_no']) {
				$error++;
				setEventMessages('Error getting '.$urltograb.': '.$tmp['curl_error_msg'], null, 'errors');
				$action = 'createcontainer';
			} elseif ($tmp['http_code'] != '200') {
				$error++;
				setEventMessages('Error getting '.$urltograb.': '.$tmp['http_code'], null, 'errors');
				$action = 'createcontainer';
			} else {
				// Remove comments
				$tmp['content'] = removeHtmlComment($tmp['content']);

				// Check there is no PHP content into the imported file (must be only HTML + JS)
				$phpcontent = dolKeepOnlyPhpCode($tmp['content']);
				if ($phpcontent) {
					$error++;
					setEventMessages('Error getting '.$urltograb.': file that include PHP content is not allowed', null, 'errors');
					$action = 'createcontainer';
				}
			}

			if (!$error) {
				$regs = array();

				preg_match('/<head>(.*)<\/head>/ims', $tmp['content'], $regs);
				$head = $regs[1];

				$objectpage->type_container = 'page';
				$objectpage->pageurl = $pageurl;
				if (empty($objectpage->pageurl)) {
					$tmpdomain = getDomainFromURL($urltograb);
					$objectpage->pageurl = $tmpdomain.'-home';
				}

				$objectpage->aliasalt = '';

				if (preg_match('/^(\d+)\-/', basename($urltograb), $regs)) {
					$objectpage->aliasalt = $regs[1];
				}

				$regtmp = array();
				if (preg_match('/<title>(.*)<\/title>/ims', $head, $regtmp)) {
					$objectpage->title = $regtmp[1];
				}
				if (preg_match('/<meta name="title"[^"]+content="([^"]+)"/ims', $head, $regtmp)) {
					if (empty($objectpage->title)) {
						$objectpage->title = $regtmp[1]; // If title not found into <title>, we get it from <meta title>
					}
				}
				if (preg_match('/<meta name="description"[^"]+content="([^"]+)"/ims', $head, $regtmp)) {
					$objectpage->description = $regtmp[1];
				}
				if (preg_match('/<meta name="keywords"[^"]+content="([^"]+)"/ims', $head, $regtmp)) {
					$objectpage->keywords = $regtmp[1];
				}
				if (preg_match('/<html\s+lang="([^"]+)"/ims', $tmp['content'], $regtmp)) {
					$tmplang = explode('-', $regtmp[1]);
					$objectpage->lang = $tmplang[0].($tmplang[1] ? '_'.strtoupper($tmplang[1]) : '');
				}

				$tmp['content'] = preg_replace('/\s*<meta name="generator"[^"]+content="([^"]+)"\s*\/?>/ims', '', $tmp['content']);

				$objectpage->content = $tmp['content'];
				$objectpage->content = preg_replace('/^.*<body(\s[^>]*)*>/ims', '', $objectpage->content);
				$objectpage->content = preg_replace('/<\/body(\s[^>]*)*>.*$/ims', '', $objectpage->content);

				// TODO Replace 'action="$urltograbdirwithoutslash' into action="/"
				// TODO Replace 'action="$urltograbdirwithoutslash..."' into   action="..."
				// TODO Replace 'a href="$urltograbdirwithoutslash' into a href="/"
				// TODO Replace 'a href="$urltograbdirwithoutslash..."' into a href="..."

				// Now loop to fetch all css files. Include them inline into header of page
				$objectpage->htmlheader = $tmp['content'];
				$objectpage->htmlheader = preg_replace('/^.*<head(\s[^>]*)*>/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<\/head(\s[^>]*)*>.*$/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<base(\s[^>]*)*>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<meta http-equiv="content-type"([^>]*)*>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<meta name="robots"([^>]*)*>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<meta name="title"([^>]*)*>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<meta name="description"([^>]*)*>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<meta name="keywords"([^>]*)*>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<meta name="generator"([^>]*)*>\n*/ims', '', $objectpage->htmlheader);
				//$objectpage->htmlheader = preg_replace('/<meta name="verify-v1[^>]*>\n*/ims', '', $objectpage->htmlheader);
				//$objectpage->htmlheader = preg_replace('/<meta name="msvalidate.01[^>]*>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<title>[^<]*<\/title>\n*/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<link[^>]*rel="shortcut[^>]*>\n/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<link[^>]*rel="alternate[^>]*>\n/ims', '', $objectpage->htmlheader);
				$objectpage->htmlheader = preg_replace('/<link[^>]*rel="canonical[^>]*>\n/ims', '', $objectpage->htmlheader);

				// Now loop to fetch JS
				$tmp = $objectpage->htmlheader;

				// We grab files found into <script> tags
				preg_match_all('/<script([^\.>]+)src=["\']([^"\'>]+)["\']([^>]*)><\/script>/i', $objectpage->htmlheader, $regs);
				$errorforsubresource = 0;
				foreach ($regs[0] as $key => $val) {
					dol_syslog("We will grab the script resource found into script tag ".$regs[2][$key]);

					$linkwithoutdomain = $regs[2][$key];
					if (preg_match('/^\//', $regs[2][$key])) {
						$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
					} else {
						$urltograbbis = $urltograbdirwithoutslash.'/'.$regs[2][$key]; // We use dir of grabbed file
					}

					//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];
					if (preg_match('/^http/', $regs[2][$key])) {
						$urltograbbis = $regs[2][$key];
						$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
						//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
					}

					//print $domaintograb.' - '.$domaintograbbis.' - '.$urltograbdirwithoutslash.' - ';
					//print $linkwithoutdomain.' - '.$urltograbbis."<br>\n";

					// Test if this is an external URL of grabbed web site. If yes, we do not load resource
					$domaintograb = getDomainFromURL($urltograbdirwithoutslash);
					$domaintograbbis = getDomainFromURL($urltograbbis);
					if ($domaintograb != $domaintograbbis) {
						continue;
					}

					/*
					$tmpgeturl = getURLContent($urltograbbis, 'GET', '', 1, array(), array('http', 'https'), 0);
					if ($tmpgeturl['curl_error_no'])
					{
						$error++;
						setEventMessages('Error getting script url '.$urltograbbis.': '.$tmpgeturl['curl_error_msg'], null, 'errors');
						$errorforsubresource++;
						$action='createcontainer';
					}
					elseif ($tmpgeturl['http_code'] != '200')
					{
						$error++;
						setEventMessages('Error getting script url '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
						$errorforsubresource++;
						$action='createcontainer';
					}
					else
					{
						dol_mkdir(dirname($filetosave));

						$fp = fopen($filetosave, "w");
						fputs($fp, $tmpgeturl['content']);
						fclose($fp);
						dolChmod($file);
					}
					*/

					//$filename = 'image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
					$tmp = preg_replace('/'.preg_quote($regs[0][$key], '/').'/i', '', $tmp);
				}
				$objectpage->htmlheader = trim($tmp)."\n";


				// Now we grab CSS found into <link> tags
				$pagecsscontent = "\n".'<style>'."\n";

				preg_match_all('/<link([^\.>]+)href=["\']([^"\'>]+\.css[^"\'>]*)["\']([^>]*)>/i', $objectpage->htmlheader, $regs);
				$errorforsubresource = 0;
				foreach ($regs[0] as $key => $val) {
					dol_syslog("We will grab the css resources found into link tag ".$regs[2][$key]);

					$linkwithoutdomain = $regs[2][$key];
					if (preg_match('/^\//', $regs[2][$key])) {
						$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
					} else {
						$urltograbbis = $urltograbdirwithoutslash.'/'.$regs[2][$key]; // We use dir of grabbed file
					}

					//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];
					if (preg_match('/^http/', $regs[2][$key])) {
						$urltograbbis = $regs[2][$key];
						$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
						//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
					}

					//print $domaintograb.' - '.$domaintograbbis.' - '.$urltograbdirwithoutslash.' - ';
					//print $linkwithoutdomain.' - '.$urltograbbis."<br>\n";

					// Test if this is an external URL of grabbed web site. If yes, we do not load resource
					$domaintograb = getDomainFromURL($urltograbdirwithoutslash);
					$domaintograbbis = getDomainFromURL($urltograbbis);
					if ($domaintograb != $domaintograbbis) {
						continue;
					}

					$tmpgeturl = getURLContent($urltograbbis, 'GET', '', 1, array(), array('http', 'https'), 0);
					if ($tmpgeturl['curl_error_no']) {
						$errorforsubresource++;
						setEventMessages('Error getting link tag url '.$urltograbbis.': '.$tmpgeturl['curl_error_msg'], null, 'errors');
						dol_syslog('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg']);
						$action = 'createcontainer';
					} elseif ($tmpgeturl['http_code'] != '200') {
						$errorforsubresource++;
						setEventMessages('Error getting link tag url '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
						dol_syslog('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg']);
						$action = 'createcontainer';
					} else {
						// Clean some comment
						//$tmpgeturl['content'] = dol_string_is_good_iso($tmpgeturl['content'], 1);
						//$tmpgeturl['content'] = mb_convert_encoding($tmpgeturl['content'], 'UTF-8', 'UTF-8');
						//$tmpgeturl['content'] = remove_bs($tmpgeturl['content']);
						//$tmpgeturl['content'] = str_replace('$screen-md-max', 'auto', $tmpgeturl['content']);

						//var_dump($tmpgeturl['content']);exit;
						$tmpgeturl['content'] = preg_replace('/\/\*\s+CSS content[a-z\s]*\s+\*\//', '', $tmpgeturl['content']);

						//dol_mkdir(dirname($filetosave));

						//$fp = fopen($filetosave, "w");
						//fputs($fp, $tmpgeturl['content']);
						//fclose($fp);
						//dolChmod($file);

						//	$filename = 'image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
						$pagecsscontent .= '/* Content of file '.$urltograbbis.' */'."\n";

						getAllImages($object, $objectpage, $urltograbbis, $tmpgeturl['content'], $action, 1, $grabimages, $grabimagesinto);

						// We try to convert the CSS we got by adding a prefix .bodywebsite with lessc to avoid conflict with CSS of Dolibarr.
						include_once DOL_DOCUMENT_ROOT.'/core/class/lessc.class.php';
						$lesscobj = new Lessc();
						try {
							$contentforlessc = ".bodywebsite {\n".$tmpgeturl['content']."\n}\n";
							//print '<pre>'.$contentforlessc.'</pre>';
							$contentforlessc = $lesscobj->compile($contentforlessc);
							//var_dump($contentforlessc); exit;

							$pagecsscontent .= $contentforlessc."\n";
							//$pagecsscontent.=$tmpgeturl['content']."\n";
						} catch (exception $e) {
							//echo "failed to compile lessc";
							dol_syslog("Failed to compile the CSS from URL ".$urltograbbis." with lessc: ".$e->getMessage(), LOG_WARNING);
							$pagecsscontent .= $tmpgeturl['content']."\n";
						}

						$objectpage->htmlheader = preg_replace('/'.preg_quote($regs[0][$key], '/').'\n*/ims', '', $objectpage->htmlheader);
					}
				}

				$pagecsscontent .= '</style>';
				//var_dump($pagecsscontent);

				//print dol_escape_htmltag($tmp);exit;
				$objectpage->htmlheader .= trim($pagecsscontent)."\n";


				// Now we have to fetch all images into page
				$tmp = $objectpage->content;

				getAllImages($object, $objectpage, $urltograb, $tmp, $action, 1, $grabimages, $grabimagesinto);

				// Normalize links href to Dolibarr internal naming
				$tmp = preg_replace('/a href="\/([^\/"]+)\/([^\/"]+)"/', 'a href="/\1-\2.php"', $tmp);
				$tmp = preg_replace('/a href="\/([^\/"]+)\/([^\/"]+)\/([^\/"]+)"/', 'a href="/\1-\2-\3.php"', $tmp);
				$tmp = preg_replace('/a href="\/([^\/"]+)\/([^\/"]+)\/([^\/"]+)\/([^\/"]+)"/', 'a href="/\1-\2-\3-\4.php"', $tmp);

				//print dol_escape_htmltag($tmp);exit;
				$objectpage->content = $tmp;

				$objectpage->grabbed_from = $urltograb;
			}
		}
	} else {
		$newaliasnames = '';
		if (!$error && GETPOST('WEBSITE_ALIASALT', 'alpha')) {
			$arrayofaliastotest = explode(',', str_replace(array('<', '>'), '', GETPOST('WEBSITE_ALIASALT', 'alpha')));
			$websitepagetemp = new WebsitePage($db);
			foreach ($arrayofaliastotest as $aliastotest) {
				$aliastotest = trim(preg_replace('/\.php$/i', '', $aliastotest));

				// Disallow alias name pageX (already used to save the page with id)
				if (preg_match('/^page\d+/i', $aliastotest)) {
					$error++;
					$langs->load("errors");
					setEventMessages("Alias name 'pageX' is not allowed", null, 'errors');
					$action = 'createcontainer';
					break;
				} else {
					$result = $websitepagetemp->fetch(0, $object->id, $aliastotest);
					if ($result < 0) {
						$error++;
						$langs->load("errors");
						setEventMessages($websitepagetemp->error, $websitepagetemp->errors, 'errors');
						$action = 'createcontainer';
						break;
					}
					if ($result > 0) {
						$error++;
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists", $websitepagetemp->pageurl), null, 'errors');
						$action = 'createcontainer';
						break;
					}
					$newaliasnames .= ($newaliasnames ? ', ' : '').$aliastotest;
				}
			}
		}

		$objectpage->title = str_replace(array('<', '>'), '', GETPOST('WEBSITE_TITLE', 'alphanohtml'));
		$objectpage->type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'aZ09');
		$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
		$objectpage->aliasalt = $newaliasnames;
		$objectpage->description = str_replace(array('<', '>'), '', GETPOST('WEBSITE_DESCRIPTION', 'alphanohtml'));
		$objectpage->lang = GETPOST('WEBSITE_LANG', 'aZ09');
		$objectpage->otherlang = GETPOST('WEBSITE_OTHERLANG', 'aZ09comma');
		$objectpage->image = GETPOST('WEBSITE_IMAGE', 'alpha');
		$objectpage->keywords = str_replace(array('<', '>'), '', GETPOST('WEBSITE_KEYWORDS', 'alphanohtml'));
		$objectpage->allowed_in_frames = GETPOST('WEBSITE_ALLOWED_IN_FRAMES', 'aZ09');
		$objectpage->htmlheader = GETPOST('htmlheader', 'none');
		$objectpage->author_alias = GETPOST('WEBSITE_AUTHORALIAS', 'alphanohtml');
		$objectpage->object_type = GETPOST('WEBSITE_OBJECTCLASS');
		$objectpage->fk_object = GETPOST('WEBSITE_OBJECTID');
		$substitutionarray = array();
		$substitutionarray['__WEBSITE_CREATED_BY__'] = $user->getFullName($langs);

		// Define id of the page the new page is translation of
		/*
		if ($objectpage->lang == $object->lang) {
			// If
			$pageidfortranslation = (GETPOSTINT('pageidfortranslation') > 0 ? GETPOSTINT('pageidfortranslation') : 0);
			if ($pageidfortranslation > 0) {
				// We must update the page $pageidfortranslation to set fk_page = $object->id.
				// But what if page $pageidfortranslation is already linked to another ?
			}
		} else {
		*/
		$pageidfortranslation = (GETPOSTINT('pageidfortranslation') > 0 ? GETPOSTINT('pageidfortranslation') : 0);
		if ($pageidfortranslation > 0) {
			// Check if the page we are translation of is already a translation of a source page. if yes, we will use source id instead
			$objectpagetmp = new WebsitePage($db);
			$objectpagetmp->fetch($pageidfortranslation);
			if ($objectpagetmp->fk_page > 0) {
				$pageidfortranslation = $objectpagetmp->fk_page;
			}
		}
		$objectpage->fk_page = $pageidfortranslation;
		//}

		$content = '';
		if (GETPOSTISSET('content')) {
			//$content = GETPOST('content', 'restricthtmlallowunvalid');	// @TODO Use a restricthtmlallowunvalidwithphp
			$content = GETPOST('content', 'none');	// @TODO Use a restricthtmlallowunvalidwithphp

			$objectpage->content = make_substitutions($content, $substitutionarray);
		} else {
			/*$sample = GETPOST('sample', 'alpha');
			if (empty($sample)) {
				$sample = 'empty';
			}

			$pathtosample = DOL_DOCUMENT_ROOT.'/website/samples/page-sample-'.dol_sanitizeFileName(strtolower($sample)).'.html';
			*/
			// Init content with content into page-sample-...
			//$objectpage->content = make_substitutions(@file_get_contents($pathtosample), $substitutionarray);
		}
	}

	if (!$error) {
		if (empty($objectpage->pageurl)) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_PAGENAME")), null, 'errors');
			$error++;
			$action = 'createcontainer';
		} elseif (!preg_match('/^[a-z0-9\-\_]+$/i', $objectpage->pageurl)) {
			$langs->load("errors");
			setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
			$error++;
			$action = 'createcontainer';
		}
		if (empty($objectpage->title)) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_TITLE")), null, 'errors');
			$error++;
			$action = 'createcontainer';
		}
		if ($objectpage->fk_page > 0 && empty($objectpage->lang)) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLanguageRequiredIfPageIsTranslationOfAnother"), null, 'errors');
			$error++;
			$action = 'createcontainer';
		}
		if ($objectpage->fk_page > 0 && !empty($objectpage->lang)) {
			if ($objectpage->lang == $website->lang) {
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorLanguageMustNotBeSourceLanguageIfPageIsTranslationOfAnother"), null, 'errors');
				$error++;
				$action = 'createcontainer';
			}
		}
	}

	$pageid = 0;
	if (!$error) {
		$pageid = $objectpage->create($user);
		if ($pageid <= 0) {
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
			$action = 'createcontainer';
		}
	}

	if (!$error) {
		// Website categories association
		$categoriesarray = GETPOST('categories', 'array');
		$result = $objectpage->setCategories($categoriesarray);
		if ($result < 0) {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (!$error) {
		// If there is no home page yet, this new page will be set as the home page
		if (empty($object->fk_default_home)) {
			$object->fk_default_home = $pageid;
			$res = $object->update($user);
			if ($res <= 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				$filetpl = $pathofwebsite.'/page'.$pageid.'.tpl.php';

				// Generate the index.php page (to be the home page) and the wrapper.php file
				$result = dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl, $filewrapper, $object);

				if ($result <= 0) {
					setEventMessages('Failed to write file '.$fileindex, null, 'errors');
				}
			}
		}
	}

	if (!$error) {
		if ($pageid > 0) {
			$filealias = $pathofwebsite.'/'.$objectpage->pageurl.'.php';
			$filetpl = $pathofwebsite.'/page'.$objectpage->id.'.tpl.php';

			// Save page alias
			$result = dolSavePageAlias($filealias, $object, $objectpage);
			if (!$result) {
				setEventMessages('Failed to write file '.basename($filealias), null, 'errors');
			}

			// Save page of content
			$result = dolSavePageContent($filetpl, $object, $objectpage, 1);
			if ($result) {
				setEventMessages($langs->trans("Saved"), null, 'mesgs');
			} else {
				setEventMessages('Failed to write file '.$filetpl, null, 'errors');
				$action = 'createcontainer';
			}
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("PageAdded", $objectpage->pageurl), null, 'mesgs');
		$action = '';
	} else {
		$db->rollback();
	}

	if (!$error) {
		$pageid = $objectpage->id;

		// To generate the CSS, robot and htmlheader file.

		// Check symlink to medias and restore it if ko
		$pathtomedias = DOL_DATA_ROOT.'/medias';
		$pathtomediasinwebsite = $pathofwebsite.'/medias';
		if (!is_link(dol_osencode($pathtomediasinwebsite))) {
			dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
			dol_mkdir(dirname($pathtomediasinwebsite)); // To be sure dir for website exists
			$result = symlink($pathtomedias, $pathtomediasinwebsite);
		}

		// Now generate the master.inc.php page if it does not exists yet
		if (!dol_is_file($filemaster)) {
			$result = dolSaveMasterFile($filemaster);
			if (!$result) {
				$error++;
				setEventMessages('Failed to write file '.$filemaster, null, 'errors');
			}
		}

		if (!dol_is_file($filehtmlheader)) {
			$htmlheadercontent = "<html>\n";
			$htmlheadercontent .= $htmlheadercontentdefault;
			$htmlheadercontent .= "</html>";
			$result = dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent);
		}

		if (!dol_is_file($filecss)) {
			$csscontent = "/* CSS content (all pages) */\nbody.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }\n.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}";
			$result = dolSaveCssFile($filecss, $csscontent);
		}

		if (!dol_is_file($filejs)) {
			$jscontent = "/* JS content (all pages) */\n";
			$result = dolSaveJsFile($filejs, $jscontent);
		}

		if (!dol_is_file($filerobot)) {
			$robotcontent = "# Robot file. Generated with Dolibarr\nUser-agent: *\nAllow: /public/\nDisallow: /administrator/";
			$result = dolSaveRobotFile($filerobot, $robotcontent);
		}

		if (!dol_is_file($filehtaccess)) {
			$htaccesscontent = "# Order allow,deny\n# Deny from all";
			$result = dolSaveHtaccessFile($filehtaccess, $htaccesscontent);
		}

		if (!dol_is_file($filemanifestjson)) {
			$manifestjsoncontent = "";
			$result = dolSaveManifestJson($filemanifestjson, $manifestjsoncontent);
		}

		if (!dol_is_file($filereadme)) {
			$readmecontent = "Website generated by Dolibarr ERP CRM";
			$result = dolSaveReadme($filereadme, $readmecontent);
		}

		if (!dol_is_file($filelicense)) {
			$licensecontent = "MIT License";
			$result = dolSaveLicense($filelicense, $licensecontent);
		}

		$action = 'preview';
	}
}

// Delete site
if ($action == 'confirm_deletesite' && $confirm == 'yes' && $permissiontodelete) {
	$error = 0;

	$db->begin();

	$res = $object->fetch(GETPOSTINT('id'));
	$website = $object;

	if ($res > 0) {
		$res = $object->delete($user);
		if ($res <= 0) {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	if (!$error) {
		if (GETPOST('delete_also_js', 'alpha') == 'on') {
			$pathofwebsitejs = DOL_DATA_ROOT.'/medias/js/'.$object->ref;

			dol_delete_dir_recursive($pathofwebsitejs);
		}
		if (GETPOST('delete_also_medias', 'alpha') == 'on') {
			$pathofwebsitemedias = DOL_DATA_ROOT.'/medias/image/'.$object->ref;

			dol_delete_dir_recursive($pathofwebsitemedias);
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SiteDeleted", $object->ref), null, 'mesgs');

		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	} else {
		$db->rollback();
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Delete page (from website page menu)
if (GETPOSTISSET('pageid') && $action == 'delete' && $permissiontodelete && !GETPOST('file_manager')) {
	$error = 0;

	$db->begin();

	$res = $object->fetch(0, $websitekey);
	$website = $object;

	$res = $objectpage->fetch($pageid, $object->id);

	if ($res > 0) {
		$res = $objectpage->delete($user);
		if ($res <= 0) {
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("PageDeleted", $objectpage->pageurl, $websitekey), null, 'mesgs');

		header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey);
		exit;
	} else {
		$db->rollback();
		dol_print_error($db);
	}
}
// Delete page (from menu search)
if (!GETPOSTISSET('pageid')) {
	$objectclass = 'WebsitePage';

	// Add part of code from actions_massactions.inc.php
	// Delete record from mass action (massaction = 'delete' for direct delete, action/confirm='delete'/'yes' with a confirmation step before)
	if (!$error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
		$db->begin();

		$objecttmp = new $objectclass($db);
		$nbok = 0;
		foreach ($toselect as $toselectid) {
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$result = $objecttmp->delete($user);

				if ($result <= 0) {
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
					break;
				} else {
					$nbok++;
				}
			} else {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			}
		}

		if (!$error) {
			if ($nbok > 1) {
				setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
			}
			$db->commit();
		} else {
			$db->rollback();
		}
		//var_dump($listofobjectthirdparties);exit;
	}

	if ($action == 'delete') {
		$mode = 'replacesite';
		$action = 'replacesite';

		$containertype = GETPOST('optioncontainertype', 'aZ09') != '-1' ? GETPOST('optioncontainertype', 'aZ09') : '';
		$langcode = GETPOST('optionlanguage', 'aZ09');
		$otherfilters = array();
		if (GETPOSTINT('optioncategory') > 0) {
			$otherfilters['category'] = GETPOSTINT('optioncategory');
		}

		$listofpages = getPagesFromSearchCriterias($containertype, $algo, $searchkey, 1000, $sortfield, $sortorder, $langcode, $otherfilters);
	}
}

// Update css site properties. Re-generates also the wrapper.
if ($action == 'updatecss' && $usercanedit) {
	// If we tried to reload another site/page, we stay on editcss mode.
	if (GETPOST('refreshsite') || GETPOST('refreshsite_x') || GETPOST('refreshsite.x') || GETPOST('refreshpage') || GETPOST('refreshpage_x') || GETPOST('refreshpage.x')) {
		$action = 'editcss';
	} else {
		$res = $object->fetch(0, $websitekey);
		$website = $object;

		if (GETPOSTISSET('virtualhost')) {
			$tmpvirtualhost = preg_replace('/\/$/', '', GETPOST('virtualhost', 'alpha'));
			if ($tmpvirtualhost && !preg_match('/^http/', $tmpvirtualhost)) {
				$error++;
				setEventMessages($langs->trans('ErrorURLMustStartWithHttp', $langs->transnoentitiesnoconv("VirtualHost")), null, 'errors');
				$action = 'editcss';
			}

			if (!$error) {
				$arrayotherlang = explode(',', GETPOST('WEBSITE_OTHERLANG', 'alphanohtml'));
				foreach ($arrayotherlang as $key => $val) {
					// It possible we have empty val here if postparam WEBSITE_OTHERLANG is empty or set like this : 'en,,sv' or 'en,sv,'
					if (empty(trim($val))) {
						continue;
					}
					$arrayotherlang[$key] = substr(trim($val), 0, 2); // Kept short language code only
				}

				$object->virtualhost = $tmpvirtualhost;
				$object->lang = GETPOST('WEBSITE_LANG', 'aZ09');
				$object->otherlang = implode(',', $arrayotherlang);
				$object->use_manifest = GETPOSTINT('use_manifest');

				$result = $object->update($user);
				if ($result < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'editcss';
				}
			}
		}

		if (!$error) {
			if (($_FILES['addedfile']["name"] != '')) {
				$uploadfolder = $conf->website->dir_output.'/'.$websitekey;
				if ($_FILES['addedfile']['type'] != 'image/png') {
					$error++;
					setEventMessages($langs->trans('ErrorFaviconType'), array(), 'errors');
				}
				$filetoread = realpath(dol_osencode($_FILES['addedfile']['tmp_name']));
				$filesize = getimagesize($filetoread);
				if ($filesize[0] != $filesize[1]) {
					$error++;
					setEventMessages($langs->trans('ErrorFaviconMustBeASquaredImage'), array(), 'errors');
				}
				if (! $error && ($filesize[0] != 16 && $filesize[0] != 32 && $filesize[0] != 64)) {
					$error++;
					setEventMessages($langs->trans('ErrorFaviconSize'), array(), 'errors');
				}
				if (!$error) {
					dol_add_file_process($uploadfolder, 1, 0, 'addedfile', 'favicon.png');
				}
			}
			if ($error) {
				if (!GETPOSTISSET('updateandstay')) {	// If we click on "Save And Stay", we don not make the redirect
					$action = 'preview';
					if ($backtopage) {
						$backtopage = preg_replace('/searchstring=[^&]*/', '', $backtopage);	// Clean backtopage url
						header("Location: ".$backtopage);
						exit;
					}
				} else {
					$action = 'editcss';
				}
			}
		}

		if (!$error) {
			// Save master.inc.php file
			dol_syslog("Save master file ".$filemaster);

			dol_mkdir($pathofwebsite);

			// Now generate the master.inc.php page
			$result = dolSaveMasterFile($filemaster);
			if (!$result) {
				$error++;
				setEventMessages('Failed to write file '.$filemaster, null, 'errors');
			}


			$dataposted = trim(GETPOST('WEBSITE_HTML_HEADER', 'none'));
			$dataposted = preg_replace(array('/<html>\n*/ims', '/<\/html>\n*/ims'), array('', ''), $dataposted);
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// Html header file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$htmlheadercontent = '';

				/* We disable php code since htmlheader is never executed as an include but only read by fgets_content.
				$htmlheadercontent.= "<?php // BEGIN PHP\n";
				$htmlheadercontent.= '$websitekey=basename(__DIR__);'."\n";
				$htmlheadercontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once './master.inc.php'; } // Load env if not already loaded"."\n";
				$htmlheadercontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
				$htmlheadercontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
				$htmlheadercontent.= "ob_start();\n";
				// $htmlheadercontent.= "header('Content-type: text/html');\n";		// Not required. htmlheader.html is never call as a standalone page
				$htmlheadercontent.= "// END PHP ?>\n";*/

				$htmlheadercontent .= $dataposted."\n";

				/*$htmlheadercontent.= "\n".'<?php // BEGIN PHP'."\n";
				$htmlheadercontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
				$htmlheadercontent.= "// END PHP"."\n";*/

				$result = dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filehtmlheader, null, 'errors');
				}
			} else {
				$error++;
			}

			$dataposted = trim(GETPOST('WEBSITE_CSS_INLINE', 'none'));
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// Css file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$csscontent = '';

				$csscontent .= "<?php // BEGIN PHP\n";
				$csscontent .= '$websitekey=basename(__DIR__);'."\n";
				$csscontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Load env if not already loaded\n"; // For the css, we need to set path of master using the dirname of css file.
				$csscontent .= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
				$csscontent .= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
				$csscontent .= "ob_start();\n";
				$csscontent .= "if (! headers_sent()) {	/* because file is included inline when in edit mode and we don't want warning */ \n";
				$csscontent .= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
				$csscontent .= "header('Content-type: text/css');\n";
				$csscontent .= "}\n";
				$csscontent .= "// END PHP ?>\n";

				$csscontent .= $dataposted."\n";

				$csscontent .= '<?php // BEGIN PHP'."\n";
				$csscontent .= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "css");'."\n";
				$csscontent .= "// END PHP\n";

				dol_syslog("Save css content into ".$filecss);

				$result = dolSaveCssFile($filecss, $csscontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filecss, null, 'errors');
				}
			} else {
				$error++;
			}


			$dataposted = trim(GETPOST('WEBSITE_JS_INLINE', 'none'));
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// Js file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$jscontent = '';

				$jscontent .= "<?php // BEGIN PHP\n";
				$jscontent .= '$websitekey=basename(__DIR__);'."\n";
				$jscontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Load env if not already loaded\n"; // For the css, we need to set path of master using the dirname of css file.
				$jscontent .= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
				$jscontent .= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
				$jscontent .= "ob_start();\n";
				$jscontent .= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
				$jscontent .= "header('Content-type: application/javascript');\n";
				$jscontent .= "// END PHP ?>\n";

				$jscontent .= $dataposted."\n";

				$jscontent .= '<?php // BEGIN PHP'."\n";
				$jscontent .= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "js");'."\n";
				$jscontent .= "// END PHP\n";

				$result = dolSaveJsFile($filejs, $jscontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filejs, null, 'errors');
				}
			} else {
				$error++;
			}

			$dataposted = trim(GETPOST('WEBSITE_ROBOT', 'nohtml'));
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// Robot file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$robotcontent = '';

				/*$robotcontent.= "<?php // BEGIN PHP\n";
				$robotcontent.= '$websitekey=basename(__DIR__);'."\n";
				$robotcontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once './master.inc.php'; } // Load env if not already loaded"."\n";
				$robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
				$robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
				$robotcontent.= "ob_start();\n";
				$robotcontent.= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
				$robotcontent.= "header('Content-type: text/css');\n";
				$robotcontent.= "// END PHP ?>\n";*/

				$robotcontent .= $dataposted."\n";

				/*$robotcontent.= "\n".'<?php // BEGIN PHP'."\n";
				$robotcontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "robot");'."\n";
				$robotcontent.= "// END PHP ?>"."\n";*/

				$result = dolSaveRobotFile($filerobot, $robotcontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filerobot, null, 'errors');
				}
			} else {
				$error++;
			}

			$dataposted = trim(GETPOST('WEBSITE_HTACCESS', 'nohtml'));
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// Htaccess file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$htaccesscontent = '';
				$htaccesscontent .= $dataposted."\n";

				$result = dolSaveHtaccessFile($filehtaccess, $htaccesscontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filehtaccess, null, 'errors');
				}
			} else {
				$error++;
			}


			$dataposted = trim(GETPOST('WEBSITE_MANIFEST_JSON', 'none'));
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// Manifest.json file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$manifestjsoncontent = '';

				$manifestjsoncontent .= "<?php // BEGIN PHP\n";
				$manifestjsoncontent .= '$websitekey=basename(__DIR__);'."\n";
				$manifestjsoncontent .= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Load env if not already loaded\n"; // For the css, we need to set path of master using the dirname of css file.
				$manifestjsoncontent .= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
				$manifestjsoncontent .= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
				$manifestjsoncontent .= "ob_start();\n";
				$manifestjsoncontent .= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
				$manifestjsoncontent .= "header('Content-type: application/manifest+json');\n";
				$manifestjsoncontent .= "// END PHP ?>\n";

				$manifestjsoncontent .= $dataposted."\n";

				$manifestjsoncontent .= '<?php // BEGIN PHP'."\n";
				$manifestjsoncontent .= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "manifest");'."\n";
				$manifestjsoncontent .= "// END PHP\n";

				$result = dolSaveManifestJson($filemanifestjson, $manifestjsoncontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filemanifestjson, null, 'errors');
				}
			} else {
				$error++;
			}


			$dataposted = trim(GETPOST('WEBSITE_README', 'nohtml'));
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// README.md file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$readmecontent = '';

				/*$readmecontent.= "<?php // BEGIN PHP\n";
				   $readmecontent.= '$websitekey=basename(__DIR__);'."\n";
				   $readmecontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Load env if not already loaded"."\n";	// For the css, we need to set path of master using the dirname of css file.
				   $readmecontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
				   $readmecontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
				   $readmecontent.= "ob_start();\n";
				   $readmecontent.= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
				   $readmecontent.= "header('Content-type: application/manifest+json');\n";
				   $readmecontent.= "// END PHP ?>\n";*/

				$readmecontent .= $dataposted."\n";

				/*$readmecontent.= '<?php // BEGIN PHP'."\n";
				   $readmecontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "manifest");'."\n";
				   $readmecontent.= "// END PHP ?>"."\n";*/

				$result = dolSaveReadme($filereadme, $readmecontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filereadme, null, 'errors');
				}
			} else {
				$error++;
			}


			$dataposted = trim(GETPOST('WEBSITE_LICENSE', 'nohtml'));
			$dataposted = str_replace('<?=', '<?php', $dataposted);

			// LICENSE file
			$phpfullcodestringold = '';
			$phpfullcodestring = dolKeepOnlyPhpCode($dataposted);

			// Security analysis
			$errorphpcheck = checkPHPCode($phpfullcodestringold, $phpfullcodestring);	// Contains the setEventMessages

			if (!$errorphpcheck) {
				$licensecontent = '';

				/*$readmecontent.= "<?php // BEGIN PHP\n";
				 $readmecontent.= '$websitekey=basename(__DIR__);'."\n";
				 $readmecontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Load env if not already loaded"."\n";	// For the css, we need to set path of master using the dirname of css file.
				 $readmecontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
				 $readmecontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
				 $readmecontent.= "ob_start();\n";
				 $readmecontent.= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
				 $readmecontent.= "header('Content-type: application/manifest+json');\n";
				 $readmecontent.= "// END PHP ?>\n";*/

				$licensecontent .= $dataposted."\n";

				/*$readmecontent.= '<?php // BEGIN PHP'."\n";
				 $readmecontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "manifest");'."\n";
				 $readmecontent.= "// END PHP ?>"."\n";*/

				$result = dolSaveLicense($filelicense, $licensecontent);
				if (!$result) {
					$error++;
					setEventMessages('Failed to write file '.$filelicense, null, 'errors');
				}
			} else {
				$error++;
			}

			// Save wrapper.php
			$result = dolSaveIndexPage($pathofwebsite, '', '', $filewrapper, $object);


			// Message if no error
			if (!$error) {
				setEventMessages($langs->trans("Saved"), null, 'mesgs');
			}

			if (!GETPOSTISSET('updateandstay')) {	// If we click on "Save And Stay", we don not make the redirect
				$action = 'preview';
				if ($backtopage) {
					$backtopage = preg_replace('/searchstring=[^&]*/', '', $backtopage);	// Clean backtopage url
					header("Location: ".$backtopage);
					exit;
				}
			} else {
				$action = 'editcss';
			}
		}
	}
}

// Update page
if ($action == 'setashome' && $usercanedit) {
	$db->begin();
	$object->fetch(0, $websitekey);
	$website = $object;

	$object->fk_default_home = $pageid;
	$res = $object->update($user);
	if (! ($res > 0)) {
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}

	if (!$error) {
		$db->commit();

		$filetpl = $pathofwebsite.'/page'.$pageid.'.tpl.php';

		// Generate the index.php page to be the home page
		$result = dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl, $filewrapper, $object);

		if ($result) {
			setEventMessages($langs->trans("Saved"), null, 'mesgs');
		} else {
			setEventMessages('Failed to write file '.$fileindex, null, 'errors');
		}

		$action = 'preview';
	} else {
		$db->rollback();
	}
}

// Update page properties (meta)
if ($action == 'updatemeta' && $usercanedit) {
	$db->begin();

	$result = $object->fetch(0, $websitekey);
	$website = $object;

	$objectpage->fk_website = $object->id;

	// Check parameters
	if (!preg_match('/^[a-z0-9\-\_]+$/i', GETPOST('WEBSITE_PAGENAME', 'alpha'))) {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
		$action = 'editmeta';
	}

	$res = $objectpage->fetch($pageid, $object->id);
	if ($res <= 0) {
		$error++;
		setEventMessages('Page not found '.$objectpage->error, $objectpage->errors, 'errors');
	}

	// Check alias not exists
	if (!$error && GETPOST('WEBSITE_PAGENAME', 'alpha')) {
		$websitepagetemp = new WebsitePage($db);
		$result = $websitepagetemp->fetch(-1 * $objectpage->id, $object->id, GETPOST('WEBSITE_PAGENAME', 'alpha'));
		if ($result < 0) {
			$error++;
			$langs->load("errors");
			setEventMessages($websitepagetemp->error, $websitepagetemp->errors, 'errors');
			$action = 'editmeta';
		}
		if ($result > 0) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists", $websitepagetemp->pageurl), null, 'errors');
			$action = 'editmeta';
		}
	}

	$newaliasnames = '';
	if (!$error && GETPOST('WEBSITE_ALIASALT', 'alpha')) {
		$arrayofaliastotest = explode(',', str_replace(array('<', '>'), '', GETPOST('WEBSITE_ALIASALT', 'alpha')));

		$websitepagetemp = new WebsitePage($db);
		foreach ($arrayofaliastotest as $aliastotest) {
			$aliastotest = trim(preg_replace('/\.php$/i', '', $aliastotest));

			// Disallow alias name pageX (already used to save the page with id)
			if (preg_match('/^page\d+/i', $aliastotest)) {
				$error++;
				$langs->load("errors");
				setEventMessages("Alias name 'pageX' is not allowed", null, 'errors');
				$action = 'editmeta';
				break;
			} else {
				$result = $websitepagetemp->fetch(-1 * $objectpage->id, $object->id, $aliastotest);
				if ($result < 0) {
					$error++;
					$langs->load("errors");
					setEventMessages($websitepagetemp->error, $websitepagetemp->errors, 'errors');
					$action = 'editmeta';
					break;
				}
				if ($result > 0) {
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists", $websitepagetemp->pageurl), null, 'errors');
					$action = 'editmeta';
					break;
				}
				$newaliasnames .= ($newaliasnames ? ', ' : '').$aliastotest;
			}
		}
	}

	if (!$error) {
		$objectpage->old_object = clone $objectpage;

		$objectpage->title = str_replace(array('<', '>'), '', GETPOST('WEBSITE_TITLE', 'alphanohtml'));
		$objectpage->type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'aZ09');
		$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
		$objectpage->aliasalt = $newaliasnames;
		$objectpage->lang = GETPOST('WEBSITE_LANG', 'aZ09');
		$objectpage->otherlang = GETPOST('WEBSITE_OTHERLANG', 'aZ09comma');
		$objectpage->description = str_replace(array('<', '>'), '', GETPOST('WEBSITE_DESCRIPTION', 'alphanohtml'));
		$objectpage->image = GETPOST('WEBSITE_IMAGE', 'alpha');
		$objectpage->keywords = str_replace(array('<', '>'), '', GETPOST('WEBSITE_KEYWORDS', 'alphanohtml'));
		$objectpage->allowed_in_frames = GETPOST('WEBSITE_ALLOWED_IN_FRAMES', 'aZ09');
		$objectpage->htmlheader = trim(GETPOST('htmlheader', 'none'));
		$objectpage->fk_page = (GETPOSTINT('pageidfortranslation') > 0 ? GETPOSTINT('pageidfortranslation') : 0);
		$objectpage->author_alias = trim(GETPOST('WEBSITE_AUTHORALIAS', 'alphanohtml'));
		$objectpage->object_type = GETPOST('WEBSITE_OBJECTCLASS', 'alpha');
		$objectpage->fk_object = GETPOST('WEBSITE_OBJECTID', 'aZ09');

		$newdatecreation = dol_mktime(GETPOSTINT('datecreationhour'), GETPOSTINT('datecreationmin'), GETPOSTINT('datecreationsec'), GETPOSTINT('datecreationmonth'), GETPOSTINT('datecreationday'), GETPOSTINT('datecreationyear'));
		if ($newdatecreation) {
			$objectpage->date_creation = $newdatecreation;
		}

		$res = $objectpage->update($user);
		if (!($res > 0)) {
			$langs->load("errors");
			if ($db->lasterrno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists"), null, 'errors');
				$action = 'editmeta';
			} else {
				$error++;
				$langs->load("errors");
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
				$action = 'editmeta';
			}
		}
	}

	if (!$error) {
		// Website categories association
		$categoriesarray = GETPOST('categories', 'array');
		$result = $objectpage->setCategories($categoriesarray);
		if ($result < 0) {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
	}

	if (!$error) {
		$filemaster = $pathofwebsite.'/master.inc.php';
		$fileoldalias = $pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
		$filealias = $pathofwebsite.'/'.$objectpage->pageurl.'.php';

		dol_mkdir($pathofwebsite);

		// Now generate the master.inc.php page
		$result = dolSaveMasterFile($filemaster);
		if (!$result) {
			setEventMessages('Failed to write file '.$filemaster, null, 'errors');
		}

		// Now delete the alias.php page
		if (!empty($fileoldalias)) {
			dol_syslog("We delete old alias page name=".$fileoldalias." to build a new alias page=".$filealias);
			dol_delete_file($fileoldalias);

			// Delete also pages into language subdirectories
			if (empty($objectpage->lang) || !in_array($objectpage->lang, explode(',', $object->otherlang))) {
				$dirname = dirname($fileoldalias);
				$filename = basename($fileoldalias);
				$sublangs = explode(',', $object->otherlang);
				foreach ($sublangs as $sublang) {
					// Under certain conditions $sublang can be an empty string
					// ($object->otherlang with empty string or with string like this 'en,,sv')
					// if is the case we try to re-delete the main alias file. Avoid it.
					if (empty(trim($sublang))) {
						continue;
					}
					$fileoldaliassub = $dirname.'/'.$sublang.'/'.$filename;
					dol_delete_file($fileoldaliassub);
				}
			}
		}
		// Now delete the alternative alias.php pages
		if (!empty($objectpage->old_object->aliasalt)) {
			$tmpaltaliases = explode(',', $objectpage->old_object->aliasalt);
			if (is_array($tmpaltaliases)) {
				foreach ($tmpaltaliases as $tmpaliasalt) {
					dol_syslog("We delete old alt alias pages name=".trim($tmpaliasalt));
					dol_delete_file($pathofwebsite.'/'.trim($tmpaliasalt).'.php');

					// Delete also pages into language subdirectories
					if (empty($objectpage->lang) || !in_array($objectpage->lang, explode(',', $object->otherlang))) {
						$dirname = dirname($pathofwebsite.'/'.trim($tmpaliasalt).'.php');
						$filename = basename($pathofwebsite.'/'.trim($tmpaliasalt).'.php');
						$sublangs = explode(',', $object->otherlang);
						foreach ($sublangs as $sublang) {
							// Under certain conditions $ sublang can be an empty string
							// ($object->otherlang with empty string or with string like this 'en,,sv')
							// if is the case we try to re-delete the main alias file. Avoid it.
							if (empty(trim($sublang))) {
								continue;
							}
							$fileoldaliassub = $dirname.'/'.$sublang.'/'.$filename;
							dol_delete_file($fileoldaliassub);
						}
					}
				}
			}
		}

		// Save page main alias
		$result = dolSavePageAlias($filealias, $object, $objectpage);
		if (!$result) {
			setEventMessages('Failed to write file '.$filealias, null, 'errors');
		}
		// Save alt aliases
		if (!empty($objectpage->aliasalt)) {
			$tmpaltaliases = explode(',', $objectpage->aliasalt);
			if (is_array($tmpaltaliases)) {
				foreach ($tmpaltaliases as $tmpaliasalt) {
					if (trim($tmpaliasalt)) {
						$filealias = $pathofwebsite.'/'.trim($tmpaliasalt).'.php';
						$result = dolSavePageAlias($filealias, $object, $objectpage);
						if (!$result) {
							setEventMessages('Failed to write file '.basename($filealias), null, 'errors');
						}
					}
				}
			}
		}


		// Save page of content
		$result = dolSavePageContent($filetpl, $object, $objectpage, 1);
		if ($result) {
			setEventMessages($langs->trans("Saved"), null, 'mesgs');

			if (!GETPOSTISSET('updateandstay')) {	// If we click on "Save And Stay", we do not make the redirect
				//header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
				//exit;
				$action = 'preview';
			} else {
				$action = 'editmeta';
			}
		} else {
			setEventMessages('Failed to write file '.$filetpl, null, 'errors');
			//header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
			//exit;
			$action = 'preview';
		}
	}
}

// Update page
if ($usercanedit && (($action == 'updatesource' || $action == 'updatecontent' || $action == 'confirm_createfromclone' || $action == 'confirm_createpagefromclone')
	|| ($action == 'preview' && (GETPOST('refreshsite') || GETPOST('refreshpage') || GETPOST('preview'))))) {
	$object->fetch(0, $websitekey);
	$website = $object;

	if ($action == 'confirm_createfromclone') {
		$db->begin();

		$objectnew = new Website($db);
		$result = $objectnew->createFromClone($user, GETPOSTINT('id'), GETPOST('siteref'), (GETPOSTINT('newlang') ? GETPOSTINT('newlang') : ''));

		if ($result < 0) {
			$error++;
			setEventMessages($objectnew->error, $objectnew->errors, 'errors');
			$action = 'preview';

			$db->rollback();
		} else {
			setEventMessages("ObjectClonedSuccessfuly", null, 'mesgs');
			$object = $objectnew;
			$id = $object->id;
			$pageid = $object->fk_default_home;
			$websitekey = GETPOST('siteref', 'aZ09');

			$db->commit();
		}
	}

	if ($action == 'confirm_createpagefromclone') {
		$istranslation = (GETPOST('is_a_translation', 'aZ09') == 'on' ? 1 : 0);
		// Protection if it is a translation page
		if ($istranslation) {
			if (GETPOST('newlang', 'aZ09') == $objectpage->lang || !GETPOST('newlang', 'aZ09')) {
				$error++;
				setEventMessages($langs->trans("LanguageMustNotBeSameThanClonedPage"), null, 'errors');
				$action = 'preview';
			}
			if (GETPOSTINT('newwebsite') != $object->id) {
				$error++;
				setEventMessages($langs->trans("WebsiteMustBeSameThanClonedPageIfTranslation"), null, 'errors');
				$action = 'preview';
			}
		}

		if (!$error) {
			$db->begin();

			$newwebsiteid = GETPOSTINT('newwebsite');
			$pathofwebsitenew = $pathofwebsite;

			$tmpwebsite = new Website($db);
			if ($newwebsiteid > 0 && $newwebsiteid != $object->id) {
				$tmpwebsite->fetch($newwebsiteid);
				$pathofwebsitenew = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$tmpwebsite->ref;
			} else {
				$tmpwebsite = $object;
			}

			$objectpage = new WebsitePage($db);
			$resultpage = $objectpage->createFromClone($user, $pageid, GETPOST('newpageurl', 'aZ09'), (GETPOST('newlang', 'aZ09') ? GETPOST('newlang', 'aZ09') : ''), $istranslation, $newwebsiteid, GETPOST('newtitle', 'alphanohtml'), $tmpwebsite);
			if ($resultpage < 0) {
				$error++;
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
				$action = 'createpagefromclone';

				$db->rollback();
			} else {
				$filetpl = $pathofwebsitenew.'/page'.$resultpage->id.'.tpl.php';
				$fileindex = $pathofwebsitenew.'/index.php';
				$filewrapper = $pathofwebsitenew.'/wrapper.php';

				//var_dump($pathofwebsitenew);
				//var_dump($filetpl);
				//exit;

				dolSavePageContent($filetpl, $tmpwebsite, $resultpage, 1);

				// Switch on the new page if web site of new page/container is same
				if (empty($newwebsiteid) || $newwebsiteid == $object->id) {
					$pageid = $resultpage->id;
				}

				$db->commit();
			}
		}
	}

	$res = 0;

	if (!$error) {
		// Check symlink to medias and restore it if ko
		$pathtomedias = DOL_DATA_ROOT.'/medias';
		$pathtomediasinwebsite = $pathofwebsite.'/medias';
		if (!is_link(dol_osencode($pathtomediasinwebsite))) {
			dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
			dol_mkdir(dirname($pathtomediasinwebsite)); // To be sure dir for website exists
			$result = symlink($pathtomedias, $pathtomediasinwebsite);
		}

		/*if (GETPOST('savevirtualhost') && $object->virtualhost != GETPOST('previewsite'))
		{
			$object->virtualhost = GETPOST('previewsite', 'alpha');
			$object->update($user);
		}*/

		$objectpage->fk_website = $object->id;

		if ($pageid > 0) {
			$res = $objectpage->fetch($pageid);
		} else {
			$res = 0;
			if ($object->fk_default_home > 0) {
				$res = $objectpage->fetch($object->fk_default_home);
			}
			if (!($res > 0)) {
				$res = $objectpage->fetch(0, $object->id);
			}
		}
	}

	if (!$error && $res > 0) {
		if ($action == 'updatesource' || $action == 'updatecontent') {
			$db->begin();

			$phpfullcodestringold = dolKeepOnlyPhpCode($objectpage->content);

			$objectpage->content = GETPOST('PAGE_CONTENT', 'none');	// any HTML content allowed

			$phpfullcodestring = dolKeepOnlyPhpCode($objectpage->content);

			// Security analysis (check PHP content and check permission website->writephp if php content is modified)
			$error = checkPHPCode($phpfullcodestringold, $phpfullcodestring);

			if ($error) {
				if ($action == 'updatesource') {
					$action = 'editsource';
				}
				if ($action == 'updatecontent') {
					$action = 'editcontent';
				}
			}

			// Clean data. We remove all the head section.
			$objectpage->content = preg_replace('/<head>.*<\/head>/ims', '', $objectpage->content);
			/* $objectpage->content = preg_replace('/<base\s+href=[\'"][^\'"]+[\'"]\s/?>/s', '', $objectpage->content); */


			$res = $objectpage->update($user);
			if ($res < 0) {
				$error++;
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
				if ($action == 'updatesource') {
					$action = 'editsource';
				}
				if ($action == 'updatecontent') {
					$action = 'editcontent';
				}
			}

			if (!$error) {
				$db->commit();

				$filemaster = $pathofwebsite.'/master.inc.php';
				//$fileoldalias=$pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
				$filealias = $pathofwebsite.'/'.$objectpage->pageurl.'.php';

				dol_mkdir($pathofwebsite);

				// Now generate the master.inc.php page
				$result = dolSaveMasterFile($filemaster);

				if (!$result) {
					setEventMessages('Failed to write the master file file '.$filemaster, null, 'errors');
				}

				// Now delete the old alias.php page if we removed one
				/*if (!empty($fileoldalias))
				{
					dol_syslog("We regenerate alias page new name=".$filealias.", old name=".$fileoldalias);
					dol_delete_file($fileoldalias);

					// Delete also pages into language subdirectories
					if (empty($objectpage->lang) || !in_array($objectpage->lang, explode(',', $object->otherlang))) {
						$dirname = dirname($fileoldalias);
						$filename = basename($fileoldalias);
						$sublangs = explode(',', $object->otherlang);
						foreach ($sublangs as $sublang) {
							$fileoldaliassub = $dirname.'/'.$sublang.'/'.$filename;
							dol_delete_file($fileoldaliassub);
						}
					}
				}*/

				// Save page alias
				$result = dolSavePageAlias($filealias, $object, $objectpage);
				if (!$result) {
					setEventMessages('Failed to write the alias file '.basename($filealias), null, 'errors');
				}

				// Save page content
				$result = dolSavePageContent($filetpl, $object, $objectpage, 1);
				if ($result) {
					setEventMessages($langs->trans("Saved"), null, 'mesgs');

					if (!GETPOSTISSET('updateandstay')) {	// If we click on "Save And Stay", we do not make the redirect
						if ($backtopage) {
							header("Location: ".$backtopage);
							exit;
						} else {
							header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
							exit;
						}
					} else {
						if ($action == 'updatesource') {
							$action = 'editsource';
						}
						if ($action == 'updatecontent') {
							$action = 'editcontent';
						}
					}
				} else {
					setEventMessages('Failed to write file '.$filetpl, null, 'errors');
					header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
					exit;
				}
			} else {
				$db->rollback();
			}
		} else {
			header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
			exit;
		}
	} else {
		if (!$error) {
			if (empty($websitekey) || $websitekey == '-1') {
				setEventMessages($langs->trans("NoWebSiteCreateOneFirst"), null, 'warnings');
			} else {
				setEventMessages($langs->trans("NoPageYet"), null, 'warnings');
				setEventMessages($langs->trans("YouCanCreatePageOrImportTemplate"), null, 'warnings');
			}
		}
	}
}

if ($action == 'deletelang' && $usercanedit) {
	$sql = "UPDATE ".MAIN_DB_PREFIX."website_page SET fk_page = NULL";
	$sql .= " WHERE rowid = ".GETPOSTINT('deletelangforid');
	//$sql .= " AND fk_page = ".((int) $objectpage->id);

	$resql = $db->query($sql);
	if (!$resql) {
		setEventMessages($db->lasterror(), null, 'errors');
	} else {
		$objectpage->fk_page = null;
	}

	$action = 'editmeta';
}


// Export site
if ($action == 'exportsite' && $user->hasRight('website', 'export')) {
	$fileofzip = $object->exportWebSite();

	if ($fileofzip) {
		$file_name = basename($fileofzip);
		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=".$file_name);
		header("Content-Length: ".filesize($fileofzip));

		readfile($fileofzip);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'preview';
	}
}

// Overwrite site
if ($action == 'overwritesite' && $user->hasRight('website', 'export')) {
	if (getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE')) {
		$fileofzip = $object->exportWebSite();
		$pathToExport = GETPOST('export_path');
		if ($fileofzip) {
			$object->overwriteTemplate($fileofzip, $pathToExport);
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}
// Regenerate site
if ($action == 'regeneratesite' && $usercanedit) {
	// Check symlink to medias and restore it if ko. Recreate also dir of website if not found.
	$pathtomedias = DOL_DATA_ROOT.'/medias';
	$pathtomediasinwebsite = $pathofwebsite.'/medias';
	if (!is_link(dol_osencode($pathtomediasinwebsite))) {
		dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
		dol_mkdir(dirname($pathtomediasinwebsite)); // To be sure that the directory for website exists
		$result = symlink($pathtomedias, $pathtomediasinwebsite);
		if (!$result) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFailedToCreateSymLinkToMedias", $pathtomediasinwebsite, $pathtomedias), null, 'errors');
			$action = 'preview';
		}
	}

	$result = $object->rebuildWebSiteFiles();
	if ($result > 0) {
		setEventMessages($langs->trans("PagesRegenerated", $result), null, 'mesgs');
		$action = 'preview';
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'preview';
	}
}

// Import site
if ($action == 'importsiteconfirm' && $usercanedit) {
	$dolibarrdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);
	$allowimportsite = true;
	if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) {
		$allowimportsite = false;
	}

	if ($allowimportsite) {
		if (empty($_FILES) && !GETPOSTISSET('templateuserfile')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
			$action = 'importsite';
		} else {
			if (!empty($_FILES) || GETPOSTISSET('templateuserfile')) {
				// Check symlink to medias and restore it if ko. Recreate also dir of website if not found.
				$pathtomedias = DOL_DATA_ROOT.'/medias';
				$pathtomediasinwebsite = $pathofwebsite.'/medias';
				if (!is_link(dol_osencode($pathtomediasinwebsite))) {
					dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
					dol_mkdir(dirname($pathtomediasinwebsite)); // To be sure dir for website exists
					$result = symlink($pathtomedias, $pathtomediasinwebsite);
					if (!$result) {
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorFailedToCreateSymLinkToMedias", $pathtomediasinwebsite, $pathtomedias), null, 'errors');
						$action = 'importsite';
					}
				}

				$fileofzip = '';
				if (GETPOSTISSET('templateuserfile')) {
					// Case we selected one template
					$fileofzip = DOL_DATA_ROOT.'/doctemplates/websites/'.GETPOST('templateuserfile', 'alpha');	// $fileofzip will be sanitized later into the importWebSite()
				} elseif (!empty($_FILES) && is_array($_FILES['userfile'])) {
					// Case we upload a new template
					if (is_array($_FILES['userfile']['tmp_name'])) {
						$userfiles = $_FILES['userfile']['tmp_name'];
					} else {
						$userfiles = array($_FILES['userfile']['tmp_name']);
					}

					// Check if $_FILES is ok
					foreach ($userfiles as $key => $userfile) {
						if (empty($_FILES['userfile']['tmp_name'][$key])) {
							$error++;
							if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
								setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
								$action = 'importsite';
							} else {
								setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
								$action = 'importsite';
							}
						}
					}

					if (!$error) {
						//$upload_dir = $conf->website->dir_temp;
						$upload_dir = DOL_DATA_ROOT.'/doctemplates/websites/';
						$result = dol_add_file_process($upload_dir, 1, -1, 'userfile', '');
					}

					// Get name of file (take last one if several name provided)
					/*
					$fileofzip = $upload_dir.'/unknown';
					foreach ($_FILES as $key => $ifile) {
						foreach ($ifile['name'] as $key2 => $ifile2) {
							$fileofzip = $upload_dir.'/'.$ifile2;
						}
					}
					*/

					$action = 'importsite';
				}

				if (!$error && GETPOSTISSET('templateuserfile')) {
					$templatewithoutzip = preg_replace('/\.zip$/i', '', GETPOST('templateuserfile'));
					$object->setTemplateName($templatewithoutzip);

					$result = $object->importWebSite($fileofzip);

					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$action = 'importsite';
					} else {
						// Force mode dynamic on
						dolibarr_set_const($db, 'WEBSITE_SUBCONTAINERSINLINE', 1, 'chaine', 0, '', $conf->entity);

						header("Location: ".$_SERVER["PHP_SELF"].'?website='.$object->ref);
						exit();
					}
				}
			}
		}
	} else {
		if (getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US')) {
			// Show clean corporate message
			$message = $langs->trans('InstallModuleFromWebHasBeenDisabledContactUs');
		} else {
			// Show technical generic message
			$message = $langs->trans("InstallModuleFromWebHasBeenDisabledByFile", $dolibarrdataroot.'/installmodules.lock');
		}
		setEventMessages($message, null, 'errors');
	}
}

$domainname = '0.0.0.0:8080';
$tempdir = $conf->website->dir_output.'/'.$websitekey.'/';

// Generate web site sitemaps
if ($action == 'generatesitemaps' && $usercanedit) {
	// Define $domainname
	if ($website->virtualhost) {
		$domainname = $website->virtualhost;
	}
	if (! preg_match('/^http/i', $domainname)) {
		$domainname = 'https://'.$domainname;
	}

	$domtree = new DOMDocument('1.0', 'UTF-8');

	$root = $domtree->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
	$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

	$domtree->formatOutput = true;

	$addrsswrapper = 0;
	$xmlname = 'sitemap.xml';

	$sql = "SELECT wp.rowid, wp.type_container , wp.pageurl, wp.lang, wp.fk_page, wp.tms as tms,";
	$sql .= " w.virtualhost, w.fk_default_home";
	$sql .= " FROM ".MAIN_DB_PREFIX."website_page as wp, ".MAIN_DB_PREFIX."website as w";
	$sql .= " WHERE wp.type_container IN ('page', 'blogpost')";
	$sql .= " AND wp.fk_website = w.rowid";
	$sql .= " AND wp.status = ".WebsitePage::STATUS_VALIDATED;
	$sql .= " AND wp.pageurl NOT IN ('404', '500', '501', '503')";
	$sql .= " AND w.ref = '".dol_escape_json($websitekey)."'";
	$sql .= " ORDER BY wp.tms DESC, wp.rowid DESC";
	$resql = $db->query($sql);
	if ($resql) {
		$num_rows = $db->num_rows($resql);
		if ($num_rows > 0) {
			$i = 0;
			while ($i < $num_rows) {
				$objp = $db->fetch_object($resql);
				$url = $domtree->createElement('url');

				$shortlangcode = '';
				if ($objp->lang) {
					$shortlangcode = substr($objp->lang, 0, 2); // en_US or en-US -> en
				}
				if (empty($shortlangcode)) {
					$shortlangcode = substr($object->lang, 0, 2); // Use short lang code of website
				}

				// Is it a blog post for the RSS wrapper ?
				if ($objp->type_container == 'blogpost') {
					$addrsswrapper = 1;
				}

				// Forge $pageurl, adding language prefix if it is an alternative language
				$pageurl = $objp->pageurl.'.php';
				if ($objp->fk_default_home == $objp->rowid) {
					$pageurl = '';
				} else {
					if ($shortlangcode != substr($object->lang, 0, 2)) {
						$pageurl = $shortlangcode.'/'.$pageurl;
					}
				}

				//$pathofpage = $dolibarr_main_url_root.'/'.$pageurl.'.php';

				// URL of sitemaps must end with trailing slash if page is ''
				$loc = $domtree->createElement('loc', $domainname.'/'.$pageurl);
				$lastmod = $domtree->createElement('lastmod', dol_print_date($db->jdate($objp->tms), 'dayrfc', 'gmt'));
				$priority = $domtree->createElement('priority', '1');

				$url->appendChild($loc);
				$url->appendChild($lastmod);
				// Add suggested frequency for refresh
				if (getDolGlobalString('WEBSITE_SITEMAPS_ADD_WEEKLY_FREQ')) {
					$changefreq = $domtree->createElement('changefreq', 'weekly');	// TODO Manage other values
					$url->appendChild($changefreq);
				}
				// Add higher priority for home page
				if ($objp->fk_default_home == $objp->rowid) {
					$url->appendChild($priority);
				}

				// Now add alternate language entries
				if ($object->isMultiLang()) {
					$alternatefound = 0;

					// Add page "translation of"
					$translationof = $objp->fk_page;
					if ($translationof) {
						$tmppage = new WebsitePage($db);
						$tmppage->fetch($translationof);
						if ($tmppage->id > 0) {
							$tmpshortlangcode = '';
							if ($tmppage->lang) {
								$tmpshortlangcode = preg_replace('/[_-].*$/', '', $tmppage->lang); // en_US or en-US -> en
							}
							if (empty($tmpshortlangcode)) {
								$tmpshortlangcode = preg_replace('/[_-].*$/', '', $object->lang); // en_US or en-US -> en
							}
							if ($tmpshortlangcode != $shortlangcode) {
								$xhtmllink = $domtree->createElement('xhtml:link', '');
								$xhtmllink->setAttribute("rel", "alternate");
								$xhtmllink->setAttribute("hreflang", $tmpshortlangcode);
								$xhtmllink->setAttribute("href", $domainname.($objp->fk_default_home == $tmppage->id ? '/' : (($tmpshortlangcode != substr($object->lang, 0, 2)) ? '/'.$tmpshortlangcode : '').'/'.$tmppage->pageurl.'.php'));
								$url->appendChild($xhtmllink);

								$alternatefound++;
							}
						}
					}

					// Add "has translation pages"
					$sql = 'SELECT rowid as id, lang, pageurl from '.MAIN_DB_PREFIX.'website_page';
					$sql .= " WHERE status = ".((int) WebsitePage::STATUS_VALIDATED).' AND fk_page IN ('.$db->sanitize($objp->rowid.($translationof ? ", ".$translationof : "")).")";
					$resqlhastrans = $db->query($sql);
					if ($resqlhastrans) {
						$num_rows_hastrans = $db->num_rows($resqlhastrans);
						if ($num_rows_hastrans > 0) {
							while ($objhastrans = $db->fetch_object($resqlhastrans)) {
								$tmpshortlangcode = '';
								if ($objhastrans->lang) {
									$tmpshortlangcode = preg_replace('/[_-].*$/', '', $objhastrans->lang); // en_US or en-US -> en
								}
								if ($tmpshortlangcode != $shortlangcode) {
									$xhtmllink = $domtree->createElement('xhtml:link', '');
									$xhtmllink->setAttribute("rel", "alternate");
									$xhtmllink->setAttribute("hreflang", $tmpshortlangcode);
									$xhtmllink->setAttribute("href", $domainname.($objp->fk_default_home == $objhastrans->id ? '/' : (($tmpshortlangcode != substr($object->lang, 0, 2) ? '/'.$tmpshortlangcode : '')).'/'.$objhastrans->pageurl.'.php'));
									$url->appendChild($xhtmllink);

									$alternatefound++;
								}
							}
						}
					} else {
						dol_print_error($db);
					}

					if ($alternatefound) {
						// Add myself
						$xhtmllink = $domtree->createElement('xhtml:link', '');
						$xhtmllink->setAttribute("rel", "alternate");
						$xhtmllink->setAttribute("hreflang", $shortlangcode);
						$xhtmllink->setAttribute("href", $domainname.'/'.$pageurl);
						$url->appendChild($xhtmllink);
					}
				}

				// Now add sitempas extension for news
				// TODO When adding and when not ?
				/*<news:news>
				   <news:publication>
					 <news:name>The Example Times</news:name>
					 <news:language>en</news:language>
				   </news:publication>
				   <news:publication_date>2008-12-23</news:publication_date>
					 <news:title>Companies A, B in Merger Talks</news:title>
					</news:news>
				*/

				$root->appendChild($url);
				$i++;
			}

			// Adding a RSS feed into a sitemap should not be required. The RSS contains pages that are already included into
			// the sitemap and RSS feeds are not shown into index.
			if ($addrsswrapper && getDolGlobalInt('WEBSITE_ADD_RSS_FEED_INTO_SITEMAP')) {
				$url = $domtree->createElement('url');

				$pageurl = 'wrapper.php?rss=1';

				// URL of sitemaps must end with trailing slash if page is ''
				$loc = $domtree->createElement('loc', $domainname.'/'.$pageurl);
				$lastmod = $domtree->createElement('lastmod', dol_print_date($db->jdate(dol_now()), 'dayrfc', 'gmt'));

				$url->appendChild($loc);
				$url->appendChild($lastmod);
				// Add suggested frequency for refresh
				if (getDolGlobalString('WEBSITE_SITEMAPS_ADD_WEEKLY_FREQ')) {
					$changefreq = $domtree->createElement('changefreq', 'weekly');	// TODO Manage other values
					$url->appendChild($changefreq);
				}

				$root->appendChild($url);
			}

			$domtree->appendChild($root);

			if ($domtree->save($tempdir.$xmlname)) {
				dolChmod($tempdir.$xmlname);
				setEventMessages($langs->trans("SitemapGenerated", $xmlname), null, 'mesgs');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} else {
		dol_print_error($db);
	}

	// Add the entry Sitemap: into the robot.txt file.
	$robotcontent = @file_get_contents($filerobot);
	$result = preg_replace('/<?php \/\/ BEGIN PHP[^?]END PHP ?>\n/ims', '', $robotcontent);
	if ($result) {
		$robotcontent = $result;
	}
	$robotsitemap = "Sitemap: ".$domainname."/".$xmlname;
	$result = strpos($robotcontent, 'Sitemap: ');
	if ($result) {
		$result = preg_replace('/Sitemap:.*/', $robotsitemap, $robotcontent);
		$robotcontent = $result ? $result : $robotcontent;
	} else {
		$robotcontent .= $robotsitemap."\n";
	}
	$result = dolSaveRobotFile($filerobot, $robotcontent);
	if (!$result) {
		$error++;
		setEventMessages('Failed to write file '.$filerobot, null, 'errors');
	}
	$action = 'preview';
}


/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);
$formwebsite = new FormWebsite($db);
$formother = new FormOther($db);
$formconfirm = "";

// Confirm generation of website sitemaps
if ($action == 'confirmgeneratesitemaps') {
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?website='.urlencode($website->ref), $langs->trans('ConfirmSitemapsCreation'), $langs->trans('ConfirmGenerateSitemaps', $object->ref), 'generatesitemaps', '', "yes", 1);
	$action = 'preview';
}
$helpurl = 'EN:Module_Website|FR:Module_Website_FR|ES:M&oacute;dulo_Website';

$arrayofjs = array(
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	//'/includes/ace/src/ext-chromevox.js'
	//'/includes/jquery/plugins/jqueryscoped/jquery.scoped.js',
);
$arrayofcss = array();

$moreheadcss = '';
$moreheadjs = '';

$arrayofjs[] = 'includes/jquery/plugins/blockUI/jquery.blockUI.js';
$arrayofjs[] = 'core/js/blockUI.js'; // Used by ecm/tpl/enabledfiletreeajax.tpl.php
if (!getDolGlobalString('MAIN_ECM_DISABLE_JS')) {
	$arrayofjs[] = "includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js";
}

$moreheadjs .= '<script type="text/javascript">'."\n";
$moreheadjs .= 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
$moreheadjs .= '</script>'."\n";

llxHeader($moreheadcss.$moreheadjs, $langs->trans("Website").(empty($website->ref) ? '' : ' - '.$website->ref), $helpurl, '', 0, 0, $arrayofjs, $arrayofcss, '', '', '<!-- Begin div class="fiche" -->'."\n".'<div class="fichebutwithotherclass">');

print "\n";
print '<!-- Open form for all page -->'."\n";
print '<form action="'.$_SERVER["PHP_SELF"].($action == 'file_manager' ? '?uploadform=1' : '').'" method="POST" enctype="multipart/form-data" class="websiteformtoolbar">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';

if ($action == 'createsite') {
	print '<input type="hidden" name="action" value="addsite">';
}
if ($action == 'createcontainer') {
	print '<input type="hidden" name="action" value="addcontainer">';
}
if ($action == 'editcss') {
	print '<input type="hidden" name="action" value="updatecss">';
}
if ($action == 'editmenu') {
	print '<input type="hidden" name="action" value="updatemenu">';
}
if ($action == 'setashome') {
	print '<input type="hidden" name="action" value="updateashome">';
}
if ($action == 'editmeta') {
	print '<input type="hidden" name="action" value="updatemeta">';
}
if ($action == 'editsource') {
	print '<input type="hidden" name="action" value="updatesource">';
}
if ($action == 'editcontent') {
	print '<input type="hidden" name="action" value="updatecontent">';
}
if ($action == 'edit') {
	print '<input type="hidden" name="action" value="update">';
}
if ($action == 'importsite') {
	print '<input type="hidden" name="action" value="importsiteconfirm">';
}
if ($action == 'file_manager') {
	print '<input type="hidden" name="action" value="file_manager">';
}
if ($mode) {
	print '<input type="hidden" name="mode" value="'.$mode.'">';
}

print '<div>';

// Add a margin under toolbar ?
$style = '';
if ($action != 'preview' && $action != 'editcontent' && $action != 'editsource' && !GETPOST('createpagefromclone', 'alphanohtml')) {
	$style = ' margin-bottom: 5px;';
}


if (!GETPOST('hide_websitemenu')) {
	$disabled = '';
	if (!$user->hasRight('website', 'write')) {
		$disabled = ' disabled="disabled"';
	}
	$disabledexport = '';
	if (!$user->hasRight('website', 'export')) {
		$disabledexport = ' disabled="disabled"';
	}

	if ($websitekey) {
		$virtualurl = '';
		$dataroot = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$websitekey;
		if (!empty($object->virtualhost)) {
			$virtualurl = $object->virtualhost;
		}
	}

	$array = array();
	if ($object->id > 0) {
		$array = $objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
		$object->lines = $array;
	}
	if (!is_array($array) && $array < 0) {
		dol_print_error(null, $objectpage->error, $objectpage->errors);
	}
	$atleastonepage = (is_array($array) && count($array) > 0);

	$websitepage = new WebsitePage($db);
	if ($pageid > 0) {
		$websitepage->fetch($pageid);
	}


	//var_dump($objectpage);exit;
	print '<div class="centpercent websitebar'.(GETPOST('dol_openinpopup', 'aZ09') ? ' hiddenforpopup' : '').'">';

	//
	// Toolbar for websites
	//

	print '<!-- Toolbar for website -->';
	if ($action != 'file_manager') {
		print '<div class="websiteselection hideonsmartphoneimp minwidth75 tdoverflowmax100 inline-block">';
		print $langs->trans("Website").': ';
		print '</div>';

		// Button Add new website
		$urltocreatenewwebsite = $_SERVER["PHP_SELF"].'?action=createsite';
		print '<span class="websiteselection paddingrightonly">';
		print '<a href="'.$urltocreatenewwebsite.'" class=""'.$disabled.' title="'.dol_escape_htmltag($langs->trans("AddWebsite")).'"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"><span></a>';
		print '</span>';

		// List of website
		print '<span class="websiteselection nopaddingrightimp">';

		$out = '';
		$out .= '<select name="website" class="minwidth100 width200 maxwidth150onsmartphone" id="website">';
		if (empty($listofwebsites)) {
			$out .= '<option value="-1">&nbsp;</option>';
		}

		// Loop on each sites
		$i = 0;
		foreach ($listofwebsites as $key => $valwebsite) {
			if (empty($websitekey)) {
				if ($action != 'createsite') {
					$websitekey = $valwebsite->ref;
				}
			}

			$out .= '<option value="'.$valwebsite->ref.'"';
			if ($websitekey == $valwebsite->ref) {
				$out .= ' selected'; // To preselect a value
			}
			//$outoption = $valwebsite->getLibStatut(3).' '.$valwebsite->ref.' ';
			$outoption = (($valwebsite->status == $valwebsite::STATUS_DRAFT) ? '<span class="opacitymedium">' : '').$valwebsite->ref.(($valwebsite->status == $valwebsite::STATUS_DRAFT) ? '</span>' : '');
			$out .= ' data-html="'.dol_escape_htmltag($outoption).'"';
			$out .= '>';
			$out .= $valwebsite->ref;
			$out .= '</option>';
			$i++;
		}
		$out .= '</select>';
		$out .= ajax_combobox('website');

		if (!empty($conf->use_javascript_ajax)) {
			$out .= '<script type="text/javascript">';
			$out .= 'jQuery(document).ready(function () {';
			$out .= '	jQuery("#website").change(function () {';
			$out .= '   	console.log("We select "+jQuery("#website option:selected").val());';
			$out .= '   	if (jQuery("#website option:selected").val() == \'-2\') {';
			$out .= '  			window.location.href = "'.dol_escape_js($urltocreatenewwebsite).'";';
			$out .= '		} else {';
			$out .= '  			window.location.href = "'.$_SERVER["PHP_SELF"].'?website="+jQuery("#website option:selected").val();';
			$out .= '       }';
			$out .= '   });';
			$out .= '});';
			$out .= '</script>';
		}
		print $out;

		print '</span>';

		// Switch offline/onine
		if (!empty($conf->use_javascript_ajax)) {
			print '<span class="websiteselection">';
			// Do not use ajax, we need a refresh of full page when we change status of a website
			//print '<div class="inline-block marginrightonly">';
			//print ajax_object_onoff($object, 'status', 'status', 'Online', 'Offline', array(), 'valignmiddle inline-block', 'statuswebsite');
			//print '</div>';
			if ($website->status == $website::STATUS_DRAFT) {
				$text_off = 'Offline';
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setwebsiteonline&token='.newToken().'&website='.urlencode($website->ref).'&websitepage='.((int) $websitepage->id).'">'.img_picto($langs->trans($text_off), 'switch_off').'</a>';
			} else {
				$text_off = 'Online';
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=setwebsiteoffline&token='.newToken().'&website='.urlencode($website->ref).'&websitepage='.((int) $websitepage->id).'">'.img_picto($langs->trans($text_off), 'switch_on').'</a>';
			}
			print '</span>';
		}

		// Refresh / Reload web site (for non javascript browsers)
		if (empty($conf->use_javascript_ajax)) {
			print '<span class="websiteselection">';
			print '<input type="image" class="valignmiddle" src="'.img_picto('', 'refresh', '', 0, 1).'" name="refreshsite" value="'.$langs->trans("Load").'">';
			print '</span>';
		}


		print '<span class="websiteselection">';

		if ($websitekey && $websitekey != '-1' && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite')) {
			// Edit website properties
			print '<a href="'.$_SERVER["PHP_SELF"].'?website='.urlencode($object->ref).'&pageid='.((int) $pageid).'&action=editcss&token='.newToken().'" class="button bordertransp" title="'.dol_escape_htmltag($langs->trans("EditCss")).'"'.$disabled.'><span class="fa fa-cog paddingrightonly"></span><span class="hideonsmartphone">'.dol_escape_htmltag($langs->trans("EditCss")).'</span></a>';

			// Import web site
			$importlabel = $langs->trans("ImportSite");
			$exportlabel = $langs->trans("ExportSite");
			if (!empty($conf->dol_optimize_smallscreen)) {
				$importlabel = $langs->trans("Import");
				$exportlabel = $langs->trans("Export");
			}

			if ($atleastonepage) {
				print '<input type="submit" class="button bordertransp" disabled="disabled" value="'.dol_escape_htmltag($importlabel).'" name="importsite">';
			} else {
				print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($importlabel).'" name="importsite">';
			}

			// // Export web site
			$extraCssClass = getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE') ? 'hideobject' : '';
			print '<input type="submit" class="button bordertransp ' . $extraCssClass . '" ' . $disabledexport . ' value="' . dol_escape_htmltag($exportlabel) . '" name="exportsite">';

			if (getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE')) {
				// Overwrite template in sources
				$overwriteGitUrl = $_SERVER["PHP_SELF"] . '?action=overwritesite&website=' . urlencode($website->ref);
				print dolButtonToOpenExportDialog('exportpopup', $langs->trans('ExportOptions'), $langs->trans('ExportSite'), 'exportsite', $overwriteGitUrl, $website);
				//print '<a href="'.$_SERVER["PHP_SELF"].'?action=overwritesite&website='.urlencode($website->ref).'" class="button bordertransp hideobject" title="'.dol_escape_htmltag($langs->trans("ExportIntoGIT").". Directory ".getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE')).'">'.dol_escape_htmltag($langs->trans("ExportIntoGIT")).'</a>';
			}

			// Clone web site
			print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("CloneSite")).'" name="createfromclone">';

			// Delete website
			if (!$permissiontodelete) {
				$disabled = ' disabled="disabled"';
				$title = $langs->trans("NotEnoughPermissions");
				$url = '#';
			} else {
				if ($website->status == $website::STATUS_VALIDATED) {
					$disabled = ' disabled="disabled"';
					$title = $langs->trans("WebsiteMustBeDisabled", $langs->transnoentitiesnoconv($website->LibStatut(0, 0)));
					$url = '#';
				} else {
					$disabled = '';
					$title = $langs->trans("Delete");
					$url = $_SERVER["PHP_SELF"].'?action=deletesite&token='.newToken().'&website='.urlencode($website->ref);
				}
			}
			print '<a href="'.$url.'" class="button buttonDelete bordertransp'.($disabled ? ' disabled' : '').'"'.$disabled.' title="'.dol_escape_htmltag($title).'">'.img_picto('', 'delete', 'class=""').'<span class="hideonsmartphone paddingleft">'.$langs->trans("Delete").'</span></a>';

			// Regenerate all pages
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=regeneratesite&token='.newToken().'&website='.urlencode($website->ref).'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("RegenerateWebsiteContent")).'"><span class="far fa-hdd"></span></a>';

			// Generate site map
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=confirmgeneratesitemaps&token='.newToken().'&website='.urlencode($website->ref).'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("GenerateSitemaps")).'"><span class="fa fa-sitemap"></span></a>';

			// Find / replace tool
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=replacesite&website='.urlencode($website->ref).'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("ReplaceWebsiteContent")).'"><span class="fa fa-search"></span></a>';
		}

		print '</span>';

		if ($websitekey && $websitekey != '-1' && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite')) {
			print '<span class="websiteselection">';

			print dolButtonToOpenUrlInDialogPopup('file_manager', $langs->transnoentitiesnoconv("MediaFiles"), '<span class="fa fa-image"></span>', '/website/index.php?action=file_manager&website='.urlencode($website->ref).'&section_dir='.urlencode('image/'.$website->ref.'/'), $disabled);

			if (isModEnabled('category')) {
				//print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=website&dol_hide_leftmenu=1&nosearch=1&type=website_page&website='.$website->ref.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("Categories")).'"><span class="fa fa-tags"></span></a>';
				print dolButtonToOpenUrlInDialogPopup('categories', $langs->transnoentitiesnoconv("Categories"), '<span class="fa fa-tags"></span>', '/categories/index.php?leftmenu=website&nosearch=1&type='.urlencode(Categorie::TYPE_WEBSITE_PAGE).'&website='.urlencode($website->ref), $disabled);
			}

			print '</span>';
		}
	} else {
		print '<input type="hidden" name="website" id="website" value="'.$websitekey.'">';
	}


	print '<span class="websitetools">';

	if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite') {
		$urlext = $virtualurl;
		$urlint = $urlwithroot.'/public/website/index.php?website='.$websitekey;

		print '<span class="websiteinputurl valignmiddle" id="websiteinputurl">';
		$linktotestonwebserver = '<a href="'.($virtualurl ? $virtualurl : '#').'" class="valignmiddle">';
		$linktotestonwebserver .= '<span class="hideonsmartphone paddingrightonly">'.$langs->trans("TestDeployOnWeb", $virtualurl).'</span>'.img_picto('', 'globe');
		$linktotestonwebserver .= '</a>';

		$htmltext = '';
		if (empty($object->fk_default_home)) {
			$htmltext .= '<br><span class="error">'.$langs->trans("YouMustDefineTheHomePage").'</span><br><br>';
		} elseif (empty($virtualurl)) {
			//$htmltext .= '<br><span class="error">'.$langs->trans("VirtualHostUrlNotDefined").'</span><br><br>';
		} else {
			$htmltext .= '<br><center>'.$langs->trans("GoTo").' <a href="'.$virtualurl.'" target="_website">'.$virtualurl.'</a></center><br>';
		}
		if (getDolGlobalString('WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER')) {
			$htmltext .= '<!-- Message defined translate key set into WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER -->';
			$htmltext .= '<br>'.$langs->trans(getDolGlobalString('WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER'));
		} else {
			$htmltext .= $langs->trans("ToDeployYourWebsiteOnLiveYouHave3Solutions").'<br><br>';
			$htmltext .= '<div class="titre inline-block">1</div> - '.$langs->trans("SetHereVirtualHost", $dataroot);
			$htmltext .= '<br>';
			$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
			$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), '{s1}');
			$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias', $htmltext);

			$examplewithapache = "<VirtualHost *:80>\n";
			$examplewithapache .= '#php_admin_value open_basedir /tmp/:'.DOL_DOCUMENT_ROOT.':'.DOL_DATA_ROOT.':/dev/urandom'."\n";
			$examplewithapache .= "\n";
			$examplewithapache .= 'DocumentRoot "'.DOL_DOCUMENT_ROOT.'"'."\n";
			$examplewithapache .= "\n";
			$examplewithapache .= '<Directory "'.DOL_DOCUMENT_ROOT.'">'."\n";
			$examplewithapache .= 'AllowOverride FileInfo Options
    		Options       -Indexes -MultiViews -FollowSymLinks -ExecCGI
    		Require all granted
    		</Directory>'."\n".'
    		<Directory "'.DOL_DATA_ROOT.'/website">
    		AllowOverride FileInfo Options
    		Options       -Indexes -MultiViews +FollowSymLinks -ExecCGI
    		Require all granted
    		</Directory>'."\n".'
    		<Directory "'.DOL_DATA_ROOT.'/medias">
    		AllowOverride FileInfo Options
    		Options       -Indexes -MultiViews -FollowSymLinks -ExecCGI
    		Require all granted
    		</Directory>'."\n";

			$examplewithapache .= "\n";
			$examplewithapache .= "#ErrorLog /var/log/apache2/".$websitekey."_error_log\n";
			$examplewithapache .= "#TransferLog /var/log/apache2/".$websitekey."_access_log\n";

			$examplewithapache .= "</VirtualHost>\n";

			$htmltext .= '<br>'.$langs->trans("ExampleToUseInApacheVirtualHostConfig").':<br>';
			$htmltext .= '<div class="quatrevingtpercent exampleapachesetup wordbreak" spellcheck="false">'.dol_nl2br(dol_escape_htmltag($examplewithapache, 1, 1)).'</div>';

			$htmltext .= '<br>';
			$htmltext .= '<div class="titre inline-block">2</div> - '.$langs->trans("YouCanAlsoTestWithPHPS");
			$htmltext .= '<br><div class="urllink"><input type="text" id="cliphpserver" spellcheck="false" class="quatrevingtpercent" value="php -S 0.0.0.0:8080 -t '.$dataroot.'"></div>';
			$htmltext .= ajax_autoselect("cliphpserver");
			$htmltext .= '<br>';
			$htmltext .= '<div class="titre inline-block">3</div> - '.$langs->trans("YouCanAlsoDeployToAnotherWHP");
		}
		print $form->textwithpicto($linktotestonwebserver, $htmltext, 1, 'none', 'valignmiddle', 0, 3, 'helpvirtualhost');
		print '</span>';
	}

	if (in_array($action, array('editcss', 'editmenu', 'file_manager', 'replacesiteconfirm')) || in_array($mode, array('replacesite'))) {
		if ($action == 'editcss') {
			// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
			// accesskey is for Mac:               CTRL + key for all browsers
			$stringforfirstkey = $langs->trans("KeyboardShortcut");
			if ($conf->browser->name == 'chrome') {
				$stringforfirstkey .= ' ALT +';
			} elseif ($conf->browser->name == 'firefox') {
				$stringforfirstkey .= ' ALT + SHIFT +';
			} else {
				$stringforfirstkey .= ' CTL +';
			}

			print '<input type="submit" accesskey="s" title="'.dol_escape_htmltag($stringforfirstkey.' s').'" id="savefileandstay" class="button buttonforacesave hideonsmartphone small" value="'.dol_escape_htmltag($langs->trans("SaveAndStay")).'" name="updateandstay">';
		}
		if (preg_match('/^create/', $action) && $action != 'file_manager' && $action != 'replacesite' && $action != 'replacesiteconfirm') {
			print '<input type="submit" id="savefile" class="button buttonforacesave button-save small" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		}
		if (preg_match('/^edit/', $action) && $action != 'file_manager' && $action != 'replacesite' && $action != 'replacesiteconfirm') {
			print '<input type="submit" id="savefile" class="button buttonforacesave button-save small" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		}
		if ($action != 'preview') {
			print '<input type="submit" class="button button-cancel small" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="cancel">';
		}
	}

	print '</span>';

	//
	// Toolbar for pages
	//

	if ($websitekey && $websitekey != '-1' && (!in_array($action, array('editcss', 'editmenu', 'importsite', 'file_manager', 'replacesite', 'replacesiteconfirm'))) && (!in_array($mode, array('replacesite'))) && !$file_manager) {
		print '</div>'; // Close current websitebar to open a new one

		print '<!-- Toolbar for websitepage -->';
		print '<div class="centpercent websitebar"'.($style ? ' style="'.$style.'"' : '').'>';

		print '<div class="websiteselection hideonsmartphoneimp minwidth75 tdoverflowmax100 inline-block">';
		print $langs->trans("PageContainer").': ';
		print '</div>';

		// Button Add new web page
		print '<span class="websiteselection paddingrightonly">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=createcontainer&token='.newToken().'&website='.urlencode($website->ref).'" class=""'.$disabled.' title="'.dol_escape_htmltag($langs->trans("AddPage")).'"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a>';
		print '</span>';


		$out = '';

		$s = $formwebsite->selectContainer($website, 'pageid', $pageid, 0, $action, 'minwidth100 maxwidth200onsmartphone');

		$out .= '<span class="websiteselection nopaddingrightimp">';
		$out .= $s;
		$out .= '</span>';

		$urltocreatenewpage = $_SERVER["PHP_SELF"].'?action=createcontainer&token='.newToken().'&website='.urlencode($website->ref);

		if (!empty($conf->use_javascript_ajax)) {
			$out .= '<script type="text/javascript">';
			$out .= 'jQuery(document).ready(function () {';
			$out .= '	jQuery("#pageid").change(function () {';
			$out .= '   	console.log("We select "+jQuery("#pageid option:selected").val());';
			$out .= '   	if (jQuery("#pageid option:selected").val() == \'-2\') {';
			$out .= '  			window.location.href = "'.$urltocreatenewpage.'";';
			$out .= '		} else {';
			$out .= '  			window.location.href = "'.$_SERVER["PHP_SELF"].'?website='.urlencode($website->ref).'&pageid="+jQuery("#pageid option:selected").val();';
			$out .= '       }';
			$out .= '   });';
			$out .= '});';
			$out .= '</script>';
		}

		print $out;

		// Button to switch status
		if (!empty($conf->use_javascript_ajax)) {
			print '<span class="websiteselection">';
			//print '<div class="inline-block marginrightonly">';
			if ($object->status == $object::STATUS_DRAFT) {	// website is off, we do not allow to change status of page
				$text_off = 'SetWebsiteOnlineBefore';
				if ($websitepage->status == $websitepage::STATUS_DRAFT) {	// page is off
					print '<span class="valignmiddle disabled opacitymedium">'.img_picto($langs->trans($text_off), 'switch_off').'</span>';
				} else {
					print '<span class="valignmiddle disabled opacitymedium">'.img_picto($langs->trans($text_off), 'switch_on').'</span>';
				}
			} else {
				print ajax_object_onoff($websitepage, 'status', 'status', 'Online', 'Offline', array(), 'valignmiddle inline-block'.(empty($websitepage->id) ? ' opacitymedium disabled' : ''), 'statuswebsitepage', 1, 'pageid='.$websitepage->id);
			}
			//print '</div>';
			print '</span>';
		}

		print '<span class="websiteselection">';

		print '<input type="image" class="valignmiddle buttonwebsite" src="'.img_picto('', 'refresh', '', 0, 1).'" name="refreshpage" value="'.$langs->trans("Load").'"'.(($action != 'editsource') ? '' : ' disabled="disabled"').'>';

		// Print nav arrows
		$pagepreviousid = 0;
		$pagenextid = 0;
		if ($pageid) {
			$sql = "SELECT MAX(rowid) as pagepreviousid FROM ".MAIN_DB_PREFIX."website_page WHERE rowid < ".((int) $pageid)." AND fk_website = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$pagepreviousid = $obj->pagepreviousid;
				}
			} else {
				dol_print_error($db);
			}
			$sql = "SELECT MIN(rowid) as pagenextid FROM ".MAIN_DB_PREFIX."website_page WHERE rowid > ".((int) $pageid)." AND fk_website = ".((int) $object->id);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$pagenextid = $obj->pagenextid;
				}
			} else {
				dol_print_error($db);
			}
		}

		if ($pagepreviousid) {
			print '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?website='.urlencode($object->ref).'&pageid='.((int) $pagepreviousid).'&action='.urlencode($action).'&token='.newToken().'">'.img_previous($langs->trans("PreviousContainer")).'</a>';
		} else {
			print '<span class="valignmiddle opacitymedium">'.img_previous($langs->trans("PreviousContainer")).'</span>';
		}
		if ($pagenextid) {
			print '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?website='.urlencode($object->ref).'&pageid='.((int) $pagenextid).'&action='.urlencode($action).'&token='.newToken().'">'.img_next($langs->trans("NextContainer")).'</a>';
		} else {
			print '<span class="valignmiddle opacitymedium">'.img_next($langs->trans("NextContainer")).'</span>';
		}

		print '</span>';

		if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite') {
			$disabled = '';
			if (!$user->hasRight('website', 'write')) {
				$disabled = ' disabled="disabled"';
			}

			// Confirmation delete site
			if ($action == 'deletesite') {
				// Create an array for form
				$formquestion = array(
					array('type' => 'checkbox', 'name' => 'delete_also_js', 'label' => $langs->trans("DeleteAlsoJs"), 'value' => 0),
					array('type' => 'checkbox', 'name' => 'delete_also_medias', 'label' => $langs->trans("DeleteAlsoMedias"), 'value' => 0),
					//array('type' => 'other','name' => 'newlang','label' => $langs->trans("Language"), 'value' => $formadmin->select_language(GETPOST('newlang', 'aZ09')?GETPOST('newlang', 'aZ09'):$langs->defaultlang, 'newlang', 0, null, '', 0, 0, 'minwidth200')),
					//array('type' => 'other','name' => 'newwebsite','label' => $langs->trans("WebSite"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0))
				);

				if ($atleastonepage) {
					$langs->load("errors");
					$formquestion[] = array('type' => 'onecolumn', 'value' => '<div class="warning">'.$langs->trans("WarningPagesWillBeDeleted").'</div>');
				}

				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteWebsite'), '', 'confirm_deletesite', $formquestion, 0, 1, 210 + ($atleastonepage ? 70 : 0), 580);

				print $formconfirm;
			}

			// Confirmation to clone
			if ($action == 'createfromclone') {
				// Create an array for form
				$formquestion = array(
					array('type' => 'text', 'name' => 'siteref', 'label' => $langs->trans("WebSite"), 'value' => 'copy_of_'.$object->ref)
				);

				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CloneSite'), '', 'confirm_createfromclone', $formquestion, 0, 1, 200);

				print $formconfirm;
			}

			if ($pageid > 0 && $atleastonepage) {		// pageid can be set without pages, if homepage of site is set and all pages were removed
				// Confirmation to clone
				if ($action == 'createpagefromclone') {
					// Create an array for form
					$preselectedlanguage = GETPOST('newlang', 'aZ09') ? GETPOST('newlang', 'aZ09') : ''; // Dy default, we do not force any language on pages
					$onlylang = array();
					if ($website->otherlang) {
						if (!empty($website->lang)) {
							$onlylang[$website->lang] = $website->lang.' ('.$langs->trans("Default").')';
						}
						foreach (explode(',', $website->otherlang) as $langkey) {
							if (empty(trim($langkey))) {
								continue;
							}
							$onlylang[$langkey] = $langkey;
						}
						$textifempty = $langs->trans("Default");
					} else {
						$onlylang['none'] = 'none';
						$textifempty = $langs->trans("Default");
					}
					$formquestion = array(
						array('type' => 'hidden', 'name' => 'sourcepageurl', 'value' => $objectpage->pageurl),
						array('type' => 'other', 'tdclass' => 'fieldrequired', 'name' => 'newwebsite', 'label' => $langs->trans("WebSite"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0)),
						array('type' => 'text', 'tdclass' => 'maxwidth200 fieldrequired', 'moreattr' => 'autofocus="autofocus"', 'name' => 'newtitle', 'label' => $langs->trans("WEBSITE_TITLE"), 'value' => $langs->trans("CopyOf").' '.$objectpage->title),
						array('type' => 'text', 'tdclass' => 'maxwidth200', 'name' => 'newpageurl', 'label' => $langs->trans("WEBSITE_PAGENAME"), 'value' => '')
						);
					if (count($onlylang) > 1) {
						$formquestion[] = array('type' => 'checkbox', 'tdclass' => 'maxwidth200', 'name' => 'is_a_translation', 'label' => $langs->trans("PageIsANewTranslation"), 'value' => 0, 'morecss' => 'margintoponly');
					}

					$value = $formadmin->select_language($preselectedlanguage, 'newlang', 0, null, $textifempty, 0, 0, 'minwidth200', 1, 0, 0, $onlylang, 1);
					$formquestion[] = array('type' => 'other', 'name' => 'newlang', 'label' => $form->textwithpicto($langs->trans("Language"), $langs->trans("DefineListOfAltLanguagesInWebsiteProperties")), 'value' => $value);

					$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$pageid, $langs->trans('ClonePage'), '', 'confirm_createpagefromclone', $formquestion, 0, 1, 300, 550);

					print $formconfirm;
				}

				print '<span class="websiteselection">';

				// Edit web page properties
				print '<a href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$pageid.'&action=editmeta&token='.newToken().'" class="button bordertransp" title="'.dol_escape_htmltag($langs->trans("EditPageMeta")).'"'.$disabled.'><span class="fa fa-cog paddingrightonly"></span><span class="hideonsmartphone">'.dol_escape_htmltag($langs->trans("EditPageMeta")).'</span></a>';

				// Edit HTML content
				print '<a href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$pageid.'&action=editsource&token='.newToken().'" class="button bordertransp"'.$disabled.'>'.dol_escape_htmltag($langs->trans($conf->dol_optimize_smallscreen ? "HTML" : "EditHTMLSource")).'</a>';

				// Edit CKEditor
				if (getDolGlobalInt('WEBSITE_ALLOW_CKEDITOR')) {
					print '<a href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$pageid.'&action=editcontent&token='.newToken().'" class="button bordertransp"'.$disabled.'>'.dol_escape_htmltag("CKEditor").'</a>';
				} else {
					print '<!-- Add option WEBSITE_ALLOW_CKEDITOR to allow ckeditor -->';
				}

				print '</span>';


				// Switch include dynamic content / edit inline
				print '<!-- button EditInLine and ShowSubcontainers -->'."\n";
				print '<div class="websiteselectionsection inline-block">';

				print '<div class="inline-block marginrightonly">';	// Button includes dynamic content
				print $langs->trans("ShowSubcontainers");
				if (!getDolGlobalString('WEBSITE_SUBCONTAINERSINLINE')) {
					print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=setshowsubcontainers&token='.newToken().'">'.img_picto($langs->trans("ShowSubContainersOnOff", $langs->transnoentitiesnoconv("Off")), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
				} else {
					print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=unsetshowsubcontainers&token='.newToken().'">'.img_picto($langs->trans("ShowSubContainersOnOff", $langs->transnoentitiesnoconv("On")), 'switch_on', '', false, 0, 0, '', 'nomarginleft').'</a>';
				}
				print '</div>';

				print '<div class="inline-block marginrightonly">';	// Button edit inline

				print '<span id="switchckeditorinline">'."\n";
				// Enable CKEditor inline with js on section and div with conteneditable=true
				print '<!-- Code to enabled edit inline ckeditor -->'."\n";
				print '<script type="text/javascript">
						$(document).ready(function() {
							var isEditingEnabled = '.(getDolGlobalString("WEBSITE_EDITINLINE") ? 'true' : 'false').';
							if (isEditingEnabled)
							{
								switchEditorOnline(true);
							}

							$( "#switchckeditorinline" ).click(function() {
								switchEditorOnline();
							});

							function switchEditorOnline(forceenable)
							{
								if (! isEditingEnabled || forceenable)
								{
									console.log("Enable inline edit for some html tags with contenteditable=true attribute");

									jQuery(\'section[contenteditable="true"],div[contenteditable="true"],header[contenteditable="true"],main[contenteditable="true"],footer[contenteditable="true"]\').each(function(idx){
										var idtouse = $(this).attr(\'id\');
										console.log("Enable inline edit for "+idtouse);
										if (idtouse !== undefined) {
											var inlineditor = CKEDITOR.inline(idtouse, {
												// Allow some non-standard markup that we used in the introduction.
												// + a[target];div{float,display} ?
												extraAllowedContent: \'span(*);cite(*);q(*);dl(*);dt(*);dd(*);ul(*);li(*);header(*);main(*);footer(*);button(*);h1(*);h2(*);h3(*);\',
												//extraPlugins: \'sourcedialog\',
												removePlugins: \'flash,stylescombo,exportpdf,scayt,wsc,pagebreak,iframe,smiley\',
												// Show toolbar on startup (optional).
												// startupFocus: true
											});

											// Custom bar tool
											// Note the Source tool does not work on inline
											inlineditor.config.toolbar = [
											    [\'Templates\',\'NewPage\'],
											    [\'Save\'],
											    [\'Maximize\',\'Preview\'],
											    [\'PasteText\'],
											    [\'Undo\',\'Redo\',\'-\',\'Find\',\'Replace\',\'-\',\'SelectAll\',\'RemoveFormat\'],
											    [\'CreateDiv\',\'ShowBlocks\'],
											    [\'Form\', \'Checkbox\', \'Radio\', \'TextField\', \'Textarea\', \'Select\', \'Button\', \'ImageButton\', \'HiddenField\'],
											    [\'Bold\',\'Italic\',\'Underline\',\'Strike\',\'Superscript\'],
											    [\'NumberedList\',\'BulletedList\',\'-\',\'Outdent\',\'Indent\',\'Blockquote\'],
											    [\'JustifyLeft\',\'JustifyCenter\',\'JustifyRight\',\'JustifyBlock\'],
											    [\'Link\',\'Unlink\'],
											    [\'Image\',\'Table\',\'HorizontalRule\'],
											    [\'Styles\',\'Format\',\'Font\',\'FontSize\'],
											    [\'TextColor\',\'BGColor\']
											];

											// Start editor
											//inlineditor.on(\'instanceReady\', function () {
											    // ...
											//});

											CKEDITOR.instances[idtouse].on(\'change\', function() {
												$(this.element.$).addClass(\'modified\');
											})
										} else {
											console.warn("A html section has the contenteditable=true attribute but has no id attribute");
										}
									})

									isEditingEnabled = true;

									// Trigger the function when clicking outside the elements with contenteditable=true attribute
									$(document).on(\'click\', function(e) {
										var target = $(e.target);
										// Check if the click is outside the elements with contenteditable=true attribute
										if (!target.closest(\'[contenteditable="true"]\').length) {
											// Repeat through the elements with contenteditable="true" attribute
											$(\'[contenteditable="true"]\').each(function() {
												var idToUse = $(this).attr(\'id\');
												var elementType = $(this).prop("tagName").toLowerCase(); // Get the tag name (div, section, footer...)
												var instance = CKEDITOR.instances[idToUse];
												// Check if the element has been modified
												if ($(this).hasClass(\'modified\')) {
													var content = instance.getData();
													content = "\\n" + content;

													// Retrieving the content and ID of the element
													var elementId = $(this).attr(\'id\');

													// Sending data via AJAX
													$.ajax({
														type: \'POST\',
														url: \'' . DOL_URL_ROOT . '/core/ajax/editinline.php\',
														data: {
															website_ref: \''.$website->ref.'\',
															page_id: \'' . $websitepage->id . '\',
															content: content,
															element_id: elementId,
															element_type: elementType,
															action: \'updatedElementContent\',
															token: \'' . newToken() . '\'
														},
														success: function(response) {
															console.log(response);
														}
													});

													$(this).removeClass(\'modified\');
												}
											});
										}
									});

								} else {
									console.log("Disable inline edit");
									for(name in CKEDITOR.instances) {
									    CKEDITOR.instances[name].destroy(true);
									}
									isEditingEnabled = false;
								}
							}
						});
						</script>';
				print $langs->trans("EditInLine");
				print '</span>';

				//$disableeditinline = $websitepage->grabbed_from;
				$disableeditinline = 0;
				if ($disableeditinline) {
					//print '<input type="submit" class="button bordertransp" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("OnlyEditionOfSourceForGrabbedContent")).'" value="'.dol_escape_htmltag($langs->trans("EditWithEditor")).'" name="editcontent">';
					print '<a class="nobordertransp opacitymedium nohoverborder marginleftonlyshort"'.$disabled.' href="#" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("OnlyEditionOfSourceForGrabbedContent")).'">'.img_picto($langs->trans("OnlyEditionOfSourceForGrabbedContent"), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
				} else {
					//print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditWithEditor")).'" name="editcontent">';
					if (!getDolGlobalString('WEBSITE_EDITINLINE')) {
						print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=seteditinline&token='.newToken().'">'.img_picto($langs->trans("EditInLineOnOff", $langs->transnoentitiesnoconv("Off")), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
					} else {
						print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=unseteditinline&token='.newToken().'">'.img_picto($langs->trans("EditInLineOnOff", $langs->transnoentitiesnoconv("On")), 'switch_on', '', false, 0, 0, '', 'nomarginleft').'</a>';
					}
				}

				print '</div>';

				print '</div>';

				// Set page as homepage
				print '<span class="websiteselection">';
				if ($object->fk_default_home > 0 && $pageid == $object->fk_default_home) {
					//$disabled=' disabled="disabled"';
					//print '<span class="button bordertransp disabled"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'"><span class="fas fa-home"></span></span>';
					//print '<input type="submit" class="button bordertransp" disabled="disabled" value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
					print '<a href="#" class="button bordertransp disabled" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'"><span class="fas fa-home valignmiddle btnTitle-icon"></span></a>';
				} else {
					//$disabled='';
					//print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=setashome&token='.newToken().'&website='.urlencode($website->ref).'&pageid='.((int) $pageid).'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'"><span class="fas fa-home valignmiddle btnTitle-icon"></span></a>';
				}
				print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("ClonePage")).'" name="createpagefromclone">';

				// Delete
				if ($websitepage->status != $websitepage::STATUS_DRAFT) {
					$disabled = ' disabled="disabled"';
					$title = $langs->trans("WebpageMustBeDisabled", $langs->transnoentitiesnoconv($websitepage->LibStatut(0, 0)));
					$url = '#';
				} else {
					$disabled = '';
					$title = '';
					$url = $_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&pageid='.((int) $websitepage->id).'&website='.urlencode($website->ref);	// action=delete for webpage, deletesite for website
				}
				print '<a href="'.$url.'" class="button buttonDelete bordertransp'.($disabled ? ' disabled' : '').'"'.$disabled.' title="'.dol_escape_htmltag($title).'">'.img_picto('', 'delete', 'class=""').'<span class="hideonsmartphone paddingleft">'.$langs->trans("Delete").'</span></a>';
				print '</span>';
			}
		}

		//print '</span>';	// end website selection

		print '<span class="websitetools">';

		if (($pageid > 0 && $atleastonepage) && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite')) {
			$realpage = $urlwithroot.'/public/website/index.php?website='.$websitekey.'&pageref='.$websitepage->pageurl;
			$pagealias = $websitepage->pageurl;

			$htmltext = $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage, $dataroot);
			$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), '{s1}');
			$htmltext = str_replace('{s1}', $dataroot.'<br>'.DOL_DATA_ROOT.'/medias<br>'.DOL_DOCUMENT_ROOT, $htmltext);
			//$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), '{s1}');
			//$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/medias', $htmltext);

			print '<div class="websiteinputurl inline-block paddingright">';
			print '<a class="websitebuttonsitepreview inline-block" id="previewpage" href="'.$realpage.'&nocache='.dol_now().'" class="button" target="tab'.$websitekey.'" alt="'.dol_escape_htmltag($htmltext).'">';
			print $form->textwithpicto('', $htmltext, 1, 'preview');
			print '</a>'; // View page in new Tab
			print '</div>';

			/*print '<div class="websiteinputurl inline-block" id="websiteinputpage">';
			print '<input type="text" id="previewpageurl" class="minwidth200imp" name="previewsite" value="'.$pagealias.'" disabled="disabled">';
			$htmltext = $langs->trans("PageNameAliasHelp", $langs->transnoentitiesnoconv("EditPageMeta"));
			print $form->textwithpicto('', $htmltext, 1, 'help', '', 0, 2, 'helppagealias');
			print '</div>';*/

			/*
			$urlext = $virtualurl.'/'.$pagealias.'.php';
			$urlint = $urlwithroot.'/public/website/index.php?website='.$websitekey;

			$htmltext = $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $dataroot, $virtualurl ? $urlext : '<span class="error">'.$langs->trans("VirtualHostUrlNotDefined").'</span>');

			print '<a class="websitebuttonsitepreview'.($virtualurl ? '' : ' websitebuttonsitepreviewdisabled cursornotallowed').'" id="previewpageext" href="'.$urlext.'" target="tab'.$websitekey.'ext" alt="'.dol_escape_htmltag($htmltext).'">';
			print $form->textwithpicto('', $htmltext, 1, 'preview_ext');
			print '</a>';
			*/
			//print '<input type="submit" class="button" name="previewpage" target="tab'.$websitekey.'"value="'.$langs->trans("ViewPageInNewTab").'">';

			// TODO Add js to save alias like we save virtual host name and use dynamic virtual host for url of id=previewpageext
		}
		if (!in_array($mode, array('replacesite')) && !in_array($action, array('editcss', 'editmenu', 'file_manager', 'replacesiteconfirm', 'createsite', 'createcontainer', 'createfromclone', 'createpagefromclone', 'deletesite'))) {
			if ($action == 'editsource' || $action == 'editmeta') {
				// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
				// accesskey is for Mac:               CTRL + key for all browsers
				$stringforfirstkey = $langs->trans("KeyboardShortcut");
				if ($conf->browser->name == 'chrome') {
					$stringforfirstkey .= ' ALT +';
				} elseif ($conf->browser->name == 'firefox') {
					$stringforfirstkey .= ' ALT + SHIFT +';
				} else {
					$stringforfirstkey .= ' CTL +';
				}

				print '<input type="submit" accesskey="s" title="'.dol_escape_htmltag($stringforfirstkey.' s').'" id="savefileandstay" class="button buttonforacesave hideonsmartphone small" value="'.dol_escape_htmltag($langs->trans("SaveAndStay")).'" name="updateandstay">';
			}
			if (preg_match('/^create/', $action)) {
				print '<input type="submit" id="savefile" class="button buttonforacesave button-save small" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
			}
			if (preg_match('/^edit/', $action)) {
				print '<input type="submit" id="savefile" class="button buttonforacesave button-save small" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
			}
			if ($action != 'preview') {
				print '<input type="submit" class="button button-cancel small" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="cancel">';
			}
		}

		print '</span>'; // end websitetools

		print '<span class="websitehelp">';
		if ($action == 'editsource' || $action == 'editcontent' || GETPOST('editsource', 'alpha') || GETPOST('editcontent', 'alpha')) {
			$url = 'https://wiki.dolibarr.org/index.php/Module_Website';

			$htmltext = '<small>';
			$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource", $url);
			$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource1", $url);
			$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource2", $url);
			$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource3", $url);
			$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSourceMore", $url);
			$htmltext .= '<br>';
			$htmltext .= '</small>';
			if ($conf->browser->layout == 'phone') {
				print $form->textwithpicto('', $htmltext, 1, 'help', 'inline-block', 1, 2, 'tooltipsubstitution');
			} else {
				//img_help(($tooltiptrigger != '' ? 2 : 1), $alt)
				print $form->textwithpicto($langs->trans("SyntaxHelp").' '.img_help(2, $langs->trans("SyntaxHelp")), $htmltext, 1, 'none', 'inline-block', 1, 2, 'tooltipsubstitution');
			}
		}
		print '</span>'; // end websitehelp


		if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone') {
			// Adding jquery code to change on the fly url of preview ext
			if (!empty($conf->use_javascript_ajax)) {
				print '<script type="text/javascript">
                    jQuery(document).ready(function() {
                		jQuery("#websiteinputurl").keyup(function() {
                            console.log("Website external url modified "+jQuery("#previewsiteurl").val());
                			if (jQuery("#previewsiteurl").val() != "" && jQuery("#previewsiteurl").val().startsWith("http"))
							{
								jQuery("a.websitebuttonsitepreviewdisabled img").css({ opacity: 1 });
							}
                			else jQuery("a.websitebuttonsitepreviewdisabled img").css({ opacity: 0.2 });
						';
				print '
                		});
                    	jQuery("#previewsiteext,#previewpageext").click(function() {

                            newurl=jQuery("#previewsiteurl").val();
							if (! newurl.startsWith("http"))
							{
								alert(\''.dol_escape_js($langs->trans("ErrorURLMustStartWithHttp")).'\');
								return false;
							}

                            newpage=jQuery("#previewsiteurl").val() + "/" + jQuery("#previewpageurl").val() + ".php";
                            console.log("Open url "+newurl);
                            /* Save url */
                            jQuery.ajax({
                                method: "POST",
                                url: "'.DOL_URL_ROOT.'/core/ajax/saveinplace.php",
                                data: {
                                    field: \'editval_virtualhost\',
                                    element: \'website\',
                                    table_element: \'website\',
                                    fk_element: '.((int) $object->id).',
                                    value: newurl,
                                },
                                context: document.body
                            });

                            jQuery("#previewsiteext").attr("href",newurl);
                            jQuery("#previewpageext").attr("href",newpage);
                        });
                    });
                    </script>';
			}
		}
	}

	print '</div>'; // end current websitebar
}


$head = array();


/*
 * Edit Site HTML header and CSS
 */

if ($action == 'editcss') {
	print '<div class="fiche">';

	print '<br>';

	if (!GETPOSTISSET('WEBSITE_CSS_INLINE')) {
		$csscontent = @file_get_contents($filecss);
		// Clean the php css file to remove php code and get only css part
		$csscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $csscontent);
	} else {
		$csscontent = GETPOST('WEBSITE_CSS_INLINE', 'none');
	}
	if (!trim($csscontent)) {
		$csscontent = '/* CSS content (all pages) */'."\nbody.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }\n.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}";
	}

	if (!GETPOSTISSET('WEBSITE_JS_INLINE')) {
		$jscontent = @file_get_contents($filejs);
		// Clean the php js file to remove php code and get only js part
		$jscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $jscontent);
	} else {
		$jscontent = GETPOST('WEBSITE_JS_INLINE', 'none');
	}
	if (!trim($jscontent)) {
		$jscontent = '/* JS content (all pages) */'."\n";
	}

	if (!GETPOSTISSET('WEBSITE_HTML_HEADER')) {
		$htmlheadercontent = @file_get_contents($filehtmlheader);
		// Clean the php htmlheader file to remove php code and get only html part
		$htmlheadercontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $htmlheadercontent);
	} else {
		$htmlheadercontent = GETPOST('WEBSITE_HTML_HEADER', 'none');
	}
	if (!trim($htmlheadercontent)) {
		$htmlheadercontent = "<html>\n";
		$htmlheadercontent .= $htmlheadercontentdefault;
		$htmlheadercontent .= "</html>";
	} else {
		$htmlheadercontent = preg_replace('/^\s*<html>/ims', '', $htmlheadercontent);
		$htmlheadercontent = preg_replace('/<\/html>\s*$/ims', '', $htmlheadercontent);
		$htmlheadercontent = '<html>'."\n".trim($htmlheadercontent)."\n".'</html>';
	}

	if (!GETPOSTISSET('WEBSITE_ROBOT')) {
		$robotcontent = @file_get_contents($filerobot);
		// Clean the php htmlheader file to remove php code and get only html part
		$robotcontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $robotcontent);
	} else {
		$robotcontent = GETPOST('WEBSITE_ROBOT', 'nohtml');
	}
	if (!trim($robotcontent)) {
		$robotcontent .= "# Robot file. Generated with ".DOL_APPLICATION_TITLE."\n";
		$robotcontent .= "User-agent: *\n";
		$robotcontent .= "Allow: /public/\n";
		$robotcontent .= "Disallow: /administrator/\n";
	}

	if (!GETPOSTISSET('WEBSITE_HTACCESS')) {
		$htaccesscontent = @file_get_contents($filehtaccess);
		// Clean the php htaccesscontent file to remove php code and get only html part
		$htaccesscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $htaccesscontent);
	} else {
		$htaccesscontent = GETPOST('WEBSITE_HTACCESS', 'nohtml');	// We must use 'nohtml' and not 'alphanohtml' because we must accept "
	}
	if (!trim($htaccesscontent)) {
		$htaccesscontent .= "# Order allow,deny\n";
		$htaccesscontent .= "# Deny from all\n";
	}

	if (!GETPOSTISSET('WEBSITE_MANIFEST_JSON')) {
		$manifestjsoncontent = @file_get_contents($filemanifestjson);
		// Clean the manifestjson file to remove php code and get only html part
		$manifestjsoncontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $manifestjsoncontent);
	} else {
		$manifestjsoncontent = GETPOST('WEBSITE_MANIFEST_JSON', 'restricthtml');
	}
	if (!trim($manifestjsoncontent)) {
		//$manifestjsoncontent.="";
	}

	if (!GETPOSTISSET('WEBSITE_README')) {
		$readmecontent = @file_get_contents($filereadme);
		// Clean the readme file to remove php code and get only html part
		$readmecontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $readmecontent);
	} else {
		$readmecontent = GETPOST('WEBSITE_README', 'none');
	}
	if (!trim($readmecontent)) {
		//$readmecontent.="";
	}

	if (!GETPOSTISSET('WEBSITE_LICENSE')) {
		$licensecontent = @file_get_contents($filelicense);
		// Clean the readme file to remove php code and get only html part
		$licensecontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP( \?>)?\n*/ims', '', $licensecontent);
	} else {
		$licensecontent = GETPOST('WEBSITE_LICENSE', 'none');
	}
	if (!trim($licensecontent)) {
		//$readmecontent.="";
	}

	print dol_get_fiche_head();

	print '<!-- Edit Website properties -->'."\n";
	print '<table class="border centpercent">';

	// Website
	print '<tr><td class="titlefieldcreate fieldrequired">';
	print $langs->trans('WebSite');
	print '</td><td>';
	print $websitekey;
	print '</td></tr>';

	// Status of web site
	if ($action != 'createcontainer') {
		if (empty($conf->use_javascript_ajax)) {
			print '<!-- Status of web site page -->'."\n";
			print '<tr><td class="fieldrequired">';
			print $langs->trans('Status');
			print '</td><td>';
			print $form->selectyesno('status', $object->status);
			print '</td></tr>';
		}
	}

	// Main language
	print '<tr><td class="tdtop fieldrequired">';
	$htmltext = '';
	print $form->textwithpicto($langs->trans('MainLanguage'), $htmltext, 1, 'help', '', 0, 2, 'WEBSITE_LANG');
	print '</td><td>';
	print img_picto('', 'language', 'class="picotfixedwidth"');
	print $formadmin->select_language((GETPOSTISSET('WEBSITE_LANG') ? GETPOST('WEBSITE_LANG', 'aZ09comma') : ($object->lang ? $object->lang : '0')), 'WEBSITE_LANG', 0, null, 1, 0, 0, 'minwidth300', 2, 0, 0, array(), 1);
	print '</td>';
	print '</tr>';

	// Other languages
	print '<tr><td class="tdtop">';
	$htmltext = $langs->trans("Example").': fr,de,sv,it,pt';
	print $form->textwithpicto($langs->trans('OtherLanguages'), $htmltext, 1, 'help', '', 0, 2);
	print '</td><td>';
	print img_picto('', 'language', 'class="picotfixedwidth"');
	print '<input type="text" class="flat" value="'.(GETPOSTISSET('WEBSITE_OTHERLANG') ? GETPOST('WEBSITE_OTHERLANG', 'alpha') : $object->otherlang).'" name="WEBSITE_OTHERLANG">';
	print '</td>';
	print '</tr>';

	// VirtualHost
	print '<tr><td class="tdtop">';

	$htmltext = $langs->trans("VirtualhostDesc");
	print $form->textwithpicto($langs->trans('Virtualhost'), $htmltext, 1, 'help', '', 0, 2, 'virtualhosttooltip');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" value="'.(GETPOSTISSET('virtualhost') ? GETPOST('virtualhost', 'alpha') : $virtualurl).'" name="virtualhost">';
	print '</td>';
	print '</tr>';

	// Favicon
	print '<tr><td>';
	print $form->textwithpicto($langs->trans('ImportFavicon'), $langs->trans('FaviconTooltip'));
	print '</td><td>';
	$maxfilesizearray = getMaxFileSizeArray();
	$maxmin = $maxfilesizearray['maxmin'];
	if ($maxmin > 0) {
		print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
	}
	print '<input type="file" class="flat minwidth300" name="addedfile" id="addedfile"/>';

	$uploadfolder = $conf->website->dir_output.'/'.$websitekey;
	if (dol_is_file($uploadfolder.'/favicon.png')) {
		print '<div class="inline-block valignmiddle marginrightonly">';
		print '<img style="max-height: 80px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=website&file='.$websitekey.'/favicon.png">';
		print '</div>';
	}
	print '</tr></td>';

	// CSS file
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans("CSSContentTooltipHelp");
	print $form->textwithpicto($langs->trans('WEBSITE_CSS_INLINE'), $htmlhelp, 1, 'help', '', 0, 2, 'csstooltip');
	print '</td><td>';

	$poscursor = array('x' => GETPOST('WEBSITE_CSS_INLINE_x'), 'y' => GETPOST('WEBSITE_CSS_INLINE_y'));
	$doleditor = new DolEditor('WEBSITE_CSS_INLINE', $csscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, 'CSS', 'css');

	print '</td></tr>';

	// JS file
	print '<tr><td class="tdtop">';
	$textwithhelp = $langs->trans('WEBSITE_JS_INLINE');
	$htmlhelp2 = $langs->trans("LinkAndScriptsHereAreNotLoadedInEditor").'<br>';
	print $form->textwithpicto($textwithhelp, $htmlhelp2, 1, 'warning', '', 0, 2, 'htmljstooltip2');

	print '</td><td>';

	$poscursor = array('x' => GETPOST('WEBSITE_JS_INLINE_x'), 'y' => GETPOST('WEBSITE_JS_INLINE_y'));
	$doleditor = new DolEditor('WEBSITE_JS_INLINE', $jscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, 'JS', 'javascript');

	print '</td></tr>';

	// Common HTML header
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_HTML_HEADER');
	$htmlhelp = $langs->trans("Example").' :<br>';
	$htmlhelp .= dol_nl2br(dol_htmlentities($htmlheadercontentdefault));	// do not use dol_htmlentitiesbr here, $htmlheadercontentdefault is HTML with content like <link> and <script> that we want to be html encode as they must be show as doc content not executable instruction.
	$textwithhelp = $form->textwithpicto('', $htmlhelp, 1, 'help', '', 0, 2, 'htmlheadertooltip');
	$htmlhelp2 = $langs->trans("LinkAndScriptsHereAreNotLoadedInEditor").'<br>';
	print $form->textwithpicto($textwithhelp, $htmlhelp2, 1, 'warning', '', 0, 2, 'htmlheadertooltip2');
	print '</td><td>';

	$poscursor = array('x' => GETPOST('WEBSITE_HTML_HEADER_x'), 'y' => GETPOST('WEBSITE_HTML_HEADER_y'));
	$doleditor = new DolEditor('WEBSITE_HTML_HEADER', $htmlheadercontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, 'HTML Header', 'html');

	print '</td></tr>';

	// Robot file
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_ROBOT');
	print '</td><td>';

	$poscursor = array('x' => GETPOST('WEBSITE_ROBOT_x'), 'y' => GETPOST('WEBSITE_ROBOT_y'));
	$doleditor = new DolEditor('WEBSITE_ROBOT', $robotcontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, 'Robot file', 'text');

	print '</td></tr>';

	// .htaccess
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_HTACCESS');
	print '</td><td>';

	$poscursor = array('x' => GETPOST('WEBSITE_HTACCESS_x'), 'y' => GETPOST('WEBSITE_HTACCESS_y'));
	$doleditor = new DolEditor('WEBSITE_HTACCESS', $htaccesscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, $langs->trans("File").' .htaccess', 'text');

	print '</td></tr>';

	// Manifest.json
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans("Example").' :<br>';
	$htmlhelp .= '<small>'.dol_htmlentitiesbr($manifestjsoncontentdefault).'</small>';
	print $form->textwithpicto($langs->trans('WEBSITE_MANIFEST_JSON'), $htmlhelp, 1, 'help', '', 0, 2, 'manifestjsontooltip');
	print '</td><td>';
	print $langs->trans("UseManifest").': '.$form->selectyesno('use_manifest', $website->use_manifest, 1).'<br>';

	$poscursor = array('x' => GETPOST('WEBSITE_MANIFEST_JSON_x'), 'y' => GETPOST('WEBSITE_MANIFEST_JSON_y'));
	$doleditor = new DolEditor('WEBSITE_MANIFEST_JSON', $manifestjsoncontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, $langs->trans("File").' manifest.json', 'text');
	print '</td></tr>';

	// README.md
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans("EnterHereReadmeInformation");
	print $form->textwithpicto($langs->trans("File").' README.md', $htmlhelp, 1, 'help', '', 0, 2, 'readmetooltip');
	print '</td><td>';

	$poscursor = array('x' => GETPOST('WEBSITE_README_x'), 'y' => GETPOST('WEBSITE_README_y'));
	$doleditor = new DolEditor('WEBSITE_README', $readmecontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, $langs->trans("File").' README.md', 'text');

	print '</td></tr>';

	// LICENSE
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans("EnterHereLicenseInformation");
	print $form->textwithpicto($langs->trans("File").' LICENSE', $htmlhelp, 1, 'help', '', 0, 2, 'licensetooltip');
	print '</td><td>';

	$poscursor = array('x' => GETPOST('WEBSITE_LICENSE_x'), 'y' => GETPOST('WEBSITE_LICENSE_y'));
	$doleditor = new DolEditor('WEBSITE_LICENSE', $licensecontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, $langs->trans("File").' LICENSE', 'text');

	print '</td></tr>';

	// RSS
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans('RSSFeedDesc');
	print $form->textwithpicto($langs->trans('RSSFeed'), $htmlhelp, 1, 'help', '', 0, 2, '');
	print '</td><td>';
	print '/wrapper.php?rss=1[&l=XX][&limit=123]';
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print '</div>';

	print '<br>';
}


if ($action == 'createsite') {
	print '<div class="fiche">';

	print '<br>';

	/*$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/website/index.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AddSite");
	   $head[$h][2] = 'card';
	$h++;

	print dol_get_fiche_head($head, 'card', '', -1, 'globe');
	*/
	if ($action == 'createcontainer') {
		print load_fiche_titre($langs->trans("AddSite"));
	}

	print '<!-- Add site -->'."\n";
	print '<div class="tabBar tabBarWithBottom">';

	print '<table class="border centpercent">';

	$siteref = $sitedesc = $sitelang = $siteotherlang = '';
	if (GETPOST('WEBSITE_REF')) {
		$siteref = GETPOST('WEBSITE_REF', 'aZ09');
	}
	if (GETPOST('WEBSITE_DESCRIPTION')) {
		$sitedesc = GETPOST('WEBSITE_DESCRIPTION', 'alpha');
	}
	if (GETPOST('WEBSITE_LANG')) {
		$sitelang = GETPOST('WEBSITE_LANG', 'aZ09');
	}
	if (GETPOST('WEBSITE_OTHERLANG')) {
		$siteotherlang = GETPOST('WEBSITE_OTHERLANG', 'aZ09comma');
	}

	print '<tr><td class="titlefieldcreate fieldrequired">';
	print $form->textwithpicto($langs->trans('WebsiteName'), $langs->trans("Example").': MyPortal, www.mywebsite.com, ...');
	print '</td><td>';
	print '<input type="text" class="flat maxwidth300" name="WEBSITE_REF" value="'.dol_escape_htmltag($siteref).'" autofocus>';
	print '</td></tr>';

	print '<tr><td class="fieldrequired">';
	print $langs->trans('MainLanguage');
	print '</td><td>';
	$shortlangcode = preg_replace('/[_-].*$/', '', trim($langs->defaultlang));
	print img_picto('', 'language', 'class="pictofixedwidth"');
	print $formadmin->select_language((GETPOSTISSET('WEBSITE_LANG') ? GETPOST('WEBSITE_LANG', 'aZ09comma') : $shortlangcode), 'WEBSITE_LANG', 0, null, 1, 0, 0, 'minwidth300', 2, 0, 0, array(), 1);
	print '</td></tr>';

	print '<tr><td>';
	$htmltext = $langs->trans("Example").': fr,de,sv,it,pt';
	print $form->textwithpicto($langs->trans('OtherLanguages'), $htmltext, 1, 'help', '', 0, 2);
	print '</td><td>';
	print img_picto('', 'language', 'class="pictofixedwidth"');
	print '<input type="text" class="flat minwidth300" name="WEBSITE_OTHERLANG" value="'.dol_escape_htmltag($siteotherlang).'">';
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('Description');
	print '</td><td>';
	print '<input type="text" class="flat minwidth500" name="WEBSITE_DESCRIPTION" value="'.dol_escape_htmltag($sitedesc).'">';
	print '</td></tr>';

	print '<tr><td>';

	$htmltext = $langs->trans("VirtualhostDesc");
	/*$htmltext = str_replace('{s1}', DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/<i>websiteref</i>', $htmltext);
	$htmltext .= '<br>';
	$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
	$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), '{s1}');
	$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias', $htmltext);*/


	print $form->textwithpicto($langs->trans('Virtualhost'), $htmltext, 1, 'help', '', 0, 2, '');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="virtualhost" value="'.dol_escape_htmltag(GETPOST('virtualhost', 'alpha')).'">';
	print '</td></tr>';

	print '</table>';
	print '</div>';

	if ($action == 'createsite') {
		print '<div class="center">';

		print '<input type="submit" class="button small" name="addcontainer" value="'.$langs->trans("Create").'">';
		print '<input class="button button-cancel small" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

		print '</div>';
	}


	//print '</div>';

	//print dol_get_fiche_end();

	print '</div>';

	print '<br>';
}

if ($action == 'importsite') {
	print '<!-- action=importsite -->';
	print '<div class="fiche">';

	print '<br>';

	print load_fiche_titre($langs->trans("ImportSite"));

	print dol_get_fiche_head(array(), '0', '', -1);

	print '<span class="opacitymedium">'.$langs->trans("ZipOfWebsitePackageToImport").'</span><br><br>';


	$dolibarrdataroot = preg_replace('/([\\/]+)$/i', '', DOL_DATA_ROOT);
	$allowimportsite = true;
	if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) {
		$allowimportsite = false;
	}

	if ($allowimportsite) {
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
		}
		print '<input class="flat minwidth400" type="file" name="userfile[]" accept=".zip">';
		print '<input type="submit" class="button small" name="buttonsubmitimportfile" value="'.dol_escape_htmltag($langs->trans("Upload")).'">';
		print '<input type="submit" class="button button-cancel small" name="preview" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '<br><br><br>';
	} else {
		if (getDolGlobalString('MAIN_MESSAGE_INSTALL_MODULES_DISABLED_CONTACT_US')) {
			// Show clean corporate message
			$message = $langs->trans('InstallModuleFromWebHasBeenDisabledContactUs');
		} else {
			// Show technical generic message
			$message = $langs->trans("InstallModuleFromWebHasBeenDisabledByFile", $dolibarrdataroot.'/installmodules.lock');
		}
		print info_admin($message).'<br><br>';
	}


	print '<span class="opacitymedium">'.$langs->trans("ZipOfWebsitePackageToLoad").'</span><br><br>';

	showWebsiteTemplates($website);

	print dol_get_fiche_end();

	print '</div>';

	print '<br>';
}

if ($action == 'editmeta' || $action == 'createcontainer') {	// Edit properties of a web site OR properties of a web page
	print '<div class="fiche">';

	print '<br>';

	/*$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/website/index.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AddPage");
	   $head[$h][2] = 'card';
	$h++;

	print dol_get_fiche_head($head, 'card', '', -1, 'globe');
	*/
	if ($action == 'createcontainer') {
		print load_fiche_titre($langs->trans("AddPage"));
	}

	print '<!-- Edit or create page/container -->'."\n";
	//print '<div class="fichecenter">';

	$hiddenfromfetchingafterload = ' hideobject';
	$hiddenmanuallyafterload = ' hideobject';
	if (GETPOST('radiocreatefrom') == 'checkboxcreatefromfetching') {
		$hiddenfromfetchingafterload = '';
	}
	if (GETPOST('radiocreatefrom') == 'checkboxcreatemanually') {
		$hiddenmanuallyafterload = '';
	}

	if ($action == 'editmeta' || empty($conf->use_javascript_ajax)) {	// No autohide/show in such case
		$hiddenfromfetchingafterload = '';
		$hiddenmanuallyafterload = '';
	}

	if ($action == 'createcontainer') {
		print '<br>';

		if (!empty($conf->use_javascript_ajax)) {
			print '<input type="radio" name="radiocreatefrom" id="checkboxcreatemanually" value="checkboxcreatemanually"'.(GETPOST('radiocreatefrom') == 'checkboxcreatemanually' ? ' checked' : '').'> ';
		}
		print '<label for="checkboxcreatemanually"><span class="opacitymediumxx">'.$langs->trans("OrEnterPageInfoManually").'</span></label><br>';
		print '<hr class="tablecheckboxcreatemanually'.$hiddenmanuallyafterload.'">';
	}

	print '<table class="border tableforfield nobackground centpercent tablecheckboxcreatemanually'.$hiddenmanuallyafterload.'">';

	if ($action != 'createcontainer') {
		print '<tr><td class="titlefield fieldrequired">';
		print $langs->trans('IDOfPage').' - '.$langs->trans('InternalURLOfPage');
		print '</td><td>';
		print $pageid;
		//print '</td></tr>';

		//print '<tr><td class="titlefield fieldrequired">';
		//print $langs->trans('InternalURLOfPage');
		//print '</td><td>';
		print ' &nbsp; - &nbsp; ';
		print '/public/website/index.php?website='.urlencode($websitekey).'&pageid='.urlencode($pageid);
		//if ($objectpage->grabbed_from) print ' - <span class="opacitymedium">'.$langs->trans('InitiallyGrabbedFrom').' '.$objectpage->grabbed_from.'</span>';
		print '</td></tr>';

		$type_container = $objectpage->type_container;
		$pageurl = $objectpage->pageurl;
		$pagealiasalt = $objectpage->aliasalt;
		$pagetitle = $objectpage->title;
		$pagedescription = $objectpage->description;
		$pageimage = $objectpage->image;
		$pagekeywords = $objectpage->keywords;
		$pagelang = $objectpage->lang;
		$pageallowedinframes = $objectpage->allowed_in_frames;
		$pagehtmlheader = $objectpage->htmlheader;
		$pagedatecreation = $objectpage->date_creation;
		$pagedatemodification = $objectpage->date_modification;
		$pageauthorid = $objectpage->fk_user_creat;
		$pageusermodifid = $objectpage->fk_user_modif;
		$pageauthoralias = $objectpage->author_alias;
		$pagestatus = $objectpage->status;
	} else {	// $action = 'createcontainer'
		$type_container = 'page';
		$pageurl = '';
		$pagealiasalt = '';
		$pagetitle = '';
		$pagedescription = '';
		$pageimage = '';
		$pagekeywords = '';
		$pagelang = '';
		$pageallowedinframes = 0;
		$pagehtmlheader = '';
		$pagedatecreation = dol_now();
		$pagedatemodification = '';
		$pageauthorid = $user->id;
		$pageusermodifid = 0;
		$pageauthoralias = '';
		$pagestatus = 1;
	}
	if (GETPOST('WEBSITE_TITLE', 'alpha')) {
		$pagetitle = str_replace(array('<', '>'), '', GETPOST('WEBSITE_TITLE', 'alphanohtml'));
	}
	if (GETPOST('WEBSITE_PAGENAME', 'alpha')) {
		$pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
	}
	if (GETPOST('WEBSITE_ALIASALT', 'alpha')) {
		$pagealiasalt = str_replace(array('<', '>'), '', GETPOST('WEBSITE_ALIASALT', 'alphanohtml'));
	}
	if (GETPOST('WEBSITE_DESCRIPTION', 'alpha')) {
		$pagedescription = str_replace(array('<', '>'), '', GETPOST('WEBSITE_DESCRIPTION', 'alphanohtml'));
	}
	if (GETPOST('WEBSITE_IMAGE', 'alpha')) {
		$pageimage = GETPOST('WEBSITE_IMAGE', 'alpha');
	}
	if (GETPOST('WEBSITE_KEYWORDS', 'alpha')) {
		$pagekeywords = str_replace(array('<', '>'), '', GETPOST('WEBSITE_KEYWORDS', 'alphanohtml'));
	}
	if (GETPOST('WEBSITE_LANG', 'aZ09')) {
		$pagelang = GETPOST('WEBSITE_LANG', 'aZ09');
	}
	if (GETPOST('WEBSITE_ALLOWED_IN_FRAMES', 'aZ09')) {
		$pageallowedinframes = GETPOST('WEBSITE_ALLOWED_IN_FRAMES', 'aZ09');
	}
	if (GETPOST('htmlheader', 'none')) {
		$pagehtmlheader = GETPOST('htmlheader', 'none');
	}

	if ($action != 'createcontainer') {
		if (empty($conf->use_javascript_ajax)) {
			print '<!-- Status of web site page -->'."\n";
			print '<tr><td class="fieldrequired">';
			print $langs->trans('Status');
			print '</td><td>';
			print $form->selectyesno('status', $objectpage->status);
			print '</td></tr>';
		}
	}

	// Type of container
	print '<tr><td class="titlefield fieldrequired">';
	print $langs->trans('WEBSITE_TYPE_CONTAINER');
	print '</td><td>';
	print img_picto('', 'object_technic', 'class="paddingrightonly"').' ';
	print $formwebsite->selectTypeOfContainer('WEBSITE_TYPE_CONTAINER', (GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha') ? GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha') : $type_container), 0, '', 1, 'minwidth300');
	print '</td></tr>';

	print '<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#selectWEBSITE_TYPE_CONTAINER").change(function() {
					console.log("We change type of page : "+jQuery("#selectWEBSITE_TYPE_CONTAINER").val());
					if (jQuery("#selectWEBSITE_TYPE_CONTAINER").val() == \'blogpost\') {
						jQuery(".trpublicauthor").show();
					} else {
						jQuery(".trpublicauthor").hide();
					}
					if (jQuery("#selectWEBSITE_TYPE_CONTAINER").val() == \'service\' || jQuery("#selectWEBSITE_TYPE_CONTAINER").val() == \'library\') {
						$(".spanprefix").html("_" + $("#selectWEBSITE_TYPE_CONTAINER").val() + "_");
						jQuery(".spanprefix").show();
					} else {
						jQuery(".spanprefix").hide();
					}
				});

				// Force at init execution a first time of the handler change
				jQuery("#selectWEBSITE_TYPE_CONTAINER").trigger(\'change\');
			});
			</script>
		';

	// Title
	print '<tr><td class="fieldrequired">';
	print $langs->trans('WEBSITE_TITLE');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_TITLE" id="WEBSITE_TITLE" value="'.dol_escape_htmltag($pagetitle).'" autofocus>';
	print '</td></tr>';

	// Alias page
	print '<tr><td class="titlefieldcreate fieldrequired">';
	print $langs->trans('WEBSITE_PAGENAME');
	print '</td><td>';
	print '<span class="opacitymedium spanprefix hidden"></span> ';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_PAGENAME" id="WEBSITE_PAGENAME" value="'.dol_escape_htmltag((string) preg_replace('/^_[a-z]+_/', '', (string) $pageurl)).'">';
	print '</td></tr>';

	print '<script type="text/javascript">
			$(document).ready(function() {
				console.log("Manage prefix for service or library");
				if ($("#selectWEBSITE_TYPE_CONTAINER").val() == "service" || $("#selectWEBSITE_TYPE_CONTAINER").val() == "library") {
					$(".spanprefix").html("_" + $("#selectWEBSITE_TYPE_CONTAINER").val() + "_");
					$(".spanprefix").show();
				}
				$(".websiteformtoolbar").on("submit", function(event) {
					if ($("#selectWEBSITE_TYPE_CONTAINER").val() == "service" || $("#selectWEBSITE_TYPE_CONTAINER").val() == "library") {
						var prefix = "_" + $("#selectWEBSITE_TYPE_CONTAINER").val() + "_";
						var userInput = $("#WEBSITE_PAGENAME").val();
						var $inputField = $("#WEBSITE_PAGENAME");
						if (userInput.indexOf(prefix) !== 0) {
							$inputField.val(prefix + userInput);
						}
					}
				});
			});
		</script>
	';

	print '<tr><td class="titlefieldcreate">';
	$htmlhelp = $langs->trans("WEBSITE_ALIASALTDesc");
	print $form->textwithpicto($langs->trans('WEBSITE_ALIASALT'), $htmlhelp, 1, 'help', '', 0, 2, 'aliastooltip');
	print '</td><td>';
	print '<input type="text" class="flat minwidth500" name="WEBSITE_ALIASALT" value="'.dol_escape_htmltag($pagealiasalt).'">';
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('WEBSITE_DESCRIPTION');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_DESCRIPTION" value="'.dol_escape_htmltag($pagedescription).'">';
	print '</td></tr>';

	// Deprecated. Image for RSS or Thumbs must be taken from the content.
	if (getDolGlobalInt('WEBSITE_MANAGE_IMAGE_FOR_PAGES')) {
		print '<tr class="trimageforpage hidden"><td>';
		$htmlhelp = $langs->trans("WEBSITE_IMAGEDesc");
		print $form->textwithpicto($langs->trans('WEBSITE_IMAGE'), $htmlhelp, 1, 'help', '', 0, 2, 'imagetooltip');
		print '</td><td>';
		print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_IMAGE" value="'.dol_escape_htmltag($pageimage).'">';
		print '</td></tr>';

		print '<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#selectWEBSITE_TYPE_CONTAINER").change(function() {
					console.log("We change type of page : "+jQuery("#selectWEBSITE_TYPE_CONTAINER").val());
					if (jQuery("#selectWEBSITE_TYPE_CONTAINER").val() == \'blogpost\') {
						jQuery(".trimageforpage").show();
					} else {
						jQuery(".trimageforpage").hide();
					}
				});
			});
			</script>
		';
	}

	// Keywords
	print '<tr><td>';
	$htmlhelp = $langs->trans("WEBSITE_KEYWORDSDesc");
	print $form->textwithpicto($langs->trans('WEBSITE_KEYWORDS'), $htmlhelp, 1, 'help', '', 0, 2, 'keywordtooltip');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_KEYWORDS" value="'.dol_escape_htmltag($pagekeywords).'">';
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('Language');
	print '</td><td>';
	$onlykeys = array();
	if ($object->lang) {
		$onlykeys[$object->lang] = $object->lang;
	} else {
		$onlykeys[$langs->defaultlang] = $langs->defaultlang;
	}
	if ($object->otherlang) {
		$tmparray = explode(',', $object->otherlang);
		foreach ($tmparray as $key) {
			$tmpkey = trim($key);
			if (strlen($key) == 2) {
				$tmpkey = strtolower($key);
			}
			$onlykeys[$tmpkey] = $tmpkey;
		}
	}
	if (empty($object->lang) && empty($object->otherlang)) {
		$onlykeys = null; // We keep full list of languages
	}
	print img_picto('', 'language', 'class="pictofixedwidth"').$formadmin->select_language($pagelang ? $pagelang : '', 'WEBSITE_LANG', 0, null, '1', 0, 0, 'minwidth200', 0, 0, 0, $onlykeys, 1);
	$htmltext = $langs->trans("AvailableLanguagesAreDefinedIntoWebsiteProperties");
	print $form->textwithpicto('', $htmltext);
	print '</td></tr>';

	// Translation of
	$translationof = 0;
	$translatedby = 0;
	print '<!-- Translation of --><tr><td>';
	print $langs->trans('TranslationLinks');
	print '</td><td>';
	if ($action != 'createcontainer') {
		// Has translation pages
		$sql = "SELECT rowid, lang from ".MAIN_DB_PREFIX."website_page where fk_page = ".((int) $objectpage->id);
		$resql = $db->query($sql);
		if ($resql) {
			$num_rows = $db->num_rows($resql);
			if ($num_rows > 0) {
				print '<span class="opacitymedium">'.$langs->trans('ThisPageHasTranslationPages').':</span>';
				$i = 0;
				$tmppage = new WebsitePage($db);
				$tmpstring = '';
				while ($obj = $db->fetch_object($resql)) {
					$result = $tmppage->fetch($obj->rowid);
					if ($result > 0) {
						if ($i > 0) {
							$tmpstring .= '<br>';
						}
						$tmpstring .= $tmppage->getNomUrl(1).' '.picto_from_langcode($tmppage->lang).' '.$tmppage->lang;
						// Button unlink
						$tmpstring .= ' <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?website='.urlencode($object->ref).'&pageid='.((int) $objectpage->id).'&action=deletelang&token='.newToken().'&deletelangforid='.((int) $tmppage->id).'">'.img_picto($langs->trans("Remove"), 'unlink').'</a>';
						$translatedby++;
						$i++;
					}
				}
				if ($i > 1) {
					print '<br>';
				} else {
					print ' ';
				}
				print $tmpstring;
			}
		} else {
			dol_print_error($db);
		}
	}
	if ((empty($translatedby) || ($objectpage->lang != $object->lang)) && ($action == 'editmeta' || $action == 'createcontainer' || $objectpage->fk_page > 0)) {
		$sourcepage = new WebsitePage($db);
		$result = 1;
		if ($objectpage->fk_page > 0) {
			$result = $sourcepage->fetch($objectpage->fk_page);
			if ($result == 0) {
				// not found, we can reset value to clean database
				// TODO
			}
		}
		if ($result >= 0) {
			if ($translatedby) {
				print '<br>';
			}
			$translationof = $objectpage->fk_page;
			print '<span class="opacitymedium">'.$langs->trans('ThisPageIsTranslationOf').'</span> ';
			print $sourcepage->getNomUrl(2).' '.$formwebsite->selectContainer($website, 'pageidfortranslation', ($translationof ? $translationof : -1), 1, $action, 'minwidth300', array($objectpage->id));
			if ($translationof > 0 && $sourcepage->lang) {
				print picto_from_langcode($sourcepage->lang).' '.$sourcepage->lang;
				// Button unlink
				print ' <a class="paddingleft" href="'.$_SERVER["PHP_SELF"].'?website='.urlencode($object->ref).'&pageid='.((int) $objectpage->id).'&action=deletelang&token='.newToken().'&deletelangforid='.((int) $objectpage->id).'">'.img_picto($langs->trans("Remove"), 'unlink').'</a>';
			}
		}
	}
	print '</td></tr>';

	// Categories
	if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
		$langs->load('categories');

		if (!GETPOSTISSET('categories')) {
			$c = new Categorie($db);
			$cats = $c->containing($objectpage->id, Categorie::TYPE_WEBSITE_PAGE);
			$arrayselected = array();
			if (is_array($cats)) {
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
			}

			//$cate_arbo = $form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, '', '', 0, 0, 3);
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, '', 'parent', 0, 0, 3);
		}

		print '<tr><td class="toptd">'.$form->editfieldkey('Categories', 'categories', '', $objectpage, 0).'</td><td>';
		print img_picto('', 'category', 'class="pictofixedwidth"');
		print $form->multiselectarray('categories', $cate_arbo, (GETPOSTISSET('categories') ? GETPOST('categories', 'array') : $arrayselected), null, null, 'minwidth200 widthcentpercentminusxx');

		print dolButtonToOpenUrlInDialogPopup('categories', $langs->transnoentitiesnoconv("Categories"), img_picto('', 'add'), '/categories/index.php?leftmenu=website&nosearch=1&type='.urlencode(Categorie::TYPE_WEBSITE_PAGE).'&website='.urlencode($website->ref), $disabled);

		print "</td></tr>";
	}

	if (getDolGlobalString('WEBSITE_PAGE_SHOW_INTERNAL_LINKS_TO_OBJECT')) {	// TODO Replace this with link into element_element ?
		print '<tr><td class="titlefieldcreate">';
		print 'ObjectClass';
		print '</td><td>';
		print '<input type="text" class="flat minwidth300" name="WEBSITE_OBJECTCLASS" placeholder="ClassName::/path/class/ObjectClass.class.php" >';
		print '</td></tr>';

		print '<tr><td class="titlefieldcreate">';
		print 'ObjectID';
		print '</td><td>';
		print '<input type="text" class="flat minwidth300" name="WEBSITE_OBJECTID" >';
		print '</td></tr>';
	}

	$fuser = new User($db);

	// Date last modification
	if ($action != 'createcontainer') {
		print '<tr><td>';
		print $langs->trans('DateLastModification');
		print '</td><td>';
		print dol_print_date($pagedatemodification, 'dayhour', 'tzuser');
		print '</td></tr>';

		print '<tr><td>';
		print $langs->trans('UserModification');
		print '</td><td>';
		if ($pageusermodifid > 0) {
			$fuser->fetch($pageusermodifid);
			print $fuser->getNomUrl(-1);
		} else {
			print '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
		}
		print '</td></tr>';
	}

	// Content - Example/templates of page
	$url = 'https://wiki.dolibarr.org/index.php/Module_Website';
	$htmltext = '<small>';
	$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource", $url);
	$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource1", $url);
	$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource2", $url);
	$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource3", $url);
	$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSourceMore", $url);
	$htmltext .= '<br>';
	$htmltext .= '</small>';

	$formmail = new FormMail($db);
	$formmail->withaiprompt = 'html';
	$formmail->withlayout = 1;
	$showlinktolayout = $formmail->withlayout;
	$showlinktoai = ($formmail->withaiprompt && isModEnabled('ai')) ? 'textgenerationwebpage' : '';
	if (($action == 'createcontainer' && $showlinktolayout) || ($action == 'createcontainer' && $showlinktoai)) {
		print '<tr><td class="titlefield tdtop">';
		if ($conf->browser->layout == 'phone') {
			print $form->textwithpicto('', $htmltext, 1, 'help', 'inline-block', 1, 2, 'tooltipsubstitution');
		} else {
			//img_help(($tooltiptrigger != '' ? 2 : 1), $alt)
			print $form->textwithpicto($langs->trans("PreviewPageContent").' '.img_help(2, $langs->trans("PreviewPageContent")), $htmltext, 1, 'none', 'inline-block', 1, 2, 'tooltipsubstitution');
		}
		print '</td><td class="tdtop">';

		$out = '';

		$showlinktolayoutlabel = $langs->trans("FillPageWithALayout");
		$showlinktoailabel = $langs->trans("FillPageWithAIContent");
		$htmlname = 'content';
		// Fill $out
		include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';

		print $out;
		print '</td></tr>';
	}

	if ($action == 'createcontainer') {
		print '<tr id="pageContent"><td class="tdtop">';
		if (!$showlinktolayout || !$showlinktoai) {
			if ($conf->browser->layout == 'phone') {
				print $form->textwithpicto('', $htmltext, 1, 'help', 'inline-block', 1, 2, 'tooltipsubstitution');
			} else {
				//img_help(($tooltiptrigger != '' ? 2 : 1), $alt)
				print $form->textwithpicto($langs->trans("PreviewPageContent").' '.img_help(2, $langs->trans("PreviewPageContent")), $htmltext, 1, 'none', 'inline-block', 1, 2, 'tooltipsubstitution');
			}
		}
		print '</td><td>';
		//$doleditor = new DolEditor('content', GETPOST('content', 'restricthtmlallowunvalid'), '', 200, 'dolibarr_mailings', 'In', true, true, true, 40, '90%');
		$doleditor = new DolEditor('content', GETPOST('content', 'none'), '', 200, 'dolibarr_mailings', 'In', true, true, true, 40, '90%');
		$doleditor->Create();
		//print '<div class="websitesample" id="contentpreview" name="contentpreview" style="height: 200px; border: 1px solid #bbb; overflow: scroll">';
		print '</div>';
		//print '<textarea id="content" name="content" class="hideobject">'.GETPOST('content', 'none').'</textarea>';
		print '</td></tr>';
	}

	// Date creation
	print '<tr><td>';
	print $langs->trans('DateCreation');
	print '</td><td>';
	print $form->selectDate($pagedatecreation, 'datecreation', 1, 1, 0, '', 1, 1);
	//print dol_print_date($pagedatecreation, 'dayhour');
	print '</td></tr>';

	// Author
	print '<tr><td>';
	print $langs->trans('Author');
	print '</td><td>';
	if ($pageauthorid > 0) {
		$fuser->fetch($pageauthorid);
		print $fuser->getNomUrl(-1);
	} else {
		print '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
	}
	print '</td></tr>';

	// Author - public alias
	print '<tr class="trpublicauthor hidden"><td>';
	print $langs->trans('PublicAuthorAlias');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_AUTHORALIAS" value="'.dol_escape_htmltag($pageauthoralias).'" placeholder="Anonymous">';
	print '</td></tr>';

	print '<tr><td class="tdhtmlheader tdtop">';
	$htmlhelp = $langs->trans("EditTheWebSiteForACommonHeader").'<br><br>';
	$htmlhelp .= $langs->trans("Example").' :<br>';
	$htmlhelp .= dol_nl2br(dol_htmlentities($htmlheadercontentdefault));	// do not use dol_htmlentitiesbr here, $htmlheadercontentdefault is HTML with content like <link> and <script> that we want to be html encode as they must be show as doc content not executable instruction.
	print $form->textwithpicto($langs->transnoentitiesnoconv('HtmlHeaderPage'), $htmlhelp, 1, 'help', '', 0, 2, 'htmlheadertooltip');
	print '</td><td>';
	$poscursor = array('x' => GETPOST('htmlheader_x'), 'y' => GETPOST('htmlheader_y'));
	$doleditor = new DolEditor('htmlheader', $pagehtmlheader, '', '120', 'ace', 'In', true, false, 'ace', ROWS_3, '100%', '', $poscursor);
	print $doleditor->Create(1, '', true, 'HTML Header', 'html');
	print '</td></tr>';

	// Allowed in frames
	print '<tr><td>';
	print $langs->trans('AllowedInFrames');
	//$htmlhelp = $langs->trans("AllowedInFramesDesc");
	//print $form->textwithpicto($langs->trans('AllowedInFrames'), $htmlhelp, 1, 'help', '', 0, 2, 'allowedinframestooltip');
	print '</td><td>';
	print '<input type="checkbox" class="flat" name="WEBSITE_ALLOWED_IN_FRAMES" value="1"'.($pageallowedinframes ? 'checked="checked"' : '').'>';
	print '</td></tr>';

	print '</table>';

	if ($action == 'createcontainer') {
		print '<div class="center tablecheckboxcreatemanually'.$hiddenmanuallyafterload.'">';

		print '<input type="submit" class="button small" name="addcontainer" value="'.$langs->trans("Create").'">';
		print '<input class="button button-cancel small" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

		print '</div>';


		print '<br>';

		if (!empty($conf->use_javascript_ajax)) {
			print '<input type="radio" name="radiocreatefrom" id="checkboxcreatefromfetching" value="checkboxcreatefromfetching"'.(GETPOST('radiocreatefrom') == 'checkboxcreatefromfetching' ? ' checked' : '').'> ';
		}
		print '<label for="checkboxcreatefromfetching"><span class="opacitymediumxx">'.$langs->trans("CreateByFetchingExternalPage").'</span></label><br>';
		print '<hr class="tablecheckboxcreatefromfetching'.$hiddenfromfetchingafterload.'">';
		print '<table class="tableforfield centpercent tablecheckboxcreatefromfetching'.$hiddenfromfetchingafterload.'">';
		print '<tr><td class="titlefield">';
		print $langs->trans("URL");
		print '</td><td>';
		print info_admin($langs->trans("OnlyEditionOfSourceForGrabbedContentFuture"), 0, 0, 'warning');
		print '<input class="flat minwidth500" type="text" name="externalurl" value="'.dol_escape_htmltag(GETPOST('externalurl', 'alpha')).'" placeholder="https://externalsite/pagetofetch"> ';
		print '<br><input class="flat paddingtop" type="checkbox" name="grabimages" value="1" checked="checked"> '.$langs->trans("GrabImagesInto");
		print ' ';
		print $langs->trans("ImagesShouldBeSavedInto").' ';
		$arraygrabimagesinto = array('root' => $langs->trans("WebsiteRootOfImages"), 'subpage' => $langs->trans("SubdirOfPage"));
		print $form->selectarray('grabimagesinto', $arraygrabimagesinto, GETPOSTISSET('grabimagesinto') ? GETPOST('grabimagesinto') : 'root', 0, 0, 0, '', 0, 0, 0, '', '', 1);
		print '<br>';

		print '<input class="button small" style="margin-top: 5px" type="submit" name="fetchexternalurl" value="'.dol_escape_htmltag($langs->trans("FetchAndCreate")).'">';
		print '<input class="button button-cancel small" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

		print '</td></tr>';
		print '</table>';
	}

	if ($action == 'createcontainer') {
		print '<script type="text/javascript">
			jQuery(document).ready(function() {
				var disableautofillofalias = 0;
				var selectedm = \'\';
				var selectedf = \'\';

				jQuery("#WEBSITE_TITLE").keyup(function() {
					if (disableautofillofalias == 0) {
						var valnospecial = jQuery("#WEBSITE_TITLE").val();
						valnospecial = valnospecial.replace(/[éèê]/g, \'e\').replace(/[à]/g, \'a\').replace(/[ù]/g, \'u\').replace(/[î]/g, \'i\');
						valnospecial = valnospecial.replace(/[ç]/g, \'c\').replace(/[ö]/g, \'o\');
						valnospecial = valnospecial.replace(/[^\w]/gi, \'-\').toLowerCase();
						valnospecial = valnospecial.replace(/\-+/g, \'-\').replace(/\-$/, \'\');
						console.log("disableautofillofalias=0 so we replace WEBSITE_TITLE with "+valnospecial);
						jQuery("#WEBSITE_PAGENAME").val(valnospecial);
					}
				});
				jQuery("#WEBSITE_PAGENAME").keyup(function() {
					if (jQuery("#WEBSITE_PAGENAME").val() == \'\') {
						disableautofillofalias = 0;
					} else {
						disableautofillofalias = 1;
					}
				});
				jQuery("#WEBSITE_PAGENAME").blur(function() {
					if (jQuery("#WEBSITE_PAGENAME").val() == \'\') {
						disableautofillofalias = 0;
						jQuery("#WEBSITE_TITLE").trigger(\'keyup\');
					}
				});

				jQuery("#checkboxcreatefromfetching,#checkboxcreatemanually").click(function() {
					console.log("we select a method to create a new container "+jQuery("#checkboxcreatefromfetching:checked").val())
					jQuery(".tablecheckboxcreatefromfetching").hide();
					jQuery(".tablecheckboxcreatemanually").hide();
					if (typeof(jQuery("#checkboxcreatefromfetching:checked").val()) != \'undefined\') {
						console.log("show create from spider form");
						if (selectedf != \'createfromfetching\') {
							jQuery(".tablecheckboxcreatefromfetching").show();
							selectedf = \'createfromfetching\';
							selectedm = \'\';
						} else {
							jQuery(".tablecheckboxcreatefromfetching").hide();
							selectedf = \'\';
						}
					}
					if (typeof(jQuery("#checkboxcreatemanually:checked").val()) != \'undefined\') {
						console.log("show create from scratch or template form");
						if (selectedm != \'createmanually\') {
							jQuery(".tablecheckboxcreatemanually").show();
							selectedm = \'createmanually\';
							selectedf = \'\';
						} else {
							jQuery(".tablecheckboxcreatemanually").hide();
							selectedm = \'\';
						}
					}
				});
			});
			</script>';
	}
	//print '</div>';

	//print dol_get_fiche_end();

	print '</div>';

	print '<br>';
}


// Print formconfirm
if ($action == 'preview') {
	print $formconfirm;
}

if ($action == 'editfile' || $action == 'file_manager' || $action == 'convertimgwebp' || $action == 'confirmconvertimgwebp') {
	print '<!-- Edit Media -->'."\n";
	print '<div class="fiche"><br>';
	//print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';


	$module = 'medias';
	$formalreadyopen = 2;	// So the form to submit a new file will not be open another time inside the core/tpl/filemanager.tpl.php
	if (empty($url)) {
		$url = DOL_URL_ROOT.'/website/index.php'; // Must be an url without param
	}
	include DOL_DOCUMENT_ROOT.'/core/tpl/filemanager.tpl.php';

	print '</div>';
}

if ($action == 'editmenu') {
	print '<!-- Edit Menu -->'."\n";
	print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';
}

if ($action == 'editsource') {
	// Editing with source editor

	$contentforedit = '';
	//$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
	//$contentforedit.=$csscontent;
	//$contentforedit.='</style>'."\n";
	$contentforedit .= $objectpage->content;
	//var_dump($_SESSION["dol_screenheight"]);
	$maxheightwin = 480;
	if (isset($_SESSION["dol_screenheight"])) {
		if ($_SESSION["dol_screenheight"] > 680) {
			$maxheightwin = $_SESSION["dol_screenheight"] - 400;
		}
		if ($_SESSION["dol_screenheight"] > 800) {
			$maxheightwin = $_SESSION["dol_screenheight"] - 490;
		}
	}

	$poscursor = array('x' => GETPOST('PAGE_CONTENT_x'), 'y' => GETPOST('PAGE_CONTENT_y'));
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('PAGE_CONTENT', $contentforedit, '', $maxheightwin, 'Full', '', true, true, 'ace', ROWS_5, '40%', 0, $poscursor);
	$doleditor->Create(0, '', false, 'HTML Source', 'php');
}

if ($action == 'editcontent') {
	// Editing with default ckeditor

	$contentforedit = '';
	//$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
	//$contentforedit.=$csscontent;
	//$contentforedit.='</style>'."\n";
	$contentforedit .= $objectpage->content;

	$nbrep = array();
	// If contentforedit has a string <img src="xxx", we replace the xxx with /viewimage.php?modulepart=medias&file=xxx except if xxx starts
	// with http, /viewimage.php or DOL_URL_ROOT./viewimage.phps
	$contentforedit = preg_replace('/(<img.*\ssrc=")(?!http|\/viewimage\.php|'.preg_quote(DOL_URL_ROOT, '/').'\/viewimage\.php)/', '\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $contentforedit, -1, $nbrep);

	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$poscursor = array('x' => GETPOST('PAGE_CONTENT_x'), 'y' => GETPOST('PAGE_CONTENT_y'));
	$doleditor = new DolEditor('PAGE_CONTENT', $contentforedit, '', 500, 'Full', '', true, true, true, ROWS_5, '90%', 0, $poscursor);
	$doleditor->Create(0, '', false);
}

print "</div>\n";
print "</form>\n";


if ($mode == 'replacesite' || $massaction == 'replace') {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="replacesiteconfirm">';
	print '<input type="hidden" name="mode" value="replacesite">';
	print '<input type="hidden" name="website" value="'.$website->ref.'">';


	print '<!-- Search page and replace string -->'."\n";
	print '<div class="fiche"><br>';

	print load_fiche_titre($langs->trans("ReplaceWebsiteContent"), '', 'search');

	print '<div class="fichecenter"><div class="fichehalfleft">';

	print '<div class="tagtable">';

	print '<div class="tagtr">';
	print '<div class="tagtd paddingrightonly opacitymedium">';
	print $langs->trans("SearchReplaceInto");
	print '</div>';
	print '<div class="tagtd">';
	print '<input type="checkbox" class="marginleftonly" id="checkboxoptioncontent" name="optioncontent" value="content"'.((!GETPOSTISSET('buttonreplacesitesearch') || GETPOST('optioncontent', 'aZ09')) ? ' checked' : '').'> <label for="checkboxoptioncontent" class="tdoverflowmax150onsmartphone inline-block valignmiddle">'.$langs->trans("Content").'</label><br>';
	print '<input type="checkbox" class="marginleftonly" id="checkboxoptionmeta" name="optionmeta" value="meta"'.(GETPOST('optionmeta', 'aZ09') ? ' checked' : '').'> <label for="checkboxoptionmeta" class="tdoverflowmax150onsmartphone inline-block valignmiddle">'.$langs->trans("Title").' | '.$langs->trans("Description").' | '.$langs->trans("Keywords").'</label><br>';
	print '<input type="checkbox" class="marginleftonly" id="checkboxoptionsitefiles" name="optionsitefiles" value="sitefiles"'.(GETPOST('optionsitefiles', 'aZ09') ? ' checked' : '').'> <label for="checkboxoptionsitefiles" class="tdoverflowmax150onsmartphone inline-block valignmiddle">'.$langs->trans("GlobalCSSorJS").'</label><br>';
	print '</div>';
	print '</div>';

	print '<div class="tagtr">';
	print '<div class="tagtd paddingrightonly opacitymedium" style="padding-right: 10px !important">';
	print $langs->trans("SearchString");
	print '</div>';
	print '<div class="tagtd">';
	print '<input type="text" name="searchstring" value="'.dol_escape_htmltag($searchkey, 0, 0, '', 1).'" autofocus>';
	print '</div>';
	print '</div>';

	print '</div>';

	print '</div><div class="fichehalfleft">';

	print '<div class="tagtable">';

	print '<div class="tagtr">';
	print '<div class="tagtd paddingrightonly opacitymedium tdoverflowmax100onsmartphone" style="padding-right: 10px !important">';
	print $langs->trans("WEBSITE_TYPE_CONTAINER");
	print '</div>';
	print '<div class="tagtd">';
	print img_picto('', 'object_technic', 'class="paddingrightonly"').' ';
	print $formwebsite->selectTypeOfContainer('optioncontainertype', (GETPOST('optioncontainertype', 'alpha') ? GETPOST('optioncontainertype', 'alpha') : ''), 1, '', 1, 'minwidth125 maxwidth400 widthcentpercentminusx');
	print '</div>';
	print '</div>';

	print '<div class="tagtr">';
	print '<div class="tagtd paddingrightonly opacitymedium tdoverflowmax100onsmartphone" style="padding-right: 10px !important">';
	print $langs->trans("Language");
	print '</div>';
	print '<div class="tagtd">';
	print img_picto('', 'language', 'class="paddingrightonly"').' '.$formadmin->select_language(GETPOSTISSET('optionlanguage') ? GETPOST('optionlanguage') : '', 'optionlanguage', 0, null, '1', 0, 0, 'minwidth125 maxwidth400 widthcentpercentminusx', 2, 0, 0, null, 1);
	print '</div>';
	print '</div>';

	// Categories
	if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
		print '<div class="tagtr">';
		print '<div class="tagtd paddingrightonly marginrightonly opacitymedium tdoverflowmax100onsmartphone" style="padding-right: 10px !important">';
		print $langs->trans("Category");
		print '</div>';
		print '<div class="tagtd">';
		print img_picto('', 'category', 'class="paddingrightonly"').' '.$form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, GETPOSTISSET('optioncategory') ? GETPOST('optioncategory') : '', 'optioncategory', 0, 0, 0, 0, 'minwidth125 maxwidth400 widthcentpercentminusx');
		include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
		print ajax_combobox('optioncategory');
		print '</div>';
		print '</div>';
	}

	print '</div>';

	print '<input type="submit" class="button margintoponly" name="buttonreplacesitesearch" value="'.dol_escape_htmltag($langs->trans("Search")).'">';

	print '</div></div>';

	if ($mode == 'replacesite') {
		print '<!-- List of search result -->'."\n";
		print '<div class="rowsearchresult clearboth">';

		print '<br>';
		print '<br>';

		if ($listofpages['code'] == 'OK') {
			$arrayofselected = is_array($toselect) ? $toselect : array();
			$param = '';
			$nbtotalofrecords = count($listofpages['list']);
			$num = $limit;
			$permissiontodelete = $user->hasRight('website', 'delete');

			// List of mass actions available
			$arrayofmassactions = array();
			if ($user->hasRight('website', 'writephp') && $searchkey) {
				$arrayofmassactions['replace'] = img_picto('', 'replacement', 'class="pictofixedwidth"').$langs->trans("Replace");
			}
			if ($user->hasRight('website', 'write')) {
				$arrayofmassactions['setcategory'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("ClassifyInCategory");
			}
			if ($user->hasRight('website', 'write')) {
				$arrayofmassactions['delcategory'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("RemoveCategory");
			}
			if ($permissiontodelete) {
				$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
			}
			if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
				$arrayofmassactions = array();
			}

			$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
			$massactionbutton .= '<div class="massactionother massactionreplace hidden">';
			$massactionbutton .= $langs->trans("ReplaceString");
			$massactionbutton .= ' <input type="text" name="replacestring" value="'.dol_escape_htmltag(GETPOST('replacestring', 'none')).'">';
			$massactionbutton .= '</div>';
			$massactionbutton .= '<div class="massactionother massactionsetcategory massactiondelcategory hidden">';
			$massactionbutton .= img_picto('', 'category', 'class="pictofixedwidth"');
			$massactionbutton .= $form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, GETPOSTISSET('setcategory') ? GETPOST('setcategory') : '', 'setcategory', 64, 0, 0, 0, 'minwidth300 alignstart');
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$massactionbutton .= ajax_combobox('setcategory');
			$massactionbutton .= '</div>';

			$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;

			//$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
			$selectedfields = '';
			$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

			print_barre_liste($langs->trans("Results"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, '', '', $limit, 1, 1, 1);

			$topicmail = "WebsitePageRef";
			$modelmail = "websitepage_send";
			$objecttmp = new WebsitePage($db);
			$trackid = 'wsp'.$object->id;
			include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

			$param = 'mode=replacesite&website='.urlencode($website->ref);
			$param .= '&searchstring='.urlencode($searchkey);
			if (GETPOST('optioncontent')) {
				$param .= '&optioncontent=content';
			}
			if (GETPOST('optionmeta')) {
				$param .= '&optionmeta=meta';
			}
			if (GETPOST('optionsitefiles')) {
				$param .= '&optionsitefiles=optionsitefiles';
			}
			if (GETPOST('optioncontainertype')) {
				$param .= '&optioncontainertype='.GETPOST('optioncontainertype', 'aZ09');
			}
			if (GETPOST('optionlanguage')) {
				$param .= '&optionlanguage='.GETPOST('optionlanguage', 'aZ09');
			}
			if (GETPOST('optioncategory')) {
				$param .= '&optioncategory='.GETPOST('optioncategory', 'aZ09');
			}

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
			}
			print getTitleFieldOfList("Type", 0, $_SERVER['PHP_SELF'], 'type_container', '', $param, '', $sortfield, $sortorder, '')."\n";
			print getTitleFieldOfList("Page", 0, $_SERVER['PHP_SELF'], 'pageurl', '', $param, '', $sortfield, $sortorder, '')."\n";
			print getTitleFieldOfList("Language", 0, $_SERVER['PHP_SELF'], 'lang', '', $param, '', $sortfield, $sortorder, 'center ')."\n";
			print getTitleFieldOfList("Categories", 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ')."\n";
			print getTitleFieldOfList("", 0, $_SERVER['PHP_SELF']);
			print getTitleFieldOfList("UserCreation", 0, $_SERVER['PHP_SELF'], 'fk_user_creat', '', $param, '', $sortfield, $sortorder, '')."\n";
			print getTitleFieldOfList("DateCreation", 0, $_SERVER['PHP_SELF'], 'date_creation', '', $param, '', $sortfield, $sortorder, 'center ')."\n";		// Date creation
			print getTitleFieldOfList("DateLastModification", 0, $_SERVER['PHP_SELF'], 'tms', '', $param, '', $sortfield, $sortorder, 'center ')."\n";		// Date last modif
			print getTitleFieldOfList("", 0, $_SERVER['PHP_SELF']);
			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
			}
			print '</tr>';

			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$c = new Categorie($db);

			$totalnbwords = 0;

			foreach ($listofpages['list'] as $answerrecord) {
				if (is_object($answerrecord) && get_class($answerrecord) == 'WebsitePage') {
					$param = '?mode=replacesite';
					$param .= '&websiteid='.$website->id;
					$param .= '&optioncontent='.GETPOST('optioncontent', 'aZ09');
					$param .= '&optionmeta='.GETPOST('optionmeta', 'aZ09');
					$param .= '&optionsitefiles='.GETPOST('optionsitefiles', 'aZ09');
					$param .= '&optioncontainertype='.GETPOST('optioncontainertype', 'aZ09');
					$param .= '&optionlanguage='.GETPOST('optionlanguage', 'aZ09');
					$param .= '&optioncategory='.GETPOST('optioncategory', 'aZ09');
					$param .= '&searchstring='.urlencode($searchkey);

					print '<tr>';

					// Action column
					if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print '<td class="nowrap center">';

						print '<!-- Status of page -->'."\n";
						if ($massactionbutton || $massaction) {
							$selected = 0;
							if (in_array($answerrecord->id, $arrayofselected)) {
								$selected = 1;
							}
							print '<input id="'.$answerrecord->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$answerrecord->id.'"'.($selected ? ' checked="checked"' : '').'>';
						}
						print '</td>';
					}

					// Type of container
					print '<td class="nowraponall">';
					//print $langs->trans("Container").'<br>';
					if (!empty($conf->cache['type_of_container'][$answerrecord->type_container])) {
						print $langs->trans($conf->cache['type_of_container'][$answerrecord->type_container]);
					} else {
						print $langs->trans($answerrecord->type_container);
					}
					print '</td>';

					// Container url and label
					$titleofpage = ($answerrecord->title ? $answerrecord->title : $langs->trans("NoTitle"));
					print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($titleofpage).'">';
					print $answerrecord->getNomUrl(1);
					print ' <span class="opacitymedium">('.dol_escape_htmltag($titleofpage).')</span>';
					//print '</td>';
					//print '<td class="tdoverflow100">';
					print '<br>';
					print '<span class="opacitymedium">'.dol_escape_htmltag($answerrecord->description ? $answerrecord->description : $langs->trans("NoDescription")).'</span>';
					print '</td>';

					// Language
					print '<td class="center">';
					print picto_from_langcode($answerrecord->lang, $answerrecord->lang);
					print '</td>';

					// Categories - Tags
					print '<td class="center">';
					if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
						// Get current categories
						$existing = $c->containing($answerrecord->id, Categorie::TYPE_WEBSITE_PAGE, 'object');
						if (is_array($existing)) {
							foreach ($existing as $tmpcategory) {
								//var_dump($tmpcategory);
								print img_object($langs->trans("Category").' : '.$tmpcategory->label, 'category', 'style="padding-left: 2px; padding-right: 2px; color: #'.($tmpcategory->color != '' ? $tmpcategory->color : '888').'"');
							}
						}
					}
					//var_dump($existing);
					print '</td>';

					// Number of words
					print '<td class="center nowraponall">';
					$textwithouthtml = dol_string_nohtmltag(dolStripPhpCode($answerrecord->content));
					$characterMap = 'áàéèëíóúüñùç0123456789';
					$nbofwords = str_word_count($textwithouthtml, 0, $characterMap);
					if ($nbofwords) {
						print $nbofwords.' '.$langs->trans("words");
						$totalnbwords += $nbofwords;
					}
					print '</td>';

					// Author
					print '<td class="tdoverflowmax125">';
					if (!empty($answerrecord->fk_user_creat)) {
						if (empty($conf->cache['user'][$answerrecord->fk_user_creat])) {
							$tmpuser = new User($db);
							$tmpuser->fetch($answerrecord->fk_user_creat);
							$conf->cache['user'][$answerrecord->fk_user_creat] = $tmpuser;
						} else {
							$tmpuser = $conf->cache['user'][$answerrecord->fk_user_creat];
						}
						print $tmpuser->getNomUrl(-1, '', 0, 0, 0, 0, 'login');
					}
					print '</td>';

					// Date creation
					print '<td class="center nowraponall">';
					print dol_print_date($answerrecord->date_creation, 'dayhour');
					print '</td>';

					// Date last modification
					print '<td class="center nowraponall">';
					print dol_print_date($answerrecord->date_modification, 'dayhour');
					print '</td>';

					// Edit properties, HTML sources, status
					print '<td class="tdwebsitesearchresult right nowraponall">';
					$disabled = '';
					$urltoedithtmlsource = $_SERVER["PHP_SELF"].'?action=editmeta&token='.newToken().'&websiteid='.$website->id.'&pageid='.$answerrecord->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].$param);
					if (!$user->hasRight('website', 'write')) {
						$disabled = ' disabled';
						$urltoedithtmlsource = '';
					}
					print '<a class="editfielda marginleftonly marginrightonly '.$disabled.'" href="'.$urltoedithtmlsource.'" title="'.$langs->trans("EditPageMeta").'">'.img_picto($langs->trans("EditPageMeta"), 'pencil-ruler').'</a>';

					$disabled = '';
					$urltoedithtmlsource = $_SERVER["PHP_SELF"].'?action=editsource&token='.newToken().'&websiteid='.$website->id.'&pageid='.$answerrecord->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].$param);
					if (!$user->hasRight('website', 'write')) {
						$disabled = ' disabled';
						$urltoedithtmlsource = '';
					}
					print '<a class="editfielda  marginleftonly marginrightonly '.$disabled.'" href="'.$urltoedithtmlsource.'" title="'.$langs->trans("EditHTMLSource").'">'.img_picto($langs->trans("EditHTMLSource"), 'edit').'</a>';

					print '<span class="marginleftonly marginrightonly"></span>';
					print ajax_object_onoff($answerrecord, 'status', 'status', 'Enabled', 'Disabled', array(), 'valignmiddle inline-block');

					print '</td>';

					// Action column
					if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print '<td class="nowrap center">';

						print '<!-- Status of page -->'."\n";
						if ($massactionbutton || $massaction) {
							$selected = 0;
							if (in_array($answerrecord->id, $arrayofselected)) {
								$selected = 1;
							}
							print '<input id="'.$answerrecord->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$answerrecord->id.'"'.($selected ? ' checked="checked"' : '').'>';
						}
						print '</td>';
					}

					print '</tr>';
				} else {
					$param = '?mode=replacesite';
					$param .= '&websiteid='.$website->id;
					$param .= '&optioncontent='.GETPOST('optioncontent', 'aZ09');
					$param .= '&optionmeta='.GETPOST('optionmeta', 'aZ09');
					$param .= '&optionsitefiles='.GETPOST('optionsitefiles', 'aZ09');
					$param .= '&optioncontainertype='.GETPOST('optioncontainertype', 'aZ09');
					$param .= '&optionlanguage='.GETPOST('optionlanguage', 'aZ09');
					$param .= '&optioncategory='.GETPOST('optioncategory', 'aZ09');
					$param .= '&searchstring='.urlencode($searchkey);

					print '<tr>';

					// Action column
					if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print '<td class="nowrap center">';
						print '</td>';
					}

					// Type of container
					print '<td>';
					$translateofrecordtype = array(
						'website_csscontent' => 'WEBSITE_CSS_INLINE',
						'website_jscontent' => 'WEBSITE_JS_INLINE',
						'website_robotcontent' => 'WEBSITE_ROBOT',
						'website_htmlheadercontent' => 'WEBSITE_HTML_HEADER',
						'website_htaccess' => 'WEBSITE_HTACCESS',
						'website_readme' => 'WEBSITE_README',
						'website_manifestjson' => 'WEBSITE_MANIFEST_JSON'
					);
					print '<span class="opacitymedium">';
					if (!empty($translateofrecordtype[$answerrecord['type']])) {
						print $langs->trans($translateofrecordtype[$answerrecord['type']]);
					} else {
						print $answerrecord['type'];
					}
					print '</span>';
					print '</td>';

					// Container url and label
					print '<td>';
					$backtopageurl = $_SERVER["PHP_SELF"].$param;
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=editcss&token='.newToken().'&website='.urlencode($website->ref).'&backtopage='.urlencode($backtopageurl).'">'.$langs->trans("EditCss").'</a>';
					print '</td>';

					// Language
					print '<td>';
					print '</td>';

					// Categories - Tags
					print '<td>';
					print '</td>';

					// Nb of words
					print '<td>';
					print '</td>';

					print '<td>';
					print '</td>';

					print '<td>';
					print '</td>';

					// Date last modification
					print '<td class="center nowraponall">';
					//print dol_print_date(filemtime());
					print '</td>';

					// Edit properties, HTML sources, status
					print '<td>';
					print '</td>';

					// Action column
					if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
						print '<td class="nowrap center">';
						print '</td>';
					}

					print '</tr>';
				}
			}

			if (count($listofpages['list']) >= 2) {
				// Total
				print '<tr class="lite_titre">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					print '</td>';
				}

				// Type of container
				print '<td>';
				print $langs->trans("Total");
				print '</td>';

				// Container url and label
				print '<td>';
				print '</td>';

				// Language
				print '<td>';
				print '</td>';

				// Categories - Tags
				print '<td>';
				print '</td>';

				// Nb of words
				print '<td class="center nowraponall">';
				print $totalnbwords.' '.$langs->trans("words");
				print '</td>';

				print '<td>';
				print '</td>';

				print '<td>';
				print '</td>';

				// Date last modification
				print '<td>';
				print '</td>';

				// Edit properties, HTML sources, status
				print '<td>';
				print '</td>';

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					print '</td>';
				}

				print '</tr>';
			}

			print '</table>';
			print '</div>';
			print '<br>';
		} else {
			print '<div class="warning">'.$listofpages['message'].'</div>';
		}

		print '</div>';
	}

	print '</form>';
}

if ((empty($action) || $action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone') && !in_array($mode, array('replacesite'))) {
	if ($pageid > 0 && $atleastonepage) {
		// $filejs
		// $filecss
		// $filephp

		// Output page under the Dolibarr top menu
		$objectpage->fetch($pageid);

		$jscontent = @file_get_contents($filejs);

		$out = '<!-- Page content '.$filetpl.' : Div with (Htmlheader/Style of page from database + CSS Of website from file + Page content from database or by include if WEBSITE_SUBCONTAINERSINLINE is on) -->'."\n";

		// Include a html so we can benefit of the header of page.
		// Note: We can't use iframe as it can be used to include another external html file
		// Note: We can't use frame as it is deprecated.
		/*if ($includepageintoaframeoradiv == 'iframe')
		{
			$out .= "<iframe><body></html>";
		}*/
		$out .= "\n<html><head>\n";
		$out .= "<!-- htmlheader/style of page from database -->\n";
		$out .= dolWebsiteReplacementOfLinks($object, $objectpage->htmlheader, 1, 'htmlheader');

		$out .= "<!-- htmlheader/style of website from files -->\n";
		// TODO Keep only the <link> or the <script> tags
		/*
		$htmlheadercontent = @file_get_contents($filehtmlheader);
		$dom = new DOMDocument;
		@$dom->loadHTML($htmlheadercontent);
		$styles = $dom->getElementsByTagName('link');
		$scripts = $dom->getElementsByTagName('script');
		foreach($styles as $stylescursor)
		{
			$out.=$stylescursor;
		}
		foreach($scripts as $scriptscursor)
		{
			$out.=$scriptscursor;
		}
		*/

		$out .= "</head>\n";
		$out .= "\n<body>";


		$out .= '<div id="websitecontentundertopmenu" class="websitecontentundertopmenu boostrap-iso">'."\n";

		// REPLACEMENT OF LINKS When page called by website editor

		$out .= '<!-- style of website from file -->'."\n";
		$out .= '<style scoped>'."\n"; // "scoped" means "apply to parent element only and not grand parent". No more supported by browsers, snif !
		$tmpout = '';
		$tmpout .= '/* Include website CSS file */'."\n";
		//$csscontent = @file_get_contents($filecss);
		ob_start();
		include $filecss;
		$csscontent = ob_get_contents();
		ob_end_clean();
		$tmpout .= dolWebsiteReplacementOfLinks($object, $csscontent, 1, 'css');
		$tmpout .= '/* Include style from the HTML header of page */'."\n";
		// Clean the html header of page to get only <style> content
		$tmp = preg_split('(<style[^>]*>|</style>)', $objectpage->htmlheader);
		$tmpstyleinheader = '';
		$i = 0;
		foreach ($tmp as $valtmp) {
			$i++;
			if ($i % 2 == 0) {
				$tmpstyleinheader .= $valtmp."\n";
			}
		}
		$tmpout .= $tmpstyleinheader."\n";
		// Clean style that may affect global style of Dolibarr
		$tmpout = preg_replace('/}[\s\n]*body\s*{[^}]+}/ims', '}', $tmpout);
		$out .= $tmpout;
		$out .= '</style>'."\n";

		// Note: <div>, <section>, ... with contenteditable="true" inside this can be edited with inline ckeditor

		// Do not enable the contenteditable when page was grabbed, ckeditor is removing span and adding borders,
		// so editable will be available only from container created from scratch
		//$out.='<div id="bodywebsite" class="bodywebsite"'.($objectpage->grabbed_from ? ' contenteditable="true"' : '').'>'."\n";
		$out .= '<div id="divbodywebsite" class="bodywebsite bodywebpage-'.$objectpage->ref.'">'."\n";

		$newcontent = $objectpage->content;

		// If mode WEBSITE_SUBCONTAINERSINLINE is on
		if (getDolGlobalString('WEBSITE_SUBCONTAINERSINLINE')) {
			// TODO Check file $filephp exists, if not create it.

			//var_dump($filetpl);
			$filephp = $filetpl;

			// Get session info and obfuscate session cookie
			$savsessionname = session_name();
			$savsessionid = $_COOKIE[$savsessionname];
			$_COOKIE[$savsessionname] = 'obfuscatedcookie';

			ob_start();
			try {
				$res = include $filephp;
				if (empty($res)) {
					print "ERROR: Failed to include file '".$filephp."'. Try to edit and re-save page with this ID.";
				}
			} catch (Exception $e) {
				print $e->getMessage();
			}
			$newcontent = ob_get_contents();
			ob_end_clean();

			// Restore data
			$_COOKIE[$savsessionname] = $savsessionid;
		}

		// Change the contenteditable to "true" or "false" when mode Edit Inline is on or off
		if (!getDolGlobalString('WEBSITE_EDITINLINE')) {
			// Remove the contenteditable="true"
			$newcontent = preg_replace('/(div|section|header|main|footer)(\s[^\>]*)contenteditable="true"/', '\1\2', $newcontent);
		} else {
			// Keep the contenteditable="true" when mode Edit Inline is on
		}
		$out .= dolWebsiteReplacementOfLinks($object, $newcontent, 0, 'html', $objectpage->id)."\n";
		//$out.=$newcontent;

		$out .= '</div>';

		$out .= '</div> <!-- End div id=websitecontentundertopmenu -->';

		/*if ($includepageintoaframeoradiv == 'iframe')
		{
			$out .= "</body></html></iframe>";
		}*/
		$out .= "\n</body></html>\n";

		$out .= "\n".'<!-- End page content '.$filetpl.' -->'."\n\n";

		print $out;

		/*file_put_contents($filetpl, $out);
		dolChmod($filetpl);

		// Output file on browser
		dol_syslog("index.php include $filetpl $filename content-type=$type");
		$original_file_osencoded=dol_osencode($filetpl);    // New file name encoded in OS encoding charset

		// This test if file exists should be useless. We keep it to find bug more easily
		if (! file_exists($original_file_osencoded))
		{
		dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
		exit;
		}

		//include_once $original_file_osencoded;
		*/

		/*print '<iframe class="websiteiframenoborder centpercent" src="'.DOL_URL_ROOT.'/public/website/index.php?website='.$websitekey.'&pageid='.$pageid.'"/>';
		print '</iframe>';*/
	} else {
		if (empty($websitekey) || $websitekey == '-1') {
			print '<br><br><div class="center previewnotyetavailable"><span class="">'.$langs->trans("NoWebSiteCreateOneFirst").'</span></div><br><br><br>';
			print '<div class="center"><div class="logo_setup"></div></div>';
		} else {
			print '<br><br><div class="center previewnotyetavailable"><span class="">'.$langs->trans("PreviewOfSiteNotYetAvailable", $object->ref).'</span></div><br><br><br>';
			print '<div class="center"><div class="logo_setup"></div></div>';
		}
	}
}

// End of page
llxFooter();
$db->close();
