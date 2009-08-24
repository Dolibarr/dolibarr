<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *  \file       htdocs/admin/modules.php
 *  \brief      Page de configuration et activation des modules
 *  \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("errors");

$mode=isset($_GET["mode"])?$_GET["mode"]:(isset($_SESSION['mode'])?$_SESSION['mode']:0);
$mesg=isset($_GET["mesg"])?$_GET["mesg"]:"";

if (!$user->admin)
    accessforbidden();


/*
 * Actions
 */

if (isset($_GET["action"]) && $_GET["action"] == 'set' && $user->admin)
{
    $result=Activate($_GET["value"]);
    $mesg='';
    if ($result) $mesg=$result;
    Header("Location: modules.php?mode=".$mode."&mesg=".urlencode($mesg));
	exit;
}

if (isset($_GET["action"]) && $_GET["action"] == 'reset' && $user->admin)
{
    $result=UnActivate($_GET["value"]);
    $mesg='';
    if ($result) $mesg=$result;
    Header("Location: modules.php?mode=".$mode."&mesg=".urlencode($mesg));
	exit;
}

/**
   	\brief      Enable a module
   	\param      value       Nom du module a activer
   	\param      withdeps    Active/desactive aussi les dependances
	\return		string		Error message or '';
*/
function Activate($value,$withdeps=1)
{
	global $db, $modules, $langs, $conf;

	$modName = $value;

	$ret='';

	// Activate module
	if ($modName)
	{
		$file = $modName . ".class.php";

		// Loop on each directory
		foreach ($conf->file->dol_document_root as $dol_document_root)
		{
			$found=@include_once($dol_document_root."/includes/modules/".$file);
			if ($found) break;
		}

		$objMod = new $modName($db);

		// Test if PHP version ok
		$verphp=versionphparray();
		$vermin=$objMod->phpmin;
		if (is_array($vermin) && versioncompare($verphp,$vermin) < 0)
		{
			return $langs->trans("ErrorModuleRequirePHPVersion",versiontostring($vermin));
		}

		// Test if Dolibarr version ok
		$verdol=versiondolibarrarray();
		$vermin=$objMod->need_dolibarr_version;
		if (is_array($vermin) && versioncompare($verdol,$vermin) < 0)
		{
			return $langs->trans("ErrorModuleRequireDolibarrVersion",versiontostring($vermin));
		}

		// Test if javascript requirement ok
		if (! empty($objMod->need_javascript_ajax) && empty($conf->use_javascript_ajax))
		{
			return $langs->trans("ErrorModuleRequireJavascript");
		}

		$result=$objMod->init();
		if ($result <= 0) $ret=$objMod->error;
	}

	if ($withdeps)
	{
		// Activation des modules dont le module depend
		for ($i = 0; $i < sizeof($objMod->depends); $i++)
		{
			Activate($objMod->depends[$i]);
		}

		// Desactivation des modules qui entrent en conflit
		for ($i = 0; $i < sizeof($objMod->conflictwith); $i++)
		{
			UnActivate($objMod->conflictwith[$i],0);
		}
	}

	return $ret;
}


/**
   \brief      Disable a module
   \param      value               Nom du module a desactiver
   \param      requiredby          1=Desactive aussi modules dependants
*/
function UnActivate($value,$requiredby=1)
{
	global $db, $modules, $conf;

	$modName = $value;

	$ret='';

	// Desactivation du module
	if ($modName)
	{
		$file = $modName . ".class.php";

		// Loop on each directory
		foreach ($conf->file->dol_document_root as $dol_document_root)
		{
			$found=@include_once($dol_document_root."/includes/modules/".$file);
			if ($found) break;
		}

		if ($found)
		{
			$objMod = new $modName($db);
			$result=$objMod->remove();
		}
		else
		{
			$genericMod = new DolibarrModules($db);
			$genericMod->name=eregi_replace('^mod','',$modName);
			$genericMod->style_sheet=1;
			$genericMod->rights_class=strtolower(eregi_replace('^mod','',$modName));
			$genericMod->const_name='MAIN_MODULE_'.strtoupper(eregi_replace('^mod','',$modName));
			dol_syslog("modules::UnActivate Failed to find module file, we use generic function with name ".$genericMod->name);
			$genericMod->_remove();
		}
	}

	// Desactivation des modules qui dependent de lui
	if ($requiredby)
	{
		for ($i = 0; $i < sizeof($objMod->requiredby); $i++)
		{
	  		UnActivate($objMod->requiredby[$i]);
		}
	}

	return $ret;
}


