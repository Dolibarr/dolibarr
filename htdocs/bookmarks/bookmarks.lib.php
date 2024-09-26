<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
	global $user, $db, $langs, $sortfield, $sortorder;

	require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

	$langs->load("bookmarks");

	$authorized_var=array('limit','optioncss','contextpage');
	$url = $_SERVER["PHP_SELF"];
	$url_param = array();
	if (!empty($_SERVER["QUERY_STRING"])) {
		if (is_array($_GET)) {	// Parse the original GET URL. So we must keep $_GET here.
			foreach ($_GET as $key => $val) {
				if (is_array($val)) {
					foreach ($val as $tmpsubval) {
						$url_param[] = http_build_query(array(dol_escape_htmltag($key).'[]' => dol_escape_htmltag($tmpsubval)));
					}
				} elseif ($val != '') {
					$url_param[$key] = http_build_query(array(dol_escape_htmltag($key) => dol_escape_htmltag($val)));
				}
			}
		}
	}

	$tmpurl = '';
	// No urlencode, all param $url will be urlencoded later
	if ($sortfield) {
		$tmpurl .= ($tmpurl ? '&' : '').'sortfield='.urlencode($sortfield);
	}
	if ($sortorder) {
		$tmpurl .= ($tmpurl ? '&' : '').'sortorder='.urlencode($sortorder);
	}
	if (!empty($_POST) && is_array($_POST)) {
		foreach ($_POST as $key => $val) {
			if ((preg_match('/^search_/', $key) || in_array($key, $authorized_var))
				&& $val != ''
				&& !array_key_exists($key, $url_param)) {
				if (is_array($val)) {
					foreach ($val as $tmpsubval) {
						$url_param[] = http_build_query(array(dol_escape_htmltag($key).'[]' => dol_escape_htmltag($tmpsubval)));
					}
				} elseif ($val != '') {
					$url_param[$key] = http_build_query(array(dol_escape_htmltag($key) => dol_escape_htmltag($val)));
				}
			}
		}
	}

	$url .= ($tmpurl ? '?'.$tmpurl : '');
	if (!empty($url_param)) {
		$url .= (strpos($url, '?') > 0 ? '&' : '?').implode('&', $url_param);
	}

	$searchForm = '<!-- form with POST method by default, will be replaced with GET for external link by js -->'."\n";
	$searchForm .= '<form id="top-menu-action-bookmark" name="actionbookmark" method="POST" action=""'.(!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? ' onsubmit="return false"' : '').'>';
	$searchForm .= '<input type="hidden" name="token" value="'.newToken().'">';

	// Url to go on create new bookmark page
	$newbtn = '';
	if ($user->hasRight('bookmark', 'creer')) {
		if (!preg_match('/bookmarks\/card.php/', $_SERVER['PHP_SELF'])) {
			//$urltoadd=DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;urlsource='.urlencode($url).'&amp;url='.urlencode($url);
			$urltoadd = DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;url='.urlencode($url);
			$newbtn .= '<a class="top-menu-dropdown-link" title="'.$langs->trans('AddThisPageToBookmarks').'" href="'.dol_escape_htmltag($urltoadd).'" >';
			$newbtn .= img_picto('', 'add', '', 0, 0, 0, '', 'paddingright').dol_escape_htmltag($langs->trans('AddThisPageToBookmarks')).'</a>';
		}
	}

	// Url to list/edit bookmark
	$listbtn = '<a class="top-menu-dropdown-link" title="'.dol_escape_htmltag($langs->trans('Bookmarks')).'" href="'.DOL_URL_ROOT.'/bookmarks/list.php">';
	$listbtn .= img_picto('', 'edit', 'class="paddingright opacitymedium"').$langs->trans('EditBookmarks').'</a>';

	$bookmarkList = '';
	$bookmarkNb = 0;
	// Menu with list of bookmarks
	$sql = "SELECT rowid, title, url, target FROM ".MAIN_DB_PREFIX."bookmark";
	$sql .= " WHERE (fk_user = ".((int) $user->id)." OR fk_user is NULL OR fk_user = 0)";
	$sql .= " AND entity IN (".getEntity('bookmarks').")";
	$sql .= " ORDER BY position";
	if ($resql = $db->query($sql)) {
		if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			$bookmarkList = '<div id="dropdown-bookmarks-list" >';
			$i = 0;
			while ((!getDolGlobalString('BOOKMARKS_SHOW_IN_MENU') || $i < getDolGlobalInt('BOOKMARKS_SHOW_IN_MENU')) && $obj = $db->fetch_object($resql)) {
				$bookmarkList .= '<a class="dropdown-item bookmark-item'.(strpos($obj->url, 'http') === 0 ? ' bookmark-item-external' : '').'" id="bookmark-item-'.$obj->rowid.'" data-id="'.$obj->rowid.'" '.($obj->target == 1 ? ' target="_blank"  rel="noopener noreferrer"' : '').' href="'.dol_escape_htmltag($obj->url).'" >';
				$bookmarkList .= dol_escape_htmltag($obj->title);
				$bookmarkList .= '</a>';
				$i++;
				$bookmarkNb++;
			}
			$bookmarkList .= '</div>';

			$searchForm .= '<input name="bookmark" id="top-bookmark-search-input" class="dropdown-search-input" placeholder="'.$langs->trans('Bookmarks').'" autocomplete="off" >';
		} else {
			$searchForm .= '<select name="bookmark" id="boxbookmark" class="topmenu-bookmark-dropdown .dropdown-toggle maxwidth100">';
			//$searchForm .= '<option>--'.$langs->trans("Bookmarks").'--</option>';
			$searchForm .= '<option hidden value="listbookmarks" class="optiongrey" selected rel="'.DOL_URL_ROOT.'/bookmarks/list.php">'.$langs->trans('Bookmarks').'</option>';
			$searchForm .= '<option value="listbookmark" class="optionblue" rel="'.dol_escape_htmltag(DOL_URL_ROOT.'/bookmarks/list.php').'" ';
			$searchForm .= ' data-html="'.dol_escape_htmltag(img_picto('', 'bookmark').' '.($user->hasRight('bookmark', 'creer') ? $langs->trans('EditBookmarks') : $langs->trans('ListOfBookmarks')).'...').'">';
			$searchForm .= dol_escape_htmltag($user->hasRight('bookmark', 'creer') ? $langs->trans('EditBookmarks') : $langs->trans('ListOfBookmarks')).'...</option>';
			// Url to go on create new bookmark page
			if ($user->hasRight('bookmark', 'creer')) {
				if (!preg_match('/bookmarks\/card.php/', $_SERVER['PHP_SELF'])) {
					$urltoadd = DOL_URL_ROOT.'/bookmarks/card.php?action=create&amp;url='.urlencode($url);
					$searchForm .= '<option value="newbookmark" class="optionblue" rel="'.dol_escape_htmltag($urltoadd).'"';
					$searchForm .= ' data-html="'.dol_escape_htmltag(img_picto('', 'bookmark').' '.$langs->trans('AddThisPageToBookmarks').'...').'">'.dol_escape_htmltag($langs->trans('AddThisPageToBookmarks').'...').'</option>';
				}
			}
			$i = 0;
			while ((!getDolGlobalString('BOOKMARKS_SHOW_IN_MENU') || $i < getDolGlobalInt('BOOKMARKS_SHOW_IN_MENU')) && $obj = $db->fetch_object($resql)) {
				$searchForm .= '<option name="bookmark'.$obj->rowid.'" value="'.$obj->rowid.'" '.($obj->target == 1 ? ' target="_blank" rel="noopener noreferrer"' : '').' rel="'.dol_escape_htmltag($obj->url).'" >';
				$searchForm .= dol_escape_htmltag($obj->title);
				$searchForm .= '</option>';
				$i++;
				$bookmarkNb++;
			}
			$searchForm .= '</select>';
		}
	} else {
		dol_print_error($db);
	}

	$searchForm .= '</form>';

	// Generate the return string
	if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		$html = $searchForm;

		$html .= '<!-- script to open selected choice -->
				<script>
				$(document).ready(function () {
					jQuery("#boxbookmark").change(function() {
						var urlselected = jQuery("#boxbookmark option:selected").attr("rel");
						if (! urlselected) console.log("Error, failed to get the URL to jump to from the rel attribute");
						var urltarget = jQuery("#boxbookmark option:selected").attr("target");
						if (! urltarget) { urltarget=""; }
						jQuery("form#top-menu-action-bookmark").attr("target",urltarget);
						jQuery("form#top-menu-action-bookmark").attr("action",urlselected);

						console.log("We change select bookmark. We choose urlselected="+urlselected+" with target="+urltarget);

						// Method is POST for internal link, GET for external
						if (urlselected.startsWith(\'http\'))
						{
							var newmethod=\'GET\';
							jQuery("form#top-menu-action-bookmark").attr("method", newmethod);
							console.log("We change method to newmethod="+newmethod);
							jQuery("form#top-menu-action-bookmark").submit();
							console.log("We restore method to POST");
							jQuery("form#top-menu-action-bookmark").attr("method", \'POST\');
						}
						else
						{
							jQuery("form#top-menu-action-bookmark").submit();
						}
			   		});
				})
				</script>';
	} else {
		$html = '
			<!-- search input -->
			<div class="dropdown-header bookmark-header">
				' . $searchForm.'
			</div>
			';

		$html .= '
			<!-- Menu bookmark tools-->
			<div class="bookmark-footer">
					'.$newbtn.$listbtn.'
				<div class="clearboth"></div>
			</div>
		';

		$html .= '
				<!-- Menu Body bookmarks -->
				<div class="bookmark-body dropdown-body">'.$bookmarkList.'
				<span id="top-bookmark-search-nothing-found" class="'.($bookmarkNb ? 'hidden-search-result ' : '').'opacitymedium">'.dol_escape_htmltag($langs->trans("NoBookmarkFound")).'</span>
				</div>
				';

		$html .= '<!-- script to open/close the popup -->
				<script>
				jQuery(document).on("keyup", "#top-bookmark-search-input", function () {
					console.log("keyup in bookmark search input");

					var filter = $(this).val(), count = 0;
					jQuery("#dropdown-bookmarks-list .bookmark-item").each(function () {
						if ($(this).text().search(new RegExp(filter, "i")) < 0) {
							$(this).addClass("hidden-search-result");
						} else {
							$(this).removeClass("hidden-search-result");
							count++;
						}
					});
					jQuery("#top-bookmark-search-filter-count").text(count);
					if (count == 0) {
						jQuery("#top-bookmark-search-nothing-found").removeClass("hidden-search-result");
					} else {
						jQuery("#top-bookmark-search-nothing-found").addClass("hidden-search-result");
					}
				});
				</script>';
	}

	return $html;
}
