<?php
/* Copyright (C) 2008-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2022-2026  Frédéric France		<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 * \file		htdocs/core/lib/agenda.lib.php
 * \brief		Set of function for the agenda module
 */


/**
 * Show filter form in agenda view
 *
 * @param	Form			$form				Form object
 * @param	int				$canedit			Can edit filter fields
 * @param	string			$status				Status see FormActions::form_select_status_action
 * @param 	int				$year				Year
 * @param 	int				$month				Month
 * @param 	int				$day				Day
 * @param 	int				$showbirthday		Show birthday
 * @param 	string			$filtera			Filter on create by user
 * @param 	string			$filtert			Filter on assigned to user
 * @param 	string			$filtered			Filter of done by user
 * @param 	int				$pid				Product id
 * @param 	int				$socid				Third party id
 * @param	string			$action				Action string
 * @param	array<array{type:string,sr:string,name:string,offsettz:int,color:string,default:string,buggedfile:string}>|int<-1,-1>		$showextcals		Array with list of external calendars (used to show links to select calendar), or -1 to show no legend
 * @param	string|string[]	$actioncode			Preselected value(s) of actioncode for filter on event type
 * @param	int|int[]		$usergroupid		Id of group to filter on users
 * @param	''|'systemauto'|'system'	$excludetype	A type to exclude ('systemauto', 'system', '')
 * @param	int   			$resourceid			Preselected value of resource for filter on resource
 * @param	int     		$search_categ_cus	Tag id
 * @param	string			$search_import_key	Import IDfilter
 * @return	void
 */
function print_actions_filter(
	$form,
	$canedit,
	$status,
	$year,
	$month,
	$day,
	$showbirthday,
	$filtera,
	$filtert,
	$filtered,
	$pid,
	$socid,
	$action,
	$showextcals = array(),
	$actioncode = '',
	$usergroupid = 0,
	$excludetype = '',
	$resourceid = 0,
	$search_categ_cus = 0,
	$search_import_key = ''
) {
	global $user, $langs, $db, $hookmanager;
	global $massaction;

	$langs->load("companies");

	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);

	// Filters
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="year" value="'.((int) $year).'">';
	print '<input type="hidden" name="month" value="'.((int) $month).'">';
	print '<input type="hidden" name="day" value="'.((int) $day).'">';
	if ($massaction != 'predelete' && $massaction != 'preaffecttag') {		// When $massaction == 'predelete', action may be already output to 'delete' by the mass action system.
		print '<input type="hidden" name="action" value="'.$action.'">';
	}
	print '<input type="hidden" name="search_showbirthday" value="'.((int) $showbirthday).'">';

	print '<div class="divsearchfield">';
	// Type
	$multiselect = 0;
	if (getDolGlobalString('MAIN_ENABLE_MULTISELECT_TYPE')) {     // We use an option here because it adds bugs when used on agenda page "peruser" and "list"
		$multiselect = (getDolGlobalString('AGENDA_USE_EVENT_TYPE'));
	}
	print img_picto($langs->trans("ActionType"), 'square', 'class="pictofixedwidth inline-block" style="color: #ddd;"');
	print $formactions->select_type_actions($actioncode, "search_actioncode", $excludetype, (getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? -1 : 1), 0, $multiselect, 0, 'minwidth150 maxwidth200 widthcentpercentminusx', 1);
	print '</div>';

	if ($canedit) {
		// Assigned to user
		print '<div class="divsearchfield">';
		print img_picto($langs->trans("ActionsToDoBy"), 'user', 'class="pictofixedwidth inline-block"');
		print $form->select_dolusers($filtert, 'search_filtert', 1, null, (int) !$canedit, '', '', '0', 0, 0, '', 2, '', 'minwidth100 maxwidth250 widthcentpercentminusx');
		print '</div>';

		// Assigned to user group
		print '<div class="divsearchfield">';
		print img_picto($langs->trans("ToUserOfGroup"), 'object_group', 'class="pictofixedwidth inline-block"');
		print $form->select_dolgroups($usergroupid, 'usergroup', 1, '', (int) !$canedit, '', array(), '0', false, 'minwidth100 maxwidth250 widthcentpercentminusx');
		print '</div>';

		if (isModEnabled('resource')) {
			include_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';
			$formresource = new FormResource($db);

			// Resource
			print '<div class="divsearchfield">';
			print img_picto($langs->trans("Resource"), 'object_resource', 'class="pictofixedwidth inline-block"');
			print $formresource->select_resource_list($resourceid, "search_resourceid", '', 1, 0, 0, [], '', 2, 0, 'minwidth100 maxwidth250 widthcentpercentminusx');
			print '</div>';
		}
	}

	if (isModEnabled('societe') && $user->hasRight('societe', 'lire')) {
		print '<div class="divsearchfield">';
		print img_picto($langs->trans("ThirdParty"), 'company', 'class="pictofixedwidth inline-block"');
		print $form->select_company($socid, 'search_socid', '', '&nbsp;', 0, 0, array(), 0, 'minwidth100 maxwidth250 widthcentpercentminusx');
		print '</div>';
	}

	if (isModEnabled('project') && $user->hasRight('projet', 'lire')) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
		$formproject = new FormProjets($db);

		print '<div class="divsearchfield">';
		print img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth inline-block"');
		print $formproject->select_projects($socid ? $socid : -1, (string) $pid, 'search_projectid', 0, 0, 1, 0, 0, 0, 0, '', 1, 0, 'minwidth100 maxwidth250 widthcentpercentminusx');
		print '</div>';
	}

	if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		$formother = new FormOther($db);
		$langs->load('categories');

		print '<div class="divsearchfield">';
		print img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"');
		print $formother->select_categories('actioncomm', $search_categ_cus, 'search_categ_cus', 1, $langs->trans('Categories'), 'minwidth100 maxwidth250 widthcentpercentminusx');
		print '</div>';
	}

	if ($canedit && !preg_match('/list/', $_SERVER["PHP_SELF"])) {
		// Status
		print '<div class="divsearchfield">';
		print img_picto($langs->trans("Status"), 'status', 'class="pictofixedwidth inline-block"');
		$formactions->form_select_status_action('formaction', $status, 1, 'search_status', 1, 2, 'minwidth100');
		print '</div>';
	}

	// Hooks
	$parameters = array('canedit' => $canedit, 'pid' => $pid, 'socid' => $socid);
	$object = null;  // Null on purpose: @phan-suppress-next-line PhanPluginConstantVariableNull
	$reshook = $hookmanager->executeHooks('searchAgendaFrom', $parameters, $object, $action); // Note that $action and $object may have been

	print '<div class="clearboth"></div>';
}