/*
 * Affichage page
 */
$_SESSION["mode"]=$mode;

$wikihelp='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader($langs->trans("Setup"),'',$wikihelp);

print_fiche_titre($langs->trans("ModulesSetup"),'','setup');


// Search modules
$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$i = 0;	// is a sequencer of modules found
$j = 0;	// j is module number. Automatically affeted if module number not defined.
foreach ($conf->file->dol_document_root as $dirroot)
{
	$dir = $dirroot . "/includes/modules/";

	// Load modules attributes in arrays (name, numero, orders) from dir directory
	//print $dir."\n<br>";
	$handle=@opendir($dir);
	if ($handle)
	{
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
					if (! empty($objMod->needleftmenu) && sizeof($objMod->needleftmenu) && ! in_array($conf->left_menu,$objMod->needleftmenu)) $modulequalified=0;
					if (! empty($objMod->needtopmenu)  && sizeof($objMod->needtopmenu)  && ! in_array($conf->top_menu,$objMod->needtopmenu))   $modulequalified=0;

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
						if (isset($categ[$objMod->special])) $categ[$objMod->special]++;					// Array of all different modules categories
			            else $categ[$objMod->special]=1;
						$dirmod[$i] = $dirroot;
						$j++;
			            $i++;
					}
		        }
		    }
		}
		closedir($handle);
	}
	else
	{
		dol_syslog("htdocs/admin/modules.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
	}
}

asort($orders);
//var_dump($orders);


// Affichage debut page

if ($mode==0) { $tagmode = 'common';     print $langs->trans("ModulesDesc")."<br>\n"; }
if ($mode==2) { $tagmode = 'other';      print $langs->trans("ModulesSpecialDesc")."<br>\n"; }
if ($mode==1) { $tagmode = 'interfaces'; print $langs->trans("ModulesInterfaceDesc")."<br>\n"; }
if ($mode==3) { $tagmode = 'functional'; print $langs->trans("ModulesJobDesc")."<br>\n"; }
print "<br>\n";


$h = 0;

$categidx=0;
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesCommon");
	$head[$h][2] = 'common';
	$h++;
}

$categidx=2;
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesOther");
	$head[$h][2] = 'other';
	$h++;
}

$categidx=1;
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesInterfaces");
	$head[$h][2] = 'interfaces';
	$h++;
}

$categidx=3;
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesSpecial");
	$head[$h][2] = 'functional';
	$h++;
}

dol_fiche_head($head, $tagmode, $langs->trans("Modules"));


if ($mesg) print '<div class="error">'.$mesg.'</div>';

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print "  <td>".$langs->trans("Family")."</td>\n";
print "  <td colspan=\"2\">".$langs->trans("Module")."</td>\n";
print "  <td>".$langs->trans("Description")."</td>\n";
print "  <td align=\"center\">".$langs->trans("Version")."</td>\n";
//print "  <td align=\"center\">".$langs->trans("DbVersion")."</td>\n";
print "  <td align=\"center\">".$langs->trans("Activated")."</td>\n";
print "  <td align=\"center\">".$langs->trans("Action")."</td>\n";
print "  <td>".$langs->trans("SetupShort")."</td>\n";
print "</tr>\n";


// Affichage liste modules

$var=true;
$oldfamily='';

