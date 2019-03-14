<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2015		Raphaël Doursenaud		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018		Nicolas ZABOURI 		<info@inovea-conseil.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/admin/dolistore/class/dolistore.class.php';

// Load translation files required by the page
$langs->loadLangs(array("errors","admin","modulebuilder"));

$mode=GETPOST('mode', 'alpha');
if (empty($mode)) $mode='common';
$action=GETPOST('action','alpha');
//var_dump($_POST);exit;
$value=GETPOST('value', 'alpha');
$page_y=GETPOST('page_y','int');
$search_keyword=GETPOST('search_keyword','alpha');
$search_status=GETPOST('search_status','alpha');
$search_nature=GETPOST('search_nature','alpha');
$search_version=GETPOST('search_version','alpha');


// For dolistore search
$options              = array();
$options['per_page']  = 20;
$options['categorie'] = ((GETPOST('categorie', 'int')?GETPOST('categorie', 'int'):0) + 0);
$options['start']     = ((GETPOST('start', 'int')?GETPOST('start', 'int'):0) + 0);
$options['end']       = ((GETPOST('end', 'int')?GETPOST('end', 'int'):0) + 0);
$options['search']    = GETPOST('search_keyword', 'alpha');
$dolistore            = new Dolistore(false);


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
if (! GETPOST('buttonreset','alpha'))
{
	if ($search_keyword) $param.='&search_keyword='.urlencode($search_keyword);
	if ($search_status && $search_status != '-1')  $param.='&search_status='.urlencode($search_status);
	if ($search_nature && $search_nature != '-1')  $param.='&search_nature='.urlencode($search_nature);
	if ($search_version && $search_version != '-1') $param.='&search_version='.urlencode($search_version);
}

$dirins=DOL_DOCUMENT_ROOT.'/custom';
$urldolibarrmodules='https://www.dolistore.com/';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('adminmodules','globaladmin'));


/*
 * Actions
 */

$formconfirm = '';

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (GETPOST('buttonreset','alpha'))
{
    $search_keyword='';
    $search_status='';
    $search_nature='';
    $search_version='';
}

