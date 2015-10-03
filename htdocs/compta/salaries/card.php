<?php
/* Copyright (C) 2011-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Charlie BENKE	<charlie@patas-monkey.com> 
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
 *	    \file       htdocs/compta/salaries/card.php
 *      \ingroup    salaries
 *		\brief      Page of salaries payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';


$langs->load("compta");
$langs->load("banks");
$langs->load("bills");
$langs->load("users");
$langs->load("salaries");

$id=GETPOST("id",'int');
$action=GETPOST('action');

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'salaries', '', '', '');

$object = new PaymentSalary($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('salarycard','globalcard'));



/**
 * Actions
 */

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
	header("Location: index.php");
	exit;
}

if ($action == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
	$error=0;

	$datep=dol_mktime(12,0,0, $_POST["datepmonth"], $_POST["datepday"], $_POST["datepyear"]);
	$datev=dol_mktime(12,0,0, $_POST["datevmonth"], $_POST["datevday"], $_POST["datevyear"]);
	$datesp=dol_mktime(12,0,0, $_POST["datespmonth"], $_POST["datespday"], $_POST["datespyear"]);
	$dateep=dol_mktime(12,0,0, $_POST["dateepmonth"], $_POST["dateepday"], $_POST["dateepyear"]);
	if (empty($datev)) $datev=$datep;
	
	$object->accountid=GETPOST("accountid","int");
	$object->fk_user=GETPOST("fk_user","int");
	$object->datev=$datev;
	$object->datep=$datep;
	$object->amount=price2num(GETPOST("amount"));
	$object->label=GETPOST("label");
	$object->datesp=$datesp;
	$object->dateep=$dateep;
	$object->note=GETPOST("note");
	$object->type_payment=GETPOST("paymenttype");
	$object->num_payment=GETPOST("num_payment");
	$object->fk_user_author=$user->id;

	// Set user current salary as ref salaray for the payment
	$fuser=new User($db);
	$fuser->fetch(GETPOST("fk_user","int"));
	$object->salary=$fuser->salary;

	if (empty($datep) || empty($datev) || empty($datesp) || empty($dateep))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
		$error++;
	}
	if (empty($object->fk_user) || $object->fk_user < 0)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Employee")),'errors');
		$error++;
	}
	if (empty($object->type_payment) || $object->type_payment < 0)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PaymentMode")),'errors');
		$error++;
	}
	if (empty($object->amount))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Amount")),'errors');
		$error++;
	}
	if (! empty($conf->banque->enabled) && ! $object->accountid > 0)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Account")),'errors');
		$error++;
	}
	
	if (! $error)
	{
		$db->begin();

		$ret=$object->create($user);
		if ($ret > 0)
		{
			$db->commit();
			header("Location: index.php");
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
				header("Location: ".DOL_URL_ROOT.'/compta/salaries/index.php');
				exit;
			}
			else
			{
				$object->error=$accountline->error;
				$db->rollback();
				setEventMessage($object->error,'errors');
			}
		}
		else
		{
			$db->rollback();
			setEventMessage($object->error,'errors');
		}
	}
	else
	{
		setEventMessage('Error try do delete a line linked to a conciliated bank transaction','errors');
	}
}


/*
 *	View
 */

llxHeader();

$form = new Form($db);

if ($id)
{
	$object = new PaymentSalary($db);
	$result = $object->fetch($id);
	if ($result <= 0)
	{
		dol_print_error($db);
		exit;
	}
}

