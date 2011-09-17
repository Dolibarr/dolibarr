<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/admin/modules.php
 *  \brief      Page to activate/disable all modules
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("errors");
$langs->load("admin");

$mode=isset($_GET["mode"])?GETPOST("mode"):(isset($_SESSION['mode'])?$_SESSION['mode']:0);
$mesg=GETPOST("mesg");

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


/*
 * View
 */

$_SESSION["mode"]=$mode;

$help_url='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$help_url);

print_fiche_titre($langs->trans("ModulesSetup"),'','setup');


// Search modules
$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$modulesdir = array();
$i = 0;	// is a sequencer of modules found
$j = 0;	// j is module number. Automatically affected if module number not defined.

foreach ($conf->file->dol_document_root as $type => $dirroot)
{
	$modulesdir[] = $dirroot . "/includes/modules/";
	
	if ($type == 'alt')
	{	
		$handle=@opendir($dirroot);
		if (is_resource($handle))
		{
			while (($file = readdir($handle))!==false)
			{
			    if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
			    {
			    	if (is_dir($dirroot . '/' . $file . '/includes/modules/'))
			    	{
			    		$modulesdir[] = $dirroot . '/' . $file . '/includes/modules/';
			    	}
			    }
			}
			closedir($handle);
		}
	}
}

foreach ($modulesdir as $dir)
{
	// Load modules attributes in arrays (name, numero, orders) from dir directory
	//print $dir."\n<br>";
	dol_syslog("Scan directory ".$dir." for modules");
	$handle=@opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
			//print "$i ".$file."\n<br>";
		    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
		    {
		        $modName = substr($file, 0, dol_strlen($file) - 10);

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

					// We discard modules according to features level (PS: if module is activated we always show it)
					$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));
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
					else dol_syslog("Module ".get_class($objMod)." not qualified");
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
//var_dump($categ);
//var_dump($modules);

// Affichage debut page

if ($mode==0) { $tagmode = 'common';      print $langs->trans("ModulesDesc")."<br>\n"; }
if ($mode==2) { $tagmode = 'other';       print $langs->trans("ModulesSpecialDesc")."<br>\n"; }
if ($mode==1) { $tagmode = 'interfaces';  print $langs->trans("ModulesInterfaceDesc")."<br>\n"; }
if ($mode==3) { $tagmode = 'functional';  print $langs->trans("ModulesJobDesc")."<br>\n"; }
if ($mode==4) { $tagmode = 'marketplace'; print $langs->trans("ModulesMarketPlaceDesc")."<br>\n"; }
print "<br>\n";


$h = 0;

$categidx=0;    // Main
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesCommon");
	$head[$h][2] = 'common';
	$h++;
}

$categidx=2;    // Other
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesOther");
	$head[$h][2] = 'other';
	$h++;
}

$categidx=1;    // Interfaces
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesInterfaces");
	$head[$h][2] = 'interfaces';
	$h++;
}

$categidx=3;    // Not used
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesSpecial");
	$head[$h][2] = 'functional';
	$h++;
}

$categidx=4;
//if (! empty($categ[$categidx]))
//{
    $head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
    $head[$h][1] = $langs->trans("ModulesMarketPlaces");
    $head[$h][2] = 'marketplace';
    $h++;
//}


dol_fiche_head($head, $tagmode, $langs->trans("Modules"));


if ($mesg) print '<div class="error">'.$mesg.'</div>';