if ($action=='install')
{
    $error=0;

    // $original_file should match format module_modulename-x.y[.z].zip
    $original_file=basename($_FILES["fileinstall"]["name"]);
    $newfile=$conf->admin->dir_temp.'/'.$original_file.'/'.$original_file;

    if (! $original_file)
    {
        $langs->load("Error");
        setEventMessages($langs->trans("ErrorModuleFileRequired"), null, 'warnings');
        $error++;
    }
    else
    {
        if (! preg_match('/\.zip$/i',$original_file))
        {
            $langs->load("errors");
            setEventMessages($langs->trans("ErrorFileMustBeADolibarrPackage",$original_file), null, 'errors');
            $error++;
        }
    	if (! preg_match('/module_.*\-[\d]+\.[\d]+.*$/i',$original_file))
		{
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFilenameDosNotMatchDolibarrPackageRules",$original_file, 'module_*-x.y*.zip'), null, 'errors');
			$error++;
		}
		if (empty($_FILES['fileinstall']['tmp_name']))
		{
		    $langs->load("errors");
		    setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
		    $error++;
		}
    }

    if (! $error)
    {
        if ($original_file)
        {
            @dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$original_file);
            dol_mkdir($conf->admin->dir_temp.'/'.$original_file);
        }

        $tmpdir=preg_replace('/\.zip$/i','',$original_file).'.dir';
        if ($tmpdir)
        {
            @dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$tmpdir);
            dol_mkdir($conf->admin->dir_temp.'/'.$tmpdir);
        }

        $result=dol_move_uploaded_file($_FILES['fileinstall']['tmp_name'], $newfile, 1, 0, $_FILES['fileinstall']['error']);
        if ($result > 0)
        {
            $result=dol_uncompress($newfile,$conf->admin->dir_temp.'/'.$tmpdir);

            if (! empty($result['error']))
            {
                $langs->load("errors");
                setEventMessages($langs->trans($result['error'],$original_file), null, 'errors');
                $error++;
            }
            else
            {
                // Now we move the dir of the module
                $modulename=preg_replace('/module_/', '', $original_file);
                $modulename=preg_replace('/\-[\d]+\.[\d]+.*$/', '', $modulename);
                // Search dir $modulename
                $modulenamedir=$conf->admin->dir_temp.'/'.$tmpdir.'/'.$modulename;
                //var_dump($modulenamedir);
                if (! dol_is_dir($modulenamedir))
                {
                    $modulenamedir=$conf->admin->dir_temp.'/'.$tmpdir.'/htdocs/'.$modulename;
                    //var_dump($modulenamedir);
                    if (! dol_is_dir($modulenamedir))
                    {
						setEventMessages($langs->trans("ErrorModuleFileSeemsToHaveAWrongFormat").'<br>Dir not found: '.$conf->admin->dir_temp.'/'.$tmpdir.'/'.$modulename.'<br>'.$conf->admin->dir_temp.'/'.$tmpdir.'/htdocs/'.$modulename, null, 'errors');
                        $error++;
                    }
                }

                if (! $error)
                {
                    //var_dump($dirins);
                    @dol_delete_dir_recursive($dirins.'/'.$modulename);
                    dol_syslog("Uncompress of module file is a success. We copy it from ".$modulenamedir." into target dir ".$dirins.'/'.$modulename);
                    $result=dolCopyDir($modulenamedir, $dirins.'/'.$modulename, '0444', 1);
                    if ($result <= 0)
                    {
                        dol_syslog('Failed to call dolCopyDir result='.$result." with param ".$modulenamedir." and ".$dirins.'/'.$modulename, LOG_WARNING);
                        $langs->load("errors");
                        setEventMessages($langs->trans("ErrorFailToCopyDir", $modulenamedir, $dirins.'/'.$modulename), null, 'errors');
                        $error++;
                    }
                }
            }
        }
        else
        {
            setEventMessages($langs->trans("ErrorFailToRenameFile", $_FILES['fileinstall']['tmp_name'], $newfile), null, 'errors');
            $error++;
        }
    }

    if (! $error)
    {
        setEventMessages($langs->trans("SetupIsReadyForUse", DOL_URL_ROOT.'/admin/modules.php?mainmenu=home', $langs->transnoentitiesnoconv("Home").' - '.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Modules")), null, 'warnings');
    }
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
	        $tmpsql="SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."user WHERE admin <> 1";
	        $resqltmp=$db->query($tmpsql);
	        if ($resqltmp)
	        {
	            $obj=$db->fetch_object($resqltmp);
	            //var_dump($obj->nb);exit;
	            if ($obj && $obj->nb > 1)
	            {
	                $msg = $langs->trans('ModuleEnabledAdminMustCheckRights');
	                setEventMessages($msg, null, 'warnings');
	            }
	        }
	        else dol_print_error($db);
	    }
	}
    header("Location: ".$_SERVER["PHP_SELF"]."?mode=".$mode.$param.($page_y?'&page_y='.$page_y:''));
	exit;
}
else if ($action == 'reset' && $user->admin && GETPOST('confirm') == 'yes')
{
    $result=unActivateModule($value);
    if ($result) setEventMessages($result, null, 'errors');
    header("Location: ".$_SERVER["PHP_SELF"]."?mode=".$mode.$param.($page_y?'&page_y='.$page_y:''));
	exit;
}



/*
 * View
 */

$form = new Form($db);

//$morejs  = array("/admin/dolistore/js/dolistore.js.php");
$morecss = array("/admin/dolistore/css/dolistore.css");

// Set dir where external modules are installed
if (! dol_is_dir($dirins))
{
    dol_mkdir($dirins);
}
$dirins_ok=(dol_is_dir($dirins));

$help_url='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$help_url, '', '', '', $morejs, $morecss, 0, 0);


// Search modules dirs
$modulesdir = dolGetModulesDirs();

