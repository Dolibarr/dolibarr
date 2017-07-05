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
$tabobj=GETPOST('tabobj','alpha');
if (empty($module)) $module='initmodule';
if (empty($tab)) $tab='description';
if (empty($tabobj)) $tabobj='newobject';

$modulename=dol_sanitizeFileName(GETPOST('modulename','alpha'));
$objectname=dol_sanitizeFileName(GETPOST('objectname','alpha'));

// Security check
if (! $user->admin && empty($conf->global->MODULEBUILDER_FOREVERYONE)) accessforbidden('ModuleBuilderNotAllowed');


// Dir for custom dirs
$tmp=explode(',', $dolibarr_main_document_root_alt);
$dirins = $tmp[0];

$FILEFLAG='modulebuilder.txt';

$now=dol_now();

/*
 * Actions
 */

if ($dirins && $action == 'initmodule' && $modulename)
{
    if (preg_match('/\s/', $modulename))
    {
        $error++;
        setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
    }

    if (! $error)
    {
        $srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
        $destdir = $dirins.'/'.strtolower($modulename);

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

        // Delete some files
        dol_delete_file($destdir.'/myobject_card.php');
        dol_delete_file($destdir.'/myobject_list.php');
        dol_delete_file($destdir.'/test/phpunit/MyObjectTest.php');
        dol_delete_file($destdir.'/sql/llx_myobject.key.sql');
        dol_delete_file($destdir.'/sql/llx_myobject.sql');
        dol_delete_file($destdir.'/scripts/myobject.php');
        dol_delete_file($destdir.'/img/object_myobject.png');
        dol_delete_file($destdir.'/class/myobject.class.php');
        dol_delete_file($destdir.'/class/api_myobject.class.php');
        dol_delete_file($destdir.'/class/MyObject.txt');
    }

    // Edit PHP files
    if (! $error)
    {
	    $listofphpfilestoedit = dol_dir_list($destdir, 'files', 1, '\.(php|MD|js|sql|txt|xml|lang)$', '', 'fullname', SORT_ASC, 0, 1);
	    foreach($listofphpfilestoedit as $phpfileval)
	    {
	        //var_dump($phpfileval['fullname']);
	    	$arrayreplacement=array(
	            'mymodule'=>strtolower($modulename),
	        	'MyModule'=>$modulename,
	        	'MYMODULE'=>strtoupper($modulename),
	        	'My module'=>$modulename,
	        	'htdocs/modulebuilder/template/'=>'',
                '---Put here your own copyright and developer email---'=>dol_print_date($now,'%Y').' '.$user->getFullName($langs).($user->email?' <'.$user->email.'>':'')
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

if ($dirins && $action == 'initobject' && $module && $objectname)
{
    if (preg_match('/\s/', $objectname))
    {
        $error++;
        setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
    }

    if (! $error)
    {
        $srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
        $destdir = $dirins.'/'.strtolower($module);

        $arrayreplacement=array(
            'mymodule'=>strtolower($module),
            'MyModule'=>$module,
            'myobject'=>strtolower($objectname),
            'MyObject'=>$objectname
        );


        // Delete some files
        $filetogenerate = array(
            'myobject_card.php'=>strtolower($objectname).'_card.php',
            'myobject_list.php'=>strtolower($objectname).'_list.php',
            'test/phpunit/MyObjectTest.php'=>'test/phpunit/'.$objectname.'Test.php',
            'sql/llx_myobject.key.sql'=>'sql/llx_'.strtolower($objectname).'.key.sql',
            'sql/llx_myobject.sql'=>'sql/llx_'.strtolower($objectname).'.sql',
            'scripts/myobject.php'=>'scripts/'.strtolower($objectname).'.php',
            'img/object_myobject.png'=>'img/object_'.strtolower($objectname).'.png',
            'class/myobject.class.php'=>'class/'.strtolower($objectname).'.class.php',
            'class/api_myobject.class.php'=>'class/api_'.strtolower($objectname).'.class.php',
            'class/MyObject.txt'=>'class/'.$objectname.'.txt'
        );

        foreach($filetogenerate as $srcfile => $destfile)
        {
            $result = dol_copy($srcdir.'/'.$srcfile, $destdir.'/'.$destfile);
            if ($result <= 0)
            {
                if ($result < 0)
                {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorFailToCopyFile", $srcdir.'/'.$srcfile, $destdir.'/'.$destfile), null, 'errors');
                }
                else	// $result == 0
                {
                    setEventMessages($langs->trans("FileAlreadyExists", $srcdir.'/'.$srcfile, $destdir.'/'.$destfile), null, 'warnings');
                }
            }
            else
            {
                // Copy is ok
            }
        }
    }

    if (! $error)
    {
        // Edit PHP files
        foreach($filetogenerate as $destfile)
        {
            $phpfileval['fullname'] = $destdir.'/'.$destfile;

            //var_dump($phpfileval['fullname']);
            $arrayreplacement=array(
                'mymodule'=>strtolower($modulename),
                'MyModule'=>$modulename,
                'MYMODULE'=>strtoupper($modulename),
                'My module'=>$modulename,
                'htdocs/modulebuilder/template/'=>'',
                'myobject'=>strtolower($objectname),
                'MyObject'=>$objectname
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
        setEventMessages('FilesForObjectInitialized', null);
    }
}

if ($dirins && $action == 'confirm_delete')
{
    if (preg_match('/\s/', $module))
    {
        $error++;
        setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
    }

    if (! $error)
    {
        $modulelowercase=strtolower($module);

        // Dir for module
        $dir = $dirins.'/'.$modulelowercase;

        $result = dol_delete_dir_recursive($dir);

        if ($result > 0)
        {
            setEventMessages($langs->trans("DirWasRemoved", $modulelowercase), null);
        }
        else
        {
            setEventMessages($langs->trans("PurgeNothingToDelete"), null, 'warnings');
        }
    }

    //header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?module=initmodule');
    //exit;
    $action = '';
    $module = 'deletemodule';
}

if ($dirins && $action == 'confirm_deleteobject' && $objectname)
{
    if (preg_match('/\s/', $objectname))
    {
        $error++;
        setEventMessages($langs->trans("SpaceOrSpecialCharAreNotAllowed"), null, 'errors');
    }

    if (! $error)
    {
        $modulelowercase=strtolower($module);
        $objectlowercase=strtolower($objectname);

        // Dir for module
        $dir = $dirins.'/'.$modulelowercase;

        // Delete some files
        $filetogenerate = array(
            'myobject_card.php'=>strtolower($objectname).'_card.php',
            'myobject_list.php'=>strtolower($objectname).'_list.php',
            'test/phpunit/MyObjectTest.php'=>'test/phpunit/'.$objectname.'Test.php',
            'sql/llx_myobject.key.sql'=>'sql/llx_'.strtolower($objectname).'.key.sql',
            'sql/llx_myobject.sql'=>'sql/llx_'.strtolower($objectname).'.sql',
            'scripts/myobject.php'=>'scripts/'.strtolower($objectname).'.php',
            'img/object_myobject.png'=>'img/object_'.strtolower($objectname).'.png',
            'class/myobject.class.php'=>'class/'.strtolower($objectname).'.class.php',
            'class/api_myobject.class.php'=>'class/api_'.strtolower($objectname).'.class.php',
            'class/MyObject.txt'=>'class/'.$objectname.'.txt'
        );

        $resultko = 0;
        foreach($filetogenerate as $filetodelete)
        {
            $resulttmp = dol_delete_file($dir.'/'.$filetodelete, 0, 0, 1);
            if (! $resulttmp) $resultko++;
        }

        if ($resultko == 0)
        {
            setEventMessages($langs->trans("FilesDeleted"), null);
        }
        else
        {
            setEventMessages($langs->trans("ErrorSomeFilesCouldNotBeDeleted"), null, 'warnings');
        }
    }

    //header("Location: ".DOL_URL_ROOT.'/modulebuilder/index.php?module=initmodule');
    //exit;
    $action = '';
    $tabobj = 'deleteobject';
}

if ($dirins && $action == 'generatepackage')
{
    $modulelowercase=strtolower($module);

    // Dir for module
    $dir = $dirins.'/'.$modulelowercase;
    // Zip file to build
    $FILENAMEZIP='';

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
            dol_print_error($e->getMessage());
        }
    }
    else
    {
        $error++;
        $langs->load("errors");
        dol_print_error($langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
        exit;
    }

    $arrayversion=explode('.',$moduleobj->version,3);
    if (count($arrayversion))
    {
        $FILENAMEZIP="module_".$modulelowercase.'-'.$arrayversion[0].'.'.$arrayversion[1].($arrayversion[2]?".".$arrayversion[2]:"").".zip";
        $outputfile = $conf->admin->dir_temp.'/'.$FILENAMEZIP;

        $result = dol_compress_dir($dir, $outputfile, 'zip');
        if ($result > 0)
        {
            setEventMessages($langs->trans("ZipFileGeneratedInto", $outputfile), null);
        }
        else
        {
            $error++;
            $langs->load("errors");
            setEventMessages($langs->trans("ErrorFailToGenerateFile", $outputfile), null, 'errors');
        }
    }
    else
    {
        $error++;
        $langs->load("errors");
        setEventMessages($langs->trans("ErrorCheckVersionIsDefined"), null, 'errors');
    }
}


/*
 * View
 */

// Set dir where external modules are installed
if (! dol_is_dir($dirins))
{
    dol_mkdir($dirins);
}
$dirins_ok=(dol_is_dir($dirins));


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
            $dirsincustom=dol_dir_list($dirins, 'directories');

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
$newdircustom=$dirins;
if (empty($newdircustom)) $newdircustom=img_warning();
print $langs->trans("ModuleBuilderDesc", 'https://wiki.dolibarr.org/index.php/Module_development#Create_your_module').'<br>';
print $langs->trans("ModuleBuilderDesc2", 'conf/conf.php', $newdircustom).'<br>';

$message='';
if (! $dirins)
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
if ($message)
{
    print $message;
}

print $langs->trans("ModuleBuilderDesc3", count($listofmodules), $FILEFLAG).'<br>';
//print '<br>';


// Load module descriptor
$error=0;
$moduleobj = null;

if (! empty($module) && $module != 'initmodule' && $module != 'deletemodule')
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

$head[$h][0] = $_SERVER["PHP_SELF"].'?module=deletemodule';
$head[$h][1] = $langs->trans("DangerZone");
$head[$h][2] = 'deletemodule';
$h++;


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
    print '<input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("Create")).'"'.($dirins?'':' disabled="disabled"').'>';
    print '</form>';
}
elseif ($module == 'deletemodule')
{
    print '<form name="delete">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="confirm_delete">';
    print '<input type="hidden" name="module" value="deletemodule">';

    print $langs->trans("EnterNameOfModuleToDeleteDesc").'<br><br>';

    print '<input type="text" name="module" placeholder="'.dol_escape_htmltag($langs->trans("ModuleKey")).'" value="">';
    print '<input type="submit" class="buttonDelete" value="'.$langs->trans("Delete").'"'.($dirins?'':' disabled="disabled"').'>';
    print '</form>';
}
elseif (! empty($module))
{
    // Tabs for module
    if (! $error)
    {
        $head2 = array();
        $h=0;

        $modulelowercase=strtolower($module);

        $modulestatusinfo=img_info('').' '.$langs->trans("ModuleIsNotActive");
        if (! empty($conf->$module->enabled))
        {
        	$modulestatusinfo=img_warning().' '.$langs->trans("ModuleIsLive");
        }

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=description&module='.$module;
        $head2[$h][1] = $langs->trans("Description");
        $head2[$h][2] = 'description';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=specifications&module='.$module;
        $head2[$h][1] = $langs->trans("Specifications");
        $head2[$h][2] = 'specifications';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module;
        $head2[$h][1] = $langs->trans("Objects");
        $head2[$h][2] = 'objects';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=menus&module='.$module;
        $head2[$h][1] = $langs->trans("Menus");
        $head2[$h][2] = 'menus';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=permissions&module='.$module;
        $head2[$h][1] = $langs->trans("Permissions");
        $head2[$h][2] = 'permissions';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=hooks&module='.$module;
        $head2[$h][1] = $langs->trans("Hooks");
        $head2[$h][2] = 'hooks';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=triggers&module='.$module;
        $head2[$h][1] = $langs->trans("Triggers");
        $head2[$h][2] = 'triggers';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=widgets&module='.$module;
        $head2[$h][1] = $langs->trans("Widgets");
        $head2[$h][2] = 'widgets';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=buildpackage&module='.$module;
        $head2[$h][1] = $langs->trans("BuildPackage");
        $head2[$h][2] = 'buildpackage';
        $h++;

        print $modulestatusinfo.'<br><br>';

        dol_fiche_head($head2, $tab, '', -1, '');

        print $langs->trans("ModuleBuilderDesc".$tab).'<br><br>';

        if ($tab == 'description')
        {
            $pathtofile = $modulelowercase.'/core/modules/mod'.$module.'.class.php';

            print '<span class="fa fa-file"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong><br>';
            print '<br>';

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
        	print ' (<a href="https://wiki.dolibarr.org/index.php/List_of_modules_id" target="_blank">'.$langs->trans("SeeHere").'</a>)';
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
        	//print "<br>'crm','financial','hr','projects','products','ecm','technic','interface','other'";
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
        	print $langs->trans("DescriptionLong");
        	print '</td><td>';
        	print $moduleobj->getDescLong();
        	print '</td></tr>';

        	print '</table>';

        	print '</div>';
        }


        if ($tab == 'specifications')
        {
            print $langs->trans("FeatureNotYetAvailable");

        }

        if ($tab == 'objects')
        {
            $head3 = array();
            $h=0;

            // Dir for module
            $dir = $dirins.'/'.$modulelowercase.'/class';

            $head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.'&tabobj=newobject';
            $head3[$h][1] = $langs->trans("NewObject");
            $head3[$h][2] = 'newobject';
            $h++;

            $listofobject = dol_dir_list($dir, 'files', 0, '\.txt$');
            foreach($listofobject as $fileobj)
            {
                $objectname = preg_replace('/\.txt$/', '', $fileobj['name']);

                $head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.'&tabobj='.$objectname;
                $head3[$h][1] = $objectname;
                $head3[$h][2] = $objectname;
                $h++;
            }

            $head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.'&tabobj=deleteobject';
            $head3[$h][1] = $langs->trans("DangerZone");
            $head3[$h][2] = 'deleteobject';
            $h++;


            dol_fiche_head($head3, $tabobj, '', -1, '');

            if ($tabobj == 'newobject')
            {
                // New module
                print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="initobject">';
                print '<input type="hidden" name="tab" value="objects">';
                print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

                print $langs->trans("EnterNameOfObjectDesc").'<br><br>';

                print '<input type="text" name="objectname" value="'.dol_escape_htmltag($modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("ObjectKey")).'">';
                print '<input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("Create")).'"'.($dirins?'':' disabled="disabled"').'>';
                print '</form>';
            }
            elseif ($tabobj == 'deleteobject')
            {
                // New module
                print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="confirm_deleteobject">';
                print '<input type="hidden" name="tab" value="objects">';
                print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';

                print $langs->trans("EnterNameOfObjectToDeleteDesc").'<br><br>';

                print '<input type="text" name="objectname" value="'.dol_escape_htmltag($modulename).'" placeholder="'.dol_escape_htmltag($langs->trans("ObjectKey")).'">';
                print '<input type="submit" class="buttonDelete" name="delete" value="'.dol_escape_htmltag($langs->trans("Delete")).'"'.($dirins?'':' disabled="disabled"').'>';
                print '</form>';
            }
            else
            {
                try {
                    $pathtoclass = strtolower($module).'/class/'.strtolower($tabobj).'.class.php';
                    $pathtoapi = strtolower($module).'/class/api_'.strtolower($tabobj).'.class.php';
                    $pathtolist = strtolower($module).'/'.strtolower($tabobj).'_list.class.php';
                    $pathtocard = strtolower($module).'/'.strtolower($tabobj).'_card.class.php';
                    print '<span class="fa fa-file"></span> '.$langs->trans("ClassFile").' : <strong>'.$pathtoclass.'</strong><br>';
                    print '<span class="fa fa-file"></span> '.$langs->trans("ApiClassFile").' : <strong>'.$pathtoapi.'</strong><br>';
                    print '<span class="fa fa-file"></span> '.$langs->trans("PageForList").' : <strong>'.$pathtolist.'</strong><br>';
                    print '<span class="fa fa-file"></span> '.$langs->trans("PageForCreateEditView").' : <strong>'.$pathtocard.'</strong><br>';

                    $result = dol_include_once($pathtoclass);
                    $tmpobjet = new $tabobj($db);

                    $reflector = new ReflectionClass($tabobj);
                    $properties = $reflector->getProperties();          // Can also use get_object_vars
                    $propdefault = $reflector->getDefaultProperties();  // Can also use get_object_vars
                    //$propstat = $reflector->getStaticProperties();

                    print load_fiche_titre($langs->trans("Properties"), '', '');

                    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="initobject">';
                    print '<input type="hidden" name="tab" value="objects">';
                    print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
                    print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

                    print '<table class="noborder">';
                    print '<tr class="liste_titre">';
                    print '<td>'.$langs->trans("Property");
                    print ' (<a href="https://wiki.dolibarr.org/index.php/Language_and_development_rules#Table_and_fields_structures" target="_blank">'.$langs->trans("Example").'</a>)';
                    print '</td>';
                    print '<td>'.$langs->trans("Comment").'</td>';
                    print '<td>'.$langs->trans("Type").'</td>';
                    print '<td>'.$langs->trans("DefaultValue").'</td>';
                    print '<td></td>';
                    print '</tr>';
                    print '<tr>';
                    print '<td><input class="text" name="propname" value=""></td>';
                    print '<td><input class="text" name="propname" value=""></td>';
                    print '<td><input class="text" name="propname" value=""></td>';
                    print '<td><input class="text" name="propname" value=""></td>';
                    print '<td align="center">';
                    print '<input class="button" type="submit" name="add" value="'.$langs->trans("Add").'">';
                    print '</td></tr>';
                    foreach($properties as $propkey => $propval)
                    {
                        if ($propval->class == $tabobj)
                        {
                            $propname=$propval->getName();

                            // Discard generic properties
                            if (in_array($propname, array('element', 'childtables', 'table_element', 'table_element_line', 'class_element_line', 'isnolinkedbythird', 'ismultientitymanaged'))) continue;

                            // Keep or not lines
                            if (in_array($propname, array('fk_element', 'lines'))) continue;


                            print '<tr class="oddeven"><td>';
                            print $propname;
                            print '</td>';
                            print '<td>';
                            print $propval->getDocComment();
                            print '</td>';
                            print '<td>';
                            print gettype($tmpobjet->$propname);
                            print '</td>';

                            print '<td>';
                            print $propdefault[$propname];
                            print '</td>';

                            print '<td>';

                            print '</td>';
                            print '</tr>';
                        }
                    }
                    print '</table>';

                    print '</form>';
                }
                catch(Exception $e)
                {
                    print $e->getMessage();
                }
            }
        }

        if ($tab == 'menus')
        {
    		print $langs->trans("FeatureNotYetAvailable");

        }

        if ($tab == 'permissions')
        {
    		print $langs->trans("FeatureNotYetAvailable");

        }

        if ($tab == 'hooks')
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
				print '<td class="tdtop">'.$trigger['relpath'].'</td>';
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

        if ($tab == 'widgets')
        {
    		print $langs->trans("FeatureNotYetAvailable");

        }

        if ($tab == 'buildpackage')
        {
            if (! class_exists('ZipArchive') && ! defined('ODTPHP_PATHTOPCLZIP'))
            {
                print img_warning().' '.$langs->trans("ErrNoZipEngine");
                print '<br>';
            }

            $modulelowercase=strtolower($module);

            // Zip file to build
            $FILENAMEZIP='';

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
                    dol_print_error($e->getMessage());
                }
            }
            else
            {
                $error++;
                $langs->load("errors");
                dol_print_error($langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
                exit;
            }

            $arrayversion=explode('.',$moduleobj->version,3);
            if (count($arrayversion))
            {
                $FILENAMEZIP="module_".$modulelowercase.'-'.$arrayversion[0].'.'.$arrayversion[1].($arrayversion[2]?".".$arrayversion[2]:"").".zip";
                $outputfile = $conf->admin->dir_temp.'/'.$FILENAMEZIP;

                $FILENAMEDOC="module_".$modulelowercase.'-'.$arrayversion[0].'.'.$arrayversion[1].($arrayversion[2]?".".$arrayversion[2]:"").".md";
                $outputfiledoc = $conf->admin->dir_temp.'/'.$FILENAMEDOC;
            }

            print '<br>';

            print '<span class="fa fa-file"></span> '. $langs->trans("PathToModulePackage") . ' : ';
            if (! dol_is_file($outputfile)) print '<strong>'.$langs->trans("FileNotYetGenerated").'</strong>';
            else {
                print '<strong>'.$outputfile.'</strong>';
                print ' ('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfile), 'dayhour').')';
            }
            print '</strong><br>';

            print '<br>';

        	print '<form name="generatepackage">';
        	print '<input type="hidden" name="action" value="generatepackage">';
        	print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
        	print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
        	print '<input type="submit" class="button" value="'.$langs->trans("BuildPackage").'">';
        	print '</form>';

        	print '<br><br><br>';

            print '<span class="fa fa-file"></span> '. $langs->trans("PathToModuleDocumentation") . ' : ';
            if (! dol_is_file($outputfiledoc)) print '<strong>'.$langs->trans("FileNotYetGenerated").'</strong>';
            else {
                print '<strong>'.$outputfiledoc.'</strong>';
                print ' ('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfiledoc), 'dayhour').')';
            }
            print '</strong><br>';

            print '<br>';

            print '<form name="generatepackage">';
        	print '<input type="hidden" name="action" value="generatedoc">';
        	print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
        	print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
        	print '<input type="submit" class="button" value="'.$langs->trans("BuildDocumentation").'">';
        	print '</form>';
        }

        dol_fiche_end();
    }
}

dol_fiche_end();



llxFooter();

$db->close();
