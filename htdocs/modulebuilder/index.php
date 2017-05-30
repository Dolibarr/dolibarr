<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/modulebuilder/index.php
 *       \brief      Home page for module builder module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("admin");
$langs->load("modulebuilder");
$langs->load("other");

$action=GETPOST('action','aZ09');
$confirm=GETPOST('confirm','alpha');
$module=GETPOST('module','alpha');
$tab=GETPOST('tab','aZ09');
if (empty($module)) $module='initmodule';
if (empty($tab)) $tab='description';

$modulename=dol_sanitizeFileName(GETPOST('modulename','alpha'));


// Security check
if (! $user->admin && empty($conf->global->MODULEBUILDER_FOREVERYONE)) accessforbidden('ModuleBuilderNotAllowed');


// Dir for custom dirs
$tmp=explode(',', $dolibarr_main_document_root_alt);
$dircustom = $tmp[0];

$FILEFLAG='modulebuilder.txt';


/*
 * Actions
 */

if ($dircustom && $action == 'initmodule' && $modulename)
{
    $srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
    $destdir = $dircustom.'/'.strtolower($modulename);

    $arrayreplacement=array(
        'mymodule'=>strtolower($modulename),
     	'MyModule'=>$modulename
    );
    
    $result = dolCopyDir($srcdir, $destdir, 0, 0, $arrayreplacement);
    //dol_mkdir($destfile);
    if ($result <= 0)
    {
    	if ($result < 0)
    	{
        	$error++;
        	$langs->load("errors");
        	setEventMessages($langs->trans("ErrorFailToCopyDir", $srcdir, $destdir), null, 'errors');
    	}
    	else	// $result == 0
    	{
        	setEventMessages($langs->trans("AllFilesDidAlreadyExist", $srcdir, $destdir), null, 'warnings');
    	}
    }

    // Edit PHP files
    if (! $error)
    {
	    $listofphpfilestoedit = dol_dir_list($destdir, 'files', 1, '\.(php|MD|js)$', '', 'fullname', SORT_ASC, 0, 1);
	    foreach($listofphpfilestoedit as $phpfileval)
	    {
	        //var_dump($phpfileval['fullname']);
	    	$arrayreplacement=array(
	            'mymodule'=>strtolower($modulename),
	        	'MyModule'=>$modulename,
	        	'MYMODULE'=>strtoupper($modulename),
	        	'My module'=>$modulename,
	        	'htdocs/modulebuilder/template/'=>'',
	        );
	        
	        
	        $result=dolReplaceInFile($phpfileval['fullname'], $arrayreplacement);
	        //var_dump($result);
	        if ($result < 0)
	        {
	        	setEventMessages($langs->trans("ErrorFailToMakeReplacementInto", $phpfileval['fullname']), null, 'errors');
	        }
	    }    
    }
        
    if (! $error)
    {
        setEventMessages('ModuleInitialized', null);
        $module=$modulename;
        $modulename = '';
    }
}

if ($dircustom && $action == 'generatepackage')
{
    $dir = $dircustom.'/'.$modulename;
	

}


/*
 * View
 */

llxHeader("",$langs->trans("ModuleBuilder"),"");


$text=$langs->trans("ModuleBuilder");

print load_fiche_titre($text, '', 'title_setup');

$listofmodules=array();

/*
if (!empty($conf->modulebuilder->enabled) && $mainmenu == 'modulebuilder')	// Entry for Module builder
{
    global $dolibarr_main_document_root_alt;
    if (! empty($dolibarr_main_document_root_alt) && is_array($dolibarr_main_document_root_alt))
    {
        foreach ($dolibarr_main_document_root_alt as $diralt)
        {*/
            $dirsincustom=dol_dir_list($dircustom, 'directories');
            
            if (is_array($dirsincustom) && count($dirsincustom) > 0)
            {
                foreach ($dirsincustom as $dircustomcursor)
                {
                    $fullname = $dircustomcursor['fullname'];
                    if (dol_is_file($fullname.'/'.$FILEFLAG))
                    {
                    	// Get real name of module (MyModule instead of mymodule)
                    	$descriptorfiles = dol_dir_list($fullname.'/core/modules/', 'files', 0, 'mod.*\.class\.php');
                    	$modulenamewithcase='';
                    	foreach($descriptorfiles as $descriptorcursor)
                    	{
                    		$modulenamewithcase=preg_replace('/^mod/', '', $descriptorcursor['name']);
                    		$modulenamewithcase=preg_replace('/\.class\.php$/', '', $modulenamewithcase);
                    	}
                    	if ($modulenamewithcase) $listofmodules[$dircustomcursor['name']]=$modulenamewithcase;
                    	//var_dump($listofmodules);
                    }
                }
            }
