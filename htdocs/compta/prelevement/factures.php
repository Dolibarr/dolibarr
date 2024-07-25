<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 *     \file       htdocs/compta/prelevement/factures.php
 *     \ingroup    prelevement
 *     \brief      Page list of invoice paid by direct debit or credit transfer
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'bills', 'companies', 'withdrawals', 'salaries', 'suppliers'));

// Get supervariables
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$userid = GETPOSTINT('userid');
$type = GETPOST('type', 'aZ09');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'p.ref';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

$object = new BonPrelevement($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals

$hookmanager->initHooks(array('directdebitprevcard', 'globalcard', 'directdebitprevlist'));

// Security check
if ($user->socid > 0) {
	accessforbidden();
}

$type = $object->type;
$salaryBonPl = $object->checkIfSalaryBonPrelevement();
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}


/*
 * View
 */

$form = new Form($db);
$invoicetmp = new Facture($db);
$thirdpartytmp = new Societe($db);

llxHeader('', $langs->trans("WithdrawalsReceipts"));

if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
	if (empty($object->id)) {
		$langs->load('errors');
		echo '<div class="error">'.$langs->trans("ErrorRecordNotFound").'</div>';
		llxFooter();
		exit;
	}

	$head = prelevement_prepare_head($object);

	print dol_get_fiche_head($head, 'invoices', $langs->trans("WithdrawalsReceipts"), -1, 'payment');

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/orders_list.php?restore_lastsearch_values=1'.($object->type != 'bank-transfer' ? '' : '&type=bank-transfer').'">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	//print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td>'.$object->getNomUrl(1).'</td></tr>';
	print '<tr><td class="titlefieldcreate">'.$langs->trans("Date").'</td><td>'.dol_print_date($object->datec, 'day').'</td></tr>';
	print '<tr><td>'.$langs->trans("Amount").'</td><td><span class="amount">'.price($object->amount).'</span></td></tr>';

	if (!empty($object->date_trans)) {
		$muser = new User($db);
		$muser->fetch($object->user_trans);

		print '<tr><td>'.$langs->trans("TransData").'</td><td>';
		print dol_print_date($object->date_trans, 'day');
		print ' &nbsp; <span class="opacitymedium">'.$langs->trans("By").'</span> '.$muser->getNomUrl(-1).'</td></tr>';
		print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
		print $object->methodes_trans[$object->method_trans];
		print '</td></tr>';
	}
	if (!empty($object->date_credit)) {
		print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
		print dol_print_date($object->date_credit, 'day');
		print '</td></tr>';
	}

	print '</table>';

	print '<br>';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Get bank account for the payment
	$acc = new Account($db);
	$fk_bank_account = $object->fk_bank_account;
	if (empty($fk_bank_account)) {
		$fk_bank_account = ($object->type == 'bank-transfer' ? getDolGlobalInt('PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT') : getDolGlobalInt('PRELEVEMENT_ID_BANKACCOUNT'));
	}
	if ($fk_bank_account > 0) {
		$result = $acc->fetch($fk_bank_account);
	}

	$labelofbankfield = "BankToReceiveWithdraw";
	if ($object->type == 'bank-transfer') {
		$labelofbankfield = 'BankToPayCreditTransfer';
	}

	print '<tr><td class="titlefieldcreate">';
	print $form->textwithpicto($langs->trans("BankAccount"), $langs->trans($labelofbankfield));
	print '</td>';

	print '<td>';
	if ($acc->id > 0) {
		print $acc->getNomUrl(1);
	}
	print '</td>';
	print '</tr>';

	$modulepart = 'prelevement';
	if ($object->type == 'bank-transfer') {
		$modulepart = 'paymentbybanktransfer';
	}

	print '<tr><td class="titlefieldcreate">';
	$labelfororderfield = 'WithdrawalFile';
	if ($object->type == 'bank-transfer') {
		$labelfororderfield = 'CreditTransferFile';
	}
	print $langs->trans($labelfororderfield).'</td><td>';

	if (isModEnabled('multicompany')) {
		$labelentity = $conf->entity;
		$relativepath = 'receipts/'.$object->ref.'-'.$labelentity.'.xml';

		if ($type != 'bank-transfer') {
			$dir = $conf->prelevement->dir_output;
		} else {
			$dir = $conf->paymentbybanktransfer->dir_output;
		}
		if (!dol_is_file($dir.'/'.$relativepath)) {	// For backward compatibility
			$relativepath = 'receipts/'.$object->ref.'.xml';
		}
	} else {
		$relativepath = 'receipts/'.$object->ref.'.xml';
	}

	print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">'.$relativepath;
	print img_picto('', 'download', 'class="paddingleft"');
	print '</a>';
	print '</td></tr></table>';

	print '</div>';

	print dol_get_fiche_end();
}


