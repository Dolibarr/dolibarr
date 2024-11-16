<?php
/* Copyright (C) 2012       Mikael Carlavan         <contact@mika-carl.fr>
 * Copyright (C) 2017       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2017       Pierre-Henry Favre      <phf@atm-consulting.fr>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/admin/expensereport_rules.php
 *		\ingroup    expensereport
 *		\brief      Page to display expense tax ik
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_rule.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "trips", "errors", "dict"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('admin', 'dictionaryadmin','expensereport_rules'));

$object = new ExpenseReportRule($db);

if (!$user->admin) {
	accessforbidden();
}


/*
 * Action
 */

$parameters = array();
$rules = array();
$tab_apply = array();
$tab_rules_type = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	//Init error
	$error = false;

	$action = GETPOST('action', 'aZ09');
	$id = GETPOSTINT('id');

	$apply_to = GETPOST('apply_to');
	$fk_user = GETPOSTINT('fk_user');
	$fk_usergroup = GETPOSTINT('fk_usergroup');
	$restrictive = GETPOSTINT('restrictive');
	$fk_c_type_fees = GETPOSTINT('fk_c_type_fees');
	$code_expense_rules_type = GETPOST('code_expense_rules_type');
	$dates = dol_mktime(12, 0, 0, GETPOSTINT('startmonth'), GETPOSTINT('startday'), GETPOSTINT('startyear'));
	$datee = dol_mktime(12, 0, 0, GETPOSTINT('endmonth'), GETPOSTINT('endday'), GETPOSTINT('endyear'));
	$amount = (float) price2num(GETPOST('amount'), 'MT', 2);

	if (!empty($id)) {
		$result = $object->fetch($id);
		if ($result < 0) {
			dol_print_error(null, $object->error, $object->errors);
		}
	}

	if ($action == 'save') {
		$error = 0;

		// check parameters
		if (empty($apply_to)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpenseReportApplyTo")), null, 'errors');
		}
		if (empty($fk_c_type_fees)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpenseReportDomain")), null, 'errors');
		}
		if (empty($code_expense_rules_type)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpenseReportLimitOn")), null, 'errors');
		}
		if (empty($dates)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpenseReportDateStart")), null, 'errors');
		}
		if (empty($datee)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpenseReportDateEnd")), null, 'errors');
		}
		if (empty($amount)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpenseReportLimitAmount")), null, 'errors');
		}

		if (empty($error)) {
			if ($apply_to == 'U') {
				$object->fk_user = (int) $fk_user;
				$object->fk_usergroup = 0;
				$object->is_for_all = 0;
			} elseif ($apply_to == 'G') {
				$object->fk_usergroup = (int) $fk_usergroup;
				$object->fk_user = 0;
				$object->is_for_all = 0;
			} elseif ($apply_to == 'A') {
				$object->is_for_all = 1;
				$object->fk_user = 0;
				$object->fk_usergroup = 0;
			}

			$object->dates = $dates;
			$object->datee = $datee;
			$object->restrictive = $restrictive;
			$object->fk_c_type_fees = $fk_c_type_fees;
			$object->code_expense_rules_type = $code_expense_rules_type;
			$object->amount = $amount;
			$object->entity = $conf->entity;

			if ($object->id > 0) {
				$res = $object->update($user);
			} else {
				$res = $object->create($user);
			}
			if ($res > 0) {
				setEventMessages($langs->trans('ExpenseReportRuleSave'), null);
			} else {
				dol_print_error($object->db);
				$error++;
			}

			if (!$error) {
				header('Location: ' . $_SERVER['PHP_SELF']);
				exit;
			} else {
				$action = '';
			}
		}
	} elseif ($action == 'delete') {
		// TODO add confirm
		$res = $object->delete($user);

		if ($res < 0) {
			dol_print_error($object->db);
		}

		header('Location: ' . $_SERVER['PHP_SELF']);
		exit;
	}

	$rules = $object->getAllRule();

	$tab_apply = array(
		'A' => $langs->trans('All'),
		'G' => $langs->trans('UserGroup'),
		'U' => $langs->trans('User')
	);
	$tab_rules_type = array(
		'EX_DAY' => $langs->trans('Day'),
		'EX_MON' => $langs->trans('Month'),
		'EX_YEA' => $langs->trans('Year'),
		'EX_EXP' => $langs->trans('OnExpense')
	);
}


/*
 * View
 */

