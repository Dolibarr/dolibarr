<?php
/* Copyright (C) 2016-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020 	   Nicolas ZABOURI		<info@inovea-conseil.com>
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

define('NOSCANPOSTFORINJECTION', 1);
define('NOSTYLECHECK', 1);
define('USEDOLIBARREDITOR', 1);
define('FORCE_CKEDITOR', 1); // We need CKEditor, even if module is off.

//header('X-XSS-Protection:0');	// Disable XSS filtering protection of some browsers (note: use of Content-Security-Policy is more efficient). Disabled as deprecated.

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formwebsite.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->loadLangs(array("admin", "other", "website", "errors"));

if (!$user->rights->website->read) accessforbidden();

$conf->dol_hide_leftmenu = 1; // Force hide of left menu.

$error = 0;
$websiteid = GETPOST('websiteid', 'int');
$websitekey = GETPOST('website', 'alpha');
$page = GETPOST('page', 'alpha');
$pageid = GETPOST('pageid', 'int');
$pageref = GETPOST('pageref', 'alphanohtml');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'websitelist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha');

$section_dir = GETPOST('section_dir', 'alpha');
$file_manager = GETPOST('file_manager', 'alpha');
$replacesite = GETPOST('replacesite', 'alpha');

if (GETPOST('deletesite', 'alpha')) { $action = 'deletesite'; }
if (GETPOST('delete', 'alpha')) { $action = 'delete'; }
if (GETPOST('preview', 'alpha')) $action = 'preview';
if (GETPOST('createsite', 'alpha')) { $action = 'createsite'; }
if (GETPOST('createcontainer', 'alpha')) { $action = 'createcontainer'; }
if (GETPOST('editcss', 'alpha')) { $action = 'editcss'; }
if (GETPOST('editmenu', 'alpha')) { $action = 'editmenu'; }
if (GETPOST('setashome', 'alpha')) { $action = 'setashome'; }
if (GETPOST('editmeta', 'alpha')) { $action = 'editmeta'; }
if (GETPOST('editsource', 'alpha')) { $action = 'editsource'; }
if (GETPOST('editcontent', 'alpha')) { $action = 'editcontent'; }
if (GETPOST('exportsite', 'alpha')) { $action = 'exportsite'; }
if (GETPOST('importsite', 'alpha')) { $action = 'importsite'; }
if (GETPOST('createfromclone', 'alpha')) { $action = 'createfromclone'; }
if (GETPOST('createpagefromclone', 'alpha')) { $action = 'createpagefromclone'; }
if (empty($action) && $file_manager) $action = 'file_manager';
if (empty($action) && $replacesite) $action = 'replacesite';
if (GETPOST('refreshsite') || GETPOST('refreshsite_x') || GETPOST('refreshsite.x')) $pageid = 0;

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield='name';
//if (! $sortorder) $sortorder='ASC';

if (empty($action)) $action = 'preview';

$object = new Website($db);
$objectpage = new WebsitePage($db);

$object->fetchAll('ASC', 'position'); // Init $object->records with list of websites

// If website not defined, we take first found
if (!($websiteid > 0) && empty($websitekey) && $action != 'createsite')
{
	foreach ($object->records as $key => $valwebsite)
	{
		$websitekey = $valwebsite->ref;
		break;
	}
}
if ($websiteid > 0 || $websitekey)
{
	$res = $object->fetch($websiteid, $websitekey);
	$websitekey = $object->ref;
}

$website = $object;

// Check pageid received as parameter
if ($pageid < 0) $pageid = 0;
if (($pageid > 0 || $pageref) && $action != 'addcontainer')
{
	$res = $objectpage->fetch($pageid, ($object->id > 0 ? $object->id : null), $pageref);
	if ($res == 0)
	{
		$res = $objectpage->fetch($pageid, ($object->id > 0 ? $object->id : null), null, $pageref);
	}

	// Check if pageid is inside the new website, if not we reset param pageid
	if ($res >= 0 && $object->id > 0)
	{
		if ($objectpage->fk_website != $object->id)	// We have a bad page that does not belong to web site
		{
			if ($object->fk_default_home > 0)
			{
				$res = $objectpage->fetch($object->fk_default_home, $object->id, ''); // We search first page of web site
				if ($res > 0) $pageid = $object->fk_default_home;
			}
			else {
				$res = $objectpage->fetch(0, $object->id, ''); // We search first page of web site
				if ($res == 0)	// Page was not found, we reset it
				{
					$objectpage = new WebsitePage($db);
				}
				else // We found a page, we set pageid to it.
				{
					$pageid = $objectpage->id;
				}
			}
		}
		else // We have a valid page. We force pageid for the case we got the page with a fetch on ref.
		{
			$pageid = $objectpage->id;
		}
	}
}

// Define pageid if pageid and pageref not received as parameter or was wrong
if (empty($pageid) && empty($pageref) && $object->id > 0 && $action != 'createcontainer')
{
	$pageid = $object->fk_default_home;
	if (empty($pageid))
	{
		$array = $objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
		if (!is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
		$atleastonepage = (is_array($array) && count($array) > 0);

		$firstpageid = 0; $homepageid = 0;
		foreach ($array as $key => $valpage)
		{
			if (empty($firstpageid)) $firstpageid = $valpage->id;
			if ($object->fk_default_home && $key == $object->fk_default_home) $homepageid = $valpage->id;
		}
		$pageid = ($homepageid ? $homepageid : $firstpageid); // We choose home page and if not defined yet, we take first page
	}
}


global $dolibarr_main_data_root;
$pathofwebsite = $dolibarr_main_data_root.'/website/'.$websitekey;
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
$filemaster = $pathofwebsite.'/master.inc.php';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


$permtouploadfile = $user->rights->website->write;
$diroutput = $conf->medias->multidir_output[$conf->entity];

$relativepath = $section_dir;
$upload_dir = preg_replace('/\/$/', '', $diroutput).'/'.preg_replace('/^\//', '', $relativepath);

$htmlheadercontentdefault = '';
$htmlheadercontentdefault .= '<link rel="stylesheet" id="google-fonts-css"  href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700" />'."\n";
$htmlheadercontentdefault .= '<link rel="stylesheet" id="font-wasesome-css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />'."\n";
$htmlheadercontentdefault .= '<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>'."\n";
$htmlheadercontentdefault .= '<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>'."\n";
$htmlheadercontentdefault .= '<script src="//cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"></script>'."\n";
$htmlheadercontentdefault .= '<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.13.0/umd/popper.min.js"></script>'."\n";
$htmlheadercontentdefault .= '<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta.2/js/bootstrap.min.js"></script>'."\n";
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
	"src": "images/'.$website->ref.'/homescreen48.png",
	"sizes": "48x48",
	"type": "image/png"
	}, {
		"src": "image/'.$website->ref.'/homescreen72.png",
		"sizes": "72x72",
		"type": "image/png"
	}, {
		"src": "image/'.$website->ref.'/homescreen96.png",
		"sizes": "96x96",
		"type": "image/png"
	}, {
		"src": "image/'.$website->ref.'/homescreen144.png",
		"sizes": "144x144",
		"type": "image/png"
	}, {
		"src": "image/'.$website->ref.'/homescreen168.png",
		"sizes": "168x168",
		"type": "image/png"
	}, {
		"src": "image/'.$website->ref.'/homescreen192.png",
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
if (GETPOST('optionmeta')) $algo .= 'meta';
if (GETPOST('optioncontent')) $algo .= 'content';
if (GETPOST('optionsitefiles')) $algo .= 'sitefiles';

if (empty($sortfield)) {
	if ($action == 'file_manager') {
		$sortfield = 'name'; $sortorder = 'ASC';
	} else {
		$sortfield = 'pageurl'; $sortorder = 'ASC';
	}
}

$searchkey = GETPOST('searchstring', 'restricthtml');

if ($action == 'replacesiteconfirm') {
	$containertype = GETPOST('optioncontainertype', 'aZ09') != '-1' ? GETPOST('optioncontainertype', 'aZ09') : '';
	$langcode = GETPOST('optionlanguage', 'aZ09');
	$otherfilters = array();
	if (GETPOST('optioncategory', 'int') > 0) {
		$otherfilters['category'] = GETPOST('optioncategory', 'int');
	}

	$listofpages = getPagesFromSearchCriterias($containertype, $algo, $searchkey, 1000, $sortfield, $sortorder, $langcode, $otherfilters, -1);
}

$usercanedit = $user->rights->website->write;
$permissiontodelete = $user->rights->website->delete;


/*
 * Actions
 */

// Protections
if (GETPOST('refreshsite') || GETPOST('refreshsite_x') || GETPOST('refreshsite.x') || GETPOST('refreshpage') || GETPOST('refreshpage_x') || GETPOST('refreshpage.x'))
{
	$action = 'preview'; // To avoid to make an action on another page or another site when we click on button to select another site or page.
}
if (GETPOST('refreshsite', 'alpha') || GETPOST('refreshsite.x', 'alpha') || GETPOST('refreshsite_x', 'alpha'))		// If we change the site, we reset the pageid and cancel addsite action.
{
	if ($action == 'addsite') $action = 'preview';
	if ($action == 'updatesource') $action = 'preview';

	$pageid = $object->fk_default_home;
	if (empty($pageid))
	{
		$array = $objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
		if (!is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
		$atleastonepage = (is_array($array) && count($array) > 0);

		$firstpageid = 0; $homepageid = 0;
		foreach ($array as $key => $valpage)
		{
			if (empty($firstpageid)) $firstpageid = $valpage->id;
			if ($object->fk_default_home && $key == $object->fk_default_home) $homepageid = $valpage->id;
		}
		$pageid = ($homepageid ? $homepageid : $firstpageid); // We choose home page and if not defined yet, we take first page
	}
}
if (GETPOST('refreshpage', 'alpha') && !in_array($action, array('updatecss'))) $action = 'preview';

if ($cancel && $action == 'renamefile') {
	$cancel = '';
}

// Cancel
if ($cancel)
{
	$action = 'preview';
	if ($backtopage)
	{
		header("Location: ".$backtopage);
		exit;
	}
}

$savbacktopage = $backtopage;
$backtopage = $_SERVER["PHP_SELF"].'?file_manager=1&website='.$websitekey.'&pageid='.$pageid.(GETPOST('section_dir', 'alpha') ? '&section_dir='.urlencode(GETPOST('section_dir', 'alpha')) : ''); // used after a confirm_deletefile into actions_linkedfiles.inc.php
if ($sortfield) $backtopage .= '&sortfield='.$sortfield;
if ($sortorder) $backtopage .= '&sortorder='.$sortorder;
include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
$backtopage = $savbacktopage;

if ($action == 'renamefile') {	// Must be after include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php'; If action were renamefile, we set it to 'file_manager'
	$action = 'file_manager';
}

if ($action == 'seteditinline')
{
	dolibarr_set_const($db, 'WEBSITE_EDITINLINE', 1);
	setEventMessages($langs->trans("FeatureNotYetAvailable"), null, 'warnings');
	dolibarr_set_const($db, 'WEBSITE_SUBCONTAINERSINLINE', 0); // Force disable of 'Include dynamic content'
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.GETPOST('website', 'alphanohtml').'&pageid='.GETPOST('pageid', 'int'));
	exit;
}
if ($action == 'unseteditinline')
{
	dolibarr_del_const($db, 'WEBSITE_EDITINLINE');
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.GETPOST('website', 'alphanohtml').'&pageid='.GETPOST('pageid', 'int'));
	exit;
}
if ($action == 'setshowsubcontainers')
{
	dolibarr_set_const($db, 'WEBSITE_SUBCONTAINERSINLINE', 1);
	dolibarr_set_const($db, 'WEBSITE_EDITINLINE', 0); // Force disable of edit inline
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.GETPOST('website', 'alphanohtml').'&pageid='.GETPOST('pageid', 'int'));
	exit;
}
if ($action == 'unsetshowsubcontainers')
{
	dolibarr_del_const($db, 'WEBSITE_SUBCONTAINERSINLINE');
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.GETPOST('website', 'alphanohtml').'&pageid='.GETPOST('pageid', 'int'));
	exit;
}

if ($massaction == 'replace' && GETPOST('confirmmassaction', 'alpha') && !$searchkey)
{
	$action = 'replacesite';
	$massaction = '';
}

