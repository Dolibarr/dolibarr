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
 *       \brief      File to return a page with search boxes
 */

//if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');		// Not disabled cause need to do translations
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
//if (! defined('NOLOGIN')) define('NOLOGIN',1);					// Not disabled cause need to load personalized language
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML',1);

require_once '../main.inc.php';

if (GETPOST('lang', 'aZ09')) $langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php

$langs->load("main");

$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');


/*
 * View
 */

$title = $langs->trans("Search");

// URL http://mydolibarr/core/search_page?dol_use_jmobile=1 can be used for tests
$head = '<!-- Quick access -->'."\n";
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

if ($conf->use_javascript_ajax && 1 == 2)   // select2 is ko with jmobile
{
	if (!is_object($form)) $form = new Form($db);
	$selected = -1;
	$searchform .= '<br><br>'.$form->selectArrayAjax('searchselectcombo', DOL_URL_ROOT.'/core/ajax/selectsearchbox.php', $selected, '', '', 0, 1, 'minwidth300', 1, $langs->trans("Search"), 0);
} else {
	$usedbyinclude = 1; // Used into next include
	$showtitlebefore = GETPOST('showtitlebefore', 'int');
	$arrayresult = array();
	include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php';

	$i = 0;
	$accesskeyalreadyassigned = array();
	foreach ($arrayresult as $key => $val)
	{
		$tmp = explode('?', $val['url']);
		$urlaction = $tmp[0];
		$keysearch = 'search_all';

		$accesskey = '';
		if (!$accesskeyalreadyassigned[$val['label'][0]])
		{
			$accesskey = $val['label'][0];
			$accesskeyalreadyassigned[$accesskey] = $accesskey;
		}

		$searchform .= printSearchForm($urlaction, $urlaction, $val['label'], 'minwidth200', $keysearch, $accesskey, $key, $val['img'], $showtitlebefore, ($i > 0 ? 0 : 1));

		$i++;
	}
}

// Execute hook printSearchForm
$parameters = array('searchform'=>$searchform);
$reshook = $hookmanager->executeHooks('printSearchForm', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook))
{
	$searchform .= $hookmanager->resPrint;
} else $searchform = $hookmanager->resPrint;


print "\n";
print "<!-- Begin SearchForm -->\n";
print '<div class="center"><div class="center" style="padding: 6px;">';
print '<style>.menu_titre { padding-top: 7px; }</style>';
print '<div id="blockvmenusearch" class="tagtable center searchpage">'."\n";
print $searchform;
print '</div>'."\n";
print '</div></div>';
print "\n<!-- End SearchForm -->\n";

print '</div>';
print '</body></html>'."\n";

$db->close();