llxHeader('', $langs->trans("ExpenseReportsSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-expensereport_rules');

$form = new Form($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("ExpenseReportsSetup"), $linkback, 'title_setup');

$head = expensereport_admin_prepare_head();
print dol_get_fiche_head($head, 'expenserules', $langs->trans("ExpenseReportsRules"), -1, 'trip');

echo '<span class="opacitymedium">' . $langs->trans('ExpenseReportRulesDesc') . '</span>';
print '<br><br>';

if ($action != 'edit') {
	echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
	echo '<input type="hidden" name="token" value="' . newToken() . '" />';
	echo '<input type="hidden" name="action" value="save" />';

	echo '<table class="noborder centpercent">';

	echo '<tr class="liste_titre headerexpensereportrules">';
	echo '<th class="linecolapplyto">' . $langs->trans('ExpenseReportApplyTo') . '</th>';
	echo '<th class="linecoltype">' . $langs->trans('Type') . '</th>';
	echo '<th class="linecollimiton">' . $langs->trans('ExpenseReportLimitOn') . '</th>';
	echo '<th class="linecoldatestart">' . $langs->trans('ExpenseReportDateStart') . '</th>';
	echo '<th class="linecoldateend">' . $langs->trans('ExpenseReportDateEnd') . '</th>';
	echo '<th class="linecollimitamount">' . $langs->trans('ExpenseReportLimitAmount') . '</th>';
	echo '<th class="linecolrestrictive">' . $langs->trans('ExpenseReportRestrictive') . '</th>';
	echo '<th>&nbsp;</th>';
	echo '</tr>';

	echo '<tr class="oddeven">';
	echo '<td>';
	echo '<div class="float linecolapplyto">' . $form->selectarray('apply_to', $tab_apply, '', 0) . '</div>';
	echo '<div id="user" class="float linecoluser">' . $form->select_dolusers('', 'fk_user') . '</div>';
	echo '<div id="group" class="float linecolgroup">' . $form->select_dolgroups(0, 'fk_usergroup') . '</div>';
	echo '</td>';

	echo '<td class="linecoltype">' . $form->selectExpense('', 'fk_c_type_fees', 0, 1, 1) . '</td>';
	echo '<td class="linecoltyperule">' . $form->selectarray('code_expense_rules_type', $tab_rules_type, '', 0) . '</td>';
	echo '<td class="linecoldatestart">' . $form->selectDate(strtotime(date('Y-m-01', dol_now())), 'start', 0, 0, 0, '', 1, 0) . '</td>';
	echo '<td class="linecoldateend">' . $form->selectDate(strtotime(date('Y-m-t', dol_now())), 'end', 0, 0, 0, '', 1, 0) . '</td>';
	echo '<td class="linecolamount"><input type="text" value="" class="maxwidth100" name="amount" class="amount right" /></td>';
	echo '<td class="linecolrestrictive">' . $form->selectyesno('restrictive', 0, 1) . '</td>';
	echo '<td class="right linecolbutton"><input type="submit" class="button button-add" value="' . $langs->trans('Add') . '" /></td>';
	echo '</tr>';

	echo '</table>';
	echo '</form>';
}


echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
echo '<input type="hidden" name="token" value="' . newToken() . '" />';

if ($action == 'edit') {
	echo '<input type="hidden" name="id" value="' . $object->id . '" />';
	echo '<input type="hidden" name="action" value="save" />';
}

print dol_get_fiche_end();


echo '<table class="noborder centpercent">';

echo '<tr class="liste_titre expensereportrules">';
echo '<th class="linecolapplyto">' . $langs->trans('ExpenseReportApplyTo') . '</th>';
echo '<th class="linecoltype">' . $langs->trans('Type') . '</th>';
echo '<th class="linecollimiton">' . $langs->trans('ExpenseReportLimitOn') . '</th>';
echo '<th class="linecoldatestart">' . $langs->trans('ExpenseReportDateStart') . '</th>';
echo '<th class="linecoldateend">' . $langs->trans('ExpenseReportDateEnd') . '</th>';
echo '<th class="linecollimitamount">' . $langs->trans('ExpenseReportLimitAmount') . '</th>';
echo '<th class="linecolrestrictive">' . $langs->trans('ExpenseReportRestrictive') . '</th>';
echo '<th>&nbsp;</th>';
echo '</tr>';

foreach ($rules as $rule) {
	echo '<tr class="oddeven linetrdata" id="'.$rule->id.'">';

	echo '<td class="linecolusergroup">';
	if ($action == 'edit' && $object->id == $rule->id) {
		$selected = ($object->is_for_all > 0) ? 'A' : ($object->fk_usergroup > 0 ? 'G' : 'U');
		echo '<div class="float">' . $form->selectarray('apply_to', $tab_apply, $selected, 0) . '</div>';
		echo '<div id="user" class="float">' . $form->select_dolusers($object->fk_user, 'fk_user') . '</div>';
		echo '<div id="group" class="float">' . $form->select_dolgroups($object->fk_usergroup, 'fk_usergroup') . '</div>';
	} else {
		if ($rule->is_for_all > 0) {
			echo $tab_apply['A'];
		} elseif ($rule->fk_usergroup > 0) {
			echo $tab_apply['G'] . ' (' . $rule->getGroupLabel() . ')';
		} elseif ($rule->fk_user > 0) {
			echo $tab_apply['U'] . ' (' . $rule->getUserName() . ')';
		}
	}
	echo '</td>';

	echo '<td class="linecoltype">';
	if ($action == 'edit' && $object->id == $rule->id) {
		echo $form->selectExpense($object->fk_c_type_fees, 'fk_c_type_fees', 0, 1, 1);
	} else {
		if ($rule->fk_c_type_fees == -1) {
			echo $langs->trans('AllExpenseReport');
		} else {
			$key = getDictionaryValue('c_type_fees', 'code', $rule->fk_c_type_fees, false, 'id');
			if ($key && $key != $langs->trans($key)) {
				echo $langs->trans($key);
			} else {
				$value = getDictionaryValue('c_type_fees', 'label', $rule->fk_c_type_fees, false, 'id');
				echo $langs->trans($value ? $value : 'Undefined'); // TODO check to return trans of 'code'
			}
		}
	}
	echo '</td>';


	echo '<td class="linecoltyperule">';
	if ($action == 'edit' && $object->id == $rule->id) {
		echo $form->selectarray('code_expense_rules_type', $tab_rules_type, $object->code_expense_rules_type, 0);
	} else {
		echo $tab_rules_type[$rule->code_expense_rules_type];
	}
	echo '</td>';


	echo '<td class="linecoldatestart">';
	if ($action == 'edit' && $object->id == $rule->id) {
		print $form->selectDate(strtotime(date('Y-m-d', $object->dates)), 'start', 0, 0, 0, '', 1, 0);
	} else {
		echo dol_print_date($rule->dates, 'day');
	}
	echo '</td>';


	echo '<td class="linecoldateend">';
	if ($action == 'edit' && $object->id == $rule->id) {
		print $form->selectDate(strtotime(date('Y-m-d', $object->datee)), 'end', 0, 0, 0, '', 1, 0);
	} else {
		echo dol_print_date($rule->datee, 'day');
	}
	echo '</td>';

	// Amount
	echo '<td class="linecolamount">';
	if ($action == 'edit' && $object->id == $rule->id) {
		echo '<input type="text" value="' . price2num($object->amount) . '" name="amount" class="amount width50 right" />';
	} else {
		echo price($rule->amount, 0, $langs, 1, -1, -1, $conf->currency);
	}
	echo '</td>';


	echo '<td class="linecolrestrictive">';
	if ($action == 'edit' && $object->id == $rule->id) {
		echo $form->selectyesno('restrictive', $object->restrictive, 1);
	} else {
		echo yn($rule->restrictive, 1, 1);
	}
	echo '</td>';


	echo '<td class="center">';
	if ($object->id != $rule->id) {
		echo '<a class="editfielda paddingright paddingleft" href="' . $_SERVER['PHP_SELF'] . '?action=edit&token=' . newToken() . '&id=' . $rule->id . '">' . img_edit() . '</a>&nbsp;';
		echo '<a class="paddingright paddingleft" href="' . $_SERVER['PHP_SELF'] . '?action=delete&token=' . newToken() . '&id=' . $rule->id . '">' . img_delete() . '</a>';
	} else {
		echo '<input type="submit" class="button button-edit" value="' . $langs->trans('Update') . '" />&nbsp;';
		echo '<a href="' . $_SERVER['PHP_SELF'] . '" class="button button-cancel">' . $langs->trans("Cancel") . '</a>';
	}
	echo '</td>';

	echo '</tr>';
}

if (!is_array($rules) || count($rules) == 0) {
	print '<tr class="none"><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
}

echo '</table>';
echo '</form>';

echo '<script type="text/javascript"> $(function() {
	$("#apply_to").change(function() {
		var value = $(this).val();
		if (value == "A") {
			$("#group").hide(); $("#user").hide();
		} else if (value == "U") {
			$("#user").show();
			$("#group").hide();
		} else if (value == "G") {
			$("#group").show();
			$("#user").hide();
		}
	});

	$("#apply_to").change();

}); </script>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