/**
 *  Show actions to do array
 *
 *  @param	int		$max		Max nb of records
 *  @return	void
 */
function show_array_actions_to_do($max = 5)
{
	global $langs, $conf, $user, $db, $socid;

	$now = dol_now();

	include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
	include_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

	$sql = "SELECT a.id, a.label, a.datep as dp, a.datep2 as dp2, a.fk_user_author, a.percent";
	$sql .= ", c.code, c.libelle as type_label";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ";
	$sql .= " ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
	$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '".$db->idate($now)."'))";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= " ORDER BY a.datep DESC, a.id DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("LastActionsToDo", $max).'</th>';
		print '<th colspan="2" class="right"><a class="commonlink" href="'.dolBuildUrl(DOL_URL_ROOT.'/comm/action/list.php', ['mode' => 'show_list', 'status' => 'todo']).'">'.$langs->trans("FullList").'</a></th>';
		print '</tr>';

		$i = 0;

		$staticaction = new ActionComm($db);
		$customerstatic = new Client($db);

		while ($i < $num) {
			$obj = $db->fetch_object($resql);


			print '<tr class="oddeven">';

			$staticaction->type_code = $obj->code;
			$staticaction->label = ($obj->label ? $obj->label : $obj->type_label);
			$staticaction->id = $obj->id;
			print '<td>'.$staticaction->getNomUrl(1, 34).'</td>';

			// print '<td>'.dol_trunc($obj->label,22).'</td>';

			print '<td>';
			if ($obj->socid > 0) {
				$customerstatic->id = $obj->socid;
				$customerstatic->name = $obj->name;
				//$customerstatic->name_alias = $obj->name_alias;
				$customerstatic->code_client = $obj->code_client;
				$customerstatic->code_compta = $obj->code_compta;
				$customerstatic->code_compta_client = $obj->code_compta;
				$customerstatic->client = $obj->client;
				$customerstatic->logo = $obj->logo;
				$customerstatic->email = $obj->email;
				$customerstatic->entity = $obj->entity;
				print $customerstatic->getNomUrl(1, '', 40);
			}
			print '</td>';

			$datep = $db->jdate($obj->dp);
			$datep2 = $db->jdate($obj->dp2);

			// Date
			print '<td width="100" class="right tddate">'.dol_print_date($datep, 'day').'&nbsp;';
			$late = 0;
			if ($obj->percent == 0 && $datep && $datep < time()) {
				$late = 1;
			}
			if ($obj->percent == 0 && !$datep && $datep2 && $datep2 < time()) {
				$late = 1;
			}
			if ($obj->percent > 0 && $obj->percent < 100 && $datep2 && $datep2 < time()) {
				$late = 1;
			}
			if ($obj->percent > 0 && $obj->percent < 100 && !$datep2 && $datep && $datep < time()) {
				$late = 1;
			}
			if ($late) {
				print img_warning($langs->trans("Late"));
			}
			print "</td>";

			// Statut
			print '<td class="right" width="14">'.$staticaction->LibStatut($obj->percent, 3)."</td>\n";

			print "</tr>\n";

			$i++;
		}
		print "</table></div><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/**
 *  Show last actions array
 *
 *  @param	int		$max		Max nb of records
 *  @return	void
 */
