<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

$langs->load("errors");
$langs->load("admin");

$mode=GETPOST('mode', 'alpha')?GETPOST('mode', 'alpha'):0;
$action=GETPOST('action','alpha');
$value=GETPOST('value', 'alpha');
$page_y=GETPOST('page_y','int');
$search_keyword=GETPOST('search_keyword','alpha');
$search_status=GETPOST('search_status','alpha');
$search_nature=GETPOST('search_nature','alpha');
$search_version=GETPOST('search_version','alpha');

if (! $user->admin)
	accessforbidden();

$specialtostring=array(0=>'common', 1=>'interfaces', 2=>'other', 3=>'functional', 4=>'marketplace');

$familyinfo=array(
	'hr'=>array('position'=>'001', 'label'=>$langs->trans("ModuleFamilyHr")),
	'crm'=>array('position'=>'006', 'label'=>$langs->trans("ModuleFamilyCrm")),
	'srm'=>array('position'=>'007', 'label'=>$langs->trans("ModuleFamilySrm")),
    'financial'=>array('position'=>'009', 'label'=>$langs->trans("ModuleFamilyFinancial")),
	'products'=>array('position'=>'012', 'label'=>$langs->trans("ModuleFamilyProducts")),
	'projects'=>array('position'=>'015', 'label'=>$langs->trans("ModuleFamilyProjects")),
	'ecm'=>array('position'=>'018', 'label'=>$langs->trans("ModuleFamilyECM")),
	'technic'=>array('position'=>'021', 'label'=>$langs->trans("ModuleFamilyTechnic")),
	'portal'=>array('position'=>'040', 'label'=>$langs->trans("ModuleFamilyPortal")),
	'interface'=>array('position'=>'050', 'label'=>$langs->trans("ModuleFamilyInterface")),
	'base'=>array('position'=>'060', 'label'=>$langs->trans("ModuleFamilyBase")),
	'other'=>array('position'=>'100', 'label'=>$langs->trans("ModuleFamilyOther")),
);

$param='';
if ($search_keyword) $param.='&search_keyword='.urlencode($search_keyword);
if ($search_status)  $param.='&search_status='.urlencode($search_status);
if ($search_nature)  $param.='&search_nature='.urlencode($search_nature);
if ($search_version) $param.='&search_version='.urlencode($search_version);



/*
 * Actions
 */


if (GETPOST('buttonreset'))
{
    $search_keyword='';
    $search_status='';
    $search_nature='';
    $search_version='';
}

if ($action == 'set' && $user->admin)
{
    $resarray = activateModule($value);
    if (! empty($resarray['errors'])) setEventMessages('', $resarray['errors'], 'errors');
	else
	{
	    //var_dump($resarray);exit;
	    if ($resarray['nbperms'] > 0)
	    {
    		$msg = $langs->trans('ModuleEnabledAdminMustCheckRights');
    		setEventMessages($msg, null, 'warnings');
	    }
	}
    header("Location: modules.php?mode=".$mode.$param.($page_y?'&page_y='.$page_y:''));
	exit;
}

if ($action == 'reset' && $user->admin)
{
    $result=unActivateModule($value);
    if ($result) setEventMessages($result, null, 'errors');
    header("Location: modules.php?mode=".$mode.$param.($page_y?'&page_y='.$page_y:''));
	exit;
}



/*
 * View
 */

$form = new Form($db);

$help_url='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$help_url);

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
		dol_syslog("htdocs/admin/modules.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
	}
}

asort($orders);
//var_dump($orders);
//var_dump($categ);
//var_dump($modules);

$nbofactivatedmodules=count($conf->modules);
$moreinfo=$langs->trans("TotalNumberOfActivatedModules",($nbofactivatedmodules-1), count($modules));
if ($nbofactivatedmodules <= 1) $moreinfo .= ' '.img_warning($langs->trans("YouMustEnableOneModule"));
print load_fiche_titre($langs->trans("ModulesSetup"),$moreinfo,'title_setup');

