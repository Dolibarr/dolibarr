#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2013 Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file scripts/bank/export-bank-receipts.php
 * \ingroup bank
 * \brief Script file to export bank receipts into Excel files
 */
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';

// Global variables
$version = DOL_VERSION;
$error = 0;

/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

if (!isset($argv[3]) || !$argv[3]) {
	print "Usage: ".$script_file." bank_ref [bank_receipt_number|all] (csv|tsv|excel|excel2007) [lang=xx_XX]\n";
	exit(-1);
}
$bankref = $argv[1];
$num = $argv[2];
$model = $argv[3];
$newlangid = 'en_EN'; // To force a new lang id

$invoicestatic = new Facture($db);
$invoicesupplierstatic = new FactureFournisseur($db);
$societestatic = new Societe($db);
$chargestatic = new ChargeSociales($db);
$memberstatic = new Adherent($db);
$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$paymentsocialcontributionstatic = new PaymentSocialContribution($db);
$paymentvatstatic = new Tva($db);
$bankstatic = new Account($db);
$banklinestatic = new AccountLine($db);

// Parse parameters
foreach ($argv as $key => $value) {
	$found = false;

	// Define options
	if (preg_match('/^lang=/i', $value)) {
		$found = true;
		$valarray = explode('=', $value);
		$newlangid = $valarray[1];
		print 'Use language '.$newlangid.".\n";
	}
}
$outputlangs = $langs;
if (!empty($newlangid)) {
	if ($outputlangs->defaultlang != $newlangid) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlangid);
	}
}

// Load translation files required by the page
$outputlangs->loadLangs(array("main", "companies", "bills", "banks", "members", "compta"));

$acct = new Account($db);
$result = $acct->fetch('', $bankref);
if ($result <= 0) {
	print "Failed to find bank account with ref ".$bankref.".\n";
	exit(-1);
} else {
	print "Export for bank account ".$acct->ref." (".$acct->label.").\n";
}

// Creation de la classe d'export du model ExportXXX
$dir = DOL_DOCUMENT_ROOT."/core/modules/export/";
$file = "export_".$model.".modules.php";
$classname = "Export".$model;
if (!dol_is_file($dir.$file)) {
	print "No driver to export with format ".$model."\n";
	exit(-1);
}
require_once $dir.$file;
$objmodel = new $classname($db);

// Define target path
$dirname = $conf->bank->dir_temp;
$filename = 'export-bank-receipts-'.$bankref.'-'.$num.'.'.$objmodel->extension;

$array_fields = array(
	'bankreceipt' => $outputlangs->transnoentitiesnoconv("AccountStatementShort"),
	'bankaccount' => $outputlangs->transnoentitiesnoconv("BankAccount"),
	'dateop' => $outputlangs->transnoentitiesnoconv("DateOperationShort"),
	'dateval' => $outputlangs->transnoentitiesnoconv("DateValueShort"),
	'type' => $outputlangs->transnoentitiesnoconv("Type"),
	'description' => $outputlangs->transnoentitiesnoconv("Description"),
	'thirdparty' => $outputlangs->transnoentitiesnoconv("Tiers"),
	'accountelem' => $outputlangs->transnoentitiesnoconv("Piece"),
	'debit' => $outputlangs->transnoentitiesnoconv("Debit"),
	'credit' => $outputlangs->transnoentitiesnoconv("Credit"),
	'soldbefore' => $outputlangs->transnoentitiesnoconv("BankBalanceBefore"),
	'soldafter' => $outputlangs->transnoentitiesnoconv("BankBalanceAfter"),
	'comment' => $outputlangs->transnoentitiesnoconv("Comment")
);
$array_selected = array('bankreceipt' => 'bankreceipt', 'bankaccount' => 'bankaccount', 'dateop' => 'dateop', 'dateval' => 'dateval', 'type' => 'type', 'description' => 'description', 'thirdparty' => 'thirdparty', 'accountelem' => 'accountelem', 'debit' => 'debit', 'credit' => 'credit', 'soldbefore' => 'soldbefore', 'soldafter' => 'soldafter', 'comment' => 'comment');
$array_export_TypeFields = array('bankreceipt' => 'Text', 'bankaccount' => 'Text', 'dateop' => 'Date', 'dateval' => 'Date', 'type' => 'Text', 'description' => 'Text', 'thirdparty' => 'Text', 'accountelem' => 'Text', 'debit' => 'Number', 'credit' => 'Number', 'soldbefore' => 'Number', 'soldafter' => 'Number', 'comment' => 'Text');

