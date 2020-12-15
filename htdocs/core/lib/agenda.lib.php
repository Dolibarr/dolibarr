<?php
/* Copyright (C) 2008-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file		htdocs/core/lib/agenda.lib.php
 *  \brief		Set of function for the agenda module
 */


/**
 * Show filter form in agenda view
 *
 * @param	Form	$form			Form object
 * @param	int		$canedit		Can edit filter fields
 * @param	int		$status			Status
 * @param 	int		$year			Year
 * @param 	int		$month			Month
 * @param 	int		$day			Day
 * @param 	int		$showbirthday	Show birthday
 * @param 	string	$filtera		Filter on create by user
 * @param 	string	$filtert		Filter on assigned to user
 * @param 	string	$filterd		Filter of done by user
 * @param 	int		$pid			Product id
 * @param 	int		$socid			Third party id
 * @param	string	$action			Action string
 * @param	array	$showextcals	Array with list of external calendars (used to show links to select calendar), or -1 to show no legend
 * @param	string|array	$actioncode		Preselected value(s) of actioncode for filter on event type
 * @param	int		$usergroupid	Id of group to filter on users
 * @param	string	$excludetype	A type to exclude ('systemauto', 'system', '')
 * @param	int   	$resourceid	    Preselected value of resource for filter on resource
 * @return	void
 */
