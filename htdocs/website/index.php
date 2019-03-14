<?php
/* Copyright (C) 2016-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/website/index.php
 *		\ingroup    website
 *		\brief      Page to website view/edit
 */

define('NOSCANPOSTFORINJECTION', 1);
define('NOSTYLECHECK', 1);

header('X-XSS-Protection:0');

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formwebsite.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

$langs->loadLangs(array("admin","other","website","errors"));

if (! $user->rights->website->read) accessforbidden();

if (! ((GETPOST('testmenuhider', 'int') || ! empty($conf->global->MAIN_TESTMENUHIDER)) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)))
{
	$conf->dol_hide_leftmenu = 1;   // Force hide of left menu.
}

$error=0;
$websitekey=GETPOST('website', 'alpha');
$page=GETPOST('page', 'alpha');
$pageid=GETPOST('pageid', 'int');
$pageref=GETPOST('pageref', 'aZ09');
$action=GETPOST('action', 'aZ09');
$confirm=GETPOST('confirm', 'alpha');
$cancel=GETPOST('cancel', 'alpha');

$type_container=GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha');

$section_dir = GETPOST('section_dir', 'alpha');
$file_manager = GETPOST('file_manager', 'alpha');

if (GETPOST('deletesite', 'alpha')) { $action='deletesite'; }
if (GETPOST('delete', 'alpha')) { $action='delete'; }
if (GETPOST('preview', 'alpha')) $action='preview';
if (GETPOST('createsite', 'alpha')) { $action='createsite'; }
if (GETPOST('createcontainer', 'alpha')) { $action='createcontainer'; }
if (GETPOST('editcss', 'alpha')) { $action='editcss'; }
if (GETPOST('editmenu', 'alpha')) { $action='editmenu'; }
if (GETPOST('setashome', 'alpha')) { $action='setashome'; }
if (GETPOST('editmeta', 'alpha')) { $action='editmeta'; }
if (GETPOST('editsource', 'alpha')) { $action='editsource'; }
if (GETPOST('editcontent', 'alpha')) { $action='editcontent'; }
if (GETPOST('exportsite', 'alpha')) { $action='exportsite'; }
if (GETPOST('importsite', 'alpha')) { $action='importsite'; }
if (GETPOST('createfromclone', 'alpha')) { $action='createfromclone'; }
if (GETPOST('createpagefromclone', 'alpha')) { $action='createpagefromclone'; }
if (empty($action) && $file_manager) $action='file_manager';

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield='name';
//if (! $sortorder) $sortorder='ASC';

if (empty($action)) $action='preview';

$object=new Website($db);
$objectpage=new WebsitePage($db);

$object->fetchAll();    // Init $object->records with list of websites

// If website not defined, we take first found
if (empty($websitekey))
{
	foreach($object->records as $key => $valwebsite)
	{
		$websitekey=$valwebsite->ref;
		break;
	}
}
if ($websitekey)
{
	$res = $object->fetch(0, $websitekey);
}
$website = $object;

// Check pageid received as aprameter
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
				$res = $objectpage->fetch($object->fk_default_home, $object->id, '');	// We search first page of web site
				if ($res > 0) $pageid = $object->fk_default_home;
			}
			else
			{
				$res = $objectpage->fetch(0, $object->id, '');	// We search first page of web site
				if ($res == 0)	// Page was not found, we reset it
				{
					$objectpage=new WebsitePage($db);
				}
				else			// We found a page, we set pageid to it.
				{
					$pageid = $objectpage->id;
				}
			}
		}
		else	// We have a valid page. We force pageid for the case we got the page with a fetch on ref.
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
		$array=$objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
		if (! is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
		$atleastonepage=(is_array($array) && count($array) > 0);

		$firstpageid=0;$homepageid=0;
		foreach($array as $key => $valpage)
		{
			if (empty($firstpageid)) $firstpageid=$valpage->id;
			if ($object->fk_default_home && $key == $object->fk_default_home) $homepageid=$valpage->id;
		}
		$pageid=($homepageid?$homepageid:$firstpageid);   // We choose home page and if not defined yet, we take first page
	}
}


global $dolibarr_main_data_root;
$pathofwebsite=$dolibarr_main_data_root.'/website/'.$websitekey;
$filehtmlheader=$pathofwebsite.'/htmlheader.html';
$filecss=$pathofwebsite.'/styles.css.php';
$filejs=$pathofwebsite.'/javascript.js.php';
$filerobot=$pathofwebsite.'/robots.txt';
$filehtaccess=$pathofwebsite.'/.htaccess';
$filetpl=$pathofwebsite.'/page'.$pageid.'.tpl.php';
$fileindex=$pathofwebsite.'/index.php';
$filewrapper=$pathofwebsite.'/wrapper.php';

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


$permtouploadfile = $user->rights->website->write;
$diroutput = $conf->medias->multidir_output[$conf->entity];

$relativepath=$section_dir;
$upload_dir = $diroutput.'/'.$relativepath;

$htmlheadercontentdefault = '';
$htmlheadercontentdefault.='<link rel="stylesheet" id="google-fonts-css"  href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700" />'."\n";
$htmlheadercontentdefault.='<link rel="stylesheet" id="font-wasesome-css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />'."\n";
$htmlheadercontentdefault.='<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>'."\n";
$htmlheadercontentdefault.='<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>'."\n";
$htmlheadercontentdefault.='<script src="//cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"></script>'."\n";
$htmlheadercontentdefault.='<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.13.0/umd/popper.min.js"></script>'."\n";
$htmlheadercontentdefault.='<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta.2/js/bootstrap.min.js"></script>'."\n";
$htmlheadercontentdefault.='<!--'."\n";
$htmlheadercontentdefault.='<script src="/document.php?modulepart=medias&file=css/myfile.css"></script>'."\n";
$htmlheadercontentdefault.='<script src="/document.php?modulepart=medias&file=js/myfile.js"></script>'."\n";
$htmlheadercontentdefault.='-->'."\n";


/*
 * Actions
 */

$backtopage=$_SERVER["PHP_SELF"].'?file_manager=1&website='.$websitekey.'&pageid='.$pageid.(GETPOST('section_dir', 'alpha')?'&section_dir='.urlencode(GETPOST('section_dir', 'alpha')):'');	// used after a confirm_deletefile into actions_linkedfiles.inc.php
include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

if ($action == 'renamefile') $action='file_manager';		// After actions_linkedfiles, if action were renamefile, we set it to 'file_manager'

