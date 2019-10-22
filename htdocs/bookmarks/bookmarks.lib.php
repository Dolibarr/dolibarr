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

	$ret = '<div class="menu_top"></div>'."\n";

	if (! empty($conf->use_javascript_ajax)) {		// Bookmark autosubmit can't work when javascript is off.

		require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';
		if (! isset($conf->global->BOOKMARKS_SHOW_IN_MENU)) $conf->global->BOOKMARKS_SHOW_IN_MENU=5;

		$langs->load("bookmarks");

		$url= $_SERVER["PHP_SELF"];

		if (! empty($_SERVER["QUERY_STRING"]))
		{
		    $url.=(dol_escape_htmltag($_SERVER["QUERY_STRING"])?'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]):'');
		}
		else
		{
		    global $sortfield,$sortorder;
		    $tmpurl='';
		    // No urlencode, all param $url will be urlencoded later
		    if ($sortfield) $tmpurl.=($tmpurl?'&':'').'sortfield='.$sortfield;
		    if ($sortorder) $tmpurl.=($tmpurl?'&':'').'sortorder='.$sortorder;
		    if (is_array($_POST))
		    {
	    	    foreach($_POST as $key => $val)
	    	    {
	                if (preg_match('/^search_/', $key) && $val != '') $tmpurl.=($tmpurl?'&':'').$key.'='.$val;
	    	    }
		    }
		    $url.=($tmpurl?'?'.$tmpurl:'');
		}

		// Menu bookmark
		$ret = '<div class="menu_top"></div>'."\n";

		$ret.= '<!-- form with POST method by default, will be replaced with GET for external link by js -->'."\n";
		$ret.= '<form id="actionbookmark" name="actionbookmark" method="POST" action="">';
        $ret.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$ret.= '<select name="bookmark" id="boxbookmark" class="flat boxcombo vmenusearchselectcombo" alt="Bookmarks">';
		$ret.= '<option hidden value="listbookmarks" class="optiongrey" selected rel="'.DOL_URL_ROOT.'/bookmarks/list.php">'.$langs->trans('Bookmarks').'</option>';
	    $ret.= '<option value="listbookmark" class="optionblue" rel="'.dol_escape_htmltag(DOL_URL_ROOT.'/bookmarks/list.php').'" ';
	    $ret.= ' data-html="'.dol_escape_htmltag('<span class="fa fa-star-o"></span> '.dol_escape_htmltag($user->rights->bookmark->creer ? $langs->trans('EditBookmarks') : $langs->trans('ListOfBookmarks')).'...').'">';
	    $ret.= dol_escape_htmltag($user->rights->bookmark->creer ? $langs->trans('EditBookmarks') : $langs->trans('ListOfBookmarks')).'...</option>';
		// Url to go on create new bookmark page
		if (! empty($user->rights->bookmark->creer))
		{
	    	//$urltoadd=DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;urlsource='.urlencode($url).'&amp;url='.urlencode($url);
		    $urltoadd=DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;url='.urlencode($url);
	    	$ret.= '<option value="newbookmark" class="optionblue" rel="'.dol_escape_htmltag($urltoadd).'"';
	    	$ret.= ' data-html="'.dol_escape_htmltag('<span class="fa fa-star-o"></span> '.$langs->trans('AddThisPageToBookmarks').'...').'">'.dol_escape_htmltag($langs->trans('AddThisPageToBookmarks').'...').'</option>';
		}
		// Menu with all bookmarks
		if (! empty($conf->global->BOOKMARKS_SHOW_IN_MENU))
		{
			$sql = "SELECT rowid, title, url, target FROM ".MAIN_DB_PREFIX."bookmark";
			$sql.= " WHERE (fk_user = ".$user->id." OR fk_user is NULL OR fk_user = 0)";
	        $sql.= " AND entity IN (".getEntity('bookmarks').")";
			$sql.= " ORDER BY position";
			if ($resql = $db->query($sql) )
			{
				$i=0;
				while ($i < $conf->global->BOOKMARKS_SHOW_IN_MENU && $obj = $db->fetch_object($resql))
				{
				    $ret.='<option name="bookmark'.$obj->rowid.'" value="'.$obj->rowid.'" '.($obj->target == 1?' target="_blank"':'').' rel="'.dol_escape_htmltag($obj->url).'"';
				    //$ret.=' data-html="'.dol_escape_htmltag('<span class="fa fa-print"></span> '.$obj->title).'"';
				    $ret.='>';
				    $ret.=dol_escape_htmltag($obj->title);
				    $ret.='</option>';
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

		$ret.='<script>
	        	$(document).ready(function () {';
		$ret.='    jQuery("#boxbookmark").change(function() {
		            var urlselected = jQuery("#boxbookmark option:selected").attr("rel");
					if (! urlselected) console.log("Error, failed to get the URL to jump to from the rel attribute");
		            var urltarget = jQuery("#boxbookmark option:selected").attr("target");
		            if (! urltarget) { urltarget=""; }
	                jQuery("form#actionbookmark").attr("target",urltarget);
		            jQuery("form#actionbookmark").attr("action",urlselected);

		            console.log("We change select bookmark. We choose urlselected="+urlselected+" with target="+urltarget);

		            // Method is POST for internal link, GET for external
		            if (urlselected.startsWith(\'http\'))
		            {
		                var newmethod=\'GET\';
		                jQuery("form#actionbookmark").attr("method", newmethod);
		                console.log("We change method to newmethod="+newmethod);
			            jQuery("#actionbookmark").submit();
		                console.log("We restore method to POST");
						jQuery("form#actionbookmark").attr("method", \'POST\');
					}
					else
					{
		            	jQuery("#actionbookmark").submit();
					}
		       });';
		$ret.='})</script>';
	}

	$ret.= '<div class="menu_end"></div>'."\n";

	return $ret;
}
