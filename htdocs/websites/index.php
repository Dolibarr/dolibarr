<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/website/index.php
 *		\ingroup    website
 *		\brief      Page to website view/edit
 */

define('NOSCANPOSTFORINJECTION',1);
define('NOSTYLECHECK',1);


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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
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
$pageid=GETPOST('pageid', 'int');
$action=GETPOST('action','alpha');

if (GETPOST('delete')) { $action='delete'; }
if (GETPOST('preview')) $action='preview';
if (GETPOST('create')) { $action='create'; }
if (GETPOST('editmedia')) { $action='editmedia'; }
if (GETPOST('editcss')) { $action='editcss'; }
if (GETPOST('editmenu')) { $action='editmenu'; }
if (GETPOST('setashome')) { $action='setashome'; }
if (GETPOST('editmeta')) { $action='editmeta'; }
if (GETPOST('editcontent')) { $action='editcontent'; }

if (empty($action)) $action='preview';

$object=new Website($db);
$objectpage=new WebsitePage($db);

$object->fetchAll();    // Init $object->records

// If website not defined, we take first found
if (empty($website))
{
    foreach($object->records as $key => $valwebsite)
    {
        $website=$valwebsite->ref;
        break;
    }
}
if ($website)
{
    $res = $object->fetch(0, $website);
}

if ($pageid < 0) $pageid = 0;
if ($pageid > 0 && $action != 'add')
{
    $res = $objectpage->fetch($pageid);
}

global $dolibarr_main_data_root;
$pathofwebsite=$dolibarr_main_data_root.'/websites/'.$website;
$filecss=$pathofwebsite.'/styles.css.php';
$filetpl=$pathofwebsite.'/page'.$pageid.'.tpl.php';
$fileindex=$pathofwebsite.'/index.php';

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current



/*
 * Actions
 */

if (GETPOST('refreshsite')) $pageid=0;      // If we change the site, we reset the pageid.

// Add page
if ($action == 'add')
{
    $db->begin();

    $objectpage->fk_website = $object->id;

    $objectpage->title = GETPOST('WEBSITE_TITLE');
    $objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
    $objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
    $objectpage->keywords = GETPOST('WEBSITE_KEYWORD');

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
	    setEventMessages($langs->trans("PageAdded", $objectpage->pageurl), null, 'mesgs');
	    $action='';
	}
	else
	{
		$db->rollback();
	}
	
	$action = 'preview';
	$id = $objectpage->id;
}

