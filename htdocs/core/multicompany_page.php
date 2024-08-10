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
 *       \file       htdocs/core/multicompany_page.php
 *       \brief      File to return a page with the list of all entities user can switch to
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

$action = GETPOST('action', 'aZ');
$entityid = GETPOST('entity', 'int');
$backtourl = GETPOST('backtourl');
if (empty($backtourl)) {
	$backtourl = DOL_URL_ROOT;
}

if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php
}

$langs->load("main");

$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');



/*
 * Actions
 */

if ($action == 'switchentity') {
	if (is_object($mc)) {
		$mc->switchEntity($entityid);
	}

	header("Location: ".$backtourl);
	exit(0);
}



/*
 * View
 */

$title = $langs->trans("Multicompanies");

// URL http://mydolibarr/core/multicompany_page?dol_use_jmobile=1 can be used for tests
$head = '<!-- Multicompany selection -->'."\n";	// This is used by DoliDroid to know page is a multicompany selection page
$arrayofjs = array();
$arrayofcss = array();
top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);


print '<body>'."\n";
print '<div>';
//print '<br>';

// Define $multicompanyList
$multicompanyList = '';

if (!isModEnabled('multicompany')) {
	$langs->load("admin");
	$multicompanyList .= '<br><span class="opacitymedium">'.$langs->trans("WarningModuleNotActive", $langs->transnoentitiesnoconv("MultiCompany")).'</span>';
	$multicompanyList .= '<br><br>';
} elseif (!empty($user->entity) && !getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE')) { // Should not be accessible if the option to centralize users on the main entity is not activated
	$langs->load("errors");
	$multicompanyList .= '<br><span class="opacitymedium">'.$langs->trans("ErrorForbidden").'</span>';
	$multicompanyList .= '<br><br>';
} else {
	// Instantiate hooks of thirdparty module
	$hookmanager->initHooks(array('multicompany'));

	if (is_object($mc)) {
		$listofentities = $mc->getEntitiesList($user->login, false, true);
	}

	$multicompanyList .= '<ul class="ullistonly left" style="list-style: none;">';
	foreach ($listofentities as $entityid => $entitycursor) {
		$url = DOL_URL_ROOT.'/core/multicompany_page.php?action=switchentity&token='.newToken().'&entity='.((int) $entityid).($backtourl ? '&backtourl='.urlencode($backtourl) : '');
		$multicompanyList .= '<li class="lilistonly" style="height: 2.5em; font-size: 1.15em;">';
		$multicompanyList .= '<a class="dropdown-item multicompany-item" id="multicompany-item-'.$entityid.'" data-id="'.$entityid.'" href="'.dol_escape_htmltag($url).'">';
		$multicompanyList .= img_picto('', 'entity', 'class="pictofixedwidth"');
		$multicompanyList .= dol_escape_htmltag($entitycursor);
		if ($conf->entity == $entityid) {
			$multicompanyList .= ' <span class="opacitymedium">('.$langs->trans("Currently").')</span>';
		}
		$multicompanyList .= '</a>';
		$multicompanyList .= '</li>';
	}
	$multicompanyList .= '</ul>';

	// Execute hook printBookmarks
	$parameters = array('multicompany'=>$multicompanyList);
	$reshook = $hookmanager->executeHooks('printMultiCompanyEntities', $parameters); // Note that $action and $object may have been modified by some hooks
	if (empty($reshook)) {
		$multicompanyList .= $hookmanager->resPrint;
	} else {
		$multicompanyList = $hookmanager->resPrint;
	}
}

print "\n";
print "<!-- Begin Multicompany list -->\n";
print '<div class="center"><div class="center" style="padding: 6px;">';
print '<style>.menu_titre { padding-top: 7px; }</style>';
print '<div id="blockvmenusearch" class="tagtable center searchpage">'."\n";
print $multicompanyList;
print '</div>'."\n";
print '</div></div>';
print "\n<!-- End Multicompany list -->\n";

print '</div>';
print '</body></html>'."\n";

$db->close();
