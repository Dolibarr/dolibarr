<?php
/* Copyright (C) 2014-2018  Alexandre Spangaro   <aspangaro@zendsi.com>
 * Copyright (C) 2015       Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2017       Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/loan/card.php
 *  \ingroup    loan
 *  \brief      Loan card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta","bills","loan"));

$id=GETPOST('id','int');
$action=GETPOST('action','aZ09');
$confirm=GETPOST('confirm');
$cancel=GETPOST('cancel','alpha');

$projectid = GETPOST('projectid','int');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', $id, '','');

$object = new Loan($db);

$hookmanager->initHooks(array('loancard','globalcard'));


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	// Classify paid
	if ($action == 'confirm_paid' && $confirm == 'yes')
	{
		$object->fetch($id);
		$result = $object->set_paid($user);
		if ($result > 0)
		{
			setEventMessages($langs->trans('LoanPaid'), null, 'mesgs');
		}
		else
		{
			setEventMessages($loan->error, null, 'errors');
		}
	}

	// Delete loan
	if ($action == 'confirm_delete' && $confirm == 'yes')
	{
		$object->fetch($id);
		$result=$object->delete($user);
		if ($result > 0)
		{
			setEventMessages($langs->trans('LoanDeleted'), null, 'mesgs');
			header("Location: index.php");
			exit;
		}
		else
		{
			setEventMessages($loan->error, null, 'errors');
		}
	}

	// Add loan
	if ($action == 'add' && $user->rights->loan->write)
	{
		if (! $cancel)
		{
			$datestart	= dol_mktime(12, 0, 0, GETPOST('startmonth','int'), GETPOST('startday','int'), GETPOST('startyear','int'));
			$dateend	= dol_mktime(12, 0, 0, GETPOST('endmonth','int'), GETPOST('endday','int'), GETPOST('endyear','int'));
			$capital 	= price2num(GETPOST('capital'));
			$rate	   = GETPOST('rate');

			if (! $capital)
			{
				$error++; $action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("LoanCapital")), null, 'errors');
			}
			if (! $datestart)
			{
				$error++; $action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DateStart")), null, 'errors');
			}
			if (! $dateend)
			{
				$error++; $action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("DateEnd")), null, 'errors');
			}
			if ($rate == '')
			{
				$error++; $action = 'create';
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Rate")), null, 'errors');
			}

			if (! $error)
			{
				$object->label					= GETPOST('label');
				$object->fk_bank				= GETPOST('accountid');
				$object->capital				= $capital;
				$object->datestart				= $datestart;
				$object->dateend				= $dateend;
				$object->nbterm					= GETPOST('nbterm');
				$object->rate					= $rate;
				$object->note_private 			= GETPOST('note_private','none');
				$object->note_public 			= GETPOST('note_public','none');
				$object->fk_project 			= GETPOST('projectid','int');

				$accountancy_account_capital	= GETPOST('accountancy_account_capital');
				$accountancy_account_insurance	= GETPOST('accountancy_account_insurance');
				$accountancy_account_interest	= GETPOST('accountancy_account_interest');

				if ($accountancy_account_capital <= 0) { $object->account_capital = ''; } else { $object->account_capital = $accountancy_account_capital; }
				if ($accountancy_account_insurance <= 0) { $object->account_insurance = ''; } else { $object->account_insurance = $accountancy_account_insurance; }
				if ($accountancy_account_interest <= 0) { $object->account_interest = ''; } else { $object->account_interest = $accountancy_account_interest; }

				$id=$object->create($user);
				if ($id <= 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
					$action = 'create';
				}
			}
		}
		else
		{
			header("Location: index.php");
			exit();
		}
	}

	// Update record
	else if ($action == 'update' && $user->rights->loan->write)
	{
		if (! $cancel)
		{
			$result = $object->fetch($id);

			$datestart	= dol_mktime(12, 0, 0, GETPOST('startmonth','int'), GETPOST('startday','int'), GETPOST('startyear','int'));
			$dateend	= dol_mktime(12, 0, 0, GETPOST('endmonth','int'), GETPOST('endday','int'), GETPOST('endyear','int'));
			$capital	= price2num(GETPOST('capital'));

			if (! $capital)
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("LoanCapital")), null, 'errors');
				$action = 'edit';
			}
			else
			{
				$object->datestart	= $datestart;
				$object->dateend	= $dateend;
				$object->capital	= $capital;
				$object->nbterm		= GETPOST("nbterm",'int');
				$object->rate		= price2num(GETPOST("rate",'alpha'));

				$accountancy_account_capital	= GETPOST('accountancy_account_capital');
				$accountancy_account_insurance	= GETPOST('accountancy_account_insurance');
				$accountancy_account_interest	= GETPOST('accountancy_account_interest');

				if ($accountancy_account_capital <= 0) { $object->account_capital = ''; } else { $object->account_capital = $accountancy_account_capital; }
				if ($accountancy_account_insurance <= 0) { $object->account_insurance = ''; } else { $object->account_insurance = $accountancy_account_insurance; }
				if ($accountancy_account_interest <= 0) { $object->account_interest = ''; } else { $object->account_interest = $accountancy_account_interest; }
			}

			$result = $object->update($user);

			if ($result > 0)
			{
				header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
				exit;
			}
			else
			{
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		else
		{
			header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
			exit;
		}
	}

	// Link to a project
	if ($action == 'classin' && $user->rights->loan->write)
	{
		$object->fetch($id);
		$result = $object->setProject($projectid);
		if ($result < 0)
			setEventMessages($object->error, $object->errors, 'errors');
	}

	if ($action == 'setlabel' && $user->rights->loan->write)
	{
		$object->fetch($id);
		$result = $object->setValueFrom('label', GETPOST('label'), '', '', 'text', '', $user, 'LOAN_MODIFY');
		if ($result < 0)
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);
if (! empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

$title = $langs->trans("Loan") . ' - ' . $langs->trans("Card");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$title,$help_url);


// Create mode
if ($action == 'create')
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print load_fiche_titre($langs->trans("NewLoan"), '', 'title_accountancy.png');

	$datec = dol_mktime(12, 0, 0, GETPOST('remonth','int'), GETPOST('reday','int'), GETPOST('reyear','int'));

	print '<form name="loan" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head();

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Label").'</td><td><input name="label" class="minwidth300" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label')).'" autofocus="autofocus"></td></tr>';

	// Bank account
	if (! empty($conf->banque->enabled))
	{
		print '<tr><td class="fieldrequired">'.$langs->trans("Account").'</td><td>';
		$form->select_comptes(GETPOST("accountid"),"accountid",0,"courant=1",1);  // Show list of bank account with courant
		print '</td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans("Account").'</td><td>';
		print $langs->trans("NoBankAccountDefined");
		print '</td></tr>';
	}

	// Capital
	print '<tr><td class="fieldrequired">'.$langs->trans("LoanCapital").'</td><td><input name="capital" size="10" value="' . dol_escape_htmltag(GETPOST("capital")) . '"></td></tr>';

	// Date Start
	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("DateStart").'</td><td>';
	print $form->selectDate($datestart?$datestart:-1,'start','','','','add',1,1);
	print '</td></tr>';

	// Date End
	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td><td>';
	print $form->selectDate($dateend?$dateend:-1,'end','','','','add',1,1);
	print '</td></tr>';

	// Number of terms
	print '<tr><td class="fieldrequired">'.$langs->trans("Nbterms").'</td><td><input name="nbterm" size="5" value="' . dol_escape_htmltag(GETPOST('nbterm')) . '"></td></tr>';

	// Rate
	print '<tr><td class="fieldrequired">'.$langs->trans("Rate").'</td><td><input name="rate" size="5" value="' . dol_escape_htmltag(GETPOST("rate")) . '"> %</td></tr>';

	// Project
	if (! empty($conf->projet->enabled))
	{
		$formproject=new FormProjets($db);

		// Projet associe
		$langs->loadLangs(array("projects"));

		print '<tr><td>'.$langs->trans("Project").'</td><td>';

		$numproject=$formproject->select_projects(-1, $projectid, 'projectid', 16, 0, 1, 1);

		print '</td></tr>';
	}

	// Note Private
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
	print '<td>';

	$doleditor = new DolEditor('note_private', GETPOST('note_private', 'alpha'), '', 160, 'dolibarr_notes', 'In', false, true, true, ROWS_6, '90%');
	print $doleditor->Create(1);

	print '</td></tr>';

	// Note Public
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_public', GETPOST('note_public', 'alpha'), '', 160, 'dolibarr_notes', 'In', false, true, true, ROWS_6, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Accountancy
	if (! empty($conf->accounting->enabled))
	{
		// Accountancy_account_capital
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("LoanAccountancyCapitalCode").'</td>';
		print '<td>';
		print $formaccounting->select_account(GETPOST('accountancy_account_capital')?GETPOST('accountancy_account_capital'):$conf->global->LOAN_ACCOUNTING_ACCOUNT_CAPITAL, 'accountancy_account_capital', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_account_insurance
		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInsuranceCode").'</td>';
		print '<td>';
		print $formaccounting->select_account(GETPOST('accountancy_account_insurance')?GETPOST('accountancy_account_insurance'):$conf->global->LOAN_ACCOUNTING_ACCOUNT_INSURANCE, 'accountancy_account_insurance', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_account_interest
		print '<tr><td class="fieldrequired">'.$langs->trans("LoanAccountancyInterestCode").'</td>';
		print '<td>';
		print $formaccounting->select_account(GETPOST('accountancy_account_interest')?GETPOST('accountancy_account_interest'):$conf->global->LOAN_ACCOUNTING_ACCOUNT_INTEREST, 'accountancy_account_interest', 1, '', 1, 1);
		print '</td></tr>';
	}
	else // For external software
	{
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

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}

// View
if ($id > 0)
{
	$object = new Loan($db);
	$result = $object->fetch($id);

	if ($result > 0)
	{
		$head=loan_prepare_head($object);

		$totalpaid = $object->getSumPayment();

		// Confirm for loan
		if ($action == 'paid')
		{
			$text=$langs->trans('ConfirmPayLoan');
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans('PayLoan'),$text,"confirm_paid",'','',2);
		}

		if ($action == 'delete')
		{
			$text=$langs->trans('ConfirmDeleteLoan');
			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('DeleteLoan'),$text,'confirm_delete','','',2);
		}

		if ($action == 'edit')
		{
			print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$id.'">';
		}

		dol_fiche_head($head, 'card', $langs->trans("Loan"), -1, 'bill');

		print '<script type="text/javascript">' . "\n";
		print '  	function popEcheancier() {' . "\n";
		print '  		$div = $(\'<div id="popCalendar"><iframe width="100%" height="98%" frameborder="0" src="createschedule.php?loanid=' . $object->id . '"></iframe></div>\');' . "\n";
		print '  		$div.dialog({' . "\n";
		print '  			modal:true' . "\n";
		print '  			,width:"90%"' . "\n";
		print '  			,height:$(window).height() - 160' . "\n";
		print '  		});' . "\n";
		print '  	}' . "\n";
		print '</script>';


		// Loan card

		$linkback = '<a href="' . DOL_URL_ROOT . '/loan/list.php">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref='<div class="refidno">';
		// Ref loan
		$morehtmlref.=$form->editfieldkey("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("Label", 'label', $object->label, $object, $user->rights->loan->write, 'string', '', null, null, '', 1);
		// Project
		if (! empty($conf->projet->enabled))
		{
			$langs->loadLangs(array("projects"));
			$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
			if ($user->rights->loan->write)
			{
				if ($action != 'classify')
					$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref.='<input type="hidden" name="action" value="classin">';
					$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					$morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref.='</form>';
				} else {
					$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
			} else {
				if (! empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
					$morehtmlref.=$proj->ref;
					$morehtmlref.='</a>';
				} else {
					$morehtmlref.='';
				}
			}
		}
		$morehtmlref.='</div>';

		$object->totalpaid = $totalpaid;  // To give a chance to dol_banner_tab to use already paid amount to show correct status

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Capital
		if ($action == 'edit')
		{
			print '<tr><td class="fieldrequired titlefield">'.$langs->trans("LoanCapital").'</td><td>';
			print '<input name="capital" size="10" value="' . $object->capital . '"></td></tr>';
			print '</td></tr>';
		}
		else
		{
			print '<tr><td class="titlefield">'.$langs->trans("LoanCapital").'</td><td>'.price($object->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
		}

		// Date start
		print '<tr><td>'.$langs->trans("DateStart")."</td>";
		print "<td>";
		if ($action == 'edit')
		{
			print $form->selectDate($object->datestart, 'start', 0, 0, 0, 'update', 1, 0);
		}
		else
		{
			print dol_print_date($object->datestart,"day");
		}
		print "</td></tr>";

		// Date end
		print '<tr><td>'.$langs->trans("DateEnd")."</td>";
		print "<td>";
		if ($action == 'edit')
		{
			print $form->selectDate($object->dateend, 'end', 0, 0, 0, 'update', 1, 0);
		}
		else
		{
			print dol_print_date($object->dateend,"day");
		}
		print "</td></tr>";

		// Nbterms
		print '<tr><td>'.$langs->trans("Nbterms").'</td>';
		print '<td>';
		if ($action == 'edit')
		{
			print '<input name="nbterm" size="4" value="' . $object->nbterm . '">';
		}
		else
		{
			print $object->nbterm;
		}
		print '</td></tr>';

		// Rate
		print '<tr><td>'.$langs->trans("Rate").'</td>';
		print '<td>';
		if ($action == 'edit')
		{
			print '<input name="rate" size="4" value="' . $object->rate . '">%';
		}
		else
		{
			print price($object->rate) . '%';
		}
		print '</td></tr>';

		// Accountancy account capital
		print '<tr>';
		if ($action == 'edit')
		{
			print '<td class="nowrap fieldrequired">';
			print $langs->trans("LoanAccountancyCapitalCode");
			print '</td><td>';

			if (! empty($conf->accounting->enabled))
			{
				print $formaccounting->select_account($object->account_capital, 'accountancy_account_capital', 1, '', 1, 1);
			}
			else
			{
				print '<input name="accountancy_account_capital" size="16" value="'.$object->account_capital.'">';
			}
			print '</td>';
		}
		else
		{
			print '<td class="nowrap">';
			print $langs->trans("LoanAccountancyCapitalCode");
			print '</td><td>';

			if (! empty($conf->accounting->enabled))
			{
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('',$object->account_capital, 1);

				print $accountingaccount->getNomUrl(0,1,1,'',1);
			} else {
				print $object->account_capital;
			}

			print '</td>';
		}
		print '</tr>';

		// Accountancy account insurance
		print '<tr>';
		if ($action == 'edit')
		{
			print '<td class="nowrap fieldrequired">';
			print $langs->trans("LoanAccountancyInsuranceCode");
			print '</td><td>';

			if (! empty($conf->accounting->enabled))
			{
				print $formaccounting->select_account($object->account_insurance, 'accountancy_account_insurance', 1, '', 1, 1);
			}
			else
			{
				print '<input name="accountancy_account_insurance" size="16" value="'.$object->account_insurance.'">';
			}
			print '</td>';
		}
		else
		{
			print '<td class="nowrap">';
			print $langs->trans("LoanAccountancyCapitalCode");
			print '</td><td>';

			if (! empty($conf->accounting->enabled))
			{
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('',$object->account_insurance, 1);

				print $accountingaccount->getNomUrl(0,1,1,'',1);
			} else {
				print $object->account_insurance;
			}

			print '</td>';
		}
		print '</tr>';

		// Accountancy account interest
		print '<tr>';
		if ($action == 'edit')
		{
			print '<td class="nowrap fieldrequired">';
			print $langs->trans("LoanAccountancyInterestCode");
			print '</td><td>';

			if (! empty($conf->accounting->enabled))
			{
				print $formaccounting->select_account($object->account_interest, 'accountancy_account_interest', 1, '', 1, 1);
			}
			else
			{
				print '<input name="accountancy_account_interest" size="16" value="'.$object->account_interest.'">';
			}
			print '</td>';
		}
		else
		{
			print '<td class="nowrap">';
			print $langs->trans("LoanAccountancyInterestCode");
			print '</td><td>';

			if (! empty($conf->accounting->enabled))
			{
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('',$object->account_interest, 1);

				print $accountingaccount->getNomUrl(0,1,1,'',1);
			} else {
				print $object->account_interest;
			}

			print '</td>';
		}
		print '</tr>';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';

		/*
		 * Payments
		 */
		$sql = "SELECT p.rowid, p.num_payment, datep as dp,";
		$sql.= " p.amount_capital, p.amount_insurance, p.amount_interest,";
		$sql.= " c.libelle as paiement_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."payment_loan as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepayment = c.id";
		$sql.= ", ".MAIN_DB_PREFIX."loan as l";
		$sql.= " WHERE p.fk_loan = ".$id;
		$sql.= " AND p.fk_loan = l.rowid";
		$sql.= " AND l.entity IN ( ".getEntity('loan').")";
		$sql.= " ORDER BY dp DESC";

		//print $sql;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$total_insurance = 0;
			$total_interest = 0;
			$total_capital = 0;
			print '<table class="noborder">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			print '<td align="right">'.$langs->trans("Insurance").'</td>';
			print '<td align="right">'.$langs->trans("Interest").'</td>';
			print '<td align="right">'.$langs->trans("LoanCapital").'</td>';
			print '</tr>';

			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td><a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"),"payment").' '.$objp->rowid.'</a></td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
				print "<td>".$objp->paiement_type.' '.$objp->num_payment."</td>\n";
				print '<td align="right">'.price($objp->amount_insurance, 0, $outputlangs, 1, -1, -1, $conf->currency)."</td>\n";
				print '<td align="right">'.price($objp->amount_interest, 0, $outputlangs, 1, -1, -1, $conf->currency)."</td>\n";
				print '<td align="right">'.price($objp->amount_capital, 0, $outputlangs, 1, -1, -1, $conf->currency)."</td>\n";
				print "</tr>";
				$total_capital += $objp->amount_capital;
				$i++;
			}

			$totalpaid = $total_capital;

			if ($object->paid == 0)
			{
				print '<tr><td colspan="5" align="right">'.$langs->trans("AlreadyPaid").' :</td><td align="right">'.price($totalpaid, 0, $langs, 0, 0, -1, $conf->currency).'</td></tr>';
				print '<tr><td colspan="5" align="right">'.$langs->trans("AmountExpected").' :</td><td align="right">'.price($object->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

				$staytopay = $object->capital - $totalpaid;

				print '<tr><td colspan="5" align="right">'.$langs->trans("RemainderToPay").' :</td>';
				print '<td align="right"'.($staytopay?' class="amountremaintopay"':'class="amountpaymentcomplete"').'>';
				print price($staytopay, 0, $langs, 0, 0, -1, $conf->currency);
				print '</td></tr>';
			}
			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		dol_fiche_end();

		if ($action == 'edit')
		{
			print '<div class="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';
		}

		/*
		 *  Buttons actions
		 */
		if ($action != 'edit')
		{
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
 			if (empty($reshook))
			{
				print '<div class="tabsAction">';

				// Edit
				if ($object->paid == 0 && $user->rights->loan->write)
				{
					print '<a href="javascript:popEcheancier()" class="butAction">'.$langs->trans('CreateCalcSchedule').'</a>';

					print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
				}

				// Emit payment
				if ($object->paid == 0 && ((price2num($object->capital) > 0 && round($staytopay) < 0) || (price2num($object->capital) > 0 && round($staytopay) > 0)) && $user->rights->loan->write)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&amp;action=create">'.$langs->trans("DoPayment").'</a>';
				}

				// Classify 'paid'
				if ($object->paid == 0 && round($staytopay) <=0 && $user->rights->loan->write)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&amp;action=paid">'.$langs->trans("ClassifyPaid").'</a>';
				}

				// Delete
				if ($object->paid == 0 && $user->rights->loan->delete)
				{
					print '<a class="butActionDelete" href="'.DOL_URL_ROOT.'/loan/card.php?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
				}

				print "</div>";
			}
		}
	}
	else
	{
		// Loan not found
		dol_print_error('',$object->error);
	}
}

// End of page
llxFooter();
$db->close();
