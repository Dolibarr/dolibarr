<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2014	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016	Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2019       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2021-2024  Frédéric France		<frederic.france@free.fr>
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
 *    \file       htdocs/hrm/index.php
 *    \ingroup    hrm
 *    \brief      Home page for HRM area.
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

if (isModEnabled('deplacement')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
}
if (isModEnabled('expensereport')) {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
}
if (isModEnabled('recruitment')) {
	require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
	require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
}
if (isModEnabled('holiday')) {
	require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
}


// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager = new HookManager($db);

$hookmanager->initHooks('hrmindex');

// Load translation files required by the page
$langs->loadLangs(array('users', 'holiday', 'trips', 'boxes'));

// Get Parameters
$socid = GETPOSTINT("socid");

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

if (!getDolGlobalString('MAIN_INFO_SOCIETE_NOM') || !getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY')) {
	$setupcompanynotcomplete = 1;
}

$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);


/*
 * Actions
 */

// Update sold
if (isModEnabled('holiday') && !empty($setupcompanynotcomplete)) {
	$holidaystatic = new Holiday($db);
	$result = $holidaystatic->updateBalance();
}


/*
 * View
 */

$listofsearchfields = array();

$childids = $user->getAllChildIds();
$childids[] = $user->id;

$title = $langs->trans('HRMArea');

llxHeader('', $title, '');

print load_fiche_titre($langs->trans("HRMArea"), '', 'hrm');


if (!empty($setupcompanynotcomplete)) {
	$langs->load("errors");
	$warnpicto = img_warning($langs->trans("WarningMandatorySetupNotComplete"));
	print '<br><div class="warning"><a href="'.DOL_URL_ROOT.'/admin/company.php?mainmenu=home'.(empty($setupcompanynotcomplete) ? '' : '&action=edit&token='.newToken()).'">'.$warnpicto.' '.$langs->trans("WarningMandatorySetupNotComplete").'</a></div>';

	llxFooter();
	exit;
}


print '<div class="fichecenter">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';


if (getDolGlobalString('MAIN_SEARCH_FORM_ON_HOME_AREAS')) {     // This is useless due to the global search combo
	if (isModEnabled('holiday') && $user->hasRight('holiday', 'read')) {
		$langs->load("holiday");
		$listofsearchfields['search_holiday'] = array('text'=>'TitreRequestCP');
	}
	if (isModEnabled('deplacement') && $user->hasRight('deplacement', 'lire')) {
		$langs->load("trips");
		$listofsearchfields['search_deplacement'] = array('text'=>'ExpenseReport');
	}
	if (isModEnabled('expensereport') && $user->hasRight('expensereport', 'lire')) {
		$langs->load("trips");
		$listofsearchfields['search_expensereport'] = array('text'=>'ExpenseReport');
	}
	if (count($listofsearchfields)) {
		print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';
		$i = 0;
		foreach ($listofsearchfields as $key => $value) {
			if ($i == 0) {
				print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
			}
			print '<tr>';
			print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
			if ($i == 0) {
				print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
			}
			print '</tr>';
			$i++;
		}
		print '</table>';
		print '</div>';
		print '</form>';
		print '<br>';
	}
}


