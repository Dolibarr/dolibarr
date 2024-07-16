<?php
/* Copyright (C) 2019-2023  Open-DSI    	    <support@open-dsi.fr>
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
 * \file 	    htdocs/accountancy/closure/index.php
 * \ingroup     Accountancy
 * \brief 	    Home closure page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "accountancy"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'aZ09');
$fiscal_period_id = GETPOSTINT('fiscal_period_id');
$validatemonth = GETPOSTINT('validatemonth');
$validateyear = GETPOSTINT('validateyear');

// Security check
if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'fiscalyear', 'write')) {
	accessforbidden();
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('accountancyclosure'));

$object = new BookKeeping($db);

$now = dol_now();
$fiscal_periods = $object->getFiscalPeriods();
if (!is_array($fiscal_periods)) {
	setEventMessages($object->error, $object->errors, 'errors');
}

$active_fiscal_periods = array();
$last_fiscal_period = null;
$current_fiscal_period = null;
$next_fiscal_period = null;
$next_active_fiscal_period = null;
if (is_array($fiscal_periods)) {
	foreach ($fiscal_periods as $fiscal_period) {
		if (empty($fiscal_period['status'])) {
			$active_fiscal_periods[] = $fiscal_period;
		}
		if (isset($current_fiscal_period)) {
			if (!isset($next_fiscal_period)) {
				$next_fiscal_period = $fiscal_period;
			}
			if (!isset($next_active_fiscal_period) && empty($fiscal_period['status'])) {
				$next_active_fiscal_period = $fiscal_period;
			}
		} else {
			if ($fiscal_period_id == $fiscal_period['id'] || (empty($fiscal_period_id) && $fiscal_period['date_start'] <= $now && $now <= $fiscal_period['date_end'])) {
				$current_fiscal_period = $fiscal_period;
			} else {
				$last_fiscal_period = $fiscal_period;
			}
		}
	}
}

$accounting_groups_used_for_balance_sheet_account = array_filter(array_map('trim', explode(',', getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT'))), 'strlen');
$accounting_groups_used_for_income_statement = array_filter(array_map('trim', explode(',', getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT'))), 'strlen');


/*
 * Actions
 */

$parameters = array('fiscal_periods' => $fiscal_periods, 'last_fiscal_period' => $last_fiscal_period, 'current_fiscal_period' => $current_fiscal_period, 'next_fiscal_period' => $next_fiscal_period);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (isset($current_fiscal_period) && $user->hasRight('accounting', 'fiscalyear', 'write')) {
		if ($action == 'confirm_step_1' && $confirm == "yes") {
			$date_start = dol_mktime(0, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
			$date_end = dol_mktime(23, 59, 59, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));

			$result = $object->validateMovementForFiscalPeriod($date_start, $date_end);
			if ($result > 0) {
				setEventMessages($langs->trans("AllMovementsWereRecordedAsValidated"), null, 'mesgs');

				header("Location: " . $_SERVER['PHP_SELF'] . (isset($current_fiscal_period) ? '?fiscal_period_id=' . $current_fiscal_period['id'] : ''));
				exit;
			} else {
				setEventMessages($langs->trans("NotAllMovementsCouldBeRecordedAsValidated"), null, 'errors');
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		} elseif ($action == 'confirm_step_2' && $confirm == "yes") {
			$new_fiscal_period_id = GETPOSTINT('new_fiscal_period_id');
			$separate_auxiliary_account = GETPOST('separate_auxiliary_account', 'aZ09');
			$generate_bookkeeping_records = GETPOST('generate_bookkeeping_records', 'aZ09');

			$result = $object->closeFiscalPeriod($current_fiscal_period['id'], $new_fiscal_period_id, $separate_auxiliary_account, $generate_bookkeeping_records);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans("AccountancyClosureCloseSuccessfully"), null, 'mesgs');

				header("Location: " . $_SERVER['PHP_SELF'] . (isset($current_fiscal_period) ? '?fiscal_period_id=' . $current_fiscal_period['id'] : ''));
				exit;
			}
		} elseif ($action == 'confirm_step_3' && $confirm == "yes") {
			$inventory_journal_id = GETPOSTINT('inventory_journal_id');
			$new_fiscal_period_id = GETPOSTINT('new_fiscal_period_id');
			$date_start = dol_mktime(0, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
			$date_end = dol_mktime(23, 59, 59, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));

			$result = $object->insertAccountingReversal($current_fiscal_period['id'], $inventory_journal_id, $new_fiscal_period_id, $date_start, $date_end);
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans("AccountancyClosureInsertAccountingReversalSuccessfully"), null, 'mesgs');

				header("Location: " . $_SERVER['PHP_SELF'] . (isset($current_fiscal_period) ? '?fiscal_period_id=' . $current_fiscal_period['id'] : ''));
				exit;
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans('Closure');

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Cl.C3.B4ture_annuelle';

llxHeader('', $title, $help_url);

$formconfirm = '';

