<?php
/* Copyright (C) 2004-2007 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**	    \file       htdocs/mantis/mantis.php
        \ingroup    mantis
		\brief      Page générant 2 frames, une pour le menu Dolibarr, l'autre pour l'affichage de Mantis
		\author	    Laurent Destailleur
		\version    $Revision$
*/

require("./pre.inc.php");

if (empty($conf->global->PHPMANTIS_URL))
{
	llxHeader();
	print '<div class="error">Module Mantis was not configured properly.</div>';
	llxFooter('$Date$ - $Revision$');
}

$mainmenu=isset($_GET["mainmenu"])?$_GET["mainmenu"]:"";
$leftmenu=isset($_GET["leftmenu"])?$_GET["leftmenu"]:"";

print "
<html>
<head>
<title>Dolibarr frame for Mantis</title>
</head>

<frameset rows=\"28,*\" border=0 framespacing=0 frameborder=0>
    <frame name=\"barre\" src=\"mantistop.php?mainmenu=".$mainmenu."&leftmenu=".$leftmenu."\" noresize scrolling=\"NO\" noborder>
    <frame name=\"main\" src=\"".$conf->global->PHPMANTIS_URL."\">
    <noframes>
    <body>

    </body>
    </noframes>
</frameset>

<noframes>
<body>
	<br><center>
	Malheureusement, votre navigateur est trop vieux pour visualiser cette zone.<br>
	Il vous faut un navigateur gérant les frames.<br>
	</center>
</body>
</noframes>

</html>
";


?>