function show_array_last_actions_done($max = 5)
{
	global $langs, $conf, $user, $db, $socid;

	$now = dol_now();

	$sql = "SELECT a.id, a.percent, a.datep as da, a.datep2 as da2, a.fk_user_author, a.label";
	$sql .= ", c.code, c.libelle";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a LEFT JOIN ";
	$sql .= " ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action ";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
	$sql .= " AND (a.percent >= 100 OR (a.percent = -1 AND a.datep2 <= '".$db->idate($now)."'))";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= " ORDER BY a.datep2 DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("LastDoneTasks", $max).'</th>';
		print '<th colspan="2" class="right"><a class="commonlink" href="'.dolBuildUrl(DOL_URL_ROOT.'/comm/action/list.php', ['mode'=> 'show_list', 'status' => 'done']).'">'.$langs->trans("FullList").'</a></th>';
		print '</tr>';

		$i = 0;

		$staticaction = new ActionComm($db);
		$customerstatic = new Societe($db);

		while ($i < $num) {
			$obj = $db->fetch_object($resql);


			print '<tr class="oddeven">';

			$staticaction->type_code = $obj->code;
			$staticaction->label = $obj->label;
			$staticaction->id = $obj->id;
			print '<td>'.$staticaction->getNomUrl(1, 34).'</td>';

			//print '<td>'.dol_trunc($obj->label,24).'</td>';

			print '<td>';
			if ($obj->socid > 0) {
				$customerstatic->id = $obj->socid;
				$customerstatic->name = $obj->name;
				//$customerstatic->name_alias = $obj->name_alias;
				$customerstatic->code_client = $obj->code_client;
				$customerstatic->code_compta = $obj->code_compta;
				$customerstatic->code_compta_client = $obj->code_compta;
				$customerstatic->client = $obj->client;
				$customerstatic->logo = $obj->logo;
				$customerstatic->email = $obj->email;
				$customerstatic->entity = $obj->entity;
				print $customerstatic->getNomUrl(1, '', 30);
			}
			print '</td>';

			// Date
			print '<td width="100" class="right tddate">'.dol_print_date($db->jdate($obj->da2), 'day');
			print "</td>";

			// Status
			print '<td class="right" width="14">'.$staticaction->LibStatut($obj->percent, 3)."</td>\n";

			print "</tr>\n";
			$i++;
		}
		// TODO Add a reminder for "contracts need to be put in service."
		// TODO Add reminder for "contracts expiring soon."
		print "</table></div><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/**
 * Prepare array with list of tabs
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function agenda_prepare_head()
{
	global $langs, $conf, $user, $extrafields;

	$extrafields->fetch_name_optionals_label('actioncomm');

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/admin/agenda_other.php");
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'other';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/admin/agenda.php");
	$head[$h][1] = $langs->trans("AutoActions");
	$head[$h][2] = 'autoactions';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/admin/agenda_reminder.php");
	$head[$h][1] = $langs->trans("Reminders");
	$head[$h][2] = 'reminders';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/admin/agenda_xcal.php");
	$head[$h][1] = $langs->trans("ExportCal");
	$head[$h][2] = 'xcal';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/admin/agenda_extsites.php");
	$head[$h][1] = $langs->trans("ExtSites");
	$head[$h][2] = 'extsites';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'agenda_admin');

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'agenda'));
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['actioncomm']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'agenda_admin', 'remove');


	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   object	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function actions_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/card.php', ['id'=> $object->id]);
	$head[$h][1] = $langs->trans("CardAction");
	$head[$h][2] = 'card';
	$h++;

	// Tab to link resources
	if (isModEnabled('resource')) {
		include_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
		$resource = new Dolresource($db);

		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/resource/element_resource.php', ['element' => 'action', 'element_id'=> $object->id]);
		$listofresourcelinked = $resource->getElementResources($object->element, $object->id);
		$nbResources = (is_array($listofresourcelinked) ? count($listofresourcelinked) : 0);
		$head[$h][1] = $langs->trans("Resources");
		if ($nbResources > 0) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.($nbResources).'</span>' : '');
		}
		$head[$h][2] = 'resources';
		$h++;
	}

	// Attached files
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->agenda->dir_output."/".$object->id;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/document.php', ['id' => $object->id]);
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>' : '');
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/info.php', ['id' => $object->id]);
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'action');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'action', 'remove');

	return $head;
}


