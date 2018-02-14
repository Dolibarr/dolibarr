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

if (! defined('DISABLE_JQUERY_TABLEDND'))   define('DISABLE_JQUERY_TABLEDND',1);
if (! defined('DISABLE_JQUERY_JNOTIFY'))    define('DISABLE_JQUERY_JNOTIFY',1);
if (! defined('DISABLE_JQUERY_FLOT'))       define('DISABLE_JQUERY_FLOT',1);
if (! defined('DISABLE_JQUERY_JEDITABLE'))  define('DISABLE_JQUERY_JEDITABLE',1);
if (! defined('DISABLE_JQUERY_JEDITABLE'))  define('DISABLE_JQUERY_JEDITABLE',1);
if (! defined('DISABLE_CKEDITOR'))          define('DISABLE_CKEDITOR',1);
if (! defined('DISABLE_CKEDITOR'))          define('DISABLE_CKEDITOR',1);
if (! defined('DISABLE_BROWSER_NOTIF'))     define('DISABLE_BROWSER_NOTIF',1);
if (! defined('DISABLE_DATE_PICKER'))       define('DISABLE_DATE_PICKER',1);
if (! defined('DISABLE_SELECT2'))           define('DISABLE_SELECT2',1);

require_once '../main.inc.php';

if (GETPOST('lang', 'aZ09')) $langs->setDefaultLang(GETPOST('lang', 'aZ09'));	// If language was forced on URL by the main.inc.php

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

// Javascript to make menu active like Jmobile did.
print '
<style>
    /*Lets hide the non active LIs by default*/
    body {
        font-size: 16px;
    }
    body ul {
        margin: 0;
        padding-left: 0;
    }
    body ul li {
        list-style: none;
    }
    body ul ul {
        display: none;
    }

    a.alilevel0 {
        background-image: url(\''.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/next.png\') !important;
        background-repeat: no-repeat !important;
        background-position-x: 10px;
        background-position-y: 16px;
        padding: 1em 15px 1em 40px;
    }
    li.lilevel0 font.vsmenudisabled {
        /* background-image: url(/dolibarr_dev/htdocs/theme/eldy/img/next.png) !important; */
        background-repeat: no-repeat !important;
        background-position-x: 10px;
        background-position-y: 16px;
        padding: 1em 15px 1em 40px;
        background: #f8f8f8;
        display: block;
        font-size: 16px !important;
    }
    li.lilevel1 {
        padding: 1em 15px 0.5em 40px;
        border-top: 1px solid #aaa;
        margin-right: 0px;
    	margin-left: 0px;
        border-right: 0px ! important;
    }
    li.lilevel1:first-child {
        margin-right: 0px;
        margin-left: 0px;
    }
    li.lilevel1 a {
        padding-bottom: 5px;
    }
    li.lilevel1 a, li.lilevel1 {
        color: #000;
        cursor: pointer;
        display: block;
    }
    li.lilevel2 a {
        padding: 0.7em 15px 0.7em 40px;
        color: #000;
        cursor: pointer;
        display: block;
    }
    li.lilevel3 a {
        padding: 0.2em 15px 0.2em 60px;
        color: #000;
        cursor: pointer;
        display: block;
    }
    li.lilevel4 a {
        padding: 0.2em 15px 8px 60px;
        color: #000;
        cursor: pointer;
        display: block;
    }
    li.lilevel5 a {
        padding: 0.2em 15px 0.2em 60px;
        color: #000;
        cursor: pointer;
        display: block;
    }
    li.lilevel3:last-child {
        padding-bottom: 10px;
    }
    a.alilevel0, li.lilevel1 a {
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        display: block;
    }
</style>

<script type="text/javascript">
$(document).ready(function(){
    $("body ul").click(function(){
        console.log("We click on body ul");

        $(this).siblings().find("li ul").slideUp(0);

        $(this).find("li ul").slideToggle(200);

        target = $(this);
        $(\'html, body\').animate({
          scrollTop: target.offset().top
        }, 300);

    })
});
</script>
';


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
//var_dump($menumanager);exit;
$menumanager->showmenu('jmobile');

print '</body>';

print '</html>'."\n";

$db->close();
