<?php
/* Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/admin/website.php
 *		\ingroup    website
 *		\brief      Page to setup the module Website
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load("other");
$langs->load("website");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

$conf->dol_hide_leftmenu = 1;


$website='website1';



/*
 * Actions
 */

// Action mise a jour ou ajout d'une constante
if ($action == 'update')
{

    
    
	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}




/*
 * View
 */

$_SESSION['website_mode'] = 'edit';


$form = new Form($db);

$help_url='';

llxHeader('',$langs->trans("WebsiteSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("WebsiteSetup"),$linkback,'title_setup');


$head = array();


/*
 * Edit mode
 */

if ($_SESSION['website_mode'] == 'edit')
{
    print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    
    dol_fiche_head($head, 'general', $langs->trans("Page").': '.$langs->trans("Home"), 0, 'globe');
    
    print load_fiche_titre($langs->trans("SEO"),'','');
    
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td>'.$langs->trans("Value").'</td>';
    print "</tr>\n";
    
    print '<tr><td>';
    print $langs->trans('WEBSITE_PAGEURL');
    print '</td><td>';
    print '/public/websites/'.$website.'/index.php?page=home';
    print '</td></tr>';
    
    print '<tr><td>';
    print $langs->trans('WEBSITE_TITLE');
    print '</td><td>';
    print '<input type="text" class="flat" size="96" name="WEBSITE_TITLE" value="'.dol_escape_htmltag($obj->WEBSITE_TITLE).'">';
    print '</td></tr>';
    
    print '<tr><td>';
    print $langs->trans('WEBSITE_DESCRIPTION');
    print '</td><td>';
    print '<input type="text" class="flat" size="96" name="WEBSITE_DESCRIPTION" value="'.dol_escape_htmltag($obj->WEBSITE_DESCRIPTION).'">';
    print '</td></tr>';
    
    print '<tr><td>';
    print $langs->trans('WEBSITE_KEYWORDS');
    print '</td><td>';
    print '<input type="text" class="flat" size="128" name="WEBSITE_KEYWORDS" value="'.dol_escape_htmltag($obj->WEBSITE_KEYWORDS).'">';
    print '</td></tr>';
    
    print '</table>';
    
    print '<br>';
    
    
    /*
     * Editing global variables not related to a specific theme
     */
    
    print load_fiche_titre($langs->trans("Other"),'','');
    
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('WEBSITE_HEADER',$obj->value,'',160,'dolibarr_notes','',false,false,$conf->fckeditor->enabled,5,60);
    $doleditor->Create();
    
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('WEBSITE_CONTENT',$obj->value,'',160,'dolibarr_notes','',false,false,$conf->fckeditor->enabled,5,60);
    $doleditor->Create();
    
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('WEBSITE_FOOTER',$obj->value,'',160,'dolibarr_notes','',false,false,$conf->fckeditor->enabled,5,60);
    $doleditor->Create();
    
    dol_fiche_end();
    
    print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Update").'" name="update"></div>';
    
    print '</form>';
}



llxFooter();

$db->close();
