<?php
/* Copyright (C) 2012       Mikael Carlavan         <contact@mika-carl.fr>
 * Copyright (C) 2017       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2017       Pierre-Henry Favre      <phf@atm-consulting.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       htdocs/admin/expensereport_ik.php
 *		\ingroup    expensereport
 *		\brief      Page to display expense tax ik
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_rule.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","other","trips","errors","dict"));

if (!$user->admin) accessforbidden();

//Init error
$error = false;
$message = false;

$action = GETPOST('action','alpha');
$id = GETPOST('id','int');

$apply_to = GETPOST('apply_to');
$fk_user = GETPOST('fk_user');
$fk_usergroup = GETPOST('fk_usergroup');

$fk_c_type_fees = GETPOST('fk_c_type_fees');
$code_expense_rules_type = GETPOST('code_expense_rules_type');
$dates = dol_mktime(12, 0, 0, GETPOST('startmonth'), GETPOST('startday'), GETPOST('startyear'));
$datee = dol_mktime(12, 0, 0, GETPOST('endmonth'), GETPOST('endday'), GETPOST('endyear'));
$amount = GETPOST('amount');
$restrictive = GETPOST('restrictive');

$object = new ExpenseReportRule($db);
if (!empty($id))
{
	$result = $object->fetch($id);
	if ($result < 0) dol_print_error('', $object->error, $object->errors);
}

// TODO do action
if ($action == 'save')
{
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

	if (empty($error))
	{
		$object->setValues($_POST);

		if($apply_to=='U'){
			$object->fk_user=$fk_user;
			$object->fk_usergroup=0;
			$object->is_for_all=0;
		}elseif($apply_to=='G'){
			$object->fk_usergroup=$fk_usergroup;
			$object->fk_user=0;
			$object->is_for_all=0;
		}elseif($apply_to=='A'){
			$object->is_for_all=1;
			$object->fk_user=0;
			$object->fk_usergroup=0;
		}

		$object->dates = $dates;
		$object->datee = $datee;

		$object->entity = $conf->entity;

		$res = $object->create($user);
		if ($res > 0) setEventMessages($langs->trans('ExpenseReportRuleSave'), null);
		else dol_print_error($object->db);

		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
}
elseif ($action == 'delete')
{
	// TODO add confirm
	$res = $object->delete($user);

	if ($res < 0) dol_print_error($object->db);

	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

$rules = ExpenseReportRule::getAllRule();

$tab_apply = array('A' => $langs->trans('All'), 'G' => $langs->trans('Group'), 'U' => $langs->trans('User'));
$tab_rules_type = array('EX_DAY' => $langs->trans('Day'), 'EX_MON' => $langs->trans('Month'), 'EX_YEA' => $langs->trans('Year'), 'EX_EXP' => $langs->trans('OnExpense'));

/*
 * View
 */

llxHeader('',$langs->trans("ExpenseReportsSetup"));

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExpenseReportsRulesSetup"),$linkback,'title_setup');

$head=expensereport_admin_prepare_head();
dol_fiche_head($head, 'expenserules', $langs->trans("ExpenseReportsRules"), -1, 'trip');

echo $langs->trans('ExpenseReportRulesDesc');

if ($action != 'edit')
{
	echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
	echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
	echo '<input type="hidden" name="action" value="save" />';

	echo '<table class="noborder" width="100%">';

	echo '<tr class="liste_titre">';
	echo '<th>'.$langs->trans('ExpenseReportApplyTo').'</th>';
	echo '<th>'.$langs->trans('ExpenseReportDomain').'</th>';
	echo '<th>'.$langs->trans('ExpenseReportLimitOn').'</th>';
	echo '<th>'.$langs->trans('ExpenseReportDateStart').'</th>';
	echo '<th>'.$langs->trans('ExpenseReportDateEnd').'</th>';
	echo '<th>'.$langs->trans('ExpenseReportLimitAmount').'</th>';
	echo '<th>'.$langs->trans('ExpenseReportRestrictive').'</th>';
	echo '<th>&nbsp;</th>';
	echo '</tr>';

	echo '<tr class="oddeven">';
	echo '<td>';
	echo '<div class="float">'.$form->selectarray('apply_to', $tab_apply, '', 0).'</div>';
	echo '<div id="user" class="float">'.$form->select_dolusers('', 'fk_user').'</div>';
	echo '<div id="group" class="float">'.$form->select_dolgroups('', 'fk_usergroup').'</div>';
	echo '</td>';

	echo '<td>'.$form->selectExpense('', 'fk_c_type_fees', 0, 1, 1).'</td>';
	echo '<td>'.$form->selectarray('code_expense_rules_type', $tab_rules_type, '', 0).'</td>';
	echo '<td>'.$form->selectDate(strtotime(date('Y-m-01', dol_now())), 'start', '', '', 0, '', 1, 0).'</td>';
	echo '<td>'.$form->selectDate(strtotime(date('Y-m-t', dol_now())), 'end', '', '', 0, '', 1, 0).'</td>';
	echo '<td><input type="text" value="" name="amount" class="amount" />'.$conf->currency.'</td>';
	echo '<td>'.$form->selectyesno('restrictive', 0, 1).'</td>';
	echo '<td align="right"><input type="submit" class="button" value="'.$langs->trans('Add').'" /></td>';
	echo '</tr>';

	echo '</table>';
	echo '</form>';
}


echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';

if ($action == 'edit')
{
	echo '<input type="hidden" name="id" value="'.$object->id.'" />';
	echo '<input type="hidden" name="action" value="save" />';
}

echo '<table class="noborder" width="100%">';

echo '<tr class="liste_titre">';
echo '<th>'.$langs->trans('ExpenseReportApplyTo').'</th>';
echo '<th>'.$langs->trans('ExpenseReportDomain').'</th>';
echo '<th>'.$langs->trans('ExpenseReportLimitOn').'</th>';
echo '<th>'.$langs->trans('ExpenseReportDateStart').'</th>';
echo '<th>'.$langs->trans('ExpenseReportDateEnd').'</th>';
echo '<th>'.$langs->trans('ExpenseReportLimitAmount').'</th>';
echo '<th>'.$langs->trans('ExpenseReportRestrictive').'</th>';
echo '<th>&nbsp;</th>';
echo '</tr>';

foreach ($rules as $rule)
{
	echo '<tr class="oddeven">';

	echo '<td>';
	if ($action == 'edit' && $object->id == $rule->id)
	{
		$selected = ($object->is_for_all > 0) ? 'A' : ($object->fk_usergroup > 0 ? 'G' : 'U');
		echo '<div class="float">'.$form->selectarray('apply_to', $tab_apply, $selected, 0).'</div>';
		echo '<div id="user" class="float">'.$form->select_dolusers($object->fk_user, 'fk_user').'</div>';
		echo '<div id="group" class="float">'.$form->select_dolgroups($object->fk_usergroup, 'fk_usergroup').'</div>';
	}
	else
	{
		if ($rule->is_for_all > 0) echo $tab_apply['A'];
		elseif ($rule->fk_usergroup > 0) echo $tab_apply['G'].' ('.$rule->getGroupLabel().')';
		elseif ($rule->fk_user > 0) echo $tab_apply['U'].' ('.$rule->getUserName().')';
	}
	echo '</td>';


	echo '<td>';
	if ($action == 'edit' && $object->id == $rule->id)
	{
		echo $form->selectExpense($object->fk_c_type_fees, 'fk_c_type_fees', 0, 1, 1);
	}
	else
	{
		if ($rule->fk_c_type_fees == -1) echo $langs->trans('AllExpenseReport');
		else
		{
			$key = getDictvalue(MAIN_DB_PREFIX.'c_type_fees', 'code', $rule->fk_c_type_fees, false, 'id');
			if ($key != $langs->trans($key)) echo $langs->trans($key);
			else echo $langs->trans(getDictvalue(MAIN_DB_PREFIX.'c_type_fees', 'label', $rule->fk_c_type_fees, false, 'id')); // TODO check to return trans of 'code'
		}
	}
	echo '</td>';


	echo '<td>';
	if ($action == 'edit' && $object->id == $rule->id)
	{
		echo $form->selectarray('code_expense_rules_type', $tab_rules_type, $object->code_expense_rules_type, 0);
	}
	else
	{
		echo $tab_rules_type[$rule->code_expense_rules_type];
	}
	echo '</td>';


	echo '<td>';
	if ($action == 'edit' && $object->id == $rule->id)
	{
		print $form->selectDate(strtotime(date('Y-m-d', $object->dates)), 'start', '', '', 0, '', 1, 0);
	}
	else
	{
		echo dol_print_date($rule->dates, 'day');
	}
	echo '</td>';


	echo '<td>';
	if ($action == 'edit' && $object->id == $rule->id)
	{
		print $form->selectDate(strtotime(date('Y-m-d', $object->datee)), 'end', '', '', 0, '', 1, 0);
	}
	else
	{
		echo dol_print_date($rule->datee, 'day');
	}
	echo '</td>';


	echo '<td>';
	if ($action == 'edit' && $object->id == $rule->id)
	{
		echo '<input type="text" value="'.price2num($object->amount).'" name="amount" class="amount" />'.$conf->currency;
	}
	else
	{
		echo price($rule->amount, 0, $langs, 1, -1, -1, $conf->currency);
	}
	echo '</td>';


	echo '<td>';
	if ($action == 'edit' && $object->id == $rule->id)
	{
		echo $form->selectyesno('restrictive', $object->restrictive, 1);
	}
	else
	{
		echo yn($rule->restrictive, 1, 1);
	}
	echo '</td>';


	echo '<td>';
	if ($object->id != $rule->id)
	{
		echo '<a href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$rule->id.'">'.img_edit().'</a>&nbsp;';
		echo '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$rule->id.'">'.img_delete().'</a>';
	}
	else
	{
		echo '<input type="submit" class="button" value="'.$langs->trans('Update').'" />&nbsp;';
		echo '<a href="'.$_SERVER['PHP_SELF'].'" class="button">'.$langs->trans('Cancel').'</a>';
	}
	echo '</td>';

	echo '</tr>';
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

dol_fiche_end();

// End of page
llxFooter();
$db->close();
