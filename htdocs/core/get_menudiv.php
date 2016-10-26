<?php
/* Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/get_menudiv.php
 *       \brief      File to return menu into a div tree
 */

//if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');		// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
//if (! defined('NOLOGIN')) define('NOLOGIN',1);					// Not disabled cause need to load personalized language
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML',1);

require_once '../main.inc.php';

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));	// If language was forced on URL by the main.inc.php
$langs->load("main");
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');


/*
 * View
 */

$title=$langs->trans("Menu");

// URL http://mydolibarr/core/get_menudiv.php?dol_use_jmobile=1 can be used for tests
$head='<!-- Menu -->'."\n";
$arrayofjs=array();
$arrayofcss=array();
top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);

print '<body>'."\n";

if (empty($user->societe_id))	// If internal user or not defined
{
	$conf->standard_menu=(empty($conf->global->MAIN_MENU_STANDARD_FORCED)?(empty($conf->global->MAIN_MENU_STANDARD)?'eldy_menu.php':$conf->global->MAIN_MENU_STANDARD):$conf->global->MAIN_MENU_STANDARD_FORCED);
}
else                        	// If external user
{
	$conf->standard_menu=(empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED)?(empty($conf->global->MAIN_MENUFRONT_STANDARD)?'eldy_menu.php':$conf->global->MAIN_MENUFRONT_STANDARD):$conf->global->MAIN_MENUFRONT_STANDARD_FORCED);
}

// Load the menu manager (only if not already done)
$file_menu=$conf->standard_menu;
if (GETPOST('menu')) $file_menu=GETPOST('menu');     // example: menu=eldy_menu.php
if (! class_exists('MenuManager'))
{
	$menufound=0;
	$dirmenus=array_merge(array("/core/menus/"),(array) $conf->modules_parts['menus']);
	foreach($dirmenus as $dirmenu)
	{
		$menufound=dol_include_once($dirmenu."standard/".$file_menu);
		if ($menufound) break;
	}
	if (! $menufound)	// If failed to include, we try with standard
	{
		dol_syslog("You define a menu manager '".$file_menu."' that can not be loaded.", LOG_WARNING);
		$file_menu='eldy_menu.php';
		include_once DOL_DOCUMENT_ROOT."/core/menus/standard/".$file_menu;
	}
}
$menumanager = new MenuManager($db, empty($user->societe_id)?0:1);
$menumanager->loadMenu('all','all');
//var_dump($menumanager->tabMenu);exit;
$menumanager->showmenu('jmobile');

print '</body>';

print '</html>'."\n";

$db->close();
