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
require_once DOL_DOCUMENT_ROOT.'/websites/class/websitepage.class.php';

$langs->load("admin");
$langs->load("other");
$langs->load("website");

if (! $user->admin) accessforbidden();

$conf->dol_hide_leftmenu = 1;

$error=0;
$website=GETPOST('website', 'alpha');
$page=GETPOST('page', 'alpha');
$pageid=GETPOST('pageid', 'alpha');
$action=GETPOST('action','alpha');

if (GETPOST('preview')) $action='preview';
if (GETPOST('create')) { $action='create'; }
if (GETPOST('editmenu')) { $action='editmenu'; }
if (GETPOST('editmeta')) { $action='editmeta'; }
if (GETPOST('editcontent')) { $action='editcontent'; }

if (empty($action)) $action='preview';

$object=new Website($db);
$objectpage=new WebsitePage($db);

if ($website)
{
    $res = $object->fetch(0, $website);
}
if ($pageid)
{
    $res = $objectpage->fetch($pageid);
}


/*
 * Actions
 */

// Add page
if ($action == 'add')
{
    $db->begin();
    
    $objectpage->fk_website = $object->id;
    
    $objectpage->title = GETPOST('WEBSITE_TITLE');
    $objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
    $objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
    $objectpage->keyword = GETPOST('WEBSITE_KEYWORD');
    
    if (empty($objectpage->title))
    {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_PAGENAME")), null, 'errors');
        $error++;
    }
    
    if (! $error)
    {
        $res = $objectpage->create($user);
        if ($res <= 0)
        {
            $error++;
            setEventMessages($objectpage->error, $objectpage->errors, 'errors');
        }
    }
	if (! $error)
	{
		$db->commit();
	    setEventMessages($langs->trans("PageAdded"), null, 'mesgs');
	    $action='';
	}
	else
	{
		$db->rollback();
	}
}

// Update page
if ($action == 'update')
{
    $db->begin();
    
    $res = $object->fetch(0, $website);
    
    $objectpage->fk_website = $object->id;
    $objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
    
    $res = $objectpage->fetch(0, $object->fk_website, $objectpage->pageurl);
    
    if ($res > 0)
    {
        $objectpage->title = GETPOST('WEBSITE_TITLE');
        $objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
        $objectpage->keyword = GETPOST('WEBSITE_KEYWORD');
        
        $res = $objectpage->update($user);
        if (! $res > 0)
        {
            $error++;
            setEventMessages($objectpage->error, $objectpage->errors, 'errors');
        }
        
    	if (! $error)
    	{
    		$db->commit();
    	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    	    $action='';
    	}
    	else
    	{
    		$db->rollback();
    	}
    }
    else
    {
        dol_print_error($db);
    }
}

// Update page
if ($action == 'updatecontent')
{
    $db->begin();
    
    $object->fetch(0, $website);
    
    $objectpage->fk_website = $object->id;
    $objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
    
    $res = $objectpage->fetch(0, $object->fk_website, $objectpage->pageurl);
    
    if ($res > 0)
    {
        $objectpage->content = GETPOST('PAGE_CONTENT');
        
        $res = $objectpage->update($user);
        if (! $res > 0)
        {
            $error++;
            setEventMessages($objectpage->error, $objectpage->errors, 'errors');
        }
        
    	if (! $error)
    	{
    		$db->commit();
    	    setEventMessages($langs->trans("Saved"), null, 'mesgs');
    	    $action='';
    	}
    	else
    	{
    		$db->rollback();
    	}
    }
    else
    {
        dol_print_error($db);
    }
}



/*
 * View
 */

$form = new Form($db);

$help_url='';

llxHeader('', $langs->trans("WebsiteSetup"), $help_url);

print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
if ($action == 'create')
{
    print '<input type="hidden" name="action" value="add">';
}
if ($action == 'editcontent')
{
    print '<input type="hidden" name="action" value="updatecontent">';
}
if ($action == 'edit')
{
    print '<input type="hidden" name="action" value="update">';
}
if ($website) print '<input type="hidden" name="website" value="'.dol_escape_htmltag($website).'">';


// Add a margin under toolbar ?
$style='';
if ($action != 'preview' && $action != 'editcontent') $style=' margin-bottom: 5px;';


print '<div class="centpercent websitebar">';