/**
 *  Define head array for tabs of agenda setup pages
 *
 *  @param	string	$param		Parameters to add to url
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function calendars_prepare_head($param)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	$query = [];
	parse_str($param, $query);

	$query = array_merge($query, ['mode' => 'show_list']);
	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/list.php', $query);
	$head[$h][1] = $langs->trans("ViewList");
	$head[$h][2] = 'cardlist';
	$h++;

	$query['mode'] = 'show_month';
	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/index.php', $query);
	$head[$h][1] = $langs->trans("ViewCal");
	$head[$h][2] = 'cardmonth';
	$h++;

	$query['mode'] = 'show_week';
	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/index.php', $query);
	$head[$h][1] = $langs->trans("ViewWeek");
	$head[$h][2] = 'cardweek';
	$h++;

	$query['mode'] = 'show_day';
	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/index.php', $query);
	$head[$h][1] = $langs->trans("ViewDay");
	$head[$h][2] = 'cardday';
	$h++;

	unset($query['mode']);
	if (getDolGlobalString('AGENDA_SHOW_PERTYPE')) {
		$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/pertype.php', $query);
		$head[$h][1] = $langs->trans("ViewPerType");
		$head[$h][2] = 'cardpertype';
		$h++;
	}

	$newparam = $param;
	$newparam = preg_replace('/&?search_filtert=\d+/', '', $newparam);
	$query = [];
	parse_str($newparam, $query);

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/comm/action/peruser.php', $query);
	$head[$h][1] = $langs->trans("ViewPerUser");
	$head[$h][2] = 'cardperuser';
	$h++;


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'agenda');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'agenda', 'remove');

	return $head;
}

/**
 * Build $eventarray (list of ActionComm objects, indexed by day) for the agenda calendar views
 * (month/week/day), applying the exact same date-range and filter logic the calendar page itself uses.
 * Extracted from htdocs/comm/action/index.php so the same logic can be shared by other agenda views.
 *
 * @param	DoliDB			$db					Database handler
 * @param	HookManager		$hookmanager		Hook manager
 * @param	User			$user				Current user
 * @param	null|CommonObject|array<int|string,mixed>|string	$object		Object passed to hooks (may be modified by hooks)
 * @param	string			$action				Action string passed to hooks (may be modified by hooks)
 * @param	string			$mode				View mode: 'show_day', 'show_week', 'show_month', or ''
 * @param	int				$year				Year
 * @param	int				$month				Month
 * @param	int				$day				Day
 * @param	int				$firstdaytoshow		Start of the visible date range (Unix timestamp)
 * @param	int				$lastdaytoshow		End of the visible date range (Unix timestamp, exclusive)
 * @param	array{usergroup:string,filtert:string,resourceid:int,actioncode:string|string[],pid:int,socid:int,type:string,status:string,search_categ_cus:int}	$filters	Already-resolved filter values
 * @param	int|null		$sincedatec			If set, only return events created (datec) or modified (tms) after this Unix timestamp; null = no restriction (identical to today's behavior)
 * @return	array{eventarray:array<int,array<int,ActionComm>>,nbevents:int,maxonsamepage:int}
 */
