<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/bookmarks/bookmarks.lib.php
 *	\ingroup	bookmarks
 *	\brief      File with library for bookmark module
 */

/**
 * Add area with bookmarks in menu
 *
 * @param 	DoliDb  	$aDb		Database handler
 * @param 	Translate	$aLangs		Object lang
 * @return	string
 */
function printBookmarksList($aDb, $aLangs)
{
	global $conf, $user;

	$db = $aDb;
	$langs = $aLangs;

	require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';
	if (! isset($conf->global->BOOKMARKS_SHOW_IN_MENU)) $conf->global->BOOKMARKS_SHOW_IN_MENU=5;

	$langs->load("bookmarks");

	$url= $_SERVER["PHP_SELF"].(! empty($_SERVER["QUERY_STRING"])?'?'.$_SERVER["QUERY_STRING"]:'');
    // TODO Add post param to $url
    
	$ret = '';
	
	// Menu bookmark
	/*
	$ret.= '<div class="menu_titre">';
	$ret.= '<table class="nobordernopadding" width="100%" summary="bookmarkstable"><tr><td>';
	$ret.= '<a class="vmenu" href="'.DOL_URL_ROOT.'/bookmarks/list.php">'.$langs->trans('Bookmarks').'</a>';
	$ret.= '</td><td align="right">';
	if ($user->rights->bookmark->creer)
	{
		$ret.= '<a class="vsmenu addbookmarkpicto" href="'.DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;urlsource='.urlencode($url).'&amp;url='.urlencode($url).'">';
		$ret.=img_object($langs->trans('AddThisPageToBookmarks'),'bookmark');
		$ret.= '</a>';
	}
	$ret.= '</td></tr></table>';
	$ret.= '</div>';
    */
	$ret.= '<div class="menu_top"></div>'."\n";

	$ret.= '<form id="actionbookmark" name="actionbookmark" method="POST" action="">';
	$ret.= '<select name="bookmark" id="boxbookmark" class="flat boxcombo vmenusearchselectcombo">';
	$ret.= '<option hidden value="listbookmarks" class="optiongrey" selected rel="'.DOL_URL_ROOT.'/bookmarks/list.php">'.$langs->trans('Bookmarks').'</option>';
    $ret.= '<option value="listbookmark" class="optionblue" rel="'.dol_escape_js(DOL_URL_ROOT.'/bookmarks/list.php').'">'.dol_escape_htmltag($user->rights->bookmark->creer ? $langs->trans('EditBookmarks') : $langs->trans('ListOfBookmarks')).'...</option>';
	if ($user->rights->bookmark->creer)
	{
    	$urltoadd=DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;urlsource='.urlencode($url).'&amp;url='.urlencode($url);
    	$ret.= '<option value="newbookmark" class="optionblue" rel="'.dol_escape_js($urltoadd).'">'.dol_escape_htmltag($langs->trans('AddThisPageToBookmarks')).'...</option>';
	}
	// Menu with all bookmarks
	if (! empty($conf->global->BOOKMARKS_SHOW_IN_MENU))
	{
		$sql = "SELECT rowid, title, url, target FROM ".MAIN_DB_PREFIX."bookmark";
		$sql.= " WHERE (fk_user = ".$user->id." OR fk_user is NULL OR fk_user = 0)";
        $sql.= " AND entity = ".$conf->entity;
		$sql.= " ORDER BY position";
		if ($resql = $db->query($sql) )
		{
			$i=0;
			while ($i < $conf->global->BOOKMARKS_SHOW_IN_MENU && $obj = $db->fetch_object($resql))
			{
				//$ret.='<div class="menu_contenu">';
			    $ret.='<option name="bookmark'.$obj->rowid.'" value="'.$obj->rowid.'" '.($obj->target == 1?' target="_blank"':'').' rel="'.dol_escape_js($obj->url).'">'.img_picto('','object_bookmark').' '.$obj->title.'</option>';
				/*$ret.='<a class="vsmenu" title="'.$obj->title.'" href="'.$obj->url.'"'.($obj->target == 1?' target="_blank"':'').'>';
				if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $ret.=' '.img_object('','bookmark').' ';
				$ret.= dol_trunc($obj->title, 20).'</a><br>';*/
				//$ret.='</div>';
				$i++;
			}
		}
		else
		{
			dol_print_error($db);
		}
	}
	
	$ret.= '</select>';
	$ret.= '</form>';
	
	$ret.=ajax_combobox('boxbookmark');
	
	$ret.='<script type="text/javascript">
        	$(document).ready(function () {';
	$ret.='    jQuery("#boxbookmark").change(function() {
	            var urlselected = jQuery("#boxbookmark option:selected").attr("rel");
	            var urltarget = jQuery("#boxbookmark option:selected").attr("target");
	            if (! urltarget) { urltarget=""; }
	            console.log("We change select bookmark. We choose "+urlselected+" with target "+urltarget);
                jQuery("form#actionbookmark").attr("target",urltarget);
	            jQuery("form#actionbookmark").attr("action",urlselected);
	            jQuery("#actionbookmark").submit();
	       });';
	$ret.='})</script>';
	$ret .= '<div class="menu_end"></div>';

	return $ret;
}

