<?php
/* Copyright (C) 2017-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
* or see http://www.gnu.org/
*/

/**
 *	\file			htdocs/core/website.inc.php
 *  \brief			Common file loaded by all website pages (after master.inc.php). It set the new object $weblangs, using parameter 'l'.
 *  				This file is included in top of all container pages.
 *  			    The global variable $websitekey must be defined.
 */

// Load website class
include_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';

// Define $website
if (! is_object($website))
{
	$website=new Website($db);
	$website->fetch(0, $websitekey);
}
// Define $weblangs
if (! is_object($weblangs))
{
	$weblangs = dol_clone($langs);	// TODO Use an object lang from a language set into $website object instead of backoffice
}
// Define $websitepage if we have $websitepagefile defined
if (! $pageid && ! empty($websitepagefile))
{
	$pageid = str_replace(array('.tpl.php', 'page'), array('', ''), basename($websitepagefile));
	if ($pageid == 'index.php') $pageid = $website->fk_default_home;
}
if (! is_object($websitepage))
{
    $websitepage=new WebsitePage($db);
}
if ($pageid > 0)
{
	$websitepage->fetch($pageid);
}

// A lang was forced, so we change weblangs init
if (GETPOST('l', 'aZ09')) $weblangs->setDefaultLang(GETPOST('l', 'aZ09'));
// A lang was forced, so we check to find if we must make a redirect on translation page
if ($_SERVER['PHP_SELF'] != DOL_URL_ROOT.'/website/index.php')	// If we browsing page using Dolibarr server or a Native web server
{
	//print_r(get_defined_constants(true));exit;
	if (GETPOST('l', 'aZ09'))
	{
		$sql ="SELECT wp.rowid, wp.lang, wp.pageurl, wp.fk_page";
		$sql.=" FROM ".MAIN_DB_PREFIX."website_page as wp";
		$sql.=" WHERE wp.fk_website = ".$website->id;
		$sql.=" AND (wp.fk_page = ".$pageid." OR wp.rowid  = ".$pageid;
		if (is_object($websitepage) && $websitepage->fk_page > 0) $sql.=" OR wp.fk_page = ".$websitepage->fk_page." OR wp.rowid = ".$websitepage->fk_page;
		$sql.=")";
		$sql.= " AND wp.lang = '".$db->escape(GETPOST('l', 'aZ09'))."'";

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				$newpageid = $obj->rowid;
				if ($newpageid != $pageid) 		// To avoid to make a redirect on same page (infinite loop)
				{
					if (defined('USEDOLIBARRSERVER')) {
						header("Location: ".DOL_URL_ROOT.'/public/website/index.php?website='.$websitekey.'&pageid='.$newpageid.'&l='.GETPOST('l', 'aZ09'));
						exit;
					}
					else
					{
						$newpageref = $obj->pageurl;
						header("Location: ".$newpageref.'.php?l='.GETPOST('l', 'aZ09'));
						exit;
					}
				}
			}
		}
	}
}

// Show off line message
if (! defined('USEDOLIBARREDITOR') && empty($website->status))
{
	$weblangs->load("website");
	http_response_code(503);
	print '<center><br><br>'.$weblangs->trans("SorryWebsiteIsCurrentlyOffLine").'</center>';
	exit;
}
