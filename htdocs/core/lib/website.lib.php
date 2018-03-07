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
 * Used to ouput the page on the Preview from backoffice.
 *
 * @param	Website		$website			Web site object
 * @param	string		$content			Content to replace
 * @param	int			$removephppart		0=Replace PHP sections with a PHP badge. 1=Remove completely PHP sections.
 * @return	boolean							True if OK
 */
function dolWebsiteReplacementOfLinks($website, $content, $removephppart=0)
{
	// Replace php code. Note $content may come from database and does not contains body tags.
	$replacewith='...php...';
	if ($removephppart) $replacewith='';
	$content = preg_replace('/value="<\?php((?!\?>).)*\?>\n*/ims', 'value="'.$replacewith.'"', $content);

	$replacewith='<span class="phptag">...php...</span>';
	if ($removephppart) $replacewith='';
	$content = preg_replace('/<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

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
		$content=preg_replace('/(href=")\/\"/', '\1'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'"', $content, -1, $nbrep);
		// Replace relative link /xxx.php with dolibarr URL:  ...href="....php"
		$content=preg_replace('/(href=")\/?([^:\"]*)(\.php\")/', '\1'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);
		// Replace relative link /xxx with dolibarr URL:  ...href="....php"
		$content=preg_replace('/(href=")\/?([a-zA-Z0-9\-]+)(["\?]+)/', '\1'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

		// Fix relative link /document.php with correct URL after the DOL_URL_ROOT:  ...href="/document.php?modulepart="
		$content=preg_replace('/(href=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1'.DOL_URL_ROOT.'\2\3"', $content, -1, $nbrep);
		$content=preg_replace('/(src=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1'.DOL_URL_ROOT.'\2\3"', $content, -1, $nbrep);

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
			$content=preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/',  '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
		}
		else
		{
			$content=preg_replace('/(<script[^>]*src=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1medias/\4\5', $content, -1, $nbrep);

			$content=preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1medias/\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1medias/\4\5', $content, -1, $nbrep);
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
 * Make a redirect to another container.
 *
 * @param 	string	$containerref		Ref of container to redirect to (must be a page from website root. Example: 'mypage.php' means 'mywebsite/mypage.php').
 * @param 	string	$containeraliasalt	Ref of alternative aliases to redirect to.
 * @param 	int		$containerid		Id of container.
 * @return  void
 */
function redirectToContainer($containerref, $containeraliasalt='',$containerid=0)
{
	global $db, $website;

	$newurl = '';
	$result=0;

	// We make redirect using the alternative alias, we must find the real $containerref
	if ($containeraliasalt)
	{
		include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
		$tmpwebsitepage=new WebsitePage($db);
		$result = $tmpwebsitepage->fetch(0, $website->id, '', $containeraliasalt);
		if ($result > 0)
		{
			$containerref = $tmpwebsitepage->pageurl;
		}
		else
		{
			print "Error, page contains a redirect to the alternative alias '".$containeraliasalt."' that does not exists in web site (".$website->id." / ".$website->ref.")";
			exit;
		}
	}

	if (defined('USEDOLIBARRSERVER'))	// When page called from Dolibarr server
	{
		// Check new container exists
		if (! $containeraliasalt)	// If containeraliasalt set, we already did the test
		{
			include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
			$tmpwebsitepage=new WebsitePage($db);
			$result = $tmpwebsitepage->fetch(0, $website->id, $containerref);
			unset($tmpwebsitepage);
		}
		if ($result > 0)
		{
			$currenturi = $_SERVER["REQUEST_URI"];
			if (preg_match('/&pageref=([^&]+)/', $currenturi, $regtmp))
			{
				if ($regtmp[0] == $containerref)
				{
					print "Error, page with uri '.$currenturi.' try a redirect to the same alias page '".$containerref."' in web site '".$website->ref."'";
					exit;
				}
				else
				{
					$newurl = preg_replace('/&pageref=([^&]+)/', '&pageref='.$containerref, $currenturi);
				}
			}
			else
			{
				$newurl = $currenturi.'&pageref='.urlencode($containerref);
			}
		}
	}
	else								// When page called from virtual host server
	{
		$newurl = '/'.$containerref.'.php';
	}

	if ($newurl)
	{
		header("Location: ".$newurl);
		exit;
	}
	else
	{
		print "Error, page contains a redirect to the alias page '".$containerref."' that does not exists in web site (".$website->id." / ".$website->ref.")";
		exit;
	}
}


/**
 * Clean an HTML page to report only content, so we can include it into another page.
 * It outputs content of file sanitized from html and body part.
 *
 * @param 	string	$containerref		Path to file to include (must be a page from website root. Example: 'mypage.php' means 'mywebsite/mypage.php')
 * @return  void
 */
function includeContainer($containerref)
{
	global $conf, $db, $langs, $mysoc, $user, $website;
	global $includehtmlcontentopened;
	global $websitekey;

	$MAXLEVEL=20;

	if (! preg_match('/\.php$/i', $containerref)) $containerref.='.php';

	$fullpathfile=DOL_DATA_ROOT.'/website/'.$websitekey.'/'.$containerref;

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
		print 'ERROR: FAILED TO INCLUDE PAGE '.$containerref.".\n";
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
 * @param	int			$grabimages		0=Do not grab images, 1=Grab images
 * @param	string		$grabimagesinto	'root' or 'subpage'
 * @return	void
 */
function getAllImages($object, $objectpage, $urltograb, &$tmp, &$action, $modifylinks=0, $grabimages=1, $grabimagesinto='subpage')
{
	global $conf;

	$error=0;

	dol_syslog("Call getAllImages with grabimagesinto=".$grabimagesinto);

	$alreadygrabbed=array();

	if (preg_match('/\/$/', $urltograb)) $urltograb.='.';
	$urltograb = dirname($urltograb);							// So urltograb is now http://www.nltechno.com or http://www.nltechno.com/dir1

	// Search X in "img...src=X"
	preg_match_all('/<img([^\.\/]+)src="([^>"]+)"([^>]*)>/i', $tmp, $regs);

	foreach ($regs[0] as $key => $val)
	{
		if (preg_match('/^data:image/i', $regs[2][$key])) continue;		// We do nothing for such images

		if (preg_match('/^\//', $regs[2][$key]))
		{
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograb);
			$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key];	// We use dirroot
		}
		else
		{
			$urltograbbis = $urltograb.'/'.$regs[2][$key];	// We use dir of grabbed file
		}

		$linkwithoutdomain = $regs[2][$key];
		$dirforimages = '/'.$objectpage->pageurl;
		if ($grabimagesinto == 'root') $dirforimages='';

		// Define $filetosave and $filename
		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];
		if (preg_match('/^http/', $regs[2][$key]))
		{
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
		}
		$filename = 'image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;

		// Clean the aa/bb/../cc into aa/cc
		$filetosave = preg_replace('/\/[^\/]+\/\.\./', '', $filetosave);
		$filename = preg_replace('/\/[^\/]+\/\.\./', '', $filename);

		//var_dump($filetosave);
		//var_dump($filename);
		//exit;

		if (empty($alreadygrabbed[$urltograbbis]))
		{
			if ($grabimages)
			{
				$tmpgeturl = getURLContent($urltograbbis);
				if ($tmpgeturl['curl_error_no'])
				{
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg'], null, 'errors');
					$action='create';
				}
				elseif ($tmpgeturl['http_code'] != '200')
				{
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
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

		if (preg_match('/^\//', $regs[2][$key]))
		{
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograb);
			$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key];	// We use dirroot
		}
		else
		{
			$urltograbbis = $urltograb.'/'.$regs[2][$key];	// We use dir of grabbed file
		}

		$linkwithoutdomain = $regs[2][$key];

		$dirforimages = '/'.$objectpage->pageurl;
		if ($grabimagesinto == 'root') $dirforimages='';

		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $regs[2][$key])?'':'/').$regs[2][$key];

		if (preg_match('/^http/', $regs[2][$key]))
		{
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;
		}

		$filename = 'image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain)?'':'/').$linkwithoutdomain;

		// Clean the aa/bb/../cc into aa/cc
		$filetosave = preg_replace('/\/[^\/]+\/\.\./', '', $filetosave);
		$filename = preg_replace('/\/[^\/]+\/\.\./', '', $filename);

		//var_dump($filetosave);
		//var_dump($filename);
		//exit;

		if (empty($alreadygrabbed[$urltograbbis]))
		{
			if ($grabimages)
			{
				$tmpgeturl = getURLContent($urltograbbis);
				if ($tmpgeturl['curl_error_no'])
				{
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['curl_error_msg'], null, 'errors');
					$action='create';
				}
				elseif ($tmpgeturl['http_code'] != '200')
				{
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
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
		}

		if ($modifylinks)
		{
			$tmp = preg_replace('/'.preg_quote($regs[0][$key],'/').'/i', 'background'.$regs[1][$key].'url("'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file='.$filename.'")', $tmp);
		}
	}
}



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
	$aliascontent.= 'else require $dolibarr_main_data_root.\'/website/\'.$website->ref.\'/page'.$objectpage->id.'.tpl.php\';'."\n";
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
	$tplcontent.= '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";
	$tplcontent.= '<meta name="keywords" content="'.dol_string_nohtmltag($objectpage->keywords).'" />'."\n";
	$tplcontent.= '<meta name="title" content="'.dol_string_nohtmltag($objectpage->title, 0, 'UTF-8').'" />'."\n";
	$tplcontent.= '<meta name="description" content="'.dol_string_nohtmltag($objectpage->description, 0, 'UTF-8').'" />'."\n";
	$tplcontent.= '<meta name="generator" content="'.DOL_APPLICATION_TITLE.' '.DOL_VERSION.' (https://www.dolibarr.org)" />'."\n";
	$tplcontent.= '<!-- Include link to CSS file -->'."\n";
	$tplcontent.= '<link rel="stylesheet" href="styles.css.php?websiteid='.$object->id.'" type="text/css" />'."\n";
	$tplcontent.= '<!-- Include HTML header from common file -->'."\n";
	$tplcontent.= '<?php print preg_replace(\'/<\/?html>/ims\', \'\', file_get_contents(DOL_DATA_ROOT."/website/'.$object->ref.'/htmlheader.html")); ?>'."\n";
	$tplcontent.= '<!-- Include HTML header from page header block -->'."\n";
	$tplcontent.= preg_replace('/<\/?html>/ims', '', $objectpage->htmlheader)."\n";
	$tplcontent.= '</head>'."\n";

	$tplcontent.= '<!-- File generated by Dolibarr website module editor -->'."\n";
	$tplcontent.= '<body id="bodywebsite" class="bodywebsite">'."\n";
	$tplcontent.= $objectpage->content."\n";
	$tplcontent.= '</body>'."\n";
	$tplcontent.= '</html>'."\n";

	$tplcontent.= '<?php // BEGIN PHP'."\n";
	$tplcontent.= '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp);'."\n";
	$tplcontent.= "// END PHP ?>"."\n";

	//var_dump($filetpl);exit;
	$result = file_put_contents($filetpl, $tplcontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filetpl, octdec($conf->global->MAIN_UMASK));

		return $result;
}


/**
 * Save content of the index.php page
 *
 * @param	string		$pathofwebsite			Path of website root
 * @param	string		$fileindex				Full path of file index.php
 * @param	string		$filetpl				File tpl to index.php page redirect to
 * @return	boolean								True if OK
 */
function dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl)
{
	global $conf;

	$result=0;

	dol_mkdir($pathofwebsite);
	dol_delete_file($fileindex);

	$indexcontent = '<?php'."\n";
	$indexcontent.= "// BEGIN PHP File generated to provide an index.php as Home Page or alias redirector - DO NOT MODIFY - It is just a generated wrapper.\n";
	$indexcontent.= '$websitekey=basename(dirname(__FILE__));'."\n";
	$indexcontent.= "if (! defined('USEDOLIBARRSERVER')) { require_once './master.inc.php'; } // Load master if not already loaded\n";
	$indexcontent.= 'if (! empty($_GET[\'pageref\']) || ! empty($_GET[\'pagealiasalt\']) || ! empty($_GET[\'pageid\'])) {'."\n";
	$indexcontent.= "	require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';\n";
	$indexcontent.= "	require_once DOL_DOCUMENT_ROOT.'/core/website.inc.php';\n";
	$indexcontent.= '	redirectToContainer($_GET[\'pageref\'], $_GET[\'pagealiasalt\'], $_GET[\'pageid\']);'."\n";
	$indexcontent.= "}\n";
	$indexcontent.= "include_once './".basename($filetpl)."'\n";
	$indexcontent.= '// END PHP ?>'."\n";
	$result = file_put_contents($fileindex, $indexcontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($fileindex, octdec($conf->global->MAIN_UMASK));

	return $result;
}


/**
 * Save content of a page on disk
 *
 * @param	string		$filehtmlheader		Full path of filename to generate
 * @param	string		$htmlheadercontent	Content of file
 * @return	boolean							True if OK
 */
function dolSaveHtmlHeader($filehtmlheader, $htmlheadercontent)
{
	global $conf, $pathofwebsite;

	dol_syslog("Save html header into ".$filehtmlheader);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filehtmlheader, $htmlheadercontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filehtmlheader, octdec($conf->global->MAIN_UMASK));

		if (! $result)
		{
			setEventMessages('Failed to write file '.$filehtmlheader, null, 'errors');
			return false;
		}

		return true;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$filecss			Full path of filename to generate
 * @param	string		$csscontent			Content of file
 * @return	boolean							True if OK
 */
function dolSaveCssFile($filecss, $csscontent)
{
	global $conf, $pathofwebsite;

	dol_syslog("Save css file into ".$filecss);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filecss, $csscontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filecss, octdec($conf->global->MAIN_UMASK));

		if (! $result)
		{
			setEventMessages('Failed to write file '.$filecss, null, 'errors');
			return false;
		}

		return true;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$filejs				Full path of filename to generate
 * @param	string		$jscontent			Content of file
 * @return	boolean							True if OK
 */
function dolSaveJsFile($filejs, $jscontent)
{
	global $conf, $pathofwebsite;

	dol_syslog("Save js file into ".$filejs);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filejs, $jscontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filejs, octdec($conf->global->MAIN_UMASK));

		if (! $result)
		{
			setEventMessages('Failed to write file '.$filejs, null, 'errors');
			return false;
		}

		return true;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$filerobot			Full path of filename to generate
 * @param	string		$robotcontent		Content of file
 * @return	boolean							True if OK
 */
function dolSaveRobotFile($filerobot, $robotcontent)
{
	global $conf, $pathofwebsite;

	dol_syslog("Save robot file into ".$filerobot);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filerobot, $robotcontent);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filerobot, octdec($conf->global->MAIN_UMASK));

		if (! $result)
		{
			setEventMessages('Failed to write file '.$filerobot, null, 'errors');
			return false;
		}

		return true;
}

/**
 * Save content of a page on disk
 *
 * @param	string		$filehtaccess		Full path of filename to generate
 * @param	string		$htaccess			Content of file
 * @return	boolean							True if OK
 */
function dolSaveHtaccessFile($filehtaccess, $htaccess)
{
	global $conf, $pathofwebsite;

	dol_syslog("Save htaccess file into ".$filehtaccess);

	dol_mkdir($pathofwebsite);
	$result = file_put_contents($filehtaccess, $htaccess);
	if (! empty($conf->global->MAIN_UMASK))
		@chmod($filehtaccess, octdec($conf->global->MAIN_UMASK));

		if (! $result)
		{
			setEventMessages('Failed to write file '.$filehtaccess, null, 'errors');
			return false;
		}

		return true;
}


