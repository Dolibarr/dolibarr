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

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$module=GETPOST('module');
$tab=GETPOST('tab');
if (empty($module)) $module='initmodule';
if (empty($tab)) $tab='description';


// Security check
if (! $user->admin && empty($conf->global->MODULEBUILDER_FOREVERYONE)) accessforbidden('ModuleBuilderNotAllowed');

$modulename=dol_sanitizeFileName(GETPOST('modulename','alpha'));

// Dir for custom dirs
$tmp=explode(',', $dolibarr_main_document_root_alt);
$dircustom = $tmp[0];

$FILEFLAG='modulebuilder.txt';


/*
 * Actions
 */

if ($dircustom && $action == 'initmodule' && $modulename)
{
    $srcfile = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
    $destfile = $dircustom.'/'.$modulename;
    $result = dolCopyDir($srcfile, $destfile, 0, 0);
    //dol_mkdir($destfile);
    if ($result <= 0)
    {
        $error++;
        setEventMessages($langs->trans("ErrorFailedToCopyDir"), null, 'errors');
    }

    // Edit PHP files
    $listofphpfilestoedit = dol_dir_list($destfile, 'files', 1, '\.php$', 'fullname', SORT_ASC, 0, true);
    foreach($listofphpfilestoedit as $phpfileval)
    {
        $arrayreplacement=array(
            'mymodule'=>$modulename
        );
        $result=dolReplaceInFile($phpfileval['fullname'], $arrayreplacement);
        var_dump($phpfileval);
        var_dump($result);
    }    
    
    if (! $error)
    {
        setEventMessages('ModuleInitialized', null);
    }
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
                        $listofmodules[$dircustomcursor['name']]=$fullname;
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
print '<br>';
            
            
print '<br>';            

$head = array();
$h=0;

$head[$h][0] = $_SERVER["PHP_SELF"].'?module=initmodule';
$head[$h][1] = $langs->trans("NewModule");
$head[$h][2] = 'initmodule';
$h++;
    
foreach($listofmodules as $tmpmodule => $fullname)
{
    $head[$h][0] = $_SERVER["PHP_SELF"].'?module='.$tmpmodule;
    $head[$h][1] = $tmpmodule;
    $head[$h][2] = $tmpmodule;
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
    print '<input type="text" name="modulename" value="" placeholder="'.dol_escape_htmltag($langs->trans("ModuleKey")).'">';
    print '<input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
    print '</form>';
}
elseif (! empty($module))
{
    $error=0;
    
    // Load module
    dol_include_once($module.'/core/modules/mod'.ucfirst($module).'.class.php');
    $class='mod'.ucfirst($module);
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
    
    // Button to delete module
    
    
    // Tabs for module
    if (! $error)
    {
        $head2 = array();
        $h=0;
        
        foreach($listofmodules as $tmpmodule => $fullname)
        {
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=description';
            $head2[$h][1] = $langs->trans("Description");
            $head2[$h][2] = 'description';
            $h++;
            
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects';
            $head2[$h][1] = $langs->trans("Objects");
            $head2[$h][2] = 'objects';
            $h++;        
        
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab='.$tmpmodule;
            $head2[$h][1] = $langs->trans("Menus");
            $head2[$h][2] = 'menus';
            $h++;
            
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab='.$tmpmodule;
            $head2[$h][1] = $langs->trans("Permissions");
            $head2[$h][2] = 'permissions';
            $h++;        
    
            $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab='.$tmpmodule;
            $head2[$h][1] = $langs->trans("Triggers");
            $head2[$h][2] = 'triggers';
            $h++;        
        }
        
        dol_fiche_head($head2, $tab, '', -1, '');
        
        print $moduleobj->getDescLong();
    
        
        
        
        
        dol_fiche_end();
    }
}

dol_fiche_end();



llxFooter();

$db->close();
