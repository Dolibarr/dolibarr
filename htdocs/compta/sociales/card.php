<?php
/* Copyright (C) 2004-2020 Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin            <regis.houssin@inodbox.com>
 * Copyright (C) 2016-2018 Frédéric France          <frederic.france@netlogic.fr>
 * Copyright (C) 2017      Alexandre Spangaro       <aspangaro@open-dsi.fr>
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
 *      \file       htdocs/compta/sociales/card.php
 *		\ingroup    tax
 *		\brief      Social contribution card page
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (!empty($conf->projet->enabled)) {
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
if (!empty($conf->accounting->enabled)) {
	include_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'banks'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$projectid = (GETPOST('projectid') ? GETPOST('projectid', 'int') : 0);

$dateech = dol_mktime(GETPOST('echhour'), GETPOST('echmin'), GETPOST('echsec'), GETPOST('echmonth'), GETPOST('echday'), GETPOST('echyear'));
$dateperiod = dol_mktime(GETPOST('periodhour'), GETPOST('periodmin'), GETPOST('periodsec'), GETPOST('periodmonth'), GETPOST('periodday'), GETPOST('periodyear'));
$label = GETPOST('label', 'alpha');
$actioncode = GETPOST('actioncode');

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax', $id, 'chargesociales', 'charges');

$object = new ChargeSociales($db);

/* *************************************************************************** */
/*                                                                             */
/* Actions                                                                     */
/*                                                                             */
/* *************************************************************************** */

// Classify paid
if ($action == 'confirm_paid' && $user->rights->tax->charges->creer && $confirm == 'yes')
{
	$object->fetch($id);
	$result = $object->set_paid($user);
}