$arrayofnatures=array('core'=>$langs->transnoentitiesnoconv("Core"), 'external'=>$langs->transnoentitiesnoconv("External").' - ['.$langs->trans("AllPublishers").']');
$arrayofwarnings=array();    // Array of warning each module want to show when activated
$arrayofwarningsext=array();    // Array of warning each module want to show when we activate an external module
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
		        	if (! empty($modNameLoaded[$modName]))   // In cache of already loaded modules ?
		        	{
		        		$mesg="Error: Module ".$modName." was found twice: Into ".$modNameLoaded[$modName]." and ".$dir.". You probably have an old file on your disk.<br>";
		        		setEventMessages($mesg, null, 'warnings');
		        		dol_syslog($mesg, LOG_ERR);
						continue;
		        	}

		            try
		            {
		                $res=include_once $dir.$file;     // A class already exists in a different file will send a non catchable fatal error.
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

		    					// We discard modules according to property ->hidden
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
		    			            $filename[$i]= $modName;
		    					    $modules[$modName] = $objMod;

		    			            // Gives the possibility to the module, to provide his own family info and position of this family
		    			            if (is_array($objMod->familyinfo) && !empty($objMod->familyinfo)) {
		    			            	$familyinfo = array_merge($familyinfo, $objMod->familyinfo);
		    			            	$familykey = key($objMod->familyinfo);
		    			            } else {
		    			            	$familykey = $objMod->family;
		    			            }

		    			            $moduleposition = ($objMod->module_position?$objMod->module_position:'50');
		    			            if ($moduleposition == '50' && ($objMod->isCoreOrExternalModule() == 'external'))
		    			            {
		    			            	$moduleposition = '80';		// External modules at end by default
		    			            }

		    			            // Add list of warnings to show into arrayofwarnings and arrayofwarningsext
		    			            if (! empty($objMod->warnings_activation))
		    			            {
		    			                $arrayofwarnings[$modName]=$objMod->warnings_activation;
		    			            }
		    			            if (! empty($objMod->warnings_activation_ext))
		    			            {
		    			                $arrayofwarningsext[$modName]=$objMod->warnings_activation_ext;
		    			            }

		    			            $orders[$i]  = $familyinfo[$familykey]['position']."_".$familykey."_".$moduleposition."_".$j;   // Sort by family, then by module position then number
		    						$dirmod[$i]  = $dir;
		    						//print $i.'-'.$dirmod[$i].'<br>';
		    			            // Set categ[$i]
		    						$specialstring = 'unknown';
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

if ($action == 'reset_confirm' && $user->admin)
{
	if(!empty($modules[$value])) {
		$objMod  = $modules[$value];

		if(!empty($objMod->langfiles)) $langs->loadLangs($objMod->langfiles);

		$form = new Form($db);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?value='.$value.'&mode='.$mode.$param, $langs->trans('ConfirmUnactivation'), $langs->trans(GETPOST('confirm_message_code')), 'reset', '', 'no', 1);
	}
}

print $formconfirm;

asort($orders);
//var_dump($orders);
//var_dump($categ);
//var_dump($modules);

$nbofactivatedmodules=count($conf->modules);
$moreinfo=$langs->trans("TotalNumberOfActivatedModules",($nbofactivatedmodules-1), count($modules));
if ($nbofactivatedmodules <= 1) $moreinfo .= ' '.img_warning($langs->trans("YouMustEnableOneModule"));
print load_fiche_titre($langs->trans("ModulesSetup"),$moreinfo,'title_setup');

// Start to show page
if ($mode=='common')      print '<span class="opacitymedium">'.$langs->trans("ModulesDesc")."</span><br>\n";
if ($mode=='marketplace') print '<span class="opacitymedium">'.$langs->trans("ModulesMarketPlaceDesc")."</span><br>\n";
if ($mode=='deploy')      print '<span class="opacitymedium">'.$langs->trans("ModulesDeployDesc", $langs->transnoentitiesnoconv("AvailableModules"))."</span><br>\n";
if ($mode=='develop')     print '<span class="opacitymedium">'.$langs->trans("ModulesDevelopDesc")."</span><br>\n";

$head = modules_prepare_head();


print "<br>\n";


if ($mode == 'common')
{
    dol_set_focus('#search_keyword');

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';

    dol_fiche_head($head, $mode, '', -1);

    $moreforfilter = '';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('Keyword') . ': <input type="text" id="search_keyword" name="search_keyword" value="'.dol_escape_htmltag($search_keyword).'">';
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
        print $moreforfilter;
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
    }

    $moreforfilter='';

    print '<div class="clearboth"></div><br>';

    $parameters=array();
    $reshook=$hookmanager->executeHooks('insertExtraHeader',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    // Show list of modules

    $oldfamily='';

    foreach ($orders as $key => $value)
    {
        $tab=explode('_',$value);
        $familyposition=$tab[0]; $familykey=$tab[1]; $module_position=$tab[2]; $numero=$tab[3];

        $modName = $filename[$key];
    	$objMod  = $modules[$modName];
    	$dirofmodule = $dirmod[$key];

    	//print $objMod->name." - ".$key." - ".$objMod->version."<br>";
    	//if (($mode != (isset($specialtostring[$special])?$specialtostring[$special]:'unknown') && $mode != 'expdev')
    	if ($mode == 'expdev' && $objMod->version != 'development' && $objMod->version != 'experimental') continue;    // Discard if not for current tab

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
        if ($familykey != $oldfamily) {
            if ($oldfamily) {
                print '</table></div><br>';
            }

            $familytext = empty($familyinfo[$familykey]['label'])?$familykey:$familyinfo[$familykey]['label'];
            print load_fiche_titre($familytext, '', '');

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste" summary="list_of_modules">'."\n";

            $atleastoneforfamily=0;
        }

        $atleastoneforfamily++;

        if ($familykey!=$oldfamily)
        {
        	$familytext=empty($familyinfo[$familykey]['label'])?$familykey:$familyinfo[$familykey]['label'];
        	$oldfamily=$familykey;
        }




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

        print '<tr class="oddeven">'."\n";
        if (!empty($conf->global->MAIN_MODULES_SHOW_LINENUMBERS)) print '<td width="20px">'.++$linenum.'</td>';

        // Picto + Name of module
        print '  <td width="200px">';
        $alttext='';
        //if (is_array($objMod->need_dolibarr_version)) $alttext.=($alttext?' - ':'').'Dolibarr >= '.join('.',$objMod->need_dolibarr_version);
        //if (is_array($objMod->phpmin)) $alttext.=($alttext?' - ':'').'PHP >= '.join('.',$objMod->phpmin);
        if (! empty($objMod->picto))
        {
        	if (preg_match('/^\//i',$objMod->picto)) print img_picto($alttext,$objMod->picto,' width="14px"',1);
        	else print img_object($alttext, $objMod->picto, 'class="valignmiddle" width="14px"');
        }
        else
        {
        	print img_object($alttext, 'generic', 'class="valignmiddle"');
        }
        print ' <span class="valignmiddle">'.$objMod->getName().'</span>';
        print "</td>\n";

        // Desc
        print '<td class="valignmiddle tdoverflowmax300">';
        print nl2br($objMod->getDesc());
        print "</td>\n";

        // Help
        print '<td class="center nowrap" style="width: 82px;">';
        //print $form->textwithpicto('', $text, 1, $imginfo, 'minheight20', 0, 2, 1);
        print '<a href="javascript:document_preview(\''.DOL_URL_ROOT.'/admin/modulehelp.php?id='.$objMod->numero.'\',\'text/html\',\''.dol_escape_js($langs->trans("Module")).'\')">'.img_picto($langs->trans("ClickToShowDescription"), $imginfo).'</a>';
        print '</td>';

        // Version
        print '<td class="center nowrap" width="120px">';
        print $versiontrans;
        if(!empty($conf->global->CHECKLASTVERSION_EXTERNALMODULE)){
            require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
            if (!empty($objMod->url_last_version)) {
                $newversion = getURLContent($objMod->url_last_version);
                if(isset($newversion['content'])){
                    if (version_compare($newversion['content'], $versiontrans) > 0) {
                        print "&nbsp;<span class='butAction' title='" . $langs->trans('LastStableVersion') . "'>".$newversion['content']."</span>";
                    }
                }
            }
        }
        print "</td>\n";

        // Activate/Disable and Setup (2 columns)
        if (! empty($conf->global->$const_name))	// If module is already activated
        {
        	$disableSetup = 0;

        	// Link enable/disabme
        	print '<td class="center valignmiddle" width="60px">';
        	if (! empty($arrayofwarnings[$modName]))
	        {
                print '<!-- This module has a warning to show when we activate it (note: your country is '.$mysoc->country_code.') -->'."\n";
	        }
	        if (! empty($objMod->disabled))
        	{
        		print $langs->trans("Disabled");
        	}
        	else if (! empty($objMod->always_enabled) || ((! empty($conf->multicompany->enabled) && $objMod->core_enabled) && ($user->entity || $conf->entity!=1)))
        	{
        		if (method_exists($objMod, 'alreadyUsed') && $objMod->alreadyUsed()) print $langs->trans("Used");
        		else {
        			print img_picto($langs->trans("Required"),'switch_on');
        			print $langs->trans("Required");
        		}
        		if (! empty($conf->multicompany->enabled) && $user->entity) $disableSetup++;
        	}
        	else
        	{
        		if(!empty($objMod->warnings_unactivation[$mysoc->country_code]) && method_exists($objMod, 'alreadyUsed') && $objMod->alreadyUsed()) {
        			print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&amp;module_position='.$module_position.'&amp;action=reset_confirm&amp;confirm_message_code='.$objMod->warnings_unactivation[$mysoc->country_code].'&amp;value=' . $modName . '&amp;mode=' . $mode . $param . '">';
        			print img_picto($langs->trans("Activated"),'switch_on');
        			print '</a>';
        		}
        		else {

        			print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&amp;module_position='.$module_position.'&amp;action=reset&amp;value=' . $modName . '&amp;mode=' . $mode .'&amp;confirm=yes' . $param . '">';
        			print img_picto($langs->trans("Activated"),'switch_on');
        			print '</a>';
        		}
        	}
        	print '</td>'."\n";

        	// Link config
        	if (! empty($objMod->config_page_url) && !$disableSetup)
        	{
        		$backtourlparam='';
        		if ($search_keyword != '') $backtourlparam.=($backtourlparam?'&':'?').'search_keyword='.$search_keyword;	// No urlencode here, done later
        		if ($search_nature > -1)   $backtourlparam.=($backtourlparam?'&':'?').'search_nature='.$search_nature;
        		if ($search_version > -1)  $backtourlparam.=($backtourlparam?'&':'?').'search_version='.$search_version;
        		if ($search_status > -1)   $backtourlparam.=($backtourlparam?'&':'?').'search_status='.$search_status;
        		$backtourl=$_SERVER["PHP_SELF"].$backtourlparam;

        		if (is_array($objMod->config_page_url))
        		{
        			print '<td class="tdsetuppicto right" width="60px">';
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
        						$urltouse=dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1);
        						print '<a href="'.$urltouse.(preg_match('/\?/',$urltouse)?'&':'?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a>';
        					}
        					else
        					{
        						$urltouse=$urlpage;
        						print '<a href="'.$urltouse.(preg_match('/\?/',$urltouse)?'&':'?').'save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a>';
        					}
        				}
        			}
        			print "</td>\n";
        		}
        		else if (preg_match('/^([^@]+)@([^@]+)$/i',$objMod->config_page_url,$regs))
        		{
        			print '<td class="tdsetuppicto right valignmiddle" width="60px"><a href="'.dol_buildpath('/'.$regs[2].'/admin/'.$regs[1],1).'?save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a></td>';
        		}
        		else
        		{
        			print '<td class="tdsetuppicto right valignmiddle" width="60px"><a href="'.$objMod->config_page_url.'?save_lastsearch_values=1&backtopage='.urlencode($backtourl).'" title="'.$langs->trans("Setup").'">'.img_picto($langs->trans("Setup"),"setup",'style="padding-right: 6px"').'</a></td>';
        		}
        	}
        	else
        	{
        		print '<td class="tdsetuppicto right valignmiddle" width="60px">'.img_picto($langs->trans("NothingToSetup"),"setup",'class="opacitytransp" style="padding-right: 6px"').'</td>';
        	}
        }
        else	// Module not yet activated
		{
		    // Link enable/disable
        	print '<td class="center valignmiddle" width="60px">';
		    if (! empty($objMod->always_enabled))
        	{
        		// Should never happened
        	}
        	else if (! empty($objMod->disabled))
        	{
        		print $langs->trans("Disabled");
        	}
        	else
        	{
	        	// Module qualified for activation
        	    $warningmessage='';
	        	if (! empty($arrayofwarnings[$modName]))
	        	{
                    print '<!-- This module has a warning to show when we activate it (note: your country is '.$mysoc->country_code.') -->'."\n";
	        	    foreach ($arrayofwarnings[$modName] as $keycountry => $cursorwarningmessage)
   	        	    {
	        	        $warningmessage .= ($warningmessage?"\n":"").$langs->trans($cursorwarningmessage, $objMod->getName(), $mysoc->country_code);
   	        	    }
	        	}
        		if ($objMod->isCoreOrExternalModule() == 'external' && ! empty($arrayofwarningsext))
	        	{
	        	    print '<!-- This module is an external module and it may have a warning to show (note: your country is '.$mysoc->country_code.') -->'."\n";
	        	    foreach ($arrayofwarningsext as $keymodule => $arrayofwarningsextbycountry)
	        	    {
                        $keymodulelowercase=strtolower(preg_replace('/^mod/','',$keymodule));
                        if (in_array($keymodulelowercase, $conf->modules))    // If module that request warning is on
	        	        {
        	        	    foreach ($arrayofwarningsextbycountry as $keycountry => $cursorwarningmessage)
        	        	    {
        	        	    	if (preg_match('/^always/', $keycountry) || ($mysoc->country_code && preg_match('/^'.$mysoc->country_code.'/', $keycountry)))
        	        	        {
        	        	            $warningmessage .= ($warningmessage?"\n":"").$langs->trans($cursorwarningmessage, $objMod->getName(), $mysoc->country_code, $modules[$keymodule]->getName());
        	        	            $warningmessage .= ($warningmessage?"\n":"").($warningmessage?"\n":"").$langs->trans("Module").' : '.$objMod->getName();
        	        	            if (! empty($objMod->editor_name)) $warningmessage .= ($warningmessage?"\n":"").$langs->trans("Publisher").' : '.$objMod->editor_name;
        	        	            if (! empty($objMod->editor_name)) $warningmessage .= ($warningmessage?"\n":"").$langs->trans("ModuleTriggeringThisWarning").' : '.$modules[$keymodule]->getName();
        	        	        }
        	        	    }
	        	        }
	        	    }
	        	}
        	    print '<!-- Message to show: '.$warningmessage.' -->'."\n";
	        	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$objMod->numero.'&amp;module_position='.$module_position.'&amp;action=set&amp;value=' . $modName . '&amp;mode=' . $mode . $param . '"';
	        	if ($warningmessage) print ' onclick="return confirm(\''.dol_escape_js($warningmessage).'\');"';
	        	print '>';
	        	print img_picto($langs->trans("Disabled"),'switch_off');
	        	print "</a>\n";
        	}
        	print "</td>\n";

        	// Link config
        	print '<td class="tdsetuppicto right valignmiddle" width="60px">'.img_picto($langs->trans("NothingToSetup"),"setup",'class="opacitytransp" style="padding-right: 6px"').'</td>';
        }

        print "</tr>\n";
    }

    if ($oldfamily)
    {
        print "</table>\n";
        print '</div>';
    }

    dol_fiche_end();

    print '<br>';

    // Show warning about external users
    print info_admin(showModulesExludedForExternal($modules))."\n";

    print '</form>';
}