if (isModEnabled('holiday')) {
	if (!getDolGlobalString('HOLIDAY_HIDE_BALANCE')) {
		$holidaystatic = new Holiday($db);
		$user_id = $user->id;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';
		print '<tr class="liste_titre"><th colspan="3">'.$langs->trans("Holidays").'</th></tr>';
		print '<tr class="oddeven">';
		print '<td>';

		$out = '';
		$nb_holiday = 0;
		$typeleaves = $holidaystatic->getTypes(1, 1);
		foreach ($typeleaves as $key => $val) {
			$nb_type = $holidaystatic->getCPforUser($user->id, $val['rowid']);
			$nb_holiday += $nb_type;
			$out .= ' - '.($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']).': <strong>'.($nb_type ? price2num($nb_type) : 0).'</strong><br>';
		}
		$balancetoshow = $langs->trans('SoldeCPUser', '{s1}');
		print '<div class="valignmiddle div-balanceofleave">'.str_replace('{s1}', img_picto('', 'holiday', 'class="paddingleft pictofixedwidth"').'<span class="balanceofleave valignmiddle'.($nb_holiday > 0 ? ' amountpaymentcomplete' : ($nb_holiday < 0 ? ' amountremaintopay' : ' amountpaymentneutral')).'">'.round($nb_holiday, 5).'</span>', $balancetoshow).'</div>';
		print '<span class="opacitymedium">'.$out.'</span>';

		print '</td>';
		print '</tr>';
		print '</table></div><br>';
	} elseif (!is_numeric(getDolGlobalString('HOLIDAY_HIDE_BALANCE'))) {
		print $langs->trans(getDolGlobalString('HOLIDAY_HIDE_BALANCE')).'<br>';
	}
}


print '</div><div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';


// Latest modified leave requests
if (isModEnabled('holiday') && $user->hasRight('holiday', 'read')) {
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.email, u.photo, u.statut as user_status,";
	$sql .= " x.rowid, x.ref, x.fk_type, x.date_debut as date_start, x.date_fin as date_end, x.halfday, x.tms as dm, x.statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."holiday as x, ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE u.rowid = x.fk_user";
	$sql .= " AND x.entity = ".$conf->entity;
	if (!$user->hasRight('holiday', 'readall')) {
		$sql .= ' AND x.fk_user IN ('.$db->sanitize(implode(',', $childids)).')';
	}
	//if (empty($user->rights->societe->client->voir) && !$user->socid) $sql.= " AND x.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	//if (!empty($socid)) $sql.= " AND x.fk_soc = ".((int) $socid);
	$sql .= $db->order("x.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result) {
		$var = false;
		$num = $db->num_rows($result);

		$holidaystatic = new Holiday($db);
		$userstatic = new User($db);

		$listhalfday = array('morning'=>$langs->trans("Morning"), "afternoon"=>$langs->trans("Afternoon"));
		$typeleaves = $holidaystatic->getTypes(1, -1);

		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("BoxTitleLastLeaveRequests", min($max, $num));
		print '<a href="'.DOL_URL_ROOT.'/holiday/list.php?sortfield=cp.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		print '<span class="badge marginleftonlyshort">...</span>';
		print '</a>';
		print '</th>';
		print '<th></th>';
		print '<th></th>';
		print '<th></th>';
		print '<th class="right">';
		print '</th>';
		print '</tr>';

		if ($num) {
			while ($i < $num && $i < $max) {
				$obj = $db->fetch_object($result);

				$holidaystatic->id = $obj->rowid;
				$holidaystatic->ref = $obj->ref;
				$holidaystatic->statut = $obj->status;
				$holidaystatic->date_debut = $db->jdate($obj->date_start);

				$userstatic->id = $obj->uid;
				$userstatic->lastname = $obj->lastname;
				$userstatic->firstname = $obj->firstname;
				$userstatic->login = $obj->login;
				$userstatic->photo = $obj->photo;
				$userstatic->email = $obj->email;
				$userstatic->statut = $obj->user_status;
				$userstatic->status = $obj->user_status;

				print '<tr class="oddeven">';
				print '<td class="nowraponall">'.$holidaystatic->getNomUrl(1).'</td>';
				print '<td class="tdoverflowmax100">'.$userstatic->getNomUrl(-1, 'leave').'</td>';

				$leavecode = empty($typeleaves[$obj->fk_type]) ? 'Undefined' : $typeleaves[$obj->fk_type]['code'];
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($langs->trans($leavecode)).'">'.dol_escape_htmltag($langs->trans($leavecode)).'</td>';

				$starthalfday = ($obj->halfday == -1 || $obj->halfday == 2) ? 'afternoon' : 'morning';
				$endhalfday = ($obj->halfday == 1 || $obj->halfday == 2) ? 'morning' : 'afternoon';

				print '<td class="tdoverflowmax125">'.dol_print_date($db->jdate($obj->date_start), 'dayreduceformat').' <span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
				print '<td class="tdoverflowmax125">'.dol_print_date($db->jdate($obj->date_end), 'dayreduceformat').' <span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->dm), 'dayreduceformat').'</td>';
				print '<td class="right nowrap" width="16">'.$holidaystatic->LibStatut($obj->status, 3, $holidaystatic->date_debut).'</td>';
				print '</tr>';

				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}


// Latest modified expense report
if (isModEnabled('expensereport') && $user->hasRight('expensereport', 'read')) {
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.email, u.statut as user_status, u.photo,";
	$sql .= " x.rowid, x.ref, x.date_debut as date, x.tms as dm, x.total_ht, x.total_ttc, x.fk_statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as x, ".MAIN_DB_PREFIX."user as u";
	//if (empty($user->rights->societe->client->voir) && !$user->socid) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE u.rowid = x.fk_user_author";
	$sql .= " AND x.entity = ".$conf->entity;
	if (!$user->hasRight('expensereport', 'readall') && !$user->hasRight('expensereport', 'lire_tous')) {
		$sql .= ' AND x.fk_user_author IN ('.$db->sanitize(implode(',', $childids)).')';
	}
	//if (empty($user->rights->societe->client->voir) && !$user->socid) $sql.= " AND x.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	//if (!empty($socid)) $sql.= " AND x.fk_soc = ".((int) $socid);
	$sql .= $db->order("x.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);

		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses", min($max, $num));
		print '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?sortfield=d.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		print '<span class="badge marginleftonlyshort">...</span>';
		//print img_picto($langs->trans("FullList"), 'expensereport');
		print '</a>';
		print '</th>';
		print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th></th>';
		print '<th class="right">';
		print '</th>';
		print '</tr>';

		if ($num) {
			$expensereportstatic = new ExpenseReport($db);
			$userstatic = new User($db);
			while ($i < $num && $i < $max) {
				$obj = $db->fetch_object($result);

				$expensereportstatic->id = $obj->rowid;
				$expensereportstatic->ref = $obj->ref;
				$expensereportstatic->statut = $obj->status;
				$expensereportstatic->status = $obj->status;

				$userstatic->id = $obj->uid;
				$userstatic->lastname = $obj->lastname;
				$userstatic->firstname = $obj->firstname;
				$userstatic->email = $obj->email;
				$userstatic->login = $obj->login;
				$userstatic->status = $obj->user_status;
				$userstatic->photo = $obj->photo;

				print '<tr class="oddeven">';
				print '<td class="tdoverflowmax200">'.$expensereportstatic->getNomUrl(1).'</td>';
				print '<td class="tdoverflowmax150">'.$userstatic->getNomUrl(-1).'</td>';
				print '<td class="right amount">'.price($obj->total_ht).'</td>';
				print '<td class="right amount">'.price($obj->total_ttc).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->dm), 'dayreduceformat').'</td>';
				print '<td class="right nowraponall" width="16">'.$expensereportstatic->LibStatut($obj->status, 3).'</td>';
				print '</tr>';

				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}


// Last modified job position
if (isModEnabled('recruitment') && $user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
	$staticrecruitmentcandidature = new RecruitmentCandidature($db);
	$staticrecruitmentjobposition = new RecruitmentJobPosition($db);
	$sql = "SELECT rc.rowid, rc.ref, rc.email, rc.lastname, rc.firstname, rc.date_creation, rc.tms, rc.status,";
	$sql.= " rp.rowid as jobid, rp.ref as jobref, rp.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as rc";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as rp ON rc.fk_recruitmentjobposition = rp.rowid";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE rc.entity IN (".getEntity($staticrecruitmentcandidature->element).")";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND rp.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND rp.fk_soc = $socid";
	}
	$sql .= $db->order("rc.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">';
		print $langs->trans("BoxTitleLatestModifiedCandidatures", min($max, $num));
		print '<a href="'.DOL_URL_ROOT.'/recruitment/recruitmentcandidature_list.php?sortfield=t.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		print '<span class="badge marginleftonlyshort">...</span>';
		//print img_picto($langs->trans("FullList"), 'recruitmentcandidature');
		print '</a>';
		print '</th>';
		print '<th></th>';
		print '<th class="right">';
		print '</th>';
		print '</tr>';
		if ($num) {
			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$staticrecruitmentcandidature->id = $objp->rowid;
				$staticrecruitmentcandidature->ref = $objp->ref;
				$staticrecruitmentcandidature->email = $objp->email;
				$staticrecruitmentcandidature->status = $objp->status;
				$staticrecruitmentcandidature->date_creation = $objp->date_creation;
				$staticrecruitmentcandidature->firstname = $objp->firstname;
				$staticrecruitmentcandidature->lastname = $objp->lastname;

				$staticrecruitmentjobposition->id = $objp->jobid;
				$staticrecruitmentjobposition->ref = $objp->jobref;
				$staticrecruitmentjobposition->label = $objp->label;

				print '<tr class="oddeven">';
				print '<td class="nowraponall">'.$staticrecruitmentcandidature->getNomUrl(1, '').'</td>';
				print '<td class="tdoverflowmax150">'.$staticrecruitmentcandidature->getFullName($langs).'</td>';
				print '<td class="nowraponall">'.$staticrecruitmentjobposition->getNomUrl(1).'</td>';
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'dayreduceformat').'</td>';
				print '<td class="right nowrap" width="16">';
				print $staticrecruitmentcandidature->getLibStatut(3);
				print "</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		print "</table>";
		print "</div>";
		print "<br>";
	} else {
		dol_print_error($db);
	}
}

print '</div></div></div>';

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardHRM', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
