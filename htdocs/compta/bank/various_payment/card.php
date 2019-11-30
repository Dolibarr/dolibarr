<?php
/* Copyright (C) 2017-2019  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/compta/bank/various_expenses/card.php
 *  \ingroup    bank
 *  \brief      Page of various expenses
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
if (! empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "users", "accountancy", "categories"));

// Get parameters
$id			= GETPOST('id', 'int');
$action		= GETPOST('action', 'alpha');
$cancel		= GETPOST('cancel', 'aZ09');
$backtopage	= GETPOST('backtopage', 'alpha');

$accountid =            GETPOST("accountid") > 0 ? GETPOST("accountid", "int") : 0;
$label =                GETPOST("label", "alpha");
$sens =                 GETPOST("sens", "int");
$amount =               price2num(GETPOST("amount", "alpha"));
$paymenttype =          GETPOST("paymenttype", "int");
$accountancy_code =     GETPOST("accountancy_code", "alpha");
$subledger_account =    GETPOST("subledger_account", "alpha");
$projectid =            (GETPOST('projectid', 'int') ? GETPOST('projectid', 'int') : GETPOST('fk_project', 'int'));

// Security check
$socid = GETPOST("socid", "int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '', '', '');

$object = new PaymentVarious($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('variouscard','globalcard'));

/**
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Link to a project
	if ($action == 'classin' && $user->rights->banque->modifier)
	{
		$object->fetch($id);
		$object->setProject(GETPOST('projectid'));
	}

	if ($cancel)
	{
		if ($action != 'addlink')
		{
			$urltogo=$backtopage?$backtopage:dol_buildpath('/compta/bank/various_payment/list.php', 1);
			header("Location: ".$urltogo);
			exit;
		}
		if ($id > 0 || ! empty($ref)) $ret = $object->fetch($id, $ref);
		$action='';
	}

	if ($action == 'add')
	{
		$error=0;

		$datep=dol_mktime(12, 0, 0, GETPOST("datepmonth", 'int'), GETPOST("datepday", 'int'), GETPOST("datepyear", 'int'));
		$datev=dol_mktime(12, 0, 0, GETPOST("datevmonth", 'int'), GETPOST("datevday", 'int'), GETPOST("datevyear", 'int'));
		if (empty($datev)) $datev=$datep;

		$object->ref='';	// TODO
		$object->accountid=GETPOST("accountid", 'int') > 0 ? GETPOST("accountid", "int") : 0;
		$object->datev=$datev;
		$object->datep=$datep;
		$object->amount=price2num(GETPOST("amount", 'alpha'));
		$object->label=GETPOST("label", 'none');
		$object->note=GETPOST("note", 'none');
		$object->type_payment=GETPOST("paymenttype", 'int') > 0 ? GETPOST("paymenttype", "int") : 0;
		$object->num_payment=GETPOST("num_payment", 'alpha');
		$object->fk_user_author=$user->id;
		$object->category_transaction=GETPOST("category_transaction", 'alpha');

		$object->accountancy_code=GETPOST("accountancy_code") > 0 ? GETPOST("accountancy_code", "alpha") : "";
        $object->subledger_account=GETPOST("subledger_account") > 0 ? GETPOST("subledger_account", "alpha") : "";

		$object->sens=GETPOST('sens');
		$object->fk_project= GETPOST('fk_project', 'int');

		if (empty($datep) || empty($datev))
		{
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
			$error++;
		}
		if (empty($object->type_payment) || $object->type_payment < 0)
		{
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
			$error++;
		}
		if (empty($object->amount))
		{
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
			$error++;
		}
		if (! empty($conf->banque->enabled) && ! $object->accountid > 0)
		{
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
			$error++;
		}
		// TODO Remove this and allow instead to edit a various payment to enter accounting code
		if (! empty($conf->accounting->enabled) && ! $object->accountancy_code)
		{
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), null, 'errors');
			$error++;
		}

		if (! $error)
		{
			$db->begin();

			$ret=$object->create($user);
			if ($ret > 0)
			{
				$db->commit();
				$urltogo=($backtopage ? $backtopage : DOL_URL_ROOT.'/compta/bank/various_payment/list.php');
				header("Location: ".$urltogo);
				exit;
			}
			else
			{
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
				$action="create";
			}
		}

		$action='create';
	}

	if ($action == 'delete')
	{
		$result=$object->fetch($id);

		if ($object->rappro == 0)
		{
			$db->begin();

			$ret=$object->delete($user);
			if ($ret > 0)
			{
				if ($object->fk_bank)
				{
					$accountline=new AccountLine($db);
					$result=$accountline->fetch($object->fk_bank);
					if ($result > 0) $result=$accountline->delete($user);	// $result may be 0 if not found (when bank entry was deleted manually and fk_bank point to nothing)
				}

				if ($result >= 0)
				{
					$db->commit();
					header("Location: ".DOL_URL_ROOT.'/compta/bank/various_payment/list.php');
					exit;
				}
				else
				{
					$object->error=$accountline->error;
					$db->rollback();
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
			else
			{
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		else
		{
			setEventMessages('Error try do delete a line linked to a conciliated bank transaction', null, 'errors');
		}
	}
}


/*
 *	View
 */

