<?php
/* Copyright (C) 2016-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * @param	string	$morequerystring	Query string to add to the link "print" to get same parameters (set this only if autodetect fails)
 * @return	void
 */
function llxHeader($head='', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='', $morequerystring='')
{
    global $conf;

    // html header
    top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);

    print '<body id="mainbody">';

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
    print '<!-- Begin div class="fiche" -->'."\n".'<div class="fichebutwithotherclass">'."\n";
}


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formwebsite.class.php';
require_once DOL_DOCUMENT_ROOT.'/websites/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/websites/class/websitepage.class.php';

$langs->load("admin");
$langs->load("other");
$langs->load("website");

if (! $user->admin) accessforbidden();

if (! ((GETPOST('testmenuhider','int') || ! empty($conf->global->MAIN_TESTMENUHIDER)) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)))
{
    $conf->dol_hide_leftmenu = 1;   // Force hide of left menu.
}

$error=0;
$website=GETPOST('website', 'alpha');
$page=GETPOST('page', 'alpha');
$pageid=GETPOST('pageid', 'int');
$pageref=GETPOST('pageref', 'aZ09');
$action=GETPOST('action','alpha');

if (GETPOST('delete')) { $action='delete'; }
if (GETPOST('preview')) $action='preview';
if (GETPOST('create')) { $action='create'; }
if (GETPOST('editmedias')) { $action='editmedias'; }
if (GETPOST('editcss')) { $action='editcss'; }
if (GETPOST('editmenu')) { $action='editmenu'; }
if (GETPOST('setashome')) { $action='setashome'; }
if (GETPOST('editmeta')) { $action='editmeta'; }
if (GETPOST('editsource')) { $action='editsource'; }
if (GETPOST('editcontent')) { $action='editcontent'; }
if (GETPOST('createfromclone')) { $action='createfromclone'; }
if (GETPOST('createpagefromclone')) { $action='createpagefromclone'; }

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
if (($pageid > 0 || $pageref) && $action != 'add')
{
	$res = $objectpage->fetch($pageid, ($object->id > 0 ? $object->id : null), $pageref);
    $pageid = $objectpage->id;
}

global $dolibarr_main_data_root;
$pathofwebsite=$dolibarr_main_data_root.'/websites/'.$website;
$filehtmlheader=$pathofwebsite.'/htmlheader.html';
$filecss=$pathofwebsite.'/styles.css.php';
$filerobot=$pathofwebsite.'/robots.txt';
$filehtaccess=$pathofwebsite.'/.htaccess';
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
if (GETPOST('refreshpage') && ! in_array($action, array('updatecss'))) $action='preview';

