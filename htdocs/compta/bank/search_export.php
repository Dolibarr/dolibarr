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
 * \file htdocs/compta/bank/search_export.php
 * \ingroup banque
 * \export all banks transactions
 */
require ('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';



$langs->load("banks");
$langs->load("compta");
$langs->load("trips");


$action = GETPOST('action', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque');

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
	header('Content-Disposition: attachment; filename=banks_export_'.$today.'.csv;');
	header('Content-Transfer-Encoding: binary');
}


$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$paymentexpensereport = new PaymentExpenseReport($db);




//open file pointer to standard output
$fp = fopen('php://output', 'w');

//add BOM to fix UTF-8 in Excel
fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
if ($fp)
{

	$separator = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	// Ligne de titre tableau des ecritures
	$header_array = array(
			html_entity_decode($langs->trans("Ref")),
			html_entity_decode($langs->trans("DateOperationShort")),
			html_entity_decode($langs->trans("Value")),
			html_entity_decode($langs->trans("InvoiceRef")),
			html_entity_decode($langs->trans("Type")),
			html_entity_decode($langs->trans("Numero")),
			html_entity_decode($langs->trans("Description")),
			html_entity_decode($langs->trans("ThirdParty")),
			html_entity_decode($langs->trans("Debit")),
			html_entity_decode($langs->trans("Credit")),
			html_entity_decode($langs->trans("Account"))
	);

	fputcsv($fp, $header_array, $separator);


	$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq,";
	$sql.= " b.fk_account, b.fk_type,";
	$sql.= " ba.rowid as bankid, ba.ref as bankref,";
	$sql.= " bu.url_id,";
	$sql.= " s.nom, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
	$sql.= " FROM ";
	$sql.= " ".MAIN_DB_PREFIX."bank_account as ba,";
	$sql.= " ".MAIN_DB_PREFIX."bank as b";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND type = 'company'";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
	$sql.= " WHERE b.fk_account = ba.rowid";
	$sql.= " AND ba.entity IN (".getEntity('bank_account', 1).")";



	$result = $db->query($sql);
	if ($result) {

		$var = true;
		$num = $db->num_rows($result);
		$i = 0;
		$total = $sous_total;
		$total_deb = 0;
		$total_cred = 0;

		while ( $i < $num ) {
			$objp = $db->fetch_object($result);
			$total = price2num($total + $objp->amount, 'MT');

			$var = ! $var;

			$c_ref = $objp->rowid;
			$c_do = dol_print_date($db->jdate($objp->do),"day");
			$c_dv = dol_print_date($db->jdate($objp->dv),"day");

			// Invoices
			$links = $object->get_url($objp->rowid);
			$invoice = '';
	        foreach ( $links as $key => $val ) {
	        	if ($links[$key]['type'] == 'payment') {
	        		$invoice .= ' '.$paymentstatic->getInvoiceUrl(3,$objp->rowid);
	        	} elseif ($links[$key]['type'] == 'payment_supplier') {
	        		$invoice .= ' '.$paymentsupplierstatic->getInvoiceUrl(3,$objp->rowid);
	        	} elseif ($links[$key]['type'] == 'payment_expensereport') {     
              $invoice .= ' '.$paymentexpensereport->getInvoiceUrl(3,$objp->rowid);
            }
	        }
			$c_invoice = html_entity_decode($invoice);

			// Payment
			$labeltype=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$langs->getLabelFromKey($db,$objp->fk_type,'c_paiement','code','libelle');
			if ($labeltype == 'SOLD') $c_labeltype = '&nbsp;'; //$langs->trans("InitialBankBalance");
			else $c_labeltype = $labeltype;



			// Num editable
			$c_num = html_entity_decode(($objp->num_chq?$objp->num_chq:""));



			// Description
			$description = '';
			$reg=array();
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthee on tente recherche de traduction
			if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) $description = $langs->trans($reg[1]);
			else $description = dol_trunc($objp->label,40);
			$c_description = html_entity_decode($description);


			// Add third party column
			$third_party = '';
			if ($objp->url_id) {
				$third_party = $objp->nom;
			}
			else {
				$third_party = '&nbsp;';
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

			// Bank Ref
			$c_bankref = html_entity_decode($objp->bankref);



			$content_array = array($c_ref,
					$c_do,
					$c_dv,
					$c_invoice,
					$c_labeltype,
					$c_num,
					$c_description,
					$c_third_party,
					$c_amount_deb,
					$c_amount_cred,
					$c_bankref);

			fputcsv($fp, $content_array, $separator);


			$i ++;
		}

		// print subtotal
		$total_array = array('',
					'',
					'',
					'',
					'',
					'',
					'',
					$langs->trans("Total"),
					price($total_deb * - 1),
					price($total_cred),
					price($total_cred - ($total_deb * - 1)) );

		fputcsv($fp, $total_array, $separator);

		$db->free($result);
	}
}

fclose($fp);
$db->close();
