<?php
/* Copyright (C) 2004-2018 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *     \file       	htdocs/externalsite/frames.php
 *     \ingroup    	externalsite
 *     \brief      	Page that build two frames: One for menu, the other for the target page to show
 *					Usage:
 *					  /externalsite/frames.php to show URL set into setup
 *					  /externalsite/frames.php?keyforcontent=EXTERNAL_SITE_CONTENT_abc to show html text defined into $conf->global->EXTERNAL_SITE_CONTENT_abc
 *					  /externalsite/frames.php?keyforcontent=EXTERNAL_SITE_URL_abc to show URL defined into $conf->global->EXTERNAL_SITE_URL_abc
 */

require '../main.inc.php';

// Load translation files required by the page
$langs->load("externalsite");


$mainmenu = GETPOST('mainmenu', "aZ09");
$leftmenu = GETPOST('leftmenu', "aZ09");
$idmenu = GETPOST('idmenu', 'int');
$theme = GETPOST('theme', 'alpha');
$codelang = GETPOST('lang', 'aZ09');
$keyforcontent = GETPOST('keyforcontent', 'aZ09');


/*
 * View
 */

if (empty($keyforcontent) && empty($conf->global->EXTERNALSITE_URL))
{
	llxHeader();
	print '<div class="error">'.$langs->trans('ExternalSiteModuleNotComplete').'</div>';
	llxFooter();
	exit;
}

if (!empty($keyforcontent))
{
	llxHeader();

	print '<div class="framecontent" style="height: '.($_SESSION['dol_screenheight'] - 90).'px">';

	if (!preg_match('/EXTERNAL_SITE_CONTENT_/', $keyforcontent)
		 && !preg_match('/EXTERNAL_SITE_URL_/', $keyforcontent))
	{
		$langs->load("errors");
		print $langs->trans("ErrorBadSyntaxForParamKeyForContent", 'EXTERNAL_SITE_CONTENT_', 'EXTERNAL_SITE_URL_');
	} elseif (empty($conf->global->$keyforcontent))
	{
		$langs->load("errors");
		print $langs->trans("ErrorVariableKeyForContentMustBeSet", 'EXTERNAL_SITE_CONTENT_'.$keyforcontent, 'EXTERNAL_SITE_URL_'.$keyforcontent);
	} else {
		if (preg_match('/EXTERNAL_SITE_CONTENT_/', $keyforcontent))
		{
			print $conf->global->$keyforcontent;
		} elseif (preg_match('/EXTERNAL_SITE_URL_/', $keyforcontent))
		{
			/*print "
			<html>
			<head>
			<title>Dolibarr frame for external web site</title>
			</head>

			<frameset ".(empty($conf->global->MAIN_MENU_INVERT)?"rows":"cols")."=\"".$heightforframes.",*\" border=0 framespacing=0 frameborder=0>
			    <frame name=\"barre\" src=\"frametop.php?mainmenu=".$mainmenu."&leftmenu=".$leftmenu."&idmenu=".$idmenu.($theme?'&theme='.$theme:'').($codelang?'&lang='.$codelang:'')."&nobackground=1\" noresize scrolling=\"NO\" noborder>
			  ";
					print '<frame name="main" src="';
					print $conf->global->$keyforcontent;
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
			";*/
			print '<iframe src="'.$conf->global->$keyforcontent.'"></iframe>';
		}
	}

	print '<div>';
	llxFooter();
} else {
	if (preg_match('/^\//', $conf->global->EXTERNALSITE_URL) || preg_match('/^http/i', $conf->global->EXTERNALSITE_URL))
	{
		print "
	<html>
	<head>
	<title>Dolibarr frame for external web site</title>
	</head>

	<frameset ".(empty($conf->global->MAIN_MENU_INVERT) ? "rows" : "cols")."=\"".$heightforframes.",*\" border=0 framespacing=0 frameborder=0>
	    <frame name=\"barre\" src=\"frametop.php?mainmenu=".$mainmenu."&leftmenu=".$leftmenu."&idmenu=".$idmenu.($theme ? '&theme='.$theme : '').($codelang ? '&lang='.$codelang : '')."&nobackground=1\" noresize scrolling=\"NO\" noborder>
	  ";
		print '<frame name="main" src="';
		print $conf->global->EXTERNALSITE_URL;
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
		llxHeader();
		print '<div class="framecontent" style="height: '.($_SESSION['dol_screenheight'] - 90).'px">';
		print $conf->global->EXTERNALSITE_URL;
		print '<div>';
		llxFooter();
	}
}