if ($mode == 'marketplace')
{
    dol_fiche_head($head, $mode, '', -1);

    // Marketplace
    print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";
    print "<tr class=\"liste_titre\">\n";
    //print '<td>'.$langs->trans("Logo").'</td>';
    print '<td colspan="2">'.$langs->trans("WebSiteDesc").'</td>';
    print '<td>'.$langs->trans("URL").'</td>';
    print '</tr>';

    print "<tr class=\"oddeven\">\n";
    $url='https://www.dolistore.com';
    print '<td align="left"><a href="'.$url.'" target="_blank" rel="external"><img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a></td>';
    print '<td>'.$langs->trans("DoliStoreDesc").'</td>';
    print '<td><a href="'.$url.'" target="_blank" rel="external">'.$url.'</a></td>';
    print '</tr>';

    print "</table>\n";

    dol_fiche_end();

    print '<br>';

    if (empty($conf->global->MAIN_DISABLE_DOLISTORE_SEARCH) && $conf->global->MAIN_FEATURES_LEVEL >= 1)
    {
    	// $options is array with filter criterias
    	//var_dump($options);
    	$dolistore->getRemoteData($options);

	    print '<span class="opacitymedium">'.$langs->trans('DOLISTOREdescriptionLong').'</span><br><br>';

	    $previouslink = $dolistore->get_previous_link();
	    $nextlink = $dolistore->get_next_link();

	    print '<div class="liste_titre liste_titre_bydiv centpercent"><div class="divsearchfield">'

	    ?>
	            <form method="POST" class="centpercent" id="searchFormList" action="<?php echo $dolistore->url ?>">
	            	<input type="hidden" name="mode" value="marketplace" />
	                <div class="divsearchfield"><?php echo $langs->trans('Keyword') ?>:
	                    <input name="search_keyword" placeholder="<?php echo $langs->trans('Chercher un module') ?>" id="search_keyword" type="text" size="50" value="<?php echo $options['search'] ?>"><br>
	                </div>
	                <div class="divsearchfield">
	                    <input class="button" value="<?php echo $langs->trans('Rechercher') ?>" type="submit">
	                    <a class="button" href="<?php echo $dolistore->url ?>"><?php echo $langs->trans('Reset') ?></a>

	                    &nbsp;
					</div>
	                <div class="divsearchfield right">
	                <?php
	                print $previouslink;
	                print $nextlink;
	                ?>
	                </div>
	            </form>

	   <?php

	   print '</div></div>';
	   print '<div class="clearboth"></div>';

	   ?>

	        <div id="category-tree-left">
	            <ul class="tree">
	                <?php echo $dolistore->get_categories(); ?>
	            </ul>
	        </div>
	        <div id="listing-content">
	            <table summary="list_of_modules" id="list_of_modules" class="productlist centpercent">
	                <tbody id="listOfModules">
	                    <?php echo $dolistore->get_products($categorie); ?>
	                </tbody>
	            </table>
	        </div>

	    <?php
    }
}


