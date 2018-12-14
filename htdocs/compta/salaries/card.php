<?php
/* Copyright (C) 2011-2018  Alexandre Spangaro      <aspangaro@zendsi.com>
 * Copyright (C) 2014       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Charlie BENKE           <charlie@patas-monkey.com>
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
 *  \file       htdocs/compta/salaries/card.php
 *  \ingroup    salaries
 *  \brief      Page of salaries payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
if (! empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("compta","banks","bills","users","salaries","hrm"));
if (! empty($conf->projet->enabled))	$langs->load("projects");

$id=GETPOST("id",'int');
$action=GETPOST('action','aZ09');
$cancel= GETPOST('cancel', 'aZ09');
$projectid = (GETPOST('projectid','int') ? GETPOST('projectid', 'int') : GETPOST('fk_project','int'));

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'salaries', '', '', '');

$object = new PaymentSalary($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('salarycard','globalcard'));



/**
 * Actions
 */

if ($cancel)
{
	header("Location: list.php");
	exit;
}

// Link to a project
if ($action == 'classin' && $user->rights->banque->modifier)
{
	$object->fetch($id);
	$object->setProject(GETPOST('projectid'));
}

if ($action == 'add' && empty($cancel))
{
	$error=0;

	$datep=dol_mktime(12,0,0, GETPOST("datepmonth",'int'), GETPOST("datepday",'int'), GETPOST("datepyear",'int'));
	$datev=dol_mktime(12,0,0, GETPOST("datevmonth",'int'), GETPOST("datevday",'int'), GETPOST("datevyear",'int'));
	$datesp=dol_mktime(12,0,0, GETPOST("datespmonth",'int'), GETPOST("datespday",'int'), GETPOST("datespyear",'int'));
	$dateep=dol_mktime(12,0,0, GETPOST("dateepmonth",'int'), GETPOST("dateepday",'int'), GETPOST("dateepyear",'int'));
	if (empty($datev)) $datev=$datep;

	$type_payment = dol_getIdFromCode($db, GETPOST("paymenttype", 'alpha'), 'c_paiement', 'code', 'id', 1);

	$object->accountid=GETPOST("accountid") > 0 ? GETPOST("accountid","int") : 0;
	$object->fk_user=GETPOST("fk_user") > 0 ? GETPOST("fk_user","int") : 0;
	$object->datev=$datev;
	$object->datep=$datep;
	$object->amount=price2num(GETPOST("amount"));
	$object->label=GETPOST("label");
	$object->datesp=$datesp;
	$object->dateep=$dateep;
	$object->note=GETPOST("note");
	$object->type_payment=($type_payment > 0 ? $type_payment : 0);
	$object->num_payment=GETPOST("num_payment");
	$object->fk_user_author=$user->id;
	$object->fk_project= GETPOST('fk_project','int');

	// Set user current salary as ref salaray for the payment
	$fuser=new User($db);
	$fuser->fetch(GETPOST("fk_user","int"));
	$object->salary=$fuser->salary;

	if (empty($datep) || empty($datev) || empty($datesp) || empty($dateep))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		$error++;
	}
	if (empty($object->fk_user) || $object->fk_user < 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Employee")), null, 'errors');
		$error++;
	}
	if (empty($type_payment) || $type_payment < 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
		$error++;
	}
	if (empty($object->amount))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
		$error++;
	}
	if (! empty($conf->banque->enabled) && ! $object->accountid > 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
		$error++;
	}

	if (! $error)
	{
		$db->begin();

		$ret=$object->create($user);
		if ($ret > 0)
		{
			$db->commit();
			header("Location: list.php");
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
				header("Location: ".DOL_URL_ROOT.'/compta/salaries/list.php');
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


/*
 *	View
 */

llxHeader("",$langs->trans("SalaryPayment"));

$form = new Form($db);
if (! empty($conf->projet->enabled)) $formproject = new FormProjets($db);

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

// Create
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

	$datespmonth = GETPOST('datespmonth', 'int');
	$datespday = GETPOST('datespday', 'int');
	$datespyear = GETPOST('datespyear', 'int');
	$dateepmonth = GETPOST('dateepmonth', 'int');
	$dateepday = GETPOST('dateepday', 'int');
	$dateepyear = GETPOST('dateepyear', 'int');
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

	// Date payment
	print '<tr><td>';
	print $form->editfieldkey('DatePayment', 'datep', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate((empty($datep)?-1:$datep), "datep", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Date value for bank
	print '<tr><td>';
	print $form->editfieldkey('DateValue', 'datev', '', $object, 0).'</td><td>';
	print $form->selectDate((empty($datev)?-1:$datev), "datev", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Employee
	print '<tr><td>';
	print $form->editfieldkey('Employee', 'fk_user', '', $object, 0, 'string', '', 1).'</td><td>';
	$noactive=0;	// We keep active and unactive users
	print $form->select_dolusers(GETPOST('fk_user','int'), 'fk_user', 1, '', 0, '', '', 0, 0, 0, 'AND employee=1', 0, '', 'maxwidth300', $noactive);
	print '</td></tr>';

	// Label
	print '<tr><td>';
	print $form->editfieldkey('Label', 'label', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="label" id="label" class="minwidth300" value="'.(GETPOST("label")?GETPOST("label"):$langs->trans("SalaryPayment")).'">';
	print '</td></tr>';

	// Date start period
	print '<tr><td>';
	print $form->editfieldkey('DateStartPeriod', 'datesp', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate($datesp, "datesp", '', '', '', 'add');
	print '</td></tr>';

	// Date end period
	print '<tr><td>';
	print $form->editfieldkey('DateEndPeriod', 'dateep', '', $object, 0, 'string', '', 1).'</td><td>';
	print $form->selectDate($dateep, "dateep", '', '', '', 'add');
	print '</td></tr>';

	// Amount
	print '<tr><td>';
	print $form->editfieldkey('Amount', 'amount', '', $object, 0, 'string', '', 1).'</td><td>';
	print '<input name="amount" id="amount" class="minwidth100" value="'.GETPOST("amount").'">';
	print '</td></tr>';

	// Project
	if (! empty($conf->projet->enabled))
	{
		$formproject=new FormProjets($db);

		print '<tr><td>'.$langs->trans("Project").'</td><td>';

		$numproject=$formproject->select_projects(-1, $projectid,'fk_project',0,0,1,1);

		print '</td></tr>';
	}

	// Bank
	if (! empty($conf->banque->enabled))
	{
		print '<tr><td>';
		print $form->editfieldkey('BankAccount', 'selectaccountid', '', $object, 0, 'string', '', 1).'</td><td>';
		$form->select_comptes($_POST["accountid"],"accountid",0,'',1);  // Affiche liste des comptes courant
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td>';
	print $form->editfieldkey('PaymentMode', 'selectpaymenttype', '', $object, 0, 'string', '', 1).'</td><td>';
	$form->select_types_paiements(GETPOST("paymenttype"), "paymenttype", '', 2);
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

	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

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
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */

if ($id)
{

	$head=salaries_prepare_head($object);

	dol_fiche_head($head, 'card', $langs->trans("SalaryPayment"), -1, 'payment');

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/salaries/list.php?restore_lastsearch_values=1'.(! empty($socid)?'&socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';

	// Employee
	$userstatic=new User($db);
	$userstatic->fetch($object->fk_user);
	$morehtmlref.=$langs->trans('Employee') . ' : ' . $userstatic->getNomUrl(1);

	// Project
	if (! empty($conf->projet->enabled))
	{
		$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
		if ($user->rights->salaries->write)
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
				$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
				$morehtmlref.=$proj->ref;
				$morehtmlref.='</a>';
			} else {
				$morehtmlref.='';
			}
		}
	}
	$morehtmlref.='</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="titlefield">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DateStartPeriod").'</td><td>';
	print dol_print_date($object->datesp,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateEndPeriod").'</td><td>';
	print dol_print_date($object->dateep,'day');
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
	print dol_print_date($object->datev,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($object->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	if (! empty($conf->banque->enabled))
	{
		if ($object->fk_account > 0)
		{
			$bankline=new AccountLine($db);
			$bankline->fetch($object->fk_bank);

			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td>';
			print $bankline->getNomUrl(1,0,'showall');
			print '</td>';
			print '</tr>';
		}
	}

	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print '</div>';

	dol_fiche_end();


	/*
	 * Action buttons
	 */
	print '<div class="tabsAction">'."\n";
	if ($object->rappro == 0)
	{
		if (! empty($user->rights->salaries->delete))
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete">'.$langs->trans("Delete").'</a>';
		}
		else
		{
			print '<a class="butActionRefused classfortooltip" href="#" title="'.(dol_escape_htmltag($langs->trans("NotAllowed"))).'">'.$langs->trans("Delete").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("LinkedToAConciliatedTransaction").'">'.$langs->trans("Delete").'</a>';
	}
	print "</div>";
}

// End of page
llxFooter();
$db->close();
