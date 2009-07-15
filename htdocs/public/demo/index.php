<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     	\file       htdocs/public/demo/index.php
 *		\ingroup    core
 *		\brief      Entry page to access demo
 *		\author	    Laurent Destailleur
 *		\version    $Id$
 */

require("../../master.inc.php");

$langcode=(empty($_GET["lang"])?'auto':$_GET["lang"]);
$langs->setDefaultLang($langcode);

$langs->load("main");
$langs->load("other");

// Security check
if (empty($conf->global->MAIN_DEMO)) accessforbidden('Constant MAIN_DEMO must be defined in Home->Setup->Misc to enable the demo entry page',1,1,1);


$demoprofiles=array(
	array('default'=>'-1', 'key'=>'profdemofun','label'=>'DemoFundation',
	'disablemodules'=>'banque,barcode,boutique,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,facture,ficheinter,fournisseur,prelevement,produit,projet,propal,propale,service,societe,stock,tax',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png'),
	array('default'=>'0', 'key'=>'profdemofun2','label'=>'DemoFundation2',
	'disablemodules'=>'barcode,boutique,cashdesk,commande,commercial,compta,comptabilite,contrat,expedition,facture,ficheinter,fournisseur,prelevement,produit,projet,propal,propale,service,societe,stock',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot6.png'),
	array('default'=>'1', 'key'=>'profdemoservonly','label'=>'DemoCompanyServiceOnly',
	'disablemodules'=>'adherent,barcode,boutique,cashdesk,don,expedition,prelevement,projet,stock',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot8.png'),
	array('default'=>'-1','key'=>'profdemoshopwithdesk','label'=>'DemoCompanyShopWithCashDesk',
	'disablemodules'=>'adherent,boutique,don,ficheinter,prelevement,produit,stock',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
	array('default'=>'0', 'key'=>'profdemoprodstock','label'=>'DemoCompanyProductAndStocks',
	'disablemodules'=>'adherent,boutique,cashdesk,don,ficheinter,prelevement,service',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot2.png'),
	array('default'=>'0', 'key'=>'profdemoall','label'=>'DemoCompanyAll',
	'disablemodules'=>'adherent,boutique,cashdesk,don',
	'icon'=>DOL_URL_ROOT.'/public/demo/dolibarr_screenshot9.png'),
	);


function llxHeaderVierge($title, $head = "")
{
	global $user, $conf, $langs;

	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
	print "\n";
	print "<html>\n";
	print "<head>\n";
	print '<meta name="robots" content="index,nofollow">'."\n";
	print '<meta name="keywords" content="dolibarr,demo,online,demonstration,example,test,web,erp,crm,demos,online">'."\n";
	print '<meta name="description" content="Dolibarr simple ERP/CRM demo. You can test here several profiles of Dolibarr ERP/CRM demos.">'."\n";
	print "<title>".$title."</title>\n";
	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/eldy/eldy.css.php">'."\n";
	if ($head) print $head."\n";
	print '<style type="text/css">';
	print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
	print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
	print '</style>';
	print "</head>\n";
	print '<body style="margin: 20px;">'."\n";
}

function llxFooter()
{
	print "\n";
	print "</body>\n";
	print "</html>\n";
}


/*
 * Actions
 */

if ($_REQUEST["action"] == 'gotodemo')
{
	//print 'ee'.$_POST["demochoice"];
	$disablestring='';
	foreach ($demoprofiles as $profilearray)
	{
		if ($profilearray['key'] == $_REQUEST["demochoice"])
		{
			$disablestring=$profilearray['disablemodules'];
			break;
		}
	}

	if ($disablestring)
	{
		$url=DOL_URL_ROOT.'/index.php?disablemodules='.$disablestring;
		if (! empty($_REQUEST["urlfrom"]))     $url.='&urlfrom='.$_REQUEST["urlfrom"];
		header("Location: ".$url);
		exit;
	}
}


/*
 * View
 */

llxHeaderVierge($langs->trans("DolibarrDemo"));


// Search modules
$dirlist=$conf->file->dol_document_root;

$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$i = 0;	// is a sequencer of modules found
$j = 0;	// j is module number. Automatically affeted if module number not defined.
foreach ($dirlist as $dirroot)
{
	$dir = $dirroot . "/includes/modules/";

	// Charge tableaux modules, nom, numero, orders depuis r�pertoire dir
	$handle=opendir($dir);
	while (($file = readdir($handle))!==false)
	{
		//print "$i ".$file."\n<br>";
	    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, strlen($file) - 10) == '.class.php')
	    {
	        $modName = substr($file, 0, strlen($file) - 10);

	        if ($modName)
	        {
	            include_once($dir.$file);
	            $objMod = new $modName($db);

	            if ($objMod->numero > 0)
	            {
	                $j = $objMod->numero;
	            }
	            else
	            {
	                $j = 1000 + $i;
	            }

				$modulequalified=1;

				// We discard modules that does not respect constraint on menu handlers
				if ($objMod->needleftmenu && sizeof($objMod->needleftmenu) && ! in_array($conf->left_menu,$objMod->needleftmenu)) $modulequalified=0;
				if ($objMod->needtopmenu  && sizeof($objMod->needtopmenu)  && ! in_array($conf->top_menu,$objMod->needtopmenu))   $modulequalified=0;

				// We discard modules according to features level (PS: if module is activated we always show it)
				$const_name = 'MAIN_MODULE_'.strtoupper(eregi_replace('^mod','',get_class($objMod)));
				if ($objMod->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2 && ! $conf->global->$const_name) $modulequalified=0;
				if ($objMod->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && ! $conf->global->$const_name) $modulequalified=0;

				if ($modulequalified)
				{
					$modules[$i] = $objMod;
		            $filename[$i]= $modName;
		            $orders[$i]  = $objMod->family."_".$j;   // Tri par famille puis numero module
					//print "x".$modName." ".$orders[$i]."\n<br>";
					$categ[$objMod->special]++;					// Array of all different modules categories
		            $dirmod[$i] = $dirroot;
					$j++;
		            $i++;
				}
	        }
	    }
	}

}

asort($orders);
//var_dump($orders);

print '<form name="choosedemo" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="username" value="demo">';
print "\n";

print '<table style="font-size:14px;" summary="List of Dolibarr demos">';

print '<tr><td>';
print '<center><img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.png" alt="Dolibarr logo"></center><br>';
print '<br>';

print $langs->trans("DemoDesc").'<br>';
print '<br>';
print '<font color="#555577"><b>'.$langs->trans("ChooseYourDemoProfil").'</b></font>';

print '</td></tr>';
print '<tr><td width="50%">';

$NBOFCOLS=2;
print '<table style="font-size:14px;" width="100%" summary="List of Dolibarr demos">'."\n";
$i=0;
foreach ($demoprofiles as $profilarray)
{
	if ($profilarray['default'] >= 0)
	{
		$url=$_SERVER["PHP_SELF"].'?action=gotodemo&amp;demochoice='.$profilarray['key'].'&amp;urlfrom='.urlencode($_SERVER["PHP_SELF"]);
		//if ($i % $NBOFCOLS == 0) print '<tr>';
		print '<tr>';
		print '<td align="left">';
		print '<table summary="Dolibarr online demonstration for profile '.$profilarray['label'].'" style="font-size:14px;" width="100%" class="CTableRow'.($i%2==0?'1':'2').'">'."\n";
		print '<tr>';
		print '<td align="left" width="50"><a href="'.$url.'"><img src="'.$profilarray['icon'].'" width="48" border="0" alt="Demo '.$profilarray['label'].'"></a></td>';
		//print '<td><input type="radio" name="demochoice"';
		//if ($profilarray['default']) print ' checked="true"';
		//print ' value="'.$profilarray['key'].'"></td>';
		print '<td align="left"><a href="'.$url.'">'.$langs->trans($profilarray['label']).'</a></td></tr>';
		print '</table>';
		print '</td>';
		//if ($i % $NBOFCOLS == ($NBOFCOLS-1)) print '</tr>'."\n";
		print '</tr>'."\n";
		$i++;
	}
}
print '</table>';

print '</td>';
print '</tr>';

// Description
print '<tr><td>';


print '</td></tr>';

// Button
/*
print '<tr><td align="center">';
print '<input type="hidden" name="action" value="gotodemo">';
print '<input class="button" type="submit" value=" < '.$langs->trans("GoToDemo").' > ">';
print '</td></tr>';
*/

print '</table>';

print '</form>';

$db->close();

// Google Adsense (ex: demo mode)
if (! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
{
	print '<div align="center">'."\n";
	print '<script type="text/javascript"><!--'."\n";
	print 'google_ad_client = "'.$conf->global->MAIN_GOOGLE_AD_CLIENT.'";'."\n";
	print '/* '.$conf->global->MAIN_GOOGLE_AD_WIDTH.'x'.$conf->global->MAIN_GOOGLE_AD_HEIGHT.', '.$conf->global->MAIN_GOOGLE_AD_NAME.' */'."\n";
	print 'google_ad_slot = "'.$conf->global->MAIN_GOOGLE_AD_SLOT.'";'."\n";
	print 'google_ad_width = '.$conf->global->MAIN_GOOGLE_AD_WIDTH.';'."\n";
	print 'google_ad_height = '.$conf->global->MAIN_GOOGLE_AD_HEIGHT.';'."\n";
	print '//-->'."\n";
	print '</script>'."\n";
	print '<script type="text/javascript"'."\n";
	print 'src="http://pagead2.googlesyndication.com/pagead/show_ads.js">'."\n";
	print '</script>'."\n";
	print '</div>'."\n";
}

llxFooter('$Date$ - $Revision$');
?>
