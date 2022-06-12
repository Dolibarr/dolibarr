<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2014	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016	Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2019       Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2021		Frédéric France		<frederic.france@netlogic.fr>
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
 *   	\file       htdocs/hrm/index.php
 *		\ingroup    hrm
 *		\brief      Home page for HRM area.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
if (!empty($conf->deplacement->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
}
if (!empty($conf->expensereport->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
}
if (!empty($conf->recruitment->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
	require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
}
if (!empty($conf->holiday->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
}

$hookmanager = new HookManager($db);
$hookmanager->initHooks('hrmindex');

// Load translation files required by the page
$langs->loadLangs(array('users', 'holiday', 'trips', 'boxes'));

$socid = GETPOST("socid", "int");

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

if (empty($conf->global->MAIN_INFO_SOCIETE_NOM) || empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
	$setupcompanynotcomplete = 1;
}

$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;


/*
 * Actions
 */

// Update sold
if (!empty($conf->holiday->enabled) && !empty($setupcompanynotcomplete)) {
	$holidaystatic = new Holiday($db);
	$result = $holidaystatic->updateBalance();
}


/*
 * View
 */

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


print '<div class="fichecenter"><div class="fichethirdleft">';

if (!empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS)) {     // This is useless due to the global search combo
	if (!empty($conf->holiday->enabled) && $user->rights->holiday->read) {
		$langs->load("holiday");
		$listofsearchfields['search_holiday'] = array('text'=>'TitreRequestCP');
	}
	if (!empty($conf->deplacement->enabled) && $user->rights->deplacement->lire) {
		$langs->load("trips");
		$listofsearchfields['search_deplacement'] = array('text'=>'ExpenseReport');
	}
	if (!empty($conf->expensereport->enabled) && $user->rights->expensereport->lire) {
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
			print '<tr '.$bc[false].'>';
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


if (!empty($conf->holiday->enabled)) {
	if (empty($conf->global->HOLIDAY_HIDE_BALANCE)) {
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
	} elseif (!is_numeric($conf->global->HOLIDAY_HIDE_BALANCE)) {
		print $langs->trans($conf->global->HOLIDAY_HIDE_BALANCE).'<br>';
	}
}


print '</div><div class="fichetwothirdright">';



// Latest leave requests
if (!empty($conf->holiday->enabled) && $user->rights->holiday->read) {
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.email, u.photo, u.statut as user_status,";
	$sql .= " x.rowid, x.ref, x.fk_type, x.date_debut as date_start, x.date_fin as date_end, x.halfday, x.tms as dm, x.statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."holiday as x, ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE u.rowid = x.fk_user";
	$sql .= " AND x.entity = ".$conf->entity;
	if (empty($user->rights->holiday->readall)) {
		$sql .= ' AND x.fk_user IN ('.$db->sanitize(join(',', $childids)).')';
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
		print '<th colspan="3">'.$langs->trans("BoxTitleLastLeaveRequests", min($max, $num)).'</th>';
		print '<th>'.$langs->trans("from").'</th>';
		print '<th>'.$langs->trans("to").'</th>';
		print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/holiday/list.php?sortfield=cp.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
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

				print '<tr class="oddeven">';
				print '<td class="nowraponall">'.$holidaystatic->getNomUrl(1).'</td>';
				print '<td class="tdoverflowmax100">'.$userstatic->getNomUrl(-1, 'leave').'</td>';

				$leavecode = empty($typeleaves[$obj->fk_type]) ? 'Undefined' : $typeleaves[$obj->fk_type]['code'];
				print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($langs->trans($leavecode)).'">'.dol_escape_htmltag($langs->trans($leavecode)).'</td>';

				$starthalfday = ($obj->halfday == -1 || $obj->halfday == 2) ? 'afternoon' : 'morning';
				$endhalfday = ($obj->halfday == 1 || $obj->halfday == 2) ? 'morning' : 'afternoon';

				print '<td>'.dol_print_date($db->jdate($obj->date_start), 'day').' <span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
				print '<td>'.dol_print_date($db->jdate($obj->date_end), 'day').' <span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>';
				print '<td class="right nowrap" width="16">'.$holidaystatic->LibStatut($obj->status, 3, $holidaystatic->date_debut).'</td>';
				print '</tr>';

				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="7" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}


// Latest expense report
if (!empty($conf->expensereport->enabled) && $user->rights->expensereport->lire) {
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.email, u.statut as user_status, u.photo,";
	$sql .= " x.rowid, x.ref, x.date_debut as date, x.tms as dm, x.total_ttc, x.fk_statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as x, ".MAIN_DB_PREFIX."user as u";
	//if (empty($user->rights->societe->client->voir) && !$user->socid) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE u.rowid = x.fk_user_author";
	$sql .= " AND x.entity = ".$conf->entity;
	if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)) {
		$sql .= ' AND x.fk_user_author IN ('.$db->sanitize(join(',', $childids)).')';
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
		print '<th colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses", min($max, $num)).'</th>';
		print '<th class="right">'.$langs->trans("TotalTTC").'</th>';
		print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/expensereport/list.php?sortfield=d.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
		print '</tr>';
		if ($num) {
			$total_ttc = $totalam = $total = 0;

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
				$userstatic->statut = $obj->user_status;
				$userstatic->photo = $obj->photo;

				print '<tr class="oddeven">';
				print '<td class="tdoverflowmax200">'.$expensereportstatic->getNomUrl(1).'</td>';
				print '<td class="tdoverflowmax150">'.$userstatic->getNomUrl(-1).'</td>';
				print '<td class="right amount">'.price($obj->total_ttc).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>';
				print '<td class="right nowraponall" width="16">'.$expensereportstatic->LibStatut($obj->status, 3).'</td>';
				print '</tr>';

				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}


// Last modified job position
if (!empty($conf->recruitment->enabled) && $user->rights->recruitment->recruitmentjobposition->read) {
	$staticrecruitmentcandidature = new RecruitmentCandidature($db);
	$staticrecruitmentjobposition = new RecruitmentJobPosition($db);
	$sql = "SELECT rc.rowid, rc.ref, rc.email, rc.lastname, rc.firstname, rc.date_creation, rc.tms, rc.status,";
	$sql.= " rp.rowid as jobid, rp.ref as jobref, rp.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as rc";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as rp ON rc.fk_recruitmentjobposition = rp.rowid";
	if (isModEnabled('societe') && empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE rc.entity IN (".getEntity($staticrecruitmentcandidature->element).")";
	if (isModEnabled('societe') && empty($user->rights->societe->client->voir) && !$socid) {
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
		print '</th>';
		print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/recruitment/recruitmentcandidature_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
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
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day').'</td>';
				print '<td class="right nowrap" width="16">';
				print $staticrecruitmentcandidature->getLibStatut(3);
				print "</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table>";
		print "</div>";
		print "<br>";
	} else {
		dol_print_error($db);
	}
}

print '</div></div>';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardHRM', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
