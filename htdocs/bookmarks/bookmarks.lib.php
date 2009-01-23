<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/bookmarks.lib.php
 *	\ingroup	bookmarks
 *	\brief      File with library for bookmark module
 *	\version    $Id$
 */

function printBookmarksList ($aDb, $aLangs)
{
	global $conf;

	$db = $aDb;
	$langs = $aLangs;

	require_once(DOL_DOCUMENT_ROOT."/bookmarks/bookmark.class.php");

	$bookm = new Bookmark($db);

	$langs->load("bookmarks");

	$url= $_SERVER["PHP_SELF"].(! empty($_SERVER["QUERY_STRING"])?'?'.$_SERVER["QUERY_STRING"]:'');

	$ret = '';
	// Menu bookmark
	$ret.= '<div class="menu_titre">';
	$ret.= '<table class="nobordernopadding" width="100%"><tr class="no"><td>';
	$ret.= '<a class="vmenu" href="'.DOL_URL_ROOT.'/bookmarks/liste.php">'.$langs->trans('Bookm').'</a>';
//	$ret.='</div>';
	// Menu New bookmark
//	$ret.= '<div class="menu_contenu">';
	$ret.= '</td><td align="right">';
	$ret.= '<a class="vsmenu" href="'.DOL_URL_ROOT.'/bookmarks/fiche.php?action=create&amp;urlsource='.urlencode($url).'&amp;url='.urlencode($url).'">';
//	$ret.= $langs->trans('NewBookmark');
	$ret.=img_object($langs->trans('AddThisPageToBookmarks'),'bookmark');
	$ret.= '</a>';
	$ret.= '</td></tr></table>';
	$ret.= '</div>';
	// Menu with all bookmarks
	if (! empty($conf->global->BOOKMARKS_SHOW_IN_MENU))
	{
		$sql = "SELECT rowid, title, url FROM ".MAIN_DB_PREFIX."bookmark";
		if ( $resql = $db->query($sql) ) {
			while ( $obj = $db->fetch_object($resql) ) {
				$ret.='<div class="menu_contenu"><a class="vsmenu" title="'.$obj->title.'" href="'.$obj->url.'">';
				$ret.=' '.img_object($langs->trans("BookmarkThisPage"),'bookmark').' ';
				$ret.= dolibarr_trunc($obj->title, 30).'</a><br></div>';
			}

		} else {

			dolibarr_print_error($db);
		}
	}

	$ret .= '<div class="menu_fin"></div>';

	return $ret;
}

?>