if ($action == 'seteditinline')
{
	dolibarr_set_const($db, 'WEBSITE_EDITINLINE', 1);
	setEventMessages($langs->trans("FeatureNotYetAvailable"), null, 'warnings');
	dolibarr_set_const($db, 'WEBSITE_SUBCONTAINERSINLINE', 0);	// Force disable of 'Include dynamic content'
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
	dolibarr_set_const($db, 'WEBSITE_EDITINLINE', 0);	// Force disable of edit inline
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.GETPOST('website', 'alphanohtml').'&pageid='.GETPOST('pageid', 'int'));
	exit;
}
if ($action == 'unsetshowsubcontainers')
{
	dolibarr_del_const($db, 'WEBSITE_SUBCONTAINERSINLINE');
	header("Location: ".$_SERVER["PHP_SELF"].'?website='.GETPOST('website', 'alphanohtml').'&pageid='.GETPOST('pageid', 'int'));
	exit;
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


if (GETPOST('refreshsite', 'alpha'))		// If we change the site, we reset the pageid and cancel addsite action.
{
	$pageid=0;
	if ($action == 'addsite') $action = 'preview';
}
if (GETPOST('refreshpage', 'alpha') && ! in_array($action, array('updatecss'))) $action='preview';


// Add site
if ($action == 'addsite')
{
	$db->begin();

    if (GETPOST('virtualhost', 'alpha') && ! preg_match('/^http/', GETPOST('virtualhost', 'alpha')))
    {
        $error++;
        setEventMessages($langs->trans('ErrorURLMustStartWithHttp', $langs->transnoentitiesnoconv("VirtualHost")), null, 'errors');
	}

	if (! $error && ! GETPOST('WEBSITE_REF', 'alpha'))
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
	}
	if (! $error && ! preg_match('/^[a-z0-9_\-\.]+$/i', GETPOST('WEBSITE_REF', 'alpha')))
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("Ref")), null, 'errors');
	}

	if (! $error)
	{
		$tmpobject=new Website($db);
		$tmpobject->ref = GETPOST('WEBSITE_REF', 'alpha');
		$tmpobject->description = GETPOST('WEBSITE_DESCRIPTION', 'alpha');
		$tmpobject->virtualhost = GETPOST('virtualhost', 'alpha');

		$result = $tmpobject->create($user);
		if ($result <= 0)
		{
			$error++;
			setEventMessages($tmpobject->error, $tmpobject->errors, 'errors');
		}
	}

	if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("SiteAdded", $object->ref), null, 'mesgs');
		$action='';

		header("Location: ".$_SERVER["PHP_SELF"].'?website='.$tmpobject->ref);
		exit;
	}
	else
	{
		$db->rollback();
		$action='createsite';
	}

	if (! $error)
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
	if (GETPOST('fetchexternalurl', 'alpha'))
	{
		$urltograb=GETPOST('externalurl', 'alpha');
		$grabimages=GETPOST('grabimages', 'alpha');
		$grabimagesinto=GETPOST('grabimagesinto', 'alpha');
		//var_dump($grabimages);exit;
	}

	if ($urltograb)
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		if (! preg_match('/^http/', $urltograb))
		{
			$error++;
			$langs->load("errors");
			setEventMessages('Error URL must start with http:// or https://', null, 'errors');
			$action = 'createcontainer';
		}

		if (! $error)
		{
			// Clean url to grab, so url can be
			// http://www.example.com/ or http://www.example.com/dir1/ or http://www.example.com/dir1/aaa
			$urltograbwithoutdomainandparam = preg_replace('/^https?:\/\/[^\/]+\/?/i', '', $urltograb);
			$urltograbwithoutdomainandparam = preg_replace('/\?.*$/', '', $urltograbwithoutdomainandparam);
			if (empty($urltograbwithoutdomainandparam) && ! preg_match('/\/$/', $urltograb))
			{
				$urltograb.='/';
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
				$action='createcontainer';
			}
		}

		if (! $error)
		{
			$tmp = getURLContent($urltograb);
			if ($tmp['curl_error_no'])
			{
				$error++;
				setEventMessages('Error getting '.$urltograb.': '.$tmp['curl_error_msg'], null, 'errors');
				$action='createcontainer';
			}
			elseif ($tmp['http_code'] != '200')
			{
				$error++;
				setEventMessages('Error getting '.$urltograb.': '.$tmp['http_code'], null, 'errors');
				$action='createcontainer';
			}
			else
			{
				// Remove comments
				$tmp['content'] = removeHtmlComment($tmp['content']);

				preg_match('/<head>(.*)<\/head>/ims', $tmp['content'], $reg);
				$head = $reg[1];

				$objectpage->type_container = 'page';
	   			$objectpage->pageurl = $pageurl;
	   			if (empty($objectpage->pageurl))
	   			{
	   				$tmpdomain = getDomainFromURL($urltograb);
	   				$objectpage->pageurl=$tmpdomain.'-home';
	   			}

	   			$objectpage->aliasalt = '';
	   			if (preg_match('/^(\d+)\-/', basename($urltograb), $reg)) $objectpage->aliasalt = $reg[1];

				if (preg_match('/<title>(.*)<\/title>/ims', $head, $regtmp))
				{
					$objectpage->title = $regtmp[1];
				}
				if (preg_match('/<meta name="title"[^"]+content="([^"]+)"/ims', $head, $regtmp))
				{
					if (empty($objectpage->title)) $objectpage->title = $regtmp[1];		// If title not found into <title>, we get it from <meta title>
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
					$tmplang=explode('-', $regtmp[1]);
					$objectpage->lang = $tmplang[0].($tmplang[1] ? '_'.strtoupper($tmplang[1]) : '');
				}

				$tmp['content'] = preg_replace('/\s*<meta name="generator"[^"]+content="([^"]+)"\s*\/?>/ims', '', $tmp['content']);

				$objectpage->content = $tmp['content'];
				$objectpage->content = preg_replace('/^.*<body(\s[^>]*)*>/ims', '', $objectpage->content);
				$objectpage->content = preg_replace('/<\/body(\s[^>]*)*>.*$/ims', '', $objectpage->content);

				$absoluteurlinaction=$urltograbdirwithoutslash;
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
				$objectpage->htmlheader = preg_replace('/<link[^>]*rel="canonical[^>]*>\n/ims', '', $objectpage->htmlheader);

				// Now loop to fetch JS
				$tmp = $objectpage->htmlheader;

				preg_match_all('/<script([^\.>]+)src=["\']([^"\'>]+)["\']([^>]*)><\/script>/i', $objectpage->htmlheader, $regs);
				$errorforsubresource = 0;
				foreach ($regs[0] as $key => $val)
				{
					dol_syslog("We will grab the resource found into script tag ".$regs[2][$key]);

					$linkwithoutdomain = $regs[2][$key];
					if (preg_match('/^\//', $regs[2][$key]))
					{
						$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key];	// We use dirroot
					}
					else
					{
						$urltograbbis = $urltograbdirwithoutslash.'/'.$regs[2][$key];	// We use dir of grabbed file
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


				// Now loop to fetch CSS
				$pagecsscontent = "\n".'<style>'."\n";

				preg_match_all('/<link([^\.>]+)href=["\']([^"\'>]+\.css[^"\'>]*)["\']([^>]*)>/i', $objectpage->htmlheader, $regs);
				$errorforsubresource = 0;
				foreach ($regs[0] as $key => $val)
				{
					dol_syslog("We will grab the resources found into link tag ".$regs[2][$key]);

					$linkwithoutdomain = $regs[2][$key];
					if (preg_match('/^\//', $regs[2][$key]))
					{
						$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key];	// We use dirroot
					}
					else
					{
						$urltograbbis = $urltograbdirwithoutslash.'/'.$regs[2][$key];	// We use dir of grabbed file
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
						$action='createcontainer';
					}
					elseif ($tmpgeturl['http_code'] != '200')
					{
						$errorforsubresource++;
						setEventMessages('Error getting link tag url'.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
						dol_syslog('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg']);
						$action='createcontainer';
					}
					else
					{
						//dol_mkdir(dirname($filetosave));

						//$fp = fopen($filetosave, "w");
						//fputs($fp, $tmpgeturl['content']);
						//fclose($fp);
						//if (! empty($conf->global->MAIN_UMASK))
						//	@chmod($file, octdec($conf->global->MAIN_UMASK));

						//	$filename = 'image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
						$pagecsscontent.='/* Content of file '.$urltograbbis.' */'."\n";

						getAllImages($object, $objectpage, $urltograbbis, $tmpgeturl['content'], $action, 1, $grabimages, $grabimagesinto);

						$pagecsscontent.=$tmpgeturl['content']."\n";

						$objectpage->htmlheader = preg_replace('/'.preg_quote($regs[0][$key], '/').'\n*/ims', '', $objectpage->htmlheader);
					}
				}

				$pagecsscontent.='</style>';
				//var_dump($pagecsscontent);

				//print dol_escape_htmltag($tmp);exit;
				$objectpage->htmlheader .= trim($pagecsscontent)."\n";


				// Now loop to fetch all images into page
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
	}
	else
	{
		$objectpage->title = GETPOST('WEBSITE_TITLE', 'alpha');
		$objectpage->type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha');
		$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
		$objectpage->aliasalt = GETPOST('WEBSITE_ALIASALT', 'alpha');
		$objectpage->description = GETPOST('WEBSITE_DESCRIPTION', 'alpha');
		$objectpage->image = GETPOST('WEBSITE_IMAGE', 'alpha');
		$objectpage->keywords = GETPOST('WEBSITE_KEYWORDS', 'alpha');
		$objectpage->lang = GETPOST('WEBSITE_LANG', 'aZ09');
		$objectpage->htmlheader = GETPOST('htmlheader', 'none');

		$substitutionarray=array();
		$substitutionarray['__WEBSITE_CREATE_BY__']=$user->getFullName($langs);

		$sample = GETPOST('sample', 'alpha');
		if (empty($sample)) $sample='empty';

		$pathtosample = DOL_DOCUMENT_ROOT.'/website/samples/page-sample-'.$sample.'.html';

		// Init content with content into pagetemplate.html, blogposttempltate.html, ...
		$objectpage->content = make_substitutions(@file_get_contents($pathtosample), $substitutionarray);
	}

	if (! $error)
	{
		if (empty($objectpage->pageurl))
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_PAGENAME")), null, 'errors');
			$error++;
			$action='createcontainer';
		}
		elseif (! preg_match('/^[a-z0-9\-\_]+$/i', $objectpage->pageurl))
		{
			$langs->load("errors");
			setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
			$error++;
			$action='createcontainer';
		}
		if (empty($objectpage->title))
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_TITLE")), null, 'errors');
			$error++;
			$action='createcontainer';
		}
	}

	if (! $error)
	{
		$res = $objectpage->create($user);
		if ($res <= 0)
		{
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
			$action='createcontainer';
		}
	}
	if (! $error)
	{
		if (! empty($objectpage->content))
		{
			$filealias=$pathofwebsite.'/'.$objectpage->pageurl.'.php';
			$filetpl=$pathofwebsite.'/page'.$objectpage->id.'.tpl.php';

			// Save page alias
			$result=dolSavePageAlias($filealias, $object, $objectpage);
			if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');

			// Save page of content
			$result=dolSavePageContent($filetpl, $object, $objectpage);
			if ($result)
			{
				setEventMessages($langs->trans("Saved"), null, 'mesgs');
			}
			else
			{
				setEventMessages('Failed to write file '.$filetpl, null, 'errors');
				$action = 'createcontainer';
			}
		}
	}
	if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("PageAdded", $objectpage->pageurl), null, 'mesgs');
		$action='';
	}
	else
	{
		$db->rollback();
	}

	if (! $error)
	{
		$pageid = $objectpage->id;

		// To generate the CSS, robot and htmlheader file.

		// Check symlink to medias and restore it if ko
		$pathtomedias=DOL_DATA_ROOT.'/medias';
		$pathtomediasinwebsite=$pathofwebsite.'/medias';
		if (! is_link(dol_osencode($pathtomediasinwebsite)))
		{
			dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
			dol_mkdir(dirname($pathtomediasinwebsite));     // To be sure dir for website exists
			$result = symlink($pathtomedias, $pathtomediasinwebsite);
		}

		if (! dol_is_file($filehtmlheader))
		{
			$htmlheadercontent ="<html>\n";
			$htmlheadercontent.=$htmlheadercontentdefault;
			$htmlheadercontent.="</html>";
			$result=dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent);
		}

		if (! dol_is_file($filecss))
		{
			$csscontent = "/* CSS content (all pages) */\nbody.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }\n.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}";
			$result=dolSaveCssFile($filecss, $csscontent);
		}

		if (! dol_is_file($filejs))
		{
			$jscontent = "/* JS content (all pages) */\n";
			$result=dolSaveJsFile($filejs, $jscontent);
		}

		if (! dol_is_file($filerobot))
		{
			$robotcontent = "# Robot file. Generated with Dolibarr\nUser-agent: *\nAllow: /public/\nDisallow: /administrator/";
			$result=dolSaveRobotFile($filerobot, $robotcontent);
		}

		if (! dol_is_file($filehtaccess))
		{
			$htaccesscontent = "# Order allow,deny\n# Deny from all";
			$result=dolSaveHtaccessFile($filehtaccess, $htaccesscontent);
		}

		$action = 'preview';
	}
}

// Delete site
if ($action == 'deletesite')
{
	$error = 0;

	$db->begin();

	$res = $object->fetch(0, $websitekey);
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

	if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("SiteDeleted", $object->ref, $websitekey), null, 'mesgs');

		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		$db->rollback();
		dol_print_error($db);
	}
}

// Delete page
if ($action == 'delete')
{
	$error = 0;

	$db->begin();

	$res = $object->fetch(0, $websitekey);
	$website = $object;

	$res = $objectpage->fetch($pageid, $object->fk_website);

	if ($res > 0)
	{
		$res = $objectpage->delete($user);
		if ($res <= 0)
		{
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		}
	}

	if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("PageDeleted", $objectpage->pageurl, $websitekey), null, 'mesgs');

		header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey);
		exit;
	}
	else
	{
		$db->rollback();
		dol_print_error($db);
	}
}