// Add page
if ($action == 'add')
{
    $db->begin();

    $objectpage->fk_website = $object->id;
    if (GETPOST('fetchexternalurl','alpha'))
    {
    	$urltograb=GETPOST('externalurl','alpha');
    }

    if ($urltograb)
    {
    	include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

    	$tmp = getURLContent($urltograb);
    	if ($tmp['curl_error_no'])
    	{
    		$error++;
    		setEventMessages($tmp['curl_error_msg'], null, 'errors');
    		$action='create';
    	}
    	else
    	{
    		preg_match('/<head>(.*)<\/head>/is', $tmp['content'], $reg);
    		$head = $reg[1];

    		$urltograbwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\/?/i', '', $urltograbwithoutdomain);
   			$objectpage->pageurl = basename($urltograbwithoutdomain);
   			if (empty($objectpage->pageurl)) $objectpage->pageurl='home';

    		if (preg_match('/<title>(.*)<\/title>/ims', $head, $regtmp))
    		{
    			$objectpage->title = $regtmp[1];
    		}
    		if (preg_match('/<meta name="description"[^"]+content="([^"]+)"/ims', $head, $regtmp))
    		{
    			$objectpage->description = $regtmp[1];
    		}
    		if (preg_match('/<meta name="keywords"[^"]+content="([^"]+)"/ims', $head, $regtmp))
    		{
    			$objectpage->keywords = $regtmp[1];
    		}
    		if (preg_match('/<html\s+lang="([^"]+)"/ims', $tmp['content'], $regtmp))
    		{
    			$tmplang=explode('-', $regtmp[1]);
    			$objectpage->lang = $tmplang[0].($tmplang[1] ? '_'.strtoupper($tmplang[1]) : '');
    		}

    		$objectpage->content = $tmp['content'];
    		$objectpage->content = preg_replace('/^.*<body[^>]*>/ims', '', $objectpage->content);
    		$objectpage->content = preg_replace('/<\/body[^>]*>.*$/ims', '', $objectpage->content);

    		$objectpage->grabbed_from = $urltograb;
    	}
    }
    else
    {
	    $objectpage->title = GETPOST('WEBSITE_TITLE');
	    $objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
	    $objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
	    $objectpage->keywords = GETPOST('WEBSITE_KEYWORDS');
	    $objectpage->lang = GETPOST('WEBSITE_LANG');
    }

    if (! $error)
    {
	    if (empty($objectpage->pageurl))
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_PAGENAME")), null, 'errors');
	        $error++;
	        $action='create';
	    }
	    else if (! preg_match('/^[a-z0-9\-\_]+$/i', $objectpage->pageurl))
	    {
	    	setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
	    	$error++;
	    	$action='create';
	    }
	    if (empty($objectpage->title))
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_TITLE")), null, 'errors');
	        $error++;
	        $action='create';
	    }
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

	if (! $error)
	{
	   $action = 'preview';
	   $pageid = $objectpage->id;
	}
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
	if (GETPOST('refreshsite') || GETPOST('refreshpage'))		// If we tried to reload another site/page, we stay on editcss mode.
	{
		$action='editcss';
	}
	else
	{
	    $res = $object->fetch(0, $website);

	    // Html header file
	    $htmlheadercontent ='';

	    /* We disable php code since htmlheader is never executed as an include but only read by fgets_content.
	    $htmlheadercontent.= "<?php // BEGIN PHP\n";
	    $htmlheadercontent.= '$websitekey=basename(dirname(__FILE__));'."\n";
	    $htmlheadercontent.= "if (! defined('USEDOLIBARRSERVER')) { require_once './master.inc.php'; } // Not already loaded"."\n";
	    $htmlheadercontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
	    $htmlheadercontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
	    $htmlheadercontent.= "ob_start();\n";
	    // $htmlheadercontent.= "header('Content-type: text/html');\n";		// Not required. htmlheader.html is never call as a standalone page
	    $htmlheadercontent.= "// END PHP ?>\n";*/

	    $htmlheadercontent.= preg_replace(array('/<html>\n*/ims','/<\/html>\n*/ims'),array('',''),GETPOST('WEBSITE_HTML_HEADER'));

	    /*$htmlheadercontent.= "\n".'<?php // BEGIN PHP'."\n";
	    $htmlheadercontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
	    $htmlheadercontent.= "// END PHP ?>"."\n";*/

	    $htmlheadercontent = trim($htmlheadercontent)."\n";

	    dol_syslog("Save html header into ".$filehtmlheader);

	    dol_mkdir($pathofwebsite);
	    $result = file_put_contents($filehtmlheader, $htmlheadercontent);
	    if (! empty($conf->global->MAIN_UMASK))
	        @chmod($filehtmlheader, octdec($conf->global->MAIN_UMASK));

	    if (! $result)
	    {
	        $error++;
	        setEventMessages('Failed to write file '.$filehtmlheader, null, 'errors');
	    }

	    // Css file
	    $csscontent ='';

	    $csscontent.= "<?php // BEGIN PHP\n";
	    $csscontent.= '$websitekey=basename(dirname(__FILE__));'."\n";
	    $csscontent.= "if (! defined('USEDOLIBARRSERVER')) { require_once dirname(__FILE__).'/master.inc.php'; } // Not already loaded"."\n";	// For the css, we need to set path of master using the dirname of css file.
	    $csscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
	    $csscontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
	    $csscontent.= "ob_start();\n";
	    $csscontent.= "header('Content-type: text/css');\n";
	    $csscontent.= "// END PHP ?>\n";

	    $csscontent.= GETPOST('WEBSITE_CSS_INLINE');

	    $csscontent.= "\n".'<?php // BEGIN PHP'."\n";
	    $csscontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
	    $csscontent.= "// END PHP ?>"."\n";

	    dol_syslog("Save css content into ".$filecss);

	    dol_mkdir($pathofwebsite);
	    $result = file_put_contents($filecss, $csscontent);
	    if (! empty($conf->global->MAIN_UMASK))
	        @chmod($filecss, octdec($conf->global->MAIN_UMASK));

	    if (! $result)
	    {
	        $error++;
	        setEventMessages('Failed to write file '.$filecss, null, 'errors');
	    }


	    // Css file
	    $robotcontent ='';

	    /*$robotcontent.= "<?php // BEGIN PHP\n";
	    $robotcontent.= '$websitekey=basename(dirname(__FILE__));'."\n";
	    $robotcontent.= "if (! defined('USEDOLIBARRSERVER')) { require_once './master.inc.php'; } // Not already loaded"."\n";
	    $robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
	    $robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
	    $robotcontent.= "ob_start();\n";
	    $robotcontent.= "header('Content-type: text/css');\n";
	    $robotcontent.= "// END PHP ?>\n";*/

	    $robotcontent.= GETPOST('WEBSITE_ROBOT');

	    /*$robotcontent.= "\n".'<?php // BEGIN PHP'."\n";
	    $robotcontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
	    $robotcontent.= "// END PHP ?>"."\n";*/

	    dol_syslog("Save file robot into ".$filerobot);

	    dol_mkdir($pathofwebsite);
	    $result = file_put_contents($filerobot, $robotcontent);
	    if (! empty($conf->global->MAIN_UMASK))
	    	@chmod($filerobot, octdec($conf->global->MAIN_UMASK));

    	if (! $result)
    	{
    		$error++;
    		setEventMessages('Failed to write file '.$filerobot, null, 'errors');
    	}


    	// Css file
    	$htaccesscontent ='';

    	/*$robotcontent.= "<?php // BEGIN PHP\n";
    	 $robotcontent.= '$websitekey=basename(dirname(__FILE__));'."\n";
    	 $robotcontent.= "if (! defined('USEDOLIBARRSERVER')) { require_once './master.inc.php'; } // Not already loaded"."\n";
    	 $robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
    	 $robotcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
    	 $robotcontent.= "ob_start();\n";
    	 $robotcontent.= "header('Content-type: text/css');\n";
    	 $robotcontent.= "// END PHP ?>\n";*/

    	$htaccesscontent.= GETPOST('WEBSITE_HTACCESS');

    	/*$robotcontent.= "\n".'<?php // BEGIN PHP'."\n";
    	 $robotcontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
    	 $robotcontent.= "// END PHP ?>"."\n";*/

    	dol_syslog("Save file htaccess into ".$filehtaccess);

    	dol_mkdir($pathofwebsite);
    	$result = file_put_contents($filehtaccess, $htaccesscontent);
    	if (! empty($conf->global->MAIN_UMASK))
    		@chmod($filehtaccess, octdec($conf->global->MAIN_UMASK));

   		if (! $result)
   		{
   			$error++;
   			setEventMessages('Failed to write file '.$filehtaccess, null, 'errors');
   		}

		// Message if no error
	    if (! $error)
	    {
	        setEventMessages($langs->trans("Saved"), null, 'mesgs');
	    }

	    $action='preview';
	}
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
        setEventMessages($object->error, $object->errors, 'errors');
    }

    if (! $error)
    {
        $db->commit();

        // Generate the index.php page to be the home page
        //-------------------------------------------------
        dol_mkdir($pathofwebsite);
        dol_delete_file($fileindex);

        $indexcontent = '<?php'."\n";
        $indexcontent.= '// File generated to provide a shortcut to the Home Page - DO NOT MODIFY - It is just an include.'."\n";
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

	// Check parameters
    if (! preg_match('/^[a-z0-9\-\_]+$/i', $objectpage->pageurl))
    {
    	$error++;
    	setEventMessages($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities('WEBSITE_PAGENAME')), null, 'errors');
    	$action='editmeta';
    }

    $res = $objectpage->fetch($pageid, $object->fk_website);
    if ($res <= 0)
    {
    	$error++;
        dol_print_error($db, 'Page not found');
    }

    if (! $error)
    {
        $objectpage->old_object = clone $objectpage;

        $objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
        $objectpage->title = GETPOST('WEBSITE_TITLE');
        $objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
        $objectpage->keywords = GETPOST('WEBSITE_KEYWORDS');
        $objectpage->lang = GETPOST('WEBSITE_LANG');

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
            dol_syslog("We regenerate the master file (because we update meta)");
            dol_delete_file($filemaster);

            $mastercontent = '<?php'."\n";
            $mastercontent.= '// File generated to link to the master file - DO NOT MODIFY - It is just an include'."\n";
            $mastercontent.= "if (! defined('USEDOLIBARRSERVER')) require_once '".DOL_DOCUMENT_ROOT."/master.inc.php';\n";
            //$mastercontent.= "include_once DOL_DOCUMENT_ROOT.'/websites/class/website.class.php';"."\n";
            //$mastercontent.= '$website = new WebSite($db)'."\n";
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

            // Save page alias
            $result=dolSavePageAlias($filealias, $object, $objectpage);
            if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');

			// Save page of content
            $result=dolSavePageContent($filetpl, $object, $objectpage);
            if ($result)
            {
                setEventMessages($langs->trans("Saved"), null, 'mesgs');
                //header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website.'&pageid='.$pageid);
                //exit;
            }
            else
            {
            	setEventMessages('Failed to write file '.$filetpl, null, 'errors');
        	    //header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website.'&pageid='.$pageid);
       	        //exit;
            }

            $action='preview';
        }
        else
        {
            $db->rollback();
        }
    }
}

