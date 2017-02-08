<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

$langs->load("errors");
$langs->load("admin");

$mode=GETPOST('mode', 'alpha')?GETPOST('mode', 'alpha'):0;
$action=GETPOST('action','alpha');
$id = GETPOST('id', 'int');

if (! $user->admin)
	accessforbidden();



/*
 * Actions
 */

// Nothing


/*
 * View
 */

$form = new Form($db);

$help_url='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$help_url);

print '<!-- Force style container -->'."\n".'<style>
.id-container {
    width: 100%;
} 
</style>';

$arrayofnatures=array('core'=>$langs->transnoentitiesnoconv("Core"), 'external'=>$langs->transnoentitiesnoconv("External").' - '.$langs->trans("AllPublishers"));

// Search modules dirs
$modulesdir = dolGetModulesDirs();


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
		        		setEventMessages($mesg, null, 'warnings');
		        		dol_syslog($mesg, LOG_ERR);
						continue;
		        	}

		            try
		            {
		                $res=include_once $dir.$file;
		                if (class_exists($modName))
						{
							try {
				                $objMod = new $modName($db);
								$modNameLoaded[$modName]=$dir;

    		    		        if (! $objMod->numero > 0 && $modName != 'modUser')
    		            		{
    		         		    	dol_syslog('The module descriptor '.$modName.' must have a numero property', LOG_ERR);
    		            		}
								$j = $objMod->numero;

    							$modulequalified=1;

		    					// We discard modules according to features level (PS: if module is activated we always show it)
		    					$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));
		    					if ($objMod->version == 'development'  && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 2))) $modulequalified=0;
		    					if ($objMod->version == 'experimental' && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL < 1))) $modulequalified=0;
								if (preg_match('/deprecated/', $objMod->version) && (empty($conf->global->$const_name) && ($conf->global->MAIN_FEATURES_LEVEL >= 0))) $modulequalified=0;

		    					// We discard modules according to property disabled
		    					if (! empty($objMod->hidden)) $modulequalified=0;

		    					if ($modulequalified > 0)
		    					{
		    					    $publisher=dol_escape_htmltag($objMod->getPublisher());
		    					    $external=($objMod->isCoreOrExternalModule() == 'external');
		    					    if ($external)
		    					    {
		    					        if ($publisher)
		    					        {
		    					            $arrayofnatures['external_'.$publisher]=$langs->trans("External").' - '.$publisher;
		    					        }
		    					        else
		    					        {
		    					            $arrayofnatures['external_']=$langs->trans("External").' - '.$langs->trans("UnknownPublishers");
		    					        }
		    					    }
		    					    ksort($arrayofnatures);
		    					}

		    					// Define array $categ with categ with at least one qualified module
		    					if ($modulequalified > 0)
		    					{
		    						$modules[$i] = $objMod;
		    			            $filename[$i]= $modName;

		    			            $special = $objMod->special;

		    			            // Gives the possibility to the module, to provide his own family info and position of this family
		    			            if (is_array($objMod->familyinfo) && !empty($objMod->familyinfo)) {
		    			            	$familyinfo = array_merge($familyinfo, $objMod->familyinfo);
		    			            	$familykey = key($objMod->familyinfo);
		    			            } else {
		    			            	$familykey = $objMod->family;
		    			            }

		    			            $moduleposition = ($objMod->module_position?$objMod->module_position:'500');
		    			            if ($moduleposition == 500 && ($objMod->isCoreOrExternalModule() == 'external'))
		    			            {
		    			                $moduleposition = 800;
		    			            }

		    			            if ($special == 1) $familykey='interface';

		    			            $orders[$i]  = $familyinfo[$familykey]['position']."_".$familykey."_".$moduleposition."_".$j;   // Sort by family, then by module position then number
		    						$dirmod[$i]  = $dir;
		    						//print $i.'-'.$dirmod[$i].'<br>';
		    			            // Set categ[$i]
		    						$specialstring = isset($specialtostring[$special])?$specialtostring[$special]:'unknown';
		    			            if ($objMod->version == 'development' || $objMod->version == 'experimental') $specialstring='expdev';
		    						if (isset($categ[$specialstring])) $categ[$specialstring]++;					// Array of all different modules categories
		    			            else $categ[$specialstring]=1;
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
		            	else
						{
							print "Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)<br>";
						}
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
		dol_syslog("htdocs/admin/modulehelp.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
	}
}

asort($orders);
//var_dump($orders);
//var_dump($categ);
//var_dump($modules);



$h = 0;

