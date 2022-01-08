<?php
/* Copyright (C) 2005-2020 Laurent Destailleur       <eldy@users.sourceforge.net>
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
 *       \file       htdocs/bookmarks/list.php
 *       \brief      Page to display list of bookmarks
 *       \ingroup    bookmark
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bookmarks', 'admin'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'bookmarklist'; // To manage different context of search

// Security check
if (empty($user->rights->bookmark->lire)) {
	restrictedArea($user, 'bookmarks');
}
$optioncss = GETPOST('optioncss', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'position';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

$id = GETPOST("id", 'int');

$object = new Bookmark($db);

$permissiontoread = !empty($user->rights->bookmark->lire);
$permissiontoadd = !empty($user->rights->bookmark->creer);
$permissiontodelete = !empty($user->rights->bookmark->supprimer);


/*
 * Actions
 */

if ($action == 'delete') {
	$res = $object->remove($id);
	if ($res > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("ListOfBookmarks");

llxHeader('', $title);

$sql = "SELECT b.rowid, b.dateb, b.fk_user, b.url, b.target, b.title, b.favicon, b.position,";
$sql .= " u.login, u.lastname, u.firstname";
$sql .= " FROM ".MAIN_DB_PREFIX."bookmark as b LEFT JOIN ".MAIN_DB_PREFIX."user as u ON b.fk_user=u.rowid";
$sql .= " WHERE 1=1";
$sql .= " AND b.entity IN (".getEntity('bookmark').")";
if (!$user->admin) {
	$sql .= " AND (b.fk_user = ".((int) $user->id)." OR b.fk_user is NULL OR b.fk_user = 0)";
}

$sql .= $db->order($sortfield.", position", $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords) {
	$num = $nbtotalofrecords;
} else {
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

$param = "";
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if ($optioncss != '') {
	$param = '&optioncss='.urlencode($optioncss);
}

$moreforfilter = '';

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if ($permissiontodelete) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/bookmarks/card.php?action=create&backtopage='.urlencode(DOL_URL_ROOT.'/bookmarks/list.php'), '', !empty($user->rights->bookmark->creer));

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'bookmark', 0, $newcardbutton, '', $limit, 0, 0, 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre">';
//print "<td>&nbsp;</td>";
print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "b.rowid", "", $param, 'align="left"', $sortfield, $sortorder);
print_liste_field_titre("Title", $_SERVER["PHP_SELF"], "b.title", "", $param, 'align="left"', $sortfield, $sortorder);
print_liste_field_titre("Link", $_SERVER["PHP_SELF"], "b.url", "", $param, 'align="left"', $sortfield, $sortorder);
print_liste_field_titre("Target", '', '', '', '', 'align="center"');
print_liste_field_titre("Visibility", $_SERVER["PHP_SELF"], "u.lastname", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "b.dateb", "", $param, 'align="center"', $sortfield, $sortorder);
print_liste_field_titre("Position", $_SERVER["PHP_SELF"], "b.position", "", $param, 'class="right"', $sortfield, $sortorder);
print_liste_field_titre('');
print "</tr>\n";

$cacheOfUsers = array();

$i = 0;
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

	$object->id = $obj->rowid;
	$object->ref = $obj->rowid;

	print '<tr class="oddeven">';

	// Id
	print '<td class="nowraponall">';
	print $object->getNomUrl(1);
	print '</td>';

	$linkintern = 0;
	$title      = $obj->title;
	$link       = $obj->url;
	$canedit    = $user->rights->bookmark->supprimer;
	$candelete  = $user->rights->bookmark->creer;

	// Title
	print "<td>";
	$linkintern = 1;
	if ($linkintern) {
		print '<a href="'.$obj->url.'">';
	}
	print $title;
	if ($linkintern) {
		print "</a>";
	}
	print "</td>\n";

	// Url
	print '<td class="tdoverflowmax200">';
	if (!$linkintern) {
		print '<a href="'.$obj->url.'"'.($obj->target ? ' target="newlink" rel="noopener"' : '').'>';
	}
	print $link;
	if (!$linkintern) {
		print '</a>';
	}
	print "</td>\n";

	// Target
	print '<td class="center">';
	if ($obj->target == 0) {
		print $langs->trans("BookmarkTargetReplaceWindowShort");
	}
	if ($obj->target == 1) {
		print $langs->trans("BookmarkTargetNewWindowShort");
	}
	print "</td>\n";

	// Author
	print '<td class="center">';
	if ($obj->fk_user) {
		if (empty($cacheOfUsers[$obj->fk_user])) {
			$tmpuser = new User($db);
			$tmpuser->fetch($obj->fk_user);
			$cacheOfUsers[$obj->fk_user] = $tmpuser;
		}
		$tmpuser = $cacheOfUsers[$obj->fk_user];
		print $tmpuser->getNomUrl(1);
	} else {
		print '<span class="opacitymedium">'.$langs->trans("Everybody").'</span>';
		if (!$user->admin) {
			$candelete = false;
			$canedit = false;
		}
	}
	print "</td>\n";

	// Date creation
	print '<td class="center">'.dol_print_date($db->jdate($obj->dateb), 'day')."</td>";

	// Position
	print '<td class="right">'.$obj->position."</td>";

	// Actions
	print '<td class="nowraponall right">';
	if ($canedit) {
		print '<a class="editfielda marginleftonly" href="'.DOL_URL_ROOT.'/bookmarks/card.php?action=edit&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">'.img_edit()."</a>";
	}
	if ($candelete) {
		print '<a class="marginleftonly" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$obj->rowid.'">'.img_delete().'</a>';
	}
	print "</td>";
	print "</tr>\n";
	$i++;
}
print "</table>";
print '</div>';

$db->free($resql);


// End of page
llxFooter();
$db->close();
