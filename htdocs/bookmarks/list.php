<?php
/* Copyright (C) 2005-2012 Laurent Destailleur       <eldy@users.sourceforge.net>
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
 *       \file       htdocs/bookmarks/list.php
 *       \brief      Page to display list of bookmarks
 *       \ingroup    bookmark
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bookmarks', 'admin'));

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

// Security check
if (! $user->rights->bookmark->lire) {
	restrictedArea($user, 'bookmarks');
}
$optioncss = GETPOST('optioncss','alpha');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='position';
if (! $sortorder) $sortorder='ASC';

$id = GETPOST("id",'int');


/*
 * Actions
 */

if ($action == 'delete')
{
	$bookmark=new Bookmark($db);
	$res=$bookmark->remove($id);
	if ($res > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		setEventMessages($bookmark->error, $bookmark->errors, 'errors');
	}
}


/*
 * View
 */

$userstatic=new User($db);

llxHeader('', $langs->trans("ListOfBookmarks"));

$newcardbutton='';
if ($user->rights->bookmark->creer)
{
	$newcardbutton='<a class="butActionNew" href="'.DOL_URL_ROOT.'/bookmarks/card.php?action=create"><span class="valignmiddle">'.$langs->trans('NewBookmark').'</span>';
	$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
	$newcardbutton.= '</a>';
}

print_barre_liste($langs->trans("ListOfBookmarks"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', -1, '', 'title_generic.png', 0, $newcardbutton);

$sql = "SELECT b.rowid, b.dateb, b.fk_user, b.url, b.target, b.title, b.favicon, b.position,";
$sql.= " u.login, u.lastname, u.firstname";
$sql.= " FROM ".MAIN_DB_PREFIX."bookmark as b LEFT JOIN ".MAIN_DB_PREFIX."user as u ON b.fk_user=u.rowid";
$sql.= " WHERE 1=1";
$sql.= " AND b.entity = ".$conf->entity;
if (! $user->admin) $sql.= " AND (b.fk_user = ".$user->id." OR b.fk_user is NULL OR b.fk_user = 0)";
$sql.= $db->order($sortfield.", position",$sortorder);
$sql.= $db->plimit($limit, $offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$param = "";
	if ($optioncss != '') $param ='&optioncss='.$optioncss;

	$moreforfilter='';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print "<tr class=\"liste_titre\">";
	//print "<td>&nbsp;</td>";
	print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"b.rowid","", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre("Title",$_SERVER["PHP_SELF"],"b.title","", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre("Link",$_SERVER["PHP_SELF"],"b.url","", $param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre("Target",'','','','','align="center"');
	print_liste_field_titre("Owner",$_SERVER["PHP_SELF"],"u.lastname","", $param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("Date",$_SERVER["PHP_SELF"],"b.dateb","", $param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("Position",$_SERVER["PHP_SELF"],"b.position","", $param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		// Id
		print '<td align="left">';
		print "<a href=\"card.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowBookmark"),"bookmark").' '.$obj->rowid."</a>";
		print '</td>';

		$linkintern=0;
		$title=$obj->title;
		$link=$obj->url;

		// Title
		print "<td>";
		$linkintern=1;
		if ($linkintern) print "<a href=\"".$obj->url."\">";
		print $title;
		if ($linkintern) print "</a>";
		print "</td>\n";

		// Url
		print '<td class="tdoverflowmax200">';
		if (! $linkintern) print '<a href="'.$obj->url.'"'.($obj->target?' target="newlink"':'').'>';
		print $link;
		if (! $linkintern) print '</a>';
		print "</td>\n";

		// Target
		print '<td align="center">';
		if ($obj->target == 0) print $langs->trans("BookmarkTargetReplaceWindowShort");
		if ($obj->target == 1) print $langs->trans("BookmarkTargetNewWindowShort");
		print "</td>\n";

		// Author
		print '<td align="center">';
		if ($obj->fk_user)
		{
			$userstatic->id=$obj->fk_user;
			$userstatic->lastname=$obj->login;
			print $userstatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("Public");
		}
		print "</td>\n";

		// Date creation
		print '<td align="center">'.dol_print_date($db->jdate($obj->dateb),'day')."</td>";

		// Position
		print '<td align="right">'.$obj->position."</td>";

		// Actions
		print '<td align="right" class="nowrap">';
		if ($user->rights->bookmark->creer)
		{
			print "<a href=\"".DOL_URL_ROOT."/bookmarks/card.php?action=edit&id=".$obj->rowid."&backtopage=".urlencode($_SERVER["PHP_SELF"])."\">".img_edit()."</a> ";
		}
		if ($user->rights->bookmark->supprimer)
		{
			print "<a href=\"".$_SERVER["PHP_SELF"]."?action=delete&id=$obj->rowid\">".img_delete()."</a>";
		}
		else
		{
			print "&nbsp;";
		}
		print "</td>";
		print "</tr>\n";
		$i++;
	}
	print "</table>";
	print '</div>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();


