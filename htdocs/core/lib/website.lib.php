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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/lib/website.lib.php
 *      \ingroup    website
 *      \brief      Library for website module
 */


/**
 * Remove PHP code part from a string.
 *
 * @param 	string	$str			String to clean
 * @param	string	$replacewith	String to use as replacement
 * @return 	string					Result string without php code
 * @see dolKeepOnlyPhpCode()
 */
function dolStripPhpCode($str, $replacewith = '')
{
	$newstr = '';

	//split on each opening tag
	$parts = explode('<?php', $str);
	if (!empty($parts))
	{
		$i=0;
		foreach($parts as $part)
		{
			if ($i == 0) 	// The first part is never php code
			{
				$i++;
				$newstr .= $part;
				continue;
			}
			// The second part is the php code. We split on closing tag
			$partlings = explode('?>', $part);
			if (!empty($partlings))
			{
				$phppart = $partlings[0];
				//remove content before closing tag
				if (count($partlings) > 1) $partlings[0] = '';	// Todo why a count > 1 and not >= 1 ?
				//append to out string
				$newstr .= '<span class="phptag" class="tooltip" title="'.dol_escape_htmltag(dolGetFirstLineOfText($phppart).'...').'">'.$replacewith.'<!-- '.$phppart.' --></span>'.implode('', $partlings);
			}
		}
	}
	return $newstr;
}

/**
 * Keep only PHP code part from a HTML string page.
 *
 * @param 	string	$str			String to clean
 * @return 	string					Result string with php code only
 * @see dolStripPhpCode()
 */
function dolKeepOnlyPhpCode($str)
{
	$newstr = '';

	//split on each opening tag
	$parts = explode('<?php', $str);
	if (!empty($parts))
	{
		$i = 0;
		foreach ($parts as $part)
		{
			if ($i == 0) 	// The first part is never php code
			{
				$i++;
				continue;
			}
			$newstr.='<?php';
			//split on closing tag
			$partlings = explode('?>', $part, 2);
			if (!empty($partlings))
			{
				$newstr .= $partlings[0].'?>';
			}
			else
			{
				$newstr .= $part.'?>';
			}
		}
	}
	return $newstr;
}

/**
 * Convert a page content to have correct links (based on DOL_URL_ROOT) into an html content. It replaces also dynamic content with '...php...'
 * Used to ouput the page on the Preview from backoffice.
 *
 * @param	Website		$website			Web site object
 * @param	string		$content			Content to replace
 * @param	int			$removephppart		0=Replace PHP sections with a PHP badge. 1=Remove completely PHP sections.
 * @param	string		$contenttype		Content type
 * @param	int			$containerid 		Contenair id
 * @return	boolean							True if OK
 * @see dolWebsiteOutput() for function used to replace content in a web server context
 */