if ($mode != 4)
{
    print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";
    //print "<tr class=\"liste_titre\">\n";
    print '<tr class="liste_total">'."\n";
    //print "  <td>".$langs->trans("Family")."</td>\n";
    print "  <td colspan=\"2\">".$langs->trans("Module")."</td>\n";
    print "  <td>".$langs->trans("Description")."</td>\n";
    print "  <td align=\"center\">".$langs->trans("Version")."</td>\n";
    //print "  <td align=\"center\">".$langs->trans("DbVersion")."</td>\n";
    print "  <td align=\"center\">".$langs->trans("Status")."</td>\n";
    print "  <td align=\"right\">".$langs->trans("SetupShort")."</td>\n";
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
        $tab=explode('_',$value);
        $family=$tab[0]; $numero=$tab[1];

        $modName = $filename[$key];
    	$objMod  = $modules[$key];
    	//var_dump($objMod);

    	if ($objMod->special != $mode) continue;    // Discard if not for tab
        if (! $objMod->getName())
        {
        	dol_syslog("Error for module ".$key." - Property name of module looks empty", LOG_WARNING);
      		continue;
        }

        $const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));

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
        //if ($oldfamily && $family!=$oldfamily && $atleastoneforfamily) {
        if ($family!=$oldfamily) {
            print '<tr class="liste_titre">'."\n  <td colspan=\"6\">";
            $familytext=empty($familylib[$family])?$family:$familylib[$family];
            print $familytext;
            print "</td>\n</tr>\n";
            $atleastoneforfamily=0;
            //print "<tr><td>yy".$oldfamily."-".$family."-".$atleastoneforfamily."<br></td><tr>";
        }

        if ($objMod->special == $mode)
        {
            $atleastoneforfamily++;

            if ($family!=$oldfamily)
            {
                $familytext=empty($familylib[$family])?$family:$familylib[$family];
                //print $familytext;
                $oldfamily=$family;
            }
            
            $var=!$var;
            print '<tr height="18" '.$bc[$var].">\n";

            // Picto
            print '  <td valign="top" width="14" align="center">';
            $alttext='';
            //if (is_array($objMod->need_dolibarr_version)) $alttext.=($alttext?' - ':'').'Dolibarr >= '.join('.',$objMod->need_dolibarr_version);
            //if (is_array($objMod->phpmin)) $alttext.=($alttext?' - ':'').'PHP >= '.join('.',$objMod->phpmin);
            if (! empty($objMod->picto))
            {
            	if (preg_match('/^\//i',$objMod->picto)) print img_picto($alttext,$objMod->picto,' width="14px"',1);
            	else print img_object($alttext,$objMod->picto,' width="14px"');
            }
            else
            {
            	print img_object($alttext,'generic');
            }
            print '</td>';

            // Name
            print '<td valign="top">'.$objMod->getName();
            print "</td>\n";

            // Desc
            print "<td valign=\"top\">";
            print nl2br($objMod->getDesc());
            print "</td>\n";

            // Version
            print "<td align=\"center\" valign=\"top\" nowrap=\"nowrap\">";
            print $objMod->getVersion();
            print "</td>\n";

            // Activate/Disable and Setup (2 columns)
            if (! empty($conf->global->$const_name))
            {
                $disableSetup = 0;

                print "<td align=\"center\" valign=\"top\">";

            	// Module actif
                if (! empty($objMod->always_enabled) || (($conf->global->MAIN_MODULE_MULTICOMPANY && $objMod->core_enabled) && ($user->entity || $conf->entity!=1)))
                {
                	print $langs->trans("Required");
                	if ($conf->global->MAIN_MODULE_MULTICOMPANY && $user->entity) $disableSetup++;
                	print '</td>'."\n";
                }
                else
                {
                	print '<a href="modules.php?id='.$objMod->numero.'&amp;action=reset&amp;value=' . $modName . '&amp;mode=' . $mode . '">';
                	print img_picto($langs->trans("Activated"),'switch_on');
                	print '</a></td>'."\n";
                }

                if (! empty($objMod->config_page_url) && !$disableSetup)
                {
                    if (is_array($objMod->config_page_url))
                    {
                        print '  <td align="right" valign="top">';
                        $i=0;
                        foreach ($objMod->config_page_url as $page)
                        {
    						$urlpage=$page;
                            if ($i++)
                            {
                                print '<a href="'.$urlpage.'" title="'.$langs->trans($page).'">'.img_picto(ucfirst($page),"setup").'</a>&nbsp;';
                            //    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
                            }
                            else
                            {
                            	if (preg_match('/^([^@]+)@([^@]+)$/i',$urlpage,$regs))
                            	{
                           			print '<a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup").'</a>&nbsp;';
                            	}
                            	else
                            	{
                            		print '<a href="'.$urlpage.'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup").'</a>&nbsp;';
                            	}
                            }
                        }
                        print "</td>\n";
                    }
                    else if (preg_match('/^([^@]+)@([^@]+)$/i',$objMod->config_page_url,$regs))
                    {
                       	print '<td align="right" valign="top"><a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup").'</a></td>';
                    }
                    else
                    {
                        print '<td align="right" valign="top"><a href="'.$objMod->config_page_url.'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup").'</a></td>';
                    }
                }
                else
                {
                    print "<td>&nbsp;</td>";
                }

            }
            else
            {
                print "<td align=\"center\" valign=\"top\">";

                if (! empty($objMod->always_enabled))
                {
                    // Ne devrait pas arriver.
                }

                // Module non actif
               	print '<a href="modules.php?id='.$objMod->numero.'&amp;action=set&amp;value=' . $modName . '&amp;mode=' . $mode . '">';
               	print img_picto($langs->trans("Disabled"),'switch_off');
               	print "</a></td>\n  <td>&nbsp;</td>\n";
            }

            print "</tr>\n";
        }

    }
    print "</table>\n";
}
else
{
    // Marketplace
    print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";
    print "<tr class=\"liste_titre\">\n";
    //print '<td>'.$langs->trans("Logo").'</td>';
    print '<td colspan="2">'.$langs->trans("WebSiteDesc").'</td>';
    print '<td>'.$langs->trans("URL").'</td>';
    print '</tr>';

    $var=!$var;
    print "<tr ".$bc[$var].">\n";
    $url='http://www.dolistore.com';
    print '<td align="left"><a href="'.$url.'" target="_blank"><img border="0" src="'.DOL_URL_ROOT.'/theme/common/dolistore.jpg"></a></td>';
    print '<td>'.$langs->trans("DoliStoreDesc").'</td>';
    print '<td><a href="'.$url.'" target="_blank">'.$url.'</a></td>';
    print '</tr>';


    print "</table>\n";
}


dol_fiche_end();

// Pour eviter bug mise en page IE
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter();
?>