$familylib=array(
'base'=>$langs->trans("ModuleFamilyBase"),
'crm'=>$langs->trans("ModuleFamilyCrm"),
'products'=>$langs->trans("ModuleFamilyProducts"),
'hr'=>$langs->trans("ModuleFamilyHr"),
'projects'=>$langs->trans("ModuleFamilyProjects"),
'financial'=>$langs->trans("ModuleFamilyFinancial"),
'ecm'=>$langs->trans("ModuleFamilyECM"),
'technic'=>$langs->trans("ModuleFamilyTechnic"),
'other'=>$langs->trans("ModuleFamilyOther")
);
foreach ($orders as $key => $value)
{
    $tab=split('_',$value);
    $family=$tab[0]; $numero=$tab[1];

    $modName = $filename[$key];
	$objMod  = $modules[$key];

    $const_name = 'MAIN_MODULE_'.strtoupper(eregi_replace('^mod','',get_class($objMod)));

    // Load all lang files of module
    if (isset($objMod->langfiles) && is_array($objMod->langfiles))
    {
    	foreach($objMod->langfiles as $domain)
    	{
    		$langs->load($domain);
    	}
    }

    // Print a separator if we change family
    //print "<tr><td>xx".$oldfamily."-".$family."-".$atleastoneforfamily."<br></td><tr>";
    if ($oldfamily && $family!=$oldfamily && $atleastoneforfamily) {
        print "<tr class=\"liste_titre\">\n  <td colspan=\"9\"></td>\n</tr>\n";
        $atleastoneforfamily=0;
        //print "<tr><td>yy".$oldfamily."-".$family."-".$atleastoneforfamily."<br></td><tr>";
    }

    if ($objMod->special == $mode)
    {
        $atleastoneforfamily++;
        $var=!$var;

        print "<tr $bc[$var]>\n";

        print "  <td class=\"body\" valign=\"top\">";
        if ($family!=$oldfamily)
        {
            $familytext=empty($familylib[$family])?$family:$familylib[$family];
        	print "<div class=\"titre\">";
            print $familytext;
            print "</div>";
            $oldfamily=$family;
        }
        else
        {
            print '&nbsp;';
        }
        print "</td>\n";

        // Picto
        print '  <td valign="top" width="14" align="center">';
        if (! empty($objMod->picto))
        {
        	if (eregi('^/',$objMod->picto)) print img_picto('',$objMod->picto,'',1);
        	else print img_object('',$objMod->picto);
        }
        else
        {
        	print img_object('','generic');
        }
        print '</td>';

        // Name
        print '<td valign="top">'.$objMod->getName();
        print "</td>\n  <td valign=\"top\">";
        print nl2br($objMod->getDesc());
        print "</td>\n  <td align=\"center\" valign=\"top\">";
        print $objMod->getVersion();
        print "</td>\n  <td align=\"center\" valign=\"top\">";

        if (! empty($conf->global->$const_name))
        {
            print img_tick();
        }
        else
        {
            print "&nbsp;";
        }

        print "</td>\n";

        // Activate/Disable and Setup
        print "<td align=\"center\" valign=\"top\">";
        if (! empty($conf->global->$const_name))
        {
            // Module actif
            if (! empty($objMod->always_enabled) || ($conf->global->MAIN_MODULE_MULTICOMPANY && $conf->entity!=1 && $objMod->core_enabled)) print $langs->trans("Required");
            else print "<a href=\"modules.php?id=".$objMod->numero."&amp;action=reset&amp;value=" . $modName . "&amp;mode=" . $mode . "\">" . $langs->trans("Disable") . "</a></td>\n";

            if (! empty($objMod->config_page_url))
            {
                if (is_array($objMod->config_page_url))
                {
                    print '  <td align="center" valign="top">';
                    $i=0;
                    foreach ($objMod->config_page_url as $page)
                    {
						$urlpage=$page;
                        if ($i++)
                        {
                            print '<a href="'.$urlpage.'" alt="'.$langs->trans($page).'">'.img_picto(ucfirst($page),"setup").'</a>&nbsp;';
                        //    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
                        }
                        else
                        {
                            //print '<a href="'.$page.'">'.$langs->trans("Setup").'</a>&nbsp;';
                            print '<a href="'.$urlpage.'" alt="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup").'</a>&nbsp;';
                        }
                    }
                    print "</td>\n";
                }
                else
                {
                    //print '  <td align="center"><a href="'.$objMod->config_page_url.'">'.$langs->trans("Setup").'</a></td>';
                    print '  <td align="center" valign="top"><a href="'.$objMod->config_page_url.'">'.img_picto($langs->trans("Setup"),"setup").'</a></td>';
                }
            }
            else
            {
                print "  <td>&nbsp;</td>";
            }

        }
        else
        {
            if (! empty($objMod->always_enabled))
            {
                // Ne devrait pas arriver.
            }

            // Module non actif
           	print "<a href=\"modules.php?id=".$objMod->numero."&amp;action=set&amp;value=" . $modName . "&amp;mode=" . $mode . "\">" . $langs->trans("Activate") . "</a></td>\n  <td>&nbsp;</td>\n";
        }

        print "</tr>\n";
    }

}
print "</table></div>\n";


// Pour eviter bug mise en page IE
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