// Start to show page
if (empty($mode)) $mode='common';
if ($mode==='common')      print $langs->trans("ModulesDesc")."<br>\n";
if ($mode==='marketplace') print $langs->trans("ModulesMarketPlaceDesc")."<br>\n";
if ($mode==='expdev')      print $langs->trans("ModuleFamilyExperimental")."<br>\n";


$h = 0;

$categidx='common';    // Main
$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
$head[$h][1] = $langs->trans("AvailableModules");
$head[$h][2] = 'common';
$h++;

$categidx='marketplace';
$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=".$categidx;
$head[$h][1] = $langs->trans("ModulesMarketPlaces");
$head[$h][2] = 'marketplace';
$h++;


print "<br>\n";


$var=true;

if ($mode != 'marketplace')
{
    
    print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

    dol_fiche_head($head, $mode, '');
    
    $moreforfilter = '';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('Keyword') . ': <input type="text" name="search_keyword" value="'.dol_escape_htmltag($search_keyword).'">';
    $moreforfilter.= '</div>';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('Origin') . ': '.$form->selectarray('search_nature', $arrayofnatures, dol_escape_htmltag($search_nature), 1);
    $moreforfilter.= '</div>';
    if (! empty($conf->global->MAIN_FEATURES_LEVEL))
    {
        $array_version = array('stable'=>$langs->transnoentitiesnoconv("Stable"));
        if ($conf->global->MAIN_FEATURES_LEVEL < 0) $array_version['deprecated']=$langs->trans("Deprecated");
        if ($conf->global->MAIN_FEATURES_LEVEL > 0) $array_version['experimental']=$langs->trans("Experimental");
        if ($conf->global->MAIN_FEATURES_LEVEL > 1) $array_version['development']=$langs->trans("Development");
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.= $langs->trans('Version') . ': '.$form->selectarray('search_version', $array_version, $search_version, 1);
        $moreforfilter.= '</div>';
    }
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('Status') . ': '.$form->selectarray('search_status', array('active'=>$langs->transnoentitiesnoconv("Enabled"), 'disabled'=>$langs->transnoentitiesnoconv("Disabled")), $search_status, 1);
    $moreforfilter.= '</div>';
    $moreforfilter.=' ';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.='<input type="submit" name="buttonsubmit" class="button" value="'.dol_escape_htmltag($langs->trans("Refresh")).'">';
    $moreforfilter.=' ';
    $moreforfilter.='<input type="submit" name="buttonreset" class="button" value="'.dol_escape_htmltag($langs->trans("Reset")).'">';
    $moreforfilter.= '</div>';

    if (! empty($moreforfilter))
    {
        //print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        //print '</div>';
    }
    
    //dol_fiche_end();
    
    print '<div class="clearboth"></div><br>';
    //print '<br><br><br><br>';

    $moreforfilter='';
    
    // Show list of modules
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'" summary="list_of_modules" id="list_of_modules" >'."\n";

    $oldfamily='';

    foreach ($orders as $key => $value)
    {
        $tab=explode('_',$value);
        $familyposition=$tab[0]; $familykey=$tab[1]; $module_position=$tab[2]; $numero=$tab[3];

        $modName = $filename[$key];
    	$objMod  = $modules[$key];
    	$dirofmodule = $dirmod[$key];

    	$special = $objMod->special;

    	//print $objMod->name." - ".$key." - ".$objMod->special.' - '.$objMod->version."<br>";
    	//if (($mode != (isset($specialtostring[$special])?$specialtostring[$special]:'unknown') && $mode != 'expdev')
    	if (($special >= 4 && $mode != 'expdev')
    		|| ($mode == 'expdev' && $objMod->version != 'development' && $objMod->version != 'experimental')) continue;    // Discard if not for current tab

        if (! $objMod->getName())
        {
        	dol_syslog("Error for module ".$key." - Property name of module looks empty", LOG_WARNING);
      		continue;
        }

        $const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));

        // Check filters
        $modulename=$objMod->getName();
        $moduletechnicalname=$objMod->name;
        $moduledesc=$objMod->getDesc();
        $moduledesclong=$objMod->getDescLong();
        $moduleauthor=$objMod->getPublisher();

        // We discard showing according to filters
        if ($search_keyword)
        {
            $qualified=0;
            if (preg_match('/'.preg_quote($search_keyword).'/i', $modulename)
                || preg_match('/'.preg_quote($search_keyword).'/i', $moduletechnicalname)
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

        // Print a separator if we change family
        //print "<tr><td>xx".$oldfamily."-".$familykey."-".$atleastoneforfamily."<br></td><tr>";
        //if ($oldfamily && $familykey!=$oldfamily && $atleastoneforfamily) {
        if ($familykey!=$oldfamily)
        {
            print '<tr class="liste_titre">'."\n";
            print '<td colspan="5">';
            $familytext=empty($familyinfo[$familykey]['label'])?$familykey:$familyinfo[$familykey]['label'];
            print $familytext;
            print "</td>\n";
    		print '<td colspan="2" align="right">'.$langs->trans("SetupShort").'</td>'."\n";
            print "</tr>\n";
            $atleastoneforfamily=0;
            //print "<tr><td>yy".$oldfamily."-".$familykey."-".$atleastoneforfamily."<br></td><tr>";
        }

        $atleastoneforfamily++;

        if ($familykey!=$oldfamily)
        {
        	$familytext=empty($familyinfo[$familykey]['label'])?$familykey:$familyinfo[$familykey]['label'];
        	$oldfamily=$familykey;
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
        print '<td class="tdtop">'.$objMod->getName();
        print "</td>\n";

        // Desc
        print '<td class="tdtop">';
        print nl2br($objMod->getDesc());
        print "</td>\n";

        // Help
        print '<td align="center" valign="top" class="nowrap" style="width: 82px;">';
        $text='';

        //if ($objMod->getDescLong()) $text.='<div class="titre">'.$objMod->getDesc().'</div><br>'.$objMod->getDescLong().'<br>';
        //else $text.='<div class="titre">'.$objMod->getDesc().'</div><br>';
        $text.='<div class="titre">'.$objMod->getDesc().'</div><br>';
            
        $textexternal='';
        $imginfo="info";
        if ($objMod->isCoreOrExternalModule() == 'external')
        {
 	    $imginfo="info_black";
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


        print $form->textwithpicto('', $text, 1, $imginfo, 'minheight20');

        print '</td>';

        // Version
        print '<td align="center" valign="top" class="nowrap">';

        // Picto warning
        $version=$objMod->getVersion(0);
        $versiontrans=$objMod->getVersion(1);
        if (preg_match('/development/i', $version))  print img_warning($langs->trans("Development"), 'style="float: left"');
        if (preg_match('/experimental/i', $version)) print img_warning($langs->trans("Experimental"), 'style="float: left"');
        if (preg_match('/deprecated/i', $version))   print img_warning($langs->trans("Deprecated"), 'style="float: left"');


        print $versiontrans;

        print "</td>\n";

        // Activate/Disable and Setup (2 columns)
        if (! empty($conf->global->$const_name))	// If module is activated
        {
        	$disableSetup = 0;

        	print '<td align="center" valign="middle">';
            if (! empty($objMod->disabled))
        	{
        		print $langs->trans("Disabled");
        	}
        	else if (! empty($objMod->always_enabled) || ((! empty($conf->multicompany->enabled) && $objMod->core_enabled) && ($user->entity || $conf->entity!=1)))
        	{
        		print $langs->trans("Required");
        		if (! empty($conf->multicompany->enabled) && $user->entity) $disableSetup++;
        	}
        	else
        	{
        		print '<a class="reposition" href="modules.php?id='.$objMod->numero.'&amp;module_position='.$module_position.'&amp;action=reset&amp;value=' . $modName . '&amp;mode=' . $mode . $param . '">';
        		print img_picto($langs->trans("Activated"),'switch_on');
        		print '</a>';
        	}
        	print '</td>'."\n";

        	// Config link
        	if (! empty($objMod->config_page_url) && !$disableSetup)
        	{
        		if (is_array($objMod->config_page_url))
        		{
        			print '<td class="tdsetuppicto" align="right" valign="top">';
        			$i=0;
        			foreach ($objMod->config_page_url as $page)
        			{
        				$urlpage=$page;
        				if ($i++)
        				{
        					print '<a href="'.$urlpage.'" title="'.$langs->trans($page).'">'.img_picto(ucfirst($page),"setup").'</a>';
        					//    print '<a href="'.$page.'">'.ucfirst($page).'</a>&nbsp;';
        				}
        				else
        				{
        					if (preg_match('/^([^@]+)@([^@]+)$/i',$urlpage,$regs))
        					{
        						print '<a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a>';
        					}
        					else
        					{
        						print '<a href="'.$urlpage.'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a>';
        					}
        				}
        			}
        			print "</td>\n";
        		}
        		else if (preg_match('/^([^@]+)@([^@]+)$/i',$objMod->config_page_url,$regs))
        		{
        			print '<td class="tdsetuppicto" align="right" valign="middle"><a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a></td>';
        		}
        		else
        		{
        			print '<td class="tdsetuppicto" align="right" valign="middle"><a href="'.$objMod->config_page_url.'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a></td>';
        		}
        	}
        	else
        	{
        		print '<td class="tdsetuppicto" align="right" valign="middle">'.img_picto($langs->trans("NothingToSetup"),"setup",'class="opacitytransp" style="padding-right: 6px"').'</td>';
        	}

        }
        else	// Module not activated
		{
        	print '<td align="center" valign="middle">';
		    if (! empty($objMod->always_enabled))
        	{
        		// Ne devrait pas arriver.
        	}
        	else if (! empty($objMod->disabled))
        	{
        		print $langs->trans("Disabled");
        	}
        	else
        	{
	        	// Module non actif
	        	print '<a class="reposition" href="modules.php?id='.$objMod->numero.'&amp;module_position='.$module_position.'&amp;action=set&amp;value=' . $modName . '&amp;mode=' . $mode . $param . '">';
	        	print img_picto($langs->trans("Disabled"),'switch_off');
	        	print "</a>\n";
        	}
        	print "</td>\n";
        	print '<td class="tdsetuppicto" align="right" valign="middle">'.img_picto($langs->trans("NothingToSetup"),"setup",'class="opacitytransp" style="padding-right: 6px"').'</td>';
        }

        print "</tr>\n";

    }
    print "</table>\n";
    print '</div>';
}
else
{
    dol_fiche_head($head, $mode, '');
    
    // Marketplace
    print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";
    print "<tr class=\"liste_titre\">\n";
    //print '<td>'.$langs->trans("Logo").'</td>';
    print '<td colspan="2">'.$langs->trans("WebSiteDesc").'</td>';
    print '<td>'.$langs->trans("URL").'</td>';
    print '</tr>';

    $var=!$var;
    print "<tr ".$bc[$var].">\n";
    $url='https://www.dolistore.com';
    print '<td align="left"><a href="'.$url.'" target="_blank" rel="external"><img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a></td>';
    print '<td>'.$langs->trans("DoliStoreDesc").'</td>';
    print '<td><a href="'.$url.'" target="_blank" rel="external">'.$url.'</a></td>';
    print '</tr>';

    $var=!$var;
    print "<tr ".$bc[$var].">\n";
    $url='https://partners.dolibarr.org';
    print '<td align="left"><a href="'.$url.'" target="_blank" rel="external"><img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolibarr_preferred_partner_int.png"></a></td>';
    print '<td>'.$langs->trans("DoliPartnersDesc").'</td>';
    print '<td><a href="'.$url.'" target="_blank" rel="external">'.$url.'</a></td>';
    print '</tr>';

    print "</table>\n";

    //dol_fiche_end();
}

dol_fiche_end();

// Show warning about external users
if ($mode != 'marketplace') print info_admin(showModulesExludedForExternal($modules))."\n";


llxFooter();

$db->close();