// Update page
if (($action == 'updatesource' || $action == 'updatecontent' || $action == 'confirm_createfromclone' || $action == 'confirm_createpagefromclone')
	|| ($action == 'preview' && (GETPOST('refreshsite') || GETPOST('refreshpage') || GETPOST('preview'))))
{
    $object->fetch(0, $website);

	if ($action == 'confirm_createfromclone')
	{
		$objectnew = new Website($db);
		$result = $objectnew->createFromClone($user, GETPOST('id','int'), GETPOST('siteref','aZ09'), (GETPOST('newlang','aZ09')?GETPOST('newlang','aZ09'):''));
		if ($result < 0)
		{
			$error++;
			setEventMessages($objectnew->error, $objectnew->errors, 'errors');
			$action='preview';
		}
		else
		{
			$object = $objectnew;
			$id = $object->id;
			$pageid = $object->fk_default_home;
		}
	}

	if ($action == 'confirm_createpagefromclone')
	{
		$istranslation=(GETPOST('is_a_translation','aZ09')=='on'?1:0);
		if ($istranslation)
		{
			if (GETPOST('newlang','aZ09') == $objectpage->lang)
			{
				$error++;
				setEventMessages($langs->trans("LanguageMustNotBeSameThanClonedPage"), null, 'errors');
				$action='preview';
			}
		}

		if (! $error)
		{
	    	$objectpage = new WebsitePage($db);
			$result = $objectpage->createFromClone($user, $pageid, GETPOST('pageurl','aZ09'), (GETPOST('newlang','aZ09')?GETPOST('newlang','aZ09'):''), $istranslation, GETPOST('newwebsite','int'));
			if ($result < 0)
			{
				$error++;
				setEventMessages($objectpage->error, $objectpage->errors, 'errors');
				$action='createpagefromclone';
			}
		}
    }

    $res = 0;

    if (! $error)
    {
	    // Check symlink to medias and restore it if ko
	    $pathtomedias=DOL_DATA_ROOT.'/medias';
	    $pathtomediasinwebsite=$pathofwebsite.'/medias';
	    if (! is_link(dol_osencode($pathtomediasinwebsite)))
	    {
	        dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
	        dol_mkdir(dirname($pathtomediasinwebsite));     // To be sure dir for website exists
	        $result = symlink($pathtomedias, $pathtomediasinwebsite);
	    }

	    /*if (GETPOST('savevirtualhost') && $object->virtualhost != GETPOST('previewsite'))
	    {
	        $object->virtualhost = GETPOST('previewsite', 'alpha');
	        $object->update($user);
	    }*/

	    $objectpage->fk_website = $object->id;

	    if ($pageid > 0)
	    {
	        $res = $objectpage->fetch($pageid);
	    }
	    else
	    {
	        $res=0;
	        if ($object->fk_default_home > 0)
	        {
	            $res = $objectpage->fetch($object->fk_default_home);
	        }
	        if (! ($res > 0))
	        {
	            $res = $objectpage->fetch(0, $object->id);
	        }
	    }
    }

    if (! $error && $res > 0)
    {
        if ($action == 'updatesource' || $action == 'updatecontent')
        {
            $db->begin();

            $objectpage->content = GETPOST('PAGE_CONTENT');

            // Clean data. We remove all the head section.
            $objectpage->content = preg_replace('/<head>.*<\/head>/s', '', $objectpage->content);
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
        		$mastercontent.= "if (! defined('USEDOLIBARRSERVER')) require_once '".DOL_DOCUMENT_ROOT."/master.inc.php';\n";
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

                // Save page alias
                $result=dolSavePageAlias($filealias, $object, $objectpage);
                if (! $result) setEventMessages('Failed to write file '.$filealias, null, 'errors');

				// Save page content
        	    $result=dolSavePageContent($filetpl, $object, $objectpage);
        	    if ($result)
        	    {
        	        setEventMessages($langs->trans("Saved"), null, 'mesgs');
        	        header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website.'&pageid='.$pageid);
       	            exit;
        	    }
        	    else
        	    {
        	        setEventMessages('Failed to write file '.$filetpl, null, 'errors');
        	        header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website.'&pageid='.$pageid);
       	            exit;
        	    }
        	}
        	else
        	{
        		$db->rollback();
        	}
        }
        else
        {
            header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website.'&pageid='.$pageid);
            exit;
        }
    }
    else
    {
        if (! $error) setEventMessages($langs->trans("NoPageYet"), null, 'warnings');
    }
}



