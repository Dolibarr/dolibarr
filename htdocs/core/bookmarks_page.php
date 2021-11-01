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
 *       \file       htdocs/core/bookmarks_page.php
 *       \brief      File to return a page with the complete list of bookmarks
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

$langs->loadLangs(array("bookmarks"));

$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');


/*
 * View
 */

$title = $langs->trans("Bookmarks");

// URL http://mydolibarr/core/bookmarks_page?dol_use_jmobile=1 can be used for tests
$head = '<!-- Bookmarks access -->'."\n";
$arrayofjs = array();
$arrayofcss = array();
top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);



print '<body>'."\n";
print '<div>';
//print '<br>';

// Instantiate hooks of thirdparty module
$hookmanager->initHooks(array('bookmarks'));

// Define $bookmarks
$bookmarkList = '';
$searchForm = '';


if (empty($conf->bookmarks->enabled)) {
	$langs->load("admin");
	$bookmarkList .= '<br><span class="opacitymedium">'.$langs->trans("WarningModuleNotActive", $langs->transnoentitiesnoconv("Bookmarks")).'</span>';
	$bookmarkList .= '<br><br>';
} else {
	// Menu with list of bookmarks
	$sql = "SELECT rowid, title, url, target FROM ".MAIN_DB_PREFIX."bookmark";
	$sql .= " WHERE (fk_user = ".((int) $user->id)." OR fk_user is NULL OR fk_user = 0)";
	$sql .= " AND entity IN (".getEntity('bookmarks').")";
	$sql .= " ORDER BY position";
	if ($resql = $db->query($sql)) {
		$bookmarkList = '<div id="dropdown-bookmarks-list" class="start">';
		$i = 0;
		while ((empty($conf->global->BOOKMARKS_SHOW_IN_MENU) || $i < $conf->global->BOOKMARKS_SHOW_IN_MENU) && $obj = $db->fetch_object($resql)) {
			$bookmarkList .= '<a class="dropdown-item bookmark-item'.(strpos($obj->url, 'http') === 0 ? ' bookmark-item-external' : '').'" id="bookmark-item-'.$obj->rowid.'" data-id="'.$obj->rowid.'" '.($obj->target == 1 ? ' target="_blank"' : '').' href="'.dol_escape_htmltag($obj->url).'" >';
			$bookmarkList .= dol_escape_htmltag($obj->title);
			$bookmarkList .= '</a>';
			$i++;
		}
		if ($i == 0) {
			$bookmarkList .= '<br><span class="opacitymedium">'.$langs->trans("NoBookmarks").'</span>';
			$bookmarkList .= '<br><br>';

			$newcardbutton = '';
			$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/bookmarks/card.php?action=create&backtopage='.urlencode(DOL_URL_ROOT.'/bookmarks/list.php'), '', !empty($user->rights->bookmark->creer));

			$bookmarkList .= '<center>'.$newcardbutton.'</center>';
		}
		$bookmarkList .= '</div>';


		$searchForm .= '<input name="bookmark" id="top-bookmark-search-input" class="dropdown-search-input" placeholder="'.$langs->trans('Bookmarks').'" autocomplete="off" >';
	} else {
		dol_print_error($db);
	}
}

// Execute hook printBookmarks
$parameters = array('bookmarks'=>$bookmarkList);
$reshook = $hookmanager->executeHooks('printBookmarks', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
	$bookmarkList .= $hookmanager->resPrint;
} else {
	$bookmarkList = $hookmanager->resPrint;
}


print "\n";
print "<!-- Begin Bookmarks list -->\n";
print '<div class="center"><div class="center" style="padding: 6px;">';
print '<style>.menu_titre { padding-top: 7px; }</style>';
print '<div id="blockvmenusearch" class="tagtable center searchpage">'."\n";
print $bookmarkList;
print '</div>'."\n";
print '</div></div>';
print "\n<!-- End SearchForm -->\n";

print '</div>';
print '</body></html>'."\n";

$db->close();
