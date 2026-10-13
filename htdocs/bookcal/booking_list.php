<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2023-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/bookcal/booking_list.php
 *	\ingroup    bookcal
 *	\brief      Management of direct debit order or credit transfer of invoices
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/lib/bookcal_calendar.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/class/calendar.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


// Load translation files required by the page
$langs->loadLangs(array("agenda", "other", "projects"));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$type = GETPOST('type', 'aZ09');
$lineid = GETPOSTINT('lineid');

$contextpage = 'bookcalbookinglist';
$canreadprojects = isModEnabled('project') && $user->hasRight('projet', 'lire');
$arrayfields = array(
	'projectlist.ref' => array('label' => 'RefProject', 'langfile' => 'projects', 'checked' => '0', 'enabled' => $canreadprojects, 'position' => 60),
	'projectlist.title' => array('label' => 'ProjectLabel', 'langfile' => 'projects', 'checked' => '0', 'enabled' => $canreadprojects, 'position' => 61),
);
$search_projectlist_ref = GETPOST('search_projectlist_ref', 'alphanohtml');
$search_projectlist_title = GETPOST('search_projectlist_title', 'alphanohtml');
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$search_projectlist_ref = '';
	$search_projectlist_title = '';
}
$limit = max(1, GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit);
$page = max(0, GETPOSTISSET('pageplusone') ? GETPOSTINT('pageplusone') - 1 : GETPOSTINT('page'));
if (GETPOST('button_search', 'alpha') || GETPOST('button_search_x', 'alpha') || GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;
$sortfield = GETPOST('sortfield', 'aZ09comma');
if (!in_array($sortfield, array('ac.datep', 'projectlist.ref', 'projectlist.title'))) {
	$sortfield = 'ac.datep';
}
$sortorder = GETPOST('sortorder', 'aZ09');
if (!in_array($sortorder, array('ASC', 'DESC'))) {
	$sortorder = 'ASC';
}
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

$fieldid = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

$moreparam = '';

$object = new Calendar($db);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	$isdraft = (($object->status == Calendar::STATUS_DRAFT) ? 1 : 0);
	if ($ret > 0) {
		$object->fetch_thirdparty();
	}
}

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
	$permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('bookcal', 'calendar', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

if (!isModEnabled("bookcal")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}

/*
 * Actions
 */

$parameters = array();
$helpurl = '';
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();
$title = $langs->trans('Calendar')." - ".$langs->trans('Bookings');

llxHeader('', $title, $helpurl, '', 0, 0, '', '', '', 'mod-bookcal page-list');


if ($object->id > 0) {
	$head = calendarPrepareHead($object);

	print dol_get_fiche_head($head, 'booking', $langs->trans("Calendar"), -1, $object->picto);

	$formconfirm = '';

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/bookcal/calendar_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	// Link to public page
	print '<tr><td>Link</td>';
	print '<td><a href="'. DOL_URL_ROOT.'/public/bookcal/index.php?id='.$object->id.'" target="_blank">Public page</a>';
	print '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Bookings
	 */

	$projectforlist = new Project($db);
	$projectsforlist = '0';
	if ($canreadprojects) {
		$projectsforlist = $projectforlist->getProjectsAuthorizedForUser($user, $user->hasRight('projet', 'all', 'lire') ? 2 : 0, 1, (int) $user->socid);
		if (!is_string($projectsforlist)) {
			$projectsforlist = '0';
		}
	}
	$sql = "SELECT ac.id, ac.ref, ac.datep as date_start, ac.datep2 as date_end, ac.label, acr.fk_element as elementid";
	$sql .= ', projectlist.rowid as projectlist_id, projectlist.ref as projectlist_ref, projectlist.title as projectlist_title, projectlist.public as projectlist_public, projectlist.fk_statut as projectlist_status';
	$sqlfields = $sql;
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as ac";
	$sql .= " JOIN ".MAIN_DB_PREFIX."actioncomm_resources as acr on acr.fk_actioncomm = ac.id";
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as projectlist ON projectlist.rowid = ac.fk_project AND projectlist.rowid IN ('.$db->sanitize($projectsforlist).')';
	$sql .= " WHERE ac.fk_bookcal_calendar = ".((int) $object->id);
	$sql .= " AND ac.entity IN (".getEntity('agenda').")";
	$sql .= " AND ac.code = 'AC_RDV'";
	$sql .= " AND acr.element_type = 'socpeople'";
	if ($search_projectlist_ref !== '') {
		$sql .= natural_search('projectlist.ref', $search_projectlist_ref);
	}
	if ($search_projectlist_title !== '') {
		$sql .= natural_search('projectlist.title', $search_projectlist_title);
	}
	$nbtotalofrecords = 0;
	$resql = $db->query(str_replace($sqlfields, 'SELECT COUNT(*) as nbtotalofrecords', $sql));
	if (!$resql) {
		dol_print_error($db);
	} else {
		$objforcount = $db->fetch_object($resql);
		if (is_object($objforcount)) {
			$nbtotalofrecords = (int) $objforcount->nbtotalofrecords;
		}
		$db->free($resql);
	}
	if ($page * $limit >= $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
	$sql .= $db->order($sortfield, $sortorder);
	if ($limit > 0) {
		$sql .= $db->plimit($limit + 1, $offset);
	}
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	} else {
		$num = $db->num_rows($resql);
		$param = '&id='.$object->id.'&search_projectlist_ref='.urlencode($search_projectlist_ref).'&search_projectlist_title='.urlencode($search_projectlist_title);
		if ($limit != $conf->liste_limit) {
			$param .= '&limit='.$limit;
		}
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
		print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
		print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';
		print_barre_liste($langs->trans('Bookings'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'calendar', 0, '', '', $limit);
		$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $contextpage, $conf->main_checkbox_left_column);
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre_filter">';
		print '<td>'.$form->showFilterButtons().'</td><td></td><td></td><td></td><td></td>';
		if (!empty($arrayfields['projectlist.ref']['checked'])) {
			print '<td class="liste_titre"><input type="text" class="flat maxwidth125" name="search_projectlist_ref" value="'.dol_escape_htmltag($search_projectlist_ref).'"></td>';
		}
		if (!empty($arrayfields['projectlist.title']['checked'])) {
			print '<td class="liste_titre"><input type="text" class="flat maxwidth125" name="search_projectlist_title" value="'.dol_escape_htmltag($search_projectlist_title).'"></td>';
		}
		print '<td></td></tr>';
		$totalarray = array('nbfield' => 6);
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td>'.$langs->trans('Title').'</td>';
		print '<td class="center">'.$langs->trans('DateStart').'</td>';
		print '<td class="center">'.$langs->trans('DateEnd').'</td>';
		print '<td>'.$langs->trans('Contact').'</td>';
		if (!empty($arrayfields['projectlist.ref']['checked'])) {
			print_liste_field_titre($arrayfields['projectlist.ref']['label'], $_SERVER['PHP_SELF'], 'projectlist.ref', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['projectlist.title']['checked'])) {
			print_liste_field_titre($arrayfields['projectlist.title']['label'], $_SERVER['PHP_SELF'], 'projectlist.title', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
		print '<td>'.$selectedfields.'</td></tr>';
		$savnbfield = $totalarray['nbfield'];
		$tmpcontact = new Contact($db);
		$tmpactioncomm = new ActionComm($db);
		$imaxinloop = ($limit > 0 ? min($num, $limit) : $num);
		for ($i = 0; $i < $imaxinloop; $i++) {
			$obj = $db->fetch_object($resql);
			if (!is_object($obj)) {
				break;
			}
			$tmpcontact->fetch($obj->elementid);
			$tmpactioncomm->fetch($obj->id);
			print '<tr class="oddeven">';
			print '<td class="nowraponall">'.$tmpactioncomm->getNomUrl(1, -1).'</td>';
			print '<td class="tdoverflowmax125">'.dol_escape_htmltag($obj->label).'</td>';
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_start), 'dayhour').'</td>';
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_end), 'dayhour').'</td>';
			print '<td class="minwidth75">'.$tmpcontact->getNomUrl(1, '-1').'</td>';
			if (!empty($arrayfields['projectlist.ref']['checked'])) {
				print '<td class="tdoverflowmax150">';
				if ($obj->projectlist_id > 0) {
					$projectforlist->id = (int) $obj->projectlist_id;
					$projectforlist->ref = dol_escape_htmltag($obj->projectlist_ref);
					$projectforlist->title = dol_escape_htmltag($obj->projectlist_title);
					$projectforlist->public = (int) $obj->projectlist_public;
					$projectforlist->status = (int) $obj->projectlist_status;
					print $projectforlist->getNomUrl(1);
				}
				print '</td>';
			}
			if (!empty($arrayfields['projectlist.title']['checked'])) {
				print '<td class="tdoverflowmax150">';
				if ($obj->projectlist_id > 0) {
					$projectforlist->id = (int) $obj->projectlist_id;
					$projectforlist->ref = dol_escape_htmltag($obj->projectlist_ref);
					$projectforlist->title = dol_escape_htmltag($obj->projectlist_title);
					$projectforlist->public = (int) $obj->projectlist_public;
					$projectforlist->status = (int) $obj->projectlist_status;
					print $projectforlist->getNomUrl(1, '', -1);
				}
				print '</td>';
			}
			print '<td></td></tr>';
		}
		if ($num == 0) {
			print '<tr class="oddeven"><td colspan="'.$savnbfield.'"><span class="opacitymedium">'.$langs->trans('NoRecordFound').'</span></td></tr>';
		}
		$db->free($resql);
		print '</table></div></form>';
	}
}

// End of page
llxFooter();
$db->close();
