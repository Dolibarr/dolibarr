<?php
use Stripe\BankAccount;

/* Copyright (C) 2014-2018  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2017       Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020       Maxime DEMAREST      <maxime@indelog.fr>
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
 *  \file       htdocs/loan/card.php
 *  \ingroup    loan
 *  \brief      Loan card
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "loan"));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$cancel = GETPOST('cancel', 'alpha');
$label = GETPOST('label', 'alpha');
$fk_bank = GETPOST('accountid', 'int');
$note_private = GETPOST('note_private', 'alpha');
$note_public = GETPOST('note_public', 'alpha');
$fk_project = GETPOST('projectid', 'int');
$insurance_amount = price2num(GETPOST('insurance_amount', 'alpha'));
$accountancy_account_capital = price2num(GETPOST('accountancy_account_capital', 'alpha'));
$accountancy_account_insurance = price2num(GETPOST('accountancy_account_insurance', 'alpha'));
$accountancy_account_interest = price2num(GETPOST('accountancy_account_interest', 'alpha'));
$fk_periodicity = intval(GETPOST('fk_periodicity', 'int'));
if ($fk_periodicity) {
	$periodicityObj = Loan::getPeriodicity($fk_periodicity);
	$periodicity = $periodicityObj->value;
	$periodicityLabel = $langs->trans($periodicityObj->label);
}
$calc_mode = GETPOST('calc_mode', 'int');
$calc_mode = ($calc_mode === '') ? -1 : intval($calc_mode);
$nbPeriods = intval(GETPOST('nbPeriods', 'int'));
$projectid = GETPOST('projectid', 'int');
$datestart = dol_mktime(12, 0, 0, GETPOST('startmonth', 'int'), GETPOST('startday', 'int'), GETPOST('startyear', 'int'));
$datec = dol_mktime(12, 0, 0, GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));

$capital = price2num(GETPOST('capital', 'alpha'));
$rate = price2num(GETPOST('rate', 'alpha'));

// Security check
$result = restrictedArea($user, 'loan', $id, '', '');

$object = new Loan($db);
if ($id > 0) $object->fetch($id);
$error = 0;

$price = function ($n) use ($conf) {
	return price($n, 0, '', 1, -1, -1, $conf->currency);
};

$hookmanager->initHooks(array('loancard', 'globalcard'));

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	// Classify paid
	if ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->loan->write) {
		$result = $object->setPaid($user);
		if ($result > 0) {
			setEventMessages($langs->trans('LoanPaid'), array(), 'mesgs');
		} else {
			setEventMessages($object->error, array(), 'errors');
		}
	}

	// Delete loan
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->loan->write) {
		$result = $object->delete($user);
		if ($result > 0) {
			setEventMessages($langs->trans('LoanDeleted'), array(), 'mesgs');
			header("Location: list.php");
			exit;
		} else {
			setEventMessages($object->error, array(), 'errors');
		}
	}

	// Add loan
	if ($action == 'add' && $user->rights->loan->write) {
		if (!$cancel) {
			if (!$capital) {
				$error++; $action = 'create';
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('LoanCapital')), array(), 'errors');
			}
			if (!$datestart) {
				$error++; $action = 'create';
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('DateStart')), array(), 'errors');
			}
			if (!$dateend) {
				$error++; $action = 'create';
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('nbPeriods')), array(), 'errors');
			}
			if ($rate == '') {
				$error++; $action = 'create';
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Rate')), array(), 'errors');
			}
			if ($fk_periodicity === -1)
			{
				$error++; $action = 'create';
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Periodicity')), array(), 'errors');
			}
			if (! in_array($calc_mode, array_keys(Loan::CALC_MODES)))
			{
				$error++; $action = 'create';
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('CalcMode')), array(), 'errors');
			}

			if (!$error) {
				$object->label					= $label;
				$object->fk_bank				= $fk_bank;
				$object->capital				= $capital;
				$object->datestart				= $datestart;
				$object->dateend				= $object->getDateOfPeriod($nbPeriods);
				$object->fk_periodicity         = $fk_periodicity;
				$object->calc_mode              = $calc_mode;
				$object->nbPeriods				= $nbPeriods;
				$object->rate					= $rate;
				$object->note_private 			= $note_private;
				$object->note_public 			= $note_public;
				$object->fk_project 			= $fk_project;
				$object->insurance_amount       = $insurance_amount;

				$accountancy_account_capital = GETPOST('accountancy_account_capital');
				$accountancy_account_insurance = GETPOST('accountancy_account_insurance');
				$accountancy_account_interest = GETPOST('accountancy_account_interest');

				if ($accountancy_account_capital <= 0) {
					$object->account_capital = '';
				} else {
					$object->account_capital = $accountancy_account_capital;
				}
				if ($accountancy_account_insurance <= 0) {
					$object->account_insurance = '';
				} else {
					$object->account_insurance = $accountancy_account_insurance;
				}
				if ($accountancy_account_interest <= 0) {
					$object->account_interest = '';
				} else {
					$object->account_interest = $accountancy_account_interest;
				}

				$id = $object->create($user);
				if ($id <= 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'create';
				}
			}
		} else {
			header("Location: list.php");
			exit();
		}
	} elseif ($action == 'update' && $user->rights->loan->write) {
		// Update record
		if (!$cancel) {

			if (!$capital) {
				setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('LoanCapital')), array(), 'errors');
				$action = 'edit';
			} else {
				$object->deleteSchedule();
				$object->datestart	        = $datestart;
				$object->dateend	        = $object->getDateOfPeriod($nbPeriods);
				$object->fk_periodicity     = $fk_periodicity;
				$object->calc_mode          = $calc_mode;
				$object->capital	        = $capital;
				$object->nbPeriods		    = $nbPeriods;
				$object->rate		        = $rate;
                $object->insurance_amount   = $insurance_amount;

				$accountancy_account_capital = GETPOST('accountancy_account_capital');
				$accountancy_account_insurance = GETPOST('accountancy_account_insurance');
				$accountancy_account_interest = GETPOST('accountancy_account_interest');

				if ($accountancy_account_capital <= 0) {
					$object->account_capital = '';
				} else {
					$object->account_capital = $accountancy_account_capital;
				}
				if ($accountancy_account_insurance <= 0) {
					$object->account_insurance = '';
				} else {
					$object->account_insurance = $accountancy_account_insurance;
				}
				if ($accountancy_account_interest <= 0) {
					$object->account_interest = '';
				} else {
					$object->account_interest = $accountancy_account_interest;
				}
			}

			$result = $object->update($user);

			if ($result > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		}
	}

	// Link to a project
	if ($action == 'classin' && $user->rights->loan->write) {
		$result = $object->setProject($projectid);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setlabel' && $user->rights->loan->write) {
		$object->fetch($id);
		$result = $object->setValueFrom('label', $label, '', '', 'text', '', $user, 'LOAN_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}

$title = $langs->trans("Loan").' - '.$langs->trans("Card");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
$arrayofjs = array('/loan/js/loan.js');
$arrayofcss = array('/loan/css/loan.css');
llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

$jsContext = array(
	'hasEcheancier' => $object->hasEcheancier(),
	'tab_translate' =>  array(
		'ConfirmResetSchedule' => $langs->transnoentitiesnoconv('ConfirmResetSchedule'),
	)
);
echo '<script type="application/javascript">LoanModule.initLoanCard(' . json_encode($jsContext) . ')</script>';

// Create mode
if ($action == 'create') {
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print load_fiche_titre($langs->trans("NewLoan"), '', 'money-bill-alt');

	print '<form name="loan" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Label
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans('Label').'</td><td><input name="label" class="minwidth300" maxlength="255" value="'.dol_escape_htmltag($label).'" autofocus="autofocus"></td></tr>';

	// Bank account
	if (isModEnabled("banque")) {
		print '<tr><td class="fieldrequired">'.$langs->trans("Account").'</td><td>';
		$form->select_comptes($fk_bank, 'accountid', 0, 'courant=1', 1);  // Show list of bank account with courant
		print '</td></tr>';
	} else {
		print '<tr><td>'.$langs->trans("Account").'</td><td>';
		print $langs->trans("NoBankAccountDefined");
		print '</td></tr>';
	}

	// Capital
	print '<tr><td class="fieldrequired">'.$langs->trans('LoanCapital').'</td><td><input name="capital" size="10" value="' . dol_escape_htmltag($capital) . '"></td></tr>';

	// Date Start
	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate($datestart ? $datestart : -1, 'start', '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Number of Periods
	print '<tr><td class="fieldrequired">'.$langs->trans('NbPeriods').'</td><td><input name="nbPeriods" size="5" value="' . dol_escape_htmltag($nbPeriods) . '"></td></tr>';

	// Number of months per period (Periodicity)
	$entityArray = array_map('intval', explode(',', getEntity('loan')));
	$periodicityInput = Form::selectarray(
		'fk_periodicity',
		array_column(Loan::getAllPeriodicities($conf->entity), 'label', 'rowid'),
		$periodicityObj ? $fk_periodicity : '',
		1
	);

	print '<tr><td class="fieldrequired">' . $langs->trans('CalcMode') . '</td><td>' . Loan::getCalcModeSelector($calc_mode) . '</td></tr>';

	print '<tr><td class="fieldrequired">' . $langs->trans('Periodicity') . '</td><td>' . $periodicityInput . '</td></tr>';

	// Rate
	print '<tr><td class="fieldrequired">'.$langs->trans('Rate').'</td><td><input name="rate" size="5" value="' . dol_escape_htmltag($rate) . '"> %</td></tr>';

	// Insurance amount
	print '<tr><td>'.$langs->trans('Insurance').'</td><td><input name="insurance_amount" size="10" value="' . dol_escape_htmltag($insurance_amount) . '" placeholder="'.$langs->trans('Amount').'"></td></tr>';

	// Project
	if (isModEnabled('project')) {
		$formproject = new FormProjets($db);

		// Projet associe
		$langs->loadLangs(array("projects"));

		print '<tr><td>'.$langs->trans("Project").'</td><td>';

		$numproject = $formproject->select_projects(-1, $projectid, 'projectid', 16, 0, 1, 1);

		print '</td></tr>';
	}

	// Note Private
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
	print '<td>';

	$doleditor = new DolEditor('note_private', $note_private, '', 160, 'dolibarr_notes', 'In', false, true, true, ROWS_6, '90%');
	print $doleditor->Create(1);

	print '</td></tr>';

	// Note Public
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_public', $note_public, '', 160, 'dolibarr_notes', 'In', false, true, true, ROWS_6, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Accountancy
	if (isModEnabled('accounting')) {
		// Accountancy_account_capital
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
		print '<td>';
		print $formaccounting->select_account($accountancy_account_capital ?: $conf->global->LOAN_ACCOUNTING_ACCOUNT_CAPITAL, 'accountancy_account_capital', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_account_insurance
		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
		print '<td>';
		print $formaccounting->select_account($accountancy_account_insurance ?: $conf->global->LOAN_ACCOUNTING_ACCOUNT_INSURANCE, 'accountancy_account_insurance', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_account_interest
		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInterestCode").'</td>';
		print '<td>';
		print $formaccounting->select_account($accountancy_account_interest ?: $conf->global->LOAN_ACCOUNTING_ACCOUNT_INTEREST, 'accountancy_account_interest', 1, '', 1, 1);
		print '</td></tr>';
	} else // For external software
	{
		// Accountancy_account_capital
		print '<tr><td class="titlefieldcreate">'.$langs->trans('LoanAccountancyCapitalCode').'</td>';
		print '<td><input name="accountancy_account_capital" size="16" value="'.$object->account_capital.'">';
		print '</td></tr>';

		// Accountancy_account_insurance
		print '<tr><td>'.$langs->trans('LoanAccountancyInsuranceCode').'</td>';
		print '<td><input name="accountancy_account_insurance" size="16" value="'.$object->account_insurance.'">';
		print '</td></tr>';

		// Accountancy_account_interest
		print '<tr><td>'.$langs->trans('LoanAccountancyInterestCode').'</td>';
		print '<td><input name="accountancy_account_interest" size="16" value="'.$object->account_interest.'">';
		print '</td></tr>';
	}
	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Add");

	print '</form>';
}

// View
if ($id > 0) {
	$object = new Loan($db);
	$result = $object->fetch($id);

	if ($result > 0) {
		$head = loan_prepare_head($object);

		$totalpaid = $object->getSumPayment();

		// Confirm for loan
		if ($action == 'paid') {
			$text = $langs->trans('ConfirmPayLoan');
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans('PayLoan'), $text, "confirm_paid", '', '', 2);
		}

		if ($action == 'delete') {
			$text = $langs->trans('ConfirmDeleteLoan');
			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteLoan'), $text, 'confirm_delete', '', '', 2);
		}

		if ($action == 'edit') {
			print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$id.'">';
		}

		print dol_get_fiche_head($head, 'card', $langs->trans("Loan"), -1, 'bill');

		// Loan card

		$linkback = '<a href="'.DOL_URL_ROOT.'/loan/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref loan
		$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', null, null, '', 1);
		// Project
		if (isModEnabled('project')) {
			$langs->loadLangs(array("projects"));
			$morehtmlref .= '<br>'.$langs->trans('Project').' ';
			if ($user->rights->loan->write) {
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
				}
				if ($action == 'classify') {
					$maxlength = 16;
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
				}
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= ' : '.$proj->getNomUrl(1);
					if ($proj->title) {
						$morehtmlref .= ' - '.$proj->title;
					}
				} else {
					$morehtmlref .= '';
				}
			}
		}
		$morehtmlref .= '</div>';

		$object->totalpaid = $totalpaid; // To give a chance to dol_banner_tab to use already paid amount to show correct status

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Capital
		if ($action == 'edit') {
			print '<tr><td class="fieldrequired titlefield">'.$langs->trans("LoanCapital").'</td><td>';
			print '<input name="capital" size="10" value="'.$object->capital.'"></td></tr>';
			print '</td></tr>';
		} else {
			print '<tr><td class="titlefield">'.$langs->trans('LoanCapital').'</td><td>'.price($object->capital, 0, '', 1, -1, -1, $conf->currency).'</td></tr>';
		}

		// Insurance
		if ($action == 'edit')
		{
		    print '<tr><td class="titlefield">'.$langs->trans('Insurance').'</td><td>';
		    print '<input name="insurance_amount" size="10" value="' . price($object->insurance_amount) . '"></td></tr>';
		    print '</td></tr>';
		}
		else
		{
		    print '<tr><td class="titlefield">'.$langs->trans('Insurance').'</td><td>'.price($object->insurance_amount, 0, '', 1, -1, -1, $conf->currency).'</td></tr>';
		}

		// Date start
		print '<tr><td>'.$langs->trans("DateStart")."</td>";
		print "<td>";
		if ($action == 'edit') {
			print $form->selectDate($object->datestart, 'start', 0, 0, 0, 'update', 1, 0);
		} else {
			print dol_print_date($object->datestart, "day");
		}
		print "</td></tr>";

		// Date end
		print '<tr><td>'.$langs->trans('DateEnd'). '</td>';
		print '<td>';
		if ($action == 'edit') {
			print $langs->trans('DateComputedFromNbPeriods');
		} else {
			print dol_print_date($object->dateend, 'day');
		}
		print '</td></tr>';

		// Nbterms
		print '<tr><td>'.$langs->trans('NbPeriods').'</td>';
		print '<td>';
		if ($action == 'edit')
		{
			print '<input name="nbPeriods" size="4" value="' . $object->nbPeriods . '">';
		}
		else
		{
			print $object->nbPeriods;
		}
		print '</td></tr>';

		// Periodicity
		print '<tr><td>'.$langs->trans('Periodicity').'</td>';
		print '<td>';
		if ($action == 'edit')
		{
			$entityArray = array_map('intval', explode(',', getEntity('loan')));
			$periodicityInput = Form::selectarray(
				'fk_periodicity',
				array_column(Loan::getAllPeriodicities($conf->entity), 'label', 'rowid'),
				$object->fk_periodicity ? $object->fk_periodicity : '',
				1
			);
			print $periodicityInput;
		}
		else
		{
			print $object->periodicity_label;
		}
		print '</td></tr>';

		// Repay Option
		print '<tr><td>'.$langs->trans('CalcMode').'</td>';
		print '<td>';
		if ($action == 'edit')
		{
			print Loan::getCalcModeSelector($object->calc_mode);
		}
		else
		{
			print $langs->trans(Loan::CALC_MODES[$object->calc_mode]);
		}
		print '</td></tr>';

		// Rate
		print '<tr><td>'.$langs->trans("Rate").'</td>';
		print '<td>';
		if ($action == 'edit') {
			print '<input name="rate" size="4" value="'.$object->rate.'">%';
		} else {
			print price($object->rate).'%';
		}
		print '</td></tr>';

		// Accountancy account capital
		print '<tr>';
		if ($action == 'edit') {
			print '<td class="nowrap fieldrequired">';
			print $langs->trans("LoanAccountancyCapitalCode");
			print '</td><td>';

			if (isModEnabled('accounting')) {
				print $formaccounting->select_account($object->account_capital, 'accountancy_account_capital', 1, '', 1, 1);
			} else {
				print '<input name="accountancy_account_capital" size="16" value="'.$object->account_capital.'">';
			}
			print '</td>';
		} else {
			print '<td class="nowrap">';
			print $langs->trans("LoanAccountancyCapitalCode");
			print '</td><td>';

			if (isModEnabled('accounting')) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('', $object->account_capital, 1);

				print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
			} else {
				print $object->account_capital;
			}

			print '</td>';
		}
		print '</tr>';

		// Accountancy account insurance
		print '<tr>';
		if ($action == 'edit') {
			print '<td class="nowrap fieldrequired">';
			print $langs->trans("LoanAccountancyInsuranceCode");
			print '</td><td>';

			if (isModEnabled('accounting')) {
				print $formaccounting->select_account($object->account_insurance, 'accountancy_account_insurance', 1, '', 1, 1);
			} else {
				print '<input name="accountancy_account_insurance" size="16" value="'.$object->account_insurance.'">';
			}
			print '</td>';
		} else {
			print '<td class="nowrap">';
			print $langs->trans("LoanAccountancyInsuranceCode");
			print '</td><td>';

			if (isModEnabled('accounting')) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('', $object->account_insurance, 1);

				print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
			} else {
				print $object->account_insurance;
			}

			print '</td>';
		}
		print '</tr>';

		// Accountancy account interest
		print '<tr>';
		if ($action == 'edit') {
			print '<td class="nowrap fieldrequired">';
			print $langs->trans("LoanAccountancyInterestCode");
			print '</td><td>';

			if (isModEnabled('accounting')) {
				print $formaccounting->select_account($object->account_interest, 'accountancy_account_interest', 1, '', 1, 1);
			} else {
				print '<input name="accountancy_account_interest" size="16" value="'.$object->account_interest.'">';
			}
			print '</td>';
		} else {
			print '<td class="nowrap">';
			print $langs->trans("LoanAccountancyInterestCode");
			print '</td><td>';

			if (isModEnabled('accounting')) {
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('', $object->account_interest, 1);

				print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
			} else {
				print $object->account_interest;
			}

			print '</td>';
		}
		print '</tr>';

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';

		/*
		 * Payments
		 */
		$sql = "SELECT p.rowid, p.num_payment, p.datep as dp,";
		$sql .= " p.amount_capital, p.amount_insurance, p.amount_interest,";
		$sql .= " b.fk_account,";
		$sql .= " c.libelle as paiement_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_loan as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepayment = c.id,";
		$sql .= " ".MAIN_DB_PREFIX."loan as l";
		$sql .= " WHERE p.fk_loan = ".((int) $id);
		$sql .= " AND p.fk_loan = l.rowid";
		$sql .= " AND l.entity IN ( ".getEntity('loan').")";
		$sql .= " ORDER BY dp DESC";

		//print $sql;
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			$total_insurance = 0;
			$total_interest = 0;
			$total_capital = 0;

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
			print '<table class="noborder paymenttable">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td>'.$langs->trans("BankAccount").'</td>';
			print '<td class="right">'.$langs->trans("Insurance").'</td>';
			print '<td class="right">'.$langs->trans("Interest").'</td>';
			print '<td class="right">'.$langs->trans("LoanCapital").'</td>';
			print '</tr>';

			$conf->cache['bankaccount'] = array();

			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td><a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"), "payment").' '.$objp->rowid.'</a></td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
				print "<td>".$objp->paiement_type.' '.$objp->num_payment."</td>\n";
				print "<td>";
				if (!empty($conf->cache['bankaccount'][$objp->fk_account])) {
					$tmpbank = $conf->cache['bankaccount'][$objp->fk_account];
				} else {
					$tmpbank = new Account($db);
					$tmpbank->fetch($objp->fk_account);
					$conf->cache['bankaccount'][$objp->fk_account] = $tmpbank;
				}
				print $tmpbank->getNomUrl(1);
				print "</td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($objp->amount_insurance, 0, '', 1, -1, -1, $conf->currency)."</span></td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($objp->amount_interest, 0, '', 1, -1, -1, $conf->currency)."</span></td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($objp->amount_capital, 0, '', 1, -1, -1, $conf->currency)."</span></td>\n";
				print "</tr>";
				$total_capital += $objp->amount_capital;
				$i++;
			}

			$totalpaid = $total_capital;

			if ($object->paid == 0 || $object->paid == 2) {
				print '<tr><td colspan="6" class="right">'.$langs->trans("AlreadyPaid").' :</td><td class="nowrap right">'.price($totalpaid, 0, '', 0, -1, -1, $conf->currency).'</td></tr>';
				print '<tr><td colspan="6" class="right">'.$langs->trans("AmountExpected").' :</td><td class="nowrap right">'.price($object->capital, 0, '', 1, -1, -1, $conf->currency).'</td></tr>';

				$staytopay = $object->capital - $totalpaid;

				print '<tr><td colspan="6" class="right">'.$langs->trans("RemainderToPay").' :</td>';
				print '<td class="nowrap right'.($staytopay ? ' amountremaintopay' : ' amountpaymentcomplete').'">';
				print price($staytopay, 0, $langs, 0, -1, -1, $conf->currency);
				print '</td></tr>';
			}
			print "</table>";
			print '</div>';

			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		if ($action == 'edit') {
			print $form->buttonsSaveCancel();

			print '</form>';
		}

		/*
		 *  Buttons actions
		 */
		if ($action != 'edit') {
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) {
				print '<div class="tabsAction">';

				// Edit
				if (($object->paid == 0 || $object->paid == 2) && $user->rights->loan->write) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a></div>';
				}

				// Emit payment
				if (($object->paid == 0 || $object->paid == 2) && ((price2num($object->capital) > 0 && round($staytopay) < 0) || (price2num($object->capital) > 0 && round($staytopay) > 0)) && $user->rights->loan->write) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&action=create&token='.newToken().'">'.$langs->trans("DoPayment").'</a></div>';
				}

				// Classify 'paid'
				if (($object->paid == 0 || $object->paid == 2) && round($staytopay) <= 0 && $user->rights->loan->write) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&action=paid&token='.newToken().'">'.$langs->trans("ClassifyPaid").'</a></div>';
				}

				// Delete
				if (($object->paid == 0 || $object->paid == 2) && $user->rights->loan->delete) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
				}

				print "</div>";
			}
		}
	} else {
		// Loan not found
		dol_print_error('', $object->error);
	}
}

// End of page
llxFooter();
$db->close();
