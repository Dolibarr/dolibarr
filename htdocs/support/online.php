<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file      htdocs/install/phpinfo.php
 *       \ingroup   install
 *       \brief     Provide an Online Help support
 *       \version   $Id$
 */

include_once("./inc.php");
$uri=eregi_replace('^http(s?)://','',$dolibarr_main_url_root);
$pos = strstr ($uri, '/');      // $pos contient alors url sans nom domaine
if ($pos == '/') $pos = '';     // si $pos vaut /, on le met a ''
define('DOL_URL_ROOT', $pos);	// URL racine relative

$langs->load("other");
$langs->load("help");


pHeader($langs->trans("DolibarrHelpCenter"),$_SERVER["PHP_SELF"]);

$urlsparkengels='http://www.spark-angels.com';
$titlesparkangels='Spark-Angels';

//print '<br>';

print $langs->trans("ToGetHelpGoOnSparkAngels1",$titlesparkangels).'<br>';
print '<br>';

print '<table class="noborder"><tr valign="middle"><td>';
print '* '.$langs->trans("ToGetHelpGoOnSparkAngels3",$urlsparkengels);
print '</td><td>';
//print '<a href="'.$urlsparkengels.'" target="_blank">';
//print '<img border="0" src="sparkangels.png" alt="SparkAngels web site" title="SparkAngels web site">';
//print $titlesparkangels;
//print '</a>';
print '</td></tr></table><br>';
print $langs->trans("ToGetHelpGoOnSparkAngels2",$titlesparkangels).'<br>';

$arrayofwidgets=array(
// Widget for Laurent Destailleur
array('name'=>'Laurent Destailleur',
			'id'=>'4256,4255',	// Put of list of sparkangels widget id (for each language)
			'lang'=>'fr,en'),	// Put list of language code of widgets (always english at end)
			// Widget for Regis Houssin
array('name'=>'R&eacute;gis Houssin',
			'id'=>'4611',
			'lang'=>'fr')
);

// Preselected widgets
$found=0;
print '<br><br>';
print '* '.$langs->trans("LinkToGoldMember",$langs->defaultlang).'<br><br>';
print '<table><tr>';
foreach ($arrayofwidgets as $arraywidget)	// Loop on each user
{
	$listofwidgets=split(',',$arraywidget['id']);
	$listoflangs=split(',',$arraywidget['lang']);
	$pos=0;
	foreach($listoflangs as $langcode)		// Loop on each lang of user
	{
		$pos++;
		if (eregi($langcode,$langs->defaultlang) || $langcode == 'en')	// If lang qualified
		{
			print '<td align="center">';
			print $arraywidget['name'].'<br>';
			print $langs->trans("PossibleLanguages").': ';
			// All languages of user are shown
			foreach ($listoflangs as $langcode2)
			{
				if (empty($widgetid)) $widgetid=$listoflangs[$pos-1];
				if (! eregi($langcode,$langs->defaultlang) && $langcode2 != 'en') continue;	// Show only english
				print $langcode2.' ';
			}
			print '<br>';
			// But only first language found is used for widget
			$widgetid=$listofwidgets[$pos-1];
			//print 'xx lang qualified='.$langcode.' id='.$widgetid;
			print '<iframe src="http://dnld0.sparkom.com/static/widget/widgetpro-iframe.html?accountId='.$widgetid.'" width="172" height="123" frameborder="0" scrolling="no" marginheight="0" > </iframe>';
			print '</td>';
			$found=1;
			break;
		}
	}
}
if (! $found) print '<td>'.$langs->trans("SorryNoHelpForYourLanguage").'</td>';
print '</tr></table>';


print '<br><br>';
print '* '.$langs->trans("BackToHelpCenter",DOL_URL_ROOT.'/support/');
print '<br><br>';



pFooter();
?>
