<?php
/* Copyright (C) 2014-2025	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2015-2024  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2017		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2020		Maxime DEMAREST				<maxime@indelog.fr>
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
 *   \file       htdocs/loan/card.php
 *   \ingroup    loan
 *   \brief      Loan card
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';

if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}

if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("banks", "bills", "compta", "loan"));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$cancel = GETPOST('cancel', 'alpha');

$projectid = GETPOSTINT('projectid');

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$hookmanager->initHooks(array('loancard', 'globalcard'));
$result = restrictedArea($user, 'loan', $id, '', '');

$object = new Loan($db);

$permissiontoadd = $user->hasRight('loan', 'write');

$error = 0;
$staytopay = 0;


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
	if ($action == 'confirm_paid' && $confirm == 'yes' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setPaid($user);
		if ($result > 0) {
			setEventMessages($langs->trans('LoanPaid'), null, 'mesgs');
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Delete loan
	if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->delete($user);
		if ($result > 0) {
			setEventMessages($langs->trans('LoanDeleted'), null, 'mesgs');
			header("Location: list.php");
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Add loan
	if ($action == 'add' && $permissiontoadd) {
		if (!$cancel) {
			$datestart = dol_mktime(12, 0, 0, GETPOSTINT('startmonth'), GETPOSTINT('startday'), GETPOSTINT('startyear'));
			$dateend = dol_mktime(12, 0, 0, GETPOSTINT('endmonth'), GETPOSTINT('endday'), GETPOSTINT('endyear'));

			$capital = GETPOSTFLOAT('capital');
			$rate = GETPOSTFLOAT('rate');

			if (!$capital) {
				$error++;
				$action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("LoanCapital")), null, 'errors');
			}
			if (!$datestart) {
				$error++;
				$action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DateStart")), null, 'errors');
			}
			if (!$dateend) {
				$error++;
				$action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DateEnd")), null, 'errors');
			}
			if ($rate == '') {
				$error++;
				$action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Rate")), null, 'errors');
			}

			if (!$error) {
				$object->label = GETPOST('label');
				$object->fk_bank = GETPOSTINT('accountid');
				$object->capital = $capital;
				$object->datestart = $datestart;
				$object->dateend = $dateend;
				$object->nbterm = (float) price2num(GETPOST('nbterm'));
				$object->rate = $rate;
				$object->note_private = GETPOST('note_private', 'restricthtml');
				$object->note_public = GETPOST('note_public', 'restricthtml');
				$object->fk_project = GETPOSTINT('projectid');
				$object->insurance_amount = GETPOSTFLOAT('insurance_amount');

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
	} elseif ($action == 'update' && $permissiontoadd) {
		// Update record
		if (!$cancel) {
			$result = $object->fetch($id);

			$datestart = dol_mktime(12, 0, 0, GETPOSTINT('startmonth'), GETPOSTINT('startday'), GETPOSTINT('startyear'));
			$dateend = dol_mktime(12, 0, 0, GETPOSTINT('endmonth'), GETPOSTINT('endday'), GETPOSTINT('endyear'));

			$capital = GETPOSTFLOAT('capital');

			if (!$capital) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("LoanCapital")), null, 'errors');
				$action = 'edit';
			} else {
				$object->datestart = $datestart;
				$object->dateend = $dateend;
				$object->capital = $capital;

				$object->nbterm = GETPOSTINT("nbterm");
				$object->rate = GETPOSTFLOAT("rate");
				$object->insurance_amount = GETPOSTFLOAT('insurance_amount');

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
	if ($action == 'classin' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setProject($projectid);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setlabel' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setValueFrom('label', GETPOST('label'), '', null, 'text', '', $user, 'LOAN_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Actions to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);
$morehtmlstatus = '';
$outputlangs = $langs;
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}
$title = $langs->trans("Loan").' - '.$langs->trans("Card");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-loan page-card');


// Create mode
if ($action == 'create') {
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print load_fiche_titre($langs->trans("NewLoan"), '', 'money-bill-alt');

	$datec = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));

	print '<form name="loan" method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Label
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Label").'</td><td><input name="label" class="minwidth300" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label')).'" autofocus="autofocus"></td></tr>';

	// Bank account
	if (isModEnabled("bank")) {
		print '<tr><td class="fieldrequired">'.$langs->trans("BankAccount").'</td><td>';
		$form->select_comptes(GETPOST("accountid"), "accountid", 0, "courant=1", 1); // Show list of bank account with courant
		print '</td></tr>';
	} else {
		print '<tr><td>'.$langs->trans("BankAccount").'</td><td>';
		print $langs->trans("NoBankAccountDefined");
		print '</td></tr>';
	}

	// Capital
	print '<tr><td class="fieldrequired">'.$langs->trans("LoanCapital").'</td><td><input name="capital" size="10" value="'.dol_escape_htmltag(GETPOST("capital")).'"></td></tr>';

	// Date Start
	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate(!empty($datestart) ? $datestart : -1, 'start', 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// Date End
	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
	print $form->selectDate(!empty($dateend) ? $dateend : -1, 'end', 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// Number of terms
	print '<tr><td class="fieldrequired">'.$langs->trans("Nbterms").'</td><td><input name="nbterm" size="5" value="'.dol_escape_htmltag(GETPOST('nbterm')).'"></td></tr>';

	// Rate
	print '<tr><td class="fieldrequired">'.$langs->trans("Rate").'</td><td><input name="rate" size="5" value="'.dol_escape_htmltag(GETPOST("rate")).'"> %</td></tr>';

	// Insurance amount
	print '<tr><td>'.$langs->trans("Insurance").'</td><td><input name="insurance_amount" size="10" value="'.dol_escape_htmltag(GETPOST("insurance_amount")).'" placeholder="'.$langs->trans('Amount').'"></td></tr>';

	// Project
	if (isModEnabled('project')) {
		$formproject = new FormProjets($db);

		// Linked Project
		$langs->loadLangs(array("projects"));

		print '<tr><td>'.$langs->trans("Project").'</td><td>';

		$numproject = $formproject->select_projects(-1, $projectid, 'projectid', 16, 0, 1, 1);

		print '</td></tr>';
	}

	// Note Private
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
	print '<td>';

	$doleditor = new DolEditor('note_private', GETPOST('note_private', 'alpha'), '', 160, 'dolibarr_notes', 'In', false, true, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_6, '90%');
	print $doleditor->Create(1);

	print '</td></tr>';

	// Note Public
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_public', GETPOST('note_public', 'alpha'), '', 160, 'dolibarr_notes', 'In', false, true, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_6, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Accountancy
	if (isModEnabled('accounting')) {
		/** @var FormAccounting $formaccounting */
		// Accountancy_account_capital
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
		print '<td>';
		print $formaccounting->select_account(GETPOST('accountancy_account_capital') ? GETPOST('accountancy_account_capital') : getDolGlobalString('LOAN_ACCOUNTING_ACCOUNT_CAPITAL'), 'accountancy_account_capital', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_account_insurance
		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
		print '<td>';
		print $formaccounting->select_account(GETPOST('accountancy_account_insurance') ? GETPOST('accountancy_account_insurance') : getDolGlobalString('LOAN_ACCOUNTING_ACCOUNT_INSURANCE'), 'accountancy_account_insurance', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_account_interest
		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInterestCode").'</td>';
		print '<td>';
		print $formaccounting->select_account(GETPOST('accountancy_account_interest') ? GETPOST('accountancy_account_interest') : getDolGlobalString('LOAN_ACCOUNTING_ACCOUNT_INTEREST'), 'accountancy_account_interest', 1, '', 1, 1);
		print '</td></tr>';
	} else {
		// For external software
		// Accountancy_account_capital
		print '<tr><td class="titlefieldcreate">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
		print '<td><input name="accountancy_account_capital" size="16" value="'.$object->accountancy_account_capital.'">';
		print '</td></tr>';

		// Accountancy_account_insurance
		print '<tr><td>'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
		print '<td><input name="accountancy_account_insurance" size="16" value="'.$object->accountancy_account_insurance.'">';
		print '</td></tr>';

		// Accountancy_account_interest
		print '<tr><td>'.$langs->trans("LoanAccountancyInterestCode").'</td>';
		print '<td><input name="accountancy_account_interest" size="16" value="'.$object->accountancy_account_interest.'">';
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

		print dol_get_fiche_head($head, 'card', $langs->trans("Loan"), -1, 'money-bill-alt', 0, '', '', 0, '', 1);

		// Loan card
		$linkback = '<a href="'.DOL_URL_ROOT.'/loan/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref loan
		$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $user->hasRight('loan', 'write'), 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, $user->hasRight('loan', 'write'), 'string', '', null, null, '', 1);
		// Project
		if (isModEnabled('project')) {
			$langs->loadLangs(array("projects"));
			$morehtmlref .= '<br>'.$langs->trans('Project').' ';
			if ($user->hasRight('loan', 'write')) {
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
				}
				if ($action == 'classify') {
					$maxlength = 0;
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects(-1, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, -1, $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
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

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

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
			print '<tr><td class="titlefield">'.$langs->trans("LoanCapital").'</td><td><span class="amount">'.price($object->capital, 0, $outputlangs, 1, -1, -1, $conf->currency).'</span></td></tr>';
		}

		// Insurance
		if ($action == 'edit') {
			print '<tr><td class="titlefield">'.$langs->trans("Insurance").'</td><td>';
			print '<input name="insurance_amount" size="10" value="'.$object->insurance_amount.'"></td></tr>';
			print '</td></tr>';
		} else {
			print '<tr><td class="titlefield">'.$langs->trans("Insurance").'</td><td><span class="amount">'.price($object->insurance_amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</span></td></tr>';
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
		print '<tr><td>'.$langs->trans("DateEnd")."</td>";
		print "<td>";
		if ($action == 'edit') {
			print $form->selectDate($object->dateend, 'end', 0, 0, 0, 'update', 1, 0);
		} else {
			print dol_print_date($object->dateend, "day");
		}
		print "</td></tr>";

		// Nbterms
		print '<tr><td>'.$langs->trans("Nbterms").'</td>';
		print '<td>';
		if ($action == 'edit') {
			print '<input name="nbterm" size="4" value="'.$object->nbterm.'">';
		} else {
			print $object->nbterm;
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
				/** @var FormAccounting $formaccounting */
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
				/** @var FormAccounting $formaccounting */
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
				/** @var FormAccounting $formaccounting */
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

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
			print '<table class="noborder paymenttable">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td>'.$langs->trans("BankAccount").'</td>';
			print '<td class="right">'.$langs->trans("Insurance").'</td>';
			print '<td class="right">'.$langs->trans("Interest").'</td>';
			print '<td class="right">'.$langs->trans("LoanCapital").'</td>';
			print '<td class="right">'.$langs->trans("Total").'</td>';
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
				print '<td class="nowrap right"><span class="amount">'.price($objp->amount_insurance, 0, $outputlangs, 1, -1, -1, $conf->currency)."</span></td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($objp->amount_interest, 0, $outputlangs, 1, -1, -1, $conf->currency)."</span></td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($objp->amount_capital, 0, $outputlangs, 1, -1, -1, $conf->currency)."</span></td>\n";
				print '<td class="nowrap right"><span class="amount">'.price($objp->amount_insurance + $objp->amount_interest + $objp->amount_capital, 0, $outputlangs, 1, -1, -1, $conf->currency)."</span></td>\n";
				print "</tr>";
				$total_capital += $objp->amount_capital;
				$i++;
			}

			$totalpaid = $total_capital;

			if ($object->paid == 0 || $object->paid == 2) {
				print '<tr><td colspan="6" class="right">'.$langs->trans("AlreadyPaid").' :</td><td class="nowrap right">'.price($totalpaid, 0, $langs, 0, -1, -1, $conf->currency).'</td><td>&nbsp;</td></tr>';
				print '<tr><td colspan="6" class="right">'.$langs->trans("AmountExpected").' :</td><td class="nowrap right">'.price($object->capital, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td><td>&nbsp;</td></tr>';

				$staytopay = $object->capital - $totalpaid;

				print '<tr><td colspan="6" class="right">'.$langs->trans("RemainderToPay").' :</td>';
				print '<td class="nowrap right'.($staytopay ? ' amountremaintopay' : ' amountpaymentcomplete').'">';
				print price($staytopay, 0, $langs, 0, -1, -1, $conf->currency);
				print '</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';
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
				if (($object->paid == 0 || $object->paid == 2) && $user->hasRight('loan', 'write')) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a></div>';
				}

				// Emit payment
				if (($object->paid == 0 || $object->paid == 2) && ((price2num($object->capital) > 0 && round($staytopay) < 0) || (price2num($object->capital) > 0 && round($staytopay) > 0)) && $user->hasRight('loan', 'write')) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&action=create&token='.newToken().'">'.$langs->trans("DoPayment").'</a></div>';
				}

				// Classify 'paid'
				if (($object->paid == 0 || $object->paid == 2) && round($staytopay) <= 0 && $user->hasRight('loan', 'write')) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&action=paid&token='.newToken().'">'.$langs->trans("ClassifyPaid").'</a></div>';
				}

				// Delete
				if (($object->paid == 0 || $object->paid == 2) && $user->hasRight('loan', 'delete')) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
				}

				print "</div>";
			}
		}
	} else {
		// Loan not found
		dol_print_error(null, $object->error);
	}
}

// End of page
llxFooter();
$db->close();