// Formulaire saisie salaire
if ($action == 'create')
{
	$year_current = strftime("%Y",dol_now());
	$pastmonth = strftime("%m",dol_now()) - 1;
	$pastmonthyear = $year_current;
	if ($pastmonth == 0)
	{
		$pastmonth = 12;
		$pastmonthyear--;
	}

	$datesp=dol_mktime(0, 0, 0, $datespmonth, $datespday, $datespyear);
	$dateep=dol_mktime(23, 59, 59, $dateepmonth, $dateepday, $dateepyear);

	if (empty($datesp) || empty($dateep)) // We define date_start and date_end
	{
		$datesp=dol_get_first_day($pastmonthyear,$pastmonth,false); $dateep=dol_get_last_day($pastmonthyear,$pastmonth,false);
	}

	print '<form name="salary" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print load_fiche_titre($langs->trans("NewSalaryPayment"),'', 'title_accountancy.png');

	dol_fiche_head('', '');
	
	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td class="fieldrequired"><label for="datep">'.$langs->trans("DatePayment").'</label></td><td>';
	print $form->select_date((empty($datep)?-1:$datep),"datep",'','','','add',1,1);
	print '</td></tr>';

	print '<tr><td><label for="datev">'.$langs->trans("DateValue").'</label></td><td>';
	print $form->select_date((empty($datev)?-1:$datev),"datev",'','','','add',1,1);
	print '</td></tr>';

	// Employee
	print "<tr>";
	print '<td class="fieldrequired"><label for="fk_user">'.$langs->trans("Employee").'</label></td><td>';
	print $form->select_dolusers(GETPOST('fk_user','int'),'fk_user',1);
	print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired"><label for="label">'.$langs->trans("Label").'</label></td><td><input name="label" id="label" size="40" value="'.($_POST["label"]?$_POST["label"]:$langs->trans("SalaryPayment")).'"></td></tr>';

	print "<tr>";
	print '<td class="fieldrequired"><label for="datesp">'.$langs->trans("DateStartPeriod").'</label></td><td>';
	print $form->select_date($datesp,"datesp",'','','','add');
	print '</td></tr>';

	print '<tr><td class="fieldrequired"><label for="dateep">'.$langs->trans("DateEndPeriod").'</label></td><td>';
	print $form->select_date($dateep,"dateep",'','','','add');
	print '</td></tr>';

	// Amount
	print '<tr><td class="fieldrequired"><label for="amount">'.$langs->trans("Amount").'</label></td><td><input name="amount" id="amount" size="10" value="'.GETPOST("amount").'"></td></tr>';

	// Bank
	if (! empty($conf->banque->enabled))
	{
		print '<tr><td class="fieldrequired"><label for="selectaccountid">'.$langs->trans("Account").'</label></td><td>';
		$form->select_comptes($_POST["accountid"],"accountid",0,'',1);  // Affiche liste des comptes courant
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td class="fieldrequired"><label for="selectpaymenttype">'.$langs->trans("PaymentMode").'</label></td><td>';
	$form->select_types_paiements(GETPOST("paymenttype"), "paymenttype");
	print "</td>\n";
	print "</tr>";

	if (! empty($conf->banque->enabled))
	{
		// Number
		print '<tr><td><label for="num_payment">'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '</label></td>';
		print '<td><input name="num_payment" id="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";
	}

	// Other attributes
	$parameters=array('colspan' => ' colspan="1"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

if ($id)
{

	$head=salaries_prepare_head($object);

	dol_fiche_head($head, 'card', $langs->trans("SalaryPayment"), 0, 'payment');

	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $object->ref;
	print '</td></tr>';

	// Person
	print '<tr><td>'.$langs->trans("Person").'</td><td>';
	$usersal=new User($db);
	$usersal->fetch($object->fk_user);
	print $usersal->getNomUrl(1);
	print '</td></tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DateStartPeriod").'</td><td colspan="3">';
	print dol_print_date($object->datesp,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateEndPeriod").'</td><td colspan="3">';
	print dol_print_date($object->dateep,'day');
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td colspan="3">';
	print dol_print_date($object->datep,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateValue").'</td><td colspan="3">';
	print dol_print_date($object->datev,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="3">'.price($object->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	if (! empty($conf->banque->enabled))
	{
		if ($object->fk_account > 0)
		{
			$bankline=new AccountLine($db);
			$bankline->fetch($object->fk_bank);

			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td colspan="3">';
			print $bankline->getNomUrl(1,0,'showall');
			print '</td>';
			print '</tr>';
		}
	}

	// Other attributes
	$parameters=array('colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

	print '</table>';

	dol_fiche_end();

	
	/*
	 * Action buttons
	 */
	print '<div class="tabsAction">'."\n";
	if ($object->rappro == 0)
	{
		if (! empty($user->rights->salaries->delete))
		{
			print '<a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete">'.$langs->trans("Delete").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.(dol_escape_htmltag($langs->trans("NotAllowed"))).'">'.$langs->trans("Delete").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("LinkedToAConcialitedTransaction").'">'.$langs->trans("Delete").'</a>';
	}
	print "</div>";
}



llxFooter();

$db->close();
