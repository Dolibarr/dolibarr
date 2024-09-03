<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2013  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/compta/prelevement/line.php
 *	\ingroup    prelevement
 *	\brief      card of withdraw line
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadlangs(array('banks', 'categories', 'bills', 'companies', 'withdrawals'));

// Get supervariables
$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$socid = GETPOSTINT('socid');

$type = GETPOST('type', 'aZ09');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortorder = GETPOST('sortorder', 'aZ09comma');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if ($sortorder == "") {
	$sortorder = "DESC";
}
if ($sortfield == "") {
	$sortfield = "pl.fk_soc";
}


if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}

if ($type == 'bank-transfer') {
	$permissiontoadd = $user->hasRight('paymentbybanktransfer', 'create');
} else {
	$permissiontoadd = $user->hasRight('prelevement', 'bons', 'creer');
}

$error = 0;


/*
 * Actions
 */

if ($action == 'confirm_rejet' && $permissiontoadd) {
	if (GETPOST("confirm") == 'yes') {
		$datarej = null;
		if (GETPOSTINT('remonth')) {
			$daterej = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
		}

		if (empty($daterej)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		} elseif ($daterej > dol_now()) {
			$error++;
			$langs->load("error");
			setEventMessages($langs->transnoentities("ErrorDateMustBeBeforeToday"), null, 'errors');
		}

		if (GETPOST('motif', 'alpha') == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefusedReason")), null, 'errors');
		}

		if (!$error) {
			$lipre = new LignePrelevement($db);

			if ($lipre->fetch($id) == 0) {
				$rej = new RejetPrelevement($db, $user, $type);

				$result = $rej->create($user, $id, GETPOSTINT('motif'), $daterej, $lipre->bon_rowid, GETPOSTINT('facturer'));

				if ($result > 0) {
					header("Location: line.php?id=".urlencode((string) ($id)).'&type='.urlencode((string) ($type)));
					exit;
				}
			}
		} else {
			$action = "rejet";
		}
	} else {
		header("Location: line.php?id=".urlencode((string) ($id)).'&type='.urlencode((string) ($type)));
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);

if ($type == 'bank-transfer') {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	$invoicestatic = new FactureFournisseur($db);
} else {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$invoicestatic = new Facture($db);
}

$title = $langs->trans("WithdrawalsLine");
if ($type == 'bank-transfer') {
	$title = $langs->trans("CreditTransferLine");
}

llxHeader('', $title);

$head = array();

$h = 0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/line.php?id='.((int) $id).'&type='.urlencode($type);
$head[$h][1] = $title;
$hselected = $h;
$h++;

if ($id) {
	$lipre = new LignePrelevement($db);
	$bon = null;

	if ($lipre->fetch($id) >= 0) {
		$bon = new BonPrelevement($db);
		$bon->fetch($lipre->bon_rowid);

		print dol_get_fiche_head($head, $hselected, $title, -1, 'payment');

		print '<table class="border centpercent tableforfield">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
		print $id.'</td></tr>';

		print '<tr><td class="titlefield">'.$langs->trans("WithdrawalsReceipts").'</td><td>';
		print $bon->getNomUrl(1).'</td></tr>';

		print '<tr><td>'.$langs->trans("Date").'</td><td>'.dol_print_date($bon->datec, 'day').'</td></tr>';

		print '<tr><td>'.$langs->trans("Amount").'</td><td><span class="amount">'.price($lipre->amount).'</span></td></tr>';

		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$lipre->LibStatut($lipre->statut, 1).'</td></tr>';

		if ($lipre->statut == 3) {
			$rej = new RejetPrelevement($db, $user, $type);
			$resf = $rej->fetch($lipre->id);
			if ($resf == 0) {
				print '<tr><td>'.$langs->trans("RefusedReason").'</td><td>'.$rej->motif.'</td></tr>';

				print '<tr><td>'.$langs->trans("RefusedData").'</td><td>';
				if ($rej->date_rejet == 0) {
					/* Historique pour certaines install */
					print $langs->trans("Unknown");
				} else {
					print dol_print_date($rej->date_rejet, 'day');
				}
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("RefusedInvoicing").'</td><td>'.$rej->invoicing.'</td></tr>';
			} else {
				print '<tr><td>'.$resf.'</td></tr>';
			}
		}

		print '</table>';
		print dol_get_fiche_end();
	} else {
		dol_print_error($db);
	}

	// Form to record a reject
	if ($action == 'rejet' && $user->hasRight('prelevement', 'bons', 'credit')) {
		$soc = new Societe($db);
		$soc->fetch($lipre->socid);

		$rej = new RejetPrelevement($db, $user, $type);

		print '<form name="confirm_rejet" method="post" action="'.DOL_URL_ROOT.'/compta/prelevement/line.php?id='.$id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="confirm_rejet">';
		print '<input type="hidden" name="type" value="'.$type.'">';

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("WithdrawalRefused").'</td>';
		print '<td></td>';
		print '</tr>';

		//Select yes/no
		print '<tr><td class="valid">'.$langs->trans("WithdrawalRefusedConfirm").' '.$soc->name.' ?</td>';
		print '<td class="valid">';
		print $form->selectyesno("confirm", 1, 0);
		print '</td></tr>';

		//Date
		print '<tr><td class="fieldrequired valid">'.$langs->trans("RefusedData").'</td>';
		print '<td class="valid">';
		print $form->selectDate('', '', 0, 0, 0, "confirm_rejet");
		print '</td></tr>';

		//Reason
		print '<tr><td class="fieldrequired valid">'.$langs->trans("RefusedReason").'</td>';
		print '<td class="valid">';
		print $form->selectarray("motif", $rej->motifs, GETPOSTISSET('motif') ? GETPOSTINT('motif') : '');
		print '</td></tr>';

		//Facturer
		print '<tr><td class="fieldrequired valid">';
		print $form->textwithpicto($langs->trans("RefusedInvoicing"), $langs->trans("DirectDebitRefusedInvoicingDesc"));
		print '</td>';
		print '<td class="valid">';
		print $form->selectarray("facturer", $rej->labelsofinvoicing, GETPOSTISSET('facturer') ? GETPOSTINT('facturer') : '', 0);
		print '</td></tr>';

		print '</table>';
		print '</div>';

		//Confirm Button
		print '<div class="center"><input type="submit" class="button button-save" value='.$langs->trans("Confirm").'></div>';
		print '</form>';
	}

	/*
	 * Action bar
	 */
	print '<div class="tabsAction">';

	if ($action == '') {
		if (is_object($bon) && $bon->statut == BonPrelevement::STATUS_CREDITED) {
			if ($lipre->statut == 2) {
				if ($user->hasRight('prelevement', 'bons', 'credit')) {
					print '<a class="butActionDelete" href="line.php?action=rejet&type='.$type.'&id='.$lipre->id.'">'.$langs->trans("StandingOrderReject").'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("StandingOrderReject").'</a>';
				}
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotPossibleForThisStatusOfWithdrawReceiptORLine").'">'.$langs->trans("StandingOrderReject").'</a>';
		}
	}

	print '</div>';

	/*
	 * List of invoices
	 */
	$sql = "SELECT pf.rowid";
	$sql .= " ,f.rowid as facid, f.ref as ref, f.total_ttc, f.paye, f.fk_statut";
	$sql .= " , s.rowid as socid, s.nom as name";

	$sqlfields = $sql; // $sql fields to remove for count total

	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
	$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
	$sql .= " , ".MAIN_DB_PREFIX."prelevement as pf";
	if ($type == 'bank-transfer') {
		$sql .= " , ".MAIN_DB_PREFIX."facture_fourn as f";
	} else {
		$sql .= " , ".MAIN_DB_PREFIX."facture as f";
	}
	$sql .= " , ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE pf.fk_prelevement_lignes = pl.rowid";
	$sql .= " AND pl.fk_prelevement_bons = p.rowid";
	$sql .= " AND f.fk_soc = s.rowid";
	if ($type == 'bank-transfer') {
		$sql .= " AND pf.fk_facture_fourn = f.rowid";
	} else {
		$sql .= " AND pf.fk_facture = f.rowid";
	}
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	$sql .= " AND pl.rowid = ".((int) $id);
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}

	// Count total nb of records
	$nbtotalofrecords = '';
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		/* The fast and low memory method to get and count full list converts the sql into a sql count */
		$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
		$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
		$resql = $db->query($sqlforcount);
		if ($resql) {
			$objforcount = $db->fetch_object($resql);
			$nbtotalofrecords = $objforcount->nbtotalofrecords;
		} else {
			dol_print_error($db);
		}

		if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
			$page = 0;
			$offset = 0;
		}
		$db->free($resql);
	}

	$result = $db->query($sql);

	$sql .= $db->order($sortfield, $sortorder);
	$sql .= $db->plimit($conf->liste_limit + 1, $offset);

	$result = $db->query($sql);

	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;

		$urladd = "&id=".urlencode((string) ($id));
		$title = $langs->trans("Bills");
		if ($type == 'bank-transfer') {
			$title = $langs->trans("SupplierInvoices");
		}

		print_barre_liste($title, $page, "factures.php", $urladd, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '');

		print"\n<!-- debut table -->\n";
		print '<table class="noborder" width="100%" cellpadding="4">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Invoice").'</td>';
		print '<td>'.$langs->trans("ThirdParty").'</td>';
		print '<td class="right">'.$langs->trans("Amount").'</td><td class="right">'.$langs->trans("Status").'</td>';
		print '</tr>';

		$total = 0;

		while ($i < min($num, $conf->liste_limit)) {
			$obj = $db->fetch_object($result);

			print '<tr class="oddeven"><td>';

			print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$obj->facid.'">';
			print img_object($langs->trans("ShowBill"), "bill");
			print '</a>&nbsp;';

			if ($type == 'bank-transfer') {
				print '<a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$obj->facid.'">'.$obj->ref."</a></td>\n";
			} else {
				print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$obj->facid.'">'.$obj->ref."</a></td>\n";
			}

			if ($type == 'bank-transfer') {
				print '<td><a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$obj->socid.'">';
			} else {
				print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">';
			}
			print img_object($langs->trans("ShowCompany"), "company").' '.$obj->name."</a></td>\n";

			print '<td class="right"><span class="amount">'.price($obj->total_ttc)."</span></td>\n";

			print '<td class="right">';
			$invoicestatic->fetch($obj->facid);
			print $invoicestatic->getLibStatut(5);
			print "</td>\n";

			print "</tr>\n";

			$i++;
		}

		print "</table>";

		$db->free($result);
	} else {
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