llxHeader("", $langs->trans("VariousPayment"));

$form = new Form($db);
if (! empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);
if (! empty($conf->projet->enabled)) $formproject = new FormProjets($db);

if ($id)
{
	$object = new PaymentVarious($db);
	$result = $object->fetch($id);
	if ($result <= 0)
	{
		dol_print_error($db);
		exit;
	}
}

$options = array();

// Load bank groups
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
$bankcateg = new BankCateg($db);

foreach ($bankcateg->fetchAll() as $bankcategory) {
    $options[$bankcategory->id] = $bankcategory->label;
}

/* ************************************************************************** */
/*                                                                            */
/* Create mode                                                                */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'create')
{
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="action" value="add">';

	print load_fiche_titre($langs->trans("NewVariousPayment"), '', 'title_accountancy.png');

	dol_fiche_head('', '');

	print '<table class="border" width="100%">';

	// Date payment
	print '<tr><td class="titlefieldcreate">';
	print $form->editfieldkey('DatePayment', 'datep', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate((empty($datep)?-1:$datep), "datep", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Date value for bank
	print '<tr><td>';
	print $form->editfieldkey('DateValue', 'datev', '', $object, 0).'</td><td>';
	print $form->selectDate((empty($datev)?-1:$datev), "datev", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Label
	print '<tr><td>';
	print $form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="label" id="label" class="minwidth300" value="'.($label?$label:$langs->trans("VariousPayment")).'">';
	print '</td></tr>';

	// Sens
	print '<tr><td>';
	print $form->editfieldkey('Sens', 'sens', '', $object, 0, 'string', '', 1).'</td><td>';
    $sensarray=array( '0' => $langs->trans("Debit"), '1' => $langs->trans("Credit"));
    print $form->selectarray('sens', $sensarray, $sens);
	print '</td></tr>';

	// Amount
	print '<tr><td>';
	print $form->editfieldkey('Amount', 'amount', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="amount" id="amount" class="minwidth100" value="'.$amount.'">';
	print '</td></tr>';

	// Bank
	if (! empty($conf->banque->enabled))
	{
		print '<tr><td>';
		print $form->editfieldkey('BankAccount', 'selectaccountid', '', $object, 0, 'string', '', 1).'</td><td>';
		$form->select_comptes($accountid, "accountid", 0, '', 1);  // Affiche liste des comptes courant
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td>';
	print $form->editfieldkey('PaymentMode', 'selectpaymenttype', '', $object, 0, 'string', '', 1).'</td><td>';
	$form->select_types_paiements($paymenttype, "paymenttype");
	print '</td></tr>';

	// Number
	if (! empty($conf->banque->enabled))
	{
		// Number
		print '<tr><td><label for="num_payment">'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '</label></td>';
		print '<td><input name="num_payment" id="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";
	}

    // Project
    if (! empty($conf->projet->enabled))
    {
        $formproject=new FormProjets($db);

        // Associated project
        $langs->load("projects");

        print '<tr><td>'.$langs->trans("Project").'</td><td>';

        $numproject=$formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1);

        print '</td></tr>';
    }

    // Other attributes
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print '</td></tr>';

    // Category
    if (is_array($options) && count($options) && $conf->categorie->enabled)
    {
    	print '<tr><td>'.$langs->trans("RubriquesTransactions").'</td><td>';
    	print Form::selectarray('category_transaction', $options, GETPOST('category_transaction'), 1);
    	print '</td></tr>';
    }

	// Accountancy account
	if (! empty($conf->accounting->enabled))
	{
		// TODO Remove the fieldrequired and allow instead to edit a various payment to enter accounting code
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("AccountAccounting").'</td>';
        print '<td>';
		print $formaccounting->select_account($accountancy_code, 'accountancy_code', 1, null, 1, 1, '');
        print '</td></tr>';
	}
	else // For external software
	{
		print '<tr><td class="titlefieldcreate">'.$langs->trans("AccountAccounting").'</td>';
		print '<td class="maxwidthonsmartphone"><input class="minwidth100" name="accountancy_code" value="'.$accountancy_code.'">';
		print '</td></tr>';
	}

    // Subledger account
    if (! empty($conf->accounting->enabled))
    {
        print '<tr><td>'.$langs->trans("SubledgerAccount").'</td>';
        print '<td>';
        if (! empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX))
        {
            print $formaccounting->select_auxaccount($subledger_account, 'subledger_account', 1, '');
        }
        else
        {
            print '<input type="text" class="maxwidth200" name="subledger_account" value="'.$subledger_account.'">';
        }
        print '</td></tr>';
    }
    else // For external software
    {
        print '<tr><td>'.$langs->trans("SubledgerAccount").'</td>';
        print '<td class="maxwidthonsmartphone"><input class="minwidth100" name="subledger_account" value="'.$subledger_account.'">';
        print '</td></tr>';
    }

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print ' &nbsp; ';
	print '<input type="button" class="button" value="'.$langs->trans("Cancel").'" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */

if ($id)
{
	$head=various_payment_prepare_head($object);

	dol_fiche_head($head, 'card', $langs->trans("VariousPayment"), -1, $object->picto);

	$morehtmlref='<div class="refidno">';
	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref.=$langs->trans('Project') . ' ';
		if ($user->rights->banque->modifier)
		{
			if ($action != 'classify')
				$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref.='<input type="hidden" name="action" value="classin">';
					$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					$morehtmlref.=$formproject->select_projects(0, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref.='</form>';
				} else {
					$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
		} else {
			if (! empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref.=$proj->getNomUrl(1);
			} else {
				$morehtmlref.='';
			}
		}
	}
	$morehtmlref.='</div>';
	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/list.php?restore_lastsearch_values=1'.(! empty($socid)?'&socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="titlefield">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	// Payment date
	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep, 'day');
	print '</td></tr>';

	// Value date
	print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
	print dol_print_date($object->datev, 'day');
	print '</td></tr>';

	// Debit / Credit
	if ($object->sens == '1') $sens = $langs->trans("Credit"); else $sens = $langs->trans("Debit");
	print '<tr><td>'.$langs->trans("Sens").'</td><td>'.$sens.'</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';

	// Accountancy code
	print '<tr><td class="nowrap">';
	print $langs->trans("AccountAccounting");
	print '</td><td>';
	if (! empty($conf->accounting->enabled))
	{
		$accountingaccount = new AccountingAccount($db);
		$accountingaccount->fetch('', $object->accountancy_code, 1);

		print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
	} else {
		print $object->accountancy_code;
	}
	print '</td></tr>';

    // Subledger account
    print '<tr><td class="nowrap">';
    print $langs->trans("SubledgerAccount");
    print '</td><td>';
    print $object->subledger_account;
    print '</td></tr>';

	if (! empty($conf->banque->enabled))
	{
		if ($object->fk_account > 0)
		{
			$bankline=new AccountLine($db);
			$bankline->fetch($object->fk_bank);

			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td colspan="3">';
			print $bankline->getNomUrl(1, 0, 'showall');
			print '</td>';
			print '</tr>';
		}
	}

	// Other attributes
	$parameters=array('socid'=>$object->id);
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();


	/*
	 * Action buttons
	 */
	print '<div class="tabsAction">'."\n";

	// TODO
	// Add button modify

	// Delete
	if (empty($object->rappro))
	{
		if (! empty($user->rights->banque->modifier))
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete">'.$langs->trans("Delete").'</a></div>';
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.(dol_escape_htmltag($langs->trans("NotAllowed"))).'">'.$langs->trans("Delete").'</a></div>';
		}
	}
	else
	{
		print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("LinkedToAConciliatedTransaction").'">'.$langs->trans("Delete").'</a></div>';
	}

	print "</div>";
}

// End of page
llxFooter();
$db->close();
