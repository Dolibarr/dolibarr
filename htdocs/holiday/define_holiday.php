<?php
/* Copyright (C) 2007-2022	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2011		Dimitri Mouillard			<dmouillard@teclib.com>
 * Copyright (C) 2013		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2016		Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *		File that defines the balance of paid holiday of users.
 *
 *   	\file       htdocs/holiday/define_holiday.php
 *		\ingroup    holiday
 *		\brief      File that defines the balance of paid holiday of users.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

// Load translation files required by the page
$langs->loadlangs(array('users', 'other', 'holiday', 'hrm'));

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'defineholidaylist';
$massaction = GETPOST('massaction', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('optioncss', 'aZ');

$search_name = GETPOST('search_name', 'alpha');
$search_supervisor = GETPOST('search_supervisor', "intcomma");

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$confirm = GETPOST('confirm', 'alpha');

$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "t.rowid"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "ASC";
}


// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('defineholidaylist'));
$extrafields = new ExtraFields($db);

$holiday = new Holiday($db);

$arrayfields = array(
	'cp.rowid' => array('label' => $langs->trans("Employee"), 'checked' => 1, 'position' => 20),
	'cp.fk_user' => array('label' => $langs->trans("Supervisor"), 'checked' => 1, 'position' => 30),
	'cp.nbHoliday' => array('label' => $langs->trans("MenuConfCP"), 'checked' => 1, 'position' => 40),
	'cp.note_public' => array('label' => $langs->trans("Note"), 'checked' => 1, 'position' => 50),
);

$permissiontoread = $user->hasRight('holiday', 'read');
$permissiontoreadall = $user->hasRight('holiday', 'readall');
$permissiontowrite = $user->hasRight('holiday', 'write');
$permissiontowriteall = $user->hasRight('holiday', 'writeall');
$permissiontodelete = $user->hasRight('holiday', 'delete');

$permissiontoapprove = $user->hasRight('holiday', 'approve');
$permissiontosetup = $user->hasRight('holiday', 'define_holiday');

if (!isModEnabled('holiday')) {
	accessforbidden('Module not enabled');
}

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

// If the user does not have perm to read the page
if (!$user->hasRight('holiday', 'read')) {
	accessforbidden();
}


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

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_name = '';
		$search_supervisor = '';
		$toselect = array();
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Holiday';
	$objectlabel = 'Holiday';
	$uploaddir = $conf->holiday->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	// If there is an update action
	if ($action == 'update' && GETPOSTISSET('update_cp') && $permissiontosetup) {
		$error = 0;
		$nbok = 0;

		$typeleaves = $holiday->getTypes(1, 1);

		$userID = array_keys(GETPOST('update_cp'));
		$userID = $userID[0];

		$db->begin();

		foreach ($typeleaves as $key => $val) {
			$userValue = GETPOST('nb_holiday_'.$val['rowid']);
			$userValue = $userValue[$userID];

			if (!empty($userValue) || (string) $userValue == '0') {
				$userValue = price2num($userValue, 5);
			} else {
				$userValue = '';
			}

			//If the user set a comment, we add it to the log comment
			$note_holiday = GETPOST('note_holiday');
			$comment = ((isset($note_holiday[$userID]) && !empty($note_holiday[$userID])) ? ' ('.$note_holiday[$userID].')' : '');

			//print 'holiday: '.$val['rowid'].'-'.$userValue;exit;
			if ($userValue != '') {
				// We add the modification to the log (must be done before the update of balance because we read current value of balance inside this method)
				$result = $holiday->addLogCP($user->id, $userID, $langs->transnoentitiesnoconv('ManualUpdate').$comment, $userValue, $val['rowid']);
				if ($result < 0) {
					setEventMessages($holiday->error, $holiday->errors, 'errors');
					$error++;
				}

				// Update of the days of the employee
				if ($result > 0) {
					$nbok++;

					$result = $holiday->updateSoldeCP($userID, $userValue, $val['rowid']);
					if ($result < 0) {
						setEventMessages($holiday->error, $holiday->errors, 'errors');
						$error++;
					}
				}

				// If it first update of balance, we set date to avoid to have sold incremented by new month
				/*
				$now=dol_now();
				$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
				$sql.= " value = '".dol_print_date($now,'%Y%m%d%H%M%S')."'";
				$sql.= " WHERE name = 'lastUpdate' and value IS NULL";	// Add value IS NULL to be sure to update only at init.
				dol_syslog('define_holiday update lastUpdate entry', LOG_DEBUG);
				$result = $db->query($sql);
				*/
			}
		}

		if (!$error && !$nbok) {
			setEventMessages($langs->trans("HolidayQtyNotModified", $user->login), null, 'warnings');
		}

		if (!$error) {
			$db->commit();

			if ($nbok > 0) {
				setEventMessages('UpdateConfCPOK', null, 'mesgs');
			}
		} else {
			$db->rollback();
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$userstatic = new User($db);


$title = $langs->trans('CPTitreMenu');
$help_url = 'EN:Module_Holiday';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-holiday page-define_holiday');

$typeleaves = $holiday->getTypes(1, 1);
$result = $holiday->updateBalance(); // Create users into table holiday if they don't exists. TODO Remove this whif we use field into table user.
if ($result < 0) {
	setEventMessages($holiday->error, $holiday->errors, 'errors');
}

// List of mass actions available
$arrayofmassactions = array(
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if ($permissiontosetup) {
	$arrayofmassactions['preincreaseholiday'] = img_picto('', 'add', 'class="pictofixedwidth"').$langs->trans("IncreaseHolidays");
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$title = $langs->trans("MenuConfCP");
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $massactionbutton, 0, '', 'title_hrm', 0, '', '', $limit, 0, 0, 1);

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($massaction == 'preincreaseholiday') {
	$langs->load("holiday", "hrm");
	require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
	$staticholiday = new Holiday($db);
	$arraytypeholidays = $staticholiday->getTypes(1, 1);
	$formquestion = array();
	$labeltypes = array();
	foreach ($typeleaves as $key => $val) {
		$labeltypes[$val['id']] = ($langs->trans($val['code']) != $val['code']) ? $langs->trans($val['code']) : $langs->trans($val['label']);
	}
	$formquestion [] = array( 'type' => 'other',
		'name' => 'typeofholiday',
		'label' => $langs->trans("Type"),
		'value' => $form->selectarray('typeholiday', $labeltypes, GETPOST('typeholiday', 'alpha'), 1)
	);
	$formquestion [] = array( 'type' => 'other',
		'name' => 'nbdaysholydays',
		'label' => $langs->trans("NumberDayAddMass"),
		'value' => '<input name="nbdaysholidays" class="maxwidth75" id="nbdaysholidays" value="'.GETPOSTINT('nbdaysholidays').'">'
	);
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassIncreaseHoliday"), $langs->trans("ConfirmMassIncreaseHolidayQuestion", count($toselect)), "increaseholiday", $formquestion, 1, 0, 200, 500, 1);
}

print '<div class="info">'.$langs->trans('LastUpdateCP').': '."\n";
$lastUpdate = $holiday->getConfCP('lastUpdate');
if ($lastUpdate) {
	print '<strong>'.dol_print_date($db->jdate($lastUpdate), 'dayhour').'</strong>';
	print '<br>'.$langs->trans("MonthOfLastMonthlyUpdate").': <strong>'.$langs->trans('Month'.substr($lastUpdate, 4, 2)).' '.substr($lastUpdate, 0, 4).'</strong>'."\n";
} else {
	print $langs->trans('None');
}
print "</div><br>\n";


$filters = '';

// Filter on array of ids of all children
$userchilds = array();
if (!$permissiontoreadall) {
	$userchilds = $user->getAllChildIds(1);
	$filters .= ' AND u.rowid IN ('.$db->sanitize(implode(', ', $userchilds)).')';
}
if (!empty($search_name)) {
	$filters .= natural_search(array('u.firstname', 'u.lastname'), $search_name);
}
if ($search_supervisor > 0) {
	$filters .= natural_search(array('u.fk_user'), $search_supervisor, 2);
}
$filters .= ' AND employee = 1'; // Only employee users are visible

$listUsers = $holiday->fetchUsers(false, true, $filters);
if (is_numeric($listUsers) && $listUsers < 0) {
	setEventMessages($holiday->error, $holiday->errors, 'errors');
}

$i = 0;



if (count($typeleaves) == 0) {
	//print '<div class="info">';
	print $langs->trans("NoLeaveWithCounterDefined")."<br>\n";
	print $langs->trans("GoIntoDictionaryHolidayTypes");
	//print '</div>';
} else {
	$canedit = 0;
	if ($permissiontosetup) {
		$canedit = 1;
	}

	$moreforfilter = '';

	$selectedfields = '';
	if ($massactionbutton) {
		$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
		$selectedfields .= ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) : ''); // This also change content of $arrayfields
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'" id="tablelines3">'."\n";

	print '<tr class="liste_titre_filter">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch center">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	// User
	if (!empty($arrayfields['cp.rowid']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" name="search_name" value="'.dol_escape_htmltag($search_name).'" class="maxwidth100">';
		print '</td>';
	}
	// Supervisor
	if (!empty($arrayfields['cp.fk_user']['checked'])) {
		print '<td class="liste_titre">';
		print $form->select_dolusers($search_supervisor, 'search_supervisor', 1, null, 0, null, null, 0, 0, 0, '', 0, '', 'maxwidth150');
		print '</td>';
	}
	// Type of leave request
	if (!empty($arrayfields['cp.nbHoliday']['checked'])) {
		if (count($typeleaves)) {
			foreach ($typeleaves as $key => $val) {
				print '<td class="liste_titre" style="text-align:center"></td>';
			}
		} else {
			print '<td class="liste_titre"></td>';
		}
	}
	if (!empty($arrayfields['cp.note_public']['checked'])) {
		print '<td class="liste_titre"></td>';
	}
	print '<td class="liste_titre"></td>';

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch center">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}

	print '</tr>';

	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	}
	if (!empty($arrayfields['cp.rowid']['checked'])) {
		print_liste_field_titre('Employee', $_SERVER["PHP_SELF"]);
	}
	if (!empty($arrayfields['cp.fk_user']['checked'])) {
		print_liste_field_titre('Supervisor', $_SERVER["PHP_SELF"]);
	}
	if (!empty($arrayfields['cp.nbHoliday']['checked'])) {
		if (count($typeleaves)) {
			foreach ($typeleaves as $key => $val) {
				$labeltype = ($langs->trans($val['code']) != $val['code']) ? $langs->trans($val['code']) : $langs->trans($val['label']);
				print_liste_field_titre($labeltype, $_SERVER["PHP_SELF"], '', '', '', '', '', '', 'center ');
			}
		} else {
			print_liste_field_titre('NoLeaveWithCounterDefined', $_SERVER["PHP_SELF"], '', '', '', '');
		}
	}
	if (!empty($arrayfields['cp.note_public']['checked'])) {
		print_liste_field_titre($permissiontosetup ? 'Note' : '', $_SERVER["PHP_SELF"]);
	}
	print_liste_field_titre('');
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	}
	print '</tr>';
	$usersupervisor = new User($db);

	foreach ($listUsers as $users) {
		$arrayofselected = is_array($toselect) ? $toselect : array();

		// If user has not permission to edit/read all, we must see only subordinates
		if (!$permissiontoreadall) {
			if (($users['rowid'] != $user->id) && (!in_array($users['rowid'], $userchilds))) {
				continue; // This user is not into hierarchy of current user, we hide it.
			}
		}

		$userstatic->id = $users['rowid'];
		$userstatic->lastname = $users['lastname'];
		$userstatic->firstname = $users['firstname'];
		$userstatic->gender = $users['gender'];
		$userstatic->photo = $users['photo'];
		$userstatic->status = $users['status'];
		$userstatic->employee = $users['employee'];
		$userstatic->fk_user = $users['fk_user'];

		if ($userstatic->fk_user > 0) {
			$usersupervisor->fetch($userstatic->fk_user);
		}

		print '<tr class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';

			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($userstatic->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$userstatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$userstatic->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
		}

		// User
		if (!empty($arrayfields['cp.rowid']['checked'])) {
			print '<td>';
			print $userstatic->getNomUrl(-1);
			print '</td>';
		}
		// Supervisor
		if (!empty($arrayfields['cp.fk_user']['checked'])) {
			print '<td>';
			if ($userstatic->fk_user > 0) {
				print $usersupervisor->getNomUrl(-1);
			}
			print '</td>';
		}

		// Amount for each type
		if (!empty($arrayfields['cp.nbHoliday']['checked'])) {
			if (count($typeleaves)) {
				foreach ($typeleaves as $key => $val) {
					$nbtoshow = '';
					if ($holiday->getCPforUser($users['rowid'], $val['rowid']) != '') {
						$nbtoshow = price2num($holiday->getCPforUser($users['rowid'], $val['rowid']), 5);
					}

					//var_dump($users['rowid'].' - '.$val['rowid']);
					print '<td style="text-align:center">';
					if ($canedit) {
						print '<input type="text"'.($canedit ? '' : ' disabled="disabled"').' value="'.$nbtoshow.'" name="nb_holiday_'.$val['rowid'].'['.$users['rowid'].']" class="width75 center" />';
					} else {
						print $nbtoshow;
					}
					//print ' '.$langs->trans('days');
					print '</td>'."\n";
				}
			} else {
				print '<td></td>';
			}
		}

		// Note
		if (!empty($arrayfields['cp.note_public']['checked'])) {
			print '<td>';
			if ($canedit) {
				print '<input type="text"'.($canedit ? '' : ' disabled="disabled"').' class="maxwidthonsmartphone" value="" name="note_holiday['.$users['rowid'].']" size="30"/>';
			}
			print '</td>';
		}

		// Button modify
		print '<td class="center">';
		if ($permissiontosetup) {	// Allowed to set the balance of any user
			print '<input type="submit" name="update_cp['.$users['rowid'].']" value="'.dol_escape_htmltag($langs->trans("Save")).'" class="button smallpaddingimp"/>';
		}
		print '</td>'."\n";

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';

			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($userstatic->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$userstatic->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$userstatic->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
		}

		print '</tr>';

		$i++;
	}

	if (count($listUsers) <= 0) {
		$colspan = 2;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				if ($key == 'cp.nbHoliday') {
					foreach ($typeleaves as $leave_key => $leave_val) {
						$colspan++;
					}
				} else {
					$colspan++;
				}
			}
		}
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print '</table>';
	print '</div>';
}

print '</form>';

// End of page
llxFooter();
$db->close();
