<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2017 Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/compta/tva/card.php
 *      \ingroup    tax
 *		\brief      Page of VAT payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/vat.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'banks', 'bills'));

$id = GETPOST("id", 'int');
$action = GETPOST("action", "alpha");
$refund = GETPOST("refund", "int");
if (empty($refund)) $refund = 0;

$datev = dol_mktime(12, 0, 0, GETPOST("datevmonth", 'int'), GETPOST("datevday", 'int'), GETPOST("datevyear", 'int'));
$datep = dol_mktime(12, 0, 0, GETPOST("datepmonth", 'int'), GETPOST("datepday", 'int'), GETPOST("datepyear", 'int'));


// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$object = new Tva($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('taxvatcard', 'globalcard'));


/**
 * Actions
 */

if ($_POST["cancel"] == $langs->trans("Cancel") && !$id)
{
	header("Location: list.php");
	exit;
}

if ($action == 'setlib' && $user->rights->tax->charges->creer)
{
	$object->fetch($id);
	$result = $object->setValueFrom('label', GETPOST('lib', 'alpha'), '', '', 'text', '', $user, 'TAX_MODIFY');
	if ($result < 0)
		setEventMessages($object->error, $object->errors, 'errors');
}

if ($action == 'setdatev' && $user->rights->tax->charges->creer)
{
	$object->fetch($id);
	$object->datev = $datev;
	$result = $object->update($user);
	if ($result < 0) dol_print_error($db, $object->error);

	$action = '';
}

if ($action == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
	$error = 0;

	$object->accountid = GETPOST("accountid", 'int');
	$object->type_payment = GETPOST("type_payment", 'alphanohtml');
	$object->num_payment = GETPOST("num_payment", 'alphanohtml');

	$object->datev = $datev;
	$object->datep = $datep;

	$amount = price2num(GETPOST("amount", 'alpha'));
	if ($refund == 1) {
		$amount = -$amount;
	}
	$object->amount = $amount;
	$object->label = GETPOST("label", 'alpha');
	$object->note_private = GETPOST("note", 'restricthtml');

	if (empty($object->datep))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePayment")), null, 'errors');
		$error++;
	}
	if (empty($object->datev))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PeriodEndDate")), null, 'errors');
		$error++;
	}
	if (empty($object->type_payment) || $object->type_payment < 0)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
		$error++;
	}
	if (empty($object->amount))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
		$error++;
	}

	if (!$error)
	{
		$db->begin();

		$ret = $object->addPayment($user);
		if ($ret > 0)
		{
			$db->commit();
			header("Location: list.php");
			exit;
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
			$action = "create";
		}
	}

	$action = 'create';
}

