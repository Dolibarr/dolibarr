<?php
/* Copyright (C) 2005-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/core/get_menudiv.php
 *       \brief      File to return menu into a div tree, to be used by other frontend
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
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', 1);
}

if (!defined('DISABLE_JQUERY_TABLEDND')) {
	define('DISABLE_JQUERY_TABLEDND', 1);
}
if (!defined('DISABLE_JQUERY_JNOTIFY')) {
	define('DISABLE_JQUERY_JNOTIFY', 1);
}
if (!defined('DISABLE_JQUERY_FLOT')) {
	define('DISABLE_JQUERY_FLOT', 1);
}
if (!defined('DISABLE_JQUERY_JEDITABLE')) {
	define('DISABLE_JQUERY_JEDITABLE', 1);
}
if (!defined('DISABLE_CKEDITOR')) {
	define('DISABLE_CKEDITOR', 1);
}
if (!defined('DISABLE_DATE_PICKER')) {
	define('DISABLE_DATE_PICKER', 1);
}
if (!defined('DISABLE_SELECT2')) {
	define('DISABLE_SELECT2', 1);
}

require_once '../main.inc.php';

if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php
}

$langs->load("main");
$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');


/*
 * View
 */

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache) && GETPOSTINT('cache')) {
	header('Cache-Control: max-age='.GETPOSTINT('cache').', public, must-revalidate');
	// For a .php, we must set an Expires to avoid to have it forced to an expired value by the web server
	header('Expires: '.gmdate('D, d M Y H:i:s', dol_now('gmt') + GETPOSTINT('cache')).' GMT');
	// HTTP/1.0
	header('Pragma: token=public');
} else {
	// HTTP/1.0
	header('Cache-Control: no-cache');
}

$title = $langs->trans("Menu");

// URL http://mydolibarr/core/get_menudiv.php?dol_use_jmobile=1 can be used for tests
$head = '<!-- Menu -->'."\n";	// This is used by DoliDroid to know page is a menu page
$arrayofjs = array();
$arrayofcss = array();
top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);

print '<body class="getmenudiv">'."\n";

// JavaScript to make menu active like Jmobile did.
print '
<style>
    /* Hide the non active LIs by default*/
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

	ul li.lilevel2 {
		padding-left: 40px;	/* width = 20 for level0, 20 for level1 */
	}

	.getmenudiv a:hover {
		text-decoration: none;
	}

	.pictofixedwidth {
    	text-align: left;
    	padding-right: 10px !important;
	}

	li.lilevel1 > a, li.lilevel1 > i {
		padding-left: 30px !important;
	}
	li.lilevel2 a {
		padding-left: 60px !important;
	}
	li.lilevel3 a {
		padding-left: 90px !important;
	}
	li.lilevel4 a {
		padding-left: 120px !important;
	}

    a.alilevel0, span.spanlilevel0 {
        background-image: url(\''.DOL_URL_ROOT.'/theme/'.urlencode($conf->theme).'/img/next.png\') !important;
        background-repeat: no-repeat !important;';
if ($langs->trans("DIRECTION") == 'rtl') {
	print 'background-position: right;';
} else {
	print 'background-position-x: 10px;';
}
print '
        background-position-y: 18px;
        padding: 1em 15px 1em 40px;
		display: block;
    }
    li.lilevel0 font.vsmenudisabled {
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
	li.lilevel1 > a, li.lilevel1 > i {
        /* background-image: url(\''.DOL_URL_ROOT.'/theme/'.urlencode($conf->theme).'/img/puce.png\') !important; */
        background-repeat: no-repeat !important;';
if ($langs->trans("DIRECTION") == 'rtl') {
	print 'background-position: right;';
} else {
	print 'background-position-x: 10px;';
}
print 'background-position-y: 1px;';
print 'padding-left: 20px;';
print '
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
        padding: 0.6em 15px 0.6em 60px;
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
	.vsmenudisabled .fa, .vsmenudisabled .fas, .vsmenudisabled .far {
	    color: #aaa !important;
	}
</style>

<script nonce="'.getNonce().'" type="text/javascript">
$(document).ready(function(){
    $("body ul").click(function(){
        console.log("We click on body ul");

        $(this).siblings().find("li ul").slideUp(0);

        $(this).find("li ul").slideToggle(200);

        var target = $(this);
        $(\'html, body\').animate({
          scrollTop: target.offset().top
        }, 300);

    })
});
</script>
';


if (empty($user->socid)) {	// If internal user or not defined
	$conf->standard_menu = (!getDolGlobalString('MAIN_MENU_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENU_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENU_STANDARD) : $conf->global->MAIN_MENU_STANDARD_FORCED);
} else { // If external user
	$conf->standard_menu = (!getDolGlobalString('MAIN_MENUFRONT_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENUFRONT_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENUFRONT_STANDARD) : $conf->global->MAIN_MENUFRONT_STANDARD_FORCED);
}

// Load the menu manager (only if not already done)
$file_menu = $conf->standard_menu;
if (GETPOST('menu', 'aZ09')) {
	$file_menu = GETPOST('menu', 'aZ09'); // example: menu=eldy_menu.php
}
if (!class_exists('MenuManager')) {
	$menufound = 0;
	$dirmenus = array_merge(array("/core/menus/"), (array) $conf->modules_parts['menus']);
	foreach ($dirmenus as $dirmenu) {
		$menufound = dol_include_once($dirmenu."standard/".$file_menu);
		if ($menufound) {
			break;
		}
	}
	if (!$menufound) {	// If failed to include, we try with standard
		dol_syslog("You define a menu manager '".$file_menu."' that can not be loaded.", LOG_WARNING);
		$file_menu = 'eldy_menu.php';
		include_once DOL_DOCUMENT_ROOT."/core/menus/standard/".$file_menu;
	}
}
$menumanager = new MenuManager($db, empty($user->socid) ? 0 : 1);
$menumanager->loadMenu('all', 'all'); // Load this->tabMenu with sql menu entries
//var_dump($menumanager);exit;
$menumanager->showmenu('jmobile');

print '</body>';

print '</html>'."\n";

$db->close();
