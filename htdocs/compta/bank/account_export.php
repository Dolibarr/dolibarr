<?php
/* Copyright (C) 2016 Neil Orley <neil.orley@oeris.fr>
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
 * \file htdocs/compta/bank/account_export.php
 * \ingroup banque
 * \export all bank transactions for a specific account
 */
require ('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';



$langs->load("banks");
$langs->load("compta");

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'banque', $fieldvalue, 'bank_account&bank_account', '', '', $fieldtype);

$object = new Account($db);


/*
 * Action
 */
if ($action == 'export') {
	// print the last line
	$now = dol_now();
	$today = dol_print_date($now, '%Y%m%d');
	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Content-Description: File Transfer');
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename=bank_account_export_'.$today.'.csv;');
	header('Content-Transfer-Encoding: binary');
}


$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);



if ($id > 0 || ! empty($ref)) {

	//open file pointer to standard output
	$fp = fopen('php://output', 'w');

	//add BOM to fix UTF-8 in Excel
	fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	if ($fp)
	{


		$result = $object->fetch($id, $ref);
		$separator = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

		// Load bank groups
		require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/bankcateg.class.php';
		$bankcateg = new BankCateg($db);
		$options = array();

		foreach ( $bankcateg->fetchAll() as $bankcategory ) {
			$options[$bankcategory->id] = $bankcategory->label;
		}



		// Ligne de titre tableau des ecritures
		$header_array = array(html_entity_decode($langs->trans("Date")),
				html_entity_decode($langs->trans("Value")),
				html_entity_decode($langs->trans("Type").'/'.$langs->trans("Type")),
				html_entity_decode($langs->trans("Description")).' '.html_entity_decode($langs->trans("InvoiceRef")),
				html_entity_decode($langs->trans("ThirdParty")),
				html_entity_decode($langs->trans("Debit")),
				html_entity_decode($langs->trans("Credit")),
				html_entity_decode($langs->trans("BankBalance")));

		fputcsv($fp, $header_array, $separator);


		$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
		$sql .= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_bordereau,";
		$sql .= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
		$sql .= " FROM " . MAIN_DB_PREFIX . "bank_account as ba";
		$sql .= ", " . MAIN_DB_PREFIX . "bank as b";
		$sql .= " WHERE b.fk_account=" . $object->id;
		$sql .= " AND b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (" . getEntity('bank_account', 1) . ")";
		$sql .= $db->order("b.datev, b.datec", "ASC"); // We add date of creation to have correct order when everything is done the same day


		$result = $db->query($sql);
		if ($result) {

			$var = true;
			$num = $db->num_rows($result);
			$i = 0;
			$total = $sous_total;
			$sep = - 1;
			$total_deb = 0;
			$total_cred = 0;

			while ( $i < $num ) {
				$objp = $db->fetch_object($result);
				$total = price2num($total + $objp->amount, 'MT');

				$var = ! $var;

				$c_do = dol_print_date($db->jdate($objp->do), "day");
				$c_dv = dol_print_date($db->jdate($objp->dv), "day");

				// Payment type
				$label = ($langs->trans("PaymentTypeShort" . $objp->fk_type) != "PaymentTypeShort" . $objp->fk_type) ? $langs->trans("PaymentTypeShort" . $objp->fk_type) : $objp->fk_type;
				if ($objp->fk_type == 'SOLD')
					$label = '&nbsp;';
				if ($objp->fk_type == 'CHQ' && $objp->fk_bordereau > 0) {
					dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
					$bordereaustatic = new RemiseCheque($db);
					$bordereaustatic->id = $objp->fk_bordereau;
					$label .= ' ' . $bordereaustatic->getNomUrl(2);
				}
				$c_label = html_entity_decode($label);

				// Num editable
				$c_label .= ($objp->num_chq ? ' #'.$objp->num_chq : "");



				// Description
				$description = '';
				// Show generic description
				if (preg_match('/^\((.*)\)$/i', $objp->label, $reg)) {
					// Generic description because between (). We show it after translating.
					$description .= $langs->trans($reg[1]);
				} else {
					$description .= dol_trunc($objp->label, 60);
				}

				// Add links to invoices after description for customers and suppliers only
				$links = $object->get_url($objp->rowid);
				foreach ( $links as $key => $val ) {
					if ($links[$key]['type'] == 'payment') {
						$paymentstatic->id = $objp->rowid;
						$description .= ' ' . $paymentstatic->getInvoiceUrl(3);
					} elseif ($links[$key]['type'] == 'payment_supplier') {
						$paymentsupplierstatic->id = $objp->rowid;
						$description .= ' ' . $paymentsupplierstatic->getInvoiceUrl(3);
					}
				}
				$c_description = html_entity_decode($description);


				// Add third party column
				$third_party = '';
				foreach ( $links as $key => $val ) {
					if (preg_match('/^\((.*)\)$/i', $links[$key]['label'])) {
						$third_party .= preg_replace('/^\((.*)\)$/i', '', $links[$key]['label']);
					} else {
						$third_party .= $links[$key]['label'].' ';
					}
				}
				$c_third_party = html_entity_decode($third_party);

				// Amount
				if ($objp->amount < 0) {
					$c_amount_deb = price($objp->amount * - 1);
					$total_deb += $objp->amount;
					$c_amount_cred = '';
				} else {
					$c_amount_cred = price($objp->amount);
					$total_cred += $objp->amount;
					$c_amount_deb = '';
				}

				// Balance
				if (! $mode_search) {
					if ($total >= 0) {
						$c_balance = price($total);
					} else {
						$c_balance = price($total);
					}
				} else {
					$c_balance = 'N/A';
				}


				$content_array = array($c_do,
						$c_dv,
						$c_label,
						$c_description,
						$c_third_party,
						$c_amount_deb,
						$c_amount_cred,
						$c_balance);

				fputcsv($fp, $content_array, $separator);


				$i ++;
			}

			// print subtotal
			$subtotal_array = array('',
					'',
					'',
					'',
					$langs->trans("SubTotal").' ' . $object->currency_code,
					price($total_deb * - 1),
					price($total_cred),
					'' );

			fputcsv($fp, $subtotal_array, $separator);

			//print the total
			$total_array = array('',
					'',
					'',
					'',
					$langs->trans("Total").' ' . $object->currency_code,
					'',
					'',
					price($sous_total + $total_cred - ($total_deb * - 1)) );

			fputcsv($fp, $total_array, $separator);

			// print the last line
			$last_line_array = array('',
					'',
					'',
					'',
					'',
					'',
					'',
					price($sous_total + $total_cred - ($total_deb * - 1)) );

			fputcsv($fp, $last_line_array, $separator);


			$db->free($result);
		}
	}

	fclose($fp);

}
$db->close();