// Set category
if ($massaction == 'setcategory' && GETPOST('confirmmassaction', 'alpha') && $usercanedit)
{
	$error = 0;
	$nbupdate = 0;

	$db->begin();

	$categoryid = GETPOST('setcategory', 'restricthtml');
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

// Replacement of string into pages
if ($massaction == 'replace' && GETPOST('confirmmassaction', 'alpha'))
{
	$replacestring = GETPOST('replacestring', 'none');

	if (empty($user->rights->website->writephp)) {
		setEventMessages("NotAllowedToAddDynamicContent", null, 'errors');
	}
	elseif (!$replacestring) {
		setEventMessages("ErrorReplaceStringEmpty", null, 'errors');
	}
	else {
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
				if (!$result)
				{
					setEventMessages('Failed to write file '.basename($filealias), null, 'errors');
				}

				// Save page of content
				$result = dolSavePageContent($filetpl, $object, $objectpage);
				if ($result)
				{
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
		if (GETPOST('optioncategory', 'int') > 0) {
			$otherfilters['category'] = GETPOST('optioncategory', 'int');
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

// Add site
if ($action == 'addsite')
{
	$db->begin();

	if (GETPOST('virtualhost', 'alpha') && !preg_match('/^http/', GETPOST('virtualhost', 'alpha')))
	{
		$error++;
		setEventMessages($langs->trans('ErrorURLMustStartWithHttp', $langs->transnoentitiesnoconv("VirtualHost")), null, 'errors');
	}

	if (!$error && !GETPOST('WEBSITE_REF', 'alpha'))
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
	}
	if (!$error && !preg_match('/^[a-z0-9_\-\.]+$/i', GETPOST('WEBSITE_REF', 'alpha')))
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("Ref")), null, 'errors');
	}

	if (!$error)
	{
		$arrayotherlang = explode(',', GETPOST('WEBSITE_OTHERLANG', 'alphanohtml'));
		foreach ($arrayotherlang as $key => $val) {
			$arrayotherlang[$key] = substr(trim($val), 0, 2); // Kept short language code only
		}

		$tmpobject = new Website($db);
		$tmpobject->ref = GETPOST('WEBSITE_REF', 'alpha');
		$tmpobject->description = GETPOST('WEBSITE_DESCRIPTION', 'alphanohtml');
		$tmpobject->lang = GETPOST('WEBSITE_LANG', 'aZ09');
		$tmpobject->otherlang = join(',', $arrayotherlang);
		$tmpobject->virtualhost = GETPOST('virtualhost', 'alpha');

		$result = $tmpobject->create($user);
		if ($result <= 0)
		{
			$error++;
			setEventMessages($tmpobject->error, $tmpobject->errors, 'errors');
		}
	}

	if (!$error)
	{
		$db->commit();
		setEventMessages($langs->trans("SiteAdded", $object->ref), null, 'mesgs');
		$action = '';

		header("Location: ".$_SERVER["PHP_SELF"].'?website='.$tmpobject->ref);
		exit;
	}
	else {
		$db->rollback();
		$action = 'createsite';
	}

	if (!$error)
	{
		$action = 'preview';
		$id = $object->id;
	}
}

// Add page/container
if ($action == 'addcontainer')
{
	dol_mkdir($pathofwebsite);

	$db->begin();

	$objectpage->fk_website = $object->id;

	if (GETPOSTISSET('fetchexternalurl'))	// Fetch from external url
	{
		$urltograb = GETPOST('externalurl', 'alpha');
		$grabimages = GETPOST('grabimages', 'alpha');
		$grabimagesinto = GETPOST('grabimagesinto', 'alpha');

		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		if (empty($urltograb))
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("URL")), null, 'errors');
			$action = 'createcontainer';
		}
		elseif (!preg_match('/^http/', $urltograb))
		{
			$error++;
			$langs->load("errors");
			setEventMessages('Error URL must start with http:// or https://', null, 'errors');
			$action = 'createcontainer';
		}

		if (!$error)
		{
			// Clean url to grab, so url can be
			// http://www.example.com/ or http://www.example.com/dir1/ or http://www.example.com/dir1/aaa
			$urltograbwithoutdomainandparam = preg_replace('/^https?:\/\/[^\/]+\/?/i', '', $urltograb);
			//$urltograbwithoutdomainandparam = preg_replace('/^file:\/\/[^\/]+\/?/i', '', $urltograb);
			$urltograbwithoutdomainandparam = preg_replace('/\?.*$/', '', $urltograbwithoutdomainandparam);
			if (empty($urltograbwithoutdomainandparam) && !preg_match('/\/$/', $urltograb))
			{
				$urltograb .= '/';
			}
			$pageurl = dol_sanitizeFileName(preg_replace('/[\/\.]/', '-', preg_replace('/\/+$/', '', $urltograbwithoutdomainandparam)));

			$urltograbdirwithoutslash = dirname($urltograb.'.');
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograbdirwithoutslash);
			// Exemple, now $urltograbdirwithoutslash is https://www.dolimed.com/screenshots
			// and $urltograbdirrootwithoutslash is https://www.dolimed.com
		}

		// Check pageurl is not already used
		if ($pageurl)
		{
			$tmpwebsitepage = new WebsitePage($db);
			$result = $tmpwebsitepage->fetch(0, $object->id, $pageurl);
			if ($result > 0)
			{
				setEventMessages($langs->trans("AliasPageAlreadyExists", $pageurl), null, 'errors');
				$error++;
				$action = 'createcontainer';
			}
		}

		if (!$error)
		{
			$tmp = getURLContent($urltograb);
			if ($tmp['curl_error_no'])
			{
				$error++;
				setEventMessages('Error getting '.$urltograb.': '.$tmp['curl_error_msg'], null, 'errors');
				$action = 'createcontainer';
			}
			elseif ($tmp['http_code'] != '200')
			{
				$error++;
				setEventMessages('Error getting '.$urltograb.': '.$tmp['http_code'], null, 'errors');
				$action = 'createcontainer';
			}
			else {
				// Remove comments
				$tmp['content'] = removeHtmlComment($tmp['content']);

				$regs = array();

				preg_match('/<head>(.*)<\/head>/ims', $tmp['content'], $regs);
				$head = $regs[1];

				$objectpage->type_container = 'page';
	   			$objectpage->pageurl = $pageurl;
	   			if (empty($objectpage->pageurl))
	   			{
	   				$tmpdomain = getDomainFromURL($urltograb);
	   				$objectpage->pageurl = $tmpdomain.'-home';
	   			}

	   			$objectpage->aliasalt = '';

	   			if (preg_match('/^(\d+)\-/', basename($urltograb), $regs)) $objectpage->aliasalt = $regs[1];

	   			$regtmp = array();
				if (preg_match('/<title>(.*)<\/title>/ims', $head, $regtmp))
				{
					$objectpage->title = $regtmp[1];
				}
				if (preg_match('/<meta name="title"[^"]+content="([^"]+)"/ims', $head, $regtmp))
				{
					if (empty($objectpage->title)) $objectpage->title = $regtmp[1]; // If title not found into <title>, we get it from <meta title>
				}
				if (preg_match('/<meta name="description"[^"]+content="([^"]+)"/ims', $head, $regtmp))
				{
					$objectpage->description = $regtmp[1];
				}
				if (preg_match('/<meta name="keywords"[^"]+content="([^"]+)"/ims', $head, $regtmp))
				{
					$objectpage->keywords = $regtmp[1];
				}
				if (preg_match('/<html\s+lang="([^"]+)"/ims', $tmp['content'], $regtmp))
				{
					$tmplang = explode('-', $regtmp[1]);
					$objectpage->lang = $tmplang[0].($tmplang[1] ? '_'.strtoupper($tmplang[1]) : '');
				}

				$tmp['content'] = preg_replace('/\s*<meta name="generator"[^"]+content="([^"]+)"\s*\/?>/ims', '', $tmp['content']);

				$objectpage->content = $tmp['content'];
				$objectpage->content = preg_replace('/^.*<body(\s[^>]*)*>/ims', '', $objectpage->content);
				$objectpage->content = preg_replace('/<\/body(\s[^>]*)*>.*$/ims', '', $objectpage->content);

				$absoluteurlinaction = $urltograbdirwithoutslash;
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
				foreach ($regs[0] as $key => $val)
				{
					dol_syslog("We will grab the script resource found into script tag ".$regs[2][$key]);

					$linkwithoutdomain = $regs[2][$key];
					if (preg_match('/^\//', $regs[2][$key]))
					{
						$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
					}
					else {
						$urltograbbis = $urltograbdirwithoutslash.'/'.$regs[2][$key]; // We use dir of grabbed file
					}

					//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];
					if (preg_match('/^http/', $regs[2][$key]))
					{
						$urltograbbis = $regs[2][$key];
						$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
						//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
					}

					//print $domaintograb.' - '.$domaintograbbis.' - '.$urltograbdirwithoutslash.' - ';
					//print $linkwithoutdomain.' - '.$urltograbbis."<br>\n";

					// Test if this is an external URL of grabbed web site. If yes, we do not load resource
					$domaintograb = getDomainFromURL($urltograbdirwithoutslash);
					$domaintograbbis = getDomainFromURL($urltograbbis);
					if ($domaintograb != $domaintograbbis) continue;

					/*
	    			$tmpgeturl = getURLContent($urltograbbis);
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
	    				if (! empty($conf->global->MAIN_UMASK))
	    					@chmod($file, octdec($conf->global->MAIN_UMASK));
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
				foreach ($regs[0] as $key => $val)
				{
					dol_syslog("We will grab the css resources found into link tag ".$regs[2][$key]);

					$linkwithoutdomain = $regs[2][$key];
					if (preg_match('/^\//', $regs[2][$key]))
					{
						$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
					}
					else {
						$urltograbbis = $urltograbdirwithoutslash.'/'.$regs[2][$key]; // We use dir of grabbed file
					}

					//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];
					if (preg_match('/^http/', $regs[2][$key]))
					{
						$urltograbbis = $regs[2][$key];
						$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
						//$filetosave = $conf->medias->multidir_output[$conf->entity].'/css/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
					}

					//print $domaintograb.' - '.$domaintograbbis.' - '.$urltograbdirwithoutslash.' - ';
					//print $linkwithoutdomain.' - '.$urltograbbis."<br>\n";

					// Test if this is an external URL of grabbed web site. If yes, we do not load resource
					$domaintograb = getDomainFromURL($urltograbdirwithoutslash);
					$domaintograbbis = getDomainFromURL($urltograbbis);
					if ($domaintograb != $domaintograbbis) continue;

					$tmpgeturl = getURLContent($urltograbbis);
					if ($tmpgeturl['curl_error_no'])
					{
						$errorforsubresource++;
						setEventMessages('Error getting link tag url '.$urltograbbis.': '.$tmpgeturl['curl_error_msg'], null, 'errors');
						dol_syslog('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg']);
						$action = 'createcontainer';
					}
					elseif ($tmpgeturl['http_code'] != '200')
					{
						$errorforsubresource++;
						setEventMessages('Error getting link tag url '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
						dol_syslog('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg']);
						$action = 'createcontainer';
					}
					else {
						// Clean some comment
						//$tmpgeturl['content'] = dol_string_is_good_iso($tmpgeturl['content'], 1);
						//$tmpgeturl['content'] = utf8_encode(utf8_decode($tmpgeturl['content']));
						//$tmpgeturl['content'] = mb_convert_encoding($tmpgeturl['content'], 'UTF-8', 'UTF-8');
						//$tmpgeturl['content'] = remove_bs($tmpgeturl['content']);
						//$tmpgeturl['content'] = str_replace('$screen-md-max', 'auto', $tmpgeturl['content']);

						//var_dump($tmpgeturl['content']);exit;
						$tmpgeturl['content'] = preg_replace('/\/\*\s+CSS content[a-z\s]*\s+\*\//', '', $tmpgeturl['content']);

						//dol_mkdir(dirname($filetosave));

						//$fp = fopen($filetosave, "w");
						//fputs($fp, $tmpgeturl['content']);
						//fclose($fp);
						//if (! empty($conf->global->MAIN_UMASK))
						//	@chmod($file, octdec($conf->global->MAIN_UMASK));

						//	$filename = 'image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
						$pagecsscontent .= '/* Content of file '.$urltograbbis.' */'."\n";

						getAllImages($object, $objectpage, $urltograbbis, $tmpgeturl['content'], $action, 1, $grabimages, $grabimagesinto);

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
		$objectpage->title = str_replace(array('<', '>'), '', GETPOST('WEBSITE_TITLE', 'alphanohtml'));
		$objectpage->type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'aZ09');
		$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
		$objectpage->aliasalt = str_replace(array('<', '>'), '', GETPOST('WEBSITE_ALIASALT', 'alphanohtml'));
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
		$substitutionarray['__WEBSITE_CREATE_BY__'] = $user->getFullName($langs);

		// Define id of page the new page is translation of
		$pageidfortranslation = (GETPOST('pageidfortranslation', 'int') > 0 ? GETPOST('pageidfortranslation', 'int') : 0);
		if ($pageidfortranslation > 0) {
			// Check if the page we are translation of is alreayd a translation of a source page. if yes, we will use source id instead
			$objectpagetmp = new WebsitePage($db);
			$objectpagetmp->fetch($pageidfortranslation);
			if ($objectpagetmp->fk_page > 0) {
				$pageidfortranslation = $objectpagetmp->fk_page;
			}
		}
		$objectpage->fk_page = $pageidfortranslation;

		$sample = GETPOST('sample', 'alpha');
		if (empty($sample)) $sample = 'empty';

		$pathtosample = DOL_DOCUMENT_ROOT.'/website/samples/page-sample-'.dol_sanitizeFileName($sample).'.html';

		// Init content with content into pagetemplate.html, blogposttempltate.html, ...
		$objectpage->content = make_substitutions(@file_get_contents($pathtosample), $substitutionarray);
	}

	if (!$error)
	{
		if (empty($objectpage->pageurl))
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_PAGENAME")), null, 'errors');
			$error++;
			$action = 'createcontainer';
		}
		elseif (!preg_match('/^[a-z0-9\-\_]+$/i', $objectpage->pageurl))
		{
			$langs->load("errors");
			setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
			$error++;
			$action = 'createcontainer';
		}
		if (empty($objectpage->title))
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_TITLE")), null, 'errors');
			$error++;
			$action = 'createcontainer';
		}
		if ($objectpage->fk_page > 0 && empty($objectpage->lang))
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLanguageRequiredIfPageIsTranslationOfAnother"), null, 'errors');
			$error++;
			$action = 'createcontainer';
		}
		if ($objectpage->fk_page > 0 && !empty($objectpage->lang))
		{
			if ($objectpage->lang == $website->lang) {
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorLanguageMustNotBeSourceLanguageIfPageIsTranslationOfAnother"), null, 'errors');
				$error++;
				$action = 'createcontainer';
			}
		}
	}

	if (!$error)
	{
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
		if ($result < 0)
		{
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (!$error) {
		// If there is no home page yet, this new page will be set as the home page
		if (empty($object->fk_default_home)) {
			$object->fk_default_home = $pageid;
			$res = $object->update($user);
			if ($res <= 0)
			{
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				$filetpl = $pathofwebsite.'/page'.$pageid.'.tpl.php';

				// Generate the index.php page (to be the home page) and wrapper.php file
				$result = dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl, $filewrapper);

				if ($result <= 0) setEventMessages('Failed to write file '.$fileindex, null, 'errors');
			}
		}
	}

	if (!$error)
	{
		if (!empty($objectpage->content))
		{
			$filealias = $pathofwebsite.'/'.$objectpage->pageurl.'.php';
			$filetpl = $pathofwebsite.'/page'.$objectpage->id.'.tpl.php';

			// Save page alias
			$result = dolSavePageAlias($filealias, $object, $objectpage);
			if (!$result)
			{
				setEventMessages('Failed to write file '.basename($filealias), null, 'errors');
			}

			// Save page of content
			$result = dolSavePageContent($filetpl, $object, $objectpage);
			if ($result)
			{
				setEventMessages($langs->trans("Saved"), null, 'mesgs');
			}
			else {
				setEventMessages('Failed to write file '.$filetpl, null, 'errors');
				$action = 'createcontainer';
			}
		}
	}

	if (!$error)
	{
		$db->commit();
		setEventMessages($langs->trans("PageAdded", $objectpage->pageurl), null, 'mesgs');
		$action = '';
	}
	else {
		$db->rollback();
	}

	if (!$error)
	{
		$pageid = $objectpage->id;

		// To generate the CSS, robot and htmlheader file.

		// Check symlink to medias and restore it if ko
		$pathtomedias = DOL_DATA_ROOT.'/medias';
		$pathtomediasinwebsite = $pathofwebsite.'/medias';
		if (!is_link(dol_osencode($pathtomediasinwebsite)))
		{
			dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
			dol_mkdir(dirname($pathtomediasinwebsite)); // To be sure dir for website exists
			$result = symlink($pathtomedias, $pathtomediasinwebsite);
		}

		// Now generate the master.inc.php page if it does not exists yet
		if (!dol_is_file($filemaster))
		{
			$result = dolSaveMasterFile($filemaster);
			if (!$result)
			{
				$error++;
				setEventMessages('Failed to write file '.$filemaster, null, 'errors');
			}
		}

		if (!dol_is_file($filehtmlheader))
		{
			$htmlheadercontent = "<html>\n";
			$htmlheadercontent .= $htmlheadercontentdefault;
			$htmlheadercontent .= "</html>";
			$result = dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent);
		}

		if (!dol_is_file($filecss))
		{
			$csscontent = "/* CSS content (all pages) */\nbody.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }\n.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}";
			$result = dolSaveCssFile($filecss, $csscontent);
		}

		if (!dol_is_file($filejs))
		{
			$jscontent = "/* JS content (all pages) */\n";
			$result = dolSaveJsFile($filejs, $jscontent);
		}

		if (!dol_is_file($filerobot))
		{
			$robotcontent = "# Robot file. Generated with Dolibarr\nUser-agent: *\nAllow: /public/\nDisallow: /administrator/";
			$result = dolSaveRobotFile($filerobot, $robotcontent);
		}

		if (!dol_is_file($filehtaccess))
		{
			$htaccesscontent = "# Order allow,deny\n# Deny from all";
			$result = dolSaveHtaccessFile($filehtaccess, $htaccesscontent);
		}

		if (!dol_is_file($filemanifestjson))
		{
			$manifestjsoncontent = "";
			$result = dolSaveManifestJson($filemanifestjson, $manifestjsoncontent);
		}

		if (!dol_is_file($filereadme))
		{
			$readmecontent = "Website generated by Dolibarr ERP CRM";
			$result = dolSaveReadme($filereadme, $readmecontent);
		}

		$action = 'preview';
	}
}

