<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo    <jlb@j1b.org>
 * Copyright (C) 2004-2013 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2005-2007 Regis Houssin         <regis.houssin@capnetworks.com>
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
 */

/**
 *       \file       htdocs/admin/system/about.php
 *       \brief      About Dolibarr File page
 */

require '../../main.inc.php';

$langs->load("admin");
$langs->load("help");
$langs->load("members");


/*
 * View
 */

llxHeader();


print_fiche_titre("Dolibarr",'','setup');

print '<div style="padding-left: 30px;">'.img_picto_common('', 'dolibarr_box.png','height="120"').'</div>';

print $langs->trans("Version").' / '.$langs->trans("DolibarrLicense").':';
print '<ul>';
print '<li>'.DOL_VERSION.' / <a href="http://www.gnu.org/copyleft/gpl.html">GNU-GPL v3+</a></li>';
print '</ul>';

//print "<br>\n";

print $langs->trans("Developpers").':';
print '<ul>';
print '<li>'.$langs->trans("SeeWikiForAllTeam").': <a href="http://wiki.dolibarr.org/index.php/Dolibarr_Project" target="_blank">http://wiki.dolibarr.org/index.php/Dolibarr_Project</a></li>';
print '<li>'.$langs->trans("DoliForge").': <a href="http://www.doliforge.org" target="_blank">http://wwww.doliforge.org</a></li>';
print '</ul>';

//print "<br>\n";

print $langs->trans("OtherInformations").':';

print '<ul>';
print '<li>';
print '<a target="_blank" href="http://www.dolibarr.org/">'.$langs->trans("OfficialWebSite").'</a>';
print '</li>';
// If the French language, it displays French website
if (preg_match('/^fr_/i',$langs->getDefaultLang()))
{
	print '<li>';
	print '<a target="_blank" href="http://www.dolibarr.fr/">'.$langs->trans("OfficialWebSiteFr").'</a>';
	print '</li>';
}
print '<li>';
print '<a target="_blank" href="http://wiki.dolibarr.org/">'.$langs->trans("OfficialWiki").'</a>';
print '</li>';
print '</ul>';

print $langs->trans("Demo").':';
print '<ul>';
print '<li>';
print '<a target="_blank" href="http://www.dolibarr.org/onlinedemo/">'.$langs->trans("OfficialDemo").'</a>';
print '</li>';
print '</ul>';

print $langs->trans("ModulesMarketPlaces").':';
print '<ul>';
print '<li>';
print '<a target="_blank" href="http://www.dolistore.com">'.$langs->trans("OfficialMarketPlace").'</a>';
print '</li>';
print '</ul>';


print $langs->trans("HelpCenter").':';
print '<ul>';
print '<li>';
//print $langs->trans("SeeWikiPage",'http://wiki.dolibarr.org/index.php/List_of_OpenSource_Software_companies_and_freelancers');
print '<a target="_blank" href="'.DOL_URL_ROOT.'/support/index.php">'.$langs->trans("HelpCenter").'</a>';
print '</li>';
print '</ul>';

print $langs->trans("Foundation").':';

print '<ul>';
$url='http://wiki.dolibarr.org/index.php/Subscribe';
if (preg_match('/^fr_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Adh%C3%A9rer';
if (preg_match('/^es_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Subscribirse';
print '<li><a href="'.$url.'" target="_blank">'.$langs->trans("SubscribeToFoundation").'</a></li>';
print '</ul>';


print $langs->trans("OfficialWebHostingService").':';
$url='http://wiki.dolibarr.org/index.php/Cloud_Solutions'; $title=$langs->trans('List');
if (preg_match('/^fr_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Solutions_de_Cloud';
if (preg_match('/^es_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Soluciones_en_la_Nube';
print '<ul>';
print '<li>';
print '<a target="_blank" href="'.$url.'">'.$title.'</a>';
print '</li>';
print '</ul>';


llxFooter();

$db->close();
?>










