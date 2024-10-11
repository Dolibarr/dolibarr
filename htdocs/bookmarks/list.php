<?php
/* Copyright (C) 2005-2022	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *    \file       htdocs/bookmarks/list.php
 *    \ingroup    bookmark
 *    \brief      Page to display list of bookmarks
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bookmarks', 'admin'));

// Get Parameters
$action 	= GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm 	= GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect 	= GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bookmarklist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$optioncss 	= GETPOST('optioncss', 'alpha');
$mode 		= GETPOST('mode', 'aZ');

$id = GETPOSTINT("id");
$search_title = GETPOST('search_title', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'b.position';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

// Initialize Objects
$object = new Bookmark($db);
$extrafields = new ExtraFields($db);
$arrayfields = array();
$hookmanager->initHooks(array('bookmarklist')); // Note that conf->hooks_modules contains array

if ($id > 0) {
	$object->fetch($id);
}

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Security check
restrictedArea($user, 'bookmark', $object);

// Permissions
$permissiontoread = $user->hasRight('bookmark', 'lire');
$permissiontoadd = $user->hasRight('bookmark', 'creer');
$permissiontodelete = ($user->hasRight('bookmark', 'supprimer') || ($permissiontoadd && $object->fk_user == $user->id));


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Bookmark';
	$objectlabel = 'Bookmark';
	$uploaddir = $conf->bookmark->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($action == 'delete' && $permissiontodelete) {
		$object->fetch($id);
		$res = $object->delete($user);
		if ($res > 0) {
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();

//$help_url = "EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url = '';
$title = $langs->trans("Bookmarks");
$morejs = array();
$morecss = array();


// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT b.rowid, b.dateb, b.fk_user, b.url, b.target, b.title, b.favicon, b.position,";
$sql .= " u.login, u.lastname, u.firstname";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as b LEFT JOIN ".MAIN_DB_PREFIX."user as u ON b.fk_user=u.rowid";
$sql .= " WHERE 1=1";
if ($search_title) {
	$sql .= natural_search('title', $search_title);
}
$sql .= " AND b.entity IN (".getEntity('bookmark').")";
if (!$user->admin) {
	$sql .= " AND (b.fk_user = ".((int) $user->id)." OR b.fk_user is NULL OR b.fk_user = 0)";
}

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield.", position", $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, '', '', 0, 0, '', '', '', 'bodyforlist mod-bookmarks page-list');

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/bookmarks/card.php?action=create&backtopage='.urlencode(DOL_URL_ROOT.'/bookmarks/list.php'), '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'bookmark', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendBookmarkRef";
$modelmail = "bookmark";
$objecttmp = new Bookmark($db);
$trackid = 'bookmark'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

$moreforfilter = '';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
// TODO

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "b.rowid", "", $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Title", $_SERVER["PHP_SELF"], "b.title", "", $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Link", $_SERVER["PHP_SELF"], "b.url", "", $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Target", $_SERVER["PHP_SELF"], "b.target", "", $param, '', $sortfield, $sortorder, 'center ');
$totalarray['nbfield']++;
print_liste_field_titre("Visibility", $_SERVER["PHP_SELF"], "u.lastname", "", $param, '', $sortfield, $sortorder, 'center ');
$totalarray['nbfield']++;
print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "b.dateb", "", $param, '', $sortfield, $sortorder, 'center ');
$totalarray['nbfield']++;
print_liste_field_titre("Position", $_SERVER["PHP_SELF"], "b.position", "", $param, '', $sortfield, $sortorder, 'right ');
$totalarray['nbfield']++;
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$object->id = $obj->rowid;
	$object->ref = $obj->rowid;

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container">';
		}
		// Output Kanban
		$selected = -1;
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		print $object->getKanbanView('', array('selected' => $selected));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show here line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Id
		print '<td class="nowraponall">';
		print $object->getNomUrl(1);
		print '</td>';

		$linkintern = 1;
		if (preg_match('/^http/i', $obj->url)) {
			$linkintern = 0;
		}
		$title      = $obj->title;
		$link       = $obj->url;
		$canedit    = $permissiontoadd;
		$candelete  = $permissiontodelete;

		// Title
		print '<td class="tdoverflowmax200" alt="'.dol_escape_htmltag($title).'">';
		print dol_escape_htmltag($title);
		print "</td>\n";

		// Url
		print '<td class="tdoverflowmax200">';
		if (empty($linkintern)) {
			print img_picto('', 'url', 'class="pictofixedwidth"');
			print '<a class="" href="'.$obj->url.'"'.($obj->target ? ' target="newlink" rel="noopener"' : '').'>';
		} else {
			//print img_picto('', 'rightarrow', 'class="pictofixedwidth"');
			print '<a class="" href="'.$obj->url.'">';
		}
		print $link;
		print '</a>';
		print "</td>\n";

		// Target
		print '<td class="tdoverflowmax100 center">';
		if ($obj->target == 0) {
			print $langs->trans("BookmarkTargetReplaceWindowShort");
		}
		if ($obj->target == 1) {
			print $langs->trans("BookmarkTargetNewWindowShort");
		}
		print "</td>\n";

		// Author
		print '<td class="tdoverflowmax100 center">';
		if ($obj->fk_user > 0) {
			if (empty($conf->cache['users'][$obj->fk_user])) {
				$tmpuser = new User($db);
				$tmpuser->fetch($obj->fk_user);
				$conf->cache['users'][$obj->fk_user] = $tmpuser;
			}
			$tmpuser = $conf->cache['users'][$obj->fk_user];
			print $tmpuser->getNomUrl(-1);
		} else {
			print '<span class="opacitymedium">'.$langs->trans("Everybody").'</span>';
			if (!$user->admin) {
				$candelete = false;
				$canedit = false;
			}
		}
		print "</td>\n";

		// Date creation
		print '<td class="center" title="'.dol_escape_htmltag(dol_print_date($db->jdate($obj->dateb), 'dayhour')).'">'.dol_print_date($db->jdate($obj->dateb), 'day')."</td>";

		// Position
		print '<td class="right">'.$obj->position."</td>";

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print "</tr>\n";
	}

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$savnbfield.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
