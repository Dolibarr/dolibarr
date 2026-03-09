<?php
/* Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
 *       \file       htdocs/core/search_page.php
 *       \brief      File to return a page with the complete search form (all search input fields)
 */

//if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');		// Not disabled cause need to do translations
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
//if (! defined('NOLOGIN')) define('NOLOGIN',1);					// Not disabled cause need to load personalized language
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML',1);

require_once '../main.inc.php';

if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php
}

$langs->loadLangs(array("main", "other"));

$action = GETPOST('action', 'aZ09');

/*$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');*/


/*
 * Actions
 */

if ($action == 'redirect') {
	global $dolibarr_main_url_root;

	$url = GETPOST('url');
	$url = dol_sanitizeUrl($url);
	//$url = preg_replace('/^http(s?):\/\//i', '', $url);

	//var_dump($url);

	$tmpurlrootwithouthttp = preg_replace('/^http(s?):\/\//i', '', DOL_MAIN_URL_ROOT);
	//var_dump($dolibarr_main_url_root);
	//var_dump(DOL_MAIN_URL_ROOT);
	//var_dump($tmpurlrootwithouthttp);
	$url = preg_replace('/'.preg_quote($dolibarr_main_url_root, '/').'/', '', $url);
	$url = preg_replace('/'.preg_quote(DOL_MAIN_URL_ROOT, '/').'/', '', $url);
	$url = preg_replace('/'.preg_quote($tmpurlrootwithouthttp, '/').'/', '', $url);
	$urlrelativeforredirect = (DOL_URL_ROOT.(preg_match('/\//', $url) ? '' : '/').$url);
	//$urlrelativeforredirectwithoutparam = preg_replace('/\?.*$/', '', $urlrelativeforredirect);
	//var_dump($urlrelativeforredirect);

	dol_syslog("Ask search form to redirect on URL: ".$urlrelativeforredirect);
	header("Location: ".$urlrelativeforredirect);
	exit;
}


/*
 * View
 */

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache) && GETPOSTINT('cache')) {
	header('Cache-Control: max-age='.GETPOSTINT('cache').', public');
	// For a .php, we must set an Expires to avoid to have it forced to an expired value by the web server
	header('Expires: '.gmdate('D, d M Y H:i:s', dol_now('gmt') + GETPOSTINT('cache')).' GMT');
	// HTTP/1.0
	header('Pragma: token=public');
} else {
	// HTTP/1.0
	header('Cache-Control: no-cache');
}

$title = $langs->trans("Search");

// URL http://mydolibarr/core/search_page?dol_use_jmobile=1 can be used for tests
$head = '<!-- Quick access -->'."\n";	// This is used by DoliDroid to know page is a search page
$arrayofjs = array();
$arrayofcss = array();
top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);



print '<body>'."\n";
print '<div>';
//print '<br>';

$nbofsearch = 0;

// Instantiate hooks of thirdparty module
$hookmanager->initHooks(array('searchform'));

// Define $searchform
$searchform = '';

if ($conf->use_javascript_ajax && 1 == 2) {   // select2 is not best with smartphone
	if (!is_object($form)) {
		$form = new Form($db);
	}
	$selected = -1;
	$searchform .= '<br><br>'.$form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT.'/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'minwidth300', 1, $langs->trans("Search"), 0);
} else {
	$usedbyinclude = 1; // Used into next include
	$showtitlebefore = GETPOSTINT('showtitlebefore');
	$arrayresult = array();
	include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php';

	$i = 0;
	$accesskeyalreadyassigned = array();
	foreach ($arrayresult as $key => $val) {
		$tmp = explode('?', $val['url']);
		$urlaction = $tmp[0];
		$keysearch = 'search_all';

		$accesskey = '';
		if (empty($accesskeyalreadyassigned[$val['label'][0]])) {
			$accesskey = $val['label'][0];	// First char of string
			$accesskeyalreadyassigned[$accesskey] = $accesskey;
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$searchform .= printSearchForm($urlaction, $urlaction, $val['label'], 'minwidth200', $keysearch, $accesskey, $key, $val['img'], $showtitlebefore, ($i > 0 ? 0 : 1));

		$i++;
	}
}


// Execute hook printSearchForm
$parameters = array('searchform'=>$searchform);
$reshook = $hookmanager->executeHooks('printSearchForm', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
	$searchform .= $hookmanager->resPrint;
} else {
	$searchform = $hookmanager->resPrint;
}

$searchform .= '<br>';


// Add search on URL
if ($conf->dol_use_jmobile) {
	$ret = '';
	$ret .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" class="searchform nowraponall tagtr">';
	$ret .= '<input type="hidden" name="token" value="'.newToken().'">';
	$ret .= '<input type="hidden" name="savelogin" value="'.dol_escape_htmltag($user->login).'">';
	$ret .= '<input type="hidden" name="action" value="redirect">';
	$ret .= '<div class="tagtd">';
	$ret .= img_picto('', 'url', '', false, 0, 0, '', 'paddingright width20');
	$ret .= '<input type="text" class="flat minwidth200"';
	$ret .= ' style="background-repeat: no-repeat; background-position: 3px;"';
	$ret .= ' placeholder="'.strip_tags($langs->trans("OrPasteAnURL")).'"';
	$ret .= ' name="url" id="url" />';
	$ret .= '<button type="submit" class="button bordertransp" style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px">';
	$ret .= '<span class="fa fa-search"></span>';
	$ret .= '</button>';
	$ret .= '</div>';
	$ret .= "</form>\n";

	$searchform .= $ret;
}


// Show all forms
print "\n";
print "<!-- Begin SearchForm -->\n";
print '<div class="center"><div class="center" style="padding: 30px;">';
print '<style>.menu_titre { padding-top: 7px; }</style>';
print '<div id="blockvmenusearch" class="tagtable center searchpage">'."\n";
print $searchform;
print '</div>'."\n";
print '</div></div>';
print "\n<!-- End SearchForm -->\n";


print '</div>';
print '</body></html>'."\n";

$db->close();
