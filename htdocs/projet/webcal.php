<?PHP
/* Copyright (C) 2004 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * $Source$
 *
 */
require("./pre.inc.php");

$url=PHPWEBCALENDAR_URL;

print "
<html>
<head>
<meta name=\"robots\" content=\"index,follow\">
<meta name=\"description\" content=\"Site Web personnel - Laurent Destailleur (CV, Développements...)\">
<meta name=\"keywords\" content=\"destailleur, laurent, page, accueil, ancien, ISEN, awstats, awmess, awban, awadmin, utilkit, universal, domotic, sendmess, getmess, destailleur\">
<title>Site Web personnel - Laurent Destailleur</title>
</head>

<frameset rows=\"28,*\" border=0 framespacing=0 frameborder=0>
    <frame name=\"barre\" src=\"webcaltop.php\" noresize scrolling=\"NO\" noborder>
    <frame name=\"main\" src=\"$url\">
    <noframes>
    <body>

    </body>
    </noframes>
</frameset>

<noframes>
<body>
	<br><center>
	Bienvenue sur le site Web personnel de Laurent Destailleur.<br>
	CV, Documents, Développements, Photos...<br>
	<br>

	Malheureusement, votre navigateur est trop vieux pour visualiser ce site.<br>
	Il vous faut un navigateur gérant les frames.<br>
	</center>
</body>
</noframes>

<!-- QJIXRKPI -->

</html>
";


?>
