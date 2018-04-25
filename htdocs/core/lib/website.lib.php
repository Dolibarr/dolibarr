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
 */
function dolWebsiteOutput($content)
{
    global $db, $langs, $conf, $user;
    global $dolibarr_main_url_root;

    dol_syslog("dolWebsiteOutput start");

    if (! defined('USEDOLIBARRSERVER'))
    {
        // Define $urlwithroot
        $urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
        $urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
        //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

        $symlinktomediaexists=1;

		// Make a change into HTML code to allow to include images from medias directory correct with direct link for virtual server
		// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
		// become
		// <img alt="" src="'.$urlwithroot.'/medias/image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
        $nbrep=0;
        if (! $symlinktomediaexists)
        {
            $content=preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $content, -1, $nbrep);
        }
        else
        {
            $content=preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1medias/\4\5', $content, -1, $nbrep);
        }
    }

    dol_syslog("dolWebsiteOutput end");

    print $content;
}


/**
 * Clean an HTML page to report only content, so we can include it into another page
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
	//$content = file_get_contents($fullpathfile);
	//print preg_replace(array('/^.*<body[^>]*>/ims','/<\/body>.*$/ims'), array('', ''), $content);*/

	if (empty($includehtmlcontentopened)) $includehtmlcontentopened=0;
	$includehtmlcontentopened++;
	if ($includehtmlcontentopened > $MAXLEVEL)
	{
		print 'ERROR: RECURSIVE CONTENT LEVEL. Depth of recursive call is more than the limit of '.$MAXLEVEL.".\n";
		return;
	}
	$res = include $fullpathfile;		// Include because we want to execute code content
	if (! $res)
	{
		print 'ERROR: FAILED TO INCLUDE PAGE '.$contentfile.".\n";
	}

	$includehtmlcontentopened--;
}

