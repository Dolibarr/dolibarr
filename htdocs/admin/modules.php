<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/admin/modules.php
 *  \brief      Page to activate/disable all modules
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("errors");
$langs->load("admin");

$mode=GETPOST('mode', 'alpha')?GETPOST('mode', 'alpha'):(isset($_SESSION['mode'])?$_SESSION['mode']:0);
$action=GETPOST('action','alpha');
$value=GETPOST('value', 'alpha');

if (! $user->admin)
	accessforbidden();

$specialtostring=array(0=>'common', 1=>'interfaces', 2=>'other', 3=>'functional', 4=>'marketplace');


/*
 * Actions
 */

if ($action == 'set' && $user->admin)
{
    $result=activateModule($value);
    if ($result) setEventMessage($result, 'errors');
    header("Location: modules.php?mode=".$mode);
	exit;
}

if ($action == 'reset' && $user->admin)
{
    $result=unActivateModule($value);
    if ($result) setEventMessage($result, 'errors');
    header("Location: modules.php?mode=".$mode);
	exit;
}


/*
 * View
 */

$form = new Form($db);

$_SESSION["mode"]=$mode;

$help_url='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$help_url);


// Search modules dirs
$modulesdir = array();
foreach ($conf->file->dol_document_root as $type => $dirroot)
{
	$modulesdir[$dirroot . '/core/modules/'] = $dirroot . '/core/modules/';

	$handle=@opendir($dirroot);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
		    if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
		    {
		    	if (is_dir($dirroot . '/' . $file . '/core/modules/'))
		    	{
		    		$modulesdir[$dirroot . '/' . $file . '/core/modules/'] = $dirroot . '/' . $file . '/core/modules/';
		    	}
		    }
		}
		closedir($handle);
	}
}
//var_dump($modulesdir);


$filename = array();
$modules = array();
$orders = array();
$categ = array();
$dirmod = array();
$i = 0;	// is a sequencer of modules found
$j = 0;	// j is module number. Automatically affected if module number not defined.
$modNameLoaded=array();

foreach ($modulesdir as $dir)
{
	// Load modules attributes in arrays (name, numero, orders) from dir directory
	//print $dir."\n<br>";
	dol_syslog("Scan directory ".$dir." for module descriptor files (modXXX.class.php)");
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
		        	if (! empty($modNameLoaded[$modName]))
		        	{
		        		$mesg="Error: Module ".$modName." was found twice: Into ".$modNameLoaded[$modName]." and ".$dir.". You probably have an old file on your disk.<br>";
		        		setEventMessage($mesg, 'warnings');
		        		dol_syslog($mesg, LOG_ERR);
						continue;
		        	}

		            try
		            {
		                $res=include_once $dir.$file;
		                $objMod = new $modName($db);
						$modNameLoaded[$modName]=$dir;

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
    					if ($objMod->version == 'development'  && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 2))) $modulequalified=0;
    					if ($objMod->version == 'experimental' && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 1))) $modulequalified=0;
						// We discard modules according to property disabled
    					if (isset($objMod->hidden) && $objMod->hidden) $modulequalified=false;

    					// Define array $categ with categ with at least one qualified module
    					if ($modulequalified)
    					{
    						$modules[$i] = $objMod;
    			            $filename[$i]= $modName;
    			            $orders[$i]  = $objMod->family."_".$j;   // Sort by family, then by module number
    						$dirmod[$i]  = $dir;
    			            // Set categ[$i]
    						$special     = isset($specialtostring[$objMod->special])?$specialtostring[$objMod->special]:'unknown';
    			            if ($objMod->version == 'development' || $objMod->version == 'experimental') $special='expdev';
    						if (isset($categ[$special])) $categ[$special]++;					// Array of all different modules categories
    			            else $categ[$special]=1;
    						$j++;
    			            $i++;
    					}
    					else dol_syslog("Module ".get_class($objMod)." not qualified");
		            }
		            catch(Exception $e)
		            {
		                 dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
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
//var_dump($categ);
//var_dump($modules);

$nbofactivatedmodules=count($conf->modules);
$moreinfo=$langs->trans("TotalNumberOfActivatedModules",($nbofactivatedmodules-1));
if ($nbofactivatedmodules <= 1) $moreinfo .= ' '.img_warning($langs->trans("YouMustEnableOneModule"));
print load_fiche_titre($langs->trans("ModulesSetup"),$moreinfo,'setup');

// Start to show page
if (empty($mode)) $mode='common';
if ($mode==='common')      print $langs->trans("ModulesDesc")."<br>\n";
if ($mode==='other')       print $langs->trans("ModulesSpecialDesc")."<br>\n";
if ($mode==='interfaces')  print $langs->trans("ModulesInterfaceDesc")."<br>\n";
if ($mode==='functional')  print $langs->trans("ModulesJobDesc")."<br>\n";
if ($mode==='marketplace') print $langs->trans("ModulesMarketPlaceDesc")."<br>\n";
if ($mode==='expdev')      print $langs->trans("ModuleFamilyExperimental")."<br>\n";


//print '<br>'."\n";


$h = 0;

$categidx='common';    // Main
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesCommon");
	$head[$h][2] = 'common';
	$h++;
}

