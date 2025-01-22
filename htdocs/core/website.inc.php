<?php
/* Copyright (C) 2017-2024  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
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
* or see https://www.gnu.org/
*/

/**
 *	\file			htdocs/core/website.inc.php
 *  \brief			Common file loaded by all website pages (after master.inc.php). It sets the new object $weblangs.
 *  				This file is included in top of all container pages (in edit mode, in dolibarr web server mode and in external web server mode).
 *  				It is run only when a web page is called.
 *  			    The global variable $websitekey must be defined.
 */

// Load website class
include_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

$website = null;
$websitepage = null;
$weblangs = null;
$pagelangs = null;

// Detection browser (copy of code from main.inc.php)
if (isset($_SERVER["HTTP_USER_AGENT"]) && is_object($conf) && empty($conf->browser->name)) {
	$tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
	$conf->browser->name = $tmp['browsername'];
	$conf->browser->os = $tmp['browseros'];
	$conf->browser->version = $tmp['browserversion'];
	$conf->browser->layout = $tmp['layout']; // 'classic', 'phone', 'tablet'
	//var_dump($conf->browser);

	if ($conf->browser->layout == 'phone') {
		$conf->dol_no_mouse_hover = 1;
	}
}
// Define $website
if (is_null($website)) {
	$website = new Website($db);
	$website->fetch(0, $websitekey);
}
// Define $websitepage if we have $websitepagefile defined
if (empty($pageid) && !empty($websitepagefile)) {
	$pageid = str_replace(array('.tpl.php', 'page'), array('', ''), basename($websitepagefile));
	if ($pageid == 'index.php') {
		$pageid = $website->fk_default_home;
	}
}
if (is_null($websitepage)) {
	$websitepage = new WebsitePage($db);
}
// Define $weblangs
if (is_null($weblangs)) {
	$weblangs = new Translate('', $conf);
}
if (is_null($pagelangs)) {
	$pagelangs = new Translate('', $conf);
}
if (!empty($pageid) && $pageid > 0) {
	$websitepage->fetch($pageid);

	// Rule to define weblang of visitor:
	// 1 - Take parameter lang
	// 2 - Cookie lang of website (set by a possible js lang selector)
	// 3 - XX/... found in url page
	// 4 - auto (so web browser lang)
	$srclang = GETPOSTISSET('lang') ? GETPOST('lang', 'aZ09') : '';
	if (empty($srclang)) {
		$srclang = (empty($_COOKIE['weblangs-shortcode']) ? '' : preg_replace('/[^a-zA-Z0-9_\-]/', '', $_COOKIE['weblangs-shortcode']));
	}
	if (empty($srclang)) {
		$reg = array();
		// With Dolibarr server, url is in parameter pageref
		if (defined('USEDOLIBARRSERVER') && !empty($_GET['pageref']) && preg_match('/^\/?(\w\w)\//', $_GET['pageref'], $reg) && $reg[1] != 'js') {	// We reuse $_GET['pageref'] because $pageref may have been cleaned already from the language code.
			$srclang = $reg[1];
		}
		// With External server, url is in parameter pageref
		if (defined('USEEXTERNALSERVER') && !empty($_SERVER['PHP_SELF']) && preg_match('/^\/?(\w\w)\//', $_SERVER['PHP_SELF'], $reg) && $reg[1] != 'js') {
			$srclang = $reg[1];
		}
	}
	if (empty($srclang)) {
		$srclang= 'auto';
	}
	$weblangs->setDefaultLang($srclang);

	$pagelangs->setDefaultLang($websitepage->lang ? $websitepage->lang : $weblangs->shortlang);

	if (!defined('USEDOLIBARREDITOR') && (in_array($websitepage->type_container, array('menu', 'other')) || empty($websitepage->status) && !defined('USEDOLIBARRSERVER'))) {
		$weblangs->load("website");

		// Security options

		// X-Content-Type-Options
		header("X-Content-Type-Options: nosniff");

		// X-Frame-Options
		if (empty($websitepage->allowed_in_frames) && !getDolGlobalString('WEBSITE_ALLOW_FRAMES_ON_ALL_PAGES')) {
			header("X-Frame-Options: SAMEORIGIN");
		}

		//httponly_accessforbidden('<center><br><br>'.$weblangs->trans("YouTryToAccessToAFileThatIsNotAWebsitePage", $websitepage->pageurl, $websitepage->type_container, $websitepage->status).'</center>', 404, 1);
		http_response_code(404);
		print '<center><br><br>'.$weblangs->trans("YouTryToAccessToAFileThatIsNotAWebsitePage", $websitepage->pageurl, $websitepage->type_container, $websitepage->status).'</center>';
		exit;
	}
}

