<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * You can also make a direct call the page with parameter like this:
 * htdocs/modulebuilder/index.php?module=Inventory@/pathtodolibarr/htdocs/product
 */

/**
 *       \file       htdocs/modulebuilder/index.php
 *       \brief      Home page for module builder module
 */

if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');			// Do not check anti CSRF attack test

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/modulebuilder.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->loadLangs(array("admin", "modulebuilder", "other", "cron"));

$action=GETPOST('action','aZ09');
$confirm=GETPOST('confirm','alpha');
$cancel=GETPOST('cancel','alpha');

$module=GETPOST('module','alpha');
$tab=GETPOST('tab','aZ09');
$tabobj=GETPOST('tabobj','alpha');
$propertykey=GETPOST('propertykey','alpha');
if (empty($module)) $module='initmodule';
if (empty($tab)) $tab='description';
if (empty($tabobj)) $tabobj='newobjectifnoobj';
$file=GETPOST('file','alpha');

$modulename=dol_sanitizeFileName(GETPOST('modulename','alpha'));
$objectname=dol_sanitizeFileName(GETPOST('objectname','alpha'));

// Security check
if (empty($conf->modulebuilder->enabled)) accessforbidden('ModuleBuilderNotAllowed');
if (! $user->admin && empty($conf->global->MODULEBUILDER_FOREVERYONE)) accessforbidden('ModuleBuilderNotAllowed');


// Dir for custom dirs
$tmp=explode(',', $dolibarr_main_document_root_alt);
$dirins = $tmp[0];
$dirread = $dirins;
$forceddirread = 0;

$tmpdir = explode('@', $module);
if (! empty($tmpdir[1]))
{
	$module=$tmpdir[0];
	$dirread=$tmpdir[1];
	$forceddirread=1;
}

$FILEFLAG='modulebuilder.txt';

