<?php
/* Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/compta/localtax/card.php
 *      \ingroup    tax
 *		\brief      Page of second or third tax payments (like IRPF for spain, ...)
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/vat.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('compta', 'banks', 'bills'));

$id = GETPOSTINT("id");
$action = GETPOST("action", "aZ09");
$cancel = GETPOST('cancel', 'aZ09');

$refund = GETPOSTINT("refund");
if (empty($refund)) {
	$refund = 0;
}

$lttype = GETPOSTINT('localTaxType');

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('localtaxvatcard', 'globalcard'));

$result = restrictedArea($user, 'tax', '', '', 'charges');

$object = new Localtax($db);

$permissiontoadd = $user->hasRight('tax', 'charges', 'creer');
$permissiontodelete = $user->hasRight('tax', 'charges', 'supprimer');


/**
 * Actions
 */

if ($cancel && !$id) {
	header("Location: list.php?localTaxType=".$lttype);
	exit;
}

if ($action == 'add' && !$cancel && $permissiontoadd) {
	$db->begin();

	$datev = dol_mktime(12, 0, 0, GETPOSTINT("datevmonth"), GETPOSTINT("datevday"), GETPOSTINT("datevyear"));
	$datep = dol_mktime(12, 0, 0, GETPOSTINT("datepmonth"), GETPOSTINT("datepday"), GETPOSTINT("datepyear"));

	$object->accountid = GETPOSTINT("accountid");
	$object->paymenttype = GETPOST("paiementtype");
	$object->datev = $datev;
	$object->datep = $datep;
	$object->amount = price2num(GETPOST("amount"));
	$object->label = GETPOST("label");
	$object->ltt = $lttype;

	$ret = $object->addPayment($user);
	if ($ret > 0) {
		$db->commit();
		header("Location: list.php?localTaxType=".$lttype);
		exit;
	} else {
		$db->rollback();
		setEventMessages($object->error, $object->errors, 'errors');
		$action = "create";
	}
}

//delete payment of localtax
if ($action == 'delete' && $permissiontodelete) {
	$result = $object->fetch($id);

	if ($object->rappro == 0) {
		$db->begin();

		$ret = $object->delete($user);
		if ($ret > 0) {
			if ($object->fk_bank) {
				$accountline = new AccountLine($db);
				$result = $accountline->fetch($object->fk_bank);
				if ($result > 0) {
					$result = $accountline->delete($user); // $result may be 0 if not found (when bank entry was deleted manually and fk_bank point to nothing)
				}
			} else {
				$accountline = null;
			}

			if ($result >= 0) {
				$db->commit();
				header("Location: ".DOL_URL_ROOT.'/compta/localtax/list.php?localTaxType='.$object->ltt);
				exit;
			} else {
				$object->error = $accountline !== null ? $accountline->error : "No account line / no bank identified found";
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

if ($id) {
	$result = $object->fetch($id);
	if ($result <= 0) {
		dol_print_error($db);
		exit;
	}
}

$title = $langs->trans("LT".$object->ltt)." - ".$langs->trans("Card");
$help_url = '';
llxHeader('', $title, $help_url);

if ($action == 'create') {
	$datev = dol_mktime(12, 0, 0, GETPOSTINT("datevmonth"), GETPOSTINT("datevday"), GETPOSTINT("datevyear"));
	$datep = dol_mktime(12, 0, 0, GETPOSTINT("datepmonth"), GETPOSTINT("datepday"), GETPOSTINT("datepyear"));

	print load_fiche_titre($langs->transcountry($lttype == 2 ? "newLT2Payment" : "newLT1Payment", $mysoc->country_code));

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" name="formlocaltax" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="localTaxType" value="'.$lttype.'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Date of payment
	print "<tr>";
	print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
	print $form->selectDate($datep, "datep", 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// End date of period
	print '<tr><td class="fieldrequired">'.$form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")).'</td><td>';
	print $form->selectDate($datev, "datev", 0, 0, 0, 'add', 1, 1);
	print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" class="minwidth200" value="'.(GETPOSTISSET("label") ? GETPOST("label", '', 2) : $langs->transcountry(($lttype == 2 ? "LT2Payment" : "LT1Payment"), $mysoc->country_code)).'"></td></tr>';

	// Amount
	print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value="'.GETPOST("amount").'"></td></tr>';

	if (isModEnabled("bank")) {
		// Type payment
		print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
		print $form->select_types_paiements(GETPOST("paiementtype"), "paiementtype", '', 0, 1, 0, 0, 1, 'maxwidth500 widthcentpercentminusx', 1);
		print "</td>\n";
		print "</tr>";

		// Bank account
		print '<tr><td class="fieldrequired" id="label_fk_account">'.$langs->trans("BankAccount").'</td><td>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		$form->select_comptes(GETPOSTINT("accountid"), "accountid", 0, "courant=1", 2, '', 0, 'maxwidth500 widthcentpercentminusx'); // Affiche liste des comptes courant
		print '</td></tr>';

		// Number
		print '<tr><td>'.$langs->trans('Numero');
		print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
		print '<td><input name="num_payment" type="text" value="'.GETPOST("num_payment").'"></td></tr>'."\n";
	}

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}


// View mode
if ($id) {
	$h = 0;
	$head = array();
	$head[$h][0] = DOL_URL_ROOT.'/compta/localtax/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	print dol_get_fiche_head($head, 'card', $langs->transcountry("LT".$object->ltt, $mysoc->country_code), -1, 'payment');

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/localtax/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

	print "<tr>";
	print '<td class="titlefield">'.$langs->trans("Ref").'</td><td>';
	print $object->ref;
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td>';
	print dol_print_date($object->datep, 'day');
	print '</td></tr>';

	print '<tr><td>'.$form->textwithpicto($langs->trans("PeriodEndDate"), $langs->trans("LastDayTaxIsRelatedTo")).'</td><td>';
	print dol_print_date($object->datev, 'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($object->amount).'</td></tr>';

	if (isModEnabled("bank")) {
		if ($object->fk_account > 0) {
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
	 * Action bar
	 */
	print "<div class=\"tabsAction\">\n";
	if ($object->rappro == 0) {
		print '<a class="butActionDelete" href="card.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a>';
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("LinkedToAConcialitedTransaction").'">'.$langs->trans("Delete").'</a>';
	}
	print "</div>";
}

// End of page
llxFooter();
$db->close();