if (!defined('USEDOLIBARRSERVER') && !defined('USEDOLIBARREDITOR')) {
	// Security options

	// X-Content-Type-Options
	header("X-Content-Type-Options: nosniff");

	// X-Frame-Options
	if (empty($websitepage->allowed_in_frames) && !getDolGlobalString('WEBSITE_ALLOW_FRAMES_ON_ALL_PAGES')) {
		header("X-Frame-Options: SAMEORIGIN");
	}

	// X-XSS-Protection
	//header("X-XSS-Protection: 1");      		// XSS filtering protection of some browsers (note: use of Content-Security-Policy is more efficient). Disabled as deprecated.

	// Content-Security-Policy-Report-Only
	if (!defined('WEBSITE_MAIN_SECURITY_FORCECSPRO')) {
		// A default security policy that keep usage of js external component like ckeditor, stripe, google, working
		// For example: to restrict to only local resources, except for css (cloudflare+google), and js (transifex + google tags) and object/iframe (youtube)
		// default-src 'self'; style-src: https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src: https://cdn.transifex.com https://www.googletagmanager.com; object-src https://youtube.com; frame-src https://youtube.com; img-src: *;
		// For example, to restrict everything to itself except img that can be on other servers:
		// default-src 'self'; img-src *;
		// Pre-existing site that uses too much js code to fix but wants to ensure resources are loaded only over https and disable plugins:
		// default-src https: 'unsafe-inline' 'unsafe-eval'; object-src 'none'
		//
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com;";
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src *; script-src 'self' 'unsafe-inline' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com; style-src 'self' 'unsafe-inline'; connect-src 'self';";
		$contentsecuritypolicy = getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCECSPRO');

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		$hookmanager->initHooks(array("main"));

		$parameters = array('contentsecuritypolicy'=>$contentsecuritypolicy, 'mode'=>'reportonly');
		$result = $hookmanager->executeHooks('setContentSecurityPolicy', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($result > 0) {
			$contentsecuritypolicy = $hookmanager->resPrint; // Replace CSP
		} else {
			$contentsecuritypolicy .= $hookmanager->resPrint; // Concat CSP
		}

		if (!empty($contentsecuritypolicy)) {
			header("Content-Security-Policy-Report-Only: ".$contentsecuritypolicy);
		}
	}

	// Content-Security-Policy
	if (!defined('WEBSITE_MAIN_SECURITY_FORCECSP')) {
		// A default security policy that keep usage of js external component like ckeditor, stripe, google, working
		// For example: to restrict to only local resources, except for css (cloudflare+google), and js (transifex + google tags) and object/iframe (youtube)
		// default-src 'self'; style-src: https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src: https://cdn.transifex.com https://www.googletagmanager.com; object-src https://youtube.com; frame-src https://youtube.com; img-src: *;
		// For example, to restrict everything to itself except img that can be on other servers:
		// default-src 'self'; img-src *;
		// Pre-existing site that uses too much js code to fix but wants to ensure resources are loaded only over https and disable plugins:
		// default-src https: 'unsafe-inline' 'unsafe-eval'; object-src 'none'
		//
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com;";
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src *; script-src 'self' 'unsafe-inline' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com; style-src 'self' 'unsafe-inline'; connect-src 'self';";
		$contentsecuritypolicy = getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCECSP');

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		$hookmanager->initHooks(array("main"));

		$parameters = array('contentsecuritypolicy'=>$contentsecuritypolicy, 'mode'=>'active');
		$result = $hookmanager->executeHooks('setContentSecurityPolicy', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($result > 0) {
			$contentsecuritypolicy = $hookmanager->resPrint; // Replace CSP
		} else {
			$contentsecuritypolicy .= $hookmanager->resPrint; // Concat CSP
		}

		if (!empty($contentsecuritypolicy)) {
			header("Content-Security-Policy: ".$contentsecuritypolicy);
		}
	}

	// Referrer-Policy
	if (!defined('WEBSITE_MAIN_SECURITY_FORCERP')) {
		// The constant WEBSITE_MAIN_SECURITY_FORCERP should never be defined by page, but the variable used just after may be

		// For public web sites, we use the same default value than "strict-origin-when-cross-origin"
		$referrerpolicy = getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCERP', "strict-origin-when-cross-origin");

		header("Referrer-Policy: ".$referrerpolicy);
	}

	// Strict-Transport-Security
	if (!defined('WEBSITE_MAIN_SECURITY_FORCESTS')) {
		// The constant WEBSITE_MAIN_SECURITY_FORCESTS should never be defined by page, but the variable used just after may be

		// Example: "max-age=31536000; includeSubDomains"
		$sts = getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCESTS');
		if (!empty($sts)) {
			header("Strict-Transport-Security: ".$sts);
		}
	}

	// Permissions-Policy (old name was Feature-Policy)
	if (!defined('WEBSITE_MAIN_SECURITY_FORCEPP')) {
		// The constant WEBSITE_MAIN_SECURITY_FORCEPP should never be defined by page, but the variable used just after may be

		// Example: "camera: 'none'; microphone: 'none';"
		$pp = getDolGlobalString('WEBSITE_MAIN_SECURITY_FORCEPP');
		if (!empty($pp)) {
			header("Permissions-Policy: ".$pp);
		}
	}
}

// A lang was forced, so we change weblangs init
if (GETPOST('l', 'aZ09')) {
	$weblangs->setDefaultLang(GETPOST('l', 'aZ09'));
}
// A lang was forced, so we check to find if we must make a redirect on translation page
if ($_SERVER['PHP_SELF'] != DOL_URL_ROOT.'/website/index.php') {	// If we browsing page using Dolibarr server or a Native web server
	//print_r(get_defined_constants(true));exit;
	if (GETPOST('l', 'aZ09')) {
		$sql = "SELECT wp.rowid, wp.lang, wp.pageurl, wp.fk_page";
		$sql .= " FROM ".MAIN_DB_PREFIX."website_page as wp";
		$sql .= " WHERE wp.fk_website = ".((int) $website->id);
		$sql .= " AND (wp.fk_page = ".((int) $pageid)." OR wp.rowid  = ".((int) $pageid);
		if (is_object($websitepage) && $websitepage->fk_page > 0) {
			$sql .= " OR wp.fk_page = ".((int) $websitepage->fk_page)." OR wp.rowid = ".((int) $websitepage->fk_page);
		}
		$sql .= ")";
		$sql .= " AND wp.lang = '".$db->escape(GETPOST('l', 'aZ09'))."'";

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$newpageid = $obj->rowid;
				if ($newpageid != $pageid) { 		// To avoid to make a redirect on same page (infinite loop)
					if (defined('USEDOLIBARRSERVER')) {
						header("Location: ".DOL_URL_ROOT.'/public/website/index.php?website='.$websitekey.'&pageid='.$newpageid.'&l='.GETPOST('l', 'aZ09'));
						exit;
					} else {
						$newpageref = $obj->pageurl;
						header("Location: ".(($obj->lang && $obj->lang != $website->lang) ? '/'.$obj->lang.'/' : '/').$newpageref.'.php?l='.GETPOST('l', 'aZ09'));
						exit;
					}
				}
			}
		}
	}
}

