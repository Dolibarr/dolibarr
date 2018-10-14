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
// Define $website and $weblangs
if (! is_object($website))
{
	$website=new Website($db);
	$website->fetch(0,$websitekey);
}
if (! is_object($weblangs))
{
	$weblangs = dol_clone($langs);
}

// A lang was forced, so we change weblangs init
if (GETPOST('l','aZ09')) $weblangs->setDefaultLang(GETPOST('l','aZ09'));
// A lang was forced, so we check to find if we must make a redirect on translation page
if (! defined('USEDOLIBARREDITOR'))
{
	if (GETPOST('l','aZ09'))
	{
		$sql ="SELECT wp.rowid, wp.lang, wp.pageurl, wp.fk_page";
		$sql.=" FROM ".MAIN_DB_PREFIX."website_page as wp, ".MAIN_DB_PREFIX."website as w";
		$sql.=" WHERE w.rowid = wp.fk_website AND w.ref = '".$db->escape($websitekey)."' AND fk_page = '".$db->escape($pageid)."' AND lang = '".$db->escape(GETPOST('l','aZ09'))."'";
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				//$pageid = $obj->rowid;
				//$pageref = $obj->pageurl;
				if (! defined('USEDOLIBARRSERVER')) {
					// TODO Redirect
				}
				else
				{
					// TODO Redirect
				}
			}
		}
	}
}

// Load websitepage class
include_once DOL_DOCUMENT_ROOT.'/website/class/websitepage.class.php';