$tmp = $object->fetchAll();
if (count($object->records) > 0)
{
    print '<div class="websiteselection">';
    print $langs->trans("Website").': ';
    print '</div>';
    
    print '<div class="websiteselection">';
    // Loop on each sites
    $i=0;
    foreach($object->records as $key => $valwebsite)
    {
        if (empty($website)) $website=$valwebsite->ref;

        if ($i) print ' - ';
        print '<a href="'.$_SERVER["PHP_SELF"].'?website='.urlencode($valwebsite->ref).'">';
        if ($valwebsite->ref == $website) print '<strong>';
        print $valwebsite->ref;
        if ($valwebsite->ref == $website) print '</strong>';
        print '</a>';
        
        $i++;    
    }
    
    print '</div>';
    
    print '<div class="websitetools">';
    
    if ($action == 'preview') 
    {
        $disabled='';
        if (empty($user->rights->websites->create)) $disabled=' disabled="disabled"';

        print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditMenu")).'" name="editmenu">';
        print '<input type="submit"'.$disabled.' class="button" value="'.dol_escape_htmltag($langs->trans("AddPage")).'" name="create">';
    }
    //else print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
    
    print '</div>';
    
    
    // Part for pages
    if ($website)
    {
        print '</div>';

        $array=$objectpage->fetchAll($object->id);
        
        print '<div class="centpercent websitebar"'.($style?' style="'.$style.'"':'').'">';
        print '<div class="websiteselection">';
        print $langs->trans("Page").': ';
        print '</div>';
        print '<div class="websiteselection">';
        $out='';
        $out.='<select name="pageid">';
        foreach($array as $key => $valpage)
        {
            if (empty($pageid) && $action != 'create') $pageid=$valpage->id;
            
            $out.='<option value="'.$key.'"';
            if ($pageid > 0 && $pageid == $key) $out.=' selected';		// To preselect a value
            $out.='>';
            $out.=$valpage->title;
            $out.='</option>';
        }
        $out.='</select>';
        print $out;
        print '<input type="submit" class="button" name="refresh" value="'.$langs->trans("Refresh").'">';
        //print $form->selectarray('page', $array);
        print '</div>';
        print '<div class="websiteselection">';
        print '</div>';
        
        print '<div class="websitetools">';
        
        if ($action == 'preview')
        {
            $disabled='';
            if (empty($user->rights->websites->create)) $disabled=' disabled="disabled"';
        
            if ($pageid > 0)
            {
                print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageMeta")).'" name="editmeta">';
                print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageContent")).'" name="editcontent">';
            }
        }
        else print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
        if (preg_match('/^create/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
        if (preg_match('/^edit/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
        
        print '</div>';
        
    }    
}
else
{
    print '<div class="websiteselection">';
    $langs->load("errors");
    print $langs->trans("ErrorModuleSetupNotComplete");
    print '<div>';
    $action='';
}


print '</div>';

$head = array();


/*
 * Edit mode
 */

if ($action == 'editmeta' || $action == 'create')
{
    print '<div class="fiche">';
    
    dol_fiche_head();
    
    print '<table class="border" width="100%">';
    
    print '<tr><td>';
    print $langs->trans('WEBSITE_PAGENAME');
    print '</td><td>';
    print '<input type="text" class="flat" size="96" name="WEBSITE_PAGENAME" value="'.dol_escape_htmltag($page).'">';
    print '</td></tr>';
    
    if ($action != 'create')
    {
        print '<tr><td>';
        print $langs->trans('WEBSITE_URL');
        print '</td><td>';
        print '/public/websites/'.$website.'/index.php?pageid='.urlencode($pageid);
        print '</td></tr>';
    }
    
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
    
    dol_fiche_end();
    
    print '</div>';
    
    print '<br>';
}

if ($action == 'editmenu')
{
    print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';
}

if ($action == 'editcontent')
{
    /*
     * Editing global variables not related to a specific theme
     */
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('PAGE_CONTENT',$obj->value,'',160,'dolibarr_notes','',false,false,$conf->fckeditor->enabled,5,60);
    $doleditor->Create();
}

print '</form>';



if ($action == 'preview')
{
    if ($pageid > 0)
    {
        $objectpage->fetch($pageid);
        
        print '<!-- Page content -->'."\n";
        print '<div class="websitecontent">';
        print $objectpage->content;
        print '</div>';
    }
    else
    {
        print '<br><br><div class="center">'.$langs->trans("PreviewOfSiteNotYetAvailable", $website).'</center><br><br><br>';
        print '<div class="center"><div class="logo_setup"></div></div>';
    }
}

    

llxFooter();

$db->close();