// Install external module

if ($mode == 'deploy')
{
	dol_fiche_head($head, $mode, '', -1);

	$dolibarrdataroot=preg_replace('/([\\/]+)$/i','',DOL_DATA_ROOT);
	$allowonlineinstall=true;
	$allowfromweb=1;
	if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) $allowonlineinstall=false;

	$fullurl='<a href="'.$urldolibarrmodules.'" target="_blank">'.$urldolibarrmodules.'</a>';
	$message='';
	if (! empty($allowonlineinstall))
	{
		if (! in_array('/custom',explode(',',$dolibarr_main_url_root_alt)))
		{
			$message=info_admin($langs->trans("ConfFileMustContainCustom", DOL_DOCUMENT_ROOT.'/custom', DOL_DOCUMENT_ROOT));
			$allowfromweb=-1;
		}
		else
		{
			if ($dirins_ok)
			{
				if (! is_writable(dol_osencode($dirins)))
				{
					$langs->load("errors");
					$message=info_admin($langs->trans("ErrorFailedToWriteInDir",$dirins));
					$allowfromweb=0;
				}
			}
			else
			{
				$message=info_admin($langs->trans("NotExistsDirect",$dirins).$langs->trans("InfDirAlt").$langs->trans("InfDirExample"));
				$allowfromweb=0;
			}
		}
	}
	else
	{
		$message=info_admin($langs->trans("InstallModuleFromWebHasBeenDisabledByFile",$dolibarrdataroot.'/installmodules.lock'));
		$allowfromweb=0;
	}

	if ($allowfromweb < 1)
	{
		print $langs->trans("SomethingMakeInstallFromWebNotPossible");
		print $message;
		//print $langs->trans("SomethingMakeInstallFromWebNotPossible2");
		print '<br>';
	}

	print '<br>';

	if ($allowfromweb >= 0)
	{
		if ($allowfromweb == 1)
		{
			//print $langs->trans("ThisIsProcessToFollow").'<br>';
		}
		else
		{
			print $langs->trans("ThisIsAlternativeProcessToFollow").'<br>';
			print '<b>'.$langs->trans("StepNb",1).'</b>: ';
			print $langs->trans("FindPackageFromWebSite",$fullurl).'<br>';
			print '<b>'.$langs->trans("StepNb",2).'</b>: ';
			print $langs->trans("DownloadPackageFromWebSite",$fullurl).'<br>';
			print '<b>'.$langs->trans("StepNb",3).'</b>: ';
		}

		if ($allowfromweb == 1)
		{
			print $langs->trans("UnpackPackageInModulesRoot",$dirins).'<br>';

			print '<br>';

			print '<form enctype="multipart/form-data" method="POST" class="noborder" action="'.$_SERVER["PHP_SELF"].'" name="forminstall">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="install">';
			print '<input type="hidden" name="mode" value="deploy">';

			print $langs->trans("YouCanSubmitFile");

			$max=$conf->global->MAIN_UPLOAD_DOC;		// En Kb
			$maxphp=@ini_get('upload_max_filesize');	// En inconnu
			if (preg_match('/k$/i',$maxphp)) $maxphp=$maxphp*1;
			if (preg_match('/m$/i',$maxphp)) $maxphp=$maxphp*1024;
			if (preg_match('/g$/i',$maxphp)) $maxphp=$maxphp*1024*1024;
			if (preg_match('/t$/i',$maxphp)) $maxphp=$maxphp*1024*1024*1024;
			// Now $max and $maxphp are in Kb
			$maxmin = $max;
			if ($maxphp > 0) $maxmin=min($max,$maxphp);

			if ($maxmin > 0)
			{
				print '<script type="text/javascript">
				$(document).ready(function() {
					jQuery("#fileinstall").on("change", function() {
						if(this.files[0].size > '.($maxmin*1024).'){
							alert("'.dol_escape_js($langs->trans("ErrorFileSizeTooLarge")).'");
							this.value = "";
						};
					});
				});
				</script>'."\n";
				// MAX_FILE_SIZE doit précéder le champ input de type file
				print '<input type="hidden" name="max_file_size" value="'.($maxmin*1024).'">';
			}

			print '<input class="flat minwidth400" type="file" name="fileinstall" id="fileinstall"> ';

			print '<input type="submit" name="send" value="'.dol_escape_htmltag($langs->trans("Send")).'" class="button">';

			if (! empty($conf->global->MAIN_UPLOAD_DOC))
			{
			    if ($user->admin)
			    {
			        $langs->load('other');
			        print ' ';
			        print info_admin($langs->trans("ThisLimitIsDefinedInSetup",$max,$maxphp),1);
			    }
			}
			else
			{
			    print ' ('.$langs->trans("UploadDisabled").')';
			}

			print '</form>';

			print '<br>';
			print '<br>';

			print '<div class="center"><div class="logo_setup"></div></div>';
		}
		else
		{
			print $langs->trans("UnpackPackageInModulesRoot",$dirins).'<br>';
			print '<b>'.$langs->trans("StepNb",4).'</b>: ';
			print $langs->trans("SetupIsReadyForUse").'<br>';
		}
	}

	if (! empty($result['return']))
	{
		print '<br>';

		foreach($result['return'] as $value)
		{
			echo $value.'<br>';
		}
	}

	dol_fiche_end();
}