$categidx='other';    // Other
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesOther");
	$head[$h][2] = 'other';
	$h++;
}

$categidx='interfaces';    // Interfaces
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesInterfaces");
	$head[$h][2] = 'interfaces';
	$h++;
}

$categidx='functional';    // Not used
if (! empty($categ[$categidx]))
{
	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
	$head[$h][1] = $langs->trans("ModulesSpecial");
	$head[$h][2] = 'functional';
	$h++;
}

$categidx='expdev';
if (! empty($categ[$categidx]))
{
	$categidx='expdev';
    $head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
    $head[$h][1] = $form->textwithpicto($langs->trans("ModuleFamilyExperimental"), $langs->trans('DoNotUseInProduction'), 1, 'warning', '', 0, 3);
    $head[$h][2] = 'expdev';
    $h++;
}

$categidx='marketplace';
$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
$head[$h][1] = $langs->trans("ModulesMarketPlaces");
$head[$h][2] = 'marketplace';
$h++;


print "<br>\n";


dol_fiche_head($head, $mode, $langs->trans("Modules"));

$var=true;

if ($mode != 'marketplace')
{
    print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";

    /*
    print '<tr class="liste_titre">'."\n";
    print "  <td colspan=\"2\">".$langs->trans("Module")."</td>\n";
    print "  <td>".$langs->trans("Description")."</td>\n";
    print "  <td align=\"center\">".$langs->trans("Version")."</td>\n";
    print '  <td align="center">'.$langs->trans("Status").'</td>'."\n";
    print '  <td align="right">'.$langs->trans("SetupShort").'</td>'."\n";
    print "</tr>\n";
	*/

    // Show list of modules

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

    	//print $objMod->name." - ".$key." - ".$objMod->special.' - '.$objMod->version."<br>";
    	if (($mode != (isset($specialtostring[$objMod->special])?$specialtostring[$objMod->special]:'unknown')	&& $mode != 'expdev')
    		|| ($mode == 'expdev' && $objMod->version != 'development' && $objMod->version != 'experimental')) continue;    // Discard if not for current tab

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
        if ($family!=$oldfamily)
        {
            print '<tr class="liste_titre">'."\n";
            print '<td colspan="5">';
            $familytext=empty($familylib[$family])?$family:$familylib[$family];
            print $familytext;
            print "</td>\n";
    		print '<td align="right">'.$langs->trans("SetupShort").'</td>'."\n";
            print "</tr>\n";
            $atleastoneforfamily=0;
            //print "<tr><td>yy".$oldfamily."-".$family."-".$atleastoneforfamily."<br></td><tr>";
        }

        $atleastoneforfamily++;

        if ($family!=$oldfamily)
        {
        	$familytext=empty($familylib[$family])?$family:$familylib[$family];
        	//print $familytext;
        	$oldfamily=$family;
        }

        $var=!$var;

        //print "\n<!-- Module ".$objMod->numero." ".$objMod->getName()." found into ".$dirmod[$key]." -->\n";
        print '<tr '.$bc[$var].">\n";

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
        print '<td valign="top">';
        print nl2br($objMod->getDesc());
        print "</td>\n";

        // Version
        print '<td align="center" valign="top" class="nowrap">';
        $version=$objMod->getVersion();
        $dirofmodule=$dirmod[$key];
        if ($objMod->isCoreOrExternalModule() == 'external')
        {
        	$text=$langs->trans("ExternalModule",$dirofmodule);
        	if (! empty($objMod->editor_name) && $objMod->editor_name != 'dolibarr') $text.=' - '.$objMod->editor_name;
        	if (! empty($objMod->editor_web) && $objMod->editor_web != 'www.dolibarr.org') $text.=' - '.$objMod->editor_web;
        	print $form->textwithpicto($version, $text, 1, 'help');
        }
        else print $version;
        print "</td>\n";

        // Activate/Disable and Setup (2 columns)
        if (! empty($conf->global->$const_name))
        {
        	$disableSetup = 0;

        	print '<td align="center" valign="middle">';

        	if (! empty($objMod->always_enabled) || ((! empty($conf->multicompany->enabled) && $objMod->core_enabled) && ($user->entity || $conf->entity!=1)))
        	{
        		print $langs->trans("Required");
        		if (! empty($conf->multicompany->enabled) && $user->entity) $disableSetup++;
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
        	print '<td align="center" valign="middle">';

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
    print '<td align="left"><a href="'.$url.'" target="_blank"><img border="0" width="180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a></td>';
    print '<td>'.$langs->trans("DoliStoreDesc").'</td>';
    print '<td><a href="'.$url.'" target="_blank">'.$url.'</a></td>';
    print '</tr>';


    print "</table>\n";
}


dol_fiche_end();


// Show warning about external users
if ($mode != 'marketplace') print '<div class="info">'.showModulesExludedForExternal($modules).'</div><br>'."\n";


llxFooter();

$db->close();
?>