function agenda_build_eventarray($db, $hookmanager, $user, &$object, &$action, $mode, $year, $month, $day, $firstdaytoshow, $lastdaytoshow, $filters, $sincedatec = null)
{
	$usergroup = $filters['usergroup'];
	$filtert = $filters['filtert'];
	$resourceid = $filters['resourceid'];
	$actioncode = $filters['actioncode'];
	$pid = $filters['pid'];
	$socid = $filters['socid'];
	$type = $filters['type'];
	$status = $filters['status'];
	$search_categ_cus = $filters['search_categ_cus'];

	// Load events from database into $eventarray
	$eventarray = array();
	$nbevents = 0;

	// DEFAULT CALENDAR + AUTOEVENT CALENDAR + CONFERENCEBOOTH CALENDAR
	$sql = 'SELECT ';
	if ($usergroup > 0) {
		$sql .= " DISTINCT";
	}
	$sql .= ' a.id, a.label,';
	$sql .= ' a.datep,';
	$sql .= ' a.datep2,';
	$sql .= ' a.percent,';
	$sql .= ' a.fk_user_author,a.fk_user_action,';
	$sql .= ' a.transparency, a.priority, a.fulldayevent, a.location,';
	$sql .= ' a.fk_soc, a.fk_contact, a.fk_project, a.fk_bookcal_calendar,';
	$sql .= ' a.fk_element, a.elementtype,';
	$sql .= ' ca.code as type_code, ca.libelle as type_label, ca.color as type_color, ca.type as type_type, ca.picto as type_picto';

	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sqlfields = $sql; // $sql fields to remove for count total

	$sql .= " FROM ".MAIN_DB_PREFIX."c_actioncomm as ca, ".MAIN_DB_PREFIX."actioncomm as a";

	// We must filter on assignment table
	if (($filtert != '-1' && $filtert != '-2') || $usergroup > 0) {
		// TODO Replace with a AND EXISTS
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm_resources as ar ON ar.fk_actioncomm = a.id AND ar.element_type = 'user'";
		if ($filtert != '-1' && $filtert != '-2'  && $filtert != '-3') {
			$sql .= " AND (ar.fk_element IN (".$db->sanitize($filtert).") OR (ar.fk_element IS NULL AND a.fk_user_action = ".((int) $filtert)."))"; // The OR is for backward compatibility
		} elseif ($filtert == '-3') {
			$sql .= " AND ar.fk_element IN (".$db->sanitize(implode(',', $user->getAllChildIds(1))).")";
		}
		if ($usergroup > 0) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element AND ugu.fk_usergroup = ".((int) $usergroup);
		}
	}

	// We must filter on resource table
	if ($resourceid > 0) {
		$sql .= ", ".MAIN_DB_PREFIX."element_resources as r";
	}

	// Add table from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sql .= " WHERE a.fk_action = ca.id";
	$sql .= " AND a.entity IN (".getEntity('agenda').")";	// bookcal is a "virtual view" of agenda
	// Condition on actioncode
	if (!empty($actioncode)) {
		if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
			if ((is_array($actioncode) && in_array('AC_NON_AUTO', $actioncode)) || $actioncode == 'AC_NON_AUTO') {
				$sql .= " AND ca.type != 'systemauto'";
			} elseif ((is_array($actioncode) && in_array('AC_ALL_AUTO', $actioncode)) || $actioncode == 'AC_ALL_AUTO') {
				$sql .= " AND ca.type = 'systemauto'";
			} else {
				if ((is_array($actioncode) && in_array('AC_OTH', $actioncode)) || $actioncode == 'AC_OTH') {
					$sql .= " AND ca.type != 'systemauto'";
				}
				if ((is_array($actioncode) && in_array('AC_OTH_AUTO', $actioncode)) || $actioncode == 'AC_OTH_AUTO') {
					$sql .= " AND ca.type = 'systemauto'";
				}
			}
		} else {
			if ((is_array($actioncode) && in_array('AC_NON_AUTO', $actioncode)) || $actioncode === 'AC_NON_AUTO') {
				$sql .= " AND ca.type != 'systemauto'";
			} elseif ((is_array($actioncode) && in_array('AC_ALL_AUTO', $actioncode))	|| $actioncode === 'AC_ALL_AUTO') {
				$sql .= " AND ca.type = 'systemauto'";
			} else {
				if (is_array($actioncode)) {
					// Remove all -1 values
					$actioncode = array_filter(
						$actioncode,
						/**
						 * @param string $value
						 * @return	bool
						 */
						function ($value) {
							return ((string) $value !== '-1');
						}
					);
					if (count($actioncode)) {
						$sql .= " AND ca.code IN (".$db->sanitize("'".implode("','", $actioncode)."'", 1).")";
					}
				} elseif ($actioncode !== '-1') {
					$sql .= " AND ca.code IN (".$db->sanitize("'".implode("','", explode(',', $actioncode))."'", 1).")";
				}
			}
		}
	}
	if ($resourceid > 0) {
		$sql .= " AND r.element_type = 'action' AND r.element_id = a.id AND r.resource_id = ".((int) $resourceid);
	}
	if ($pid) {
		$sql .= " AND a.fk_project=".((int) $pid);
	}
	// If the internal user must only see his customers, force searching by him
	$search_sale = 0;
	if (isModEnabled("societe") && !$user->hasRight('societe', 'client', 'voir')) {
		$search_sale = $user->id;
	}
	// Search on sale representative
	if ($search_sale && $search_sale != '-1') {
		if ($search_sale == -2) {
			$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = a.fk_soc)";
		} elseif ($search_sale > 0) {
			$sql .= " AND (a.fk_soc IS NULL OR EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = a.fk_soc AND sc.fk_user = ".((int) $search_sale)."))";
		}
	}
	// Search on socid
	if ($socid > 0) {
		$sql .= " AND a.fk_soc = ".((int) $socid);
	}
	//var_dump($day.' '.$month.' '.$year);
	if ($mode == 'show_day') {
		$sql .= " AND (";
		$sql .= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel'))."'";
		$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel'))."')";
		$sql .= " OR ";
		$sql .= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel'))."'";
		$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel'))."')";
		$sql .= " OR ";
		$sql .= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel'))."'";
		$sql .= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel'))."')";
		$sql .= ')';
	} else {
		// To limit array
		$sql .= " AND (";
		$sql .= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7))."'"; // Start 7 days before
		$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10))."')"; // End 7 days after + 3 to go from 28 to 31
		$sql .= " OR ";
		$sql .= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7))."'";
		$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10))."')";
		$sql .= " OR ";
		$sql .= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7))."'";
		$sql .= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10))."')";
		$sql .= ')';
	}
	if ($sincedatec !== null) {
		$sql .= " AND (a.datec > '".$db->idate($sincedatec)."' OR a.tms > '".$db->idate($sincedatec)."')";
	}
	if ($type) {
		$sql .= " AND ca.id = ".((int) $type);
	}
	if ($status == '0') {
		// To do (not started)
		$sql .= " AND a.percent = 0";
	}
	if ($status === 'na') {
		// Not applicable
		$sql .= " AND a.percent = -1";
	}
	if ($status == '50') {
		// Running already started
		$sql .= " AND (a.percent > 0 AND a.percent < 100)";
	}
	if ($status == 'done' || $status == '100') {
		$sql .= " AND (a.percent = 100)";
	}
	if ($status == 'todo') {
		$sql .= " AND (a.percent >= 0 AND a.percent < 100)";
	}

	// Search in categories, -1 is all and -2 is no categories
	if ($search_categ_cus != -1) {
		if ($search_categ_cus == -2) {
			$sql .= " AND NOT EXISTS (SELECT ca.fk_actioncomm FROM ".MAIN_DB_PREFIX."categorie_actioncomm as ca WHERE ca.fk_actioncomm = a.id)";
		} elseif ($search_categ_cus > 0) {
			$sql .= " AND EXISTS (SELECT ca.fk_actioncomm FROM ".MAIN_DB_PREFIX."categorie_actioncomm as ca WHERE ca.fk_actioncomm = a.id AND ca.fk_categorie IN (".$db->sanitize((string) $search_categ_cus)."))";
		}
	}

	// Sort on date
	$sql .= $db->order("datep");

	$MAXONSAMEPAGE = getDolGlobalInt('AGENDA_MAX_ON_SAME_PAGE', 5000); // Useless to have more. Protection to avoid memory overload when high number of event (for example after a mass import)

	$sql .= $db->plimit($MAXONSAMEPAGE + 1);

	dol_syslog("agenda_build_eventarray", LOG_DEBUG);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$nbevents += $num;

		$i = 0;
		while ($i < $num && $i < $MAXONSAMEPAGE) {
			$obj = $db->fetch_object($resql);

			// Discard auto action if option is on
			if (getDolGlobalString('AGENDA_ALWAYS_HIDE_AUTO') && $obj->type_code == 'AC_OTH_AUTO') {
				$i++;
				continue;
			}

			// Create a new object action
			$event = new ActionComm($db);

			$event->id = $obj->id;
			$event->ref = (string) $event->id;

			$event->fulldayevent = $obj->fulldayevent;

			// event->datep and event->datef must be GMT date.
			if ($event->fulldayevent) {
				$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
				$event->datep = $db->jdate($obj->datep, $tzforfullday ? 'tzuser' : 'tzserver');	// If saved in $tzforfullday = gmt, we must invert date to be in user tz
				$event->datef = $db->jdate($obj->datep2, $tzforfullday ? 'tzuser' : 'tzserver');
			} else {
				// Example: $obj->datep = '1970-01-01 01:00:00', jdate will return 0 if TZ of PHP server is Europe/Berlin (+1)
				$event->datep = $db->jdate($obj->datep, 'tzserver');
				$event->datef = $db->jdate($obj->datep2, 'tzserver');
			}
			//$event->datep_formated_gmt = dol_print_date($event->datep, 'dayhour', 'gmt');
			//var_dump($obj->id.' '.$obj->datep.' '.dol_print_date($obj->datep, 'dayhour', 'gmt'));
			//var_dump($obj->id.' '.$event->datep.' '.dol_print_date($event->datep, 'dayhour', 'gmt'));

			$event->type_code = $obj->type_code;
			$event->type_label = $obj->type_label;
			$event->type_color = $obj->type_color;
			$event->type = $obj->type_type;
			$event->type_picto = $obj->type_picto;

			$event->label = $obj->label;
			$event->percentage = $obj->percent;

			$event->authorid = $obj->fk_user_author; // user id of creator
			$event->userownerid = $obj->fk_user_action; // user id of owner
			$event->fetch_userassigned(); // This load $event->userassigned

			$event->priority = $obj->priority;
			$event->location = $obj->location;
			$event->transparency = $obj->transparency;
			$event->fk_element = $obj->fk_element;
			$event->elementid = $obj->fk_element;
			$event->elementtype = $obj->elementtype;

			$event->fk_project = $obj->fk_project;

			$event->socid = $obj->fk_soc;
			$event->contact_id = $obj->fk_contact;
			$event->fk_bookcal_calendar = $obj->fk_bookcal_calendar;
			if (!empty($event->fk_bookcal_calendar)) {
				$event->type = "bookcal_calendar";
			}

			// Defined date_start_in_calendar and date_end_in_calendar property
			// They are date start and end of action but modified to not be outside calendar view.
			$event->date_start_in_calendar = $event->datep;
			if ($event->datef != '' && $event->datef >= $event->datep) {
				$event->date_end_in_calendar = $event->datef;
			} else {
				$event->date_end_in_calendar = $event->datep;
			}

			// Check values
			if ($event->date_end_in_calendar < $firstdaytoshow || $event->date_start_in_calendar >= $lastdaytoshow) {
				// This record is out of visible range
			} else {
				if ($event->date_start_in_calendar < $firstdaytoshow) {
					$event->date_start_in_calendar = $firstdaytoshow;
				}
				if ($event->date_end_in_calendar >= $lastdaytoshow) {
					$event->date_end_in_calendar = ($lastdaytoshow - 1);
				}

				// Add an entry in actionarray for each day
				$daycursor = $event->date_start_in_calendar;
				$annee = (int) dol_print_date($daycursor, '%Y', 'tzuserrel');
				$mois = (int) dol_print_date($daycursor, '%m', 'tzuserrel');
				$jour = (int) dol_print_date($daycursor, '%d', 'tzuserrel');

				$daycursorend = $event->date_end_in_calendar;
				$anneeend = (int) dol_print_date($daycursorend, '%Y', 'tzuserrel');
				$moisend = (int) dol_print_date($daycursorend, '%m', 'tzuserrel');
				$jourend = (int) dol_print_date($daycursorend, '%d', 'tzuserrel');

				//var_dump(dol_print_date($event->date_start_in_calendar, 'dayhour', 'gmt'));	// Hour at greenwich
				//var_dump($annee.'-'.$mois.'-'.$jour);
				//print 'annee='.$annee.' mois='.$mois.' jour='.$jour.'<br>';

				// Loop on each day covered by action to prepare an index to show on calendar
				$loop = true;
				$j = 0;
				$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');	// $mois, $jour, $annee has been set for user tz
				$daykeyend = dol_mktime(0, 0, 0, $moisend, $jourend, $anneeend, 'gmt');	// $moisend, $jourend, $anneeend has been set for user tz
				/*
				 print 'GMT '.$event->date_start_in_calendar.' '.dol_print_date($event->date_start_in_calendar, 'dayhour', 'gmt').'<br>';
				 print 'TZSERVER '.$event->date_start_in_calendar.' '.dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzserver').'<br>';
				 print 'TZUSERREL '.$event->date_start_in_calendar.' '.dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzuserrel').'<br>';
				 print 'GMT '.$event->date_end_in_calendar.' '.dol_print_date($event->date_end_in_calendar, 'dayhour', 'gmt').'<br>';
				 print 'TZSERVER '.$event->date_end_in_calendar.' '.dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzserver').'<br>';
				 print 'TZUSER '.$event->date_end_in_calendar.' '.dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzuserrel').'<br>';
				 */
				do {
					//if ($event->id==408)
					//print 'daykey='.$daykey.' daykeyend='.$daykeyend.' '.dol_print_date($daykey, 'dayhour', 'gmt').' - '.dol_print_date($event->datep, 'dayhour', 'gmt').' '.dol_print_date($event->datef, 'dayhour', 'gmt').'<br>';
					//print 'daykey='.$daykey.' daykeyend='.$daykeyend.' '.dol_print_date($daykey, 'dayhour', 'tzuserrel').' - '.dol_print_date($event->datep, 'dayhour', 'tzuserrel').' '.dol_print_date($event->datef, 'dayhour', 'tzuserrel').'<br>';

					$eventarray[$daykey][] = $event;
					$j++;

					$daykey += 60 * 60 * 24;
					//if ($daykey > $event->date_end_in_calendar) {
					if ($daykey > $daykeyend) {
						$loop = false;
					}
				} while ($loop);
				//var_dump($eventarray);
				//print 'Event '.$i.' id='.$event->id.' (start='.dol_print_date($event->datep).'-end='.dol_print_date($event->datef);
				//print ' startincalendar='.dol_print_date($event->date_start_in_calendar).'-endincalendar='.dol_print_date($event->date_end_in_calendar).') was added in '.$j.' different index key of array<br>';
			}

			$parameters['obj'] = $obj;
			$reshook = $hookmanager->executeHooks('hookEventElements', $parameters, $event, $action); // Note that $action and $object may have been modified by some hooks
			$event = $hookmanager->resPrint;
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}

			$i++;
		}
	} else {
		dol_print_error($db);
	}
	//var_dump($eventarray);

	return array('eventarray' => $eventarray, 'nbevents' => $nbevents, 'maxonsamepage' => $MAXONSAMEPAGE);
}

