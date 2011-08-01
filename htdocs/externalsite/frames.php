<?php
/* Copyright (C) 2004-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * $Id: frames.php,v 1.10 2011/07/31 23:50:56 eldy Exp $
 */

/**	    \file       htdocs/externalsite/frames.php
        \ingroup    externalsite
		\brief      Page that build two frames: One for menu, the other for the target page to show
		\author	    Laurent Destailleur
		\version    $Revision: 1.10 $
*/

require("../main.inc.php");

$langs->load("externalsite@externalsite");

if (empty($conf->global->EXTERNALSITE_URL))
{
	llxHeader();
	print '<div class="error">Module ExternalSite was not configured properly.</div>';
	llxFooter('$Date: 2011/07/31 23:50:56 $ - $Revision: 1.10 $');
}

$mainmenu=GETPOST('mainmenu');
$leftmenu=GETPOST('leftmenu');
$idmenu=GETPOST('idmenu');
$theme=GETPOST('theme');
$codelang=GETPOST('lang');

print "
<html>
<head>
<title>Dolibarr frame for external web site</title>
</head>

<frameset rows=\"".$heightforframes.",*\" border=0 framespacing=0 frameborder=0>
    <frame name=\"barre\" src=\"frametop.php?mainmenu=".$mainmenu."&leftmenu=".$leftmenu."&idmenu=".$idmenu.($theme?'&theme='.$theme:'').($codelang?'&lang='.$codelang:'')."&nobackground=1\" noresize scrolling=\"NO\" noborder>
    <frame name=\"main\" src=\"".$conf->global->EXTERNALSITE_URL."\">
    <noframes>
    <body>

    </body>
    </noframes>
</frameset>

<noframes>
<body>
	<br><center>
	Sorry, your browser is too old or not correctly configured to view this area.<br>
	Your browser must support frames.<br>
	</center>
</body>
</noframes>

</html>
";


?>
