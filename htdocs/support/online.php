<?php
/* Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file      htdocs/support/online.php
 *       \ingroup   install
 *       \brief     Provide an Online Help support
 */

error_reporting(0);

include_once 'inc.php';
$uri=preg_replace('/^http(s?):\/\//i','',$dolibarr_main_url_root);
$pos = strstr($uri, '/');      // $pos contient alors url sans nom domaine
if ($pos == '/') $pos = '';     // si $pos vaut /, on le met a ''
define('DOL_URL_ROOT', $pos);	// URL racine relative


$langs->load("other");
$langs->load("help");


/*
 * View
 */

pHeader($langs->trans("DolibarrHelpCenter"),$_SERVER["PHP_SELF"]);

$urlsparkengels='http://www.spark-angels.com';
$titlesparkangels='Spark-Angels';

//print '<br>';

print $langs->trans("ToGetHelpGoOnSparkAngels1",$titlesparkangels).'<br>';

print '<br><br>';


// List of predefined coaches
// We list here the 4 most active coaches on Dolibarr projects (according to number of commits
// found in page http://www.nltechno.com/stats/dolibarr/cvschangelogbuilder_dolibarr.html
$limit=4;
$arrayofwidgets=array(
// Widget for Laurent Destailleur
array('name'=>'Laurent Destailleur',	// id user 4702
		'sort'=>1,
		'logo'=>'logoUrl='.urlencode('http://www.nltechno.com/images/logo_nltechno_long.jpg'), // Put your own logo
		'id'=>'4256,4255',	// Put of list of sparkangels widget id (for each language)
		'lang'=>'fr,en'),	// Put list of language code of widgets (always english at end)
// Widget for Auguria
array('name'=>'Auguria',
		'sort'=>2,
		//'logo'=>'logoUrl='.urlencode('http://www.cap-networks.com/images/logo_small.jpg'),
		'id'=>'7196',
		'lang'=>'fr'),
//Widget for Open-Concept
array('name'=>'Open-Concept.pro',
		'sort'=>2,
		'logo'=>'logoUrl='.urlencode('http://www.open-concept.pro/CMS/images/Logo/logosimplecomplet.png'),
		'id'=>'9340',
		'lang'=>'fr')
);
$arrayofwidgets=dol_sort_array($arrayofwidgets,'sort','asc',0,0);

$found=0;
print '* '.$langs->trans("LinkToGoldMember",$langs->defaultlang).'<br><br>';
print '<table summary="listofgoldcoaches"><tr>';
foreach ($arrayofwidgets as $arraywidget)	// Loop on each user
{
	if ($found >= $limit) break;
	$listofwidgets=explode(',',$arraywidget['id']);
	$listoflangs=explode(',',$arraywidget['lang']);
	$pos=0;
	foreach($listoflangs as $langcode)		// Loop on each lang of user
	{
		$pos++;
		if (preg_match('/'.$langcode.'/i',$langs->defaultlang) || $langcode == 'en')	// If lang qualified
		{
			print '<td align="center">';
			print $arraywidget['name'].'<br>';
			print $langs->trans("PossibleLanguages").': ';
			// All languages of user are shown
			foreach ($listoflangs as $langcode2)
			{
				if (empty($widgetid)) $widgetid=$listoflangs[$pos-1];
				if (! preg_match('/'.$langcode.'/i',$langs->defaultlang) && $langcode2 != 'en') continue;	// Show only english
				print $langcode2.' ';
			}
			print '<br>';

			// Only first language found is used for widget
			$widgetid=$listofwidgets[$pos-1];

			// Widget V3
			print '<iframe src="http://www.spark-angels.com/static/widget/template-pro3/widgetpro3-iframe.html?widgetId='.$widgetid.'&lgCode='.$langcode.'&'.(isset($arraywidget['logo'])?$arraywidget['logo']:'').'" width="172" height="123" frameborder="0" scrolling="no" marginheight="0" > </iframe>';

			print '</td>';
			$found++;
			break;
		}
	}
}
if (! $found) print '<td>'.$langs->trans("SorryNoHelpForYourLanguage").'</td>';
print '</tr></table>';

print '<br><br>';

// List of coaches
$sparkangellangcode=substr($langs->defaultlang,0,2);
if (! in_array($sparkangellangcode,array('fr','en','sp'))) $sparkangellangcode='en';
print '<table class="noborder" summary="ListOfSupport"><tr valign="middle"><td>';
print '* '.$langs->trans("ToGetHelpGoOnSparkAngels3",$urlsparkengels);
print '<div id="sparkom_bsaHelpersSearch">'."\n";
print '<form target="_blank" id="frJSkw" action="http://www.spark-angels.com/rss/action/resultsearch.html" name="fResult" method="get">'."\n";
print '   <input type="hidden" value="" title="Rechercher" maxlength="1024" name="kws" id="kws"/> <!-- mots clés pour la recherche dont la ou les compétences matchent avec ces mots -->'."\n";
print '   <input id="dhids" name="dhids" type="hidden" value=""><!-- identifiant SHSAPI communiqué par SparkAngels. -->'."\n";
print '   <input id="lgSearch" name="lgS" type="hidden" value=""><!-- code langue, les accompagnateurs proposés suite à cette recherche prétendent pouvoir fournir de l assistance dans au moins cette langue-->'."\n";
print '   <input id="myLv" name="myLv" type="hidden" value=""><!-- niveau de l internaute dans le domaine de sa recherche.-->'."\n";
print '   <input id="catSrv" name="catSrv" type="hidden" value=""><!-- type de catégorie de service demandée.-->'."\n";
print '   <input type="submit" value="'.$langs->trans("Search").'" onclick="getSAParams();">'."\n";
print '<script type="text/javascript">'."\n";
print '<!--'."\n";
print '   function getSAParams(){'."\n";
print '       document.getElementById(\'dhids\').value= "4702";'."\n";
print '       document.getElementById(\'kws\').value= "dolibarr";'."\n";
print '       document.getElementById(\'lgSearch\').value= "'.$sparkangellangcode.'";'."\n";
print '       document.getElementById(\'myLv\').value= "0";'."\n";
print '       document.getElementById(\'catSrv\').value= "1";'."\n";
print '   }'."\n";
print '-->'."\n";
print '</script>'."\n";
print '</form>'."\n";
print '</div>'."\n";

print '</td><td>';
//print '<a href="'.$urlsparkengels.'" target="_blank">';
//print '<img border="0" src="sparkangels.png" alt="SparkAngels web site" title="SparkAngels web site">';
//print $titlesparkangels;
//print '</a>';
print '</td></tr></table>';
//print $langs->trans("ToGetHelpGoOnSparkAngels2",$titlesparkangels).'<br>';


// Otherwise, go back to help center home
print '<br><br>';
print '* '.$langs->trans("BackToHelpCenter",'index.php');
print '<br><br>';



pFooter();
