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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/system/phpinfo.php
 *		\brief      Page des infos systeme de php
 *		\version    $Id: phpinfo.php,v 1.19 2011/07/31 22:23:14 eldy Exp $
 */

require("../../main.inc.php");

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
$chaine = preg_replace('/background-color: #ffffff;/i','',$chaine);
$chaine = preg_replace('/(.*)<style/i','<style',$chaine);
$chaine = preg_replace('/<title>(.*)<body>/i','',$chaine);
$chaine = preg_replace('/a:link \{([^\}]*)\}/i','',$chaine);
$chaine = preg_replace('/a:hover \{([^\}]*)\}/i','',$chaine);
$chaine = preg_replace('/td, th \{([^\}]*)\}/i','',$chaine);
$chaine = preg_replace('/img \{([^\}]*)\}/i','',$chaine);
$chaine = preg_replace('/table(.*)important; \}/i','',$chaine);
$chaine = preg_replace('/<hr \/>/i','',$chaine);
$chaine = preg_replace('/<\/body><\/html>/i','',$chaine);
$chaine = preg_replace('/body, td, th, h1, h2 \{font-family: sans-serif;\}/i','',$chaine);
$chaine = preg_replace('/cellpadding="3" /i','cellpadding="1" cellspacing="1" ',$chaine);
$chaine = preg_replace('/class="h"/i','class="liste_titre"',$chaine);
$chaine = preg_replace('/<th colspan="2">/i','<td>',$chaine);
$chaine = preg_replace('/th>/i','td>',$chaine);
// Titres
$chaine = preg_replace('/<h1([^>]*)>/i','<div class="titre">',$chaine);
$chaine = preg_replace('/<h2>/i','<div class="titre">',$chaine);
$chaine = preg_replace('/<\/h1>/i','</div><br>',$chaine);
$chaine = preg_replace('/<\/h2>/i','</div>',$chaine);

$chaine = preg_replace('/<td class="e">/i','<td class="impair">',$chaine);
$chaine = preg_replace('/<td class="v">/i','<td class="pair">',$chaine);

$chaine = preg_replace('/<div class="titre">Configuration<\/div><br>/i','',$chaine);

if (isset($title))
{
	print_fiche_titre($title,'','setup');
	print '<br>';
}

print "$chaine\n";	// Ne pas centrer la reponse php car certains tableau du bas tres large rendent ceux du haut completement a droite
print "<br>\n";

llxfooter('$Date: 2011/07/31 22:23:14 $ - $Revision: 1.19 $');
?>
