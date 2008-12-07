<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
       	\file       htdocs/public/demo/index.php
		\ingroup    core
		\brief      File to access demo
		\author	    Laurent Destailleur
		\version    $Id$
*/

require("../../master.inc.php");

$langs->setDefaultLang('auto');

$langs->load("main");
$langs->load("other");


$demoprofiles=array(
	array('default'=>'-1', 'key'=>'profdemofun','label'=>'DemoFundation',
	'disablemodules'=>'banque,barcode,bookmark,boutique,cashdesk,commercial,commande,comptabilite,contrat,expedition,facture,fournisseur,prelevement,produit,projet,service,societe,stock,tax'),
	array('default'=>'0', 'key'=>'profdemofun2','label'=>'DemoFundation2',
	'disablemodules'=>'barcode,boutique,bookmark,cashdesk,commercial,contrat,expedition,facture,commande,fournisseur,prelevement,produit,projet,service,societe,stock'),
	array('default'=>'1', 'key'=>'profdemoservonly','label'=>'DemoCompanyServiceOnly',
	'disablemodules'=>'adherent,barcode,boutique,bookmark,cashdesk,don,expedition,prelevement,projet,stock'),
	array('default'=>'-1','key'=>'profdemoshopwithdesk','label'=>'DemoCompanyShopWithCashDesk',
	'disablemodules'=>'adherent,boutique,bookmark,don,prelevement,produit,stock'),
	array('default'=>'0', 'key'=>'profdemoprodstock','label'=>'DemoCompanyProductAndStocks',
	'disablemodules'=>'adherent,boutique,bookmark,cashdesk,don,prelevement,service'),
	array('default'=>'0', 'key'=>'profdemoall','label'=>'DemoCompanyAll',
	'disablemodules'=>'adherent,boutique,bookmark,cashdesk,don'),
	);


function llxHeaderVierge($title, $head = "")
{
	global $user, $conf, $langs;

	print "<html>\n";
	print "<head>\n";
	print "<title>".$title."</title>\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";
	print '<link rel="stylesheet" type="text/css" href="/theme/eldy/eldy.css.php">'."\n";
	if ($head) print $head."\n";
	print "</head>\n";
	print '<body style="margin: 20px;">'."\n";
}

function llxFooter()
{
	print "</body>\n";
	print "</html>\n";
}


/*
 * Actions
 */

if ($_POST["action"] == 'gotodemo')
{
	//print 'ee'.$_POST["demochoice"];
	$disablestring='';
	foreach ($demoprofiles as $profilearray)
	{
		if ($profilearray['key'] == $_POST["demochoice"])
		{
			$disablestring=$profilearray['disablemodules'];
			break;
		}
	}

	if ($disablestring)
	{
		header("Location: ".DOL_URL_ROOT.'/index.php?disablemodules='.$disablestring);
		exit;
	}
}


/*
 * View
 */
 
llxHeaderVierge($langs->trans("DolibarrDemo"));


// Search modules
$dirlist=$conf->dol_document_root;

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

print '<table style="font-size:14px;">';

print '<tr><td>';
print '<center><img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo_2.png" alt="Dolibarr logo"></center><br>';
print '<br>';

print $langs->trans("DemoDesc").'<br>';
print '<br>';
print '<b>'.$langs->trans("ChooseYourDemoProfil").'</b>';

print '</td></tr>';
print '<tr><td width="50%">';

print '<table style="font-size:14px;">'."\n";
foreach ($demoprofiles as $profilarray)
{
	if ($profilarray['default'] >= 0)
	{
		print '<tr><td><input type="radio" name="demochoice"';
		if ($profilarray['default']) print ' checked="true"';
		print ' value="'.$profilarray['key'].'"></td>';
		print '<td>'.$langs->trans($profilarray['label']).'</td></tr>'."\n";
	}	
}
print '</table>';

print '</td>';
print '</tr>';

// Description
print '<tr><td>';


print '</td></tr>';

// Button
print '<tr><td align="center">';
print '<input type="hidden" name="action" value="gotodemo">';
print '<input class="button" type="submit" value=" < '.$langs->trans("GoToDemo").' > ">';
print '</td></tr>';

print '</table>';

print '</form>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