$categidx='desc';    // Main
$head[$h][0] = DOL_URL_ROOT."/admin/modulehelp.php?id=".$id.'&mode=desc';
$head[$h][1] = $langs->trans("Description");
$head[$h][2] = 'desc';
$h++;

/*$categidx='feature';
$head[$h][0] = DOL_URL_ROOT."/admin/modulehelp.php?id=".$id.'&mode=feature';
$head[$h][1] = $langs->trans("Features");
$head[$h][2] = 'feature';
$h++;*/


$i=0;
foreach($orders as $tmpkey => $tmpvalue)
{
    $objMod  = $modules[$tmpkey];
    if ($objMod->numero == $id)
    {
        $key = $i;
        break;
    }
    $i++;
}
$value = $orders[$key];


print '<div class="centpercent">';


print load_fiche_titre($objMod->getDesc(),$moreinfo,'object_'.$objMod->picto);
print '<br>';

dol_fiche_head($head, $mode, $title);



$tab=explode('_',$value);
$familyposition=$tab[0]; $familykey=$tab[1]; $module_position=$tab[2]; $numero=$tab[3];

$modName = $filename[$key];
$objMod  = $modules[$key];
$dirofmodule = $dirmod[$key];

$special = $objMod->special;

if (! $objMod->getName())
{
	dol_syslog("Error for module ".$key." - Property name of module looks empty", LOG_WARNING);
}

$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));

// Check filters
$modulename=$objMod->getName();
$moduledesc=$objMod->getDesc();
$moduledesclong=$objMod->getDescLong();
$moduleauthor=$objMod->getPublisher();

// We discard showing according to filters
if ($search_keyword)
{
    $qualified=0;
    if (preg_match('/'.preg_quote($search_keyword).'/i', $modulename)
        || preg_match('/'.preg_quote($search_keyword).'/i', $moduledesc)
        || preg_match('/'.preg_quote($search_keyword).'/i', $moduledesclong)
        || preg_match('/'.preg_quote($search_keyword).'/i', $moduleauthor)
        ) $qualified=1;
    if (! $qualified) continue;
}
if ($search_status)
{
    if ($search_status == 'active' && empty($conf->global->$const_name)) continue;
    if ($search_status == 'disabled' && ! empty($conf->global->$const_name)) continue;
}
if ($search_nature)
{
    if (preg_match('/^external/',$search_nature) && $objMod->isCoreOrExternalModule() != 'external') continue;
    if (preg_match('/^external_(.*)$/',$search_nature, $reg))
    {
        //print $reg[1].'-'.dol_escape_htmltag($objMod->getPublisher());
        $publisher=dol_escape_htmltag($objMod->getPublisher());
        if ($reg[1] && dol_escape_htmltag($reg[1]) != $publisher) continue;
        if (! $reg[1] && ! empty($publisher)) continue;
    }
    if ($search_nature == 'core' && $objMod->isCoreOrExternalModule() == 'external') continue;
}
if ($search_version)
{
    if (($objMod->version == 'development' || $objMod->version == 'experimental' || preg_match('/deprecated/', $objMod->version)) && $search_version == 'stable') continue;
    if ($objMod->version != 'development'  && ($search_version == 'development')) continue;
    if ($objMod->version != 'experimental' && ($search_version == 'experimental')) continue;
    if (! preg_match('/deprecated/', $objMod->version) && ($search_version == 'deprecated')) continue;
}

// Load all lang files of module
if (isset($objMod->langfiles) && is_array($objMod->langfiles))
{
	foreach($objMod->langfiles as $domain)
	{
		$langs->load($domain);
	}
}

$var=!$var;


// Version (with picto warning or not)
$version=$objMod->getVersion(0);
$versiontrans='';
if (preg_match('/development/i', $version))  $versiontrans.=img_warning($langs->trans("Development"), 'style="float: left"');
if (preg_match('/experimental/i', $version)) $versiontrans.=img_warning($langs->trans("Experimental"), 'style="float: left"');
if (preg_match('/deprecated/i', $version))   $versiontrans.=img_warning($langs->trans("Deprecated"), 'style="float: left"');
$versiontrans.=$objMod->getVersion(1);

// Define imginfo
$imginfo="info";
if ($objMod->isCoreOrExternalModule() == 'external')
{
    $imginfo="info_black";
}

// Define text of description of module
$text='';

if ($objMod->getDescLong()) $text.=$objMod->getDesc().'<br>';

$text.='<br><strong>'.$langs->trans("Version").':</strong> '.$version;

