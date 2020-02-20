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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/bookmarks/bookmarks.lib.php
 *	\ingroup	bookmarks
 *	\brief      File with library for bookmark module
 */


/**
 * Add area with bookmarks in top menu
 *
 * @return	string
 */
function printDropdownBookmarksList()
{
    global $conf, $user, $db, $langs;

    require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';

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

    $searchForm = '<!-- form with POST method by default, will be replaced with GET for external link by js -->'."\n";
    $searchForm.= '<form id="top-menu-action-bookmark" name="actionbookmark" method="POST" action="" onsubmit="return false" >';
    $searchForm.= '<input name="bookmark" id="top-bookmark-search-input" class="dropdown-search-input" placeholder="'.$langs->trans('Bookmarks').'" autocomplete="off" >';
    $searchForm.= '</form>';

    // Url to list bookmark
    $listbtn = '<a class="top-menu-dropdown-link" title="'.$langs->trans('AddThisPageToBookmarks').'" href="'.DOL_URL_ROOT.'/bookmarks/list.php" >';
    $listbtn.= '<span class="fa fa-list"></span> '.$langs->trans('Bookmarks').'</a>';

    // Url to go on create new bookmark page
    $newbtn = '';
    if (! empty($user->rights->bookmark->creer))
    {
        //$urltoadd=DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;urlsource='.urlencode($url).'&amp;url='.urlencode($url);
        $urltoadd=DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;url='.urlencode($url);
        $newbtn.= '<a class="top-menu-dropdown-link" title="'.$langs->trans('AddThisPageToBookmarks').'" href="'.dol_escape_htmltag($urltoadd).'" >';
        $newbtn.= img_picto('', 'bookmark').' '.dol_escape_htmltag($langs->trans('AddThisPageToBookmarks')).'</a>';
    }

    $bookmarkList='<div id="dropdown-bookmarks-list" >';
    // Menu with list of bookmarks
    $sql = "SELECT rowid, title, url, target FROM ".MAIN_DB_PREFIX."bookmark";
    $sql.= " WHERE (fk_user = ".$user->id." OR fk_user is NULL OR fk_user = 0)";
    $sql.= " AND entity IN (".getEntity('bookmarks').")";
    $sql.= " ORDER BY position";
    if ($resql = $db->query($sql) )
    {
    	$i=0;
    	while (($conf->global->BOOKMARKS_SHOW_IN_MENU == 0 || $i < $conf->global->BOOKMARKS_SHOW_IN_MENU) && $obj = $db->fetch_object($resql))
    	{
    		$bookmarkList.='<a class="dropdown-item bookmark-item" id="bookmark-item-'.$obj->rowid.'" data-id="'.$obj->rowid.'" '.($obj->target == 1?' target="_blank"':'').' href="'.dol_escape_htmltag($obj->url).'" >';
    		$bookmarkList.= dol_escape_htmltag($obj->title);
    		$bookmarkList.='</a>';
    		$i++;
    	}
    }
    else
    {
    	dol_print_error($db);
    }
    $bookmarkList.='</div>';

    $html = '
        <!-- search input -->
        <div class="dropdown-header bookmark-header">
            ' . $searchForm . '
        </div>
        ';

    $html.= '
        <!-- Menu Body -->
        <div class="bookmark-body dropdown-body">
        '.$bookmarkList.'
        </div>
        ';

    $html.= '
        <!-- Menu Footer-->
        <div class="bookmark-footer">
                '.$newbtn.$listbtn.'
            <div style="clear:both;"></div>
        </div>
    ';

    $html .= '<script>
            $( document ).on("keyup", "#top-bookmark-search-input", function () {

                var filter = $(this).val(), count = 0;
                $("#dropdown-bookmarks-list .bookmark-item").each(function () {

                    if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                        $(this).addClass("hidden-search-result");
                    } else {
                        $(this).removeClass("hidden-search-result");
                        count++;
                    }
                });
                $("#top-bookmark-search-filter-count").text(count);
            });
		    </script>';

    return $html;
}
