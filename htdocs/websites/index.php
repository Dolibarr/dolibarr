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


/**
 *	Show HTML header HTML + BODY + Top menu + left menu + DIV
 *
 * @param 	string 	$head				Optionnal head lines
 * @param 	string 	$title				HTML title
 * @param	string	$help_url			Url links to help page
 * 		                            	Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage
 *                                  	For other external page: http://server/url
 * @param	string	$target				Target to use on links
 * @param 	int    	$disablejs			More content into html header
 * @param 	int    	$disablehead		More content into html header
 * @param 	array  	$arrayofjs			Array of complementary js files
 * @param 	array  	$arrayofcss			Array of complementary css files
 * @param	string	$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @return	void
 */
function llxHeader($head='', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='')
{
    global $conf;

    // html header
    top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

    // top menu and left menu area
    if (empty($conf->dol_hide_topmenu))
    {
        top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $help_url);
    }
    if (empty($conf->dol_hide_leftmenu))
    {
        left_menu('', $help_url, '', '', 1, $title, 1);
    }

    // main area
    //main_area($title);
}



require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/websites/class/website.class.php';

$langs->load("admin");
$langs->load("other");
$langs->load("website");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

$conf->dol_hide_leftmenu = 1;


$website='website1';
$object=new Website($db);


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

llxHeader('', $langs->trans("WebsiteSetup"), $help_url);

$style=' style="padding-top: 4px; padding-left: 10px; border-bottom: 1px solid #888; height: 20px; vertical-align: middle; margin-bottom: 5px;"';

print '<div class="websiteselection centpercent"'.$style.'>';

// Loop on each sites

$tmp = $object->fetchAll();
foreach($object->lines as $websitearray)
{
    var_dump($websitearray);
}

print '</div>';

$head = array();


/*
 * Edit mode
 */

if ($_SESSION['website_mode'] == 'edit')
{
    print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    
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
    
    print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Update").'" name="update"></div>';
    
    print '</form>';
}



llxFooter();

$db->close();