// Delete site
if ($action == 'confirm_deletesite' && $confirm == 'yes')
{
	$error = 0;

	$db->begin();

	$res = $object->fetch(GETPOST('id', 'int'));
	$website = $object;

	if ($res > 0)
	{
		$res = $object->delete($user);
		if ($res <= 0)
		{
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	if (!$error)
	{
		if (GETPOST('delete_also_js', 'alpha') == 'on')
		{
			$pathofwebsitejs = DOL_DATA_ROOT.'/medias/js/'.$object->ref;

			dol_delete_dir_recursive($pathofwebsitejs);
		}
		if (GETPOST('delete_also_medias', 'alpha') == 'on')
		{
			$pathofwebsitemedias = DOL_DATA_ROOT.'/medias/image/'.$object->ref;

			dol_delete_dir_recursive($pathofwebsitemedias);
		}
	}

	if (!$error)
	{
		$db->commit();
		setEventMessages($langs->trans("SiteDeleted", $object->ref), null, 'mesgs');

		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
	else {
		$db->rollback();
		dol_print_error($db);
	}
}

// Delete page (from website page menu)
if (GETPOSTISSET('pageid') && $action == 'delete' && $permissiontodelete) {
	$error = 0;

	$db->begin();

	$res = $object->fetch(0, $websitekey);
	$website = $object;

	$res = $objectpage->fetch($pageid, $object->id);

	if ($res > 0)
	{
		$res = $objectpage->delete($user);
		if ($res <= 0)
		{
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		}
	}

	if (!$error)
	{
		$db->commit();
		setEventMessages($langs->trans("PageDeleted", $objectpage->pageurl, $websitekey), null, 'mesgs');

		header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey);
		exit;
	}
	else {
		$db->rollback();
		dol_print_error($db);
	}
}
// Delete page (from menu search)
if (!GETPOSTISSET('pageid')) {
	$objectclass = 'WebsitePage';

	// Add part of code from actions_massactions.inc.php
	// Delete record from mass action (massaction = 'delete' for direct delete, action/confirm='delete'/'yes' with a confirmation step before)
	if (!$error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete)
	{
		$db->begin();

		$objecttmp = new $objectclass($db);
		$nbok = 0;
		foreach ($toselect as $toselectid)
		{
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0)
			{
				$result = $objecttmp->delete($user);

				if ($result <= 0)
				{
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
					break;
				} else $nbok++;
			} else {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			}
		}

		if (!$error)
		{
			if ($nbok > 1) setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
			else setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
			$db->commit();
		} else {
			$db->rollback();
		}
		//var_dump($listofobjectthirdparties);exit;
	}

	if ($action == 'delete') {
		$action = 'replacesiteconfirm';

		$containertype = GETPOST('optioncontainertype', 'aZ09') != '-1' ? GETPOST('optioncontainertype', 'aZ09') : '';
		$langcode = GETPOST('optionlanguage', 'aZ09');
		$otherfilters = array();
		if (GETPOST('optioncategory', 'int') > 0) {
			$otherfilters['category'] = GETPOST('optioncategory', 'int');
		}

		$listofpages = getPagesFromSearchCriterias($containertype, $algo, $searchkey, 1000, $sortfield, $sortorder, $langcode, $otherfilters);
	}
}

// Update css Update site properties
if ($action == 'updatecss')
{
	// If we tried to reload another site/page, we stay on editcss mode.
	if (GETPOST('refreshsite') || GETPOST('refreshsite_x') || GETPOST('refreshsite.x') || GETPOST('refreshpage') || GETPOST('refreshpage_x') || GETPOST('refreshpage.x'))
	{
		$action = 'editcss';
	}
	else {
		$res = $object->fetch(0, $websitekey);
		$website = $object;

		if (GETPOSTISSET('virtualhost'))
		{
			$tmpvirtualhost = preg_replace('/\/$/', '', GETPOST('virtualhost', 'alpha'));
			if ($tmpvirtualhost && !preg_match('/^http/', $tmpvirtualhost))
			{
				$error++;
				setEventMessages($langs->trans('ErrorURLMustStartWithHttp', $langs->transnoentitiesnoconv("VirtualHost")), null, 'errors');
				$action = 'editcss';
			}

			if (!$error)
			{
				$arrayotherlang = explode(',', GETPOST('WEBSITE_OTHERLANG', 'alphanohtml'));
				foreach ($arrayotherlang as $key => $val) {
					$arrayotherlang[$key] = substr(trim($val), 0, 2); // Kept short language code only
				}

				$object->virtualhost = $tmpvirtualhost;
				$object->lang = GETPOST('WEBSITE_LANG', 'aZ09');
				$object->otherlang = join(',', $arrayotherlang);
				$object->use_manifest = GETPOST('use_manifest', 'alpha');

				$result = $object->update($user);
				if ($result < 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'editcss';
				}
			}
		}

		if (!$error)
		{
			// Save master.inc.php file
			dol_syslog("Save master file ".$filemaster);

			dol_mkdir($pathofwebsite);

			// Now generate the master.inc.php page
			$result = dolSaveMasterFile($filemaster);
			if (!$result)
			{
				$error++;
				setEventMessages('Failed to write file '.$filemaster, null, 'errors');
			}


			// Html header file
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

			$htmlheadercontent .= preg_replace(array('/<html>\n*/ims', '/<\/html>\n*/ims'), array('', ''), GETPOST('WEBSITE_HTML_HEADER', 'none'));

			/*$htmlheadercontent.= "\n".'<?php // BEGIN PHP'."\n";
    	    $htmlheadercontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
    	    $htmlheadercontent.= "// END PHP ?>"."\n";*/

			$htmlheadercontent = trim($htmlheadercontent)."\n";

			$result = dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent);
			if (!$result)
			{
				$error++;
				setEventMessages('Failed to write file '.$filehtmlheader, null, 'errors');
			}


			// Css file
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

			$csscontent .= trim(GETPOST('WEBSITE_CSS_INLINE', 'none'))."\n";

			$csscontent .= '<?php // BEGIN PHP'."\n";
			$csscontent .= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "css");'."\n";
			$csscontent .= "// END PHP ?>\n";

			dol_syslog("Save css content into ".$filecss);

			$result = dolSaveCssFile($filecss, $csscontent);
			if (!$result)
			{
				$error++;
				setEventMessages('Failed to write file '.$filecss, null, 'errors');
			}


			// Js file
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

			$jscontent .= trim(GETPOST('WEBSITE_JS_INLINE', 'none'))."\n";

			$jscontent .= '<?php // BEGIN PHP'."\n";
			$jscontent .= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "js");'."\n";
			$jscontent .= "// END PHP ?>\n";

			$result = dolSaveJsFile($filejs, $jscontent);
			if (!$result)
			{
				$error++;
				setEventMessages('Failed to write file '.$filejs, null, 'errors');
			}


			// Robot file
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

			$robotcontent .= trim(GETPOST('WEBSITE_ROBOT', 'restricthtml'))."\n";

			/*$robotcontent.= "\n".'<?php // BEGIN PHP'."\n";
    	    $robotcontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "robot");'."\n";
    	    $robotcontent.= "// END PHP ?>"."\n";*/

			$result = dolSaveRobotFile($filerobot, $robotcontent);
			if (!$result)
			{
				$error++;
				setEventMessages('Failed to write file '.$filerobot, null, 'errors');
			}


			// Htaccess file
			$htaccesscontent = '';
			$htaccesscontent .= trim(GETPOST('WEBSITE_HTACCESS', 'restricthtml'))."\n";

			$result = dolSaveHtaccessFile($filehtaccess, $htaccesscontent);
			if (!$result)
			{
				$error++;
				setEventMessages('Failed to write file '.$filehtaccess, null, 'errors');
			}


	   		// manifest.json file
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

	   		$manifestjsoncontent .= trim(GETPOST('WEBSITE_MANIFEST_JSON', 'none'))."\n";

	   		$manifestjsoncontent .= '<?php // BEGIN PHP'."\n";
	   		$manifestjsoncontent .= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "manifest");'."\n";
	   		$manifestjsoncontent .= "// END PHP ?>\n";

	   		$result = dolSaveManifestJson($filemanifestjson, $manifestjsoncontent);
	   		if (!$result)
	   		{
	   			$error++;
	   			setEventMessages('Failed to write file '.$filemanifestjson, null, 'errors');
	   		}


	   		// README.md file
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

	   		$readmecontent .= trim(GETPOST('WEBSITE_README', 'restricthtml'))."\n";

	   		/*$readmecontent.= '<?php // BEGIN PHP'."\n";
       		$readmecontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "manifest");'."\n";
       		$readmecontent.= "// END PHP ?>"."\n";*/

	   		$result = dolSaveReadme($filereadme, $readmecontent);
	   		if (!$result)
	   		{
	   			$error++;
	   			setEventMessages('Failed to write file '.$filereadme, null, 'errors');
	   		}


	   		// Save wrapper.php
			$result = dolSaveIndexPage($pathofwebsite, '', '', $filewrapper);


			// Message if no error
			if (!$error)
			{
				setEventMessages($langs->trans("Saved"), null, 'mesgs');
			}

			if (!GETPOSTISSET('updateandstay'))	// If we click on "Save And Stay", we don not make the redirect
			{
				$action = 'preview';
				if ($backtopage)
				{
					header("Location: ".$backtopage);
					exit;
				}
			}
			else {
				$action = 'editcss';
			}
		}
	}
}

// Update page
if ($action == 'setashome')
{
	$db->begin();
	$object->fetch(0, $websitekey);
	$website = $object;

	$object->fk_default_home = $pageid;
	$res = $object->update($user);
	if (!$res > 0)
	{
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}

	if (!$error)
	{
		$db->commit();

		$filetpl = $pathofwebsite.'/page'.$pageid.'.tpl.php';

		// Generate the index.php page to be the home page
		$result = dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl, $filewrapper);

		if ($result) setEventMessages($langs->trans("Saved"), null, 'mesgs');
		else setEventMessages('Failed to write file '.$fileindex, null, 'errors');

		$action = 'preview';
	}
	else {
		$db->rollback();
	}
}