/**
 * Complete an agenda calendar event array with contact birthday pseudo-events.
 * Shared by the month, week and day agenda views (comm/action/index.php, comm/action/peruser.php).
 *
 * @param	DoliDB							$db			Database handler
 * @param	Translate						$langs		Language object (already loaded)
 * @param	User							$user		Current user (used for private contact visibility)
 * @param	string							$mode		'show_day' restricts to the given day, any other value = whole month
 * @param	int								$month		Month number (1-12)
 * @param	int								$day		Day of month (only used when $mode == 'show_day')
 * @param	int								$year		Year the birthday events must be placed in
 * @param	array<int,ActionComm[]>			$eventarray	Event array to complete, keyed by GMT day timestamp (modified by reference)
 * @param	int								$nbevents	Running event counter (modified by reference)
 * @return	int											Number of birthday events added, or <0 if the SQL query failed
 */
function agenda_get_birthday_events($db, $langs, $user, $mode, $month, $day, $year, &$eventarray, &$nbevents)
{
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

	$sql = 'SELECT sp.rowid, sp.lastname, sp.firstname, sp.birthday';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'socpeople as sp';
	$sql .= ' WHERE (priv=0 OR (priv=1 AND fk_user_creat='.((int) $user->id).'))';
	$sql .= " AND sp.entity IN (".getEntity('contact').")";
	if ($mode == 'show_day') {
		$sql .= ' AND MONTH(birthday) = '.((int) $month);
		$sql .= ' AND DAY(birthday) = '.((int) $day);
	} else {
		$sql .= ' AND MONTH(birthday) = '.((int) $month);
	}
	$sql .= ' ORDER BY birthday';

	dol_syslog("agenda.lib.php::agenda_get_birthday_events", LOG_DEBUG);
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		return -1;
	}

	$num = $db->num_rows($resql);
	$nbevents += $num;

	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$event = new ActionComm($db);

		$event->id = $obj->rowid; // We put contact id in action id for birthdays events
		$event->ref = (string) $event->id;

		$datebirth = dol_stringtotime($obj->birthday, 1);
		$datearray = dol_getdate($datebirth, true);
		$event->datep = dol_mktime(0, 0, 0, $datearray['mon'], $datearray['mday'], $year, true); // For full day events, date are also GMT but they won't but converted during output
		$event->datef = $event->datep;

		$event->type_code = 'BIRTHDAY';
		$event->type_label = '';
		$event->type_color = '';
		$event->type = 'birthdate';
		$event->type_picto = 'birthdate';

		$event->label = $langs->trans("Birthday").' '.dolGetFirstLastname($obj->firstname, $obj->lastname);
		$event->percentage = 100;
		$event->fulldayevent = 1;

		$event->contact_id = $obj->rowid;

		$event->date_start_in_calendar = $event->datep;
		$event->date_end_in_calendar = $event->datef;

		// Add an entry in eventarray for each day
		$daycursor = $event->datep;
		$annee = (int) dol_print_date($daycursor, '%Y', 'tzuserrel');
		$mois = (int) dol_print_date($daycursor, '%m', 'tzuserrel');
		$jour = (int) dol_print_date($daycursor, '%d', 'tzuserrel');

		$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');

		$eventarray[$daykey][] = $event;

		$i++;
	}

	return $num;
}