if ($mode == 'develop')
{
	dol_fiche_head($head, $mode, '', -1);

	// Marketplace
	print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";
	print "<tr class=\"liste_titre\">\n";
	//print '<td>'.$langs->trans("Logo").'</td>';
	print '<td colspan="2">'.$langs->trans("DevelopYourModuleDesc").'</td>';
	print '<td>'.$langs->trans("URL").'</td>';
	print '</tr>';

	print '<tr class="oddeven" height="80">'."\n";
	print '<td align="left">';
	//span class="fa fa-bug"></span>
	//print '<img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolibarr_preferred_partner_int.png">';
	print '<div class="imgmaxheight50 logo_setup"></div>';
	print '</td>';
	print '<td>'.$langs->trans("TryToUseTheModuleBuilder", $langs->transnoentitiesnoconv("ModuleBuilder")).'</td>';
	print '<td>'.$langs->trans("SeeTopRightMenu").'</td>';
	print '</tr>';

	print '<tr class="oddeven" height="80">'."\n";
	$url='https://partners.dolibarr.org';
	print '<td align="left">';
	print'<a href="'.$url.'" target="_blank" rel="external"><img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolibarr_preferred_partner_int.png"></a>';
	print '</td>';
	print '<td>'.$langs->trans("DoliPartnersDesc").'</td>';
	print '<td><a href="'.$url.'" target="_blank" rel="external">'.$url.'</a></td>';
	print '</tr>';

	print "</table>\n";

	dol_fiche_end();
}

// End of page
llxFooter();
$db->close();