// Update page properties (meta)
if ($action == 'updatemeta')
{
	$db->begin();

	$result = $object->fetch(0, $websitekey);
	$website = $object;

	$objectpage->fk_website = $object->id;

	// Check parameters
	if (!preg_match('/^[a-z0-9\-\_]+$/i', GETPOST('WEBSITE_PAGENAME', 'alpha')))
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
		$action = 'editmeta';
	}

	$res = $objectpage->fetch($pageid, $object->id);
	if ($res <= 0)
	{
		$error++;
		setEventMessages('Page not found '.$objectpage->error, $objectpage->errors, 'errors');
	}

	// Check alias not exists
	if (!$error && GETPOST('WEBSITE_PAGENAME', 'alpha'))
	{
		$websitepagetemp = new WebsitePage($db);
		$result = $websitepagetemp->fetch(-1 * $objectpage->id, $object->id, GETPOST('WEBSITE_PAGENAME', 'alpha'));
		if ($result < 0)
		{
			$error++;
			$langs->load("errors");
			setEventMessages($websitepagetemp->error, $websitepagetemp->errors, 'errors');
			$action = 'editmeta';
		}
		if ($result > 0)
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists", $websitepagetemp->pageurl), null, 'errors');
			$action = 'editmeta';
		}
	}
	if (!$error && GETPOST('WEBSITE_ALIASALT', 'alpha'))
	{
		$arrayofaliastotest = explode(',', GETPOST('WEBSITE_ALIASALT', 'alpha'));
		$websitepagetemp = new WebsitePage($db);
		foreach ($arrayofaliastotest as $aliastotest)
		{
			// Disallow alias name pageX (already used to save the page with id)
			if (preg_match('/^page\d+/i', $aliastotest)) {
				$error++;
				$langs->load("errors");
				setEventMessages("Alias 'pageX' is not allowed", null, 'errors');
				$action = 'editmeta';
				break;
			} else {
				$result = $websitepagetemp->fetch(-1 * $objectpage->id, $object->id, $aliastotest);
				if ($result < 0)
				{
					$error++;
					$langs->load("errors");
					setEventMessages($websitepagetemp->error, $websitepagetemp->errors, 'errors');
					$action = 'editmeta';
					break;
				}
				if ($result > 0)
				{
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists", $websitepagetemp->pageurl), null, 'errors');
					$action = 'editmeta';
					break;
				}
			}
		}
	}

	if (!$error)
	{
		$objectpage->old_object = clone $objectpage;

		$objectpage->title = str_replace(array('<', '>'), '', GETPOST('WEBSITE_TITLE', 'alphanohtml'));
		$objectpage->type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'aZ09');
		$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
		$objectpage->aliasalt = str_replace(array('<', '>'), '', GETPOST('WEBSITE_ALIASALT', 'alphanohtml'));
		$objectpage->lang = GETPOST('WEBSITE_LANG', 'aZ09');
		$objectpage->otherlang = GETPOST('WEBSITE_OTHERLANG', 'aZ09comma');
		$objectpage->description = str_replace(array('<', '>'), '', GETPOST('WEBSITE_DESCRIPTION', 'alphanohtml'));
		$objectpage->image = GETPOST('WEBSITE_IMAGE', 'alpha');
		$objectpage->keywords = str_replace(array('<', '>'), '', GETPOST('WEBSITE_KEYWORDS', 'alphanohtml'));
		$objectpage->allowed_in_frames = GETPOST('WEBSITE_ALLOWED_IN_FRAMES', 'aZ09');
		$objectpage->htmlheader = trim(GETPOST('htmlheader', 'none'));
		$objectpage->fk_page = (GETPOST('pageidfortranslation', 'int') > 0 ? GETPOST('pageidfortranslation', 'int') : 0);
		$objectpage->author_alias = trim(GETPOST('WEBSITE_AUTHORALIAS', 'alphanohtml'));
		$objectpage->object_type = GETPOST('WEBSITE_OBJECTCLASS', 'alpha');
		$objectpage->fk_object = GETPOST('WEBSITE_OBJECTID', 'aZ09');

		$newdatecreation = dol_mktime(GETPOST('datecreationhour', 'int'), GETPOST('datecreationmin', 'int'), GETPOST('datecreationsec', 'int'), GETPOST('datecreationmonth', 'int'), GETPOST('datecreationday', 'int'), GETPOST('datecreationyear', 'int'));
		if ($newdatecreation) $objectpage->date_creation = $newdatecreation;

		$res = $objectpage->update($user);
		if (!($res > 0))
		{
			$langs->load("errors");
			if ($db->lasterrno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists"), null, 'errors');
				$action = 'editmeta';
			}
			else {
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
		if ($result < 0)
		{
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (!$error)
	{
		$db->commit();
	}
	else {
		$db->rollback();
	}

	if (!$error)
	{
		$filemaster = $pathofwebsite.'/master.inc.php';
		$fileoldalias = $pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
		$filealias = $pathofwebsite.'/'.$objectpage->pageurl.'.php';

		dol_mkdir($pathofwebsite);


		// Now generate the master.inc.php page
		$result = dolSaveMasterFile($filemaster);
		if (!$result) setEventMessages('Failed to write file '.$filemaster, null, 'errors');

		// Now delete the alias.php page
		if (!empty($fileoldalias))
		{
			dol_syslog("We delete old alias page name=".$fileoldalias." to build a new alias page=".$filealias);
			dol_delete_file($fileoldalias);
		}
		// Now delete the alternative alias.php pages
		if (!empty($objectpage->old_object->aliasalt))
		{
			$tmpaltaliases = explode(',', $objectpage->old_object->aliasalt);
			if (is_array($tmpaltaliases))
			{
				foreach ($tmpaltaliases as $tmpaliasalt)
				{
					dol_syslog("We delete old alt alias pages name=".trim($tmpaliasalt));
					dol_delete_file($pathofwebsite.'/'.trim($tmpaliasalt).'.php');
				}
			}
		}

		// Save page alias
		$result = dolSavePageAlias($filealias, $object, $objectpage);
		if (!$result) setEventMessages('Failed to write file '.$filealias, null, 'errors');
		// Save alt aliases
		if (!empty($objectpage->aliasalt))
		{
			$tmpaltaliases = explode(',', $objectpage->aliasalt);
			if (is_array($tmpaltaliases))
			{
				foreach ($tmpaltaliases as $tmpaliasalt)
				{
					if (trim($tmpaliasalt))
					{
						$filealias = $pathofwebsite.'/'.trim($tmpaliasalt).'.php';
						$result = dolSavePageAlias($filealias, $object, $objectpage);
						if (!$result) setEventMessages('Failed to write file '.basename($filealias), null, 'errors');
					}
				}
			}
		}


		// Save page of content
		$result = dolSavePageContent($filetpl, $object, $objectpage);
		if ($result)
		{
			setEventMessages($langs->trans("Saved"), null, 'mesgs');

			if (!GETPOSTISSET('updateandstay'))	// If we click on "Save And Stay", we do not make the redirect
			{
				//header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
				//exit;
				$action = 'preview';
			}
			else {
				$action = 'editmeta';
			}
		}
		else {
			setEventMessages('Failed to write file '.$filetpl, null, 'errors');
			//header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
   			//exit;
			$action = 'preview';
		}
	}
}

// Update page
if (($action == 'updatesource' || $action == 'updatecontent' || $action == 'confirm_createfromclone' || $action == 'confirm_createpagefromclone')
	|| ($action == 'preview' && (GETPOST('refreshsite') || GETPOST('refreshpage') || GETPOST('preview'))))
{
	$object->fetch(0, $websitekey);
	$website = $object;

	if ($action == 'confirm_createfromclone')
	{
		$db->begin();

		$objectnew = new Website($db);
		$result = $objectnew->createFromClone($user, GETPOST('id', 'int'), GETPOST('siteref', 'aZ09'), (GETPOST('newlang', 'aZ09') ?GETPOST('newlang', 'aZ09') : ''));

		if ($result < 0)
		{
			$error++;
			setEventMessages($objectnew->error, $objectnew->errors, 'errors');
			$action = 'preview';

			$db->rollback();
		}
		else {
			$object = $objectnew;
			$id = $object->id;
			$pageid = $object->fk_default_home;
			$websitekey = GETPOST('siteref', 'aZ09');

			$db->commit();
		}
	}

	if ($action == 'confirm_createpagefromclone')
	{
		$istranslation = (GETPOST('is_a_translation', 'aZ09') == 'on' ? 1 : 0);
		// Protection if it is a translation page
		if ($istranslation)
		{
			if (GETPOST('newlang', 'aZ09') == $objectpage->lang || !GETPOST('newlang', 'aZ09')) {
				$error++;
				setEventMessages($langs->trans("LanguageMustNotBeSameThanClonedPage"), null, 'errors');
				$action = 'preview';
			}
			if (GETPOST('newwebsite', 'int') != $object->id) {
				$error++;
				setEventMessages($langs->trans("WebsiteMustBeSameThanClonedPageIfTranslation"), null, 'errors');
				$action = 'preview';
			}
		}

		if (!$error)
		{
			$db->begin();

			$newwebsiteid = GETPOST('newwebsite', 'int');
			$pathofwebsitenew = $pathofwebsite;

			$tmpwebsite = new Website($db);
			if ($newwebsiteid > 0 && $newwebsiteid != $object->id)
			{
				$tmpwebsite->fetch($newwebsiteid);
				$pathofwebsitenew = $dolibarr_main_data_root.'/website/'.$tmpwebsite->ref;
			}
			else {
				$tmpwebsite = $object;
			}

			$objectpage = new WebsitePage($db);
			$resultpage = $objectpage->createFromClone($user, $pageid, GETPOST('newpageurl', 'aZ09'), (GETPOST('newlang', 'aZ09') ? GETPOST('newlang', 'aZ09') : ''), $istranslation, $newwebsiteid, GETPOST('newtitle', 'alphanohtml'));
			if ($resultpage < 0)
			{
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

				dolSavePageContent($filetpl, $tmpwebsite, $resultpage);

				// Switch on the new page if web site of new page/container is same
				if (empty($newwebsiteid) || $newwebsiteid == $object->id)
				{
					$pageid = $resultpage->id;
				}

				$db->commit();
			}
		}
	}

	$res = 0;

	if (!$error)
	{
		// Check symlink to medias and restore it if ko
		$pathtomedias = DOL_DATA_ROOT.'/medias';
		$pathtomediasinwebsite = $pathofwebsite.'/medias';
		if (!is_link(dol_osencode($pathtomediasinwebsite)))
		{
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

		if ($pageid > 0)
		{
			$res = $objectpage->fetch($pageid);
		}
		else {
			$res = 0;
			if ($object->fk_default_home > 0)
			{
				$res = $objectpage->fetch($object->fk_default_home);
			}
			if (!($res > 0))
			{
				$res = $objectpage->fetch(0, $object->id);
			}
		}
	}

	if (!$error && $res > 0)
	{
		if ($action == 'updatesource' || $action == 'updatecontent')
		{
			$db->begin();

			$phpfullcodestringold = dolKeepOnlyPhpCode($objectpage->content);

			$objectpage->content = GETPOST('PAGE_CONTENT', 'none');

			// Security analysis
			$phpfullcodestring = dolKeepOnlyPhpCode($objectpage->content);
			//print dol_escape_htmltag($phpfullcodestring);exit;
			$forbiddenphpcommands = array("exec", "passthru", "system", "shell_exec", "proc_open");
			if (empty($conf->global->WEBSITE_PHP_ALLOW_WRITE))    // If option is not on, we disallow functions to write files
			{
				$forbiddenphpcommands = array_merge($forbiddenphpcommands, array("fopen", "file_put_contents", "fputs", "fputscsv", "fwrite", "fpassthru", "unlink", "mkdir", "rmdir", "symlink", "touch", "umask"));
			}
			foreach ($forbiddenphpcommands as $forbiddenphpcommand)
			{
				if (preg_match('/'.$forbiddenphpcommand.'\s*\(/ms', $phpfullcodestring))
				{
					$error++;
					setEventMessages($langs->trans("DynamicPHPCodeContainsAForbiddenInstruction", $forbiddenphpcommand), null, 'errors');
					if ($action == 'updatesource') $action = 'editsource';
					if ($action == 'updatecontent') $action = 'editcontent';
				}
			}

			if (empty($user->rights->website->writephp))
			{
				if ($phpfullcodestringold != $phpfullcodestring)
				{
					$error++;
					setEventMessages($langs->trans("NotAllowedToAddDynamicContent"), null, 'errors');
					if ($action == 'updatesource') $action = 'editsource';
					if ($action == 'updatecontent') $action = 'editcontent';
				}
			}

			// Clean data. We remove all the head section.
			$objectpage->content = preg_replace('/<head>.*<\/head>/ims', '', $objectpage->content);
			/* $objectpage->content = preg_replace('/<base\s+href=[\'"][^\'"]+[\'"]\s/?>/s', '', $objectpage->content); */


			$res = $objectpage->update($user);
			if ($res < 0)
			{
				$error++;
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
				if ($action == 'updatesource') $action = 'editsource';
				if ($action == 'updatecontent') $action = 'editcontent';
			}

			if (!$error)
			{
				$db->commit();

				$filemaster = $pathofwebsite.'/master.inc.php';
				//$fileoldalias=$pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
				$filealias = $pathofwebsite.'/'.$objectpage->pageurl.'.php';

				dol_mkdir($pathofwebsite);


				// Now generate the master.inc.php page
				$result = dolSaveMasterFile($filemaster);

				if (!$result) setEventMessages('Failed to write the master file file '.$filemaster, null, 'errors');


				// Now generate the alias.php page
				if (!empty($fileoldalias))
				{
					dol_syslog("We regenerate alias page new name=".$filealias.", old name=".$fileoldalias);
					dol_delete_file($fileoldalias);
				}

				// Save page alias
				$result = dolSavePageAlias($filealias, $object, $objectpage);
				if (!$result) setEventMessages('Failed to write the alias file '.basename($filealias), null, 'errors');

				// Save page content
				$result = dolSavePageContent($filetpl, $object, $objectpage);
				if ($result)
				{
					setEventMessages($langs->trans("Saved"), null, 'mesgs');

					if (!GETPOSTISSET('updateandstay'))	// If we click on "Save And Stay", we do not make the redirect
					{
						if ($backtopage) {
							header("Location: ".$backtopage);
							exit;
						} else {
							header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
							exit;
						}
					} else {
						if ($action == 'updatesource') $action = 'editsource';
						if ($action == 'updatecontent') $action = 'editcontent';
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
		if (!$error)
		{
			if (empty($websitekey) || $websitekey == '-1')
			{
				setEventMessages($langs->trans("NoWebSiteCreateOneFirst"), null, 'warnings');
			} else {
				setEventMessages($langs->trans("NoPageYet"), null, 'warnings');
				setEventMessages($langs->trans("YouCanCreatePageOrImportTemplate"), null, 'warnings');
			}
		}
	}
}

// Export site
if ($action == 'exportsite')
{
	$fileofzip = $object->exportWebSite();

	if ($fileofzip)
	{
		$file_name = basename($fileofzip);

		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=".$file_name);
		header("Content-Length: ".filesize($fileofzip));

		readfile($fileofzip);
		exit;
	}
	else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'preview';
	}
}

// Regenerate site
if ($action == 'regeneratesite')
{
	$result = $object->rebuildWebSiteFiles();
	if ($result > 0)
	{
		setEventMessages($langs->trans("PagesRegenerated", $result), null, 'mesgs');
		$action = 'preview';
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'preview';
	}
}

// Import site
if ($action == 'importsiteconfirm')
{
	if (empty($_FILES) && !GETPOSTISSET('templateuserfile'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
		$action = 'importsite';
	}
	else {
		if (!empty($_FILES) || GETPOSTISSET('templateuserfile'))
		{
			// Check symlink to medias and restore it if ko
			$pathtomedias = DOL_DATA_ROOT.'/medias';
			$pathtomediasinwebsite = $pathofwebsite.'/medias';
			if (!is_link(dol_osencode($pathtomediasinwebsite)))
			{
				dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
				dol_mkdir(dirname($pathtomediasinwebsite)); // To be sure dir for website exists
				$result = symlink($pathtomedias, $pathtomediasinwebsite);
				if (!$result)
				{
					setEventMessages($langs->trans("ErrorFieldToCreateSymLinkToMedias", $pathtomediasinwebsite, $pathtomedias), null, 'errors');
					$action = 'importsite';
				}
			}

			$fileofzip = '';
			if (GETPOSTISSET('templateuserfile'))
			{
				$fileofzip = DOL_DATA_ROOT.'/doctemplates/websites/'.GETPOST('templateuserfile', 'alpha');
			}
			elseif (!empty($_FILES))
			{
				if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
				else $userfiles = array($_FILES['userfile']['tmp_name']);

				foreach ($userfiles as $key => $userfile)
				{
					if (empty($_FILES['userfile']['tmp_name'][$key]))
					{
						$error++;
						if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
							setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
							$action = 'importsite';
						}
						else {
							setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
							$action = 'importsite';
						}
					}
				}

				if (!$error)
				{
					$upload_dir = $conf->website->dir_temp;
					$result = dol_add_file_process($upload_dir, 1, -1, 'userfile', '');
				}

				// Get name of file (take last one if several name provided)
				$fileofzip = $upload_dir.'/unknown';
				foreach ($_FILES as $key => $ifile)
				{
					foreach ($ifile['name'] as $key2 => $ifile2)
					{
						$fileofzip = $upload_dir.'/'.$ifile2;
					}
				}
			}

			if (!$error)
			{
				$result = $object->importWebSite($fileofzip);
				if ($result < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'importsite';
				}
				else {
					// Force mode dynamic on
					dolibarr_set_const($db, 'WEBSITE_SUBCONTAINERSINLINE', 1, 'chaine', 0, '', $conf->entity);

					header("Location: ".$_SERVER["PHP_SELF"].'?website='.$object->ref);
					exit();
				}
			}
		}
	}
}




/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);
$formwebsite = new FormWebsite($db);
$formother = new FormOther($db);

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
if (empty($conf->global->MAIN_ECM_DISABLE_JS)) $arrayofjs[] = "includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js";

$moreheadjs .= '<script type="text/javascript">'."\n";
$moreheadjs .= 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
$moreheadjs .= '</script>'."\n";

llxHeader($moreheadcss.$moreheadjs, $langs->trans("WebsiteSetup"), $helpurl, '', 0, 0, $arrayofjs, $arrayofcss, '', '', '<!-- Begin div class="fiche" -->'."\n".'<div class="fichebutwithotherclass">');

print "\n";
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

if ($action == 'createsite')
{
	print '<input type="hidden" name="action" value="addsite">';
}
if ($action == 'createcontainer')
{
	print '<input type="hidden" name="action" value="addcontainer">';
}
if ($action == 'editcss')
{
	print '<input type="hidden" name="action" value="updatecss">';
}
if ($action == 'editmenu')
{
	print '<input type="hidden" name="action" value="updatemenu">';
}
if ($action == 'setashome')
{
	print '<input type="hidden" name="action" value="updateashome">';
}
if ($action == 'editmeta')
{
	print '<input type="hidden" name="action" value="updatemeta">';
}
if ($action == 'editsource')
{
	print '<input type="hidden" name="action" value="updatesource">';
}
if ($action == 'editcontent')
{
	print '<input type="hidden" name="action" value="updatecontent">';
}
if ($action == 'edit')
{
	print '<input type="hidden" name="action" value="update">';
}
if ($action == 'importsite')
{
	print '<input type="hidden" name="action" value="importsiteconfirm">';
}
if ($action == 'file_manager')
{
	print '<input type="hidden" name="action" value="file_manager">';
}
if ($action == 'replacesite')
{
	print '<input type="hidden" name="action" value="replacesiteconfirm">';
}
if ($action == 'replacesiteconfirm')
{
	print '<input type="hidden" name="action" value="replacesiteconfirm">';
}

print '<div>';

// Add a margin under toolbar ?
$style = '';
if ($action != 'preview' && $action != 'editcontent' && $action != 'editsource' && !GETPOST('createpagefromclone', 'alphanohtml')) $style = ' margin-bottom: 5px;';


if (!GETPOST('hide_websitemenu'))
{
	$disabled = '';
	if (empty($user->rights->website->write)) $disabled = ' disabled="disabled"';

	if ($websitekey)
	{
		$virtualurl = '';
		$dataroot = DOL_DATA_ROOT.'/website/'.$websitekey;
		if (!empty($object->virtualhost)) $virtualurl = $object->virtualhost;
	}

	$array = array();
	if ($object->id > 0)
	{
		$array = $objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
		$object->lines = $array;
	}
	if (!is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
	$atleastonepage = (is_array($array) && count($array) > 0);


	//var_dump($objectpage);exit;
	print '<div class="centpercent websitebar'.(GETPOST('dol_openinpopup', 'int') ? ' hidden' : '').'">';

	//
	// Toolbar for websites
	//

	print '<!-- Bar for website -->';
	if ($action != 'file_manager') {
		print '<span class="websiteselection hideonsmartphoneimp minwidth100 tdoverflowmax100">';
		print $langs->trans("Website").' : ';
		print '</span>';

		$urltocreatenewwebsite = $_SERVER["PHP_SEFL"].'?action=createsite';
		if (empty($conf->use_javascript_ajax)) {
			print '<span class="websiteselection hideonsmartphoneimp">';
			print '<a href="'.$urltocreatenewwebsite.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("AddWebsite")).'"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"><span></a>';
			print '</span>';
		}

		// List of website
		print '<span class="websiteselection">';
		$out = '';
		$out .= '<select name="website" class="minwidth100 width200 maxwidth150onsmartphone" id="website">';
		if (empty($object->records)) $out .= '<option value="-1">&nbsp;</option>';
		if (!empty($conf->use_javascript_ajax)) {
			$valueoption = '<span class="classlink">'.img_picto('', 'add', 'class="paddingrightonly"').$langs->trans("AddWebsite").'</span>';
			$out .= '<option value="-2" data-html="'.dol_escape_htmltag($valueoption).'">'.$valueoption.'</option>';
		}
		// Loop on each sites
		$i = 0;
		foreach ($object->records as $key => $valwebsite)
		{
			if (empty($websitekey)) {
				if ($action != 'createsite') $websitekey = $valwebsite->ref;
			}

			$out .= '<option value="'.$valwebsite->ref.'"';
			if ($websitekey == $valwebsite->ref) $out .= ' selected'; // To preselect a value
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
			$out .= '<script language="javascript">';
			$out .= 'jQuery(document).ready(function () {';
			$out .= '	jQuery("#website").change(function () {';
			$out .= '   	console.log("We select "+jQuery("#website option:selected").val());';
			$out .= '   	if (jQuery("#website option:selected").val() == \'-2\') {';
			$out .= '  			window.location.href = "'.$urltocreatenewwebsite.'";';
			$out .= '		} else {';
			$out .= '  			window.location.href = "'.$_SERVER["PHP_SEFL"].'?website="+jQuery("#website option:selected").val();';
			$out .= '       }';
			$out .= '   });';
			$out .= '});';
			$out .= '</script>';
		}
		print $out;

		if (empty($conf->use_javascript_ajax)) {
			print '<input type="image" class="valignmiddle" src="'.img_picto('', 'refresh', '', 0, 1).'" name="refreshsite" value="'.$langs->trans("Load").'">';
		}

		if ($websitekey && $websitekey != '-1' && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite'))
		{
			print ' &nbsp; ';

			print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditCss")).'" name="editcss">';

			$importlabel = $langs->trans("ImportSite");
			$exportlabel = $langs->trans("ExportSite");
			if (!empty($conf->dol_optimize_smallscreen)) {
				$importlabel = $langs->trans("Import");
				$exportlabel = $langs->trans("Export");
			}

			if ($atleastonepage)
			{
				print '<input type="submit" class="button bordertransp" disabled="disabled" value="'.dol_escape_htmltag($importlabel).'" name="importsite">';
			}
			else {
				print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($importlabel).'" name="importsite">';
			}

			//print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditMenu")).'" name="editmenu">';
			print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($exportlabel).'" name="exportsite">';
			print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("CloneSite")).'" name="createfromclone">';

			print '<input type="submit" class="buttonDelete bordertransp" name="deletesite" value="'.$langs->trans("Delete").'"'.($atleastonepage ? ' disabled="disabled"' : '').'>';

			print ' &nbsp; ';

			print '<a href="'.$_SERVER["PHP_SEFL"].'?action=replacesite&website='.$website->ref.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("ReplaceWebsiteContent")).'"><span class="fa fa-search"><span></a>';

			print '<a href="'.$_SERVER["PHP_SEFL"].'?action=regeneratesite&website='.$website->ref.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("RegenerateWebsiteContent")).'"><span class="fa fa-cogs"><span></a>';
		}

		print '</span>';

		if ($websitekey && $websitekey != '-1' && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite'))
		{
			print '<span class="websiteselection">';
			//print '<a href="'.$_SERVER["PHP_SELF"].'?action=file_manager&website='.$website->ref.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("MediaFiles")).'"><span class="fa fa-image"><span></a>';
			print dolButtonToOpenUrlInDialogPopup('file_manager', $langs->transnoentitiesnoconv("MediaFiles"), '<span class="fa fa-image"><span>', '/website/index.php?action=file_manager&website='.$website->ref, $disabled);

			if (!empty($conf->categorie->enabled)) {
				//print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=website&dol_hide_leftmenu=1&nosearch=1&type=website_page&website='.$website->ref.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("Categories")).'"><span class="fa fa-tags"><span></a>';
				print dolButtonToOpenUrlInDialogPopup('categories', $langs->transnoentitiesnoconv("Categories"), '<span class="fa fa-tags"><span>', '/categories/index.php?leftmenu=website&nosearch=1&type=website_page&website='.$website->ref, $disabled);
			}

			print '</span>';
		}
	} else {
		print '<input type="hidden" name="website" id="website" value="'.$websitekey.'">';
	}


	print '<span class="websitetools websiteselection">';

	if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite')
	{
		$urlext = $virtualurl;
		$urlint = $urlwithroot.'/public/website/index.php?website='.$websitekey;

		print '<span class="websiteinputurl valignmiddle" id="websiteinputurl">';
		$linktotestonwebserver = '<a href="'.($virtualurl ? $virtualurl : '#').'" class="valignmiddle">';
		$linktotestonwebserver .= '<span class="hideonsmartphone paddingrightonly">'.$langs->trans("TestDeployOnWeb", $virtualurl).'</span>'.img_picto('', 'globe');
		$linktotestonwebserver .= '</a>';
		$htmltext = '';
		if (empty($object->fk_default_home))
		{
			$htmltext .= '<br><span class="error">'.$langs->trans("YouMustDefineTheHomePage").'</span><br><br>';
		}
		elseif (empty($virtualurl))
		{
			//$htmltext .= '<br><span class="error">'.$langs->trans("VirtualHostUrlNotDefined").'</span><br><br>';
		}
		else {
			$htmltext .= '<br><center>'.$langs->trans("GoTo").' <a href="'.$virtualurl.'" target="_website">'.$virtualurl.'</a></center><br>';
		}
		if (!empty($conf->global->WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER))
		{
			$htmltext .= '<br>'.$langs->trans($conf->global->WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER);
		}
		else {
			$htmltext .= $langs->trans("SetHereVirtualHost", $dataroot);
			$htmltext .= '<br>';
			$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
			$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), '{s1}');
			$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias', $htmltext);

			$examplewithapache = '#php_admin_value open_basedir /tmp/:'.DOL_DOCUMENT_ROOT.':'.DOL_DATA_ROOT.':/dev/urandom'."\n";
			$examplewithapache .= '<Directory "'.DOL_DOCUMENT_ROOT.'">'."\n";
			$examplewithapache .= 'AllowOverride FileInfo Options
    		Options       -Indexes -MultiViews -FollowSymLinks -ExecCGI
    		Require all granted
    		</Directory>
    		<Directory "'.DOL_DATA_ROOT.'/website">
    		AllowOverride FileInfo Options
    		Options       -Indexes -MultiViews +FollowSymLinks -ExecCGI
    		Require all granted
    		</Directory>
    		<Directory "'.DOL_DATA_ROOT.'/medias">
    		AllowOverride FileInfo Options
    		Options       -Indexes -MultiViews -FollowSymLinks -ExecCGI
    		Require all granted
    		</Directory>';

			$htmltext .= '<br>'.$langs->trans("ExampleToUseInApacheVirtualHostConfig").':<br>';
			$htmltext .= '<div class="centpercent exampleapachesetup">'.dol_nl2br(dol_escape_htmltag($examplewithapache, 1, 1)).'</div>';

			$htmltext .= '<br>';
			$htmltext .= $langs->trans("YouCanAlsoTestWithPHPS", $dataroot);
			$htmltext .= '<br>';
			$htmltext .= '<br>';
			$htmltext .= $langs->trans("YouCanAlsoDeployToAnotherWHP");
		}
		print $form->textwithpicto($linktotestonwebserver, $htmltext, 1, 'none', 'valignmiddle', 0, 3, 'helpvirtualhost');
		print '</span>';
	}

	if (in_array($action, array('editcss', 'editmenu', 'file_manager', 'replacesite', 'replacesiteconfirm')))
	{
		if ($action == 'editcss') print '<input type="submit" id="savefilean stay" class="button buttonforacesave" value="'.dol_escape_htmltag($langs->trans("SaveAndStay")).'" name="updateandstay">';
		if (preg_match('/^create/', $action) && $action != 'file_manager' && $action != 'replacesite' && $action != 'replacesiteconfirm') print '<input type="submit" id="savefile" class="button buttonforacesave button-save" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		if (preg_match('/^edit/', $action) && $action != 'file_manager' && $action != 'replacesite' && $action != 'replacesiteconfirm') print '<input type="submit" id="savefile" class="button buttonforacesave button-save" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		if ($action != 'preview') print '<input type="submit" class="button button-cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="cancel">';
	}

	print '</span>';

	//
	// Toolbar for pages
	//

	if ($websitekey && $websitekey != '-1' && !in_array($action, array('editcss', 'editmenu', 'importsite', 'file_manager', 'replacesite', 'replacesiteconfirm')) && !$file_manager)
	{
		print '</div>'; // Close current websitebar to open a new one

		print '<!-- Bar for websitepage -->';
		print '<div class="centpercent websitebar"'.($style ? ' style="'.$style.'"' : '').'">';

		print '<span class="websiteselection hideonsmartphoneimp minwidth100 tdoverflowmax100">';
		print $langs->trans("PageContainer").': ';
		print '</span>';

		print '<span class="websiteselection hideonsmartphoneimp">';
		print '<a href="'.$_SERVER["PHP_SEFL"].'?action=createcontainer&website='.$website->ref.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("AddPage")).'"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"><span></a>';
		print '</span>';

		//print '<span class="websiteselection">';

		if ($action != 'addcontainer')
		{
			$out = '';

			$s = $formwebsite->selectContainer($website, 'pageid', $pageid, 0, $action, 'maxwidth200onsmartphone');

			if ($formwebsite->num > 0) {
				$out .= '<span class="websiteselection">';
				$out .= $s;
				$out .= '</span>';

				$urltocreatenewpage = $_SERVER["PHP_SEFL"].'?action=createcontainer&website='.$website->ref;

				if (!empty($conf->use_javascript_ajax)) {
					$out .= '<script language="javascript">';
					$out .= 'jQuery(document).ready(function () {';
					$out .= '	jQuery("#pageid").change(function () {';
					$out .= '   	console.log("We select "+jQuery("#pageid option:selected").val());';
					$out .= '   	if (jQuery("#pgeid option:selected").val() == \'-2\') {';
					$out .= '  			window.location.href = "'.$urltocreatenewpage.'";';
					$out .= '		} else {';
					$out .= '  			window.location.href = "'.$_SERVER["PHP_SEFL"].'?website='.$website->ref.'&pageid="+jQuery("#pageid option:selected").val();';
					$out .= '       }';
					$out .= '   });';
					$out .= '});';
					$out .= '</script>';
				}
			}

			print $out;
		}
		else {
			print $langs->trans("New");
		}

		//print '<input type="submit" class="button" name="refreshpage" value="'.$langs->trans("Load").'"'.($atleastonepage?'':' disabled="disabled"').'>';
		print '<input type="image" class="valignmiddle" src="'.img_picto('', 'refresh', '', 0, 1).'" name="refreshpage" value="'.$langs->trans("Load").'"'.(($atleastonepage && $action != 'editsource') ? '' : ' disabled="disabled"').'>';


		// Print nav arrows
		$pagepreviousid = 0;
		$pagenextid = 0;
		if ($pageid)
		{
			$sql = 'SELECT MAX(rowid) as pagepreviousid FROM '.MAIN_DB_PREFIX.'website_page WHERE rowid < '.$pageid.' AND fk_website = '.$object->id;
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$pagepreviousid = $obj->pagepreviousid;
				}
			}
			else dol_print_error($db);
			$sql = 'SELECT MIN(rowid) as pagenextid FROM '.MAIN_DB_PREFIX.'website_page WHERE rowid > '.$pageid.' AND fk_website = '.$object->id;
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$pagenextid = $obj->pagenextid;
				}
			}
			else dol_print_error($db);
		}

		if ($pagepreviousid) print '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?website='.urlencode($object->ref).'&pageid='.$pagepreviousid.'&action='.$action.'">'.img_previous($langs->trans("PreviousContainer")).'</a>';
		else print '<span class="valignmiddle opacitymedium">'.img_previous($langs->trans("Previous")).'</span>';
		if ($pagenextid) print '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?website='.urlencode($object->ref).'&pageid='.$pagenextid.'&action='.$action.'">'.img_next($langs->trans("NextContainer")).'</a>';
		else print '<span class="valignmiddle opacitymedium">'.img_next($langs->trans("Next")).'</span>';

		$websitepage = new WebSitePage($db);
		if ($pageid > 0 && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone'))
		{
			$websitepage->fetch($pageid);
		}

		if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite')
		{
			$disabled = '';
			if (empty($user->rights->website->write)) $disabled = ' disabled="disabled"';

			// Confirmation delete site
			if ($action == 'deletesite') {
				// Create an array for form
				$formquestion = array(
					array('type' => 'checkbox', 'name' => 'delete_also_js', 'label' => $langs->trans("DeleteAlsoJs"), 'value' => 0),
					array('type' => 'checkbox', 'name' => 'delete_also_medias', 'label' => $langs->trans("DeleteAlsoMedias"), 'value' => 0),
					//array('type' => 'other','name' => 'newlang','label' => $langs->trans("Language"), 'value' => $formadmin->select_language(GETPOST('newlang', 'aZ09')?GETPOST('newlang', 'aZ09'):$langs->defaultlang, 'newlang', 0, null, '', 0, 0, 'minwidth200')),
					//array('type' => 'other','name' => 'newwebsite','label' => $langs->trans("WebSite"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0))
				);

				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteWebsite'), '', 'confirm_deletesite', $formquestion, 0, 1, 200);

				print $formconfirm;
			}

			// Confirmation to clone
			if ($action == 'createfromclone') {
				// Create an array for form
				$formquestion = array(
					array('type' => 'text', 'name' => 'siteref', 'label'=> $langs->trans("WebSite"), 'value'=> 'copy_of_'.$object->ref)
				);

				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CloneSite'), '', 'confirm_createfromclone', $formquestion, 0, 1, 200);

				print $formconfirm;
			}

			if ($pageid > 0 && $atleastonepage)		// pageid can be set without pages, if homepage of site is set and all pages were removed
			{
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
							$onlylang[$langkey] = $langkey;
						}
						$textifempty = $langs->trans("Default");
					} else {
						$onlylang['none'] = 'none';
						$textifempty = $langs->trans("Default");
					}
					$formquestion = array(
						array('type' => 'hidden', 'name' => 'sourcepageurl', 'value'=> $objectpage->pageurl),
						array('type' => 'other', 'tdclass'=>'fieldrequired', 'name' => 'newwebsite', 'label' => $langs->trans("WebSite"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0)),
						array('type' => 'text', 'tdclass'=>'maxwidth200 fieldrequired', 'moreattr'=>'autofocus="autofocus"', 'name' => 'newtitle', 'label'=> $langs->trans("WEBSITE_TITLE"), 'value'=> $langs->trans("CopyOf").' '.$objectpage->title),
						array('type' => 'text', 'tdclass'=>'maxwidth200', 'name' => 'newpageurl', 'label'=> $langs->trans("WEBSITE_PAGENAME"), 'value'=> '')
						);
					if (count($onlylang) > 1) {
						$formquestion[] = array('type' => 'checkbox', 'tdclass'=>'maxwidth200', 'name' => 'is_a_translation', 'label' => $langs->trans("PageIsANewTranslation"), 'value' => 0, 'morecss'=>'margintoponly');
					}

					$value= $formadmin->select_language($preselectedlanguage, 'newlang', 0, null, $textifempty, 0, 0, 'minwidth200', 1, 0, 0, $onlylang, 1);
					$formquestion[] = array('type' => 'other', 'name' => 'newlang', 'label' => $form->textwithpicto($langs->trans("Language"), $langs->trans("DefineListOfAltLanguagesInWebsiteProperties")), 'value' => $value);

				   	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$pageid, $langs->trans('ClonePage'), '', 'confirm_createpagefromclone', $formquestion, 0, 1, 300, 550);

					print $formconfirm;
				}

				print ' &nbsp; ';

				print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditPageMeta")).'" name="editmeta">';

				print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditHTMLSource")).'" name="editsource">';

				print '<!-- button EditInLine and ShowSubcontainers -->'."\n";
				print '<div class="websiteselectionsection inline-block">';
				print '<div class="inline-block marginrightonly">';

				print '<span id="switchckeditorinline">'."\n";
				print '<!-- Code to enabled edit inline ckeditor -->'."\n";
				print '<script type="text/javascript">
						$(document).ready(function() {
							var isEditingEnabled = '.($conf->global->WEBSITE_EDITINLINE ? 'true' : 'false').';
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
									console.log("Enable inline edit");
									jQuery(\'section[contenteditable="true"],div[contenteditable="true"]\').each(function(idx){
										var idtouse = $(this).attr(\'id\');
										console.log("Enable inline edit for "+idtouse);
										CKEDITOR.inline(idtouse, {
											// Allow some non-standard markup that we used in the introduction.
											extraAllowedContent: \'span(*);cite(*);q(*);dl(*);dt(*);dd(*);ul(*);li(*);header(*);button(*);h1(*);h2(*);\',
											//extraPlugins: \'sourcedialog\',
											removePlugins: \'flash,stylescombo\',
											// Show toolbar on startup (optional).
											// startupFocus: true
										});
									})

									isEditingEnabled = true;
								}
								else {
									console.log("Disable inline edit");
									for(name in CKEDITOR.instances)
									{
									    CKEDITOR.instances[name].destroy(true);
									}
									isEditingEnabled = false;
								}
							};
						});
						</script>';
				print $langs->trans("EditInLine");
				print '</span>';

				//$disableeditinline = $websitepage->grabbed_from;
				$disableeditinline = 0;
				if ($disableeditinline)
				{
					//print '<input type="submit" class="button bordertransp" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("OnlyEditionOfSourceForGrabbedContent")).'" value="'.dol_escape_htmltag($langs->trans("EditWithEditor")).'" name="editcontent">';
					print '<a class="nobordertransp opacitymedium nohoverborder marginleftonlyshort"'.$disabled.' href="#" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("OnlyEditionOfSourceForGrabbedContent")).'">'.img_picto($langs->trans("OnlyEditionOfSourceForGrabbedContent"), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
				}
				else {
					//print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditWithEditor")).'" name="editcontent">';
					if (empty($conf->global->WEBSITE_EDITINLINE))
					{
						print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=seteditinline&token='.newToken().'">'.img_picto($langs->trans("EditInLineOnOff", $langs->transnoentitiesnoconv("Off")), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
					}
					else {
						print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=unseteditinline&token='.newToken().'">'.img_picto($langs->trans("EditInLineOnOff", $langs->transnoentitiesnoconv("On")), 'switch_on', '', false, 0, 0, '', 'nomarginleft').'</a>';
					}
				}

				print '</div>';
				print '<div class="inline-block marginrightonly">';
				print $langs->trans("ShowSubcontainers");
				if (empty($conf->global->WEBSITE_SUBCONTAINERSINLINE))
				{
					print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=setshowsubcontainers&token='.newToken().'">'.img_picto($langs->trans("ShowSubContainersOnOff", $langs->transnoentitiesnoconv("Off")), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
				}
				else {
					print '<a class="nobordertransp nohoverborder marginleftonlyshort valignmiddle"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=unsetshowsubcontainers&token='.newToken().'">'.img_picto($langs->trans("ShowSubContainersOnOff", $langs->transnoentitiesnoconv("On")), 'switch_on', '', false, 0, 0, '', 'nomarginleft').'</a>';
				}
				/*}*/
				print '</div>';
				print '</div>';

				// Set page as homepage
				if ($object->fk_default_home > 0 && $pageid == $object->fk_default_home)
				{
					//$disabled=' disabled="disabled"';
					//print '<span class="button bordertransp disabled"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'"><span class="fa fa-home"></span></span>';
					//print '<input type="submit" class="button bordertransp" disabled="disabled" value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
					print '<a href="#" class="button bordertransp disabled" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'"><span class="fa fa-home valignmiddle btnTitle-icon"><span></a>';
				}
				else {
					//$disabled='';
					//print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
					print '<a href="'.$_SERVER["PHP_SEFL"].'?action=setashome&token='.newToken().'&website='.$website->ref.'&pageid='.$pageid.'" class="button bordertransp"'.$disabled.' title="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'"><span class="fa fa-home valignmiddle btnTitle-icon"><span></a>';
				}
				print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("ClonePage")).'" name="createpagefromclone">';
				print '<input type="submit" class="buttonDelete bordertransp" name="delete" value="'.$langs->trans("Delete").'"'.($atleastonepage ? '' : ' disabled="disabled"').'>';
			}
		}

		//print '</span>';	// end website selection

		print '<span class="websitetools">';

		if (($pageid > 0 && $atleastonepage) && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone' || $action == 'deletesite'))
		{
			$realpage = $urlwithroot.'/public/website/index.php?website='.$websitekey.'&pageref='.$websitepage->pageurl;
			$pagealias = $websitepage->pageurl;

			$htmltext = $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage, $dataroot);
			$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
			$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), '{s1}');
			$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias', $htmltext);

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
		if (!in_array($action, array('editcss', 'editmenu', 'file_manager', 'replacesite', 'replacesiteconfirm', 'createsite', 'createcontainer', 'createfromclone', 'createpagefromclone', 'deletesite')))
		{
			if ($action == 'editsource' || $action == 'editmeta') print '<input type="submit" id="savefilean stay" class="button buttonforacesave" value="'.dol_escape_htmltag($langs->trans("SaveAndStay")).'" name="updateandstay">';
			if (preg_match('/^create/', $action)) print '<input type="submit" id="savefile" class="button buttonforacesave button-save" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
			if (preg_match('/^edit/', $action)) print '<input type="submit" id="savefile" class="button buttonforacesave button-save" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
			if ($action != 'preview') print '<input type="submit" class="button button-cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="cancel">';
		}

		print '</span>'; // end websitetools

		print '<span class="websitehelp">';
		if (GETPOST('editsource', 'alpha') || GETPOST('editcontent', 'alpha'))
		{
			$url = 'https://wiki.dolibarr.org/index.php/Module_Website';

			$htmltext = $langs->transnoentitiesnoconv("YouCanEditHtmlSource", $url);
			$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSource2", $url);
			$htmltext .= $langs->transnoentitiesnoconv("YouCanEditHtmlSourceMore", $url);
			$htmltext .= '<br>';
			if ($conf->browser->layout == 'phone')
			{
				print $form->textwithpicto('', $htmltext, 1, 'help', 'inline-block', 1, 2, 'tooltipsubstitution');
			}
			else {
				//img_help(($tooltiptrigger != '' ? 2 : 1), $alt)
				print $form->textwithpicto($langs->trans("SyntaxHelp").' '.img_help(2, $langs->trans("SyntaxHelp")), $htmltext, 1, 'none', 'inline-block', 1, 2, 'tooltipsubstitution');
			}
		}
		print '</span>'; // end websitehelp


		if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
		{
			// Adding jquery code to change on the fly url of preview ext
			if (!empty($conf->use_javascript_ajax))
			{
				print '<script type="text/javascript" language="javascript">
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
                                    fk_element: '.$object->id.',
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

if ($action == 'editcss')
{
	print '<div class="fiche">';

	print '<br>';

	if (!GETPOSTISSET('WEBSITE_CSS_INLINE'))
	{
		$csscontent = @file_get_contents($filecss);
		// Clean the php css file to remove php code and get only css part
		$csscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $csscontent);
	}
	else {
		$csscontent = GETPOST('WEBSITE_CSS_INLINE', 'none');
	}
	if (!trim($csscontent)) {
		$csscontent = '/* CSS content (all pages) */'."\nbody.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }\n.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}";
	}

	if (!GETPOSTISSET('WEBSITE_JS_INLINE'))
	{
		$jscontent = @file_get_contents($filejs);
		// Clean the php js file to remove php code and get only js part
		$jscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $jscontent);
	}
	else {
		$jscontent = GETPOST('WEBSITE_JS_INLINE', 'none');
	}
	if (!trim($jscontent)) $jscontent = '/* JS content (all pages) */'."\n";

	if (!GETPOSTISSET('WEBSITE_HTML_HEADER'))
	{
		$htmlheadercontent = @file_get_contents($filehtmlheader);
		// Clean the php htmlheader file to remove php code and get only html part
		$htmlheadercontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $htmlheadercontent);
	}
	else {
		$htmlheadercontent = GETPOST('WEBSITE_HTML_HEADER', 'none');
	}
	if (!trim($htmlheadercontent))
	{
		$htmlheadercontent = "<html>\n";
		$htmlheadercontent .= $htmlheadercontentdefault;
		$htmlheadercontent .= "</html>";
	}
	else {
		$htmlheadercontent = preg_replace('/^\s*<html>/ims', '', $htmlheadercontent);
		$htmlheadercontent = preg_replace('/<\/html>\s*$/ims', '', $htmlheadercontent);
		$htmlheadercontent = '<html>'."\n".trim($htmlheadercontent)."\n".'</html>';
	}

	if (!GETPOSTISSET('WEBSITE_ROBOT'))
	{
		$robotcontent = @file_get_contents($filerobot);
		// Clean the php htmlheader file to remove php code and get only html part
		$robotcontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $robotcontent);
	}
	else {
		$robotcontent = GETPOST('WEBSITE_ROBOT', 'nothtml');
	}
	if (!trim($robotcontent))
	{
		$robotcontent .= "# Robot file. Generated with ".DOL_APPLICATION_TITLE."\n";
		$robotcontent .= "User-agent: *\n";
		$robotcontent .= "Allow: /public/\n";
		$robotcontent .= "Disallow: /administrator/\n";
	}

	if (!GETPOSTISSET('WEBSITE_HTACCESS'))
	{
		$htaccesscontent = @file_get_contents($filehtaccess);
		// Clean the php htaccesscontent file to remove php code and get only html part
		$htaccesscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $htaccesscontent);
	}
	else {
		$htaccesscontent = GETPOST('WEBSITE_HTACCESS', 'nohtml');
	}
	if (!trim($htaccesscontent))
	{
		$htaccesscontent .= "# Order allow,deny\n";
		$htaccesscontent .= "# Deny from all\n";
	}


	if (!GETPOSTISSET('WEBSITE_MANIFEST_JSON'))
	{
		$manifestjsoncontent = @file_get_contents($filemanifestjson);
		// Clean the manifestjson file to remove php code and get only html part
		$manifestjsoncontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $manifestjsoncontent);
	}
	else {
		$manifestjsoncontent = GETPOST('WEBSITE_MANIFEST_JSON', 'restricthtml');
	}
	if (!trim($manifestjsoncontent))
	{
		//$manifestjsoncontent.="";
	}

	if (!GETPOSTISSET('WEBSITE_README'))
	{
		$readmecontent = @file_get_contents($filereadme);
		// Clean the readme file to remove php code and get only html part
		$readmecontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $readmecontent);
	}
	else {
		$readmecontent = GETPOST('WEBSITE_README', 'none');
	}
	if (!trim($readmecontent))
	{
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
	print '<!-- Status of website -->'."\n";
	print '<tr><td class="fieldrequired">';
	print $langs->trans('Status');
	print '</td><td>';
	print ajax_object_onoff($object, 'status', 'status', 'Enabled', 'Disabled');
	//print dol_print_date($pagedatecreation, 'dayhour');
	print '</td></tr>';

	// Main language
	print '<tr><td class="tdtop fieldrequired">';
	$htmltext = '';
	print $form->textwithpicto($langs->trans('MainLanguage'), $htmltext, 1, 'help', '', 0, 2, 'WEBSITE_LANG');
	print '</td><td>';
	print $formadmin->select_language((GETPOSTISSET('WEBSITE_LANG') ? GETPOST('WEBSITE_LANG', 'aZ09comma') : ($object->lang ? $object->lang : '0')), 'WEBSITE_LANG', 0, null, 1, 0, 0, 'minwidth300', 2, 0, 0, array(), 1);
	print '</td>';
	print '</tr>';

	// Other languages
	print '<tr><td class="tdtop">';
	$htmltext = $langs->trans("Example").': fr,de,sv,it,pt';
	print $form->textwithpicto($langs->trans('OtherLanguages'), $htmltext, 1, 'help', '', 0, 2);
	print '</td><td>';
	print '<input type="text" class="flat" value="'.(GETPOSTISSET('WEBSITE_OTHERLANG') ? GETPOST('WEBSITE_OTHERLANG', 'alpha') : $object->otherlang).'" name="WEBSITE_OTHERLANG">';
	print '</td>';
	print '</tr>';

	// VirtualHost
	print '<tr><td class="tdtop">';

	$htmltext = $langs->trans("SetHereVirtualHost", DOL_DATA_ROOT.'/website/{s1}'.$websitekey.'{s2}');
	$htmltext = str_replace(array('{s1}', '{s2}'), array('<i>', '</i>'), $htmltext);
	$htmltext .= '<br>';
	$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
	$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), '{s1}');
	$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias', $htmltext);

	print $form->textwithpicto($langs->trans('Virtualhost'), $htmltext, 1, 'help', '', 0, 2, 'virtualhosttooltip');
	print '</td><td>';
	print '<input type="text" class="flat" value="'.(GETPOSTISSET('virtualhost') ? GETPOST('virtualhost', 'alpha') : $virtualurl).'" name="virtualhost">';
	print '</td>';
	print '</tr>';

	// CSS file
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans("CSSContentTooltipHelp");
	print $form->textwithpicto($langs->trans('WEBSITE_CSS_INLINE'), $htmlhelp, 1, 'help', '', 0, 2, 'csstooltip');
	print '</td><td>';

	$doleditor = new DolEditor('WEBSITE_CSS_INLINE', $csscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'CSS', 'css');

	print '</td></tr>';

	// JS file
	print '<tr><td class="tdtop">';
	$textwithhelp = $langs->trans('WEBSITE_JS_INLINE');
	$htmlhelp2 = $langs->trans("LinkAndScriptsHereAreNotLoadedInEditor").'<br>';
	print $form->textwithpicto($textwithhelp, $htmlhelp2, 1, 'warning', '', 0, 2, 'htmljstooltip2');

	print '</td><td>';

	$doleditor = new DolEditor('WEBSITE_JS_INLINE', $jscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'JS', 'javascript');

	print '</td></tr>';

	// Common HTML header
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_HTML_HEADER');
	$htmlhelp = $langs->trans("Example").' :<br>';
	$htmlhelp .= dol_htmlentitiesbr($htmlheadercontentdefault);
	$textwithhelp = $form->textwithpicto('', $htmlhelp, 1, 'help', '', 0, 2, 'htmlheadertooltip');
	$htmlhelp2 = $langs->trans("LinkAndScriptsHereAreNotLoadedInEditor").'<br>';
	print $form->textwithpicto($textwithhelp, $htmlhelp2, 1, 'warning', '', 0, 2, 'htmlheadertooltip2');
	print '</td><td>';

	$doleditor = new DolEditor('WEBSITE_HTML_HEADER', $htmlheadercontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'HTML Header', 'html');

	print '</td></tr>';

	// Robot file
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_ROBOT');
	print '</td><td>';

	$doleditor = new DolEditor('WEBSITE_ROBOT', $robotcontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'Robot file', 'text');

	print '</td></tr>';

	// .htaccess
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_HTACCESS');
	print '</td><td>';

	$doleditor = new DolEditor('WEBSITE_HTACCESS', $htaccesscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, $langs->trans("File").' .htaccess', 'text');

	print '</td></tr>';

	// Manifest.json
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans("Example").' :<br>';
	$htmlhelp .= dol_htmlentitiesbr($manifestjsoncontentdefault);
	print $form->textwithpicto($langs->trans('WEBSITE_MANIFEST_JSON'), $htmlhelp, 1, 'help', '', 0, 2, 'manifestjsontooltip');
	print '</td><td>';
	print $langs->trans("UseManifest").': '.$form->selectyesno('use_manifest', $website->use_manifest, 1).'<br>';
	$doleditor = new DolEditor('WEBSITE_MANIFEST_JSON', $manifestjsoncontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, $langs->trans("File").' manifest.json', 'text');
	print '</td></tr>';

	// README.md
	print '<tr><td class="tdtop">';
	$htmlhelp = $langs->trans("EnterHereLicenseInformation");
	print $form->textwithpicto($langs->trans('WEBSITE_README'), $htmlhelp, 1, 'help', '', 0, 2, 'readmetooltip');
	print '</td><td>';

	$doleditor = new DolEditor('WEBSITE_README', $readmecontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, $langs->trans("File").' README.md', 'text');

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