if (isset($current_fiscal_period)) {
	if ($action == 'step_1') {
		$form_question = array();

		$form_question['date_start'] = array(
			'name' => 'date_start',
			'type' => 'date',
			'label' => $langs->trans('DateStart'),
			'value' => $current_fiscal_period['date_start']
		);
		$form_question['date_end'] = array(
			'name' => 'date_end',
			'type' => 'date',
			'label' => $langs->trans('DateEnd'),
			'value' => $current_fiscal_period['date_end']
		);

		$formconfirm = $form->formconfirm(
			$_SERVER["PHP_SELF"] . '?fiscal_period_id=' . $current_fiscal_period['id'],
			$langs->trans('ValidateMovements'),
			$langs->trans('DescValidateMovements', $langs->transnoentitiesnoconv("RegistrationInAccounting")),
			'confirm_step_1',
			$form_question,
			'',
			1,
			300
		);
	} elseif ($action == 'step_2') {
		$form_question = array();

		$fiscal_period_arr = array();
		foreach ($active_fiscal_periods as $info) {
			$fiscal_period_arr[$info['id']] = $info['label'];
		}
		$form_question['new_fiscal_period_id'] = array(
			'name' => 'new_fiscal_period_id',
			'type' => 'select',
			'label' => $langs->trans('AccountancyClosureStep3NewFiscalPeriod'),
			'values' => $fiscal_period_arr,
			'default' => isset($next_active_fiscal_period) ? $next_active_fiscal_period['id'] : '',
		);
		$form_question['generate_bookkeeping_records'] = array(
			'name' => 'generate_bookkeeping_records',
			'type' => 'checkbox',
			'label' => $langs->trans('AccountancyClosureGenerateClosureBookkeepingRecords'),
			'value' => 1
		);
		$form_question['separate_auxiliary_account'] = array(
			'name' => 'separate_auxiliary_account',
			'type' => 'checkbox',
			'label' => $langs->trans('AccountancyClosureSeparateAuxiliaryAccounts'),
			'value' => 0
		);

		$formconfirm = $form->formconfirm(
			$_SERVER["PHP_SELF"] . '?fiscal_period_id=' . $current_fiscal_period['id'],
			$langs->trans('AccountancyClosureClose'),
			$langs->trans('AccountancyClosureConfirmClose'),
			'confirm_step_2',
			$form_question,
			'',
			1,
			300
		);
	} elseif ($action == 'step_3') {
		$form_question = array();

		$form_question['inventory_journal_id'] = array(
			'name' => 'inventory_journal_id',
			'type' => 'other',
			'label' => $langs->trans('InventoryJournal'),
			'value' => $formaccounting->select_journal(0, "inventory_journal_id", 8, 1, 0, 0)
		);
		$fiscal_period_arr = array();
		foreach ($active_fiscal_periods as $info) {
			$fiscal_period_arr[$info['id']] = $info['label'];
		}
		$form_question['new_fiscal_period_id'] = array(
			'name' => 'new_fiscal_period_id',
			'type' => 'select',
			'label' => $langs->trans('AccountancyClosureStep3NewFiscalPeriod'),
			'values' => $fiscal_period_arr,
			'default' => isset($next_active_fiscal_period) ? $next_active_fiscal_period['id'] : '',
		);
		$form_question['date_start'] = array(
			'name' => 'date_start',
			'type' => 'date',
			'label' => $langs->trans('DateStart'),
			'value' => dol_time_plus_duree($current_fiscal_period['date_end'], -1, 'm')
		);
		$form_question['date_end'] = array(
			'name' => 'date_end',
			'type' => 'date',
			'label' => $langs->trans('DateEnd'),
			'value' => $current_fiscal_period['date_end']
		);

		$formconfirm = $form->formconfirm(
			$_SERVER["PHP_SELF"] . '?fiscal_period_id=' . $current_fiscal_period['id'],
			$langs->trans('AccountancyClosureAccountingReversal'),
			$langs->trans('AccountancyClosureConfirmAccountingReversal'),
			'confirm_step_3',
			$form_question,
			'',
			1,
			300
		);
	}
}

// Call Hook formConfirm
$parameters = array('formConfirm' => $formconfirm, 'fiscal_periods' => $fiscal_periods, 'last_fiscal_period' => $last_fiscal_period, 'current_fiscal_period' => $current_fiscal_period, 'next_fiscal_period' => $next_fiscal_period);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$formconfirm .= $hookmanager->resPrint;
} elseif ($reshook > 0) {
	$formconfirm = $hookmanager->resPrint;
}

// Print form confirm
print $formconfirm;

$fiscal_period_nav_text = $langs->trans("FiscalPeriod");

