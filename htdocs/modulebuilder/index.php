<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Security check
if (! $user->admin && empty($conf->global->MODULEBUILDER_FOREVERYONE)) accessforbidden('ModuleBuilderNotAllowed');



/*
 * View
 */

$socstatic=new Societe($db);


llxHeader("",$langs->trans("ModuleBuilder"),"");


$text=$langs->trans("ModuleBuilder");

print load_fiche_titre($text, '', 'title_setup');

$tmp=explode(',', $dolibarr_main_document_root_alt);
$dircustom = $tmp[0];

// Show description of content
print $langs->trans("ModuleBuilderDesc", $dircustom).'<br><br>';



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
                        print '<div class="boxstats">'.$module.'</div>';
                    }
                }
            }
/*        }
    }
    else
    {
        $newmenu->add('', 'NoGeneratedModuleFound', 0, 0);
    }*/



llxFooter();

$db->close();