if ($action == 'createsite')
{
	print '<div class="fiche">';

	print '<br>';

	/*$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/website/index.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AddSite");
   	$head[$h][2] = 'card';
	$h++;

    print dol_get_fiche_head($head, 'card', $langs->trans("AddSite"), -1, 'globe');
    */
	if ($action == 'createcontainer') print load_fiche_titre($langs->trans("AddSite"));

	print '<!-- Add site -->'."\n";
	//print '<div class="fichecenter">';

	print '<table class="border centpercent">';

	$siteref = $sitedesc = $sitelang = $siteotherlang = '';
	if (GETPOST('WEBSITE_REF'))         $siteref = GETPOST('WEBSITE_REF', 'alpha');
	if (GETPOST('WEBSITE_DESCRIPTION')) $sitedesc = GETPOST('WEBSITE_DESCRIPTION', 'alpha');
	if (GETPOST('WEBSITE_LANG'))        $sitelang = GETPOST('WEBSITE_LANG', 'aZ09');
	if (GETPOST('WEBSITE_OTHERLANG'))   $siteotherlang = GETPOST('WEBSITE_OTHERLANG', 'aZ09comma');

	print '<tr><td class="titlefieldcreate fieldrequired">';
	print $form->textwithpicto($langs->trans('WebSite'), $langs->trans("Example").': www.mywebsite.com, myportal, ...');
	print '</td><td>';
	print '<input type="text" class="flat maxwidth300" name="WEBSITE_REF" value="'.dol_escape_htmltag($siteref).'" autofocus>';
	print '</td></tr>';

	print '<tr><td class="fieldrequired">';
	print $langs->trans('MainLanguage');
	print '</td><td>';
	$shortlangcode = preg_replace('/[_-].*$/', '', trim($langs->defaultlang));
	print $formadmin->select_language((GETPOSTISSET('WEBSITE_LANG') ? GETPOST('WEBSITE_LANG', 'aZ09comma') : $shortlangcode), 'WEBSITE_LANG', 0, null, 1, 0, 0, 'minwidth300', 2, 0, 0, array(), 1);
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('Description');
	print '</td><td>';
	print '<input type="text" class="flat minwidth500" name="WEBSITE_DESCRIPTION" value="'.dol_escape_htmltag($sitedesc).'">';
	print '</td></tr>';

	print '<tr><td>';
	$htmltext = $langs->trans("Example").': fr,de,sv,it,pt';
	print $form->textwithpicto($langs->trans('OtherLanguages'), $htmltext, 1, 'help', '', 0, 2);
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_OTHERLANG" value="'.dol_escape_htmltag($siteotherlang).'">';
	print '</td></tr>';

	print '<tr><td>';

	$htmltext = $langs->trans("SetHereVirtualHost", '{s1}');
	$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/website/<i>websiteref</i>', $htmltext);
	$htmltext .= '<br>';
	$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
	$htmltext .= '<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), '{s1}');
	$htmltext = str_replace('{s1}', DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias', $htmltext);

	print $form->textwithpicto($langs->trans('Virtualhost'), $htmltext, 1, 'help', '', 0, 2, 'virtualhosttooltip');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="virtualhost" value="'.dol_escape_htmltag(GETPOST('virtualhost', 'alpha')).'">';
	print '</td></tr>';


	print '</table>';

	if ($action == 'createsite')
	{
		print '<div class="center">';

		print '<input class="button" type="submit" name="addcontainer" value="'.$langs->trans("Create").'">';
		print '<input class="button button-cancel" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

		print '</div>';
	}


	//print '</div>';

	//print dol_get_fiche_end();

	print '</div>';

	print '<br>';
}

if ($action == 'importsite')
{
	print '<div class="fiche">';

	print '<br>';

	print load_fiche_titre($langs->trans("ImportSite"));

	print dol_get_fiche_head(array(), '0', '', -1);

	print '<span class="opacitymedium">'.$langs->trans("ZipOfWebsitePackageToImport").'</span><br><br>';

	print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';
	print '<input class="flat minwidth400" type="file" name="userfile[]" accept=".zip">';
	print '<input type="submit" class="button" name="buttonsubmitimportfile" value="'.dol_escape_htmltag($langs->trans("Upload")).'">';
	print '<input type="submit" class="button button-cancel" name="preview" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';

	print '<br><br><br>';


	print '<span class="opacitymedium">'.$langs->trans("ZipOfWebsitePackageToLoad").'</span><br><br>';

	showWebsiteTemplates($website);

	print dol_get_fiche_end();

	print '</div>';

	print '<br>';
}

if ($action == 'editmeta' || $action == 'createcontainer')	// Edit properties of a web site OR properties of a web page
{
	print '<div class="fiche">';

	print '<br>';

	/*$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/website/index.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AddPage");
   	$head[$h][2] = 'card';
	$h++;

    print dol_get_fiche_head($head, 'card', $langs->trans("AddPage"), -1, 'globe');
    */
	if ($action == 'createcontainer') print load_fiche_titre($langs->trans("AddPage"));

	print '<!-- Edit or create page/container -->'."\n";
	//print '<div class="fichecenter">';

	$hiddenfromfetchingafterload = ' hideobject';
	$hiddenmanuallyafterload = ' hideobject';
	if (GETPOST('radiocreatefrom') == 'checkboxcreatefromfetching') $hiddenfromfetchingafterload = '';
	if (GETPOST('radiocreatefrom') == 'checkboxcreatemanually') $hiddenmanuallyafterload = '';

	if ($action == 'editmeta' || empty($conf->use_javascript_ajax)) {	// No autohide/show in such case
		$hiddenfromfetchingafterload = '';
		$hiddenmanuallyafterload = '';
	}

	if ($action == 'createcontainer')
	{
		print '<br>';

		if (!empty($conf->use_javascript_ajax)) print '<input type="radio" name="radiocreatefrom" id="checkboxcreatefromfetching" value="checkboxcreatefromfetching"'.(GETPOST('radiocreatefrom') == 'checkboxcreatefromfetching' ? ' checked' : '').'> ';
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
		$arraygrabimagesinto = array('root'=>$langs->trans("WebsiteRootOfImages"), 'subpage'=>$langs->trans("SubdirOfPage"));
		print $form->selectarray('grabimagesinto', $arraygrabimagesinto, GETPOSTISSET('grabimagesinto') ? GETPOST('grabimagesinto') : 'root', 0, 0, 0, '', 0, 0, 0, '', '', 1);
		print '<br>';
		print '<input class="button" style="margin-top: 5px" type="submit" name="fetchexternalurl" value="'.dol_escape_htmltag($langs->trans("FetchAndCreate")).'">';
		print '</td></tr>';
		print '</table>';

		print '<br>';

		if (!empty($conf->use_javascript_ajax)) print '<input type="radio" name="radiocreatefrom" id="checkboxcreatemanually" value="checkboxcreatemanually"'.(GETPOST('radiocreatefrom') == 'checkboxcreatemanually' ? ' checked' : '').'> ';
		print '<label for="checkboxcreatemanually"><span class="opacitymediumxx">'.$langs->trans("OrEnterPageInfoManually").'</span></label><br>';
		print '<hr class="tablecheckboxcreatemanually'.$hiddenmanuallyafterload.'">';
	}

	print '<table class="border tableforfield nobackground centpercent tablecheckboxcreatemanually'.$hiddenmanuallyafterload.'">';

	if ($action != 'createcontainer')
	{
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
	}
	else {
		$type_container = 'page';
		$pagedatecreation = dol_now();
		$pageauthorid = $user->id;
		$pageusermodifid = 0;
		$pageauthoralias = '';
		$pagestatus = 1;
	}
	if (GETPOST('WEBSITE_TITLE', 'alpha'))       $pagetitle = str_replace(array('<', '>'), '', GETPOST('WEBSITE_TITLE', 'alphanohtml'));
	if (GETPOST('WEBSITE_PAGENAME', 'alpha'))    $pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
	if (GETPOST('WEBSITE_ALIASALT', 'alpha'))    $pagealiasalt = str_replace(array('<', '>'), '', GETPOST('WEBSITE_ALIASALT', 'alphanohtml'));
	if (GETPOST('WEBSITE_DESCRIPTION', 'alpha')) $pagedescription = str_replace(array('<', '>'), '', GETPOST('WEBSITE_DESCRIPTION', 'alphanohtml'));
	if (GETPOST('WEBSITE_IMAGE', 'alpha'))       $pageimage = GETPOST('WEBSITE_IMAGE', 'alpha');
	if (GETPOST('WEBSITE_KEYWORDS', 'alpha'))    $pagekeywords = str_replace(array('<', '>'), '', GETPOST('WEBSITE_KEYWORDS', 'alphanohtml'));
	if (GETPOST('WEBSITE_LANG', 'aZ09'))         $pagelang = GETPOST('WEBSITE_LANG', 'aZ09');
	if (GETPOST('WEBSITE_ALLOWED_IN_FRAMES', 'aZ09')) $pageallowedinframes = GETPOST('WEBSITE_ALLOWED_IN_FRAMES', 'aZ09');
	if (GETPOST('htmlheader', 'none'))			 $pagehtmlheader = GETPOST('htmlheader', 'none');

	if ($action != 'createcontainer')
	{
		print '<!-- Status of page -->'."\n";
		print '<tr><td class="fieldrequired">';
		print $langs->trans('Status');
		print '</td><td>';
		print ajax_object_onoff($objectpage, 'status', 'status', 'Enabled', 'Disabled');
		//print dol_print_date($pagedatecreation, 'dayhour');
		print '</td></tr>';
	}

	// Type of container
	print '<tr><td class="titlefield fieldrequired">';
	print $langs->trans('WEBSITE_TYPE_CONTAINER');
	print '</td><td>';
	print img_picto('', 'object_technic', 'class="paddingrightonly"').' ';
	$formwebsite->selectTypeOfContainer('WEBSITE_TYPE_CONTAINER', (GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha') ? GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha') : $type_container), 0, '', 1);
	print '</td></tr>';

	if ($action == 'createcontainer')
	{
		print '<tr><td class="titlefield fieldrequired">';
		print $langs->trans('WEBSITE_PAGE_EXAMPLE');
		print '</td><td>';
		print $formwebsite->selectSampleOfContainer('sample', (GETPOSTISSET('sample') ? GETPOST('sample', 'alpha') : 'empty'), 0, '', 1);
		print '</td></tr>';
	}

	// Title
	print '<tr><td class="fieldrequired">';
	print $langs->trans('WEBSITE_TITLE');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_TITLE" id="WEBSITE_TITLE" value="'.dol_escape_htmltag($pagetitle).'" autofocus>';
	print '</td></tr>';

	// Alias
	print '<tr><td class="titlefieldcreate fieldrequired">';
	print $langs->trans('WEBSITE_PAGENAME');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_PAGENAME" id="WEBSITE_PAGENAME" value="'.dol_escape_htmltag($pageurl).'">';
	print '</td></tr>';

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

	print '<tr><td>';
	$htmlhelp = $langs->trans("WEBSITE_IMAGEDesc");
	print $form->textwithpicto($langs->trans('WEBSITE_IMAGE'), $htmlhelp, 1, 'help', '', 0, 2, 'imagetooltip');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_IMAGE" value="'.dol_escape_htmltag($pageimage).'">';
	print '</td></tr>';

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
	if ($object->lang) $onlykeys[$object->lang] = $object->lang;
	else $onlykeys[$langs->defaultlang] = $langs->defaultlang;
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
	print $formadmin->select_language($pagelang ? $pagelang : '', 'WEBSITE_LANG', 0, null, '1', 0, 0, 'minwidth200', 0, 0, 0, $onlykeys, 1);
	$htmltext = $langs->trans("AvailableLanguagesAreDefinedIntoWebsiteProperties");
	print $form->textwithpicto('', $htmltext);
	print '</td></tr>';

	// Translation of
	$translationof = 0;
	$translatedby = 0;
	print '<!-- Translation of --><tr><td>';
	print $langs->trans('TranslationLinks');
	print '</td><td>';
	if ($action != 'createcontainer')
	{
		// Has translation pages
		$sql = 'SELECT rowid, lang from '.MAIN_DB_PREFIX.'website_page where fk_page = '.$objectpage->id;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num_rows = $db->num_rows($resql);
			if ($num_rows > 0)
			{
				print '<span class="opacitymedium">'.$langs->trans('ThisPageHasTranslationPages').':</span>';
				$i = 0;
				$tmppage = new WebsitePage($db);
				$tmpstring = '';
				while ($obj = $db->fetch_object($resql))
				{
					$result = $tmppage->fetch($obj->rowid);
					if ($result > 0) {
						if ($i > 0) $tmpstring .= '<br>';
						$tmpstring .= $tmppage->getNomUrl(1).' ('.$tmppage->lang.')';
						$translatedby++;
						$i++;
					}
				}
				if ($i > 1) print '<br>';
				else print ' ';
				print $tmpstring;
			}
		}
		else dol_print_error($db);
	}
	if (empty($translatedby) && ($action == 'editmeta' || $action == 'createcontainer' || $objectpage->fk_page > 0))
	{
		$sourcepage = new WebsitePage($db);
		$result = $sourcepage->fetch($objectpage->fk_page);
		if ($result == 0)
		{
			// not found, we can reset value to clean database
		}
		elseif ($result > 0)
		{
			$translationof = $objectpage->fk_page;
			print '<span class="opacitymedium">'.$langs->trans('ThisPageIsTranslationOf').'</span> ';
			print $formwebsite->selectContainer($website, 'pageidfortranslation', ($translationof ? $translationof : -1), 1, $action, 'minwidth300', array($objectpage->id));
			if ($translationof > 0 && $sourcepage->lang) {
				print $sourcepage->getNomUrl(2).' ('.$sourcepage->lang.')';
			}
		}
	}
	print '</td></tr>';

	// Allowed in frames
	print '<tr><td>';
	print $langs->trans('AllowedInFrames');
	//$htmlhelp = $langs->trans("AllowedInFramesDesc");
	//print $form->textwithpicto($langs->trans('AllowedInFrames'), $htmlhelp, 1, 'help', '', 0, 2, 'allowedinframestooltip');
	print '</td><td>';
	print '<input type="checkbox" class="flat" name="WEBSITE_ALLOWED_IN_FRAMES" value="1"'.($pageallowedinframes ? 'checked="checked"' : '').'>';
	print '</td></tr>';

	// Categories
	if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire))
	{
		$langs->load('categories');

		if (!GETPOSTISSET('categories')) {
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, '', null, null, null, 1);
			$c = new Categorie($db);
			$cats = $c->containing($objectpage->id, Categorie::TYPE_WEBSITE_PAGE);
			$arrayselected = array();
			if (is_array($cats)) {
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
			}

			$cate_arbo = $form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, '', 'parent', null, null, 1);
		}

		print '<tr><td class="toptd">'.$form->editfieldkey('Categories', 'categories', '', $objectpage, 0).'</td><td>';
		print img_picto('', 'category', 'class="paddingright"');
		print $form->multiselectarray('categories', $cate_arbo, (GETPOSTISSET('categories') ? GETPOST('categories', 'array') : $arrayselected), null, null, 'minwidth200 widthcentpercentminusxx');
		print "</td></tr>";
	}

	if (!empty($conf->global->WEBSITE_PAGE_SHOW_INTERNAL_LINKS_TO_OBJECT)) {	// TODO Replace this with link into element_element ?
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

	print '<tr><td>';
	print $langs->trans('Author');
	print '</td><td>';
	if ($pageauthorid > 0)
	{
		$fuser->fetch($pageauthorid);
		print $fuser->getNomUrl(1);
	} else {
		print '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
	}
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('PublicAuthorAlias');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_AUTHORALIAS" value="'.dol_escape_htmltag($pageauthoralias).'">';
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('DateCreation');
	print '</td><td>';
	print $form->selectDate($pagedatecreation, 'datecreation', 1, 1, 0, '', 1, 1);
	//print dol_print_date($pagedatecreation, 'dayhour');
	print '</td></tr>';

	if ($action != 'createcontainer')
	{
		print '<tr><td>';
		print $langs->trans('UserModif');
		print '</td><td>';
		if ($pageusermodifid > 0)
		{
			$fuser->fetch($pageusermodifid);
			print $fuser->getNomUrl(1);
		}
		print '</td></tr>';

		print '<tr><td>';
		print $langs->trans('DateModification');
		print '</td><td>';
		print dol_print_date($pagedatemodification, 'dayhour', 'tzuser');
		print '</td></tr>';
	}

	print '<tr><td class="tdhtmlheader tdtop">';
	$htmlhelp = $langs->trans("EditTheWebSiteForACommonHeader").'<br><br>';
	$htmlhelp .= $langs->trans("Example").' :<br>';
	$htmlhelp .= dol_htmlentitiesbr($htmlheadercontentdefault);
	print $form->textwithpicto($langs->trans('HtmlHeaderPage'), $htmlhelp, 1, 'help', '', 0, 2, 'htmlheadertooltip');
	print '</td><td>';
	$doleditor = new DolEditor('htmlheader', $pagehtmlheader, '', '120', 'ace', 'In', true, false, 'ace', ROWS_3, '100%', '');
	print $doleditor->Create(1, '', true, 'HTML Header', 'html');
	print '</td></tr>';

	print '</table>';
	if ($action == 'createcontainer')
	{
		print '<div class="center tablecheckboxcreatemanually'.$hiddenmanuallyafterload.'">';

		print '<input class="button" type="submit" name="addcontainer" value="'.$langs->trans("Create").'">';
		print '<input class="button button-cancel" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

		print '</div>';
	}

	if ($action == 'createcontainer')
	{
		print '<script type="text/javascript" language="javascript">
			jQuery(document).ready(function() {
				var disableautofillofalias = 0;
				jQuery("#WEBSITE_TITLE").keyup(function() {
					if (disableautofillofalias == 0)
					{
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
					disableautofillofalias = 1;
				});

				jQuery("#checkboxcreatefromfetching,#checkboxcreatemanually").change(function() {
					console.log("we select a method to create a new container"+jQuery("#checkboxcreatefromfetching:checked").val())
					if (typeof(jQuery("#checkboxcreatefromfetching:checked").val()) != \'undefined\')
					{
						jQuery(".tablecheckboxcreatefromfetching").show();
						jQuery(".tablecheckboxcreatemanually").hide();
					}
					if (typeof(jQuery("#checkboxcreatemanually:checked").val()) != \'undefined\')
					{
						jQuery(".tablecheckboxcreatefromfetching").hide();
						jQuery(".tablecheckboxcreatemanually").show();
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

if ($action == 'editfile' || $action == 'file_manager')
{
	print '<!-- Edit Media -->'."\n";
	print '<div class="fiche"><br>';
	//print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';

	$module = 'medias';
	if (empty($url)) $url = DOL_URL_ROOT.'/website/index.php'; // Must be an url without param
	include DOL_DOCUMENT_ROOT.'/core/tpl/filemanager.tpl.php';

	print '</div>';
}

if ($action == 'editmenu')
{
	print '<!-- Edit Menu -->'."\n";
	print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';
}

if ($action == 'editsource')
{
	// Editing with source editor

	$contentforedit = '';
	//$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
	//$contentforedit.=$csscontent;
	//$contentforedit.='</style>'."\n";
	$contentforedit .= $objectpage->content;
	//var_dump($_SESSION["dol_screenheight"]);
	$maxheightwin = 480;
	if (isset($_SESSION["dol_screenheight"]))
	{
		if ($_SESSION["dol_screenheight"] > 680) $maxheightwin = $_SESSION["dol_screenheight"] - 400;
		if ($_SESSION["dol_screenheight"] > 800) $maxheightwin = $_SESSION["dol_screenheight"] - 490;
	}
	//var_dump($_SESSION["dol_screenheight"]);
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('PAGE_CONTENT', $contentforedit, '', $maxheightwin, 'Full', '', true, true, 'ace', ROWS_5, '40%');
	$doleditor->Create(0, '', false, 'HTML Source', 'php');
}

/*if ($action == 'editcontent')
{
	// Editing with default ckeditor

	$contentforedit = '';
	//$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
    //$contentforedit.=$csscontent;
    //$contentforedit.='</style>'."\n";
	$contentforedit .= $objectpage->content;

	$contentforedit = preg_replace('/(<img.*src=")(?!http)/', '\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $contentforedit, -1, $nbrep);

	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('PAGE_CONTENT',$contentforedit,'',500,'Full','',true,true,true,ROWS_5,'90%');
	$doleditor->Create(0, '', false);
}*/

print "</div>\n";
print "</form>\n";


if ($action == 'replacesite' || $action == 'replacesiteconfirm' || $massaction == 'replace')
{
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="replacesiteconfirm">';
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
	print '<input type="checkbox" class="marginleftonly" name="optioncontent" value="content"'.((!GETPOSTISSET('buttonreplacesitesearch') || GETPOST('optioncontent', 'aZ09')) ? ' checked' : '').'> '.$langs->trans("Content").'<br>';
	print '<input type="checkbox" class="marginleftonly" name="optionmeta" value="meta"'.(GETPOST('optionmeta', 'aZ09') ? ' checked' : '').'> '.$langs->trans("Title").' | '.$langs->trans("Description").' | '.$langs->trans("Keywords").'<br>';
	print '<input type="checkbox" class="marginleftonly" name="optionsitefiles" value="sitefiles"'.(GETPOST('optionsitefiles', 'aZ09') ? ' checked' : '').'> '.$langs->trans("GlobalCSSorJS").'<br>';
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
	print '<div class="tagtd paddingrightonly opacitymedium" style="padding-right: 10px !important">';
	print $langs->trans("WEBSITE_TYPE_CONTAINER");
	print '</div>';
	print '<div class="tagtd">';
	print img_picto('', 'object_technic', 'class="paddingrightonly"').' ';
	$formwebsite->selectTypeOfContainer('optioncontainertype', (GETPOST('optioncontainertype', 'alpha') ? GETPOST('optioncontainertype', 'alpha') : ''), 1);
	print '</div>';
	print '</div>';

	print '<div class="tagtr">';
	print '<div class="tagtd paddingrightonly opacitymedium" style="padding-right: 10px !important">';
	print $langs->trans("Language");
	print '</div>';
	print '<div class="tagtd">';
	print img_picto('', 'language', 'class="paddingrightonly"').' '.$formadmin->select_language(GETPOSTISSET('optionlanguage') ? GETPOST('optionlanguage') : '', 'optionlanguage', 0, null, '1', 0, 0, 'minwidth300', 2, 0, 0, null, 1);
	print '</div>';
	print '</div>';

	// Categories
	if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire))
	{
		print '<div class="tagtr">';
		print '<div class="tagtd paddingrightonly marginrightonly opacitymedium" style="padding-right: 10px !important">';
		print $langs->trans("Category");
		print '</div>';
		print '<div class="tagtd">';
		print img_picto('', 'category', 'class="paddingrightonly"').' '.$form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, GETPOSTISSET('optioncategory') ? GETPOST('optioncategory') : '', 'optioncategory', null, null);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
		print ajax_combobox('optioncategory');
		print '</div>';
		print '</div>';
	}

	print '</div>';

	print '<input type="submit" class="button" name="buttonreplacesitesearch" value="'.dol_escape_htmltag($langs->trans("Search")).'">';

	print '</div></div>';

	if ($action == 'replacesiteconfirm')
	{
		print '<!-- List of search result -->'."\n";
		print '<div class="rowsearchresult clearboth">';

		print '<br>';
		print '<br>';

		if ($listofpages['code'] == 'OK')
		{
			$arrayofselected = is_array($toselect) ? $toselect : array();
			$param = '';
			$nbtotalofrecords = count($listofpages['list']);
			$num = $limit;
			$permissiontodelete = $user->rights->website->delete;

			// List of mass actions available
			$arrayofmassactions = array();
			if ($user->rights->website->writephp && $searchkey) $arrayofmassactions['replace'] = $langs->trans("Replace");
			if ($user->rights->website->write) $arrayofmassactions['setcategory'] = $langs->trans("ClassifyInCategory");
			if ($permissiontodelete) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
			if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();

			$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
			$massactionbutton .= '<div class="massactionother massactionreplace hidden">';
			$massactionbutton .= $langs->trans("ReplaceString");
			$massactionbutton .= ' <input type="text" name="replacestring" value="'.dol_escape_htmltag(GETPOST('replacestring', 'none')).'">';
			$massactionbutton .= '</div>';
			$massactionbutton .= '<div class="massactionother massactionsetcategory hidden">';
			$massactionbutton .= $langs->trans("Category");
			$massactionbutton .= ' '.$form->select_all_categories(Categorie::TYPE_WEBSITE_PAGE, GETPOSTISSET('setcategory') ? GETPOST('setcategory') : '', 'setcategory', 64, 0, 0, 0, 'minwidth300 alignstart');
			include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
			$massactionbutton .= ajax_combobox('setcategory');
			$massactionbutton .= '</div>';

			$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
			//$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
			$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

			print_barre_liste($langs->trans("Results"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, '', '', $limit, 1, 1, 1);

			$topicmail = "WebsitePageRef";
			$modelmail = "websitepage_send";
			$objecttmp = new WebsitePage($db);
			$trackid = 'wsp'.$object->id;
			include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

			$param = 'action=replacesiteconfirm&website='.urlencode($website->ref);
			$param .= '&searchstring='.urlencode($searchkey);
			if (GETPOST('optioncontent')) $param .= '&optioncontent=content';
			if (GETPOST('optionmeta')) $param .= '&optionmeta=meta';
			if (GETPOST('optionsitefiles')) $param .= '&optionsitefiles=optionsitefiles';
			if (GETPOST('optioncontainertype')) $param .= '&optioncontainertype='.GETPOST('optioncontainertype', 'aZ09');
			if (GETPOST('optionlanguage')) $param .= '&optionlanguage='.GETPOST('optionlanguage', 'aZ09');
			if (GETPOST('optioncategory')) $param .= '&optioncategory='.GETPOST('optioncategory', 'aZ09');

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print getTitleFieldOfList("Type", 0, $_SERVER['PHP_SELF'], 'type_container', '', $param, '', $sortfield, $sortorder, '')."\n";
			print getTitleFieldOfList("Page", 0, $_SERVER['PHP_SELF'], 'pageurl', '', $param, '', $sortfield, $sortorder, '')."\n";
			print getTitleFieldOfList("Categories", 0, $_SERVER['PHP_SELF']);
			print getTitleFieldOfList("", 0, $_SERVER['PHP_SELF']);
			print getTitleFieldOfList("", 0, $_SERVER['PHP_SELF']);
			print getTitleFieldOfList("", 0, $_SERVER['PHP_SELF']);
			print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
			print '</tr>';

			require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$c = new Categorie($db);

			$totalnbwords = 0;

			foreach ($listofpages['list'] as $answerrecord)
			{
				if (get_class($answerrecord) == 'WebsitePage')
				{
					print '<tr>';

					// Type of container
					print '<td class="nowraponall">'.$langs->trans("Container").' - ';
					print $langs->trans($answerrecord->type_container); // TODO Use label of container
					print '</td>';

					// Container url and label
					print '<td>';
					print $answerrecord->getNomUrl(1);
					print ' <span class="opacitymedium">('.($answerrecord->title ? $answerrecord->title : $langs->trans("NoTitle")).')</span>';
					//print '</td>';
					//print '<td class="tdoverflow100">';
					print '<br>';
					print '<span class="opacitymedium">'.$answerrecord->description.'</span>';
					print '</td>';

					// Categories - Tags
					print '<td>';
					if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire))
					{
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


					$param = '?action=replacesiteconfirm';
					$param .= '&websiteid='.$website->id;
					$param .= '&optioncontent='.GETPOST('optioncontent', 'aZ09');
					$param .= '&optionmeta='.GETPOST('optionmeta', 'aZ09');
					$param .= '&optionsitefiles='.GETPOST('optionsitefiles', 'aZ09');
					$param .= '&optioncontainertype='.GETPOST('optioncontainertype', 'aZ09');
					$param .= '&optionlanguage='.GETPOST('optionlanguage', 'aZ09');
					$param .= '&optioncategory='.GETPOST('optioncategory', 'aZ09');
					$param .= '&searchstring='.urlencode($searchkey);

					// Language
					print '<td>';
					print $answerrecord->lang;
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


					// Edit properties, HTML sources, status
					print '<td class="tdwebsitesearchresult right nowraponall">';
					$disabled = '';
					$urltoedithtmlsource = $_SERVER["PHP_SELF"].'?action=editmeta&websiteid='.$website->id.'&pageid='.$answerrecord->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].$param);
					if (empty($user->rights->website->write)) {
						$disabled = ' disabled';
						$urltoedithtmlsource = '';
					}
					print '<a class="editfielda marginleftonly marginrightonly '.$disabled.'" href="'.$urltoedithtmlsource.'" title="'.$langs->trans("EditPageMeta").'">'.img_picto($langs->trans("EditPageMeta"), 'pencil-ruler').'</a>';

					$disabled = '';
					$urltoedithtmlsource = $_SERVER["PHP_SELF"].'?action=editsource&websiteid='.$website->id.'&pageid='.$answerrecord->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].$param);
					if (empty($user->rights->website->write)) {
						$disabled = ' disabled';
						$urltoedithtmlsource = '';
					}
					print '<a class="editfielda  marginleftonly marginrightonly '.$disabled.'" href="'.$urltoedithtmlsource.'" title="'.$langs->trans("EditHTMLSource").'">'.img_picto($langs->trans("EditHTMLSource"), 'edit').'</a>';

					print '<span class="marginleftonly marginrightonly"></span>'.ajax_object_onoff($answerrecord, 'status', 'status', 'Enabled', 'Disabled');

					print '</td>';

					// Action column
					print '<td class="nowrap center">';

					print '<!-- Status of page -->'."\n";
					if ($massactionbutton || $massaction)
					{
						$selected = 0;
						if (in_array($answerrecord->id, $arrayofselected)) $selected = 1;
						print '<input id="'.$answerrecord->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$answerrecord->id.'"'.($selected ? ' checked="checked"' : '').'>';
					}
					print '</td>';

					print '</tr>';
				} else {
					print '<tr>';

					// Type of container
					print '<td>';
					$translateofrecordtype = array(
						'website_csscontent'=>'WEBSITE_CSS_INLINE',
						'website_jscontent'=>'WEBSITE_JS_INLINE',
						'website_robotcontent'=>'WEBSITE_ROBOT',
						'website_htmlheadercontent'=>'WEBSITE_HTML_HEADER',
						'website_htaccess'=>'WEBSITE_HTACCESS',
						'website_readme'=>'WEBSITE_README',
						'website_manifestjson'=>'WEBSITE_MANIFEST_JSON'
					);
					if (!empty($translateofrecordtype[$answerrecord['type']])) {
						print $langs->trans($translateofrecordtype[$answerrecord['type']]);
					} else {
						print $answerrecord['type'];
					}
					print '</td>';

					$param = '?action=replacesiteconfirm';
					$param .= '&websiteid='.$website->id;
					$param .= '&optioncontent='.GETPOST('optioncontent', 'aZ09');
					$param .= '&optionmeta='.GETPOST('optionmeta', 'aZ09');
					$param .= '&optionsitefiles='.GETPOST('optionsitefiles', 'aZ09');
					$param .= '&optioncontainertype='.GETPOST('optioncontainertype', 'aZ09');
					$param .= '&optionlanguage='.GETPOST('optionlanguage', 'aZ09');
					$param .= '&optioncategory='.GETPOST('optioncategory', 'aZ09');
					$param .= '&searchstring='.urlencode($searchkey);

					// Container url and label
					print '<td>';
					$backtopageurl = $_SERVER["PHP_SELF"].$param;
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=editcss&website='.$website->ref.'&backtopage='.urlencode($backtopageurl).'">'.$langs->trans("EditCss").'</a>';
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

					// Edit properties, HTML sources, status
					print '<td>';
					print '</td>';

					// Action column
					print '<td class="nowrap center">';
					print '</td>';

					print '</tr>';
				}
			}

			if (count($listofpages['list']) >= 2) {
				// Total
				print '<tr class="lite_titre">';

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

				// Edit properties, HTML sources, status
				print '<td>';
				print '</td>';

				// Action column
				print '<td class="nowrap center">';
				print '</td>';

				print '</tr>';
			}

			print '</table>';
			print '</div>';
			print '<br>';
		}
		else {
			print '<div class="warning">'.$listofpages['message'].'</div>';
		}

		print '</div>';
	}

	print '</form>';
}

if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
{
	if ($pageid > 0 && $atleastonepage)
	{
		// $filejs
		// $filecss
		// $filephp

		// Ouput page under the Dolibarr top menu
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
		$out .= '<style scoped>'."\n"; // "scoped" means "apply to parent element only". No more supported by browsers, snif !
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
		foreach ($tmp as $valtmp)
		{
			$i++;
			if ($i % 2 == 0) $tmpstyleinheader .= $valtmp."\n";
		}
		$tmpout .= $tmpstyleinheader."\n";
		// Clean style that may affect global style of Dolibarr
		$tmpout = preg_replace('/}[\s\n]*body\s*{[^}]+}/ims', '}', $tmpout);
		$out .= $tmpout;
		$out .= '</style>'."\n";

		// Note: <div> or <section> with contenteditable="true" inside this can be edited with inline ckeditor

		// Do not enable the contenteditable when page was grabbed, ckeditor is removing span and adding borders,
		// so editable will be available only from container created from scratch
		//$out.='<div id="bodywebsite" class="bodywebsite"'.($objectpage->grabbed_from ? ' contenteditable="true"' : '').'>'."\n";
		$out .= '<div id="divbodywebsite" class="bodywebsite bodywebpage-'.$objectpage->ref.'">'."\n";

		$newcontent = $objectpage->content;

		// If mode WEBSITE_SUBCONTAINERSINLINE is on
		if (!empty($conf->global->WEBSITE_SUBCONTAINERSINLINE))
		{
			// TODO Check file $filephp exists, if not create it.

			//var_dump($filetpl);
			$filephp = $filetpl;
			ob_start();
			try {
				$res = include $filephp;
				if (empty($res)) {
					print "ERROR: Failed to include file '".$filephp."'. Try to edit and save page.";
				}
			} catch (Exception $e)
			{
				print $e->getMessage();
			}
			$newcontent = ob_get_contents();
			ob_end_clean();
		}

		// Change the contenteditable to "true" or "false" when mode Edit Inline is on or off
		if (empty($conf->global->WEBSITE_EDITINLINE))
		{
			// Remove the contenteditable="true"
			$newcontent = preg_replace('/(div|section)(\s[^\>]*)contenteditable="true"/', '\1\2', $newcontent);
		}
		else {
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
        if (! empty($conf->global->MAIN_UMASK))
            @chmod($filetpl, octdec($conf->global->MAIN_UMASK));

        // Output file on browser
        dol_syslog("index.php include $filetpl $filename content-type=$type");
        $original_file_osencoded=dol_osencode($filetpl);	// New file name encoded in OS encoding charset

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
	}
	else {
		if (empty($websitekey) || $websitekey == '-1')
		{
			print '<br><br><div class="center previewnotyetavailable"><span class="">'.$langs->trans("NoWebSiteCreateOneFirst").'</span></div><br><br><br>';
			print '<div class="center"><div class="logo_setup"></div></div>';
		}
		else {
			print '<br><br><div class="center previewnotyetavailable"><span class="">'.$langs->trans("PreviewOfSiteNotYetAvailable", $object->ref).'</span></div><br><br><br>';
			print '<div class="center"><div class="logo_setup"></div></div>';
		}
	}
}

// End of page
llxFooter();
$db->close();