$textexternal='';
if ($objMod->isCoreOrExternalModule() == 'external')
{
    $textexternal.='<br><strong>'.$langs->trans("Origin").':</strong> '.$langs->trans("ExternalModule",$dirofmodule);
    if ($objMod->editor_name != 'dolibarr') $textexternal.='<br><strong>'.$langs->trans("Publisher").':</strong> '.(empty($objMod->editor_name)?$langs->trans("Unknown"):$objMod->editor_name);
    if (! empty($objMod->editor_url) && ! preg_match('/dolibarr\.org/i',$objMod->editor_url)) $textexternal.='<br><strong>'.$langs->trans("Url").':</strong> '.$objMod->editor_url;
    $text.=$textexternal;
    $text.='<br>';
}
else
{
    $text.='<br><strong>'.$langs->trans("Origin").':</strong> '.$langs->trans("Core").'<br>';
}
$text.='<br><strong>'.$langs->trans("LastActivationDate").':</strong> ';
if (! empty($conf->global->$const_name)) $text.=dol_print_date($objMod->getLastActivationDate(), 'dayhour');
else $text.=$langs->trans("Disabled");
$text.='<br>';

$text.='<br><strong>'.$langs->trans("AddRemoveTabs").':</strong> ';
if (isset($objMod->tabs) && is_array($objMod->tabs) && count($objMod->tabs))
{
    $i=0;
    foreach($objMod->tabs as $val)
    {
        $tmp=explode(':',$val,3);
        $text.=($i?', ':'').$tmp[0].':'.$tmp[1];
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddDictionaries").':</strong> ';
if (isset($objMod->dictionaries) && isset($objMod->dictionaries['tablib']) && is_array($objMod->dictionaries['tablib']) && count($objMod->dictionaries['tablib']))
{
    $i=0;
    foreach($objMod->dictionaries['tablib'] as $val)
    {
        $text.=($i?', ':'').$val;
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddBoxes").':</strong> ';
if (isset($objMod->boxes) && is_array($objMod->boxes) && count($objMod->boxes))
{
    $i=0;
    foreach($objMod->boxes as $val)
    {
        $text.=($i?', ':'').($val['file']?$val['file']:$val[0]);
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddModels").':</strong> ';
if (isset($objMod->module_parts) && isset($objMod->module_parts['models']) && $objMod->module_parts['models'])
{
    $text.=$langs->trans("Yes");
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddSubstitutions").':</strong> ';
if (isset($objMod->module_parts) && isset($objMod->module_parts['substitutions']) && $objMod->module_parts['substitutions'])
{
    $text.=$langs->trans("Yes");
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddSheduledJobs").':</strong> ';
if (isset($objMod->cronjobs) && is_array($objMod->cronjobs) && count($objMod->cronjobs))
{
    $i=0;
    foreach($objMod->cronjobs as $val)
    {
        $text.=($i?', ':'').($val['label']);
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddTriggers").':</strong> ';
if (isset($objMod->module_parts) && isset($objMod->module_parts['triggers']) && $objMod->module_parts['triggers'])
{
    $text.=$langs->trans("Yes");
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddHooks").':</strong> ';
if (isset($objMod->module_parts) && is_array($objMod->module_parts['hooks']) && count($objMod->module_parts['hooks']))
{
    $i=0;
    foreach($objMod->module_parts['hooks'] as $val)
    {
        $text.=($i?', ':'').($val);
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddPermissions").':</strong> ';
if (isset($objMod->rights) && is_array($objMod->rights) && count($objMod->rights))
{
    $i=0;
    foreach($objMod->rights as $val)
    {
        $text.=($i?', ':'').($val[1]);
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddMenus").':</strong> ';
if (isset($objMod->menu) && ! empty($objMod->menu)) // objMod can be an array or just an int 1
{
    $text.=$langs->trans("Yes");
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddExportProfiles").':</strong> ';
if (isset($objMod->export_label) && is_array($objMod->export_label) && count($objMod->export_label))
{
    $i=0;
    foreach($objMod->export_label as $val)
    {
        $text.=($i?', ':'').($val);
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddImportProfiles").':</strong> ';
if (isset($objMod->import_label) && is_array($objMod->import_label) && count($objMod->import_label))
{
    $i=0;
    foreach($objMod->import_label as $val)
    {
        $text.=($i?', ':'').($val);
        $i++;
    }
}
else $text.=$langs->trans("No");

$text.='<br><strong>'.$langs->trans("AddOtherPagesOrServices").':</strong> ';
$text.=$langs->trans("DetectionNotPossible");

print $text;
        


dol_fiche_end();

print '</div>';


llxFooter();

$db->close();
