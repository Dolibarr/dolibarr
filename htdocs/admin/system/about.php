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
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

$langs->load("admin");
$langs->load("help");
$langs->load("members");

$youuselaststable = 0;


/*
 * View
 */

llxHeader();


print load_fiche_titre("Dolibarr",'','title_setup');

print '<div style="padding-left: 30px;">'.img_picto_common('', 'dolibarr_box.png','height="120"').'</div>';



print '<div class="fichecenter"><div class="fichehalfleft">';

print $langs->trans("Version").' / '.$langs->trans("DolibarrLicense").':';
print '<ul>';
print '<li><strong>'.DOL_VERSION.'</strong>';

$result = getURLContent('http://sourceforge.net/projects/dolibarr/rss');
//var_dump($result['content']);
$sfurl = simplexml_load_string($result['content']);
if ($sfurl)
{
    $i=0;
    $version='0.0';
    while (! empty($sfurl->channel[0]->item[$i]->title) && $i < 10000)
    {
        $title=$sfurl->channel[0]->item[$i]->title;
        if (preg_match('/([0-9]+\.([0-9\.]+))/', $title, $reg))
        {
            $newversion=$reg[1];
            $newversionarray=explode('.',$newversion);
            $versionarray=explode('.',$version);
            //var_dump($newversionarray);var_dump($versionarray);
            if (versioncompare($newversionarray, $versionarray) > 0) $version=$newversion;
        }
        $i++;
    }

    // Show version
    if ($version != '0.0') 
    {
        print ' ('.$langs->trans("LastStableVersion").': <b>'.$version.'</b>';
    	if (DOL_VERSION == $version)
    	{
    	    $youuselaststable=1;
            print $langs->trans("YouUseLastStableVersion");  
    	}
    	print ')';
    }
	print ' / <a href="http://www.gnu.org/copyleft/gpl.html">GNU-GPL v3+</a></li>';
}
else
{
    print $langs->trans("LastStableVersion").' : <b>' .$langs->trans("UpdateServerOffline").'</b><br>';
}


print '</ul>';

//print "<br>\n";

print $langs->trans("Developpers").':';
print '<ul>';
print '<li>'.$langs->trans("SourcesRepository").': <a href="http://www.github.com/Dolibarr/dolibarr" target="_blank" rel="external">http://www.github.com/Dolibarr/dolibarr</a></li>';
print '<li>'.$langs->trans("SeeWikiForAllTeam").': <a href="http://wiki.dolibarr.org/index.php/Dolibarr_Project" target="_blank" rel="external">http://wiki.dolibarr.org/index.php/Dolibarr_Project</a></li>';
print '</ul>';

//print "<br>\n";

print $langs->trans("OtherInformations").':';

print '<ul>';
print '<li>';
print '<a target="_blank" href="http://www.dolibarr.org/" rel="external">'.$langs->trans("OfficialWebSite").'</a>';
print '</li>';
// Show local site
if (preg_match('/^fr_/i',$langs->getDefaultLang()))
{
	print '<li>';
	print '<a target="_blank" href="http://www.dolibarr.fr/" rel="external">'.$langs->trans("OfficialWebSiteLocal", $langs->transnoentitiesnoconv("France")).'</a>';
	print '</li>';
}
if (preg_match('/^el_/i',$langs->getDefaultLang()))
{
	print '<li>';
	print '<a target="_blank" href="http://www.dolibarr.gr/" rel="external">'.$langs->trans("OfficialWebSiteLocal", $langs->transnoentitiesnoconv("Greece")).'</a>';
	print '</li>';
}
if (preg_match('/^es_/i',$langs->getDefaultLang()))
{
	print '<li>';
	print '<a target="_blank" href="http://www.dolibarr.es/" rel="external">'.$langs->trans("OfficialWebSiteLocal", $langs->transnoentitiesnoconv("Spain")).'</a>';
	print '</li>';
}
if (preg_match('/^it_/i',$langs->getDefaultLang()))
{
	print '<li>';
	print '<a target="_blank" href="http://www.dolibarr.it/" rel="external">'.$langs->trans("OfficialWebSiteLocal", $langs->transnoentitiesnoconv("Italy")).'</a>';
	print '</li>';
}
if (preg_match('/^de_/i',$langs->getDefaultLang()))
{
	print '<li>';
	print '<a target="_blank" href="http://www.dolibarr.de/" rel="external">'.$langs->trans("OfficialWebSiteLocal", $langs->transnoentitiesnoconv("Germany")).'</a>';
	print '</li>';
}
print '<li>';
print '<a target="_blank" href="http://wiki.dolibarr.org/" rel="external">'.$langs->trans("OfficialWiki").'</a>';
print '</li>';
print '</ul>';