// Update css
if ($action == 'updatecss')
{
	if (GETPOST('refreshsite', 'alpha') || GETPOST('refreshpage', 'alpha'))		// If we tried to reload another site/page, we stay on editcss mode.
	{
		$action='editcss';
	}
	else
	{
		$res = $object->fetch(0, $websitekey);
		$website = $object;

		if (GETPOSTISSET('virtualhost'))
		{
		    if (GETPOST('virtualhost', 'alpha') && ! preg_match('/^http/', GETPOST('virtualhost', 'alpha')))
    		{
    		    $error++;
    		    setEventMessages($langs->trans('ErrorURLMustStartWithHttp', $langs->transnoentitiesnoconv("VirtualHost")), null, 'errors');
    		    $action='editcss';
    		}

    		if (! $error)
    		{
    		    $object->virtualhost = GETPOST('virtualhost', 'alpha');

    		    $result = $object->update($user);
        		if ($result < 0)
        		{
        		    $error++;
        		    setEventMessages($object->error, $object->errors, 'errors');
        		    $action='editcss';
        		}
    		}
		}

		if (! $error)
		{
    		// Save master.inc.php file
    		$filemaster=$pathofwebsite.'/master.inc.php';

    		dol_syslog("Save master file ".$filemaster);

    		dol_mkdir($pathofwebsite);

    		// Now generate the master.inc.php page
    		$result = dolSaveMasterFile($filemaster);
    		if (! $result) setEventMessages('Failed to write file '.$filemaster, null, 'errors');


    		// Html header file
    		$htmlheadercontent ='';

    		/* We disable php code since htmlheader is never executed as an include but only read by fgets_content.
    	    $htmlheadercontent.= "<?php // BEGIN PHP\n";
    	    $htmlheadercontent.= '$websitekey=basename(__DIR__);'."\n";
    	    $htmlheadercontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once './master.inc.php'; } // Not already loaded"."\n";
    	    $htmlheadercontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
    	    $htmlheadercontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
    	    $htmlheadercontent.= "ob_start();\n";
    	    // $htmlheadercontent.= "header('Content-type: text/html');\n";		// Not required. htmlheader.html is never call as a standalone page
    	    $htmlheadercontent.= "// END PHP ?>\n";*/

    		$htmlheadercontent.= preg_replace(array('/<html>\n*/ims','/<\/html>\n*/ims'), array('',''), GETPOST('WEBSITE_HTML_HEADER', 'none'));

    		/*$htmlheadercontent.= "\n".'<?php // BEGIN PHP'."\n";
    	    $htmlheadercontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
    	    $htmlheadercontent.= "// END PHP ?>"."\n";*/

    		$htmlheadercontent = trim($htmlheadercontent)."\n";

    		dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent);


    		// Css file
    		$csscontent ='';

    		$csscontent.= "<?php // BEGIN PHP\n";
    		$csscontent.= '$websitekey=basename(__DIR__);'."\n";
    		$csscontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Not already loaded"."\n";	// For the css, we need to set path of master using the dirname of css file.
    		$csscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
    		$csscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
    		$csscontent.= "ob_start();\n";
    		$csscontent.= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
    		$csscontent.= "header('Content-type: text/css');\n";
    		$csscontent.= "// END PHP ?>\n";

    		$csscontent.= GETPOST('WEBSITE_CSS_INLINE', 'none');

    		$csscontent.= "\n".'<?php // BEGIN PHP'."\n";
    		$csscontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
    		$csscontent.= "// END PHP ?>"."\n";

    		dol_syslog("Save css content into ".$filecss);

    		dol_mkdir($pathofwebsite);
    		$result = file_put_contents($filecss, $csscontent);
    		if (! empty($conf->global->MAIN_UMASK))
    			@chmod($filecss, octdec($conf->global->MAIN_UMASK));

    		if (! $result)
    		{
    			$error++;
    			setEventMessages('Failed to write file '.$filecss, null, 'errors');
    		}


    		// Js file
    		$jscontent ='';

    		$jscontent.= "<?php // BEGIN PHP\n";
    		$jscontent.= '$websitekey=basename(__DIR__);'."\n";
    		$jscontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__.'/master.inc.php'; } // Not already loaded"."\n";	// For the css, we need to set path of master using the dirname of css file.
    		$jscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
    		$jscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
    		$jscontent.= "ob_start();\n";
    		$jscontent.= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
    		$jscontent.= "header('Content-type: application/javascript');\n";
    		$jscontent.= "// END PHP ?>\n";

    		$jscontent.= GETPOST('WEBSITE_JS_INLINE', 'none');

    		$jscontent.= "\n".'<?php // BEGIN PHP'."\n";
    		$jscontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
    		$jscontent.= "// END PHP ?>"."\n";

    		dol_syslog("Save js content into ".$filejs);

    		dol_mkdir($pathofwebsite);
    		$result = file_put_contents($filejs, $jscontent);
    		if (! empty($conf->global->MAIN_UMASK))
    			@chmod($filejs, octdec($conf->global->MAIN_UMASK));

    		if (! $result)
    		{
    			$error++;
    			setEventMessages('Failed to write file '.$filejs, null, 'errors');
    		}


    		// Robot file
    		$robotcontent ='';

    		/*$robotcontent.= "<?php // BEGIN PHP\n";
    	    $robotcontent.= '$websitekey=basename(__DIR__);'."\n";
    	    $robotcontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once './master.inc.php'; } // Not already loaded"."\n";
    	    $robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
    	    $robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
    	    $robotcontent.= "ob_start();\n";
    		$robotcontent.= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
    	    $robotcontent.= "header('Content-type: text/css');\n";
    	    $robotcontent.= "// END PHP ?>\n";*/

    		$robotcontent.= GETPOST('WEBSITE_ROBOT', 'none');

    		/*$robotcontent.= "\n".'<?php // BEGIN PHP'."\n";
    	    $robotcontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
    	    $robotcontent.= "// END PHP ?>"."\n";*/

    		dol_syslog("Save file robot into ".$filerobot);

    		dol_mkdir($pathofwebsite);
    		$result = file_put_contents($filerobot, $robotcontent);
    		if (! empty($conf->global->MAIN_UMASK))
    			@chmod($filerobot, octdec($conf->global->MAIN_UMASK));

    		if (! $result)
    		{
    			$error++;
    			setEventMessages('Failed to write file '.$filerobot, null, 'errors');
    		}


    		// Css file
    		$htaccesscontent ='';

    		/*$htaccesscontent.= "<?php // BEGIN PHP\n";
        	$htaccesscontent.= '$websitekey=basename(__DIR__);'."\n";
        	$htaccesscontent.= "if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once './master.inc.php'; } // Not already loaded"."\n";
        	$htaccesscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
        	$htaccesscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
        	$htaccesscontent.= "ob_start();\n";
    		$htaccesscontent.= "header('Cache-Control: max-age=3600, public, must-revalidate');\n";
        	$htaccesscontent.= "header('Content-type: text/css');\n";
        	$htaccesscontent.= "// END PHP ?>\n";*/

    		$htaccesscontent.= GETPOST('WEBSITE_HTACCESS', 'none');

    		/*$htaccesscontent.= "\n".'<?php // BEGIN PHP'."\n";
        	$htaccesscontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
        	$htaccesscontent.= "// END PHP ?>"."\n";*/

    		dol_syslog("Save file htaccess into ".$filehtaccess);

    		dol_mkdir($pathofwebsite);
    		$result = file_put_contents($filehtaccess, $htaccesscontent);
    		if (! empty($conf->global->MAIN_UMASK))
    			@chmod($filehtaccess, octdec($conf->global->MAIN_UMASK));

       		if (! $result)
       		{
       			$error++;
       			setEventMessages('Failed to write file '.$filehtaccess, null, 'errors');
       		}


    		// Message if no error
    		if (! $error)
    		{
    			setEventMessages($langs->trans("Saved"), null, 'mesgs');
    		}

    		$action='preview';
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
	if (! $res > 0)
	{
		$error++;
		setEventMessages($object->error, $object->errors, 'errors');
	}

	if (! $error)
	{
		$db->commit();

		// Generate the index.php page to be the home page
		//-------------------------------------------------
		$result = dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl, $filewrapper);

		if ($result) setEventMessages($langs->trans("Saved"), null, 'mesgs');
		else setEventMessages('Failed to write file '.$fileindex, null, 'errors');

		$action='preview';
	}
	else
	{
		$db->rollback();
	}
}

