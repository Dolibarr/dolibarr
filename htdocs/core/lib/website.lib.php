<?php
/* Copyright (C) 2017 Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/lib/website.lib.php
 *      \ingroup    website
 *      \brief      Library for website module
 */


/**
 * Render a string of an HTML content and output it.
 *
 * @param   string  $content    Content string
 * @return  void
 * @see	dolWebsiteSaveContent
 */
function dolWebsiteOutput($content)
{
    global $db, $langs, $conf, $user;
    global $dolibarr_main_url_root, $dolibarr_main_data_root;

    dol_syslog("dolWebsiteOutput start (mode=".(defined('USEDOLIBARRSERVER')?'USEDOLIBARRSERVER':'').')');

    // Define $urlwithroot
    $urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
    $urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
    //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

    // Note: This seems never called when page is output inside the website editor (search 'REPLACEMENT OF LINKS When page called by website editor')

    if (! defined('USEDOLIBARRSERVER'))	// REPLACEMENT OF LINKS When page called from virtual host
    {
        $symlinktomediaexists=1;

		// Make a change into HTML code to allow to include images from medias directory correct with direct link for virtual server
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.$urlwithroot.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
        $nbrep=0;
        if (! $symlinktomediaexists)
        {
            $content=preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
            $content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/',  '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
        }
        else
        {
        	$content=preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1medias/\4\5', $content, -1, $nbrep);
            $content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/', '\1medias/\4\5', $content, -1, $nbrep);
        }
    }
    else								// REPLACEMENT OF LINKS When page called from dolibarr server
    {
    	global $website;

    	// Replace relative link / with dolibarr URL:  ...href="/"...
    	$content=preg_replace('/(href=")\/\"/', '\1'.DOL_URL_ROOT.'/public/websites/index.php?website='.$website->ref.'&pageid='.$website->fk_default_home.'"', $content, -1, $nbrep);
    	// Replace relative link /xxx.php with dolibarr URL:  ...href="....php"
    	$content=preg_replace('/(href=")\/?([^\"]*)(\.php\")/', '\1'.DOL_URL_ROOT.'/public/websites/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

    	// Fix relative link /document.php with correct URL after the DOL_URL_ROOT:  ...href="/document.php?modulepart="
    	$content=preg_replace('/(href=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1'.DOL_URL_ROOT.'\2\3"', $content, -1, $nbrep);
    	// Fix relative link /viewimage.php with correct URL after the DOL_URL_ROOT:  ...href="/viewimage.php?modulepart="
    	$content=preg_replace('/(href=")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1'.DOL_URL_ROOT.'\2\3"', $content, -1, $nbrep);

    	// Fix relative link into medias with correct URL after the DOL_URL_ROOT: ../url("medias/
    	$content=preg_replace('/url\((["\']?)medias\//', 'url(\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
    }

    dol_syslog("dolWebsiteOutput end");

    print $content;
}


/**
 * Convert a page content to have correct links into a new html content.
 * Used to ouput the page on the Preview.
 *
 * @param	Website		$website			Web site object
 * @param	string		$content			Content to replace
 * @return	boolean							True if OK
 */
function dolWebsiteReplacementOfLinks($website, $content)
{
	// Replace php code. Note $content may come from database and does not contains body tags.
	$content = preg_replace('/<\?php[^\?]+\?>\n*/ims', '<span style="background: #ddd; border: 1px solid #ccc; border-radius: 4px;">...php...</span>', $content);

	// Replace relative link / with dolibarr URL
	$content = preg_replace('/(href=")\/\"/', '\1'.DOL_URL_ROOT.'/websites/index.php?website='.$website->ref.'&pageid='.$website->fk_default_home.'"', $content, -1, $nbrep);
	// Replace relative link /xxx.php with dolibarr URL
	$content = preg_replace('/(href=")\/?([^\"]*)(\.php\")/', '\1'.DOL_URL_ROOT.'/websites/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

	$content = preg_replace('/url\((["\']?)medias\//', 'url(\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	// <img src="image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	$content = preg_replace('/(<img.*src=")(?!(http|'.preg_quote(DOL_URL_ROOT,'/').'\/viewimage))/', '\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	return $content;
}


/**
 * Format img tags to introduce viewimage on img src.
 *
 * @param   string  $content    Content string
 * @return  void
 * @see	dolWebsiteOutput
 */
/*
function dolWebsiteSaveContent($content)
{
	global $db, $langs, $conf, $user;
	global $dolibarr_main_url_root, $dolibarr_main_data_root;

	//dol_syslog("dolWebsiteSaveContent start (mode=".(defined('USEDOLIBARRSERVER')?'USEDOLIBARRSERVER':'').')');

	// Define $urlwithroot
	$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
	$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	//$content = preg_replace('/(<img.*src=")(?!(http|'.preg_quote(DOL_URL_ROOT,'/').'\/viewimage))/', '\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	return $content;
}
*/


/**
 * Clean an HTML page to report only content, so we can include it into another page.
 * It outputs content of file sanitized from html and body part.
 *
 * @param 	string	$contentfile		Path to file to include (must include website root. Example: 'mywebsite/mypage.php')
 * @return  void
 */
function dolIncludeHtmlContent($contentfile)
{
	global $conf, $db, $langs, $mysoc, $user, $website;
	global $includehtmlcontentopened;

	$MAXLEVEL=20;

	$fullpathfile=DOL_DATA_ROOT.'/websites/'.$contentfile;

	if (empty($includehtmlcontentopened)) $includehtmlcontentopened=0;
	$includehtmlcontentopened++;
	if ($includehtmlcontentopened > $MAXLEVEL)
	{
		print 'ERROR: RECURSIVE CONTENT LEVEL. Depth of recursive call is more than the limit of '.$MAXLEVEL.".\n";
		return;
	}
	// file_get_contents is not possible. We must execute code with include
	//$content = file_get_contents($fullpathfile);
	//print preg_replace(array('/^.*<body[^>]*>/ims','/<\/body>.*$/ims'), array('', ''), $content);*/

	ob_start();
	$res = include $fullpathfile;		// Include because we want to execute code content
	$tmpoutput = ob_get_contents();
	ob_end_clean();

	print "\n".'<!-- include '.$fullpathfile.' level = '.$includehtmlcontentopened.' -->'."\n";
	print preg_replace(array('/^.*<body[^>]*>/ims','/<\/body>.*$/ims'), array('', ''), $tmpoutput);

	if (! $res)
	{
		print 'ERROR: FAILED TO INCLUDE PAGE '.$contentfile.".\n";
	}

	$includehtmlcontentopened--;
}

/**
 * Generate a zip with all data of web site.
 *
 * @param 	Website		$website		Object website
 * @return  void
 */
function exportWebSite($website)
{
	global $db, $conf;

	dol_mkdir($conf->websites->dir_temp);
	$srcdir = $conf->websites->dir_output.'/'.$website->ref;
	$destdir = $conf->websites->dir_temp.'/'.$website->ref;

	$arrayreplacement=array();

	dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacement);

	$srcdir = DOL_DATA_ROOT.'/medias/images/'.$website->ref;
	$destdir = $conf->websites->dir_temp.'/'.$website->ref.'/medias/images/'.$website->ref;

	dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacement);

	// Build sql file
	dol_mkdir($conf->websites->dir_temp.'/'.$website->ref.'/export');

	$filesql = $conf->websites->dir_temp.'/'.$website->ref.'/export/pages.sql';
	$fp = fopen($filesql,"w");

	$objectpages = new WebsitePage($db);
	$listofpages = $objectpages->fetchAll($website->id);

	// Assign ->newid and ->newfk_page
	$i=1;
	foreach($listofpages as $pageid => $objectpageold)
	{
		$objectpageold->newid=$i;
		$i++;
	}
	$i=1;
	foreach($listofpages as $pageid => $objectpageold)
	{
		// Search newid
		$newfk_page=0;
		foreach($listofpages as $pageid2 => $objectpageold2)
		{
			if ($pageid2 == $objectpageold->fk_page)
			{
				$newfk_page = $objectpageold2->newid;
				break;
			}
		}
		$objectpageold->newfk_page=$newfk_page;
		$i++;
	}
	foreach($listofpages as $pageid => $objectpageold)
	{
		$line = 'INSERT INTO llx_website_page(rowid, fk_page, fk_website, pageurl, title, description, keyword, status, date_creation, tms, lang, import_key, grabbed_from, content)';
		$line.= " VALUES(";
		$line.= $objectpageold->newid."+__MAXROWID__, ";
		$line.= ($objectpageold->newfk_page ? $db->escape($objectpageold->newfk_page)."+__MAXROWID__" : "null").", ";
		$line.= "__WEBSITE_ID__, ";
		$line.= "'".$db->escape($objectpageold->pageurl)."', ";
		$line.= "'".$db->escape($objectpageold->title)."', ";
		$line.= "'".$db->escape($objectpageold->description)."', ";
		$line.= "'".$db->escape($objectpageold->keyword)."', ";
		$line.= "'".$db->escape($objectpageold->status)."', ";
		$line.= "'".$db->idate($objectpageold->date_creation)."', ";
		$line.= "'".$db->idate($objectpageold->date_modification)."', ";
		$line.= "'".$db->escape($objectpageold->lang)."', ";
		$line.= ($objectpageold->import_key ? "'".$db->escape($objectpageold->import_key)."'" : "null").", ";
		$line.= "'".$db->escape($objectpageold->grabbed_from)."', ";
		$line.= "'".$db->escape($objectpageold->content)."'";
		$line.= ");";
		$line.= "\n";
		fputs($fp, $line);
	}

	fclose($fp);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filesql, octdec($conf->global->MAIN_UMASK));

	// Build zip file
	$filedir = $conf->websites->dir_temp.'/'.$website->ref;
	$fileglob = $conf->websites->dir_temp.'/'.$website->ref.'/export/'.$website->ref.'_export_*.zip';
	$filename = $conf->websites->dir_temp.'/'.$website->ref.'/export/'.$website->ref.'_export_'.dol_print_date(dol_now(),'dayhourlog').'.zip';

	dol_delete_file($fileglob, 0);
	dol_compress_file($filedir, $filename, 'zip');

	return $filename;
}