// List of invoices or salaries
if ($salaryBonPl) {
	$sql = "SELECT pf.rowid, p.type,";
	$sql .= " f.rowid as salaryid, f.ref as ref, f.amount,";
	$sql .= " u.rowid as userid, u.firstname, u.lastname, pl.statut as status, pl.amount as amount_requested";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."prelevement_lignes as pl ON pl.fk_prelevement_bons = p.rowid";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."prelevement as pf ON pf.fk_prelevement_lignes = pl.rowid";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."salary as f ON f.rowid = pf.fk_salary AND f.entity IN (".getEntity('salary').")";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON f.fk_user = u.rowid";
	$sql .= " WHERE 1 = 1";
	if ($object->id > 0) {
		$sql .= " AND p.rowid = ".((int) $object->id);
	}
	if ($userid > 0) {
		$sql .= " AND u.rowid = ".((int) $userid);
	}
} else {
	$sql = "SELECT pf.rowid, p.type,";
	$sql .= " f.rowid as facid, f.ref as ref, f.total_ttc,";
	if ($object->type == 'bank-transfer') {
		$sql .= " f.ref_supplier,";
	}
	$sql .= " s.rowid as socid, s.nom as name, pl.statut as status, pl.amount as amount_requested";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p,";
	$sql .= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
	$sql .= " ".MAIN_DB_PREFIX."prelevement as pf";
	if ($object->type != 'bank-transfer') {
		$sql .= ", ".MAIN_DB_PREFIX."facture as f";
	} else {
		$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
	}
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE pf.fk_prelevement_lignes = pl.rowid";
	$sql .= " AND pl.fk_prelevement_bons = p.rowid";
	$sql .= " AND f.fk_soc = s.rowid";
	if ($object->type != 'bank-transfer') {
		$sql .= " AND pf.fk_facture = f.rowid";
	} else {
		$sql .= " AND pf.fk_facture_fourn = f.rowid";
	}
	if ($object->type != 'bank-transfer') {
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
	} else {
		$sql .= " AND f.entity IN (".getEntity('supplier_invoice').")";
	}
	if ($object->id > 0) {
		$sql .= " AND p.rowid = ".((int) $object->id);
	}
	if ($socid > 0) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= $db->order($sortfield, $sortorder);
}
// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$param = "&id=".((int) $id);
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="id" value="'.$id.'">';

	$massactionbutton = '';
	$title = ($salaryBonPl ? $langs->trans("Salaries") : ($object->type == 'bank-transfer' ? $langs->trans("SupplierInvoices") : $langs->trans("Invoices")));

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit);

	print"\n<!-- debut table -->\n";
	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre(($salaryBonPl ? "Salary" : "Bill"), $_SERVER["PHP_SELF"], "p.ref", '', $param, '', $sortfield, $sortorder);
	if ($object->type == 'bank-transfer' && !$salaryBonPl) {
		print_liste_field_titre("RefSupplierShort", $_SERVER["PHP_SELF"], "f.ref_supplier", '', $param, '', $sortfield, $sortorder);
	}
	print_liste_field_titre(($salaryBonPl ? "Employee" : "ThirdParty"), $_SERVER["PHP_SELF"], "s.nom", '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre(($salaryBonPl ? "AmountSalary" : "AmountInvoice"), $_SERVER["PHP_SELF"], "f.total_ttc", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("AmountRequested", $_SERVER["PHP_SELF"], "pl.amount", "", $param, 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", $param, 'align="center"', $sortfield, $sortorder);
	print "</tr>\n";

	$totalinvoices = 0;
	$totalamount_requested = 0;

	if ($salaryBonPl) {
		$salarytmp = new Salary($db);
		$usertmp = new User($db);
	} else {
		$invoicetmpcustomer = new Facture($db);
		$invoicetmpsupplier = new FactureFournisseur($db);
	}

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($resql);

		$itemurl = '';
		$partyurl = '';
		if ($salaryBonPl && ($salarytmp instanceof Salary) && ($usertmp instanceof User)) {
			$salarytmp->fetch($obj->salaryid);
			$usertmp->fetch($obj->userid);
			$itemurl = $salarytmp->getNomUrl(1);
			$partyurl = $usertmp->getNomUrl(1);
		} elseif ($invoicetmpcustomer instanceof Facture && $invoicetmpsupplier instanceof FactureFournisseur) {
			if ($obj->type == 'bank-transfer') {
				$invoicetmp = $invoicetmpsupplier;
			} else {
				$invoicetmp = $invoicetmpcustomer;
			}
			$invoicetmp->fetch($obj->facid);

			$thirdpartytmp->fetch($obj->socid);
			$itemurl = $invoicetmp->getNomUrl(1);
			$partyurl = $thirdpartytmp->getNomUrl(1);
		}

		print '<tr class="oddeven">';

		print '<td class="nowraponall">';
		print $itemurl;
		print "</td>\n";

		if ($object->type == 'bank-transfer' && !$salaryBonPl) {
			$labeltoshow = '';
			if ($invoicetmp instanceof Facture) {
				$labeltoshow = $invoicetmp->ref_supplier;
			}
			print '<td class="tdoverflowmax150" title="'.dolPrintHTMLForAttribute($labeltoshow).'">';
			if ($invoicetmp instanceof Facture) {
				print dol_escape_htmltag($invoicetmp->ref_supplier);
			}
			print "</td>\n";
		}

		print '<td class="tdoverflowmax125">';
		print $partyurl;
		print "</td>\n";

		// Amount of invoice
		print '<td class="right"><span class="amount">'.price(($salaryBonPl ? $obj->amount : $obj->total_ttc))."</span></td>\n";

		// Amount requested
		print '<td class="right"><span class="amount">'.price($obj->amount_requested)."</span></td>\n";

		// Status of requests
		print '<td class="center">';
		if ($obj->status == 0) {
			print $langs->trans("StatusWaiting");
		} elseif ($obj->status == 2) {
			if ($obj->type == 'bank-transfer') {
				print $langs->trans("StatusDebited");
			} else {
				print $langs->trans("StatusCredited");
			}
		} elseif ($obj->status == 3) {
			print '<span class="error">'.$langs->trans("StatusRefused").'</span>';
		}
		print "</td>";

		print "</tr>\n";

		$totalinvoices += $obj->total_ttc;
		$totalamount_requested += $obj->amount_requested;

		$i++;
	}

	if ($num > 0) {
		print '<tr class="liste_total">';
		print '<td>'.$langs->trans("Total").'</td>';
		if ($object->type == 'bank-transfer' && !$salaryBonPl) {
			print '<td>&nbsp;</td>';
		}
		print '<td>&nbsp;</td>';
		print '<td class="right">';
		print "</td>\n";
		print '<td class="right">';
		// If the page show all record (no pagination) and total does not match total of file, we show a warning. Should not happen.
		if (($nbtotalofrecords <= $num) && $totalamount_requested != $object->amount) {
			print img_warning("AmountOfFileDiffersFromSumOfInvoices");
		}
		print price($totalamount_requested);
		print "</td>\n";
		print '<td>&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";
	print '</div>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
