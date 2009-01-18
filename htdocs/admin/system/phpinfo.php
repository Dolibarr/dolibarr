<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *      \file       htdocs/admin/system/phpinfo.php
 *		\brief      Page des infos système de php
 *		\version    $Id$
 */

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
accessforbidden();


/*
 * View
 */

llxHeader();

/* Style phpinfo
 body {background-color: #ffffff; color: #000000;}
 body, td, th, h1, h2 {font-family: sans-serif;}
 pre {margin: 0px; font-family: monospace;}

 a:link {color: #000099; text-decoration: none; background-color: #ffffff;}
 a:hover {text-decoration: underline;}
 table {border-collapse: collapse;}
 .center {text-align: center;}
 .center table { margin-left: auto; margin-right: auto; text-align: left;}
 .center th { text-align: center !important; }

 td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
 h1 {font-size: 150%;}
 h2 {font-size: 125%;}
 .p {text-align: left;}
 .e {background-color: #ccccff; font-weight: bold; color: #000000;}
 .h {background-color: #9999cc; font-weight: bold; color: #000000;}
 .v {background-color: #cccccc; color: #000000;}
 .vr {background-color: #cccccc; text-align: right; color: #000000;}
 img {float: right; border: 0px;}
 hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
 */

ob_start();

if ($_GET["what"] == 'conf')
{
	$title=$langs->trans("Setup");
	phpinfo(INFO_CONFIGURATION);
}
elseif ($_GET["what"] == 'env')
{
	$title=$langs->trans("OSEnv");
	phpinfo(INFO_ENVIRONMENT);
}
elseif ($_GET["what"] == 'modules')
{
	$title=$langs->trans("Modules");
	phpinfo(INFO_MODULES);
}
else
{
	phpinfo();
}

$chaine = ob_get_contents();
ob_end_clean();

// Nettoie la sortie php pour inclusion dans une page deja existante
$chaine = eregi_replace('background-color: #ffffff;','',$chaine);
$chaine = eregi_replace('.*<style','<style',$chaine);
$chaine = eregi_replace('<title>.*<body>','',$chaine);
$chaine = eregi_replace('<title>.*<body>','',$chaine);
$chaine = eregi_replace('a:link \{[^\}]*}','',$chaine);
$chaine = eregi_replace('a:hover \{[^\}]*}','',$chaine);
$chaine = eregi_replace('td, th \{[^\}]*}','',$chaine);
$chaine = eregi_replace('img \{[^\}]*}','',$chaine);
$chaine = eregi_replace('table.*important; }','',$chaine);
$chaine = eregi_replace('<hr />','',$chaine);
$chaine = eregi_replace('</body></html>','',$chaine);
$chaine = eregi_replace('body, td, th, h1, h2 \{font-family: sans-serif;\}','',$chaine);
$chaine = eregi_replace('cellpadding="3" ','cellpadding="1" cellspacing="1" ',$chaine);
$chaine = eregi_replace('class="h"','class="liste_titre"',$chaine);
$chaine = eregi_replace('<th colspan="2">','<td>',$chaine);
$chaine = eregi_replace('th>','td>',$chaine);
// Titres
$chaine = eregi_replace('<h1([^>]*)>','<div class="titre">',$chaine);
$chaine = eregi_replace('<h2>','<div class="titre">',$chaine);
$chaine = eregi_replace('</h1>','</div><br>',$chaine);
$chaine = eregi_replace('</h2>','</div>',$chaine);

$chaine = eregi_replace('<td class="e">','<td class="impair">',$chaine);
$chaine = eregi_replace('<td class="v">','<td class="pair">',$chaine);

$chaine = eregi_replace('<div class="titre">Configuration</div><br>','',$chaine);

if (isset($title))
{
	print_fiche_titre($title,'','setup');
	print '<br>';
}

print "$chaine\n";	// Ne pas centrer la réponse php car certains tableau du bas très large rendent ceux du haut complètement à droite
print "<br>\n";

llxfooter('$Date$ - $Revision$');
?>
