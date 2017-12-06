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
 * Convert a page content to have correct links (based on DOL_URL_ROOT) into an html content.
 * Used to ouput the page on the Preview.
 *
 * @param	Website		$website			Web site object
 * @param	string		$content			Content to replace
 * @return	boolean							True if OK
 */
function dolWebsiteReplacementOfLinks($website, $content)
{
	// Replace php code. Note $content may come from database and does not contains body tags.

	$content = preg_replace('/value="<\?php((?!\?>).)*\?>\n*/ims', 'value="...php..."', $content);
	$content = preg_replace('/<\?php((?!\?>).)*\?>\n*/ims', '<span style="background: #ddd; border: 1px solid #ccc; border-radius: 4px;">...php...</span>', $content);

	// Replace relative link / with dolibarr URL
	$content = preg_replace('/(href=")\/\"/', '\1'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageid='.$website->fk_default_home.'"', $content, -1, $nbrep);
	// Replace relative link /xxx.php with dolibarr URL
	$content = preg_replace('/(href=")\/?([^:\"]*)(\.php\")/', '\1'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

	$content = preg_replace('/url\((["\']?)medias\//', 'url(\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	// <img src="image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	$content = preg_replace('/(<img[^>]*src=")(?!(http|'.preg_quote(DOL_URL_ROOT,'/').'\/viewimage))/', '\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	// action="newpage.php" => action="dolibarr/website/index.php?website=...&pageref=newpage
	$content = preg_replace('/(action=")\/?([^:\"]*)(\.php\")/', '\1'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

	return $content;
}


/**
 * Render a string of an HTML content and output it.
 * Used to ouput the page when viewed from server (Dolibarr or Apache).
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

	if (defined('USEDOLIBARRSERVER'))	// REPLACEMENT OF LINKS When page called from Dolibarr server
	{
		global $website;

		// Replace relative link / with dolibarr URL:  ...href="/"...
		$content=preg_replace('/(href=")\/\"/', '\1'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageid='.$website->fk_default_home.'"', $content, -1, $nbrep);
		// Replace relative link /xxx.php with dolibarr URL:  ...href="....php"
		$content=preg_replace('/(href=")\/?([^:\"]*)(\.php\")/', '\1'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

		// Fix relative link /document.php with correct URL after the DOL_URL_ROOT:  ...href="/document.php?modulepart="
		$content=preg_replace('/(href=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1'.DOL_URL_ROOT.'\2\3"', $content, -1, $nbrep);
		// Fix relative link /viewimage.php with correct URL after the DOL_URL_ROOT:  ...href="/viewimage.php?modulepart="
		$content=preg_replace('/(href=")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1'.DOL_URL_ROOT.'\2\3"', $content, -1, $nbrep);

		// Fix relative link into medias with correct URL after the DOL_URL_ROOT: ../url("medias/
		$content=preg_replace('/url\((["\']?)medias\//', 'url(\1'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

		// action="newpage.php" => action="dolibarr/website/index.php?website=...&pageref=newpage
		$content = preg_replace('/(action=")\/?([^:\"]*)(\.php\")/', '\1'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);
	}
	else								// REPLACEMENT OF LINKS When page called from virtual host
	{
		$symlinktomediaexists=1;

		// Make a change into HTML code to allow to include images from medias directory correct with direct link for virtual server
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.$urlwithroot.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		$nbrep=0;
		if (! $symlinktomediaexists)
		{
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/?>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/',  '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
		}
		else
		{
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/?>)/', '\1medias/\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/', '\1medias/\4\5', $content, -1, $nbrep);
		}
	}

	dol_syslog("dolWebsiteOutput end");

	print $content;
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

	$fullpathfile=DOL_DATA_ROOT.'/website/'.$contentfile;

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
 * Download all images found into page content $tmp.
 * If $modifylinks is set, links to images will be replace with a link to viewimage wrapper.
 *
 * @param 	Website	 	$object			Object website
 * @param 	WebsitePage	$objectpage		Object website page
 * @param 	string		$urltograb		URL to grab (exemple: http://www.nltechno.com/ or http://www.nltechno.com/dir1/ or http://www.nltechno.com/dir1/mapage1)
 * @param 	string		$tmp			Content to parse
 * @param 	string		$action			Var $action
 * @param	string		$modifylinks	0=Do not modify content, 1=Replace links with a link to viewimage
 * @return	void
 */
function getAllImages($object, $objectpage, $urltograb, &$tmp, &$action, $modifylinks=0)
{
	global $conf;

	$error=0;

	$alreadygrabbed=array();

	if (preg_match('/\/$/', $urltograb)) $urltograb.='.';
	$urltograb = dirname($urltograb);							// So urltograb is now http://www.nltechno.com or http://www.nltechno.com/dir1

	preg_match_all('/<img([^\.\/]+)src="([^>"]+)"([^>]*)>/i', $tmp, $regs);

	foreach ($regs[0] as $key => $val)
	{
		if (preg_match('/^data:image/i', $regs[2][$key])) continue;		// We do nothing for such images

		$urltograbbis = $urltograb.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];
		$linkwithoutdomain = $regs[2][$key];
		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];
		if (preg_match('/^http/', $regs[2][$key]))
		{
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
		}

		$filename = 'image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;

		// Clean the aa/bb/../cc into aa/cc
		$filetosave = preg_replace('/\/[^\/]+\/\.\./', '', $filetosave);
		$filename = preg_replace('/\/[^\/]+\/\.\./', '', $filename);

		//var_dump($filetosave);
		//var_dump($filename);
		//exit;

		if (empty($alreadygrabbed[$urltograbbis]))
		{
			$tmpgeturl = getURLContent($urltograbbis);
			if ($tmpgeturl['curl_error_no'])
			{
				$error++;
				setEventMessages($tmpgeturl['curl_error_msg'], null, 'errors');
				$action='create';
			}
			else
			{
				$alreadygrabbed[$urltograbbis]=1;	// Track that file was alreay grabbed.

				dol_mkdir(dirname($filetosave));

				$fp = fopen($filetosave, "w");
				fputs($fp, $tmpgeturl['content']);
				fclose($fp);
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($filetosave, octdec($conf->global->MAIN_UMASK));
			}
		}

		if ($modifylinks)
		{
			$tmp = preg_replace('/'.preg_quote($regs[0][$key],'/').'/i', '<img'.$regs[1][$key].'src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file='.$filename.'"'.$regs[3][$key].'>', $tmp);
		}
	}

	// Search X in "background...url(X)"
	preg_match_all('/background([^\.\/\(;]+)url\([\"\']?([^\)\"\']*)[\"\']?\)/i', $tmp, $regs);

	foreach ($regs[0] as $key => $val)
	{
		if (preg_match('/^data:image/i', $regs[2][$key])) continue;		// We do nothing for such images

		$urltograbbis = $urltograb.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];

		$linkwithoutdomain = $regs[2][$key];
		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];

		if (preg_match('/^http/', $regs[2][$key]))
		{
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
		}

		$filename = 'image/'.$object->ref.'/'.$objectpage->pageurl.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;

		// Clean the aa/bb/../cc into aa/cc
		$filetosave = preg_replace('/\/[^\/]+\/\.\./', '', $filetosave);
		$filename = preg_replace('/\/[^\/]+\/\.\./', '', $filename);

		//var_dump($filetosave);
		//var_dump($filename);
		//exit;

		if (empty($alreadygrabbed[$urltograbbis]))
		{
			$tmpgeturl = getURLContent($urltograbbis);
			if ($tmpgeturl['curl_error_no'])
			{
				$error++;
				setEventMessages($tmpgeturl['curl_error_msg'], null, 'errors');
				$action='create';
			}
			else
			{
				$alreadygrabbed[$urltograbbis]=1;	// Track that file was alreay grabbed.

				dol_mkdir(dirname($filetosave));

				$fp = fopen($filetosave, "w");
				fputs($fp, $tmpgeturl['content']);
				fclose($fp);
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($filetosave, octdec($conf->global->MAIN_UMASK));
			}
		}

		if ($modifylinks)
		{
			$tmp = preg_replace('/'.preg_quote($regs[0][$key],'/').'/i', 'background'.$regs[1][$key].'url("'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file='.$filename.'")', $tmp);
		}
	}

}