// Show off line message when all website is off
if (!defined('USEDOLIBARREDITOR') && empty($website->status)) {
	// Security options

	// X-Content-Type-Options
	header("X-Content-Type-Options: nosniff");

	// X-Frame-Options
	if (empty($websitepage->allowed_in_frames) && !getDolGlobalString('WEBSITE_ALLOW_FRAMES_ON_ALL_PAGES')) {
		header("X-Frame-Options: SAMEORIGIN");
	}

	$weblangs->load("website");

	//httponly_accessforbidden('<center><br><br>'.$weblangs->trans("SorryWebsiteIsCurrentlyOffLine").'</center>', 503, 1);
	http_response_code(503);
	print '<center><br><br>'.$weblangs->trans("SorryWebsiteIsCurrentlyOffLine").'</center>';
	exit;
}


// Get session info and obfuscate session cookie and other variables
$prefix = dol_getprefix('');
$sessionname = 'DOLSESSID_'.$prefix;
//$savsessionid = $_COOKIE[$sessionname];

$_COOKIE[$sessionname] = 'obfuscatedcookie';
unset($conf->file->instance_unique_id);

unset($dolibarr_main_instance_unique_id);
unset($dolibarr_main_db_host);
unset($dolibarr_main_db_port);
unset($dolibarr_main_db_name);
unset($dolibarr_main_db_user);
unset($dolibarr_main_db_pass);
unset($dolibarr_main_db_type);
unset($dolibarr_main_document_root);
unset($dolibarr_main_document_root_alt);