if ($action == 'reopen' && $user->rights->tax->charges->creer) {
	$result = $object->fetch($id);
	if ($object->paye)
	{
		$result = $object->set_unpaid($user);
		if ($result > 0)
		{
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
			exit();
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Link to a project
if ($action == 'classin' && $user->rights->tax->charges->creer)
{
	$object->fetch($id);
	$object->setProject(GETPOST('projectid'));
}

if ($action == 'setlib' && $user->rights->tax->charges->creer)
{
	$object->fetch($id);
	$result = $object->setValueFrom('libelle', GETPOST('lib'), '', '', 'text', '', $user, 'TAX_MODIFY');
	if ($result < 0)
		setEventMessages($object->error, $object->errors, 'errors');
}

// payment mode
if ($action == 'setmode' && $user->rights->tax->charges->creer) {
	$object->fetch($id);
	$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
	if ($result < 0)
		setEventMessages($object->error, $object->errors, 'errors');
}

// bank account
if ($action == 'setbankaccount' && $user->rights->tax->charges->creer) {
	$object->fetch($id);
	$result = $object->setBankAccount(GETPOST('fk_account', 'int'));
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Delete social contribution
if ($action == 'confirm_delete' && $confirm == 'yes')
{
	$object->fetch($id);
	$result = $object->delete($user);
	if ($result > 0) {
		header("Location: list.php");
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


// Add social contribution
if ($action == 'add' && $user->rights->tax->charges->creer)
{
	$amount = price2num(GETPOST('amount'), 'MT');

	if (!$dateech)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Date")), null, 'errors');
		$action = 'create';
	} elseif (!$dateperiod)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Period")), null, 'errors');
		$action = 'create';
	} elseif (!$actioncode > 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Type")), null, 'errors');
		$action = 'create';
	} elseif (empty($amount))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
		$action = 'create';
	} elseif (!is_numeric($amount)) {
		setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("Amount")), null, 'errors');
		$action = 'create';
	} else {
		$object->type = $actioncode;
		$object->label = GETPOST('label', 'alpha');
		$object->date_ech = $dateech;
		$object->periode = $dateperiod;
		$object->amount = $amount;
		$object->mode_reglement_id = (int) GETPOST('mode_reglement_id', 'int');
		$object->fk_account = (int) GETPOST('fk_account', 'int');
		$object->fk_project = (int) GETPOST('fk_project', 'int');

		$id = $object->create($user);
		if ($id <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	}
}


if ($action == 'update' && !$_POST["cancel"] && $user->rights->tax->charges->creer)
{
	$amount = price2num(GETPOST('amount'), 'MT');

	if (!$dateech)
	{
		setEventMessages($langs->trans("ErrorFieldReqrequire_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
uired", $langs->transnoentities("Date")), null, 'errors');
		$action = 'edit';
	} elseif (!$dateperiod)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Period")), null, 'errors');
		$action = 'edit';
	} elseif (empty($amount))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount")), null, 'errors');
		$action = 'edit';
	} elseif (!is_numeric($amount))
	{
		setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("Amount")), null, 'errors');
		$action = 'create';
	} else {
		$result = $object->fetch($id);

		$object->date_ech = $dateech;
		$object->periode = $dateperiod;
		$object->amount = $amount;

		$result = $object->update($user);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm != 'yes') { $action = ''; }

if ($action == 'confirm_clone' && $confirm == 'yes' && ($user->rights->tax->charges->creer))
{
	$db->begin();

	$originalId = $id;

	$object->fetch($id);

	if ($object->id > 0)
	{
		$object->paye = 0;
		$object->id = $object->ref = null;

		if (GETPOST('clone_label', 'alphanohtml')) {
			$object->label = GETPOST('clone_label', 'alphanohtml');
		} else {
			$object->label = $langs->trans("CopyOf").' '.$object->label;
		}

		if (GETPOST('clone_for_next_month', 'int')) {
			$object->periode = dol_time_plus_duree($object->periode, 1, 'm');
			$object->date_ech = dol_time_plus_duree($object->date_ech, 1, 'm');
		} else {
			$newdateperiod = dol_mktime(0, 0, 0, GETPOST('clone_periodmonth', 'int'), GETPOST('clone_periodday', 'int'), GETPOST('clone_periodyear', 'int'));
			$newdateech = dol_mktime(0, 0, 0, GETPOST('clone_date_echmonth', 'int'), GETPOST('clone_date_echday', 'int'), GETPOST('clone_date_echyear', 'int'));
			if ($newdateperiod) $object->periode = $newdateperiod;
			if ($newdateech) $object->date_ech = $newdateech;
		}

		if ($object->check())
		{
			$id = $object->create($user);
			if ($id > 0)
			{
				$db->commit();
				$db->close();

				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				$id = $originalId;
				$db->rollback();

				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	} else {
		$db->rollback();
		dol_print_error($db, $object->error);
	}
}





/*
 * View
 */

$form = new Form($db);
$formsocialcontrib = new FormSocialContrib($db);
$bankaccountstatic = new Account($db);
if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$title = $langs->trans("SocialContribution").' - '.$langs->trans("Card");
$help_url = 'EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("", $title, $help_url);


// Mode creation
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewSocialContribution"));

	print '<form name="charge" method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Label
	print "<tr>";
	print '<td class="titlefieldcreate fieldrequired">';
	print $langs->trans("Label");
	print '</td>';
	print '<td><input type="text" name="label" class="flat minwidth300" value="'.dol_escape_htmltag(GETPOST('label', 'alpha')).'" autofocus></td>';
	print '</tr>';
	print '<tr>';

	// Type
	print '<td class="fieldrequired">';
	print $langs->trans("Type");
	print '</td>';
	print '<td>';
	$formsocialcontrib->select_type_socialcontrib(GETPOST("actioncode", 'alpha') ?GETPOST("actioncode", 'alpha') : '', 'actioncode', 1);
	print '</td>';
	print '</tr>';

	// Date
	print '<tr>';
	print '<td class="fieldrequired">';
	print $langs->trans("Date");
	print '</td>';
	print '<td>';
	print $form->selectDate(!empty($dateech) ? $dateech : '-1', 'ech', 0, 0, 0, 'charge', 1);
	print '</td>';
	print "</tr>\n";

	// Date end period
	print '<tr>';
	print '<td class="fieldrequired">';
	print $form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo"));
	print '</td>';
	print '<td>';
	print $form->selectDate(!empty($dateperiod) ? $dateperiod : '-1', 'period', 0, 0, 0, 'charge', 1);
	print '</td>';
	print '</tr>';

	// Amount
	print '<tr>';
	print '<td class="fieldrequired">';
	print $langs->trans("Amount");
	print '</td>';
	print '<td><input type="text" size="6" name="amount" class="flat" value="'.dol_escape_htmltag(GETPOST('amount', 'alpha')).'"></td>';
	print '</tr>';

	// Project
	if (!empty($conf->projet->enabled))
	{
		$formproject = new FormProjets($db);

		// Associated project
		$langs->load("projects");

		print '<tr><td>'.$langs->trans("Project").'</td><td>';

		$numproject = $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1);

		print '</td></tr>';
	}

	// Payment Mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements($mode_reglement_id, 'mode_reglement_id');
	print '</td></tr>';

	// Bank Account
	if (!empty($conf->banque->enabled))
	{
		print '<tr><td>'.$langs->trans('BankAccount').'</td><td colspan="2">';
		$form->select_comptes($fk_account, 'fk_account', 0, '', 2);
		print '</td></tr>';
	}

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}

/* *************************************************************************** */
/*                                                                             */
/* Card Mode                                                                   */
/*                                                                             */
/* *************************************************************************** */
if ($id > 0)
{
	$object = new ChargeSociales($db);
	$result = $object->fetch($id);

	if ($result > 0)
	{
		$head = tax_prepare_head($object);

		$totalpaye = $object->getSommePaiement();

		// Clone confirmation
		if ($action === 'clone')
		{
			$formquestion = array(
				array('type' => 'text', 'name' => 'clone_label', 'label' => $langs->trans("Label"), 'value' => $langs->trans("CopyOf").' '.$object->label),
			);
			if (!empty($conf->global->TAX_ADD_CLON_FOR_NEXT_MONTH_CHECKBOX))
			{
				$formquestion[] = array('type' => 'checkbox', 'name' => 'clone_for_next_month', 'label' => $langs->trans("CloneTaxForNextMonth"), 'value' => 1);
			} else {
				$formquestion[] = array('type' => 'date', 'name' => 'clone_date_ech', 'label' => $langs->trans("Date"), 'value' => -1);
				$formquestion[] = array('type' => 'date', 'name' => 'clone_period', 'label' => $langs->trans("PeriodEndDate"), 'value' => -1);
			}

			print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneTax', $object->ref), 'confirm_clone', $formquestion, 'yes', 1, 240);
		}


		if ($action == 'paid')
		{
			$text = $langs->trans('ConfirmPaySocialContribution');
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans('PaySocialContribution'), $text, "confirm_paid", '', '', 2);
		}

		// Confirmation of the removal of the Social Contribution
		if ($action == 'delete')
		{
			$text = $langs->trans('ConfirmDeleteSocialContribution');
			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('DeleteSocialContribution'), $text, 'confirm_delete', '', '', 2);
		}

		if ($action == 'edit')
		{
			print "<form name=\"charge\" action=\"".$_SERVER["PHP_SELF"]."?id=$object->id&amp;action=update\" method=\"post\">";
			print '<input type="hidden" name="token" value="'.newToken().'">';
		}

		print dol_get_fiche_head($head, 'card', $langs->trans("SocialContribution"), -1, 'bill');

		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->rights->tax->charges->creer, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->rights->tax->charges->creer, 'string', '', null, null, '', 1);
		// Project
		if (!empty($conf->projet->enabled))
		{
			$langs->load("projects");
			$morehtmlref .= '<br>'.$langs->trans('Project').' ';
			if ($user->rights->tax->charges->creer)
			{
				if ($action != 'classify') {
					$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
				}
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref .= '<input type="hidden" name="action" value="classin">';
					$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
					$morehtmlref .= $formproject->select_projects(0, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref .= '</form>';
				} else {
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
			} else {
				if (!empty($object->fk_project)) {
					$proj = new Project($db);
					$proj->fetch($object->fk_project);
					$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
					$morehtmlref .= $proj->ref;
					$morehtmlref .= '</a>';
				} else {
					$morehtmlref .= '';
				}
			}
		}
		$morehtmlref .= '</div>';

		$morehtmlright = '';

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$object->totalpaye = $totalpaye; // To give a chance to dol_banner_tab to use already paid amount to show correct status

		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		// Type
		print '<tr><td class="titlefield">';
		print $langs->trans("Type")."</td><td>".$object->type_label."</td>";
		print "</tr>";

		// Date
		if ($action == 'edit')
		{
			print '<tr><td>'.$langs->trans("Date")."</td><td>";
			print $form->selectDate($object->date_ech, 'ech', 0, 0, 0, 'charge', 1);
			print "</td></tr>";
		} else {
			print "<tr><td>".$langs->trans("Date")."</td><td>".dol_print_date($object->date_ech, 'day')."</td></tr>";
		}

		// Period end date
		print "<tr><td>".$form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo"))."</td>";
		print "<td>";
		if ($action == 'edit')
		{
			print $form->selectDate($object->periode, 'period', 0, 0, 0, 'charge', 1);
		} else {
			print dol_print_date($object->periode, "day");
		}
		print "</td></tr>";

		// Amount
		if ($action == 'edit')
		{
			print '<tr><td>'.$langs->trans("AmountTTC")."</td><td>";
			print '<input type="text" name="amount" size="12" class="flat" value="'.$object->amount.'">';
			print "</td></tr>";
		} else {
			print '<tr><td>'.$langs->trans("AmountTTC").'</td><td>'.price($object->amount, 0, $outputlangs, 1, -1, -1, $conf->currency).'</td></tr>';
		}

		// Mode of payment
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode')
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'), 1).'</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editmode') {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id');
		} else {
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
		}
		print '</td></tr>';

		// Bank Account
		if (!empty($conf->banque->enabled))
		{
			print '<tr><td class="nowrap">';
			print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
			print $langs->trans('BankAccount');
			print '<td>';
			if ($action != 'editbankaccount' && $user->rights->tax->charges->creer)
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'), 1).'</a></td>';
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editbankaccount') {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
			} else {
				$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';

		$nbcols = 3;
		if (!empty($conf->banque->enabled)) {
			$nbcols++;
		}

		/*
		 * Payments
		 */
		$sql = "SELECT p.rowid, p.num_paiement as num_payment, datep as dp, p.amount,";
		$sql .= " c.code as type_code,c.libelle as paiement_type,";
		$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.currency_code as bacurrency_code, ba.fk_accountancy_journal';
		$sql .= " FROM ".MAIN_DB_PREFIX."paiementcharge as p";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepaiement = c.id";
		$sql .= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql .= " WHERE p.fk_charge = ".$id;
		$sql .= " AND p.fk_charge = cs.rowid";
		$sql .= " AND cs.entity IN (".getEntity('tax').")";
		$sql .= " ORDER BY dp DESC";

		//print $sql;
		$resql = $db->query($sql);
		if ($resql)
		{
			$totalpaye = 0;

			$num = $db->num_rows($resql);
			$i = 0; $total = 0;

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
			print '<table class="noborder paymenttable">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
			if (!empty($conf->banque->enabled)) {
				print '<td class="liste_titre right">'.$langs->trans('BankAccount').'</td>';
			}
			print '<td class="right">'.$langs->trans("Amount").'</td>';
			print '</tr>';

			$paymentsocialcontributiontmp = new PaymentSocialContribution($db);

			if ($num > 0)
			{
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);

					$paymentsocialcontributiontmp->id = $objp->rowid;
					$paymentsocialcontributiontmp->ref = $objp->rowid;
					$paymentsocialcontributiontmp->datep = $db->jdate($objp->dp);

					print '<tr class="oddeven"><td>';
					print $paymentsocialcontributiontmp->getNomUrl(1);
					print '</td>';

					print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
					$labeltype = $langs->trans("PaymentType".$objp->type_code) != ("PaymentType".$objp->type_code) ? $langs->trans("PaymentType".$objp->type_code) : $objp->paiement_type;
					print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
					if (!empty($conf->banque->enabled))
					{
						$bankaccountstatic->id = $objp->baid;
						$bankaccountstatic->ref = $objp->baref;
						$bankaccountstatic->label = $objp->baref;
						$bankaccountstatic->number = $objp->banumber;
						$bankaccountstatic->currency_code = $objp->bacurrency_code;

						if (!empty($conf->accounting->enabled)) {
							$bankaccountstatic->account_number = $objp->account_number;

							$accountingjournal = new AccountingJournal($db);
							$accountingjournal->fetch($objp->fk_accountancy_journal);
							$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
						}

						print '<td class="right">';
						if ($bankaccountstatic->id)
							print $bankaccountstatic->getNomUrl(1, 'transactions');
						print '</td>';
					}
					print '<td class="right">'.price($objp->amount)."</td>\n";
					print "</tr>";
					$totalpaye += $objp->amount;
					$i++;
				}
			} else {
				print '<tr class="oddeven"><td><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
				print '<td></td><td></td><td></td><td></td>';
				print '</tr>';
			}

			print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AlreadyPaid")." :</td><td class=\"right\">".price($totalpaye)."</td></tr>\n";
			print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AmountExpected")." :</td><td class=\"right\">".price($object->amount)."</td></tr>\n";

			$resteapayer = $object->amount - $totalpaye;
			$cssforamountpaymentcomplete = 'amountpaymentcomplete';

			print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("RemainderToPay")." :</td>";
			print '<td class="right'.($resteapayer ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayer)."</td></tr>\n";

			print "</table>";
			print '</div>';

			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		if ($action == 'edit')
		{
			print '<div align="center">';
			print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
			print ' &nbsp; ';
			print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';
		}

		if ($action == 'edit') print "</form>\n";



		/*
		*   Actions buttons
		*/
		if ($action != 'edit')
		{
			print "<div class=\"tabsAction\">\n";

			// Reopen
			if ($object->paye && $user->rights->tax->charges->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath("/compta/sociales/card.php", 1).'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a></div>';
			}

			// Edit
			if ($object->paye == 0 && $user->rights->tax->charges->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
			}

			// Emit payment
			if ($object->paye == 0 && ((price2num($object->amount) < 0 && price2num($resteapayer, 'MT') < 0) || (price2num($object->amount) > 0 && price2num($resteapayer, 'MT') > 0)) && $user->rights->tax->charges->creer)
			{
				print "<div class=\"inline-block divButAction\"><a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/paiement_charge.php?id=$object->id&amp;action=create\">".$langs->trans("DoPayment")."</a></div>";
			}

			// Classify 'paid'
			if ($object->paye == 0 && round($resteapayer) <= 0 && $user->rights->tax->charges->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&amp;action=paid&amp;token='.newToken().'">'.$langs->trans("ClassifyPaid").'</a></div>';
			}

			// Clone
			if ($user->rights->tax->charges->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath("/compta/sociales/card.php", 1).'?id='.$object->id.'&amp;action=clone">'.$langs->trans("ToClone")."</a></div>";
			}

			// Delete
			if ($user->rights->tax->charges->supprimer)
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id.'&amp;action=delete&amp;token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
			}

			print "</div>";
		}
	} else {
		/* Social contribution not found */
		dol_print_error('', $object->error);
	}
}

// End of page
llxFooter();
$db->close();