function print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, $filtera, $filtert, $filterd, $pid, $socid, $action, $showextcals = array(), $actioncode = '', $usergroupid = '', $excludetype = '', $resourceid = 0)
{
	global $conf, $user, $langs, $db, $hookmanager;
	global $begin_h, $end_h, $begin_d, $end_d;
	global $massaction;

	$langs->load("companies");

	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);

	// Filters
	//print '<form name="listactionsfilter" class="listactionsfilter" action="' . $_SERVER["PHP_SELF"] . '" method="get">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="year" value="'.$year.'">';
	print '<input type="hidden" name="month" value="'.$month.'">';
	print '<input type="hidden" name="day" value="'.$day.'">';
	if ($massaction != 'predelete') {		// When $massaction == 'predelete', action may be already output to 'delete' by the mass action system.
		print '<input type="hidden" name="action" value="'.$action.'">';
	}
	print '<input type="hidden" name="search_showbirthday" value="'.$showbirthday.'">';

	if ($canedit)
	{
		print '<div class="divsearchfield">';
		// Type
		print '<span class="fas fa-square inline-block fawidth30" style=" color: #ddd;"></span>';
		print '<span class="hideonsmartphone">'.$langs->trans("Type").'</span>';
		$multiselect = 0;
		if (!empty($conf->global->MAIN_ENABLE_MULTISELECT_TYPE))     // We use an option here because it adds bugs when used on agenda page "peruser" and "list"
		{
			$multiselect = (!empty($conf->global->AGENDA_USE_EVENT_TYPE));
		}
		print $formactions->select_type_actions($actioncode, "search_actioncode", $excludetype, (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : -1), 0, $multiselect, 0, 'maxwidth500');
		print '</div>';

		// Assigned to
		print '<div class="divsearchfield">';
		print img_picto('', 'user', 'class="fawidth30 inline-block"');
		print '<span class="hideonsmartphone">'.$langs->trans("ActionsToDoBy").'</span>';
		print $form->select_dolusers($filtert, 'search_filtert', 1, '', !$canedit, '', '', 0, 0, 0, '', 0, '', 'maxwidth500 widthcentpercentminusxx');
		print '</div>';
		print '<div class="divsearchfield">';
		print img_picto('', 'object_group', 'class="fawidth30 inline-block"');
		print '<span class="hideonsmartphone">'.$langs->trans("ToUserOfGroup").'</span>';
		print $form->select_dolgroups($usergroupid, 'usergroup', 1, '', !$canedit, '', '', '0', false, 'maxwidth500');
		print '</div>';

		if ($conf->resource->enabled)
		{
			include_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';
			$formresource = new FormResource($db);

			// Resource
			print '<div class="divsearchfield">';
			print img_picto('', 'object_resource', 'class="fawidth30 inline-block"');
			print '<span class="hideonsmartphone">'.$langs->trans("Resource").'</span>';
			print $formresource->select_resource_list($resourceid, "search_resourceid", '', 1, 0, 0, null, '', 2, 0, 'maxwidth500');
			print '</div>';
		}
	}

	if (!empty($conf->societe->enabled) && $user->rights->societe->lire)
	{
		print '<div class="divsearchfield">';
		print img_picto('', 'company', 'class="fawidth30 inline-block"');
		print '<span class="hideonsmartphone">'.$langs->trans("ThirdParty").'</span>';
		print $form->select_company($socid, 'search_socid', '', '&nbsp;', 0, 0, null, 0, 'minwidth100 maxwidth500');
		print '</div>';
	}

	if (!empty($conf->projet->enabled) && $user->rights->projet->lire)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
		$formproject = new FormProjets($db);

		print '<div class="divsearchfield">';
		print img_picto('', 'project', 'class="fawidth30 inline-block"');
		print '<span class="hideonsmartphone">'.$langs->trans("Project").'</span>';
		print $formproject->select_projects($socid ? $socid : -1, $pid, 'search_projectid', 0, 0, 1, 0, 0, 0, 0, '', 1, 0, 'maxwidth500');
		print '</div>';
	}

	if ($canedit && !preg_match('/list/', $_SERVER["PHP_SELF"]))
	{
		// Status
		print '<div class="divsearchfield">';
		print img_picto('', 'setup', 'class="fawidth30 inline-block"');
		print '<span class="hideonsmartphone">'.$langs->trans("Status").'</span>';
		$formactions->form_select_status_action('formaction', $status, 1, 'search_status', 1, 2, 'minwidth100');
		print '</div>';
	}

	// Hooks
	$parameters = array('canedit'=>$canedit, 'pid'=>$pid, 'socid'=>$socid);
	$object = null;
	$reshook = $hookmanager->executeHooks('searchAgendaFrom', $parameters, $object, $action); // Note that $action and $object may have been

	print '<div style="clear:both"></div>';
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
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
	$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '".$db->idate($now)."'))";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;
	$sql .= " ORDER BY a.datep DESC, a.id DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("LastActionsToDo", $max).'</th>';
		print '<th colspan="2" class="right"><a class="commonlink" href="'.DOL_URL_ROOT.'/comm/action/list.php?action=show_list&status=todo">'.$langs->trans("FullList").'</a></th>';
		print '</tr>';

		$i = 0;

		$staticaction = new ActionComm($db);
		$customerstatic = new Client($db);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);


			print '<tr class="oddeven">';

			$staticaction->type_code = $obj->code;
			$staticaction->label = ($obj->label ? $obj->label : $obj->type_label);
			$staticaction->id = $obj->id;
			print '<td>'.$staticaction->getNomUrl(1, 34).'</td>';

			// print '<td>'.dol_trunc($obj->label,22).'</td>';

			print '<td>';
			if ($obj->socid > 0)
			{
				$customerstatic->id = $obj->socid;
				$customerstatic->name = $obj->name;
				//$customerstatic->name_alias = $obj->name_alias;
				$customerstatic->code_client = $obj->code_client;
				$customerstatic->code_compta = $obj->code_compta;
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
			if ($obj->percent == 0 && $datep && $datep < time()) $late = 1;
			if ($obj->percent == 0 && !$datep && $datep2 && $datep2 < time()) $late = 1;
			if ($obj->percent > 0 && $obj->percent < 100 && $datep2 && $datep2 < time()) $late = 1;
			if ($obj->percent > 0 && $obj->percent < 100 && !$datep2 && $datep && $datep < time()) $late = 1;
			if ($late) print img_warning($langs->trans("Late"));
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
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
	$sql .= " AND (a.percent >= 100 OR (a.percent = -1 AND a.datep2 <= '".$db->idate($now)."'))";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;
	$sql .= " ORDER BY a.datep2 DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("LastDoneTasks", $max).'</th>';
		print '<th colspan="2" class="right"><a class="commonlink" href="'.DOL_URL_ROOT.'/comm/action/list.php?action=show_list&status=done">'.$langs->trans("FullList").'</a></th>';
		print '</tr>';

		$i = 0;

		$staticaction = new ActionComm($db);
		$customerstatic = new Societe($db);

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);


			print '<tr class="oddeven">';

			$staticaction->type_code = $obj->code;
			$staticaction->libelle = $obj->label;
			$staticaction->id = $obj->id;
			print '<td>'.$staticaction->getNomUrl(1, 34).'</td>';

			//print '<td>'.dol_trunc($obj->label,24).'</td>';

			print '<td>';
			if ($obj->socid > 0)
			{
				$customerstatic->id = $obj->socid;
				$customerstatic->name = $obj->name;
				//$customerstatic->name_alias = $obj->name_alias;
				$customerstatic->code_client = $obj->code_client;
				$customerstatic->code_compta = $obj->code_compta;
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
		// TODO Ajouter rappel pour "il y a des contrats a mettre en service"
		// TODO Ajouter rappel pour "il y a des contrats qui arrivent a expiration"
		print "</table></div><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function agenda_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda_other.php";
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'other';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda.php";
	$head[$h][1] = $langs->trans("AutoActions");
	$head[$h][2] = 'autoactions';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda_reminder.php";
	$head[$h][1] = $langs->trans("Reminders");
	$head[$h][2] = 'reminders';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda_xcal.php";
	$head[$h][1] = $langs->trans("ExportCal");
	$head[$h][2] = 'xcal';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda_extsites.php";
	$head[$h][1] = $langs->trans("ExtSites");
	$head[$h][2] = 'extsites';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'agenda_admin');

	$head[$h][0] = DOL_URL_ROOT."/admin/agenda_extrafields.php";
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'agenda_admin', 'remove');


	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function actions_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("CardAction");
	$head[$h][2] = 'card';
	$h++;

	// Tab to link resources
	if ($conf->resource->enabled)
	{
		include_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
		$resource = new DolResource($db);

		$head[$h][0] = DOL_URL_ROOT.'/resource/element_resource.php?element=action&element_id='.$object->id;
		$listofresourcelinked = $resource->getElementResources($object->element, $object->id);
		$nbResources = (is_array($listofresourcelinked) ?count($listofresourcelinked) : 0);
		$head[$h][1] = $langs->trans("Resources");
		if ($nbResources > 0) $head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.($nbResources).'</span>' : '');
		$head[$h][2] = 'resources';
		$h++;
	}

	// Attached files
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->agenda->dir_output."/".$object->id;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/comm/action/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>' : '');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/info.php?id='.$object->id;
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
 *  @return array			    Array of head
 */