function dolWebsiteReplacementOfLinks($website, $content, $removephppart = 0, $contenttype = 'html', $containerid = '')
{
	$nbrep = 0;

	dol_syslog('dolWebsiteReplacementOfLinks start (contenttype='.$contenttype." containerid=".$containerid." USEDOLIBARREDITOR=".(defined('USEDOLIBARREDITOR')?'1':'')." USEDOLIBARRSERVER=".(defined('USEDOLIBARRSERVER')?'1':'').')', LOG_DEBUG);
	//if ($contenttype == 'html') { print $content;exit; }

	// Replace php code. Note $content may come from database and does not contains body tags.
	$replacewith='...php...';
	if ($removephppart) $replacewith='';
	$content = preg_replace('/value="<\?php((?!\?>).)*\?>\n*/ims', 'value="'.$replacewith.'"', $content);

	$replacewith='"callto=#';
	if ($removephppart) $replacewith='';
	$content = preg_replace('/"callto:<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	$replacewith='"mailto=#';
	if ($removephppart) $replacewith='';
	$content = preg_replace('/"mailto:<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	$replacewith='src="php';
	if ($removephppart) $replacewith='';
	$content = preg_replace('/src="<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	$replacewith='href="php';
	if ($removephppart) $replacewith='';
	$content = preg_replace('/href="<\?php((?!\?>).)*\?>\n*/ims', $replacewith, $content);

	//$replacewith='<span class="phptag">...php...</span>';
	$replacewith='...php...';
	if ($removephppart) $replacewith='';
	//$content = preg_replace('/<\?php((?!\?toremove>).)*\?toremove>\n*/ims', $replacewith, $content);
	/*if ($content === null) {
		if (preg_last_error() == PREG_JIT_STACKLIMIT_ERROR) $content = 'preg_replace error (when removing php tags) PREG_JIT_STACKLIMIT_ERROR';
	}*/
	$content = dolStripPhpCode($content, $replacewith);
	//var_dump($content);

	// Protect the link styles.css.php to any replacement that we make after.
	$content = str_replace('href="styles.css.php', 'href="!~!~!~styles.css.php', $content);
	$content = str_replace('href="http', 'href="!~!~!~http', $content);
	$content = str_replace('href="//', 'href="!~!~!~//', $content);
	$content = str_replace('src="viewimage.php', 'src="!~!~!~/viewimage.php', $content);
	$content = str_replace('src="/viewimage.php', 'src="!~!~!~/viewimage.php', $content);
	$content = str_replace('src="'.DOL_URL_ROOT.'/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
	$content = str_replace('href="document.php', 'href="!~!~!~/document.php', $content);
	$content = str_replace('href="/document.php', 'href="!~!~!~/document.php', $content);
	$content = str_replace('href="'.DOL_URL_ROOT.'/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);

	// Replace relative link '/' with dolibarr URL
	$content = preg_replace('/(href=")\/(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageid='.$website->fk_default_home.'\2"', $content, -1, $nbrep);
	// Replace relative link /xxx.php#aaa or /xxx.php with dolibarr URL (we discard param ?...)
	$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2\3"', $content, -1, $nbrep);
	// Replace relative link /xxx.php?a=b&c=d#aaa or /xxx.php?a=b&c=d with dolibarr URL
	$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php\?([^#\"<>]*)(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2&\3\4"', $content, -1, $nbrep);

	// Fix relative link into medias with correct URL after the DOL_URL_ROOT: ../url("medias/
	$content = preg_replace('/url\((["\']?)medias\//', 'url(\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
	$content = preg_replace('/data-slide-bg=(["\']?)medias\//', 'data-slide-bg=\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

	// <img src="medias/...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	// <img src="...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	$content = preg_replace('/(<img[^>]*src=")\/?medias\//', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
	// <img src="image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
	$content = preg_replace('/(<img[^>]*src=")\/?([^:\"\!]+)\"/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=\2"', $content, -1, $nbrep);
	// <img src="viewimage.php/modulepart=medias&file=image.png" => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png"
	$content = preg_replace('/(<img[^>]*src=")(\/?viewimage\.php)/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content, -1, $nbrep);

	// action="newpage.php" => action="dolibarr/website/index.php?website=...&pageref=newpage
	$content = preg_replace('/(action=")\/?([^:\"]*)(\.php\")/', '\1!~!~!~'.DOL_URL_ROOT.'/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

	// Fix relative link /document.php with correct URL after the DOL_URL_ROOT:  ...href="/document.php?modulepart="
	$content=preg_replace('/(href=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
	$content=preg_replace('/(src=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

	// Fix relative link /viewimage.php with correct URL after the DOL_URL_ROOT:  ...href="/viewimage.php?modulepart="
	$content=preg_replace('/(url\(")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

	// Fix relative URL
	$content = str_replace('src="!~!~!~/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
	$content = str_replace('href="!~!~!~/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);
	// Remove the protection tag !~!~!~
	$content = str_replace('!~!~!~', '', $content);

	dol_syslog('dolWebsiteReplacementOfLinks end', LOG_DEBUG);
	//if ($contenttype == 'html') { print $content;exit; }

	return $content;
}

/**
 * Render a string of an HTML content and output it.
 * Used to ouput the page when viewed from server (Dolibarr or Apache).
 *
 * @param   string  $content    	Content string
 * @param	string	$contenttype	Content type
 * @param	int		$containerid 	Contenair id
 * @return  void
 * @see	dolWebsiteReplacementOfLinks()  for function used to replace content in the backoffice context.
 */
function dolWebsiteOutput($content, $contenttype = 'html', $containerid = '')
{
	global $db, $langs, $conf, $user;
	global $dolibarr_main_url_root, $dolibarr_main_data_root;

	dol_syslog("dolWebsiteOutput start (contenttype=".$contenttype." containerid=".$containerid." USEDOLIBARREDITOR=".(defined('USEDOLIBARREDITOR')?'1':'')." USEDOLIBARRSERVER=".(defined('USEDOLIBARRSERVER')?'1':'').')');

	// Define $urlwithroot
	$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	if (defined('USEDOLIBARREDITOR'))		// REPLACEMENT OF LINKS When page called from Dolibarr editor
	{
		// We remove the <head> part of content
		if ($contenttype == 'html')
		{
			$content = preg_replace('/<head>.*<\/head>/ims', '', $content);
			$content = preg_replace('/^.*<body(\s[^>]*)*>/ims', '', $content);
			$content = preg_replace('/<\/body(\s[^>]*)*>.*$/ims', '', $content);
		}
	}
	elseif (defined('USEDOLIBARRSERVER'))	// REPLACEMENT OF LINKS When page called from Dolibarr server
	{
		global $website;

		// Protect the link styles.css.php to any replacement that we make after.
		$content = str_replace('href="styles.css.php', 'href="!~!~!~styles.css.php', $content);
		$content = str_replace('href="http', 'href="!~!~!~http', $content);
		$content = str_replace('href="//', 'href="!~!~!~//', $content);
		$content = str_replace('src="viewimage.php', 'src="!~!~!~/viewimage.php', $content);
		$content = str_replace('src="/viewimage.php', 'src="!~!~!~/viewimage.php', $content);
		$content = str_replace('src="'.DOL_URL_ROOT.'/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
		$content = str_replace('href="document.php', 'href="!~!~!~/document.php', $content);
		$content = str_replace('href="/document.php', 'href="!~!~!~/document.php', $content);
		$content = str_replace('href="'.DOL_URL_ROOT.'/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);

		// Replace relative link / with dolibarr URL:  ...href="/"...
		$content = preg_replace('/(href=")\/\"/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'"', $content, -1, $nbrep);
		// Replace relative link /xxx.php#aaa or /xxx.php with dolibarr URL:  ...href="....php" (we discard param ?...)
		$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2\3"', $content, -1, $nbrep);
		// Replace relative link /xxx.php?a=b&c=d#aaa or /xxx.php?a=b&c=d with dolibarr URL
		$content = preg_replace('/(href=")\/?([^:\"\!]*)\.php\?([^#\"<>]*)(#[^\"<>]*)?\"/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2&\3\4"', $content, -1, $nbrep);
		// Replace relative link without .php like /xxx#aaa or /xxx with dolibarr URL:  ...href="....php"
		$content = preg_replace('/(href=")\/?([a-zA-Z0-9\-_#]+)(\"|\?)/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2\3', $content, -1, $nbrep);

		// Fix relative link /document.php with correct URL after the DOL_URL_ROOT:  href="/document.php?modulepart=" => href="/dolibarr/document.php?modulepart="
		$content = preg_replace('/(href=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
		$content = preg_replace('/(src=")(\/?document\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

		// Fix relative link /viewimage.php with correct URL after the DOL_URL_ROOT: href="/viewimage.php?modulepart=" => href="/dolibarr/viewimage.php?modulepart="
		$content = preg_replace('/(href=")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
		$content = preg_replace('/(src=")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);
		$content = preg_replace('/(url\(")(\/?viewimage\.php\?[^\"]*modulepart=[^\"]*)(\")/', '\1!~!~!~'.DOL_URL_ROOT.'\2\3', $content, -1, $nbrep);

		// Fix relative link into medias with correct URL after the DOL_URL_ROOT: ../url("medias/
		$content = preg_replace('/url\((["\']?)medias\//', 'url(\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
		$content = preg_replace('/data-slide-bg=(["\']?)medias\//', 'data-slide-bg=\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);

		// <img src="medias/...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
		// <img src="...image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
		$content = preg_replace('/(<img[^>]*src=")\/?medias\//', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=', $content, -1, $nbrep);
		// <img src="image.png... => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png...
		$content = preg_replace('/(<img[^>]*src=")\/?([^:\"\!]+)\"/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file=\2"', $content, -1, $nbrep);
		// <img src="viewimage.php/modulepart=medias&file=image.png" => <img src="dolibarr/viewimage.php/modulepart=medias&file=image.png"
		$content = preg_replace('/(<img[^>]*src=")(\/?viewimage\.php)/', '\1!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content, -1, $nbrep);

		// action="newpage.php" => action="dolibarr/website/index.php?website=...&pageref=newpage
		$content = preg_replace('/(action=")\/?([^:\"]*)(\.php\")/', '\1!~!~!~'.DOL_URL_ROOT.'/public/website/index.php?website='.$website->ref.'&pageref=\2"', $content, -1, $nbrep);

		// Fix relative URL
		$content = str_replace('src="!~!~!~/viewimage.php', 'src="!~!~!~'.DOL_URL_ROOT.'/viewimage.php', $content);
		$content = str_replace('href="!~!~!~/document.php', 'href="!~!~!~'.DOL_URL_ROOT.'/document.php', $content);
		// Remove the protection tag !~!~!~
		$content = str_replace('!~!~!~', '', $content);
	}
	else									// REPLACEMENT OF LINKS When page called from virtual host
	{
		$symlinktomediaexists=1;

		// Make a change into HTML code to allow to include images from medias directory correct with direct link for virtual server
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.$urlwithroot.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		$nbrep=0;
		if (! $symlinktomediaexists)
		{
			// <img src="image.png... => <img src="medias/image.png...
			$content=preg_replace('/(<img[^>]*src=")\/?image\//', '\1/wrapper.php?modulepart=medias&file=medias/image/', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)\/?image\//', '\1/wrapper.php?modulepart=medias&file=medias/image/', $content, -1, $nbrep);

			$content=preg_replace('/(<script[^>]*src=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(<a[^>]*href=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);

			$content=preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/', '\1/wrapper.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);

			$content=preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)hashp=([^\)]*)(["\']?\))/', '\1/wrapper.php\2hashp\3\4', $content, -1, $nbrep);

			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=mycompany([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=mycompany\3file=\4\5', $content, -1, $nbrep);

			// If some links to documents or viewimage remains, we replace with wrapper
			$content=preg_replace('/(<img[^>]*src=")\/?viewimage\.php/', '\1/wrapper.php', $content, -1, $nbrep);
			$content=preg_replace('/(<a[^>]*href=")\/?documents\.php/', '\1/wrapper.php', $content, -1, $nbrep);
		}
		else
		{
			// <img src="image.png... => <img src="medias/image.png...
			$content=preg_replace('/(<img[^>]*src=")\/?image\//', '\1/medias/image/', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)\/?image\//', '\1/medias/image/', $content, -1, $nbrep);

			$content=preg_replace('/(<script[^>]*src=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(<a[^>]*href=")[^\"]*document\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);

			$content=preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/medias/\4\5', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)modulepart=medias([^\)]*)file=([^\)]*)(["\']?\))/', '\1/medias/\4\5', $content, -1, $nbrep);

			$content=preg_replace('/(<a[^>]*href=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)hashp=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);
			$content=preg_replace('/(url\(["\']?)[^\)]*viewimage\.php([^\)]*)hashp=([^\)]*)(["\']?\))/', '\1/wrapper.php\2hashp=\3\4', $content, -1, $nbrep);

			$content=preg_replace('/(<img[^>]*src=")[^\"]*viewimage\.php([^\"]*)modulepart=mycompany([^\"]*)file=([^\"]*)("[^>]*>)/', '\1/wrapper.php\2modulepart=mycompany\3file=\4\5', $content, -1, $nbrep);

			// If some links to documents or viewimage remains, we replace with wrapper
			$content=preg_replace('/(<img[^>]*src=")\/?viewimage\.php/', '\1/wrapper.php', $content, -1, $nbrep);
			$content=preg_replace('/(<a[^>]*href=")\/?document\.php/', '\1/wrapper.php', $content, -1, $nbrep);
		}
	}

	$content=preg_replace('/ contenteditable="true"/', ' contenteditable="false"', $content, -1, $nbrep);

	dol_syslog("dolWebsiteOutput end");

	print $content;
}


/**
 * Format img tags to introduce viewimage on img src.
 *
 * @param   string  $content    Content string
 * @return  void
 * @see	dolWebsiteOutput()
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
 * @param 	string	$containerref		Ref of container to redirect to (Example: 'mypage' or 'mypage.php').
 * @param 	string	$containeraliasalt	Ref of alternative aliases to redirect to.
 * @param 	int		$containerid		Id of container.
 * @param	int		$permanent			0=Use temporary redirect 302, 1=Use permanent redirect 301
 * @return  void
 */
function redirectToContainer($containerref, $containeraliasalt = '', $containerid = 0, $permanent = 0)
{
	global $db, $website;

	$newurl = '';
	$result = 0;

	// We make redirect using the alternative alias, we must find the real $containerref
	if ($containeraliasalt)
	{
		include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
		$tmpwebsitepage = new WebsitePage($db);
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

	if (defined('USEDOLIBARREDITOR'))
	{
		/*print '<div class="margintoponly marginleftonly">';
		print "This page contains dynamic code that make a redirect to '".$containerref."' in your current context. Redirect has been canceled as it is not supported in edition mode.";
		print '</div>';*/
		$text = "This page contains dynamic code that make a redirect to '".$containerref."' in your current context. Redirect has been canceled as it is not supported in edition mode.";
		setEventMessages($text, null, 'warnings', 'WEBSITEREDIRECTDISABLED'.$containerref);
		return;
	}

	if (defined('USEDOLIBARRSERVER'))	// When page called from Dolibarr server
	{
		// Check new container exists
		if (!$containeraliasalt)	// If containeraliasalt set, we already did the test
		{
			include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
			$tmpwebsitepage = new WebsitePage($db);
			$result = $tmpwebsitepage->fetch(0, $website->id, $containerref);
			unset($tmpwebsitepage);
		}
		if ($result > 0)
		{
			$currenturi = $_SERVER["REQUEST_URI"];
			$regtmp = array();
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
		if ($permanent) {
			header("Status: 301 Moved Permanently", false, 301);
		}
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
	global $conf, $db, $hookmanager, $langs, $mysoc, $user, $website, $websitepage, $weblangs;	// Very important. Required to have var available when running included containers.
	global $includehtmlcontentopened;
	global $websitekey, $websitepagefile;

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
	$res = include $fullpathfile; // Include because we want to execute code content
	$tmpoutput = ob_get_contents();
	ob_end_clean();

	print "\n".'<!-- include '.$fullpathfile.' level = '.$includehtmlcontentopened.' -->'."\n";
	print preg_replace(array('/^.*<body[^>]*>/ims', '/<\/body>.*$/ims'), array('', ''), $tmpoutput);

	if (!$res)
	{
		print 'ERROR: FAILED TO INCLUDE PAGE '.$containerref.".\n";
	}

	$includehtmlcontentopened--;
}

/**
 * Return HTML content to add structured data for an article, news or Blog Post.
 *
 * @param 	string		$type				'blogpost', 'product', 'software'...
 * @param	array		$data				Array of data parameters for structured data
 * @return  string							HTML content
 */
function getStructuredData($type, $data = array())
{
	global $conf, $db, $hookmanager, $langs, $mysoc, $user, $website, $websitepage, $weblangs;	// Very important. Required to have var available when running inluded containers.

	if ($type == 'software')
	{
		$ret = '<!-- Add structured data for blog post -->'."\n";
		$ret .= '<script type="application/ld+json">'."\n";
		$ret .= '{
			"@context": "https://schema.org",
			"@type": "SoftwareApplication",
			"name": "'.$data['name'].'",
			"operatingSystem": "'.$data['os'].'",
			"applicationCategory": "https://schema.org/GameApplication",
			"aggregateRating": {
				"@type": "AggregateRating",
				"ratingValue": "'.$data['ratingvalue'].'",
				"ratingCount": "'.$data['ratingcount'].'"
			},
			"offers": {
				"@type": "Offer",
				"price": "'.$data['price'].'",
				"priceCurrency": "'.($data['currency']?$data['currency']:$conf->currency).'"
			}
		}'."\n";
		$ret .= '</script>'."\n";
	}
	elseif ($type == 'blogpost')
	{
		if ($websitepage->fk_user_creat > 0)
		{
			include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			$tmpuser = new User($db);
			$restmpuser = $tmpuser->fetch($websitepage->fk_user_creat);

			if ($restmpuser > 0)
			{
				$ret = '<!-- Add structured data for blog post -->'."\n";
				$ret .= '<script type="application/ld+json">'."\n";
				$ret .= '{
					  "@context": "https://schema.org",
					  "@type": "NewsArticle",
					  "mainEntityOfPage": {
					    "@type": "WebPage",
					    "@id": "'.$websitepage->pageurl.'"
					  },
					  "headline": "'.$websitepage->title.'",
					  "image": [
					    "'.$websitepage->image.'"
					   ],
					  "datePublished": "'.dol_print_date($websitepage->date_creation, 'dayhourrfc').'",
					  "dateModified": "'.dol_print_date($websitepage->date_modification, 'dayhourrfc').'",
					  "author": {
					    "@type": "Person",
					    "name": "'.$tmpuser->getFullName($weblangs).'"
					  },
					  "publisher": {
					     "@type": "Organization",
					     "name": "'.$mysoc->name.'",
					     "logo": {
					        "@type": "ImageObject",
					        "url": "/viewimage.php?modulepart=mycompany&file=logos%2F'.urlencode($mysoc->logo).'"
					     }
					   },
					  "description": "'.$websitepage->description.'"
					}'."\n";
				$ret .= '</script>'."\n";
			}
		}
	}
	elseif ($type == 'product')
	{
		$ret = '<!-- Add structured data for blog post -->'."\n";
		$ret.= '<script type="application/ld+json">'."\n";
		$ret.= '{
				"@context": "https://schema.org/",
				"@type": "Product",
				"name": "'.$data['label'].'",
				"image": [
					"'.$data['image'].'",
				],
				"description": "'.$data['description'].'",
				"sku": "'.$data['ref'].'",
				"brand": {
					"@type": "Thing",
					"name": "'.$data['brand'].'"
				},
				"author": {
					"@type": "Person",
					"name": "'.$data['author'].'"
				}
				},
				"offers": {
					"@type": "Offer",
					"url": "https://example.com/anvil",
					"priceCurrency": "'.($data['currency']?$data['currency']:$conf->currency).'",
					"price": "'.$data['price'].'",
					"itemCondition": "https://schema.org/UsedCondition",
					"availability": "https://schema.org/InStock",
					"seller": {
						"@type": "Organization",
						"name": "'.$mysoc->name.'"
					}
				}
			}'."\n";
		$ret.= '</script>'."\n";
	}
	return $ret;
}

/**
 * Return list of containers object that match a criteria.
 * WARNING: This function can be used by websites.
 *
 * @param 	string		$type				Type of container to search into (Example: 'page')
 * @param 	string		$algo				Algorithm used for search (Example: 'meta' is searching into meta information like title and description, 'content', 'sitefiles', or any combination, ...)
 * @param	string		$searchstring		Search string
 * @param	int			$max				Max number of answers
 * @return  string							HTML content
 */
function getPagesFromSearchCriterias($type, $algo, $searchstring, $max = 25)
{
	global $conf, $db, $hookmanager, $langs, $mysoc, $user, $website, $websitepage, $weblangs;	// Very important. Required to have var available when running inluded containers.

	$error = 0;
	$arrayresult = array('code'=>'', 'list'=>array());

	if (! is_object($weblangs)) $weblangs = $langs;

	if (empty($searchstring))
	{
		$error++;
		$arrayresult['code']='KO';
		$arrayresult['message']=$weblangs->trans("EmptySearchString");
	}
	elseif (dol_strlen($searchstring) < 2)
	{
		$weblangs->load("errors");
		$error++;
		$arrayresult['code']='KO';
		$arrayresult['message']=$weblangs->trans("ErrorSearchCriteriaTooSmall");
	}
	elseif (! in_array($type, array('', 'page')))
	{
		$error++;
		$arrayresult['code']='KO';
		$arrayresult['message']='Bad value for parameter $type';
	}

	$searchdone = 0;
	$found = 0;

	if (! $error && (empty($max) || ($found < $max)) && (preg_match('/meta/', $algo) || preg_match('/content/', $algo)))
	{
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'website_page';
		$sql.= " WHERE fk_website = ".$website->id;
		if ($type) $sql.= " AND type_container = '".$db->escape($type)."'";
		$sql.= " AND (";
		$searchalgo = '';
		if (preg_match('/meta/', $algo))
		{
			$searchalgo.= ($searchalgo?' OR ':'')."title LIKE '%".$db->escape($searchstring)."%' OR description LIKE '%".$db->escape($searchstring)."%'";
			$searchalgo.= ($searchalgo?' OR ':'')."keywords LIKE '".$db->escape($searchstring).",%' OR keywords LIKE '% ".$db->escape($searchstring)."%'";		// TODO Use a better way to scan keywords
		}
		if (preg_match('/content/', $algo))
		{
			$searchalgo.= ($searchalgo?' OR ':'')."content LIKE '%".$db->escape($searchstring)."%'";
		}
		$sql.=$searchalgo;
		$sql.= ")";
		$sql.= $db->plimit($max);

		$resql = $db->query($sql);
		if ($resql)
		{
			$i = 0;
			while (($obj = $db->fetch_object($resql)) && ($i < $max || $max == 0))
			{
				if ($obj->rowid > 0)
				{
					$tmpwebsitepage = new WebsitePage($db);
					$tmpwebsitepage->fetch($obj->rowid);
					if ($tmpwebsitepage->id > 0) $arrayresult['list'][]=$tmpwebsitepage;
					$found++;
				}
				$i++;
			}
		}
		else
		{
			$error++;
			$arrayresult['code']=$db->lasterrno();
			$arrayresult['message']=$db->lasterror();
		}

		$searchdone = 1;
	}

	if (! $error && (empty($max) || ($found < $max)) && (preg_match('/sitefiles/', $algo)))
	{
		global $dolibarr_main_data_root;

		$pathofwebsite=$dolibarr_main_data_root.'/website/'.$website->ref;
		$filehtmlheader=$pathofwebsite.'/htmlheader.html';
		$filecss=$pathofwebsite.'/styles.css.php';
		$filejs=$pathofwebsite.'/javascript.js.php';
		$filerobot=$pathofwebsite.'/robots.txt';
		$filehtaccess=$pathofwebsite.'/.htaccess';
		$filemanifestjson=$pathofwebsite.'/manifest.json.php';
		$filereadme=$pathofwebsite.'/README.md';

		$filecontent = file_get_contents($filehtmlheader);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent))
		{
			$arrayresult['list'][]=array('type'=>'website_htmlheadercontent');
		}

		$filecontent = file_get_contents($filecss);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent))
		{
			$arrayresult['list'][]=array('type'=>'website_csscontent');
		}

		$filecontent = file_get_contents($filejs);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent))
		{
			$arrayresult['list'][]=array('type'=>'website_jscontent');
		}

		$filerobot = file_get_contents($filerobot);
		if ((empty($max) || ($found < $max)) && preg_match('/'.preg_quote($searchstring, '/').'/', $filecontent))
		{
			$arrayresult['list'][]=array('type'=>'website_robotcontent');
		}

		$searchdone = 1;
	}

	if (! $error)
	{
		if ($searchdone)
		{
			$arrayresult['code']='OK';
			if (empty($arrayresult['list']))
			{
				$arrayresult['code']='KO';
				$arrayresult['message']=$weblangs->trans("NoRecordFound");
			}
		}
		else
		{
			$error++;
			$arrayresult['code']='KO';
			$arrayresult['message']='No supported algorithm found';
		}
	}

	return $arrayresult;
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
function getAllImages($object, $objectpage, $urltograb, &$tmp, &$action, $modifylinks = 0, $grabimages = 1, $grabimagesinto = 'subpage')
{
	global $conf;

	$error = 0;

	dol_syslog("Call getAllImages with grabimagesinto=".$grabimagesinto);

	$alreadygrabbed = array();

	if (preg_match('/\/$/', $urltograb)) $urltograb .= '.';
	$urltograb = dirname($urltograb); // So urltograb is now http://www.nltechno.com or http://www.nltechno.com/dir1

	// Search X in "img...src=X"
	preg_match_all('/<img([^\.\/]+)src="([^>"]+)"([^>]*)>/i', $tmp, $regs);

	foreach ($regs[0] as $key => $val)
	{
		if (preg_match('/^data:image/i', $regs[2][$key])) continue; // We do nothing for such images

		if (preg_match('/^\//', $regs[2][$key]))
		{
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograb);
			$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
		}
		else
		{
			$urltograbbis = $urltograb.'/'.$regs[2][$key]; // We use dir of grabbed file
		}

		$linkwithoutdomain = $regs[2][$key];
		$dirforimages = '/'.$objectpage->pageurl;
		if ($grabimagesinto == 'root') $dirforimages = '';

		// Define $filetosave and $filename
		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $regs[2][$key]) ? '' : '/').$regs[2][$key];
		if (preg_match('/^http/', $regs[2][$key]))
		{
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;
		}
		$filename = 'image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;

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
					$action = 'create';
				}
				elseif ($tmpgeturl['http_code'] != '200')
				{
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
					$action = 'create';
				}
				else
				{
					$alreadygrabbed[$urltograbbis] = 1; // Track that file was alreay grabbed.

					dol_mkdir(dirname($filetosave));

					$fp = fopen($filetosave, "w");
					fputs($fp, $tmpgeturl['content']);
					fclose($fp);
					if (!empty($conf->global->MAIN_UMASK))
						@chmod($filetosave, octdec($conf->global->MAIN_UMASK));
				}
			}
		}

		if ($modifylinks)
		{
			$tmp = preg_replace('/'.preg_quote($regs[0][$key], '/').'/i', '<img'.$regs[1][$key].'src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file='.$filename.'"'.$regs[3][$key].'>', $tmp);
		}
	}

	// Search X in "background...url(X)"
	preg_match_all('/background([^\.\/\(;]+)url\([\"\']?([^\)\"\']*)[\"\']?\)/i', $tmp, $regs);

	foreach ($regs[0] as $key => $val)
	{
		if (preg_match('/^data:image/i', $regs[2][$key])) continue; // We do nothing for such images

		if (preg_match('/^\//', $regs[2][$key]))
		{
			$urltograbdirrootwithoutslash = getRootURLFromURL($urltograb);
			$urltograbbis = $urltograbdirrootwithoutslash.$regs[2][$key]; // We use dirroot
		}
		else
		{
			$urltograbbis = $urltograb.'/'.$regs[2][$key]; // We use dir of grabbed file
		}

		$linkwithoutdomain = $regs[2][$key];

		$dirforimages = '/'.$objectpage->pageurl;
		if ($grabimagesinto == 'root') $dirforimages = '';

		$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $regs[2][$key]) ? '' : '/').$regs[2][$key];

		if (preg_match('/^http/', $regs[2][$key]))
		{
			$urltograbbis = $regs[2][$key];
			$linkwithoutdomain = preg_replace('/^https?:\/\/[^\/]+\//i', '', $regs[2][$key]);
			$filetosave = $conf->medias->multidir_output[$conf->entity].'/image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;
		}

		$filename = 'image/'.$object->ref.$dirforimages.(preg_match('/^\//', $linkwithoutdomain) ? '' : '/').$linkwithoutdomain;

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
					$action = 'create';
				}
				elseif ($tmpgeturl['http_code'] != '200')
				{
					$error++;
					setEventMessages('Error getting '.$urltograbbis.': '.$tmpgeturl['http_code'], null, 'errors');
					$action = 'create';
				}
				else
				{
					$alreadygrabbed[$urltograbbis] = 1; // Track that file was alreay grabbed.

					dol_mkdir(dirname($filetosave));

					$fp = fopen($filetosave, "w");
					fputs($fp, $tmpgeturl['content']);
					fclose($fp);
					if (!empty($conf->global->MAIN_UMASK))
						@chmod($filetosave, octdec($conf->global->MAIN_UMASK));
				}
			}
		}

		if ($modifylinks)
		{
			$tmp = preg_replace('/'.preg_quote($regs[0][$key], '/').'/i', 'background'.$regs[1][$key].'url("'.DOL_URL_ROOT.'/viewimage.php?modulepart=medias&file='.$filename.'")', $tmp);
		}
	}
}