/*        }
    }
    else
    {
        $newmenu->add('', 'NoGeneratedModuleFound', 0, 0);
    }*/


// Show description of content
print $langs->trans("ModuleBuilderDesc").'<br>';
print $langs->trans("ModuleBuilderDesc2", 'conf/conf.php', $dircustom).'<br>';
print $langs->trans("ModuleBuilderDesc3", count($listofmodules), $FILEFLAG).'<br>';
//print '<br>';


// Load module descriptor
$error=0;
$moduleobj = null;

if (! empty($module) && $module != 'initmodule')
{
	$modulelowercase=strtolower($module);
	
    // Load module
    dol_include_once($modulelowercase.'/core/modules/mod'.$module.'.class.php');
    $class='mod'.$module;

    if (class_exists($class))
    {
        try {
            $moduleobj = new $class($db);
        }
        catch(Exception $e)
        {
            $error++;
            print $e->getMessage();
        }
    }
    else
    {
        $error++;
        $langs->load("errors");
        print img_warning('').' '.$langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module);
    }
}

print '<br>';            


// Tabs for all modules
$head = array();
$h=0;

$head[$h][0] = $_SERVER["PHP_SELF"].'?module=initmodule';
$head[$h][1] = $langs->trans("NewModule");
$head[$h][2] = 'initmodule';
$h++;
    
foreach($listofmodules as $tmpmodule => $tmpmodulewithcase)
{
    $head[$h][0] = $_SERVER["PHP_SELF"].'?module='.$tmpmodulewithcase;
    $head[$h][1] = $tmpmodulewithcase;
    $head[$h][2] = $tmpmodulewithcase;
    $h++;
}



dol_fiche_head($head, $module, $langs->trans("Modules"), -1, 'generic');