$fiscal_period_nav_text .= '&nbsp;<a href="' . (isset($last_fiscal_period) ? $_SERVER["PHP_SELF"] . '?fiscal_period_id=' . $last_fiscal_period['id'] : '#" class="disabled') . '">' . img_previous() . '</a>';
$fiscal_period_nav_text .= '&nbsp;<a href="' . (isset($next_fiscal_period) ? $_SERVER["PHP_SELF"] . '?fiscal_period_id=' . $next_fiscal_period['id'] : '#" class="disabled') . '">' . img_next() . '</a>';
if (!empty($current_fiscal_period)) {
	$fiscal_period_nav_text .= $current_fiscal_period['label'].' &nbsp;(' . (isset($current_fiscal_period) ? dol_print_date($current_fiscal_period['date_start'], 'day') . '&nbsp;-&nbsp;' . dol_print_date($current_fiscal_period['date_end'], 'day') . ')' : '');
}

print load_fiche_titre($langs->trans("Closure") . " - " . $fiscal_period_nav_text, '', 'title_accountancy');

if (empty($current_fiscal_period)) {
	print $langs->trans('ErrorNoFiscalPeriodActiveFound', $langs->trans("Accounting"), $langs->trans("Setup"), $langs->trans("FiscalPeriod"));
}

if (isset($current_fiscal_period)) {
	// Step 1
	$head = array();
	$head[0][0] = DOL_URL_ROOT . '/accountancy/closure/index.php?fiscal_period_id=' . $current_fiscal_period['id'];
	$head[0][1] = $langs->trans("AccountancyClosureStep1");
	$head[0][2] = 'step1';
	print dol_get_fiche_head($head, 'step1', '', -1, '');

	print '<span class="opacitymedium">' . $langs->trans("AccountancyClosureStep1Desc") . '</span><br>';

	$count_by_month = $object->getCountByMonthForFiscalPeriod($current_fiscal_period['date_start'], $current_fiscal_period['date_end']);
	if (!is_array($count_by_month)) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	if (empty($count_by_month['total'])) {
		$buttonvalidate = '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("ValidateMovements") . '</a>';
	} else {
		$buttonvalidate = '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=step_1&fiscal_period_id=' . $current_fiscal_period['id'] . '">' . $langs->trans("ValidateMovements") . '</a>';
	}
	print_barre_liste($langs->trans("OverviewOfMovementsNotValidated"), '', '', '', '', '', '', -1, '', '', 0, $buttonvalidate, '', 0, 1, 0);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	$nb_years = is_array($count_by_month['list']) ? count($count_by_month['list']) : 0;
	if ($nb_years > 1) {
		print '<td class="right">' . $langs->trans("Year") . '</td>';
	}
	for ($i = 1; $i <= 12; $i++) {
		print '<td class="right">' . $langs->trans('MonthShort' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)) . '</td>';
	}
	print '<td class="right"><b>' . $langs->trans("Total") . '</b></td>';
	print '</tr>';

	if (is_array($count_by_month['list'])) {
		foreach ($count_by_month['list'] as $info) {
			print '<tr class="oddeven">';
			if ($nb_years > 1) {
				print '<td class="right">' . $info['year'] . '</td>';
			}
			for ($i = 1; $i <= 12; $i++) {
				print '<td class="right">' . ((int) $info['count'][$i]) . '</td>';
			}
			print '<td class="right"><b>' . $info['total'] . '</b></td></tr>';
		}
	}

	print "</table>\n";
	print '</div>';

	print '<br>';

	// Step 2
	$head = array();
	$head[0][0] = DOL_URL_ROOT . '/accountancy/closure/index.php?fiscal_period_id=' . $current_fiscal_period['id'];
	$head[0][1] = $langs->trans("AccountancyClosureStep2");
	$head[0][2] = 'step2';
	print dol_get_fiche_head($head, 'step2', '', -1, '');

	// print '<span class="opacitymedium">' . $langs->trans("AccountancyClosureStep2Desc") . '</span><br>';

	if (empty($count_by_month['total']) && empty($current_fiscal_period['status'])) {
		$button = '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=step_2&fiscal_period_id=' . $current_fiscal_period['id'] . '">' . $langs->trans("AccountancyClosureClose") . '</a>';
	} else {
		$button = '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("AccountancyClosureClose") . '</a>';
	}
	print_barre_liste('', '', '', '', '', '', '', -1, '', '', 0, $button, '', 0, 1, 0);

	print '<br>';

	// Step 3
	$head = array();
	$head[0][0] = DOL_URL_ROOT . '/accountancy/closure/index.php?fiscal_period_id=' . $current_fiscal_period['id'];
	$head[0][1] = $langs->trans("AccountancyClosureStep3");
	$head[0][2] = 'step3';
	print dol_get_fiche_head($head, 'step3', '', -1, '');

	// print '<span class="opacitymedium">' . $langs->trans("AccountancyClosureStep3Desc") . '</span><br>';

	if (empty($current_fiscal_period['status'])) {
		$button = '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("AccountancyClosureAccountingReversal") . '</a>';
	} else {
		$button = '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=step_3&fiscal_period_id=' . $current_fiscal_period['id'] . '">' . $langs->trans("AccountancyClosureAccountingReversal") . '</a>';
	}
	print_barre_liste('', '', '', '', '', '', '', -1, '', '', 0, $button, '', 0, 1, 0);
}

// End of page
llxFooter();
$db->close();