/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);
$formwebsite = new FormWebsite($db);

$help_url='';

llxHeader('', $langs->trans("WebsiteSetup"), $help_url, '', 0, 0,
	array(
		'/includes/ace/ace.js',
		'/includes/ace/ext-statusbar.js',
		'/includes/ace/ext-language_tools.js',
		//'/includes/ace/ext-chromevox.js'
	), array());

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
if ($action == 'editsource')
{
    print '<input type="hidden" name="action" value="updatesource">';
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
if ($action != 'preview' && $action != 'editcontent' && $action != 'editsource') $style=' margin-bottom: 5px;';

//var_dump($objectpage);exit;
print '<div class="centpercent websitebar">';

if (count($object->records) > 0)
{
    // ***** Part for web sites

    print '<div class="websiteselection hideonsmartphoneimp">';
    print $langs->trans("Website").': ';
    print '</div>';

    // List of websites
    print '<div class="websiteselection">';
    $out='';
    $out.='<select name="website" class="minwidth100" id="website">';
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
    $out.=ajax_combobox('website');
    print $out;
    print '<input type="submit" class="button" name="refreshsite" value="'.$langs->trans("Load").'">';

    if ($website)
    {
        $virtualurl='';
        $dataroot=DOL_DATA_ROOT.'/websites/'.$website;
        if (! empty($object->virtualhost)) $virtualurl=$object->virtualhost;
    }

    if ($website && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone'))
    {
        $disabled='';
        if (empty($user->rights->websites->write)) $disabled=' disabled="disabled"';

        print ' &nbsp; ';

        print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditCss")).'" name="editcss">';
        print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditMenu")).'" name="editmenu">';
        print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("CloneSite")).'" name="createfromclone">';

		print ' &nbsp; ';

        print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("MediaFiles")).'" name="editmedias">';
    }

    print '</div>';

    // Button for websites
    print '<div class="websitetools">';

    if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
    {
        print '<div class="websiteinputurl" id="websiteinputurl">';
        print '<input type="text" id="previewsiteurl" class="minwidth200imp" name="previewsite" placeholder="'.$langs->trans("http://myvirtualhost").'" value="'.$virtualurl.'">';
        //print '<input type="submit" class="button" name="previewwebsite" target="tab'.$website.'" value="'.$langs->trans("ViewSiteInNewTab").'">';
        $htmltext=$langs->trans("SetHereVirtualHost", $dataroot);
        print $form->textwithpicto('', $htmltext, 1, 'help', '', 0, 2, 'helpvirtualhost');
        print '</div>';

        $urlext=$virtualurl;
        $urlint=$urlwithroot.'/public/websites/index.php?website='.$website;
        print '<a class="websitebuttonsitepreview'.($urlext?'':' websitebuttonsitepreviewdisabled cursornotallowed').'" id="previewsiteext" href="'.$urlext.'" target="tab'.$website.'ext" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $dataroot, $urlext)).'">';
        print $form->textwithpicto('', $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $dataroot, $urlext?$urlext:'<span class="error">'.$langs->trans("VirtualHostUrlNotDefined").'</span>'), 1, 'preview_ext');
        print '</a>';

        print '<a class="websitebuttonsitepreview" id="previewsite" href="'.$urlwithroot.'/public/websites/index.php?website='.$website.'" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $urlint)).'">';
        print $form->textwithpicto('', $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $urlint, $dataroot), 1, 'preview');
        print '</a>';
    }

    if (in_array($action, array('editcss','editmenu','editmedias')))
    {
        if (preg_match('/^create/',$action) && $action != 'editmedias') print '<input type="submit" id="savefile" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
        if (preg_match('/^edit/',$action) && $action != 'editmedias') print '<input type="submit" id="savefile" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
        if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
    }

    print '</div>';


    // ***** Part for pages

    if ($website && ! in_array($action, array('editcss','editmenu','editmedias')))
    {
        print '</div>';	// Close current websitebar to open a new one

        $array=$objectpage->fetchAll($object->id);
        if (! is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
        $atleastonepage=(is_array($array) && count($array) > 0);

        print '<div class="centpercent websitebar"'.($style?' style="'.$style.'"':'').'">';

        print '<div class="websiteselection hideonsmartphoneimp">';
        print '<input type="submit"'.$disabled.' class="button" value="'.dol_escape_htmltag($langs->trans("AddPage")).'" name="create">';
        print '</div>';

        print '<div class="websiteselection hideonsmartphoneimp">';
        print $langs->trans("Page").': ';
        print '</div>';
        print '<div class="websiteselection">';

        if ($action != 'add')
        {
            $out='';
            $out.='<select name="pageid" id="pageid" class="minwidth200 maxwidth300">';
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
                    $out.=$valpage->pageurl.' - '.$valpage->title;
                    if ($object->fk_default_home && $key == $object->fk_default_home) $out.=' ('.$langs->trans("HomePage").')';
                    $out.='</option>';
                }
            }
            else $out.='<option value="-1">&nbsp;</option>';
            $out.='</select>';
            $out.=ajax_combobox('pageid');
            print $out;
        }
        else
        {
            print $langs->trans("New");
        }

        print '<input type="submit" class="button" name="refreshpage" value="'.$langs->trans("Load").'"'.($atleastonepage?'':' disabled="disabled"').'>';

        if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
        {
            $disabled='';
            if (empty($user->rights->websites->write)) $disabled=' disabled="disabled"';

            // Confirmation to clone
            if ($action == 'createfromclone') {
            	// Create an array for form
            	$formquestion = array(
            	array('type' => 'text', 'name' => 'siteref', 'label'=> $langs->trans("Website")  ,'value'=> 'copy_of_'.$object->ref),
            	//array('type' => 'checkbox', 'name' => 'is_a_translation', 'label' => $langs->trans("SiteIsANewTranslation"), 'value' => 0),
            	//array('type' => 'other','name' => 'newlang','label' => $langs->trans("Language"), 'value' => $formadmin->select_language(GETPOST('newlang', 'az09')?GETPOST('newlang', 'az09'):$langs->defaultlang, 'newlang', 0, null, '', 0, 0, 'minwidth200')),
            	//array('type' => 'other','name' => 'newwebsite','label' => $langs->trans("Website"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0))
            	);

            	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id='.$object->id, $langs->trans('CloneSite'), '', 'confirm_createfromclone', $formquestion, 0, 1, 200);

            	print $formconfirm;
            }

            if ($pageid > 0)
            {
                // Confirmation to clone
                if ($action == 'createpagefromclone') {
	                // Create an array for form
					$formquestion = array(
						array('type' => 'text', 'name' => 'pageurl', 'label'=> $langs->trans("WEBSITE_PAGENAME")  ,'value'=> 'copy_of_'.$objectpage->pageurl),
						array('type' => 'checkbox', 'name' => 'is_a_translation', 'label' => $langs->trans("PageIsANewTranslation"), 'value' => 0),
						array('type' => 'other','name' => 'newlang','label' => $langs->trans("Language"), 'value' => $formadmin->select_language(GETPOST('newlang', 'az09')?GETPOST('newlang', 'az09'):$langs->defaultlang, 'newlang', 0, null, '', 0, 0, 'minwidth200')),
						array('type' => 'other','name' => 'newwebsite','label' => $langs->trans("Website"), 'value' => $formwebsite->selectWebsite($object->id, 'newwebsite', 0))
					);

	               	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?pageid=' . $pageid, $langs->trans('ClonePage'), '', 'confirm_createpagefromclone', $formquestion, 0, 1, 250);

					print $formconfirm;
	            }

                print ' &nbsp; ';

                print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageMeta")).'" name="editmeta">';
                print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageSource")).'" name="editsource">';
                print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageContent")).'" name="editcontent">';
                if ($object->fk_default_home > 0 && $pageid == $object->fk_default_home) print '<input type="submit" class="button" disabled="disabled" value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
                else print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
        		print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("ClonePage")).'" name="createpagefromclone">';
                print '<input type="submit" class="buttonDelete" name="delete" value="'.$langs->trans("Delete").'"'.($atleastonepage?'':' disabled="disabled"').'>';
            }
        }

        print '</div>';	// end website selection

        print '<div class="websitetools">';

        if ($website && $pageid > 0 && ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone'))
        {
            $websitepage = new WebSitePage($db);
            $websitepage->fetch($pageid);

            $realpage=$urlwithroot.'/public/websites/index.php?website='.$website.'&pageref='.$websitepage->pageurl;
            $pagealias = $websitepage->pageurl;

            print '<div class="websiteinputurl" id="websiteinputpage">';
            print '<input type="text" id="previewpageurl" class="minwidth200imp" name="previewsite" value="'.$pagealias.'" disabled="disabled">';
            $htmltext=$langs->trans("PageNameAliasHelp", $langs->transnoentitiesnoconv("EditPageMeta"));
            print $form->textwithpicto('', $htmltext, 1, 'help', '', 0, 2, 'helppagealias');
            print '</div>';

            $urlext=$virtualurl.'/'.$pagealias.'.php';
            $urlint=$urlwithroot.'/public/websites/index.php?website='.$website;
            print '<a class="websitebuttonsitepreview'.($virtualurl?'':' websitebuttonsitepreviewdisabled cursornotallowed').'" id="previewpageext" href="'.$urlext.'" target="tab'.$website.'ext" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $dataroot, $urlext)).'">';
            print $form->textwithpicto('', $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $dataroot, $virtualurl?$urlext:'<span class="error">'.$langs->trans("VirtualHostUrlNotDefined").'</span>'), 1, 'preview_ext');
            print '</a>';

            print '<a class="websitebuttonsitepreview" id="previewpage" href="'.$realpage.'&nocache='.dol_now().'" class="button" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage)).'">';
            print $form->textwithpicto('', $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage, $dataroot), 1, 'preview');
            print '</a>';       // View page in new Tab
            //print '<input type="submit" class="button" name="previewpage" target="tab'.$website.'"value="'.$langs->trans("ViewPageInNewTab").'">';

            // TODO Add js to save alias like we save virtual host name and use dynamic virtual host for url of id=previewpageext
        }
        if (! in_array($action, array('editcss','editmenu','editmedias','create','createpagefromclone')))
        {
            if (preg_match('/^create/',$action)) print '<input type="submit" id="savefile" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
            if (preg_match('/^edit/',$action)) print '<input type="submit" id="savefile" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
            if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
        }

        print '</div>';	// end websitetools

        print '<div class="websitehelp">';
        if (GETPOST('editsource', 'alpha') || GETPOST('editcontent', 'alpha'))
        {
        	$htmltext=$langs->transnoentitiesnoconv("YouCanEditHtmlSource");
        	print $form->textwithpicto($langs->trans("SyntaxHelp"), $htmltext, 1, 'help', 'inline-block', 0, 2, 'tooltipsubstitution');
        }
        print '</div>';	// end websitehelp



        if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
        {
            // Adding jquery code to change on the fly url of preview ext
            if (! empty($conf->use_javascript_ajax))
            {
                print '<script type="text/javascript" language="javascript">
                    jQuery(document).ready(function() {
                		jQuery("#websiteinputurl").keyup(function() {
                            console.log("Website external url modified "+jQuery("#previewsiteurl").val());
                			if (jQuery("#previewsiteurl").val() != "") jQuery("a.websitebuttonsitepreviewdisabled img").css({ opacity: 1 });
                			else jQuery("a.websitebuttonsitepreviewdisabled img").css({ opacity: 0.2 });
                		});
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


print '</div>';	// end current websitebar

$head = array();


/*
 * Edit mode
 */

if ($action == 'editcss')
{
    print '<div class="fiche">';

    print '<br>';

    $csscontent = @file_get_contents($filecss);
    // Clean the php css file to remove php code and get only css part
    $csscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $csscontent);
    $csscontent.= GETPOST('WEBSITE_CSS_INLINE');
    if (! trim($csscontent)) $csscontent='/* CSS content (all pages) */'."\n".'body.bodywebsite { margin: 0; }';

    $htmlheader = @file_get_contents($filehtmlheader);
    // Clean the php htmlheader file to remove php code and get only html part
    $htmlheader = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $htmlheader);
    if (! trim($htmlheader)) $htmlheader='<!-- HTML header content (common for all pages) -->';
    else $htmlheader='<html>'."\n".trim($htmlheader)."\n".'</html>';

    $robotcontent = @file_get_contents($filerobot);
    // Clean the php htmlheader file to remove php code and get only html part
    $robotcontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $robotcontent);
    if (! trim($robotcontent))
    {
    	$robotcontent.="# Robot file. Generated with ".DOL_APPLICATION_TITLE."\n";
    	$robotcontent.="User-agent: *\n";
    	$robotcontent.="Allow: /public/\n";
    	$robotcontent.="Disallow: /administrator/\n";
    }

    $htaccesscontent = @file_get_contents($filehtaccess);
    // Clean the php htaccesscontent file to remove php code and get only html part
    $htaccesscontent = preg_replace('/<\?php \/\/ BEGIN PHP[^\?]*END PHP \?>\n*/ims', '', $htaccesscontent);
    if (! trim($htaccesscontent))
    {
    	$htaccesscontent.="# Order allow,deny\n";
		$htaccesscontent.="# Deny from all\n";
    }
    //else $htaccesscontent='<html>'."\n".$htaccesscontent."\n".'</html>';*/

    dol_fiche_head();

    print '<!-- Edit CSS -->'."\n";
    print '<table class="border" width="100%">';

    // Website
    print '<tr><td class="titlefieldcreate">';
    print $langs->trans('WebSite');
    print '</td><td>';
    print $website;
    print '</td></tr>';

    // CSS file
    print '<tr><td class="tdtop">';
    print $langs->trans('WEBSITE_CSS_INLINE');
    print '</td><td>';

    $doleditor=new DolEditor('WEBSITE_CSS_INLINE', $csscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'CSS', 'css');

    print '</td></tr>';

    // Common HTML header
	print '<tr><td class="tdtop">';
    print $langs->trans('WEBSITE_HTML_HEADER');
    print '</td><td>';

    $doleditor=new DolEditor('WEBSITE_HTML_HEADER', $htmlheader, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'HTML Header', 'html');

    print '</td></tr>';

    // Robot file
	print '<tr><td class="tdtop">';
    print $langs->trans('WEBSITE_ROBOT');
    print '</td><td>';

    $doleditor=new DolEditor('WEBSITE_ROBOT', $robotcontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, 'Robot file', 'txt');

    print '</td></tr>';

    // .htaccess
	print '<tr><td class="tdtop">';
    print $langs->trans('WEBSITE_HTACCESS');
    print '</td><td>';

    $doleditor=new DolEditor('WEBSITE_HTACCESS', $htaccesscontent, '', '220', 'ace', 'In', true, false, 'ace', 0, '100%', '');
	print $doleditor->Create(1, '', true, $langs->trans("File").' .htaccess', 'txt');

    print '</td></tr>';

    print '</table>';

    dol_fiche_end();

    print '</div>';

    print '<br>';
}

if ($action == 'editmeta' || $action == 'create')
{
    print '<div class="fiche">';

    print '<br>';

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath('/websites/index.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AddPage");
   	$head[$h][2] = 'card';
	$h++;

    //dol_fiche_head($head, 'card', $langs->trans("AddPage"), -1, 'globe');
	if ($action == 'create') print_fiche_titre($langs->trans("AddPage"));

    print '<!-- Edit Meta -->'."\n";
    //print '<div class="fichecenter">';

    if ($action == 'create')
    {
    	print '<br>';

    	print ' * '.$langs->trans("CreateByFetchingExternalPage").'<br><hr>';
    	print '<table class="border" width="100%">';
    	print '<tr><td class="titlefieldcreate">';
    	print $langs->trans("URL");
    	print '</td><td>';
    	print '<input class="flat minwidth300" type="text" name="externalurl" value="'.dol_escape_htmltag(GETPOST('externalurl','alpha')).'" placeholder="http://externalsite/pagetofetch"> ';
		print '<input class="button" type="submit" name="fetchexternalurl" value="'.dol_escape_htmltag($langs->trans("FetchAndCreate")).'">';
    	print '</td></tr>';
    	print '</table>';

    	print '<br>';

    	print ' * '.$langs->trans("OrEnterPageInfoManually").'<br><hr>';
    }

    print '<table class="border" width="100%">';

    if ($action != 'create')
    {
        print '<tr><td class="titlefield">';
        print $langs->trans('WEBSITE_PAGEURL');
        print '</td><td>';
        print '/public/websites/index.php?website='.urlencode($website).'&pageid='.urlencode($pageid);
        print '</td></tr>';

        /*
        print '<tr><td class="titlefield">';
        print $langs->trans('InitiallyGrabbedFrom');
        print '</td><td>';
        print $objectpage->grabbed_from;
        print '</td></tr>';
        */

        $pageurl=$objectpage->pageurl;
        $pagetitle=$objectpage->title;
        $pagedescription=$objectpage->description;
        $pagekeywords=$objectpage->keywords;
        $pagelang=$objectpage->lang;
    }
    if (GETPOST('WEBSITE_PAGENAME'))    $pageurl=GETPOST('WEBSITE_PAGENAME','alpha');
    if (GETPOST('WEBSITE_TITLE'))       $pagetitle=GETPOST('WEBSITE_TITLE','alpha');
    if (GETPOST('WEBSITE_DESCRIPTION')) $pagedescription=GETPOST('WEBSITE_DESCRIPTION','alpha');
    if (GETPOST('WEBSITE_KEYWORDS'))    $pagekeywords=GETPOST('WEBSITE_KEYWORDS','alpha');
    if (GETPOST('WEBSITE_LANG'))        $pagelang=GETPOST('WEBSITE_LANG','aZ09');

    print '<tr><td class="titlefieldcreate fieldrequired">';
    print $langs->trans('WEBSITE_PAGENAME');
    print '</td><td>';
    print '<input type="text" class="flat maxwidth300" name="WEBSITE_PAGENAME" value="'.dol_escape_htmltag($pageurl).'">';
    print '</td></tr>';

    print '<tr><td class="fieldrequired">';
    print $langs->trans('WEBSITE_TITLE');
    print '</td><td>';
    print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_TITLE" value="'.dol_escape_htmltag($pagetitle).'">';
    print '</td></tr>';

    print '<tr><td>';
    print $langs->trans('WEBSITE_DESCRIPTION');
    print '</td><td>';
    print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_DESCRIPTION" value="'.dol_escape_htmltag($pagedescription).'">';
    print '</td></tr>';

    print '<tr><td>';
    print $langs->trans('WEBSITE_KEYWORDS');
    print '</td><td>';
    print '<input type="text" class="flat quatrevingtpercent" name="WEBSITE_KEYWORDS" value="'.dol_escape_htmltag($pagekeywords).'">';
    print '</td></tr>';

    print '<tr><td>';
    print $langs->trans('Language');
    print '</td><td>';
    print $formadmin->select_language($pagelang?$pagelang:$langs->defaultlang, 'WEBSITE_LANG');
    print '</td></tr>';

    print '</table>';

    if ($action == 'create')
    {
    	print '<div class="center">';

	    print '<input class="button" type="submit" name="add" value="'.$langs->trans("Create").'">';
    	print '<input class="button" type="submit" name="preview" value="'.$langs->trans("Cancel").'">';

    	print '</div>';
    }


	//print '</div>';

    //dol_fiche_end();

    print '</div>';

    print '<br>';
}

if ($action == 'editmedias')
{
    print '<!-- Edit Media -->'."\n";
    print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';
}

if ($action == 'editmenu')
{
    print '<!-- Edit Menu -->'."\n";
    print '<div class="center">'.$langs->trans("FeatureNotYetAvailable").'</center>';
}

if ($action == 'editsource')
{
	/*
	 * Editing global variables not related to a specific theme
	 */

	//$csscontent = @file_get_contents($filecss);

	$contentforedit = '';
	/*$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
	 $contentforedit.=$csscontent;
	 $contentforedit.='</style>'."\n";*/
	$contentforedit .= $objectpage->content;

	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('PAGE_CONTENT',$contentforedit,'',500,'Full','',true,true,'ace',ROWS_5,'90%');
	$doleditor->Create(0, '', false, 'HTML Source', 'php');
}

if ($action == 'editcontent')
{
    /*
     * Editing global variables not related to a specific theme
     */

    //$csscontent = @file_get_contents($filecss);

    $contentforedit = '';
    /*$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
    $contentforedit.=$csscontent;
    $contentforedit.='</style>'."\n";*/
    $contentforedit .= $objectpage->content;

    $contentforedit = preg_replace('/(<img.*src=")(?!http)/', '\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $contentforedit, -1, $nbrep);

    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('PAGE_CONTENT',$contentforedit,'',500,'Full','',true,true,true,ROWS_5,'90%');
    $doleditor->Create(0, '', false);
}

print "</div>\n</form>\n";



if ($action == 'preview' || $action == 'createfromclone' || $action == 'createpagefromclone')
{
    if ($pageid > 0)
    {
    	// Ouput page under the Dolibarr top menu
        $objectpage->fetch($pageid);
        $csscontent = @file_get_contents($filecss);

        $out = '<!-- Page content '.$filetpl.' : Div with (CSS + Page content from database) -->'."\n";

        $out.='<div id="websitecontentundertopmenu" class="websitecontentundertopmenu">'."\n";

        // REPLACEMENT OF LINKS When page called by website editor

        $out.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
        $out.=dolWebsiteReplacementOfLinks($object, $csscontent);
        $out.='</style>'."\n";

		$out.='<div id="bodywebsite" class="bodywebsite">'."\n";

        $out.=dolWebsiteReplacementOfLinks($object, $objectpage->content)."\n";

        $out.='</div>';

        $out.='</div>';

        $out.= "\n".'<!-- End page content '.$filetpl.' -->'."\n\n";

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




/**
 * Save content of a page on disk
 *
 * @param	string		$filealias			Full path of filename to generate
 * @param	Website		$object				Object website
 * @param	WebsitePage	$objectpage			Object websitepage
 * @return	boolean							True if OK
 */
function dolSavePageAlias($filealias, $object, $objectpage)
{
	global $conf;

	// Now create the .tpl file (duplicate code with actions updatesource or updatecontent but we need this to save new header)
	dol_syslog("We regenerate the alias page filealias=".$filealias);

	$aliascontent = '<?php'."\n";
	$aliascontent.= "// File generated to wrap the alias page - DO NOT MODIFY - It is just a wrapper to real page\n";
	$aliascontent.= 'global $dolibarr_main_data_root;'."\n";
	$aliascontent.= 'if (empty($dolibarr_main_data_root)) require \'./page'.$objectpage->id.'.tpl.php\'; ';
	$aliascontent.= 'else require $dolibarr_main_data_root.\'/websites/\'.$website->ref.\'/page'.$objectpage->id.'.tpl.php\';'."\n";
	$aliascontent.= '?>'."\n";
	$result = file_put_contents($filealias, $aliascontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filealias, octdec($conf->global->MAIN_UMASK));

	return ($result?true:false);
}


/**
 * Save content of a page on disk
 *
 * @param	string		$filetpl			Full path of filename to generate
 * @param	Website		$object				Object website
 * @param	WebsitePage	$objectpage			Object websitepage
 * @return	boolean							True if OK
 */
function dolSavePageContent($filetpl, $object, $objectpage)
{
	global $conf;

	// Now create the .tpl file (duplicate code with actions updatesource or updatecontent but we need this to save new header)
	dol_syslog("We regenerate the tpl page filetpl=".$filetpl);

	dol_delete_file($filetpl);

	$shortlangcode = '';
	if ($objectpage->lang) $shortlangcode=preg_replace('/[_-].*$/', '', $objectpage->lang);		// en_US or en-US -> en

	$tplcontent ='';
	$tplcontent.= "<?php // BEGIN PHP\n";
	$tplcontent.= '$websitekey=basename(dirname(__FILE__));'."\n";
	$tplcontent.= "if (! defined('USEDOLIBARRSERVER')) { require_once './master.inc.php'; } // Not already loaded"."\n";
	$tplcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
	$tplcontent.= "require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
	$tplcontent.= "ob_start();\n";
	$tplcontent.= "// END PHP ?>\n";
	$tplcontent.= '<html'.($shortlangcode ? ' lang="'.$shortlangcode.'"':'').'>'."\n";
	$tplcontent.= '<head>'."\n";
	$tplcontent.= '<title>'.dol_string_nohtmltag($objectpage->title, 0, 'UTF-8').'</title>'."\n";
	$tplcontent.= '<meta charset="UTF-8">'."\n";
	$tplcontent.= '<meta http-equiv="content-type" content="text/html; charset=utf-8" />'."\n";
	$tplcontent.= '<meta name="robots" content="index, follow" />'."\n";
	$tplcontent.= '<meta name="viewport" content="width=device-width, initial-scale=0.8">'."\n";
	$tplcontent.= '<meta name="keywords" content="'.dol_string_nohtmltag($objectpage->keywords).'" />'."\n";
	$tplcontent.= '<meta name="title" content="'.dol_string_nohtmltag($objectpage->title, 0, 'UTF-8').'" />'."\n";
	$tplcontent.= '<meta name="description" content="'.dol_string_nohtmltag($objectpage->description, 0, 'UTF-8').'" />'."\n";
	$tplcontent.= '<meta name="generator" content="'.DOL_APPLICATION_TITLE.' '.DOL_VERSION.'" />'."\n";
	$tplcontent.= '<!-- Include link to CSS file -->'."\n";
	$tplcontent.= '<link rel="stylesheet" href="styles.css.php?websiteid='.$object->id.'" type="text/css" />'."\n";
	$tplcontent.= '<!-- Include common HTML header file -->'."\n";
	$tplcontent.= '<?php print file_get_contents(DOL_DATA_ROOT."/websites/'.$object->ref.'/htmlheader.html"); ?>'."\n";
	$tplcontent.= '</head>'."\n";

	$tplcontent.= '<!-- File generated by Dolibarr website module editor -->'."\n";
	$tplcontent.= '<body id="bodywebsite" class="bodywebsite">'."\n";
	$tplcontent.= $objectpage->content."\n";
	$tplcontent.= '</body>'."\n";

	$tplcontent.= '<?php // BEGIN PHP'."\n";
	$tplcontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
	$tplcontent.= "// END PHP ?>"."\n";

	//var_dump($filetpl);exit;
	$result = file_put_contents($filetpl, $tplcontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filetpl, octdec($conf->global->MAIN_UMASK));

	return $result;
}