$now=dol_now();
$newmask = 0;
if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
if (empty($newmask))	// This should no happen
{
    $newmask='0664';
}


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
        dol_delete_file($destdir.'/myobject_note.php');
        dol_delete_file($destdir.'/myobject_document.php');
        dol_delete_file($destdir.'/myobject_agenda.php');
        dol_delete_file($destdir.'/myobject_list.php');
        dol_delete_file($destdir.'/lib/myobject.lib.php');
        dol_delete_file($destdir.'/test/phpunit/MyObjectTest.php');
        dol_delete_file($destdir.'/sql/llx_myobject.sql');
        dol_delete_file($destdir.'/sql/llx_myobject_extrafields.sql');
        dol_delete_file($destdir.'/sql/llx_myobject.key.sql');
        dol_delete_file($destdir.'/scripts/myobject.php');
        dol_delete_file($destdir.'/img/object_myobject.png');
        dol_delete_file($destdir.'/class/myobject.class.php');
        dol_delete_file($destdir.'/class/api_myobject.class.php');
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
	        	'my module'=>$modulename,
	        	'Mon module'=>$modulename,
	        	'mon module'=>$modulename,
	    		'htdocs/modulebuilder/template'=>strtolower($modulename),
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

    $srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
    $destdir = $dirins.'/'.strtolower($module);

    // Scan dir class to find if an object with same name already exists.
    if (! $error)
    {
    	$dirlist=dol_dir_list($destdir.'/class','files',0,'\.txt$');
    	$alreadyfound=false;
    	foreach($dirlist as $key => $val)
    	{
    		$filefound=preg_replace('/\.txt$/','',$val['name']);
    		if (strtolower($objectname) == strtolower($filefound) && $objectname != $filefound)
    		{
    			$alreadyfound=true;
    			$error++;
    			setEventMessages($langs->trans("AnObjectAlreadyExistWithThisNameAndDiffCase"), null, 'errors');
    			break;
    		}
    	}
    }

    if (! $error)
    {
        // Delete some files
        $filetogenerate = array(
            'myobject_card.php'=>strtolower($objectname).'_card.php',
            'myobject_note.php'=>strtolower($objectname).'_note.php',
            'myobject_document.php'=>strtolower($objectname).'_document.php',
            'myobject_agenda.php'=>strtolower($objectname).'_agenda.php',
        	'myobject_list.php'=>strtolower($objectname).'_list.php',
            'lib/myobject.lib.php'=>'lib/'.strtolower($objectname).'.lib.php',
        	'test/phpunit/MyObjectTest.php'=>'test/phpunit/'.$objectname.'Test.php',
            'sql/llx_myobject.sql'=>'sql/llx_'.strtolower($objectname).'.sql',
            'sql/llx_myobject_extrafields.sql'=>'sql/llx_'.strtolower($objectname).'_extrafields.sql',
        	'sql/llx_myobject.key.sql'=>'sql/llx_'.strtolower($objectname).'.key.sql',
        	'scripts/myobject.php'=>'scripts/'.strtolower($objectname).'.php',
            'img/object_myobject.png'=>'img/object_'.strtolower($objectname).'.png',
            'class/myobject.class.php'=>'class/'.strtolower($objectname).'.class.php',
            'class/api_myobject.class.php'=>'class/api_'.strtolower($objectname).'.class.php'
        );

        foreach($filetogenerate as $srcfile => $destfile)
        {
            $result = dol_copy($srcdir.'/'.$srcfile, $destdir.'/'.$destfile, $newmask, 0);
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
                    setEventMessages($langs->trans("FileAlreadyExists", $destfile), null, 'warnings');
                }
            }
            else
            {
                // Copy is ok
                if ($destfile == 'class/'.$objectname.'.txt')
                {
                	// Regenerate left menu entry in descriptor
                	$stringtoadd='';
					// TODO Loop on each .txt file in class dir.
                	$stringtoadd.="
\t\t\$this->menu[\$r++]=array(
                				'fk_menu'=>'fk_mainmenu=mymodule',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'List MyObject',
								'mainmenu'=>'mymodule',
								'leftmenu'=>'mymodule_myobject',
								'url'=>'/mymodule/myobject_list.php',
								'langs'=>'mymodule@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1100+\$r,
								'enabled'=>'\$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '\$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '\$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'\$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
\t\t\$this->menu[\$r++]=array(
                				'fk_menu'=>'fk_mainmenu=mymodule,fk_leftmenu=mymodule_myobject',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'New MyObject',
								'mainmenu'=>'mymodule',
								'leftmenu'=>'mymodule_myobject',
								'url'=>'/mymodule/myobject_card.php?action=create',
								'langs'=>'mymodule@mymodule',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1100+\$r,
								'enabled'=>'\$conf->mymodule->enabled',  // Define condition to show or hide menu entry. Use '\$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '\$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'\$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
               		";
                	$moduledescriptorfile=$dirins.'/'.strtolower($module).'/core/modules/mod'.$module.'.class.php';
                	// TODO Allow a replace with regex using dolReplaceRegexInFile
                	dolReplaceInFile($moduledescriptorfile, array('END MODULEBUILDER LEFTMENU MYOBJECT */' => '*/'."\n".$stringtoadd."\n\t\t/* END MODULEBUILDER LEFTMENU MYOBJECT */"));

					// Add module descriptor to list of files to replace "MyObject' string with real name of object.
                	$filetogenerate[]='core/modules/mod'.$module.'.class.php';
                }
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
                'mymodule'=>strtolower($module),
                'MyModule'=>$module,
                'MYMODULE'=>strtoupper($module),
                'My module'=>$module,
                'my module'=>$module,
                'mon module'=>$module,
            	'Mon module'=>$module,
            	'htdocs/modulebuilder/template/'=>strtolower($modulename),
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
        // Edit the class file to write properties
        rebuildObjectClass($destdir, $module, $objectname, $newmask);

        // Edit sql with new properties
        rebuildObjectSql($destdir, $module, $objectname, $newmask);
    }

    if (! $error)
    {
        setEventMessages($langs->trans('FilesForObjectInitialized', $objectname), null);
    }
}

if ($dirins && $action == 'addproperty' && !empty($module) && ! empty($tabobj))
{
    $objectname = $tabobj;

    $srcdir = $dirread.'/'.strtolower($module);
    $destdir = $dirins.'/'.strtolower($module);
    dol_mkdir($destdir);

    // TODO Complete list of fields with new one

    // Edit the class file to write properties
    $result=rebuildObjectClass($destdir, $module, $objectname, $newmask, $srcdir);
	if ($result <= 0)
	{
		$error++;
	}

    // Edit sql with new properties
    rebuildObjectSql($destdir, $module, $objectname, $newmask, $srcdir);
	if ($result <= 0)
	{
		$error++;
	}

    if (! $error)
    {
        setEventMessages($langs->trans('FilesForObjectUpdated', $objectname), null);
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
    if (preg_match('/[^a-z0-9]/i', $objectname))
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
            'myobject_note.php'=>strtolower($objectname).'_note.php',
            'myobject_document.php'=>strtolower($objectname).'_document.php',
            'myobject_agenda.php'=>strtolower($objectname).'_agenda.php',
        	'myobject_list.php'=>strtolower($objectname).'_list.php',
            'lib/myobject.lib.php'=>'lib/'.strtolower($objectname).'.lib.php',
        	'test/phpunit/MyObjectTest.php'=>'test/phpunit/'.$objectname.'Test.php',
            'sql/llx_myobject.sql'=>'sql/llx_'.strtolower($objectname).'.sql',
            'sql/llx_myobject_extrafields.sql'=>'sql/llx_'.strtolower($objectname).'_extrafields.sql',
        	'sql/llx_myobject.key.sql'=>'sql/llx_'.strtolower($objectname).'.key.sql',
        	'scripts/myobject.php'=>'scripts/'.strtolower($objectname).'.php',
            'img/object_myobject.png'=>'img/object_'.strtolower($objectname).'.png',
            'class/myobject.class.php'=>'class/'.strtolower($objectname).'.class.php',
            'class/api_myobject.class.php'=>'class/api_'.strtolower($objectname).'.class.php'
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

if ($dirins && $action == 'confirm_deleteproperty' && $propertykey)
{
    if (! $error)
    {
        $modulelowercase=strtolower($module);
        $objectlowercase=strtolower($objectname);

        // File of class
        $fileforclass = $dirins.'/'.$modulelowercase.'/class/'.$objectlowercase.'.class.php';

        // TODO

        // File of sql
        $fileforsql = $dirins.'/'.$modulelowercase.'/sql/'.$objectlowercase.'.sql';
        $fileforsqlextra = $dirins.'/'.$modulelowercase.'/sql/'.$objectlowercase.'_extrafields.sql';
        $fileforsqlkey = $dirins.'/'.$modulelowercase.'/sql/'.$objectlowercase.'.key.sql';


        // TODO
    }
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

        $dirofmodule = dol_buildpath($modulelowercase, 0).'/bin';
        $outputfilezip = $dirofmodule.'/'.$FILENAMEZIP;
		if ($dirofmodule)
		{
	        if (! dol_is_dir($dirofmodule)) dol_mkdir($dirofmodule);
	        $result = dol_compress_dir($dir, $outputfilezip, 'zip');
		}
		else
		{
			$result = -1;
		}

        if ($result > 0)
        {
            setEventMessages($langs->trans("ZipFileGeneratedInto", $outputfilezip), null);
        }
        else
        {
            $error++;
            $langs->load("errors");
            setEventMessages($langs->trans("ErrorFailToGenerateFile", $outputfilezip), null, 'errors');
        }
    }
    else
    {
        $error++;
        $langs->load("errors");
        setEventMessages($langs->trans("ErrorCheckVersionIsDefined"), null, 'errors');
    }
}

if ($dirins && $action == 'generatedoc')
{
	$modulelowercase=strtolower($module);

	// Dir for module
	$dir = $dirins.'/'.$modulelowercase;
	// Zip file to build
	$FILENAMEDOC='';

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
		$FILENAMEDOC=$modulelowercase.'.html';

		$dirofmodule = dol_buildpath($modulelowercase, 0).'/doc';
		$outputfiledoc = $dirofmodule.'/'.$FILENAMEDOC;
		if ($dirofmodule)
		{
			if (! dol_is_dir($dirofmodule)) dol_mkdir($dirofmodule);
			//...

			$result = 0;
		}
		else
		{
			$result = 0;
		}

		if ($result > 0)
		{
			setEventMessages($langs->trans("DocFileGeneratedInto", $outputfiledoc), null);
		}
		else
		{
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFailToGenerateFile", $outputfiledoc), null, 'errors');
		}
	}
	else
	{
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorCheckVersionIsDefined"), null, 'errors');
	}
}

// Save file
if ($action == 'savefile' && empty($cancel))
{
    $relofcustom = basename($dirins);

    if ($relofcustom)
    {
        // Check that relative path ($file) start with name 'custom'
        if (! preg_match('/^'.$relofcustom.'/', $file)) $file=$relofcustom.'/'.$file;

        $pathoffile=dol_buildpath($file, 0);
        $pathoffilebackup=dol_buildpath($file.'.back', 0);

        // Save old version
        if (dol_is_file($pathoffile))
        {
            dol_move($pathoffile, $pathoffilebackup, 0, 1, 0, 0);
        }

        $content = GETPOST('editfilecontent');

        // Save file on disk
        file_put_contents($pathoffile, $content);
        @chmod($pathoffile, octdec($newmask));

        setEventMessages($langs->trans("FileSaved"), null);
    }
}

// Enable module
if ($action == 'set' && $user->admin)
{
	$param='';
	if ($module) $param.='&module='.$module;
	if ($tab)    $param.='&tab='.$tab;
	if ($tabobj) $param.='&tabobj='.$tabobj;

	$value = GETPOST('value','alpha');
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
	header("Location: ".$_SERVER["PHP_SELF"]."?".$param);
	exit;
}

// Disable module
if ($action == 'reset' && $user->admin)
{
	$param='';
	if ($module) $param.='&module='.$module;
	if ($tab)    $param.='&tab='.$tab;
	if ($tabobj) $param.='&tabobj='.$tabobj;

	$value = GETPOST('value','alpha');
	$result=unActivateModule($value);
	if ($result) setEventMessages($result, null, 'errors');
	header("Location: ".$_SERVER["PHP_SELF"]."?".$param);
	exit;
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

llxHeader('', $langs->trans("ModuleBuilder"), '', '', 0, 0,
	array(
		'/includes/ace/ace.js',
		'/includes/ace/ext-statusbar.js',
		'/includes/ace/ext-language_tools.js',
		//'/includes/ace/ext-chromevox.js'
	), array());


$text=$langs->trans("ModuleBuilder");

print load_fiche_titre($text, '', 'title_setup');

// Search modules to edit
$listofmodules=array();

$dirsincustom=dol_dir_list($dirread, 'directories');
if (is_array($dirsincustom) && count($dirsincustom) > 0) {
	foreach ($dirsincustom as $dircustomcursor) {
		$fullname = $dircustomcursor['fullname'];
		if (dol_is_file($fullname . '/' . $FILEFLAG)) {
			// Get real name of module (MyModule instead of mymodule)
			$descriptorfiles = dol_dir_list($fullname . '/core/modules/', 'files', 0, 'mod.*\.class\.php$');
			$modulenamewithcase = '';
			foreach ($descriptorfiles as $descriptorcursor) {
				$modulenamewithcase = preg_replace('/^mod/', '', $descriptorcursor['name']);
				$modulenamewithcase = preg_replace('/\.class\.php$/', '', $modulenamewithcase);
			}
			if ($modulenamewithcase)
			{
				$listofmodules[$dircustomcursor['name']] = $modulenamewithcase;
			}
			//var_dump($listofmodules);
		}
	}
}
if ($forceddirread && empty($listofmodules))
{
	$listofmodules[strtolower($module)] = $module;
}

// Show description of content
$newdircustom=$dirins;
if (empty($newdircustom)) $newdircustom=img_warning();
print $langs->trans("ModuleBuilderDesc", 'https://wiki.dolibarr.org/index.php/Module_development#Create_your_module').'<br>';
print $langs->trans("ModuleBuilderDesc2", 'conf/conf.php', $newdircustom).'<br>';
// If dirread was forced to somewhere else, by using URL
// htdocs/modulebuilder/index.php?module=Inventory@/home/ldestailleur/git/dolibarr/htdocs/product
if ($forceddirread) print $langs->trans("DirScanned").' : <strong>'.$dirread.'</strong><br>';

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
    	if (empty($forceddirread)) $error++;
        $langs->load("errors");
        print img_warning('').' '.$langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module).'<br>';
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
    $head[$h][0] = $_SERVER["PHP_SELF"].'?module='.$tmpmodulewithcase.($forceddirread?'@'.$dirread:'');
    $head[$h][1] = $tmpmodulewithcase;
    $head[$h][2] = $tmpmodulewithcase;
    $h++;
}