print $langs->trans("Demo").':';
print '<ul>';
print '<li>';
print '<a target="_blank" href="http://www.dolibarr.org/onlinedemo/" rel="external">'.$langs->trans("OfficialDemo").'</a>';
print '</li>';
print '</ul>';

print $langs->trans("ModulesMarketPlaces").':';
print '<ul>';
print '<li>';
print '<a target="_blank" href="http://www.dolistore.com" rel="external">'.$langs->trans("OfficialMarketPlace").'</a>';
print '</li>';
print '</ul>';


print '</div><div class="fichehalfright">';


print $langs->trans("HelpCenter").':';
print '<ul>';
print '<li>';
//print $langs->trans("SeeWikiPage",'http://wiki.dolibarr.org/index.php/List_of_OpenSource_Software_companies_and_freelancers');
print '<a target="_blank" href="'.DOL_URL_ROOT.'/support/index.php" data-ajax="false">'.$langs->trans("HelpCenter").'</a>';
print '</li>';
print '</ul>';


print $langs->trans("Foundation").':';

print '<ul>';
$url='http://wiki.dolibarr.org/index.php/Subscribe';
if (preg_match('/^fr_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Adh%C3%A9rer';
if (preg_match('/^es_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Subscribirse';
print '<li><a href="'.$url.'" target="_blank" rel="external">'.$langs->trans("SubscribeToFoundation").'</a></li>';

print '<li><a href="http://facebook.com/dolibarr" target="_blank" rel="external">FaceBook</a></li>';
print '<li><a href="http://twitter.com/dolibarr" target="_blank" rel="external">Twitter</a></li>';
print '<li><a href="http://plus.google.com/+DolibarrOrg" target="_blank" rel="external">Google Plus page</a></li>';

print '</ul>';


print $langs->trans("OtherResources").':';
print '<ul>';

$url='http://saas.dolibarr.org'; $title=$langs->trans("OfficialWebHostingService");
if (preg_match('/^fr_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Solutions_de_Cloud';
if (preg_match('/^es_/i',$langs->getDefaultLang())) $url='http://wiki.dolibarr.org/index.php/Soluciones_en_la_Nube';
print '<li>';
print '<a target="_blank" href="'.$url.'" rel="external">'.$title.'</a>';
print '</li>';
$url='http://partners.dolibarr.org'; $title=$langs->trans("ReferencedPreferredPartners");
print '<li>';
print '<a target="_blank" href="'.$url.'" rel="external">'.$title.'</a>';
print '</li>';

print '</ul>';

print '</div>';
print '</div>';
print '<div class="clearboth"></div>';


if ($youuselaststable)
{
    print '<br>';
    print '<br>';
    
    $tmp=versiondolibarrarray();
    if ((empty($tmp[2]) && (strpos($tmp[1], '0') === 0)) || (strpos($tmp[2], '0') === 0))
    {
        print $langs->trans("TitleExampleForMajorRelease").':<br>';
        print '<textarea style="width:80%; min-height: 60px">';
        print $langs->trans("ExampleOfNewsMessageForMajorRelease", DOL_VERSION, DOL_VERSION);
        print '</textarea>';
    }
    else
    {
        print $langs->trans("TitleExampleForMaintenanceRelease").':<br>';
        print '<textarea style="width:80%; min-height: 60px">';
        print $langs->trans("ExampleOfNewsMessageForMaintenanceRelease", DOL_VERSION, DOL_VERSION);
        print '</textarea>';
    }
}


llxFooter();

$db->close();
