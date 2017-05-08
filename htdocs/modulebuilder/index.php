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

// Security check
if (! $user->admin && empty($conf->global->MODULEBUILDER_FOREVERYONE)) accessforbidden('ModuleBuilderNotAllowed');

$modulename=dol_sanitizeFileName(GETPOST('modulename','alpha'));

// Dir for custom dirs
$tmp=explode(',', $dolibarr_main_document_root_alt);
$dircustom = $tmp[0];



/*
 * Actions
 */

if ($dircustom && $action == 'initmodule' && $modulename)
{
    $srcfile = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
    $destfile = $dircustom.'/'.$modulename;
    $result = dolCopyDir($srcfile, $destfile, 0, 0);
    //dol_mkdir($destfile);
    
    fopen($destfile, $mode)
    
    if ($result > 0)
    {
        setEventMessages('ModuleInitialized', null);
    }
    else
    {
        setEventMessages($langs->trans("ErrorFailedToCopyDir"), null, 'errors');
    }
}


/*
 * View
 */

$socstatic=new Societe($db);


llxHeader("",$langs->trans("ModuleBuilder"),"");


$text=$langs->trans("ModuleBuilder");

print load_fiche_titre($text, '', 'title_setup');

// Show description of content
print $langs->trans("ModuleBuilderDesc").'<br>';
print $langs->trans("ModuleBuilderDesc2", 'conf/conf.php', $dircustom).'<br>';
print '<br>';


// New module
print '<div class="modulebuilderbox">';
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="initmodule">';
print '<input type="text" name="modulename" value="" placeholder="'.dol_escape_htmltag($langs->trans("ModuleName")).'">';
print '<input type="submit" class="button" name="create" value="'.dol_escape_htmltag($langs->trans("CreateNewModule")).'">';
print '</form>';
print '<div>';


$listofmodules=array();
/*
if (!empty($conf->modulebuilder->enabled) && $mainmenu == 'modulebuilder')	// Entry for Module builder
{
    global $dolibarr_main_document_root_alt;
    if (! empty($dolibarr_main_document_root_alt) && is_array($dolibarr_main_document_root_alt))
    {
        foreach ($dolibarr_main_document_root_alt as $diralt)
        {*/
            $dirsincustom=dol_dir_list($dircustom);
            
            if (is_array($dirsincustom) && count($dirsincustom) > 0)
            {
                foreach ($dirsincustom as $dircustom)
                {
                    $fullname = $dircustom['fullname'];
                    if (dol_is_file($fullname.'/modulebuilder.txt'))
                    {
                        $listofmodules[$module]=$fullname;
                    }
                }
            }
/*        }
    }
    else
    {
        $newmenu->add('', 'NoGeneratedModuleFound', 0, 0);
    }*/

foreach($listofmodules as $modules => $fullname)
{
    print '<div class="modulebuilderbox>'.$module.'</div>';
}
            

llxFooter();

$db->close();