$head[$h][0] = $_SERVER["PHP_SELF"].'?module=deletemodule';
$head[$h][1] = $langs->trans("DangerZone");
$head[$h][2] = 'deletemodule';
$h++;


dol_fiche_head($head, $module, $langs->trans("Modules"), -1, 'generic');	// Modules

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
        $const_name = 'MAIN_MODULE_'.strtoupper($module);

        $param='';
        if ($tab) $param.= '&tab='.$tab;
        if ($module) $param.='&module='.$module;
        if ($tabobj) $param.='&tabobj='.$tabobj;

        $urltomodulesetup='<a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword='.urlencode($module).'">'.$langs->trans('Home').'-'.$langs->trans("Setup").'-'.$langs->trans("Modules").'</a>';
        $linktoenabledisable='';
        if (! empty($conf->global->$const_name))	// If module is already activated
        {
        	$linktoenabledisable.='<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$moduleobj->numero.'&action=reset&value=mod' . $module . $param . '">';
        	$linktoenabledisable.=img_picto($langs->trans("Activated"),'switch_on');
        	$linktoenabledisable.='</a>';
        }
        else
        {
        	$linktoenabledisable.='<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$moduleobj->numero.'&action=set&value=mod' . $module . $param . '">';
        	$linktoenabledisable.=img_picto($langs->trans("Disabled"),'switch_off');
        	$linktoenabledisable.="</a>\n";
        }

        $modulestatusinfo=img_info('').' '.$langs->trans("ModuleIsNotActive", $urltomodulesetup);
        if (! empty($conf->$module->enabled))
        {
        	$modulestatusinfo=img_warning().' '.$langs->trans("ModuleIsLive");
        }

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=description&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Description");
        $head2[$h][2] = 'description';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=specifications&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Specifications");
        $head2[$h][2] = 'specifications';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=languages&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Languages");
        $head2[$h][2] = 'languages';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Objects");
        $head2[$h][2] = 'objects';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=menus&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Menus");
        $head2[$h][2] = 'menus';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=permissions&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Permissions");
        $head2[$h][2] = 'permissions';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=hooks&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Hooks");
        $head2[$h][2] = 'hooks';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=triggers&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Triggers");
        $head2[$h][2] = 'triggers';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=widgets&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("Widgets");
        $head2[$h][2] = 'widgets';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=cron&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("CronList");
        $head2[$h][2] = 'cron';
        $h++;

        $head2[$h][0] = $_SERVER["PHP_SELF"].'?tab=buildpackage&module='.$module.($forceddirread?'@'.$dirread:'');
        $head2[$h][1] = $langs->trans("BuildPackage");
        $head2[$h][2] = 'buildpackage';
        $h++;

        print $modulestatusinfo;
        print ' '.$linktoenabledisable;
        print '<br><br>';

        if ($tab == 'description')
        {
        	$pathtofile = $modulelowercase.'/core/modules/mod'.$module.'.class.php';
            $pathtofilereadme = $modulelowercase.'/README.md';
            $pathtochangelog = $modulelowercase.'/ChangeLog.md';

            if ($action != 'editfile' || empty($file))
            {
        		dol_fiche_head($head2, $tab, '', -1, '');	// Description - level 2

            	print $langs->trans("ModuleBuilderDesc".$tab).'<br><br>';

            	print '<span class="fa fa-file"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
                print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                print '<br>';

                print '<span class="fa fa-file"></span> '.$langs->trans("ReadmeFile").' : <strong>'.$pathtofilereadme.'</strong>';
                print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=markdown&file='.urlencode($pathtofilereadme).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                print '<br>';

                print '<span class="fa fa-file"></span> '.$langs->trans("ChangeLog").' : <strong>'.$pathtochangelog.'</strong>';
                print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=markdown&file='.urlencode($pathtochangelog).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                print '<br>';

                print '<br>';
                print '<br>';

            	print_fiche_titre($langs->trans("DescriptorFile"));

	            if (! empty($moduleobj))
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

	            	print '</table>';

	            	print '<br><br>';

					// Readme file
	            	print_fiche_titre($langs->trans("ReadmeFile"));

	            	print '<div class="underbanner clearboth"></div>';
	            	print '<div class="fichecenter">';

	            	print $moduleobj->getDescLong();

	            	print '<br><br>';

	            	// ChangeLog
	            	print_fiche_titre($langs->trans("ChangeLog"));

	            	print '<div class="underbanner clearboth"></div>';
	            	print '<div class="fichecenter">';

	            	print $moduleobj->getChangeLog();

	            	print '</div>';
            	}
            	else
            	{
            		print $langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module).'<br>';
            	}

            	dol_fiche_end();
            }
        	else
        	{
        		$fullpathoffile=dol_buildpath($file, 0);	// Description - level 2

        	    $content = file_get_contents($fullpathoffile);

        	    // New module
        	    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        	    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        	    print '<input type="hidden" name="action" value="savefile">';
        	    print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
        	    print '<input type="hidden" name="tab" value="'.$tab.'">';
        	    print '<input type="hidden" name="module" value="'.$module.'">';

        		dol_fiche_head($head2, $tab, '', -1, '');

        	    $doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%', '');
        	    print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'html'));

        	    dol_fiche_end();

        	    print '<center>';
        	    print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
        	    print ' &nbsp; ';
        	    print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
        	    print '</center>';

        	    print '</form>';
        	}
        }
        else
        {
        	dol_fiche_head($head2, $tab, '', -1, '');	// Level 2
        }


        if ($tab == 'specifications')
        {
            if ($action != 'editfile' || empty($file))
            {
            	$specs=dol_dir_list(dol_buildpath($modulelowercase.'/doc', 0), 'files', 1, '(\.md|\.asciidoc)$');

	            foreach ($specs as $spec)
	            {
	            	$pathtofile = $modulelowercase.'/doc/'.$spec['relativename'];
					$format='asciidoc';
					if (preg_match('/\.md$/i', $spec['name'])) $format='markdown';
	            	print '<span class="fa fa-file"></span> '.$langs->trans("SpecificationFile").' : <strong>'.$pathtofile.'</strong>';
	                print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.'&action=editfile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
	                print '<br>';
	            }
            }
            else
            {
                // Use MD or asciidoc

                //print $langs->trans("UseAsciiDocFormat").'<br>';

                $fullpathoffile=dol_buildpath($file, 0);

        	    $content = file_get_contents($fullpathoffile);

                // New module
                print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="savefile">';
                print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
                print '<input type="hidden" name="tab" value="'.$tab.'">';
                print '<input type="hidden" name="module" value="'.$module.'">';

                $doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
                print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'html'));
                print '<br>';
                print '<center>';
                print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
                print ' &nbsp; ';
                print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
                print '</center>';

                print '</form>';
            }
        }

        if ($tab == 'languages')
        {
        	if ($action != 'editfile' || empty($file))
        	{
        		$langfiles=dol_dir_list(dol_buildpath($modulelowercase.'/langs', 0), 'files', 1, '\.lang$');

        		foreach ($langfiles as $langfile)
        		{
        			$pathtofile = $modulelowercase.'/langs/'.$langfile['relativename'];
        			print '<span class="fa fa-file"></span> '.$langs->trans("LanguageFile").' '.basename(dirname($pathtofile)).' : <strong>'.$pathtofile.'</strong>';
        			print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.'&action=editfile&format='.$format.'&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
        			print '<br>';
        		}
        	}
        	else
        	{
        		// Edit text language file

        		//print $langs->trans("UseAsciiDocFormat").'<br>';

        		$fullpathoffile=dol_buildpath($file, 0);

        		$content = file_get_contents($fullpathoffile);

        		// New module
        		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        		print '<input type="hidden" name="action" value="savefile">';
        		print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
        		print '<input type="hidden" name="tab" value="'.$tab.'">';
        		print '<input type="hidden" name="module" value="'.$module.'">';

        		$doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
        		print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'text'));
        		print '<br>';
        		print '<center>';
        		print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
        		print ' &nbsp; ';
        		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
        		print '</center>';

        		print '</form>';
        	}
        }

        if ($tab == 'objects')
        {
            $head3 = array();
            $h=0;

            // Dir for module
            $dir = $dirread.'/'.$modulelowercase.'/class';

            $head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread?'@'.$dirread:'').'&tabobj=newobject';
            $head3[$h][1] = $langs->trans("NewObject");
            $head3[$h][2] = 'newobject';
            $h++;

            // Scan for object class files
            $listofobject = dol_dir_list($dir, 'files', 0, '\.class\.php$');

            $firstobjectname='';
            foreach($listofobject as $fileobj)
            {
            	if (preg_match('/^api_/',$fileobj['name'])) continue;
            	if (preg_match('/^actions_/',$fileobj['name'])) continue;

            	$tmpcontent=file_get_contents($fileobj['fullname']);
            	if (preg_match('/class\s+([^\s]*)\s+extends\s+CommonObject/ims',$tmpcontent,$reg))
            	{
		        	//$objectname = preg_replace('/\.txt$/', '', $fileobj['name']);
            		$objectname = $reg[1];
	                if (empty($firstobjectname)) $firstobjectname = $objectname;
            	}

            	$head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread?'@'.$dirread:'').'&tabobj='.$objectname;
                $head3[$h][1] = $objectname;
                $head3[$h][2] = $objectname;
                $h++;
            }

            $head3[$h][0] = $_SERVER["PHP_SELF"].'?tab=objects&module='.$module.($forceddirread?'@'.$dirread:'').'&tabobj=deleteobject';
            $head3[$h][1] = $langs->trans("DangerZone");
            $head3[$h][2] = 'deleteobject';
            $h++;

            // If tabobj was not defined, then we check if there is one obj. If yes, we force on it, if no, we will show tab to create new objects.
            if ($tabobj == 'newobjectifnoobj')
            {
                if ($firstobjectname) $tabobj=$firstobjectname;
                else $tabobj = 'newobject';
            }

            dol_fiche_head($head3, $tabobj, '', -1, '');	// Level 3

            if ($tabobj == 'newobject')
            {
                // New object tab
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
                // Delete object tab
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
            {	// tabobj = module
                if ($action == 'deleteproperty')
                {
                    $formconfirm = $form->formconfirm(
                        $_SERVER["PHP_SELF"].'?propertykey='.urlencode(GETPOST('propertykey','alpha')).'&objectname='.urlencode($objectname).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj),
                        $langs->trans('Delete'), $langs->trans('ConfirmDeleteProperty', GETPOST('propertykey','alpha')), 'confirm_deleteproperty', '', 0, 1
                        );

                    // Print form confirm
                    print $formconfirm;
                }

                if ($action != 'editfile' || empty($file))
                {
                    try {
                        $pathtoclass    = strtolower($module).'/class/'.strtolower($tabobj).'.class.php';
                        $pathtoapi      = strtolower($module).'/class/api_'.strtolower($tabobj).'.class.php';
                        $pathtoagenda   = strtolower($module).'/'.strtolower($tabobj).'_agenda.php';
                        $pathtocard     = strtolower($module).'/'.strtolower($tabobj).'_card.php';
                        $pathtodocument = strtolower($module).'/'.strtolower($tabobj).'_document.php';
                        $pathtolist     = strtolower($module).'/'.strtolower($tabobj).'_list.php';
                        $pathtonote     = strtolower($module).'/'.strtolower($tabobj).'_note.php';
                        $pathtophpunit  = strtolower($module).'/test/phpunit/'.$tabobj.'Test.php';
                        $pathtosql      = strtolower($module).'/sql/llx_'.strtolower($tabobj).'.sql';
                        $pathtosqlextra = strtolower($module).'/sql/llx_'.strtolower($tabobj).'_extrafields.sql';
                        $pathtosqlkey   = strtolower($module).'/sql/llx_'.strtolower($tabobj).'.key.sql';
                        print '<div class="fichehalfleft">';
                        print '<span class="fa fa-file"></span> '.$langs->trans("ClassFile").' : <strong>'.$pathtoclass.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtoclass).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("ApiClassFile").' : <strong>'.$pathtoapi.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtoapi).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("TestClassFile").' : <strong>'.$pathtophpunit.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtophpunit).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("SqlFile").' : <strong>'.$pathtosql.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=sql&file='.urlencode($pathtosql).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("SqlFileExtraFields").' : <strong>'.$pathtosqlextra.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&file='.urlencode($pathtosqlextra).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("SqlFileKey").' : <strong>'.$pathtosqlkey.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=sql&file='.urlencode($pathtosqlkey).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '</div>';
                        print '<div class="fichehalfleft">';
                        print '<span class="fa fa-file"></span> '.$langs->trans("PageForList").' : <strong>'.$pathtolist.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtolist).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("PageForCreateEditView").' : <strong>'.$pathtocard.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtocard).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("PageForAgendaTab").' : <strong>'.$pathtoagenda.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtoagenda).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("PageForDocumentTab").' : <strong>'.$pathtodocument.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtodocument).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '<br>';
                        print '<span class="fa fa-file"></span> '.$langs->trans("PageForNoteTab").' : <strong>'.$pathtonote.'</strong>';
                        print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.($forceddirread?'@'.$dirread:'').'&action=editfile&format=php&file='.urlencode($pathtonote).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
                        print '</div>';

                        print '<br><br><br>';

                        if (empty($forceddirread))
                        {
                        	$result = dol_include_once($pathtoclass);
                        }
                        else
                        {
                        	$result = @include_once($dirread.'/'.$pathtoclass);
                        }
                        if (class_exists($tabobj)) $tmpobjet = new $tabobj($db);

                        $reflector = new ReflectionClass($tabobj);
                        $properties = $reflector->getProperties();          // Can also use get_object_vars
                        $propdefault = $reflector->getDefaultProperties();  // Can also use get_object_vars
                        //$propstat = $reflector->getStaticProperties();

                        print load_fiche_titre($langs->trans("Properties"), '', '');

                        print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
                        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                        print '<input type="hidden" name="action" value="addproperty">';
                        print '<input type="hidden" name="tab" value="objects">';
                        print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module.($forceddirread?'@'.$dirread:'')).'">';
                        print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

                        print '<table class="noborder">';
                        print '<tr class="liste_titre">';
                        print '<td>'.$langs->trans("Property");
                        print ' (<a href="https://wiki.dolibarr.org/index.php/Language_and_development_rules#Table_and_fields_structures" target="_blank">'.$langs->trans("Example").'</a>)';
                        print '</td>';
                        print '<td>';
                        print $form->textwithpicto($langs->trans("Label"), $langs->trans("YouCanUseTranslationKey"));
                        print '</td>';
                        print '<td>'.$langs->trans("Type").'</td>';
                        print '<td class="center">'.$langs->trans("NotNull").'</td>';
                        //print '<td>'.$langs->trans("DefaultValue").'</td>';
                        print '<td class="center">'.$langs->trans("DatabaseIndex").'</td>';
                        print '<td class="right">'.$langs->trans("Position").'</td>';
                        print '<td class="right">'.$langs->trans("Enabled").'</td>';
                        print '<td class="right">'.$langs->trans("Visible").'</td>';
                        print '<td class="right">'.$langs->trans("IsAMeasure").'</td>';
                        print '<td class="center">'.$langs->trans("SearchAll").'</td>';
                        print '<td>'.$langs->trans("Comment").'</td>';
                        print '<td></td>';
                        print '</tr>';
                        print '<tr>';
                        print '<td><input class="text" name="propname" value=""></td>';
                        print '<td><input class="text" name="proplabel" value=""></td>';
                        print '<td><input class="text" name="proptype" value=""></td>';
                        print '<td class="center"><input class="text" size="2" name="propnotnull" value=""></td>';
                        //print '<td><input class="text" name="propdefault" value=""></td>';
                        print '<td class="center"><input class="text" size="2" name="propindex" value=""></td>';
                        print '<td class="right"><input class="text right" size="2" name="propposition" value=""></td>';
                        print '<td class="center"><input class="text" size="2" name="propenabled" value=""></td>';
                        print '<td class="center"><input class="text" size="2" name="propvisible" value=""></td>';
                        print '<td class="center"><input class="text" size="2" name="propisameasure" value=""></td>';
                        print '<td class="center"><input class="text" size="2" name="propsearchall" value=""></td>';
                        print '<td><input class="text" name="propcomment" value=""></td>';
                        print '<td align="center">';
                        print '<input class="button" type="submit" name="add" value="'.$langs->trans("Add").'">';
                        print '</td></tr>';

                        $properties = $tmpobjet->fields;

                        foreach($properties as $propkey => $propval)
                        {
                            /* If from Reflection
                            if ($propval->class == $tabobj)
                            {
                                $propname=$propval->getName();
                                $comment=$propval->getDocComment();
                                $type=gettype($tmpobjet->$propname);
                                $default=$propdefault[$propname];
                                // Discard generic properties
                                if (in_array($propname, array('element', 'childtables', 'table_element', 'table_element_line', 'class_element_line', 'isnolinkedbythird', 'ismultientitymanaged'))) continue;

                                // Keep or not lines
                                if (in_array($propname, array('fk_element', 'lines'))) continue;
                            }*/

                            $propname=$propkey;
                            $proplabel=$propval['label'];
                            $proptype=$propval['type'];
                            $propnotnull=$propval['notnull'];
                            $propsearchall=$propval['searchall'];
                            //$propdefault=$propval['default'];
                            $propindex=$propval['index'];
                            $propposition=$propval['position'];
                            $propenabled=$propval['enabled'];
                            $propvisible=$propval['visible'];
                            $propisameasure=$propval['isameasure'];
                            $propcomment=$propval['comment'];

                            print '<tr class="oddeven">';

                            print '<td>';
                            print $propname;
                            print '</td>';
                            print '<td>';
                            print $proplabel;
                            print '</td>';
                            print '<td>';
                            print $proptype;
                            print '</td>';
                            print '<td class="center">';
                            print $propnotnull?'X':'';
                            print '</td>';
                            /*print '<td>';
                            print $propdefault;
                            print '</td>';*/
                            print '<td class="center">';
                            print $propindex?'X':'';
                            print '</td>';
                            print '<td align="right">';
                            print $propposition;
                            print '</td>';
                            print '<td class="center">';
                            print $propenabled?$propenabled:'';
                            print '</td>';
                            print '<td class="center">';
                            print $propvisible?$propvisible:'';
                            print '</td>';
                            print '<td class="center">';
                            print $propisameasure?$propisameasure:'';
                            print '</td>';
                            print '<td class="center">';
                            print $propsearchall?'X':'';
                            print '</td>';
                            print '<td>';
                            print $propcomment;
                            print '</td>';
                            print '<td class="center">';
                            print '<a href="'.$_SERVER["PHP_SELF"].'?action=deleteproperty&propertykey='.urlencode($propname).'&tab='.urlencode($tab).'&module='.urlencode($module).'&tabobj='.urlencode($tabobj).'">'.img_delete().'</a>';
                            print '</td>';

                            print '</tr>';
                        }
                        print '</table>';

                        print '</form>';
                    }
                    catch(Exception $e)
                    {
                        print $e->getMessage();
                    }
                }
                else
                {
                	if (empty($forceddirread))
                	{
                		$fullpathoffile=dol_buildpath($file, 0);
                	}
                	else
                	{
                		$fullpathoffile=$dirread.'/'.$file;
                	}

                    $content = file_get_contents($fullpathoffile);

                    // New module
                    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="savefile">';
                    print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
                    print '<input type="hidden" name="tab" value="'.$tab.'">';
                    print '<input type="hidden" name="module" value="'.$module.($forceddirread?'@'.$dirread:'').'">';

                    $doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
                    print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'html'));
                    print '<br>';
                    print '<center>';
                    print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
                    print ' &nbsp; ';
                    print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
                    print '</center>';

                    print '</form>';
                }
            }

            dol_fiche_end();	// Level 3
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
        	if ($action != 'editfile' || empty($file))
			{
				$pathtohook = strtolower($module).'/class/actions_'.strtolower($module).'.class.php';
   			    print '<span class="fa fa-file"></span> '.$langs->trans("HooksFile").' : <strong>'.$pathtohook.'</strong>';
   			    print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.'&action=editfile&format=php&file='.urlencode($pathtohook).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
   			    print '<br>';
			}
			else
			{
        	    $fullpathoffile=dol_buildpath($file, 0);

        	    $content = file_get_contents($fullpathoffile);

			    // New module
			    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			    print '<input type="hidden" name="action" value="savefile">';
			    print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
			    print '<input type="hidden" name="tab" value="'.$tab.'">';
			    print '<input type="hidden" name="module" value="'.$module.'">';

			    $doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
                print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'html'));
                print '<br>';
                print '<center>';
                print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
                print ' &nbsp; ';
                print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
                print '</center>';

                print '</form>';
			}
        }

        if ($tab == 'triggers')
        {
        	require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

			$interfaces = new Interfaces($db);
			$triggers = $interfaces->getTriggersList(array('/'.strtolower($module).'/core/triggers'));

			if ($action != 'editfile' || empty($file))
			{
				if (! empty($triggers))
				{
	    			foreach ($triggers as $trigger)
	    			{
	    			    $pathtofile = $trigger['relpath'];

	    			    print '<span class="fa fa-file"></span> '.$langs->trans("TriggersFile").' : <strong>'.$pathtofile.'</strong>';
	    			    print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
	    			    print '<br>';
	    			}
				}
				else
				{
					print $langs->trans("NoTrigger");
				}
			}
			else
			{
        	    $fullpathoffile=dol_buildpath($file, 0);

        	    $content = file_get_contents($fullpathoffile);

			    // New module
			    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			    print '<input type="hidden" name="action" value="savefile">';
			    print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
			    print '<input type="hidden" name="tab" value="'.$tab.'">';
			    print '<input type="hidden" name="module" value="'.$module.'">';

			    $doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
                print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'html'));
                print '<br>';
                print '<center>';
                print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
                print ' &nbsp; ';
                print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
                print '</center>';

                print '</form>';
			}
        }

        if ($tab == 'widgets')
        {
            require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

			$widgets = ModeleBoxes::getWidgetsList(array('/'.strtolower($module).'/core/boxes'));

			if ($action != 'editfile' || empty($file))
			{
				if (! empty($widget))
				{
	    			foreach ($widgets as $widget)
	    			{
	    			    $pathtofile = $widget['relpath'];

	    			    print '<span class="fa fa-file"></span> '.$langs->trans("WidgetFile").' : <strong>'.$pathtofile.'</strong>';
	    			    print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
	    			    print '<br>';
	    			}
    			}
    			else
    			{
    				print $langs->trans("NoWidget");
    			}
			}
			else
			{
        	    $fullpathoffile=dol_buildpath($file, 0);

        	    $content = file_get_contents($fullpathoffile);

			    // New module
			    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			    print '<input type="hidden" name="action" value="savefile">';
			    print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
			    print '<input type="hidden" name="tab" value="'.$tab.'">';
			    print '<input type="hidden" name="module" value="'.$module.'">';

			    $doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
                print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'html'));
                print '<br>';
                print '<center>';
                print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
                print ' &nbsp; ';
                print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
                print '</center>';

                print '</form>';
			}
        }

        if ($tab == 'cron')
        {
        	$pathtofile = $modulelowercase.'/core/modules/mod'.$module.'.class.php';

        	$cronjobs = $moduleobj->cronjobs;

        	if ($action != 'editfile' || empty($file))
        	{
        		print $langs->trans("CronJobDefDesc", '<a href="'.DOL_URL_ROOT.'/cron/list.php?status=-2">'.$langs->trans('CronList').'</a>').'<br>';
        		print '<br>';

        		print '<span class="fa fa-file"></span> '.$langs->trans("DescriptorFile").' : <strong>'.$pathtofile.'</strong>';
        		print ' <a href="'.$_SERVER['PHP_SELF'].'?tab='.$tab.'&module='.$module.'&action=editfile&format=php&file='.urlencode($pathtofile).'">'.img_picto($langs->trans("Edit"), 'edit').'</a>';
        		print '<br>';

        		print '<br>';
        		print load_fiche_titre($langs->trans("CronJobProfiles"), '', '');

        		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        		print '<input type="hidden" name="action" value="addproperty">';
        		print '<input type="hidden" name="tab" value="objects">';
        		print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
        		print '<input type="hidden" name="tabobj" value="'.dol_escape_htmltag($tabobj).'">';

        		print '<div class="div-table-responsive">';
        		print '<table class="noborder">';

        		print '<tr class="liste_titre">';
        		print_liste_field_titre("CronLabel",$_SERVER["PHP_SELF"],"","",$param,'',$sortfield,$sortorder);
        		print_liste_field_titre("CronTask",'','',"",$param,'',$sortfield,$sortorder);
        		print_liste_field_titre("CronFrequency",'',"","",$param,'',$sortfield,$sortorder);
        		print_liste_field_titre("StatusAtInstall",$_SERVER["PHP_SELF"],"","",$param,'',$sortfield,$sortorder);
        		print_liste_field_titre("Comment",$_SERVER["PHP_SELF"],"","",$param,'',$sortfield,$sortorder);
        		print "</tr>\n";

        		if (count($cronjobs))
        		{
	        		foreach ($cronjobs as $cron)
	        		{
	        			print '<tr class="oddeven">';

	        			print '<td>';
	        			print $cron['label'];
	        			print '</td>';

	        			print '<td>';
	        			if ($cron['jobtype']=='method')
	        			{
	        				$text=$langs->trans("CronClass");
	        				$texttoshow=$langs->trans('CronModule').': '.$module.'<br>';
	        				$texttoshow.=$langs->trans('CronClass').': '. $cron['class'].'<br>';
	        				$texttoshow.=$langs->trans('CronObject').': '. $cron['objectname'].'<br>';
	        				$texttoshow.=$langs->trans('CronMethod').': '. $cron['method'];
	        				$texttoshow.='<br>'.$langs->trans('CronArgs').': '. $cron['parameters'];
	        				$texttoshow.='<br>'.$langs->trans('Comment').': '. $langs->trans($cron['comment']);
	        			}
	        			elseif ($cron['jobtype']=='command')
	        			{
	        				$text=$langs->trans('CronCommand');
	        				$texttoshow=$langs->trans('CronCommand').': '.dol_trunc($cron['command']);
	        				$texttoshow.='<br>'.$langs->trans('CronArgs').': '. $cron['parameters'];
	        				$texttoshow.='<br>'.$langs->trans('Comment').': '. $langs->trans($cron['comment']);
	        			}
	        			print $form->textwithpicto($text, $texttoshow, 1);
	        			print '</td>';

	        			print '<td>';
	        			if($cron['unitfrequency'] == "60") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Minutes');
	        			if($cron['unitfrequency'] == "3600") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Hours');
	        			if($cron['unitfrequency'] == "86400") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Days');
	        			if($cron['unitfrequency'] == "604800") print $langs->trans('CronEach')." ".($cron['frequency'])." ".$langs->trans('Weeks');
	        			print '</td>';

	        			print '<td>';
	        			print $cron['status'];
	        			print '</td>';

	        			print '<td>';
	        			if (!empty($cron['comment'])) {print $cron['comment'];}
	        			print '</td>';

	        			print '</tr>';
	        		}
        		}
        		else
        		{
        			print '<tr><td class="opacitymedium" colspan="5">'.$langs->trans("None").'</td></tr>';
        		}

        		print '</table>';
        		print '</div>';
        	}
        	else
        	{
        		$fullpathoffile=dol_buildpath($file, 0);

        		$content = file_get_contents($fullpathoffile);

        		// New module
        		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        		print '<input type="hidden" name="action" value="savefile">';
        		print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'">';
        		print '<input type="hidden" name="tab" value="'.$tab.'">';
        		print '<input type="hidden" name="module" value="'.$module.'">';

        		$doleditor=new DolEditor('editfilecontent', $content, '', '300', 'Full', 'In', true, false, 'ace', 0, '99%');
        		print $doleditor->Create(1, '', false, $langs->trans("File").' : '.$file, (GETPOST('format','aZ09')?GETPOST('format','aZ09'):'html'));
        		print '<br>';
        		print '<center>';
        		print '<input type="submit" class="button" id="savefile" name="savefile" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
        		print ' &nbsp; ';
        		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
        		print '</center>';

        		print '</form>';
        	}
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
                $outputfilezip = dol_buildpath($modulelowercase, 0).'/bin/'.$FILENAMEZIP;

                $FILENAMEDOC=$modulelowercase.'.html';
                $outputfiledoc = dol_buildpath($modulelowercase, 0).'/doc/'.$FILENAMEDOC;
            }

            print '<br>';

            print '<span class="fa fa-file"></span> '. $langs->trans("PathToModulePackage") . ' : ';
            if (! dol_is_file($outputfilezip)) print '<strong>'.$langs->trans("FileNotYetGenerated").'</strong>';
            else {
            	$relativepath = $modulelowercase.'/bin/'.$FILENAMEZIP;
                print '<strong><a href="'.DOL_URL_ROOT.'/document.php?modulepart=packages&file='.urlencode($relativepath).'">'.$outputfilezip.'</a></strong>';
                print ' ('.$langs->trans("GeneratedOn").' '.dol_print_date(dol_filemtime($outputfilezip), 'dayhour').')';
            }
            print '</strong><br>';

            print '<br>';

        	print '<form name="generatepackage">';
        	print '<input type="hidden" name="action" value="generatepackage">';
        	print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
        	print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
        	print '<input type="submit" class="button" name="generatepackage" value="'.$langs->trans("BuildPackage").'">';
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

            print '<form name="generatedoc">';
        	print '<input type="hidden" name="action" value="generatedoc">';
        	print '<input type="hidden" name="tab" value="'.dol_escape_htmltag($tab).'">';
        	print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
        	print '<input type="submit" class="button" name="generatedoc" value="'.$langs->trans("BuildDocumentation").'">';
        	print '</form>';
        }

        if ($tab != 'description')
        {
        	dol_fiche_end();
        }
    }
}

dol_fiche_end(); // End modules



llxFooter();

$db->close();