// Build request to find records for a bank account/receipt
$listofnum = "";
if (!empty($num) && $num != "all") {
	$listofnum .= "'";
	$arraynum = explode(',', $num);
	foreach ($arraynum as $val) {
		if ($listofnum != "'")
			$listofnum .= "','";
		$listofnum .= $val;
	}
	$listofnum .= "'";
}
$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
$sql .= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type,";
$sql .= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= ", ".MAIN_DB_PREFIX."bank as b";
$sql .= " WHERE b.fk_account = ".$acct->id;
if ($listofnum)
	$sql .= " AND b.num_releve IN (".$listofnum.")";
if (!isset($num))
	$sql .= " OR b.num_releve is null";
$sql .= " AND b.fk_account = ba.rowid";
$sql .= $db->order("b.num_releve, b.datev, b.datec", "ASC"); // We add date of creation to have correct order when everything is done the same day
                                                             // print $sql;

$resql = $db->query($sql);
if ($resql) {
	$balancebefore = array();

	$numrows = $db->num_rows($resql);

	if ($numrows > 0) {
		// Open file
		print 'Open file '.$filename.' into directory '.$dirname."\n";
		dol_mkdir($dirname);
		$result = $objmodel->open_file($dirname."/".$filename, $outputlangs);

		if ($result < 0) {
			print 'Failed to create file '.$filename.' into dir '.$dirname.'.'."\n";
			return -1;
		}

		// Genere en-tete
		$objmodel->write_header($outputlangs);

		// Genere ligne de titre
		$objmodel->write_title($array_fields, $array_selected, $outputlangs, $array_export_TypeFields);
	}

	$i = 0;
	while ($i < $numrows) {
		$thirdparty = '';
		$accountelem = '';
		$comment = '';

		$objp = $db->fetch_object($resql);

		// Calculate start balance
		if (!isset($balancebefore[$objp->num_releve])) {
			print 'Calculate start balance for receipt '.$objp->num_releve."\n";

			$sql2 = "SELECT sum(b.amount) as amount";
			$sql2 .= " FROM ".MAIN_DB_PREFIX."bank as b";
			$sql2 .= " WHERE b.num_releve < '".$db->escape($objp->num_releve)."'";
			$sql2 .= " AND b.fk_account = ".$objp->bankid;
			$resql2 = $db->query($sql2);
			if ($resql2) {
				$obj2 = $db->fetch_object($resql2);
				$balancebefore[$objp->num_releve] = ($obj2->amount ? $obj2->amount : 0);
				$db->free($resql2);
			} else {
				dol_print_error($db);
				exit(-1);
			}

			$total = $balancebefore[$objp->num_releve];
		}

		$totalbefore = $total;
		$total = $total + $objp->amount;

		// Date operation
		$dateop = $db->jdate($objp->do);

		// Date de valeur
		$datevalue = $db->jdate($objp->dv);

		// Num cheque
		$numchq = ($objp->num_chq ? $objp->num_chq : '');

		// Libelle
		$reg = array();
		preg_match('/\((.+)\)/i', $objp->label, $reg); // Si texte entoure de parenthese on tente recherche de traduction
		if ($reg[1] && $langs->transnoentitiesnoconv($reg[1]) != $reg[1])
			$description = $langs->transnoentitiesnoconv($reg[1]);
		else
			$description = $objp->label;

		/*
		 * Ajout les liens (societe, company...)
		 */
		$links = $acct->get_url($objp->rowid);
		foreach ($links as $key => $val) {
			if ($links[$key]['type'] == 'payment') {
				$paymentstatic->fetch($links[$key]['url_id']);
				$tmparray = $paymentstatic->getBillsArray('');
				if (is_array($tmparray)) {
					foreach ($tmparray as $tmpkey => $tmpval) {
						$invoicestatic->fetch($tmpval);
						if ($accountelem) {
							$accountelem .= ', ';
                        }
						$accountelem .= $invoicestatic->ref;
					}
				}
			} elseif ($links[$key]['type'] == 'payment_supplier') {
				$paymentsupplierstatic->fetch($links[$key]['url_id']);
				$tmparray = $paymentsupplierstatic->getBillsArray('');
				if (is_array($tmparray)) {
					foreach ($tmparray as $tmpkey => $tmpval) {
						$invoicesupplierstatic->fetch($tmpval);
						if ($accountelem) {
							$accountelem .= ', ';
                        }
						$accountelem .= $invoicesupplierstatic->ref;
					}
				}
			} elseif ($links[$key]['type'] == 'payment_sc') {
				$paymentsocialcontributionstatic->fetch($links[$key]['url_id']);
				if ($accountelem) {
					$accountelem .= ', ';
                }
				$accountelem .= $langs->transnoentitiesnoconv("SocialContribution").' '.$paymentsocialcontributionstatic->ref;
			} elseif ($links[$key]['type'] == 'payment_vat') {
				$paymentvatstatic->fetch($links[$key]['url_id']);
				if ($accountelem) {
					$accountelem .= ', ';
                }
				$accountelem .= $langs->transnoentitiesnoconv("VATPayments").' '.$paymentvatstatic->ref;
			} elseif ($links[$key]['type'] == 'banktransfert') {
				$comment = $outputlangs->transnoentitiesnoconv("Transfer");
				if ($objp->amount > 0) {
					if ($comment) {
						$comment .= ' ';
                    }
					$banklinestatic->fetch($links[$key]['url_id']);
					$bankstatic->id = $banklinestatic->fk_account;
					$bankstatic->label = $banklinestatic->bank_account_label;
					$comment .= ' ('.$langs->transnoentitiesnoconv("from").' ';
					$comment .= $bankstatic->getNomUrl(1, 'transactions');
					$comment .= ' '.$langs->transnoentitiesnoconv("toward").' ';
					$bankstatic->id = $objp->bankid;
					$bankstatic->label = $objp->bankref;
					$comment .= $bankstatic->getNomUrl(1, '');
					$comment .= ')';
				} else {
					if ($comment) {
						$comment .= ' ';
                    }
					$bankstatic->id = $objp->bankid;
					$bankstatic->label = $objp->bankref;
					$comment .= ' ('.$langs->transnoentitiesnoconv("from").' ';
					$comment .= $bankstatic->getNomUrl(1, '');
					$comment .= ' '.$langs->transnoentitiesnoconv("toward").' ';
					$banklinestatic->fetch($links[$key]['url_id']);
					$bankstatic->id = $banklinestatic->fk_account;
					$bankstatic->label = $banklinestatic->bank_account_label;
					$comment .= $bankstatic->getNomUrl(1, 'transactions');
					$comment .= ')';
				}
			} elseif ($links[$key]['type'] == 'company') {
				if ($thirdparty) {
					$thirdparty .= ', ';
                }
				$thirdparty .= dol_trunc($links[$key]['label'], 24);
				$newline = 0;
			} elseif ($links[$key]['type'] == 'member') {
				if ($thirdparty) {
					$accountelem .= ', ';
                }
				$thirdparty .= $links[$key]['label'];
				$newline = 0;
			}
			/*
			 * elseif ($links[$key]['type']=='sc')
			 * {
			 * if ($accountelem) $accountelem.= ', ';
			 * //$accountelem.= '<a href="'.DOL_URL_ROOT.'/compta/sociales/card.php?id='.$links[$key]['url_id'].'">';
			 * //$accountelem.= img_object($langs->transnoentitiesnoconv('ShowBill'),'bill').' ';
			 * $accountelem.= $langs->transnoentitiesnoconv("SocialContribution");
			 * //$accountelem.= '</a>';
			 * $newline=0;
			 * }
			 * else
			 * {
			 * if ($accountelem) $accountelem.= ', ';
			 * //$accountelem.= '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
			 * $accountelem.= $links[$key]['label'];
			 * //$accountelem.= '</a>';
			 * $newline=0;
			 * }
			 */
		}

		$debit = $credit = '';
		if ($objp->amount < 0) {
			$totald = $totald + abs($objp->amount);
			$debit = price2num($objp->amount * - 1);
		} else {
			$totalc = $totalc + abs($objp->amount);
			$credit = price2num($objp->amount);
		}

		$i++;

		$rec = new stdClass();
		$rec->bankreceipt = $objp->num_releve;
		$rec->bankaccount = $objp->banklabel;
		$rec->dateop = dol_print_date($dateop, 'dayrfc');
		$rec->dateval = dol_print_date($datevalue, 'dayrfc');
		$rec->type = $objp->fk_type.' '.($objp->num_chq ? $objp->num_chq : '');
		$rec->description = $description;
		$rec->thirdparty = $thirdparty;
		$rec->accountelem = $accountelem;
		$rec->debit = $debit;
		$rec->credit = $credit;
		$rec->comment = $comment;
		$rec->soldbefore = price2num($totalbefore);
		$rec->soldafter = price2num($total);

		// end of special operation processing
		$objmodel->write_record($array_selected, $rec, $outputlangs, $array_export_TypeFields);
	}

	if ($numrows > 0) {
		print "Found ".$numrows." records for receipt ".$num."\n";

		// Genere en-tete
		$objmodel->write_footer($outputlangs);

		// Close file
		$objmodel->close_file();

		print 'File '.$filename.' was generated into dir '.$dirname.'.'."\n";

		$ret = 0;
	} else {
		print "No records found for receipt ".$num."\n";

		$ret = 0;
	}
} else {
	dol_print_error($db);
	$ret = - 1;
}

$db->close();

exit($ret);