if ($action == 'delete')
{
	$result = $object->fetch($id);

	if ($object->rappro == 0)
	{
		$db->begin();

		$ret = $object->delete($user);
		if ($ret > 0)
		{
			if ($object->fk_bank)
			{
				$accountline = new AccountLine($db);
				$result = $accountline->fetch($object->fk_bank);
				if ($result > 0) $result = $accountline->delete($user); // $result may be 0 if not found (when bank entry was deleted manually and fk_bank point to nothing)
			}

			if ($result >= 0)
			{
				$db->commit();
				header("Location: ".DOL_URL_ROOT.'/compta/tva/list.php');
				exit;
			} else {
				$object->error = $accountline->error;
				$db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		$mesg = 'Error try do delete a line linked to a conciliated bank transaction';
		setEventMessages($mesg, null, 'errors');
	}
}


/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans("VAT")." - ".$langs->trans("Card");
$help_url = '';
llxHeader("", $title, $helpurl);


if ($id)
{
	$result = $object->fetch($id);
	if ($result <= 0)
	{
		dol_print_error($db);
		exit;
	}
}

// Form to enter VAT
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("VAT").' - '.$langs->trans("New"));

	if (!empty($conf->use_javascript_ajax))
	{
		print "\n".'<script type="text/javascript" language="javascript">';
		print '$(document).ready(function () {
                $("#radiopayment").click(function() {
                    $("#label").val($(this).data("label"));

                });
                $("#radiorefund").click(function() {
                    $("#label").val($(this).data("label"));

                });
        });';
		print '</script>'."\n";
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" name="formvat" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print '<div id="selectmethod">';
	print '<div class="hideonsmartphone float">';
	print $langs->trans("Type").':&nbsp;&nbsp;&nbsp;';
	print '</div>';
	print '<label for="radiopayment">';
	print '<input type="radio" id="radiopayment" data-label="'.$langs->trans('VATPayment').'" class="flat" name="refund" value="0"'.($refund ? '' : ' checked="checked"').'>';
	print '&nbsp;';
	print $langs->trans("Payment");
	print '</label>';
	print '&nbsp;&nbsp;&nbsp;';
	print '<label for="radiorefund">';
	print '<input type="radio" id="radiorefund" data-label="'.$langs->trans('VATRefund').'" class="flat" name="refund" value="1"'.($refund ? ' checked="checked"' : '').'>';
	print '&nbsp;';
	print $langs->trans("PaymentBack");
	print '</label>';
	print '</div>';
	print "<br>\n";

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	print "<tr>";
	print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
	print $form->selectDate($datep, "datep", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	print '<tr><td class="fieldrequired">'.$form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")).'</td><td>';
	print $form->selectDate((GETPOST("datevmonth", 'int') ? $datev : -1), "datev", '', '', '', 'add', 1, 1);
	print '</td></tr>';

	// Label
	if ($refund == 1) {
		$label = $langs->trans("VATRefund");
	} else {
		$label = $langs->trans("VATPayment");
	}
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input class="minwidth300" name="label" id="label" value="'.($_POST["label"] ?GETPOST("label", '', 2) : $label).'"></td></tr>';

	// Amount
	print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value="'.GETPOST("amount", "alpha").'"></td></tr>';

	if (!empty($conf->banque->enabled))
	{
		print '<tr><td class="fieldrequired">'.$langs->trans("BankAccount").'</td><td>';
		$form->select_comptes(GETPOST("accountid", 'int'), "accountid", 0, "courant=1", 2); // List of bank account available
		print '</td></tr>';
	}

	// Type payment
	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	$form->select_types_paiements(GETPOST("type_payment"), "type_payment");
	print "</td>\n";
	print "</tr>";

	// Number
	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print '<td><input name="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// View mode
if ($id)
{
	$head = vat_prepare_head($object);

	print dol_get_fiche_head($head, 'card', $langs->trans("VATPayment"), -1, 'payment');

	$morehtmlref = '<div class="refidno">';
	// Label of social contribution
	$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->rights->tax->charges->creer, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->rights->tax->charges->creer, 'string', '', null, null, '', 1);
	// Project
	$morehtmlref .= '</div>';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/tva/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	// Label
	//print '<tr><td class="titlefield">'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';

	print "<tr>";
	print '<td class="titlefield">'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep, 'day');
	print '</td></tr>';

	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")), 'datev', $object->datev, $object, $user->rights->tax->charges->creer, 'day');
	print '</td><td>';
	print $form->editfieldval("PeriodEndDate", 'datev', $object->datev, $object, $user->rights->tax->charges->creer, 'day');
	//print dol_print_date($object->datev,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($object->amount).'</td></tr>';

	if (!empty($conf->banque->enabled))
	{
		if ($object->fk_account > 0)
		{
			$bankline = new AccountLine($db);
			$bankline->fetch($object->fk_bank);

			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td>';
			print $bankline->getNomUrl(1, 0, 'showall');
			print '</td>';
			print '</tr>';
		}
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	/*
	 * Action buttons
	 */
	print "<div class=\"tabsAction\">\n";
	if ($object->rappro == 0)
	{
		if (!empty($user->rights->tax->charges->supprimer))
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a></div>';
		} else {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.(dol_escape_htmltag($langs->trans("NotAllowed"))).'">'.$langs->trans("Delete").'</a></div>';
		}
	} else {
		print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("LinkedToAConciliatedTransaction").'">'.$langs->trans("Delete").'</a></div>';
	}
	print "</div>";
}

llxFooter();
$db->close();
