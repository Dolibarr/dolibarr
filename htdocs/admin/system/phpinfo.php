<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis@dolibarr.fr>
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
 */

/**
 *      \file       htdocs/admin/system/phpinfo.php
 *		\brief      Page des infos systeme de php
 */

require("../../main.inc.php");

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$what=GETPOST('what','alpha');


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

if ($what == 'conf')
{
	$title = 'Setup';
	phpinfo(INFO_CONFIGURATION);
}
elseif ($what == 'env')
{
	$title = 'OSEnv';
	phpinfo(INFO_ENVIRONMENT);
}
elseif ($what == 'modules')
{
	$title = 'Modules';
	phpinfo(INFO_MODULES);
}
else
{
	phpinfo();
}

$chaine = ob_get_contents();
ob_end_clean();

// Nettoie la sortie php pour inclusion dans une page deja existante
$chaine = preg_replace('/^<!DOCTYPE(.*)<div class="center">/ims', '', $chaine);
$chaine = preg_replace('/<\/div><\/body><\/html>$/im', '', $chaine);
$chaine = preg_replace('/table(.*)important; \}/i','',$chaine);
$chaine = str_replace('<hr />', '', $chaine);
$chaine = str_replace('cellpadding="3" ', 'cellpadding="1" cellspacing="1" ', $chaine);
$chaine = str_replace('class="h"','class="liste_titre"', $chaine);
$chaine = str_replace('<th colspan="2">', '<td>', $chaine);
$chaine = str_replace('th>', 'td>', $chaine);
// Titles
$chaine = preg_replace('/<h1([^>]*)>/i','<div class="titre">',$chaine);
$chaine = str_replace('<h2>', '<div class="titre">', $chaine);
$chaine = str_replace('</h1>', '</div><br>', $chaine);
$chaine = str_replace('</h2>', '</div>', $chaine);

$chaine = str_replace('<td class="e">', '<td class="impair">', $chaine);
$chaine = str_replace('<td class="v">', '<td class="pair">', $chaine);
$chaine = str_replace('<div class="titre">Configuration</div><br>', '', $chaine);
// Add LF
$chaine = preg_replace('/(pcntl_[a-z]+),/','$1, ',$chaine);

if (isset($title))
{
	print_fiche_titre($langs->trans($title), '', 'setup');
	print '<br>';
}

print "$chaine\n";	// Ne pas centrer la reponse php car certains tableau du bas tres large rendent ceux du haut completement a droite
print "<br>\n";

llxFooter();
$db->close();
?>