function calendars_prepare_head($param)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/list.php?action=show_list'.($param ? '&'.$param : '');
	$head[$h][1] = $langs->trans("ViewList");
	$head[$h][2] = 'cardlist';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/index.php?action=show_month'.($param ? '&'.$param : '');
	$head[$h][1] = $langs->trans("ViewCal");
	$head[$h][2] = 'cardmonth';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/index.php?action=show_week'.($param ? '&'.$param : '');
	$head[$h][1] = $langs->trans("ViewWeek");
	$head[$h][2] = 'cardweek';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/index.php?action=show_day'.($param ? '&'.$param : '');
	$head[$h][1] = $langs->trans("ViewDay");
	$head[$h][2] = 'cardday';
	$h++;

	//if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
	if (!empty($conf->global->AGENDA_SHOW_PERTYPE))
	{
		$head[$h][0] = DOL_URL_ROOT.'/comm/action/pertype.php'.($param ? '?'.$param : '');
		$head[$h][1] = $langs->trans("ViewPerType");
		$head[$h][2] = 'cardpertype';
		$h++;
	}

	$newparam = $param;
	$newparam = preg_replace('/&?search_filtert=\d+/', '', $newparam);
	$head[$h][0] = DOL_URL_ROOT.'/comm/action/peruser.php'.($newparam ? '?'.$newparam : '');
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
