<?php
/* Copyright (C) 2004-2010 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *
 */

/**
 *     \file       htdocs/externalsite/frames.php
 *     \ingroup    externalsite
 *     \brief      Page that build two frames: One for menu, the other for the target page to show
 *     \author	   Laurent Destailleur
 */

require '../main.inc.php';

$langs->load("externalsite");

if (empty($conf->global->EXTERNALSITE_URL))
{
	llxHeader();
	print '<div class="error">'.$langs->trans('ExternalSiteModuleNotComplete').'</div>';
	llxFooter();
}

$mainmenu=GETPOST('mainmenu', 'alpha');
$leftmenu=GETPOST('leftmenu', 'alpha');
$idmenu=GETPOST('idmenu', 'int');
$theme=GETPOST('theme', 'alpha');
$codelang=GETPOST('lang', 'alpha');

print "
<html>
<head>
<title>Dolibarr frame for external web site</title>
</head>

<frameset ".(empty($conf->global->MAIN_MENU_INVERT)?"rows":"cols")."=\"".$heightforframes.",*\" border=0 framespacing=0 frameborder=0>
    <frame name=\"barre\" src=\"frametop.php?mainmenu=".$mainmenu."&leftmenu=".$leftmenu."&idmenu=".$idmenu.($theme?'&theme='.$theme:'').($codelang?'&lang='.$codelang:'')."&nobackground=1\" noresize scrolling=\"NO\" noborder>
    <frame name=\"main\" src=\"".$conf->global->EXTERNALSITE_URL."\">
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


