<?php
/* Copyright (C) 2004-2018 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *
 */

/**
 *     \file       	htdocs/core/frames.php
 *     \ingroup    	core
 *     \brief      	Page that build two frames: One for menu, the other for the target page to show
 *					Usage:
 *					  /core/frames.php to show URL set into setup
 *					  /core/frames.php?keyforcontent=EXTERNAL_SITE_CONTENT_abc to show html text defined into conf 'EXTERNAL_SITE_CONTENT_abc'
 *					  /core/frames.php?keyforcontent=EXTERNAL_SITE_URL_abc to show URL defined into conf 'EXTERNAL_SITE_URL_abc'
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var int	$heightforframes
 */

/** @phan-file-suppress PhanUndeclaredGlobalVariable */

// Load translation files required by the page
$langs->load("other");


$mainmenu = GETPOST('mainmenu', "aZ09");
$leftmenu = GETPOST('leftmenu', "aZ09");
$idmenu = GETPOSTINT('idmenu');
$theme = GETPOST('theme', 'aZ09');
$codelang = GETPOST('lang', 'aZ09');

$menu = new Menubase($db);
$menu->fetch($idmenu);

if (!$menu->id || empty($menu->showtopmenuinframe)) {
	accessforbidden('Parameter idmenu is wrong. Must be the ID of a menu entry allowed to be output into a frame');
}



/*
 * View
 */

// The content of the top frame
if (GETPOST('top')) {
	top_htmlhead("", "");

	print '<body id="mainbody">'."\n";

	top_menu("", "", "_top");

	print '</body>';

	exit;
}

$reg = array();

$keyforcontent = '';
if (preg_match('/^__[(.+)]__$/', $menu->url, $reg)) {
	$keyforcontent = $reg[1];
}

if ($keyforcontent) {
	llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-externalsite page-frames');

	print '<div class="framecontent" style="height: '.($_SESSION['dol_screenheight'] - 90).'px">';

	if (!getDolGlobalString($keyforcontent)) {
		$langs->load("errors");
		print $langs->trans("ErrorVariableKeyForContentMustBeSet", $keyforcontent, $keyforcontent);
	} else {
		if (preg_match('/EXTERNAL_SITE_URL_/', $keyforcontent)) {
			print '<iframe src="'.getDolGlobalString($keyforcontent).'"></iframe>';
		} else {
			print getDolGlobalString($keyforcontent);
		}
	}

	print '<div>';
	llxFooter();
} else {
	if (preg_match('/^\//', $menu->url) || preg_match('/^http/i', $menu->url)) {
		// menu->url is an URL starting with http or /
		print "
			<html>
			<head>
			<title>Dolibarr frame for external web site</title>
			</head>

			<frameset ".(!getDolGlobalString('MAIN_MENU_INVERT') ? "rows" : "cols")."=\"".$heightforframes.",*\" border=0 framespacing=0 frameborder=0>
			    <frame name=\"barre\" src=\"".$_SERVER["PHP_SELF"]."?top=1&mainmenu=".$mainmenu."&leftmenu=".$leftmenu."&idmenu=".$idmenu.($theme ? '&theme='.$theme : '').($codelang ? '&lang='.$codelang : '')."&nobackground=1\" noresize scrolling=\"NO\" noborder>
			  ";
				print '<frame name="main" src="';
				print $menu->url;
				print '">';
				print "
			    <noframes>
			    <body>

			    </body>
			    </noframes>
			</frameset>

			<noframes>
			<body>
				<br><div class=\"center\">
				Sorry, your browser is too old or not correctly configured to view this area.<br>
				Your browser must support frames.<br>
				</div>
			</body>
			</noframes>

			</html>
		";
	} else {
		// menu->url is an URL starting with http or /
		llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-externalsite page-frames');
		print '<div class="framecontent" style="height: '.($_SESSION['dol_screenheight'] - 90).'px">';
		print $menu->url;
		print '<div>';
		llxFooter();
	}
}