// Update page (meta)
if ($action == 'updatemeta')
{
	$db->begin();

	$result = $object->fetch(0, $websitekey);
	$website = $object;

	$objectpage->fk_website = $object->id;

	// Check parameters
	if (! preg_match('/^[a-z0-9\-\_]+$/i', GETPOST('WEBSITE_PAGENAME', 'alpha')))
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
		$action='editmeta';
	}

	$res = $objectpage->fetch($pageid, $object->id);
	if ($res <= 0)
	{
		$error++;
		setEventMessages('Page not found '.$objectpage->error, $objectpage->errors, 'errors');
	}

	// Check alias not exists
	if (! $error && GETPOST('WEBSITE_PAGENAME', 'alpha'))
	{
		$websitepagetemp=new WebsitePage($db);
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
	if (! $error && GETPOST('WEBSITE_ALIASALT', 'alpha'))
	{
		$arrayofaliastotest=explode(',', GETPOST('WEBSITE_ALIASALT', 'alpha'));
		$websitepagetemp=new WebsitePage($db);
		foreach($arrayofaliastotest as $aliastotest)
		{
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

	if (! $error)
	{
		$objectpage->old_object = clone $objectpage;

		$objectpage->title = GETPOST('WEBSITE_TITLE', 'alpha');
		$objectpage->type_container = GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha');
		$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME', 'alpha');
		$objectpage->aliasalt = GETPOST('WEBSITE_ALIASALT', 'alpha');
		$objectpage->description = GETPOST('WEBSITE_DESCRIPTION', 'alpha');
		$objectpage->image = GETPOST('WEBSITE_IMAGE', 'alpha');
		$objectpage->keywords = GETPOST('WEBSITE_KEYWORDS', 'alpha');
		$objectpage->lang = GETPOST('WEBSITE_LANG', 'aZ09');
		$objectpage->htmlheader = trim(GETPOST('htmlheader', 'none'));
		$newdatecreation=dol_mktime(GETPOST('datecreationhour', 'int'), GETPOST('datecreationmin', 'int'), GETPOST('datecreationsec', 'int'), GETPOST('datecreationmonth', 'int'), GETPOST('datecreationday', 'int'), GETPOST('datecreationyear', 'int'));
		if ($newdatecreation) $objectpage->date_creation = $newdatecreation;

		$res = $objectpage->update($user);
		if (! ($res > 0))
		{
			$langs->load("errors");
			if ($db->lasterrno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorAPageWithThisNameOrAliasAlreadyExists"), null, 'errors');
				$action = 'editmeta';
			}
			else
			{
				$error++;
				$langs->load("errors");
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
				$action = 'editmeta';
			}
		}
	}

	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}

	if (! $error)
	{
		$filemaster=$pathofwebsite.'/master.inc.php';
		$fileoldalias=$pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
		$filealias=$pathofwebsite.'/'.$objectpage->pageurl.'.php';

		dol_mkdir($pathofwebsite);


		// Now generate the master.inc.php page
		$result = dolSaveMasterFile($filemaster);
		if (! $result) setEventMessages('Failed to write file '.$filemaster, null, 'errors');

		// Now delete the alias.php page
		if (! empty($fileoldalias))
		{
			dol_syslog("We delete old alias page name=".$fileoldalias." to build a new alias page=".$filealias);
			dol_delete_file($fileoldalias);
		}
		// Now delete the alternative alias.php pages
		if (! empty($objectpage->old_object->aliasalt))
		{
			$tmpaltaliases=explode(',', $objectpage->old_object->aliasalt);
			if (is_array($tmpaltaliases))
			{
				foreach($tmpaltaliases as $tmpaliasalt)
				{
					dol_syslog("We delete old alt alias pages name=".trim($tmpaliasalt));
					dol_delete_file($pathofwebsite.'/'.trim($tmpaliasalt).'.php');
				}
			}
		}

		// Save page alias
		$result=dolSavePageAlias($filealias, $object, $objectpage);
		if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');
		// Save alt aliases
		if (! empty($objectpage->aliasalt))
		{
			$tmpaltaliases=explode(',', $objectpage->aliasalt);
			if (is_array($tmpaltaliases))
			{
				foreach($tmpaltaliases as $tmpaliasalt)
				{
					$result=dolSavePageAlias($pathofwebsite.'/'.trim($tmpaliasalt).'.php', $object, $objectpage);
					if (! $result) setEventMessages('Failed to write file '.$pathofwebsite.'/'.trim($tmpaliasalt).'.php', null, 'errors');
				}
			}
		}


		// Save page of content
		$result=dolSavePageContent($filetpl, $object, $objectpage);
		if ($result)
		{
			setEventMessages($langs->trans("Saved"), null, 'mesgs');
			//header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
			//exit;
		}
		else
		{
			setEventMessages('Failed to write file '.$filetpl, null, 'errors');
			//header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
   			//exit;
		}

		$action='preview';
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
		$result = $objectnew->createFromClone($user, GETPOST('id', 'int'), GETPOST('siteref', 'aZ09'), (GETPOST('newlang', 'aZ09')?GETPOST('newlang', 'aZ09'):''));
		if ($result < 0)
		{
			$error++;
			setEventMessages($objectnew->error, $objectnew->errors, 'errors');
			$action='preview';

			$db->rollback();
		}
		else
		{
			$object = $objectnew;
			$id = $object->id;
			$pageid = $object->fk_default_home;
			$websitekey = GETPOST('siteref', 'aZ09');

			$db->commit();
		}
	}

	if ($action == 'confirm_createpagefromclone')
	{
		$istranslation=(GETPOST('is_a_translation', 'aZ09')=='on'?1:0);
		if ($istranslation)
		{
			if (GETPOST('newlang', 'aZ09') == $objectpage->lang)
			{
				$error++;
				setEventMessages($langs->trans("LanguageMustNotBeSameThanClonedPage"), null, 'errors');
				$action='preview';
			}
		}

		if (! $error)
		{
			$db->begin();

			$newwebsiteid = GETPOST('newwebsite', 'int');
			$pathofwebsitenew = $pathofwebsite;

			$tmpwebsite=new Website($db);
			if ($newwebsiteid > 0 && $newwebsiteid != $object->id)
			{
				$tmpwebsite->fetch($newwebsiteid);
				$pathofwebsitenew = $dolibarr_main_data_root.'/website/'.$tmpwebsite->ref;
			}
			else
			{
				$tmpwebsite = $object;
			}

			$objectpage = new WebsitePage($db);
			$resultpage = $objectpage->createFromClone($user, $pageid, GETPOST('pageurl', 'aZ09'), (GETPOST('newlang', 'aZ09')?GETPOST('newlang', 'aZ09'):''), $istranslation, $newwebsiteid);
			if ($resultpage < 0)
			{
				$error++;
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
				$action='createpagefromclone';

				$db->rollback();
			}
			else
			{
				$fileindex=$pathofwebsitenew.'/index.php';
				$filetpl=$pathofwebsitenew.'/page'.$resultpage->id.'.tpl.php';
				$filewrapper=$pathofwebsitenew.'/wrapper.php';

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

	if (! $error)
	{
		// Check symlink to medias and restore it if ko
		$pathtomedias=DOL_DATA_ROOT.'/medias';
		$pathtomediasinwebsite=$pathofwebsite.'/medias';
		if (! is_link(dol_osencode($pathtomediasinwebsite)))
		{
			dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
			dol_mkdir(dirname($pathtomediasinwebsite));     // To be sure dir for website exists
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
		else
		{
			$res=0;
			if ($object->fk_default_home > 0)
			{
				$res = $objectpage->fetch($object->fk_default_home);
			}
			if (! ($res > 0))
			{
				$res = $objectpage->fetch(0, $object->id);
			}
		}
	}

	if (! $error && $res > 0)
	{
		if ($action == 'updatesource' || $action == 'updatecontent')
		{
			$db->begin();

			$objectpage->content = GETPOST('PAGE_CONTENT', 'none');

			// Clean data. We remove all the head section.
			$objectpage->content = preg_replace('/<head>.*<\/head>/ims', '', $objectpage->content);
			/* $objectpage->content = preg_replace('/<base\s+href=[\'"][^\'"]+[\'"]\s/?>/s', '', $objectpage->content); */


			$res = $objectpage->update($user);
			if ($res < 0)
			{
				$error++;
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
			}

			if (! $error)
			{
				$db->commit();

				$filemaster=$pathofwebsite.'/master.inc.php';
				//$fileoldalias=$pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
				$filealias=$pathofwebsite.'/'.$objectpage->pageurl.'.php';

				dol_mkdir($pathofwebsite);


				// Now generate the master.inc.php page
				$result = dolSaveMasterFile($filemaster);

				if (! $result) setEventMessages('Failed to write file '.$filemaster, null, 'errors');


				// Now generate the alias.php page
				if (! empty($fileoldalias))
				{
					dol_syslog("We regenerate alias page new name=".$filealias.", old name=".$fileoldalias);
					dol_delete_file($fileoldalias);
				}

				// Save page alias
				$result=dolSavePageAlias($filealias, $object, $objectpage);
				if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');

				// Save page content
				$result=dolSavePageContent($filetpl, $object, $objectpage);
				if ($result)
				{
					setEventMessages($langs->trans("Saved"), null, 'mesgs');
					header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
	   				exit;
				}
				else
				{
					setEventMessages('Failed to write file '.$filetpl, null, 'errors');
					header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
	   				exit;
				}
			}
			else
			{
				$db->rollback();
			}
		}
		else
		{
			header("Location: ".$_SERVER["PHP_SELF"].'?website='.$websitekey.'&pageid='.$pageid);
			exit;
		}
	}
	else
	{
		if (! $error)
		{
			if (empty($websitekey) || $websitekey == '-1')
			{
				setEventMessages($langs->trans("NoWebSiteCreateOneFirst"), null, 'warnings');
			}
			else
			{
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
		header("Content-Length: " . filesize($fileofzip));

		readfile($fileofzip);
		exit;
	}
}

// Import site
if ($action == 'importsiteconfirm')
{
	if (empty($_FILES))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
		$action = 'importsite';
	}
	else
	{
		if (! empty($_FILES))
		{
			// Check symlink to medias and restore it if ko
			$pathtomedias=DOL_DATA_ROOT.'/medias';
			$pathtomediasinwebsite=$pathofwebsite.'/medias';
			if (! is_link(dol_osencode($pathtomediasinwebsite)))
			{
				dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
				dol_mkdir(dirname($pathtomediasinwebsite));     // To be sure dir for website exists
				$result = symlink($pathtomedias, $pathtomediasinwebsite);
				if (! $result)
				{
					setEventMessages($langs->trans("ErrorFieldToCreateSymLinkToMedias", $pathtomediasinwebsite, $pathtomedias), null, 'errors');
					$action = 'importsite';
				}
			}

			if (is_array($_FILES['userfile']['tmp_name'])) $userfiles=$_FILES['userfile']['tmp_name'];
			else $userfiles=array($_FILES['userfile']['tmp_name']);

			foreach($userfiles as $key => $userfile)
			{
				if (empty($_FILES['userfile']['tmp_name'][$key]))
				{
					$error++;
					if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2){
						setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
						$action = 'importsite';
					}
					else {
						setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
						$action = 'importsite';
					}
				}
			}

			if (! $error)
			{
				$upload_dir = $conf->website->dir_temp;
				$result = dol_add_file_process($upload_dir, 1, -1, 'userfile', '');

				// Get name of file (take last one if several name provided)
				$fileofzip = $upload_dir.'/unknown';
				foreach($_FILES as $key => $ifile)
				{
					foreach($ifile['name'] as $key2 => $ifile2)
					{
						$fileofzip = $upload_dir . '/' .$ifile2;
					}
				}

				$result = $object->importWebSite($fileofzip);
				if ($result < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'importsite';
				}
				else
				{
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

$help_url='';

$arrayofjs = array(
	'/includes/ace/ace.js',
	'/includes/ace/ext-statusbar.js',
	'/includes/ace/ext-language_tools.js',
	//'/includes/ace/ext-chromevox.js'
	//'/includes/jquery/plugins/jqueryscoped/jquery.scoped.js',
);
$arrayofcss = array();

$moreheadcss='';
$moreheadjs='';

$arrayofjs[]='includes/jquery/plugins/blockUI/jquery.blockUI.js';
$arrayofjs[]='core/js/blockUI.js';	// Used by ecm/tpl/enabledfiletreeajax.tpl.php
if (empty($conf->global->MAIN_ECM_DISABLE_JS)) $arrayofjs[]="includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js";

$moreheadjs.='<script type="text/javascript">'."\n";
$moreheadjs.='var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
$moreheadjs.='</script>'."\n";

llxHeader($moreheadcss.$moreheadjs, $langs->trans("WebsiteSetup"), $help_url, '', 0, 0, $arrayofjs, $arrayofcss, '', '', '<!-- Begin div class="fiche" -->'."\n".'<div class="fichebutwithotherclass">');

print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';

print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
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

print '<div>';

// Add a margin under toolbar ?
$style='';
if ($action != 'preview' && $action != 'editcontent' && $action != 'editsource') $style=' margin-bottom: 5px;';


if (! GETPOST('hide_websitemenu'))
{
	//var_dump($objectpage);exit;
	print '<div class="centpercent websitebar">';


	// ***** Part for web sites
	print '<!-- Bar for website -->';
	print '<div class="websiteselection hideonsmartphoneimp minwidth100 tdoverflowmax100">';
	print $langs->trans("Website").' : ';
	print '</div>';

	print '<div class="websiteselection hideonsmartphoneimp">';
	print ' <input type="submit"'.$disabled.' class="button" value="'.dol_escape_htmltag($langs->trans("Add")).'" name="createsite">';
	print '</div>';

	// List of website
	print '<div class="websiteselection">';
	$out='';
	$out.='<select name="website" class="minwidth100" id="website">';
	if (empty($object->records)) $out.='<option value="-1">&nbsp;</option>';
	// Loop on each sites
	$i=0;
	foreach($object->records as $key => $valwebsite)
	{
		if (empty($websitekey)) $websitekey=$valwebsite->ref;

		$out.='<option value="'.$valwebsite->ref.'"';
		if ($websitekey == $valwebsite->ref) $out.=' selected';		// To preselect a value
		$out.='>';
		$out.=$valwebsite->ref;
		$out.='</option>';
		$i++;
	}
	$out.='</select>';
	$out.=ajax_combobox('website');
	print $out;
	//print '<input type="submit" class="button" name="refreshsite" value="'.$langs->trans("Load").'">';
	print '<input type="image" class="valignmiddle" src="'.img_picto('', 'refresh', '', 0, 1).'" name="refreshpage" value="'.$langs->trans("Load").'">';


	if ($websitekey)
	{
		$virtualurl='';
		$dataroot=DOL_DATA_ROOT.'/website/'.$websitekey;
		if (! empty($object->virtualhost)) $virtualurl=$object->virtualhost;
	}

	$array=array();
	if ($object->id > 0)
	{
		$array=$objectpage->fetchAll($object->id, 'ASC,ASC', 'type_container,pageurl');
	}
	if (! is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
	$atleastonepage=(is_array($array) && count($array) > 0);

	if ($websitekey && $websitekey != '-1' && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone'))
	{
		$disabled='';
		if (empty($user->rights->website->write)) $disabled=' disabled="disabled"';

		print ' &nbsp; ';

		print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditCss")).'" name="editcss">';
		//print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditMenu")).'" name="editmenu">';
		print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("CloneSite")).'" name="createfromclone">';
		print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("ExportSite")).'" name="exportsite">';
		if (! $atleastonepage)
		{
			print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("ImportSite")).'" name="importsite">';
		}
		else
		{
			print '<input type="submit" class="button nobordertransp" disabled="disabled" value="'.dol_escape_htmltag($langs->trans("ImportSite")).'" name="importsite">';
		}

		print '<input type="submit" class="buttonDelete" name="deletesite" value="'.$langs->trans("Delete").'"'.($atleastonepage?' disabled="disabled"':'').'>';

		print ' &nbsp; ';

		print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("MediaFiles")).'" name="file_manager">';
		/*print '<a class="button button_file_manager"'.$disabled.'>'.dol_escape_htmltag($langs->trans("MediaFiles")).'</a>';
		print '<script language="javascript">
			jQuery(document).ready(function () {
           		jQuery(".button_file_manager").click(function () {
					var $dialog = $(\'<div></div>\').html(\'<iframe style="border: 0px;" src="'.DOL_URL_ROOT.'/website/index.php?hide_websitemenu=1&dol_hide_topmenu=1&dol_hide_leftmenu=1&file_manager=1&website='.$websitekey.'&pageid='.$pageid.'" width="100%" height="100%"></iframe>\')
					.dialog({
						autoOpen: false,
						modal: true,
						height: 500,
						width: \'80%\',
						title: "'.dol_escape_js($langs->trans("FileManager")).'"
					});
					$dialog.dialog(\'open\');
				});
			});
			</script>';
		*/
	}

	print '</div>';


	// Toolbar for websites

	print '<div class="websitetools websiteselection">';

	if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
	{
		$urlext=$virtualurl;
		$urlint=$urlwithroot.'/public/website/index.php?website='.$websitekey;

		print '<div class="websiteinputurl valignmiddle" id="websiteinputurl">';
		$linktotestonwebserver = '<a href="'.($virtualurl?$virtualurl:'#').'" class="valignmiddle">';
		$linktotestonwebserver.= $langs->trans("TestDeployOnWeb", $virtualurl).' '.img_picto('', 'object_globe');
		$linktotestonwebserver.= '</a>';
		$htmltext = '';
		if (empty($object->fk_default_home))
		{
		    $htmltext.= '<br><span class="error">'.$langs->trans("YouMustDefineTheHomePage").'</span><br><br>';
		}
		elseif (empty($virtualurl))
		{
		    $htmltext.= '<br><span class="error">'.$langs->trans("VirtualHostUrlNotDefined").'</span><br><br>';
		}
		else
		{
		    $htmltext.= '<br><center>'.$langs->trans("GoTo").' <a href="'.$virtualurl.'" target="_website">'.$virtualurl.'</a></center><br>';
		}
		if (! empty($conf->global->WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER))
		{
		    $htmltext.= '<br>'.$conf->global->WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER;
		}
		else
		{
    		$htmltext.=$langs->trans("SetHereVirtualHost", $dataroot);
    		$htmltext.='<br>';
    		$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
    		$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias');
    		$htmltext.='<br>';
    		$htmltext.='<br>';
    		$htmltext.=$langs->trans("YouCanAlsoTestWithPHPS", $dataroot);
		}
		print $form->textwithpicto($linktotestonwebserver, $htmltext, 1, 'none', 'valignmiddle', 0, 2, 'helpvirtualhost');
		print '</div>';
	}

	if (in_array($action, array('editcss','editmenu','file_manager')))
	{
		if (preg_match('/^create/', $action) && $action != 'file_manager') print '<input type="submit" id="savefile" class="button buttonforacesave" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		if (preg_match('/^edit/', $action) && $action != 'file_manager') print '<input type="submit" id="savefile" class="button buttonforacesave" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
	}

	print '</div>';


	// Toolbar for pages

	if ($websitekey && $websitekey != '-1' && ! in_array($action, array('editcss','editmenu','importsite')))
	{
		print '</div>';	// Close current websitebar to open a new one

		print '<!-- Bar for websitepage -->';
		print '<div class="centpercent websitebar"'.($style?' style="'.$style.'"':'').'">';

		print '<div class="websiteselection hideonsmartphoneimp minwidth100 tdoverflowmax100">';
		print $langs->trans("PageContainer").': ';
		print '</div>';

		print '<div class="websiteselection hideonsmartphoneimp">';
		print '<input type="submit"'.$disabled.' class="button" value="'.dol_escape_htmltag($langs->trans("Add")).'" name="createcontainer">';
		print '</div>';

		print '<div class="websiteselection">';

		if ($action != 'addcontainer')
		{
			$out='';
			if ($atleastonepage && $action != 'editsource')
			{
			    $out.='<select name="pageid" id="pageid" class="minwidth200 maxwidth300">';
			}
			else
			{
			    $out.='<select name="pageidbis" id="pageid" class="minwidth200 maxwidth300" disabled="disabled">';
			}
			if ($atleastonepage)
			{
				if (empty($pageid) && $action != 'createcontainer')      // Page id is not defined, we try to take one
				{
					$firstpageid=0;$homepageid=0;
					foreach($array as $key => $valpage)
					{
						if (empty($firstpageid)) $firstpageid=$valpage->id;
						if ($object->fk_default_home && $key == $object->fk_default_home) $homepageid=$valpage->id;
					}
					$pageid=$homepageid?$homepageid:$firstpageid;   // We choose home page and if not defined yet, we take first page
				}

				foreach($array as $key => $valpage)
				{
					$out.='<option value="'.$key.'"';
					if ($pageid > 0 && $pageid == $key) $out.=' selected';		// To preselect a value
					$out.='>';
					$out.='['.$valpage->type_container.' '.sprintf("%03d", $valpage->id).'] ';
					$out.=$valpage->pageurl.' - '.$valpage->title;
					if ($object->fk_default_home && $key == $object->fk_default_home) $out.=' ('.$langs->trans("HomePage").')';
					$out.='</option>';
				}
			}
			else $out.='<option value="-1">&nbsp;</option>';
			$out.='</select>';
			if ($atleastonepage && $action != 'editsource')
			{
			    $out.=ajax_combobox('pageid');
			}
			else
			{
    			$out.='<input type="hidden" name="pageid" value="'.$pageid.'">';
    			$out.=ajax_combobox('pageid');
			}
			print $out;
		}
		else
		{
			print $langs->trans("New");
		}

		//print '<input type="submit" class="button" name="refreshpage" value="'.$langs->trans("Load").'"'.($atleastonepage?'':' disabled="disabled"').'>';
		print '<input type="image" class="valignmiddle" src="'.img_picto('', 'refresh', '', 0, 1).'" name="refreshpage" value="'.$langs->trans("Load").'"'.(($atleastonepage && $action != 'editsource')?'':' disabled="disabled"').'>';


		// Print nav arrows
		$pagepreviousid=0;
		$pagenextid=0;
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
		else print '<span class="valignmiddle opacitymedium">'.img_previous($langs->trans("PreviousContainer")).'</span>';
		if ($pagenextid) print '<a class="valignmiddle" href="'.$_SERVER['PHP_SELF'].'?website='.urlencode($object->ref).'&pageid='.$pagenextid.'&action='.$action.'">'.img_next($langs->trans("NextContainer")).'</a>';
		else print '<span class="valignmiddle opacitymedium">'.img_next($langs->trans("NextContainer")).'</span>';

		$websitepage = new WebSitePage($db);
		if ($pageid > 0 && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone'))
		{
			$websitepage->fetch($pageid);
		}

		if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
		{
			$disabled='';
			if (empty($user->rights->website->write)) $disabled=' disabled="disabled"';

			// Confirmation to clone
			if ($action == 'createfromclone') {
				// Create an array for form
				$formquestion = array(
				array('type' => 'text', 'name' => 'siteref', 'label'=> $langs->trans("WebSite")  ,'value'=> 'copy_of_'.$object->ref),
				//array('type' => 'checkbox', 'name' => 'is_a_translation', 'label' => $langs->trans("SiteIsANewTranslation"), 'value' => 0),
				//array('type' => 'other','name' => 'newlang','label' => $langs->trans("Language"), 'value' => $formadmin->select_language(GETPOST('newlang', 'az09')?GETPOST('newlang', 'az09'):$langs->defaultlang, 'newlang', 0, null, '', 0, 0, 'minwidth200')),
				//array('type' => 'other','name' => 'newwebsite','label' => $langs->trans("WebSite"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0))
				);

				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id='.$object->id, $langs->trans('CloneSite'), '', 'confirm_createfromclone', $formquestion, 0, 1, 200);

				print $formconfirm;
			}

			if ($pageid > 0 && $atleastonepage)		// pageid can be set without pages, if homepage of site is set and all page were removed
			{
				// Confirmation to clone
				if ($action == 'createpagefromclone') {
					// Create an array for form
					$preselectedlanguage = GETPOST('newlang', 'az09') ? GETPOST('newlang', 'az09') : ($objectpage->lang ? $objectpage->lang : $langs->defaultlang);
					$formquestion = array(
						array('type' => 'hidden', 'name' => 'sourcepageurl', 'value'=> $objectpage->pageurl),
						array('type' => 'checkbox', 'tdclass'=>'maxwidth200', 'name' => 'is_a_translation', 'label' => $langs->trans("PageIsANewTranslation"), 'value' => 0),
						array('type' => 'other','name' => 'newlang', 'label' => $langs->trans("Language"), 'value' => $formadmin->select_language($preselectedlanguage, 'newlang', 0, null, 1, 0, 0, 'minwidth200', 0, 1)),
						array('type' => 'other','name' => 'newwebsite', 'label' => $langs->trans("WebSite"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0)),
						array('type' => 'text', 'tdclass'=>'maxwidth200 fieldrequired', 'name' => 'pageurl', 'label'=> $langs->trans("WEBSITE_PAGENAME"), 'value'=> 'copy_of_'.$objectpage->pageurl),
					);

				   	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?website='.$object->ref.'&pageid=' . $pageid, $langs->trans('ClonePage'), '', 'confirm_createpagefromclone', $formquestion, 0, 1, 300, 550);

					print $formconfirm;
				}

				print ' &nbsp; ';

				print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditPageMeta")).'" name="editmeta">';

				print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditHTMLSource")).'" name="editsource">';

				print '<!-- button EditInLine and ShowSubcontainers -->'."\n";
				print '<div class="websiteselectionsection inline-block">';
				print '<div class="inline-block">';
				print $langs->trans("EditInLine");
				if ($websitepage->grabbed_from)
				{
					//print '<input type="submit" class="button nobordertransp" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("OnlyEditionOfSourceForGrabbedContent")).'" value="'.dol_escape_htmltag($langs->trans("EditWithEditor")).'" name="editcontent">';
					print '<a class="button nobordertransp opacitymedium nohoverborder"'.$disabled.' href="#" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("OnlyEditionOfSourceForGrabbedContent")).'">'.img_picto($langs->trans("OnlyEditionOfSourceForGrabbedContent"), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
				}
				else
				{
					//print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditWithEditor")).'" name="editcontent">';
					if (empty($conf->global->WEBSITE_EDITINLINE))
					{
						print '<a class="button nobordertransp nohoverborder"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=seteditinline">'.img_picto($langs->trans("EditInLineOff"), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
					}
					else
					{
						print '<a class="button nobordertransp nohoverborder"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=unseteditinline">'.img_picto($langs->trans("EditInLineOn"), 'switch_on', '', false, 0, 0, '', 'nomarginleft').'</a>';
					}
				}
				print '</div>';
				print '<div class="inline-block">';
				print $langs->trans("ShowSubcontainers");
				/*if ($websitepage->grabbed_from)
				{
					print '<a class="button nobordertransp opacitymedium nohoverborder"'.$disabled.' href="#" disabled="disabled" title="'.dol_escape_htmltag($langs->trans("OnlyEditionOfSourceForGrabbedContent")).'">'.img_picto($langs->trans("OnlyEditionOfSourceForGrabbedContent"),'switch_off','',false,0,0,'','nomarginleft').'</a>';
				}
				else
				{*/
					if (empty($conf->global->WEBSITE_SUBCONTAINERSINLINE))
					{
						print '<a class="button nobordertransp nohoverborder"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=setshowsubcontainers">'.img_picto($langs->trans("ShowSubContainersOff"), 'switch_off', '', false, 0, 0, '', 'nomarginleft').'</a>';
					}
					else
					{
						print '<a class="button nobordertransp nohoverborder"'.$disabled.' href="'.$_SERVER["PHP_SELF"].'?website='.$object->ref.'&pageid='.$websitepage->id.'&action=unsetshowsubcontainers">'.img_picto($langs->trans("ShowSubContainersOn"), 'switch_on', '', false, 0, 0, '', 'nomarginleft').'</a>';
					}
				/*}*/
				print '</div>';
				print '</div>';

				if ($object->fk_default_home > 0 && $pageid == $object->fk_default_home) print '<input type="submit" class="button nobordertransp" disabled="disabled" value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
				else print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
				print '<input type="submit" class="button nobordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("ClonePage")).'" name="createpagefromclone">';
				print '<input type="submit" class="buttonDelete" name="delete" value="'.$langs->trans("Delete").'"'.($atleastonepage?'':' disabled="disabled"').'>';
			}
		}

		print '</div>';	// end website selection

		print '<div class="websitetools">';

		if (($pageid > 0 && $atleastonepage) && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone'))
		{
			$realpage=$urlwithroot.'/public/website/index.php?website='.$websitekey.'&pageref='.$websitepage->pageurl;
			$pagealias = $websitepage->pageurl;

			$htmltext = $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage, $dataroot);
			$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
			$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias');

			print '<a class="websitebuttonsitepreview" id="previewpage" href="'.$realpage.'&nocache='.dol_now().'" class="button" target="tab'.$websitekey.'" alt="'.dol_escape_htmltag($htmltext).'">';
			print $form->textwithpicto('', $htmltext, 1, 'preview');
			print '</a>';       // View page in new Tab

			print '<div class="websiteinputurl" id="websiteinputpage">';
			print '<input type="text" id="previewpageurl" class="minwidth200imp" name="previewsite" value="'.$pagealias.'" disabled="disabled">';
			$htmltext=$langs->trans("PageNameAliasHelp", $langs->transnoentitiesnoconv("EditPageMeta"));
			print $form->textwithpicto('', $htmltext, 1, 'help', '', 0, 2, 'helppagealias');
			print '</div>';

			$urlext=$virtualurl.'/'.$pagealias.'.php';
			$urlint=$urlwithroot.'/public/website/index.php?website='.$websitekey;

			$htmltext = $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $dataroot, $virtualurl?$urlext:'<span class="error">'.$langs->trans("VirtualHostUrlNotDefined").'</span>');

			print '<a class="websitebuttonsitepreview'.($virtualurl?'':' websitebuttonsitepreviewdisabled cursornotallowed').'" id="previewpageext" href="'.$urlext.'" target="tab'.$websitekey.'ext" alt="'.dol_escape_htmltag($htmltext).'">';
			print $form->textwithpicto('', $htmltext, 1, 'preview_ext');
			print '</a>';
			//print '<input type="submit" class="button" name="previewpage" target="tab'.$websitekey.'"value="'.$langs->trans("ViewPageInNewTab").'">';

			// TODO Add js to save alias like we save virtual host name and use dynamic virtual host for url of id=previewpageext
		}
		if (! in_array($action, array('editcss','editmenu','file_manager','createsite','createcontainer','createpagefromclone')))
		{
			if (preg_match('/^create/', $action)) print '<input type="submit" id="savefile" class="button buttonforacesave" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
			if (preg_match('/^edit/', $action)) print '<input type="submit" id="savefile" class="button buttonforacesave" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
			if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
		}

		print '</div>';	// end websitetools

		print '<div class="websitehelp">';
		if (GETPOST('editsource', 'alpha') || GETPOST('editcontent', 'alpha'))
		{
			$htmltext=$langs->transnoentitiesnoconv("YouCanEditHtmlSource").'<br>';
            if ($conf->browser->layout == 'phone')
            {
                print $form->textwithpicto('', $htmltext, 1, 'help', 'inline-block', 1, 2, 'tooltipsubstitution');
            }
            else
            {
                print $form->textwithpicto($langs->trans("SyntaxHelp"), $htmltext, 1, 'help', 'inline-block', 1, 2, 'tooltipsubstitution');
            }
		}
		print '</div>';	// end websitehelp



		if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
		{
			// Adding jquery code to change on the fly url of preview ext
			if (! empty($conf->use_javascript_ajax))
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

	print '</div>';	// end current websitebar
}



$head = array();


/*
 * Edit Site HTML header and CSS
 */

if ($action == 'editcss')
{
	print '<div class="fiche">';

	print '<br>';
	if (GETPOST('editcss', 'alpha') || GETPOST('refreshpage', 'alpha'))
	{
		$csscontent = @file_get_contents($filecss);
		// Clean the php css file to remove php code and get only css part
		$csscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $csscontent);
	}
	else
	{
		$csscontent = GETPOST('WEBSITE_CSS_INLINE');
	}
	if (! trim($csscontent)) $csscontent='/* CSS content (all pages) */'."\n"."body.bodywebsite { margin: 0; font-family: 'Open Sans', sans-serif; }\n.bodywebsite h1 { margin-top: 0; margin-bottom: 0; padding: 10px;}";

	if (GETPOST('editcss', 'alpha') || GETPOST('refreshpage', 'alpha'))
	{
		$jscontent = @file_get_contents($filejs);
		// Clean the php js file to remove php code and get only js part
		$jscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $jscontent);
	}
	else
	{
		$jscontent = GETPOST('WEBSITE_JS_INLINE');
	}
	if (! trim($jscontent)) $jscontent='/* JS content (all pages) */'."\n";

	if (GETPOST('editcss', 'alpha') || GETPOST('refreshpage', 'alpha'))
	{
		$htmlheadercontent = @file_get_contents($filehtmlheader);
		// Clean the php htmlheader file to remove php code and get only html part
		$htmlheadercontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $htmlheadercontent);
	}
	else
	{
		$htmlheadercontent = GETPOST('WEBSITE_HTML_HEADER');
	}
	if (! trim($htmlheadercontent))
	{
		$htmlheadercontent ="<html>\n";
		$htmlheadercontent.=$htmlheadercontentdefault;
		$htmlheadercontent.="</html>";
	}
	else
	{
		$htmlheadercontent = preg_replace('/^\s*<html>/ims', '', $htmlheadercontent);
		$htmlheadercontent = preg_replace('/<\/html>\s*$/ims', '', $htmlheadercontent);
		$htmlheadercontent='<html>'."\n".trim($htmlheadercontent)."\n".'</html>';
	}

	if (GETPOST('editcss', 'alpha') || GETPOST('refreshpage', 'alpha'))
	{
		$robotcontent = @file_get_contents($filerobot);
		// Clean the php htmlheader file to remove php code and get only html part
		$robotcontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $robotcontent);
	}
	else
	{
		$robotcontent = GETPOST('WEBSITE_ROBOT');
	}
	if (! trim($robotcontent))
	{
		$robotcontent.="# Robot file. Generated with ".DOL_APPLICATION_TITLE."\n";
		$robotcontent.="User-agent: *\n";
		$robotcontent.="Allow: /public/\n";
		$robotcontent.="Disallow: /administrator/\n";
	}

	if (GETPOST('editcss', 'alpha') || GETPOST('refreshpage', 'alpha'))
	{
		$htaccesscontent = @file_get_contents($filehtaccess);
		// Clean the php htaccesscontent file to remove php code and get only html part
		$htaccesscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $htaccesscontent);
	}
	else
	{
		$htaccesscontent = GETPOST('WEBSITE_HTACCESS');
	}
	if (! trim($htaccesscontent))
	{
		$htaccesscontent.="# Order allow,deny\n";
		$htaccesscontent.="# Deny from all\n";
	}
	//else $htaccesscontent='<html>'."\n".$htaccesscontent."\n".'</html>';*/

	dol_fiche_head();

	print '<!-- Edit CSS -->'."\n";
	print '<table class="border" width="100%">';

	// Website
	print '<tr><td class="titlefieldcreate">';
	print $langs->trans('WebSite');
	print '</td><td>';
	print $websitekey;
	print '</td></tr>';

	// CSS file
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_CSS_INLINE');
	print '</td><td>';

	$doleditor=new DolEditor('WEBSITE_CSS_INLINE', $csscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'CSS', 'css');

	print '</td></tr>';

	// JS file
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_JS_INLINE');
	print '</td><td>';

	$doleditor=new DolEditor('WEBSITE_JS_INLINE', $jscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'JS', 'javascript');

	print '</td></tr>';

	// Common HTML header
	print '<tr><td class="tdtop">';
	$htmlhelp=$langs->trans("Example").' :<br>';
	$htmlhelp.=dol_htmlentitiesbr($htmlheadercontentdefault);
	print $form->textwithpicto($langs->trans('WEBSITE_HTML_HEADER'), $htmlhelp, 1, 'help', '', 0, 2, 'htmlheadertooltip');
	print '</td><td>';

	$doleditor=new DolEditor('WEBSITE_HTML_HEADER', $htmlheadercontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'HTML Header', 'html');

	print '</td></tr>';

	// Robot file
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_ROBOT');
	print '</td><td>';

	$doleditor=new DolEditor('WEBSITE_ROBOT', $robotcontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'Robot file', 'text');

	print '</td></tr>';

	// .htaccess
	print '<tr><td class="tdtop">';
	print $langs->trans('WEBSITE_HTACCESS');
	print '</td><td>';

	$doleditor=new DolEditor('WEBSITE_HTACCESS', $htaccesscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, $langs->trans("File").' .htaccess', 'text');

	print '</td></tr>';

	// VirtualHost
	print '<tr><td class="tdtop">';

	$htmltext = $langs->trans("SetHereVirtualHost", DOL_DATA_ROOT.'/website/<i>'.$websitekey.'</i>');
	$htmltext.='<br>';
	$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
	$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias');

	print $form->textwithpicto($langs->trans('Virtualhost'), $htmltext, 1, 'help', '', 0, 2, 'virtualhosttooltip');
	print '</td><td>';
	print '<input type="text" class="flat" value="'.(GETPOSTISSET('virtualhost') ? GETPOST('virtualhost', 'alpha') : $virtualurl).'" name="virtualhost">';
    print '</td>';

	print '</table>';

	dol_fiche_end();

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

    dol_fiche_head($head, 'card', $langs->trans("AddSite"), -1, 'globe');
    */
	if ($action == 'createcontainer') print load_fiche_titre($langs->trans("AddSite"));

	print '<!-- Add site -->'."\n";
	//print '<div class="fichecenter">';

	print '<table class="border" width="100%">';

	if (GETPOST('WEBSITE_REF'))         $siteref=GETPOST('WEBSITE_REF', 'alpha');
	if (GETPOST('WEBSITE_DESCRIPTION')) $sitedesc=GETPOST('WEBSITE_DESCRIPTION', 'alpha');

	print '<tr><td class="titlefieldcreate fieldrequired">';
	print $langs->trans('Ref');
	print '</td><td>';
	print '<input type="text" class="flat maxwidth300" name="WEBSITE_REF" value="'.dol_escape_htmltag($siteref).'">';
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('Description');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_DESCRIPTION" value="'.dol_escape_htmltag($sitedesc).'">';
	print '</td></tr>';

	print '<tr><td>';

	$htmltext = $langs->trans("SetHereVirtualHost", DOL_DATA_ROOT.'/website/<i>websiteref</i>');
	$htmltext.='<br>';
	$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("ReadPerm"), DOL_DOCUMENT_ROOT);
	$htmltext.='<br>'.$langs->trans("CheckVirtualHostPerms", $langs->transnoentitiesnoconv("WritePerm"), DOL_DATA_ROOT.'/website<br>'.DOL_DATA_ROOT.'/medias');

	print $form->textwithpicto($langs->trans('Virtualhost'), $htmltext, 1, 'help', '', 0, 2, 'virtualhosttooltip');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="virtualhost" value="'.dol_escape_htmltag(GETPOST('virtualhost', 'alpha')).'">';
	print '</td></tr>';


	print '</table>';

	if ($action == 'createsite')
	{
		print '<div class="center">';

		print '<input class="button" type="submit" name="addcontainer" value="'.$langs->trans("Create").'">';
		print '<input class="button" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

		print '</div>';
	}


	//print '</div>';

	//dol_fiche_end();

	print '</div>';

	print '<br>';
}

if ($action == 'importsite')
{
	print '<div class="fiche">';

	print '<br>';

	print load_fiche_titre($langs->trans("ImportSite"));

	dol_fiche_head(array(), '0', '', -1);

	print $langs->trans("ZipOfWebsitePackageToImport").'<br><br>';

	print '<input class="flat minwidth400" type="file" name="userfile[]" accept=".zip">';
	print '<input type="submit" class="button" name="buttonsubmitimportfile" value="'.dol_escape_htmltag($langs->trans("Upload")).'">';
	print '<input type="submit" class="button" name="preview" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';

	dol_fiche_end();

	print '</div>';

	print '<br>';
}

if ($action == 'editmeta' || $action == 'createcontainer')
{
	print '<div class="fiche">';

	print '<br>';

	/*$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/website/index.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AddPage");
   	$head[$h][2] = 'card';
	$h++;

    dol_fiche_head($head, 'card', $langs->trans("AddPage"), -1, 'globe');
    */
	if ($action == 'createcontainer') print load_fiche_titre($langs->trans("AddPage"));

	print '<!-- Edit or create page/container -->'."\n";
	//print '<div class="fichecenter">';

	if ($action == 'createcontainer')
	{
		print '<br>';

		print ' * '.$langs->trans("CreateByFetchingExternalPage").'<br><hr>';
		print '<table class="border" width="100%">';
		print '<tr><td class="titlefield">';
		print $langs->trans("URL");
		print '</td><td>';
		print info_admin($langs->trans("OnlyEditionOfSourceForGrabbedContentFuture"), 0, 0, 'warning');
		print '<input class="flat minwidth500" type="text" name="externalurl" value="'.dol_escape_htmltag(GETPOST('externalurl', 'alpha')).'" placeholder="https://externalsite/pagetofetch"> ';
		print '<br><input class="flat paddingtop" type="checkbox" name="grabimages" value="1" checked="checked"> '.$langs->trans("GrabImagesInto");
		print ' ';
		print $langs->trans("ImagesShouldBeSavedInto").' ';
		$arraygrabimagesinto=array('root'=>$langs->trans("WebsiteRootOfImages"), 'subpage'=>$langs->trans("SubdirOfPage"));
		print $form->selectarray('grabimagesinto', $arraygrabimagesinto, GETPOSTISSET('grabimagesinto')?GETPOST('grabimagesinto'):'root');
		print '<br>';
		print '<input class="button" style="margin-top: 5px" type="submit" name="fetchexternalurl" value="'.dol_escape_htmltag($langs->trans("FetchAndCreate")).'">';
		print '</td></tr>';
		print '</table>';

		print '<br>';

		print ' * '.$langs->trans("OrEnterPageInfoManually").'<br><hr>';
	}

	print '<table class="border" width="100%">';

	if ($action != 'createcontainer')
	{
		print '<tr><td class="titlefield">';
		print $langs->trans('IDOfPage');
		print '</td><td>';
		print $pageid;
		print '</td></tr>';

		print '<tr><td class="titlefield">';
		print $langs->trans('InternalURLOfPage');
		print '</td><td>';
		print '/public/website/index.php?website='.urlencode($websitekey).'&pageid='.urlencode($pageid);
		print '</td></tr>';

		/*
        print '<tr><td class="titlefield">';
        print $langs->trans('InitiallyGrabbedFrom');
        print '</td><td>';
        print $objectpage->grabbed_from;
        print '</td></tr>';
        */

		$type_container=$objectpage->type_container;
		$pageurl=$objectpage->pageurl;
		$pagealiasalt=$objectpage->aliasalt;
		$pagetitle=$objectpage->title;
		$pagedescription=$objectpage->description;
		$pageimage=$objectpage->image;
		$pagekeywords=$objectpage->keywords;
		$pagelang=$objectpage->lang;
		$pagehtmlheader=$objectpage->htmlheader;
		$pagedatecreation=$objectpage->date_creation;
		$pagedatemodification=$objectpage->date_modification;
		$pageauthorid=$objectpage->fk_user_creat;
		$pageusermodifid=$objectpage->fk_user_modif;
	}
	else
	{
		$type_container = 'page';
		$pagedatecreation=dol_now();
		$pageauthorid=$user->id;
		$pageusermodifid=0;
	}
	if (GETPOST('WEBSITE_TITLE', 'alpha'))       $pagetitle=GETPOST('WEBSITE_TITLE', 'alpha');
	if (GETPOST('WEBSITE_PAGENAME', 'alpha'))    $pageurl=GETPOST('WEBSITE_PAGENAME', 'alpha');
	if (GETPOST('WEBSITE_ALIASALT', 'alpha'))    $pagealiasalt=GETPOST('WEBSITE_ALIASALT', 'alpha');
	if (GETPOST('WEBSITE_DESCRIPTION', 'alpha')) $pagedescription=GETPOST('WEBSITE_DESCRIPTION', 'alpha');
	if (GETPOST('WEBSITE_IMAGE', 'alpha'))       $pageimage=GETPOST('WEBSITE_IMAGE', 'alpha');
	if (GETPOST('WEBSITE_KEYWORDS', 'alpha'))    $pagekeywords=GETPOST('WEBSITE_KEYWORDS', 'alpha');
	if (GETPOST('WEBSITE_LANG', 'aZ09'))         $pagelang=GETPOST('WEBSITE_LANG', 'aZ09');
	if (GETPOST('htmlheader', 'none'))			$pagehtmlheader=GETPOST('htmlheader', 'none');

	// Title
	print '<tr><td class="fieldrequired">';
	print $langs->trans('WEBSITE_TITLE');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_TITLE" id="WEBSITE_TITLE" value="'.dol_escape_htmltag($pagetitle).'">';
	print '</td></tr>';

	// Alias
	print '<tr><td class="titlefieldcreate fieldrequired">';
	print $langs->trans('WEBSITE_PAGENAME');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_PAGENAME" id="WEBSITE_PAGENAME" value="'.dol_escape_htmltag($pageurl).'">';
	print '</td></tr>';

	// Type of container
	print '<tr><td class="titlefield fieldrequired">';
	print $langs->trans('WEBSITE_TYPE_CONTAINER');
	print '</td><td>';
	print $formwebsite->selectTypeOfContainer('WEBSITE_TYPE_CONTAINER', (GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha')?GETPOST('WEBSITE_TYPE_CONTAINER', 'alpha'):$type_container));
	print '</td></tr>';

	if ($action == 'createcontainer')
	{
		print '<tr><td class="titlefield fieldrequired">';
		print $langs->trans('WEBSITE_PAGE_EXAMPLE');
		print '</td><td>';
		print $formwebsite->selectSampleOfContainer('sample', (GETPOST('sample', 'alpha')?GETPOST('sample', 'alpha'):'corporatehomepage'));
		print '</td></tr>';
	}

	print '<tr><td>';
	print $langs->trans('WEBSITE_DESCRIPTION');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_DESCRIPTION" value="'.dol_escape_htmltag($pagedescription).'">';
	print '</td></tr>';

	print '<tr><td>';
	$htmlhelp=$langs->trans("WEBSITE_IMAGEDesc");
	print $form->textwithpicto($langs->trans('WEBSITE_IMAGE'), $htmlhelp, 1, 'help', '', 0, 2, 'imagetooltip');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_IMAGE" value="'.dol_escape_htmltag($pageimage).'">';
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('WEBSITE_KEYWORDS');
	print '</td><td>';
	print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_KEYWORDS" value="'.dol_escape_htmltag($pagekeywords).'">';
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('Language');
	print '</td><td>';
	print $formadmin->select_language($pagelang?$pagelang:$langs->defaultlang, 'WEBSITE_LANG', 0, null, '1');
	print '</td></tr>';

	if ($action != 'createcontainer')
	{
		// Translation of
		if ($objectpage->fk_page > 0)
		{
			print '<tr><td>';
			print $langs->trans('ThisPageIsTranslationOf');
			print '</td><td>';
			$sourcepage=new WebsitePage($db);
			$result = $sourcepage->fetch($objectpage->fk_page);
			if ($result == 0)	// not found, we can reset value
			{

			}
			elseif ($result > 0)
			{
				print $sourcepage->getNomUrl(1);
			}
			print '</td></tr>';
		}

		// Has translation pages
		$sql='SELECT rowid, lang from '.MAIN_DB_PREFIX.'website_page where fk_page = '.$objectpage->id;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num_rows = $db->num_rows($resql);
			if ($num_rows > 0)
			{
				print '<tr><td>';
				print $langs->trans('ThisPageHasTranslationPages');
				print '</td><td>';
				$i=0;
				while ($obj = $db->fetch_object($resql))
				{
					$tmppage=new WebsitePage($db);
					$tmppage->fetch($obj->rowid);
					if ($i > 0) print ' - ';
					print $tmppage->getNomUrl(1).' ('.$tmppage->lang.')';
					$i++;
				}
				print '</td></tr>';
			}
		}
		else dol_print_error($db);
	}

	print '<tr><td class="titlefieldcreate">';
	$htmlhelp=$langs->trans("WEBSITE_ALIASALTDesc");
	print $form->textwithpicto($langs->trans('WEBSITE_ALIASALT'), $htmlhelp, 1, 'help', '', 0, 2, 'aliastooltip');
	print '</td><td>';
	print '<input type="text" class="flat minwidth300" name="WEBSITE_ALIASALT" value="'.dol_escape_htmltag($pagealiasalt).'">';
	print '</td></tr>';

	$fuser=new User($db);

	print '<tr><td>';
	print $langs->trans('Author');
	print '</td><td>';
	if ($pageauthorid > 0)
	{
	    $fuser->fetch($pageauthorid);
	    print $fuser->getNomUrl(1);
	}
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans('DateCreation');
	print '</td><td>';
	print $form->select_date($pagedatecreation, 'datecreation', 1, 1, 0, '', 1, 1);
	//print dol_print_date($pagedatecreation, 'dayhour');
	print '</td></tr>';

	if ($action != 'createcontainer')
	{
	    print '<tr><td>';
	    print $langs->trans('LastModificationAuthor');
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
	$htmlhelp =$langs->trans("EditTheWebSiteForACommonHeader").'<br><br>';
	$htmlhelp.=$langs->trans("Example").' :<br>';
	$htmlhelp.=dol_htmlentitiesbr($htmlheadercontentdefault);
	print $form->textwithpicto($langs->trans('HtmlHeaderPage'), $htmlhelp, 1, 'help', '', 0, 2, 'htmlheadertooltip');
	print '</td><td>';
	$doleditor=new DolEditor('htmlheader', $pagehtmlheader, '', '120', 'ace', 'In', true, false, 'ace', ROWS_3, '100%', '');
	print $doleditor->Create(1, '', true, 'HTML Header', 'html');
	print '</td></tr>';

	print '</table>';
	if ($action == 'createcontainer')
	{
		print '<div class="center">';

		print '<input class="button" type="submit" name="addcontainer" value="'.$langs->trans("Create").'">';
		print '<input class="button" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

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
						var valnospecial = jQuery("#WEBSITE_TITLE").val().replace(/[^\w]/gi, \'-\').toLowerCase();
						valnospecial = valnospecial.replace(/\-+/g, \'-\').replace(/\-$/, \'\');
						console.log("disableautofillofalias=0 so we replace WEBSITE_TITLE with "+valnospecial);
						jQuery("#WEBSITE_PAGENAME").val(valnospecial);
					}
				});
				jQuery("#WEBSITE_PAGENAME").keyup(function() {
					disableautofillofalias = 1;
				});
			});
			</script>';
	}
	//print '</div>';

	//dol_fiche_end();

	print '</div>';

	print '<br>';
}

if ($action == 'editfile' || $action == 'file_manager')
{
	print '<!-- Edit Media -->'."\n";
	print '<div class="fiche"><br><br>';
	//print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';

	$module = 'medias';
	if (empty($url)) $url=DOL_URL_ROOT.'/website/index.php';	// Must be an url without param
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
	$maxheightwin=480;
	if (isset($_SESSION["dol_screenheight"]))
	{
		if ($_SESSION["dol_screenheight"] > 680) $maxheightwin = $_SESSION["dol_screenheight"]-400;
		if ($_SESSION["dol_screenheight"] > 800) $maxheightwin = $_SESSION["dol_screenheight"]-490;
	}
	//var_dump($_SESSION["dol_screenheight"]);
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('PAGE_CONTENT', $contentforedit, '', $maxheightwin, 'Full', '', true, true, 'ace', ROWS_5, '40%');
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
		$out.="\n<html><head>\n";
		$out.="<!-- htmlheader/style of page from database -->\n";
		$out.=dolWebsiteReplacementOfLinks($object, $objectpage->htmlheader, 1);
		$out.="</head>\n";
		$out.="\n<body>";


		$out.='<div id="websitecontentundertopmenu" class="websitecontentundertopmenu boostrap-iso">'."\n";

		// REPLACEMENT OF LINKS When page called by website editor

		$out.='<!-- style of website from file -->'."\n";
		$out.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". No more supported by browsers, snif !
		$tmpout='';
		$tmpout.= '/* Include website CSS file */'."\n";
		//$csscontent = @file_get_contents($filecss);
		ob_start();
		include $filecss;
		$csscontent = ob_get_contents();
		ob_end_clean();
		$tmpout.= dolWebsiteReplacementOfLinks($object, $csscontent, 1);
		$tmpout.= '/* Include style from the HTML header of page */'."\n";
		// Clean the html header of page to get only <style> content
		$tmp = preg_split('(<style[^>]*>|</style>)', $objectpage->htmlheader);
		$tmpstyleinheader ='';
		$i=0;
		foreach($tmp as $valtmp)
		{
			$i++;
			if ($i % 2 == 0) $tmpstyleinheader.=$valtmp."\n";
		}
		$tmpout.= $tmpstyleinheader."\n";
		// Clean style that may affect global style of Dolibarr
		$tmpout=preg_replace('/}[\s\n]*body\s*{[^}]+}/ims', '}', $tmpout);
		$out.=$tmpout;
		$out.='</style>'."\n";

		// Note: <div> or <section> with contenteditable="true" inside this can be edited with inline ckeditor

		// Do not enable the contenteditable when page was grabbed, ckeditor is removing span and adding borders,
		// so editable will be available only from container created from scratch
		//$out.='<div id="bodywebsite" class="bodywebsite"'.($objectpage->grabbed_from ? ' contenteditable="true"' : '').'>'."\n";
		$out.='<div id="divbodywebsite" class="bodywebsite">'."\n";

		$newcontent = $objectpage->content;

		// If mode WEBSITE_SUBCONTAINERSINLINE is on
		if (! empty($conf->global->WEBSITE_SUBCONTAINERSINLINE))
		{
			define('USEDOLIBARREDITOR', 1);
			//var_dump($filetpl);
			$filephp = $filetpl;
			ob_start();
			include $filephp;
			$newcontent = ob_get_contents();
			ob_end_clean();
		}

		// Change the contenteditable to "true" or "false" when mode Edit Inline is on or off
		if (empty($conf->global->WEBSITE_EDITINLINE))
		{
			$newcontent = preg_replace('/(div|section)(\s[^\>]*)contenteditable="true"/', '\1\2', $newcontent);
		}
		else
		{
			// TODO Add the contenteditable="true" when mode Edit Inline is on
		}
		$out.=dolWebsiteReplacementOfLinks($object, $newcontent)."\n";
		//$out.=$newcontent;

		$out.='</div>';

		$out.='</div> <!-- End div id=websitecontentundertopmenu -->';

		/*if ($includepageintoaframeoradiv == 'iframe')
		{
			$out .= "</body></html></iframe>";
		}*/
		$out .= "\n</body></html>\n";

		$out.= "\n".'<!-- End page content '.$filetpl.' -->'."\n\n";

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
	else
	{
		if (empty($websitekey) || $websitekey == '-1')
		{
			print '<br><br><div class="center">'.$langs->trans("NoWebSiteCreateOneFirst").'</center><br><br><br>';
			print '<div class="center"><div class="logo_setup"></div></div>';
		}
		else
		{
			print '<br><br><div class="center">'.$langs->trans("PreviewOfSiteNotYetAvailable", $object->ref).'</center><br><br><br>';
			print '<div class="center"><div class="logo_setup"></div></div>';
		}
	}
}

// End of page
llxFooter();
$db->close();