// Update page
if ($action == 'delete')
{
    $db->begin();

    $res = $object->fetch(0, $website);

    $res = $objectpage->fetch($pageid, $object->fk_website);

    if ($res > 0)
    {
        $res = $objectpage->delete($user);
        if (! $res > 0)
        {
            $error++;
            setEventMessages($objectpage->error, $objectpage->errors, 'errors');
        }

        if (! $error)
        {
            $db->commit();
            setEventMessages($langs->trans("PageDeleted", $objectpage->pageurl, $website), null, 'mesgs');
            
            header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website);
            exit;
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

// Update css
if ($action == 'updatecss')
{
    //$db->begin();

    $res = $object->fetch(0, $website);

    /*
    $res = $object->update($user);
    if ($res > 0)
    {
        $db->commit();
        $action='';
    }
    else
    {
        $error++;
       $db->rollback();
    }*/
    
    $csscontent = '<!-- START DOLIBARR-WEBSITE-ADDED-HEADER -->'."\n";
    $csscontent.= '<?php '."\n";
    $csscontent.= "header('Content-type: text/css');\n";
    $csscontent.= "?>"."\n";
    $csscontent.= '<!-- END -->'."\n";
    $csscontent.= GETPOST('WEBSITE_CSS_INLINE');
    
    dol_syslog("Save file css into ".$filecss);
    
    dol_mkdir($pathofwebsite);
    $result = file_put_contents($filecss, $csscontent);
    if (! empty($conf->global->MAIN_UMASK))
        @chmod($filecss, octdec($conf->global->MAIN_UMASK));
        
    if (! $result)
    {
        $error++;
        setEventMessages('Failed to write file '.$filecss, null, 'errors');
    }
        
    if (! $error)
    {
        setEventMessages($langs->trans("Saved"), null, 'mesgs');
    }
    
    $action='preview';
}

// Update page
if ($action == 'setashome')
{
    $db->begin();
    $object->fetch(0, $website);

    $object->fk_default_home = $pageid;
    $res = $object->update($user);
    if (! $res > 0)
    {
        $error++;
        setEventMessages($objectpage->error, $objectpage->errors, 'errors');
    }
    
    if (! $error)
    {
        $db->commit();
        
        // Generate the index.php page to be the home page
        //-------------------------------------------------
        dol_mkdir($pathofwebsite);
        dol_delete_file($fileindex);

        $indexcontent = '<?php'."\n";
        $indexcontent.= '// File generated to wrap the home page'."\n";
        $indexcontent.= "include_once './".basename($filetpl)."'\n";
        $indexcontent.= '?>'."\n";
        $result = file_put_contents($fileindex, $indexcontent);
        if (! empty($conf->global->MAIN_UMASK))
            @chmod($fileindex, octdec($conf->global->MAIN_UMASK));
        
        if ($result) setEventMessages($langs->trans("Saved"), null, 'mesgs');
        else setEventMessages('Failed to write file '.$fileindex, null, 'errors');
        
        $action='preview';
    }
    else
    {
        $db->rollback();
    }
}

// Update page (meta)
if ($action == 'updatemeta')
{
    $db->begin();
    $object->fetch(0, $website);

    $objectpage->fk_website = $object->id;

    $res = $objectpage->fetch($pageid, $object->fk_website);
    if ($res > 0)
    {
        $objectpage->old_object = clone $objectpage;
        
        $objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
        $objectpage->title = GETPOST('WEBSITE_TITLE');
        $objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
        $objectpage->keywords = GETPOST('WEBSITE_KEYWORDS');

        $res = $objectpage->update($user);
        if (! $res > 0)
        {
            $error++;
            setEventMessages($objectpage->error, $objectpage->errors, 'errors');
        }

        if (! $error)
        {
            $db->commit();

            $filemaster=$pathofwebsite.'/master.inc.php';
            $fileoldalias=$pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
            $filealias=$pathofwebsite.'/'.$objectpage->pageurl.'.php';

            dol_mkdir($pathofwebsite);

            
            // Now generate the master.inc.php page
            dol_syslog("We regenerate the master file");
            dol_delete_file($filemaster);
            
            $mastercontent = '<?php'."\n";
            $mastercontent.= '// File generated to link to the master file'."\n";
            $mastercontent.= "if (! defined('USEDOLIBARRSERVER')) require '".DOL_DOCUMENT_ROOT."/master.inc.php';\n";
            $mastercontent.= '?>'."\n";
            $result = file_put_contents($filemaster, $mastercontent);
            if (! empty($conf->global->MAIN_UMASK))
                @chmod($filemaster, octdec($conf->global->MAIN_UMASK));
            
            if (! $result) setEventMessages('Failed to write file '.$filemaster, null, 'errors');
            
            
            // Now generate the alias.php page
            if (! empty($fileoldalias))
            {
                dol_syslog("We regenerate alias page new name=".$filealias.", old name=".$fileoldalias);
                dol_delete_file($fileoldalias);
            }
            
            $aliascontent = '<?php'."\n";
            $aliascontent.= '// File generated to wrap the alias page'."\n";
            $aliascontent.= "include_once './page".$objectpage->id.".tpl.php';\n";
            $aliascontent.= '?>'."\n";
            $result = file_put_contents($filealias, $aliascontent);
            if (! empty($conf->global->MAIN_UMASK))
                @chmod($filealias, octdec($conf->global->MAIN_UMASK));
            
            if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');


            // Now create the .tpl file (duplicate code with actions updatecontent but we need this to save new header)
            dol_syslog("We regenerate the tpl page filetpl=".$filetpl);
            
            dol_delete_file($filetpl);
            
            $tplcontent ='';
            $tplcontent.= '<?php require "./master.inc.php"; ?>'."\n";
            $tplcontent.= '<html>'."\n";
            $tplcontent.= '<header>'."\n";
            $tplcontent.= '<meta http-equiv="content-type" content="text/html; charset=utf-8" />'."\n";
            $tplcontent.= '<meta name="robots" content="index, follow" />'."\n";
            $tplcontent.= '<meta name="viewport" content="width=device-width, initial-scale=0.8">'."\n";
            $tplcontent.= '<meta name="keywords" content="'.join(', ', explode(',',$objectpage->keywords)).'" />'."\n";
            $tplcontent.= '<meta name="title" content="'.dol_escape_htmltag($objectpage->title).'" />'."\n";
            $tplcontent.= '<meta name="description" content="'.dol_escape_htmltag($objectpage->description).'" />'."\n";
            $tplcontent.= '<meta name="generator" content="'.DOL_APPLICATION_TITLE.'" />'."\n";
            $tplcontent.= '<link rel="stylesheet" href="styles.css.php?website='.$website.'" type="text/css" />'."\n";
            $tplcontent.= '<title>'.dol_escape_htmltag($objectpage->title).'</title>'."\n";
            $tplcontent.= '</header>'."\n";
            
            $tplcontent.= '<body>'."\n";
            $tplcontent.= $objectpage->content."\n";
            $tplcontent.= '</body>'."\n";
            //var_dump($filetpl);exit;
            $result = file_put_contents($filetpl, $tplcontent);
            if (! empty($conf->global->MAIN_UMASK))
                @chmod($filetpl, octdec($conf->global->MAIN_UMASK));
                 
            if ($result)
            {
                setEventMessages($langs->trans("Saved"), null, 'mesgs');
                //header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website.'&pageid='.$pageid);
                //exit;
            }
            else setEventMessages('Failed to write file '.$filetpl, null, 'errors');
            
            $action='preview';
        }
        else
        {
            $db->rollback();
        }
    }
    else
    {
        dol_print_error($db, 'Page not found');
    }
}

// Update page
if ($action == 'updatecontent')
{
    $db->begin();
    $object->fetch(0, $website);

    $objectpage->fk_website = $object->id;

    $res = $objectpage->fetch($pageid, $object->fk_website);
    if ($res > 0)
    {
        $objectpage->content = GETPOST('PAGE_CONTENT');

        // Clean data. We remove all the head section.
        $objectpage->content = preg_replace('/<head.*<\/head>/s', '', $objectpage->content);
        /* $objectpage->content = preg_replace('/<base\s+href=[\'"][^\'"]+[\'"]\s/?>/s', '', $objectpage->content); */
        
        $res = $objectpage->update($user);
        if ($res < 0)
        {
            $error++;
            setEventMessages($objectpage->error, $objectpage->errors, 'errors');
        }

    	if (! $error)
    	{
    		$db->commit();
    	    
    		$filemaster=$pathofwebsite.'/master.inc.php';
    		//$fileoldalias=$pathofwebsite.'/'.$objectpage->old_object->pageurl.'.php';
    		$filealias=$pathofwebsite.'/'.$objectpage->pageurl.'.php';
    		
    	    dol_mkdir($pathofwebsite);
    		
    		
    		// Now generate the master.inc.php page
    		dol_syslog("We regenerate the master file");
    		dol_delete_file($filemaster);
    		
    		$mastercontent = '<?php'."\n";
    		$mastercontent.= '// File generated to link to the master file'."\n";
    		$mastercontent.= "if (! defined('USEDOLIBARRSERVER')) require '".DOL_DOCUMENT_ROOT."/master.inc.php';\n";
    		$mastercontent.= '?>'."\n";
    		$result = file_put_contents($filemaster, $mastercontent);
    		if (! empty($conf->global->MAIN_UMASK))
    		    @chmod($filemaster, octdec($conf->global->MAIN_UMASK));
    		
		    if (! $result) setEventMessages('Failed to write file '.$filemaster, null, 'errors');
		
		
		    // Now generate the alias.php page
            if (! empty($fileoldalias))
            {
    		    dol_syslog("We regenerate alias page new name=".$filealias.", old name=".$fileoldalias);
    		    dol_delete_file($fileoldalias);
            }
            
		    $aliascontent = '<?php'."\n";
		    $aliascontent.= '// File generated to wrap the alias page'."\n";
		    $aliascontent.= "include_once './page".$objectpage->id.".tpl.php';\n";
		    $aliascontent.= '?>'."\n";
		    $result = file_put_contents($filealias, $aliascontent);
		    if (! empty($conf->global->MAIN_UMASK))
		        @chmod($filealias, octdec($conf->global->MAIN_UMASK));
		
            if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');
		
    		        
    	    // Now create the .tpl file
    	    // TODO Keep a one time generate file or include a dynamicaly generated content ? 
    	    dol_delete_file($filetpl);

            $tplcontent ='';
            $tplcontent.= "<?php if (! defined('USEDOLIBARRSERVER')) require './master.inc.php'; ?>"."\n";
    	    $tplcontent.= '<html>'."\n";
    	    $tplcontent.= '<header>'."\n";
    	    $tplcontent.= '<meta http-equiv="content-type" content="text/html; charset=utf-8" />'."\n";
    	    $tplcontent.= '<meta name="robots" content="index, follow" />'."\n";
    	    $tplcontent.= '<meta name="viewport" content="width=device-width, initial-scale=0.8">'."\n";
    	    $tplcontent.= '<meta name="keywords" content="'.join(', ', explode(',',$objectpage->keywords)).'" />'."\n";
    	    $tplcontent.= '<meta name="title" content="'.dol_escape_htmltag($objectpage->title).'" />'."\n";
    	    $tplcontent.= '<meta name="description" content="'.dol_escape_htmltag($objectpage->description).'" />'."\n";
    	    $tplcontent.= '<meta name="generator" content="'.DOL_APPLICATION_TITLE.'" />'."\n";
    	    $tplcontent.= '<link rel="stylesheet" href="styles.css.php?website='.$website.'" type="text/css" />'."\n";
    	    $tplcontent.= '<title>'.dol_escape_htmltag($objectpage->title).'</title>'."\n";
    	    $tplcontent.= '</header>'."\n";
    	    	
    	    $tplcontent.= '<body>'."\n";
    	    $tplcontent.= $objectpage->content."\n";
    	    $tplcontent.= '</body>'."\n";
            //var_dump($filetpl);exit;	    
    	    $result = file_put_contents($filetpl, $tplcontent);
    	    if (! empty($conf->global->MAIN_UMASK))
    	        @chmod($filetpl, octdec($conf->global->MAIN_UMASK));
                 
    	    if ($result)
    	    {
    	        setEventMessages($langs->trans("Saved"), null, 'mesgs');
    	        header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website.'&pageid='.$pageid);
   	            exit;
    	    }
    	    else setEventMessages('Failed to write file '.$filetpl, null, 'errors');    	        
    	}
    	else
    	{
    		$db->rollback();
    	}
    }
    else
    {
        dol_print_error($db, 'Page not found');
    }
}



/*
 * View
 */

$form = new Form($db);

$help_url='';

llxHeader('', $langs->trans("WebsiteSetup"), $help_url);

print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST"><div>';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
if ($action == 'create')
{
    print '<input type="hidden" name="action" value="add">';
}
if ($action == 'editcss')
{
    print '<input type="hidden" name="action" value="updatecss">';
}
if ($action == 'editmenu')
{
    print '<input type="hidden" name="action" value="updatemenu">';
}
if ($action == 'setashome')
{
    print '<input type="hidden" name="action" value="updateashome">';
}
if ($action == 'editmeta')
{
    print '<input type="hidden" name="action" value="updatemeta">';
}
if ($action == 'editcontent')
{
    print '<input type="hidden" name="action" value="updatecontent">';
}
if ($action == 'edit')
{
    print '<input type="hidden" name="action" value="update">';
}


// Add a margin under toolbar ?
$style='';
if ($action != 'preview' && $action != 'editcontent') $style=' margin-bottom: 5px;';


print '<div class="centpercent websitebar">';

if (count($object->records) > 0)
{
    // ***** Part for web sites
    
    print '<div class="websiteselection">';
    print $langs->trans("Website").': ';
    print '</div>';

    // List of websites
    print '<div class="websiteselection">';
    $out='';
    $out.='<select name="website">';
    if (empty($object->records)) $out.='<option value="-1">&nbsp;</option>';
    // Loop on each sites
    $i=0;
    foreach($object->records as $key => $valwebsite)
    {
        if (empty($website)) $website=$valwebsite->ref;

        $out.='<option value="'.$valwebsite->ref.'"';
        if ($website == $valwebsite->ref) $out.=' selected';		// To preselect a value
        $out.='>';
        $out.=$valwebsite->ref;
        $out.='</option>';
        $i++;
    }
    $out.='</select>';
    print $out;
    print '<input type="submit" class="button" name="refreshsite" value="'.$langs->trans("Load").'">';

    if ($website)
    {
        $realurl=$urlwithroot.'/public/websites/index.php?website='.$website;
        $dataroot=DOL_DATA_ROOT.'/websites/'.$website;
        if (! empty($object->virtualhost)) $realurl=$object->virtualhost; 
    }
    
    if ($website && $action == 'preview')
    {
        $disabled='';
        if (empty($user->rights->websites->write)) $disabled=' disabled="disabled"';
    
        print ' &nbsp; ';
        
        //print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("MediaFiles")).'" name="editmedia">';
        print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditCss")).'" name="editcss">';
        print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditMenu")).'" name="editmenu">';
        print '<input type="submit"'.$disabled.' class="button" value="'.dol_escape_htmltag($langs->trans("AddPage")).'" name="create">';
    }
    
    print '</div>';

    // Button for websites
    print '<div class="websitetools">';

    if ($action == 'preview')
    {
        print '<div class="websiteinputurl">';
        print '<input type="text" id="previewsiteurl" class="minwidth200imp" name="previewsite" value="'.$realurl.'">';
        //print '<input type="submit" class="button" name="previewwebsite" target="tab'.$website.'" value="'.$langs->trans("ViewSiteInNewTab").'">';
        $htmltext=$langs->trans("SetHereVirtualHost", $dataroot);
        print $form->textwithpicto('', $htmltext);
        print '</div>';
        
        $urlext=$realurl;
        $urlint=DOL_URL_ROOT.'/public/websites/index.php?website='.$website;
        print '<a class="websitebuttonsitepreview" id="previewsiteext" href="'.$urlext.'" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByWebServer")).'">';
        print $form->textwithpicto('', $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $urlext), 1, 'preview_ext');
        print '</a>';
        
        print '<a class="websitebuttonsitepreview" id="previewsite" href="'.DOL_URL_ROOT.'/public/websites/index.php?website='.$website.'" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByDolibarr")).'">';
        print $form->textwithpicto('', $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $urlint), 1, 'preview');
        print '</a>';
    }

    if (in_array($action, array('editcss','editmenu','create')))
    {
        if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
        if (preg_match('/^create/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
        if (preg_match('/^edit/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
    }
    
    print '</div>';


    // ***** Part for pages
    
    if ($website)
    {
        print '</div>';

        $array=$objectpage->fetchAll($object->id);
        if (! is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
        $atleastonepage=(is_array($array) && count($array) > 0);
        
        print '<div class="centpercent websitebar"'.($style?' style="'.$style.'"':'').'">';
        print '<div class="websiteselection">';
        print $langs->trans("Page").': ';
        print '</div>';
        print '<div class="websiteselection">';
        
        if ($action != 'add')
        {
            $out='';
            $out.='<select name="pageid">';
            if ($atleastonepage)
            {
                if (empty($pageid) && $action != 'create')      // Page id is not defined, we try to take one
                {
                    $firstpageid=0;$homepageid=0;
                    foreach($array as $key => $valpage)
                    {
                        if (empty($firstpageid)) $firstpageid=$valpage->id;
                        if ($object->fk_default_home && $key == $object->fk_default_home) $homepageid=$valpage->id;
                    }
                    $pageid=$homepageid?$homepageid:$firstpageid;   // We choose home page and if not defined yet, we take first page
                }
    
                foreach($array as $key => $valpage)
                {
                    $out.='<option value="'.$key.'"';
                    if ($pageid > 0 && $pageid == $key) $out.=' selected';		// To preselect a value
                    $out.='>';
                    $out.=$valpage->title;
                    if ($object->fk_default_home && $key == $object->fk_default_home) $out.=' ('.$langs->trans("HomePage").')';
                    $out.='</option>';
                }
            }
            else $out.='<option value="-1">&nbsp;</option>';
            $out.='</select>';
            print $out;
        }
        else
        {
            print $langs->trans("New");
        }

        print '<input type="submit" class="button" name="refreshpage" value="'.$langs->trans("Load").'"'.($atleastonepage?'':' disabled="disabled"').'>';
        //print $form->selectarray('page', $array);
        
        if ($action == 'preview')
        {
            $disabled='';
            if (empty($user->rights->websites->write)) $disabled=' disabled="disabled"';
        
            if ($pageid > 0)
            {
                print ' &nbsp; ';
                
                if ($object->fk_default_home > 0 && $pageid == $object->fk_default_home) print '<input type="submit" class="button" disabled="disabled" value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
                else print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
                print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageMeta")).'" name="editmeta">';
                print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageContent")).'" name="editcontent">';
                //print '<a href="'.$_SERVER["PHP_SELF"].'?action=editmeta&website='.urlencode($website).'&pageid='.urlencode($pageid).'" class="button">'.dol_escape_htmltag($langs->trans("EditPageMeta")).'</a>';
                //print '<a href="'.$_SERVER["PHP_SELF"].'?action=editcontent&website='.urlencode($website).'&pageid='.urlencode($pageid).'" class="button">'.dol_escape_htmltag($langs->trans("EditPageContent")).'</a>';
                print '<input type="submit" class="buttonDelete" name="delete" value="'.$langs->trans("Delete").'"'.($atleastonepage?'':' disabled="disabled"').'>';
            }
        }
        
        print '</div>';
        print '<div class="websiteselection">';
        print '</div>';

        print '<div class="websitetools">';

        if ($website && $pageid > 0 && $action == 'preview')
        {
            $websitepage = new WebSitePage($db);
            $websitepage->fetch($pageid);
            
            $realpage=$urlwithroot.'/public/websites/index.php?website='.$website.'&page='.$pageid;
            $pagealias = $websitepage->pageurl;
            
            print '<div class="websiteinputurl">';
            print '<input type="text" id="previewpageurl" class="minwidth200imp" name="previewsite" value="'.$pagealias.'" disabled="disabled">';
            //print '<input type="submit" class="button" name="previewwebsite" target="tab'.$website.'" value="'.$langs->trans("ViewSiteInNewTab").'">';
            $htmltext=$langs->trans("WEBSITE_PAGENAME", $pagealias);
            print $form->textwithpicto('', $htmltext);
            print '</div>';
            
            $urlext=$realurl.'/'.$pagealias.'.php';
            print '<a class="websitebuttonsitepreview" id="previewpageext" href="'.$urlext.'" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByWebServer")).'">';
            print $form->textwithpicto('', $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $urlext), 1, 'preview_ext');
            print '</a>';
            
            print '<a class="websitebuttonsitepreview" id="previewpage" href="'.$realpage.'&nocache='.dol_now().'" class="button" target="tab'.$website.'">';
            print $form->textwithpicto('', $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage), 1, 'preview'); 
            print '</a>';       // View page in new Tab
            //print '<input type="submit" class="button" name="previewpage" target="tab'.$website.'"value="'.$langs->trans("ViewPageInNewTab").'">';
            
            // TODO Add js to save alias like we save virtual host name and use dynamic virtual host for url of id=previewpageext
        }
        if (! in_array($action, array('editcss','editmenu','create')))
        {
            if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
            if (preg_match('/^create/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
            if (preg_match('/^edit/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
        }
        
        print '</div>';

        if ($action == 'preview')
        {
            // Adding jquery code to change on the fly url of preview ext
            if (! empty($conf->use_javascript_ajax))
            {
                print '<script type="text/javascript" language="javascript">
                    jQuery(document).ready(function() {
                    	jQuery("#previewsiteext,#previewpageext").click(function() {
                            newurl=jQuery("#previewsiteurl").val();
                            newpage=jQuery("#previewsiteurl").val() + "/" + jQuery("#previewpageurl").val() + ".php";
                            console.log("Open url "+newurl);
                            /* Save url */
                            jQuery.ajax({
                                method: "POST",
                                url: "'.DOL_URL_ROOT.'/core/ajax/saveinplace.php",
                                data: {
                                    field: \'editval_virtualhost\',
                                    element: \'websites\',
                                    table_element: \'website\',
                                    fk_element: '.$object->id.',
                                    value: newurl,
                                },
                                context: document.body
                            });
                    
                            jQuery("#previewsiteext").attr("href",newurl);
                            jQuery("#previewpageext").attr("href",newpage);
                        });
                    });
                    </script>';
            }
        }
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

if ($action == 'editcss')
{
    print '<div class="fiche">';

    print '<br>';

    $csscontent = @file_get_contents($filecss);
    // Clean php css file to get only css part
    $csscontent = preg_replace('/<!-- START DOLIBARR.*END -->/s', '', $csscontent); 
    
    dol_fiche_head();

    print '<!-- Edit CSS -->'."\n";
    print '<table class="border" width="100%">';

    print '<tr><td class="titlefieldcreate">';
    print $langs->trans('WebSite');
    print '</td><td>';
    print $website;
    print '</td></tr>';

    print '<tr><td class="tdtop">';
    print $langs->trans('WEBSITE_CSS_INLINE');
    print '</td><td>';
    print '<textarea class="flat centpercent" rows="32" name="WEBSITE_CSS_INLINE">';
    print $csscontent;
    print '</textarea>';
    print '</td></tr>';

    /*print '<tr><td>';
    print $langs->trans('WEBSITE_CSS_URL');
    print '</td><td>';
    print '<input type="text" class="flat" size="96" name="WEBSITE_CSS_URL" value="'.dol_escape_htmltag($obj->WEBSITE_CSS_URL).'">';
    print '</td></tr>';*/

    print '</table>';

    dol_fiche_end();

    print '</div>';

    print '<br>';
}

if ($action == 'editmeta' || $action == 'create')
{
    print '<div class="fiche">';
 
    print '<br>';
    
    dol_fiche_head();
    
    print '<!-- Edit Meta -->'."\n";
    print '<table class="border" width="100%">';
    
    if ($action != 'create')
    {
        print '<tr><td>';
        print $langs->trans('WEBSITE_PAGEURL');
        print '</td><td>';
        print '/public/websites/index.php?website='.urlencode($website).'&pageid='.urlencode($pageid);
        print '</td></tr>';
        $pageurl=dol_escape_htmltag($objectpage->pageurl);
        $pagetitle=dol_escape_htmltag($objectpage->title);
        $pagedescription=dol_escape_htmltag($objectpage->description);
        $pagekeywords=dol_escape_htmltag($objectpage->keywords);
    }
    if (GETPOST('WEBSITE_PAGENAME'))    $pageurl=GETPOST('WEBSITE_PAGENAME');
    if (GETPOST('WEBSITE_TITLE'))       $pagetitle=GETPOST('WEBSITE_TITLE');
    if (GETPOST('WEBSITE_DESCRIPTION')) $pagedescription=GETPOST('WEBSITE_DESCRIPTION');
    if (GETPOST('WEBSITE_KEYWORDS'))    $pagekeywords=GETPOST('WEBSITE_KEYWORDS');

    print '<tr><td class="titlefieldcreate">';
    print $langs->trans('WEBSITE_PAGENAME');
    print '</td><td>';
    print '<input type="text" class="flat" size="96" name="WEBSITE_PAGENAME" value="'.$pageurl.'">';
    print '</td></tr>';
    
    print '<tr><td>';
    print $langs->trans('WEBSITE_TITLE');
    print '</td><td>';
    print '<input type="text" class="flat" size="96" name="WEBSITE_TITLE" value="'.$pagetitle.'">';
    print '</td></tr>';

    print '<tr><td>';
    print $langs->trans('WEBSITE_DESCRIPTION');
    print '</td><td>';
    print '<input type="text" class="flat" size="96" name="WEBSITE_DESCRIPTION" value="'.$pagedescription.'">';
    print '</td></tr>';

    print '<tr><td>';
    print $langs->trans('WEBSITE_KEYWORDS');
    print '</td><td>';
    print '<input type="text" class="flat" size="128" name="WEBSITE_KEYWORDS" value="'.$pagekeywords.'">';
    print '</td></tr>';

    print '</table>';

    dol_fiche_end();

    print '</div>';

    print '<br>';
}

if ($action == 'editmedia')
{
    print '<!-- Edit Media -->'."\n";
    print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';
}

if ($action == 'editmenu')
{
    print '<!-- Edit Menu -->'."\n";
    print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';
}

if ($action == 'editcontent')
{
    /*
     * Editing global variables not related to a specific theme
     */
    
    $csscontent = @file_get_contents($filecss);
    
    $contentforedit = '';
    /*$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
    $contentforedit.=$csscontent;
    $contentforedit.='</style>'."\n";*/
    $contentforedit .= $objectpage->content;
    
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('PAGE_CONTENT',$contentforedit,'',500,'Full','',true,true,true,ROWS_5,'90%');
    $doleditor->Create(0, '', false);
}

print "</div>\n</form>\n";



if ($action == 'preview')
{
    if ($pageid > 0)
    {
        $objectpage->fetch($pageid);

        print "\n".'<!-- Page content '.$filetpl.' : Div with (CSS + Page content from database) -->'."\n";

        
        $csscontent = @file_get_contents($filecss);
        
        $out='';
        
        $out.='<div id="websitecontent" class="websitecontent">'."\n";
        
        $out.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
        $out.=$csscontent;
        $out.='</style>'."\n";
        
        $out.=$objectpage->content."\n";
        
        $out.='</div>';
        
        print $out;
        
        /*file_put_contents($filetpl, $out);
        if (! empty($conf->global->MAIN_UMASK))
            @chmod($filetpl, octdec($conf->global->MAIN_UMASK));

        // Output file on browser
        dol_syslog("index.php include $filetpl $filename content-type=$type");
        $original_file_osencoded=dol_osencode($filetpl);	// New file name encoded in OS encoding charset
        
        // This test if file exists should be useless. We keep it to find bug more easily
        if (! file_exists($original_file_osencoded))
        {
            dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
            exit;
        }
        
        //include_once $original_file_osencoded;
        */
        
        /*print '<iframe class="websiteiframenoborder centpercent" src="'.DOL_URL_ROOT.'/public/websites/index.php?website='.$website.'&pageid='.$pageid.'"/>';
        print '</iframe>';*/
    }
    else
    {
        print '<br><br><div class="center">'.$langs->trans("PreviewOfSiteNotYetAvailable", $website).'</center><br><br><br>';
        print '<div class="center"><div class="logo_setup"></div></div>';
    }
}



llxFooter();

$db->close();