if ($module == 'initmodule')
{
    // New module
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="initmodule">';
    print '<input type="hidden" name="module" value="initmodule">';
    
    print $langs->trans("EnterNameOfModuleDesc").'<br><br>';
    
    print '<input type="text" name="modulename" value="'.dol_escape_htmltag($modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("ModuleKey")).'">';
    print '<input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
    print '</form>';
}
elseif (! empty($module))
{
    // Tabs for module
    if (! $error)
    {
        $head2 = array();
        $h=0;

       	$modulestatusinfo=img_info('').' '.$langs->trans("ModuleIsNotActive");
        if (! empty($conf->$module->enabled))
        {
        	$modulestatusinfo=img_warning().' '.$langs->trans("ModuleIsLive");	
        }
        
        foreach($listofmodules as $tmpmodule => $tmpmodulewithcase)
        {
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=description&module='.$tmpmodulewithcase;
            $head2[$h][1] = $langs->trans("Description");
            $head2[$h][2] = 'description';
            $h++;
            
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$tmpmodulewithcase;
            $head2[$h][1] = $langs->trans("Objects");
            $head2[$h][2] = 'objects';
            $h++;        
        
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=menus&module='.$tmpmodulewithcase;
            $head2[$h][1] = $langs->trans("Menus");
            $head2[$h][2] = 'menus';
            $h++;
            
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=permissions&module='.$tmpmodulewithcase;
            $head2[$h][1] = $langs->trans("Permissions");
            $head2[$h][2] = 'permissions';
            $h++;        
    
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=triggers&module='.$tmpmodulewithcase;
            $head2[$h][1] = $langs->trans("Triggers");
            $head2[$h][2] = 'triggers';
            $h++;        

            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=buildpackage&module='.$tmpmodulewithcase;
            $head2[$h][1] = $langs->trans("BuildPackage");
            $head2[$h][2] = 'buildpackage';
            $h++;        
        }

        print $modulestatusinfo.'<br><br>';
       
        dol_fiche_head($head2, $tab, '', -1, '');

        print $langs->trans("ModuleBuilderDesc".$tab).'<br><br>';
        
        if ($tab == 'description')
        {
        	print '<div class="underbanner clearboth"></div>';
        	print '<div class="fichecenter">';
        	
        	print '<table class="border centpercent">';
        	print '<tr class="liste_titre"><td class="titlefield">';
        	print $langs->trans("Parameter");
        	print '</td><td>';
        	print $langs->trans("Value");
        	print '</td></tr>';

        	print '<tr><td>';
        	print $langs->trans("Numero");
        	print '</td><td>';
        	print $moduleobj->numero;
        	print '</td></tr>';
        	
        	print '<tr><td>';
        	print $langs->trans("Name");
        	print '</td><td>';
        	print $moduleobj->getName();
        	print '</td></tr>';
        	
        	print '<tr><td>';
        	print $langs->trans("Version");
        	print '</td><td>';
        	print $moduleobj->getVersion();
        	print '</td></tr>';
        	
        	print '<tr><td>';
        	print $langs->trans("Family");
        	print "<br>'crm','financial','hr','projects','products','ecm','technic','interface','other'";
        	print '</td><td>';
        	print $moduleobj->family;
        	print '</td></tr>';
        	
        	print '<tr><td>';
        	print $langs->trans("EditorName");
        	print '</td><td>';
        	print $moduleobj->editor_name;
        	print '</td></tr>';
        	
        	print '<tr><td>';
        	print $langs->trans("EditorUrl");
        	print '</td><td>';
        	print $moduleobj->editor_url;
        	print '</td></tr>';
        	
        	print '<tr><td>';
        	print $langs->trans("Description");
        	print '</td><td>';
        	print $moduleobj->getDesc();
        	print '</td></tr>';
        	
        	print '<tr><td>';
        	print $langs->trans("LongDescription");
        	print '</td><td>';
        	print $moduleobj->getDescLong();
        	print '</td></tr>';
        	
        	print '</table>';
    		
        	print '</div>';
    		
        	print '<br><br>';
        	print '<form name="delete">';
        	print '<input type="hidden" name="action" value="confirm_delete">';
        	print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
        	print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
        	print '<input type="submit" class="buttonDelete" value="'.$langs->trans("Delete").'">';		
        	print '</form>';
        }
        
        if ($tab == 'objects')
        {
    		print $langs->trans("FeatureNotYetAvailable");
        	
        
        }
        
        if ($tab == 'menus')
        {
    		print $langs->trans("FeatureNotYetAvailable");
        	
        
        }
        
        if ($tab == 'permissions')
        {
    		print $langs->trans("FeatureNotYetAvailable");
        
        }
        
        if ($tab == 'triggers')
        {
        	require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
        	
			$interfaces = new Interfaces($db);
			$triggers = $interfaces->getTriggersList(array('/'.strtolower($module).'/core/triggers'));
			
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder">
			<tr class="liste_titre">
			<td colspan="2">'.$langs->trans("File").'</td>
			<td align="center">'.$langs->trans("Active").'</td>
			<td align="center">&nbsp;</td>
			</tr>
			';
			
			$var=True;
			foreach ($triggers as $trigger)
			{
				
				print '<tr class="oddeven">';
				print '<td valign="top" width="14" align="center">'.$trigger['picto'].'</td>';
				print '<td class="tdtop">'.$trigger['file'].'</td>';
				print '<td valign="top" align="center">'.$trigger['status'].'</td>';
				print '<td class="tdtop">';
				$text=$trigger['info'];
				$text.="<br>\n<strong>".$langs->trans("File")."</strong>:<br>\n".$trigger['relpath'];
				//$text.="\n".$langs->trans("ExternalModule",$trigger['isocreorexternal']);
				print $form->textwithpicto('', $text);
				print '</td>';
				print '</tr>';
			}
			
			print '</table>';
			print '</div>';
        }

        if ($tab == 'buildpackage')
        {
        	print '<form name="generatepackage">';
        	print '<input type="hidden" name="action" value="generatepackage">';
        	print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
        	print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
        	print '<input type="submit" class="button" value="'.$langs->trans("Generate").'">';		
        	print '</form>';
        }
        
        dol_fiche_end();
    }
}

dol_fiche_end();



llxFooter();

$db->close();
