<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Teddy Andreotti			<125155@supinfo.com>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021       Charlene Benke          <charlene@patas-monkey.com>
 * Copyright (C) 2022       Udo Tamm				<dev@dolibit.de>
 * Copyright (C) 2023       Sylvain Legrand			<technique@infras.fr>
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
 *	\file       htdocs/fourn/facture/paiement.php
 *	\ingroup    fournisseur,facture
 *	\brief      Payment page for supplier/purchase invoices
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'banks', 'compta'));

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$facid = GETPOSTINT('facid');
$socid = GETPOSTINT('socid');
$accountid = GETPOSTINT('accountid');
$day = GETPOSTINT('day');
$month = GETPOSTINT('month');
$year = GETPOSTINT('year');

$search_ref = GETPOST('search_ref', 'alpha');
$search_account = GETPOST('search_account', 'alpha');
$search_paymenttype = GETPOST('search_paymenttype');
$search_amount = GETPOST('search_amount', 'alpha'); // alpha because we must be able to search on "< x"
$search_company = GETPOST('search_company', 'alpha');
$search_payment_num = GETPOST('search_payment_num', 'alpha');

$limit = GETPOSTINT('limit') ? GETPOST('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "p.rowid";
}

$amounts = array();
$amountsresttopay = array();
$addwarning = 0;

$multicurrency_amounts = array();
$multicurrency_amountsresttopay = array();

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}

$object = new PaiementFourn($db);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('paymentsupplierlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$arrayfields = array();



/*
 * Actions
 */

if ($cancel) {
	if (!empty($backtopageforcancel)) {
		header("Location: ".$backtopageforcancel);
		exit;
	} elseif (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
	header("Location: ".DOL_URL_ROOT.'/fourn/facture/list.php');
	exit;
}

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_ref = "";
	$search_account = "";
	$search_amount = "";
	$search_paymenttype = "";
	$search_payment_num = "";
	$search_company = "";
	$day = '';
	$year = '';
	$month = '';
	$search_array_options = array();
}

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'add_paiement' || ($action == 'confirm_paiement' && $confirm == 'yes')) {
		$error = 0;

		$datepaye = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
		$paiement_id = 0;
		$totalpayment = 0;
		$atleastonepaymentnotnull = 0;
		$multicurrency_totalpayment = 0;
		$formquestion = array();

		// Generate payment array and check if there is payment higher than invoice and payment date before invoice date
		$tmpinvoice = new FactureFournisseur($db);
		foreach ($_POST as $key => $value) {
			if (substr($key, 0, 7) == 'amount_') {
				$cursorfacid = substr($key, 7);
				$amounts[$cursorfacid] = price2num(GETPOST($key));
				if (!empty($amounts[$cursorfacid])) {
					$atleastonepaymentnotnull++;
					if (is_numeric($amounts[$cursorfacid])) {
						$totalpayment += (float) $amounts[$cursorfacid];
					} else {
						setEventMessages($langs->transnoentities("InputValueIsNotAnNumber", GETPOST($key)), null, 'warnings');
					}
				}
				$result = $tmpinvoice->fetch($cursorfacid);
				if ($result <= 0) {
					dol_print_error($db);
				}
				$amountsresttopay[$cursorfacid] = price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement());
				if ($amounts[$cursorfacid]) {
					// Check amount
					if ($amounts[$cursorfacid] && (abs((float) $amounts[$cursorfacid]) > abs((float) $amountsresttopay[$cursorfacid]))) {
						$addwarning = 1;
						$formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPaySupplier")).' '.$langs->trans("HelpPaymentHigherThanReminderToPaySupplier");
					}
					// Check date
					if ($datepaye && ($datepaye < $tmpinvoice->date)) {
						$langs->load("errors");
						//$error++;
						setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
					}
				}

				$formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => GETPOST($key));
			} elseif (substr($key, 0, 21) == 'multicurrency_amount_') {
				$cursorfacid = substr($key, 21);
				$multicurrency_amounts[$cursorfacid] = (GETPOST($key) ? price2num(GETPOST($key)) : 0);
				$multicurrency_totalpayment += $multicurrency_amounts[$cursorfacid];
				if (!empty($multicurrency_amounts[$cursorfacid])) {
					$atleastonepaymentnotnull++;
				}
				$result = $tmpinvoice->fetch($cursorfacid);
				if ($result <= 0) {
					dol_print_error($db);
				}
				$multicurrency_amountsresttopay[$cursorfacid] = price2num($tmpinvoice->multicurrency_total_ttc - $tmpinvoice->getSommePaiement(1));
				if ($multicurrency_amounts[$cursorfacid]) {
					// Check amount
					if ($multicurrency_amounts[$cursorfacid] && (abs((float) $multicurrency_amounts[$cursorfacid]) > abs((float) $multicurrency_amountsresttopay[$cursorfacid]))) {
						$addwarning = 1;
						$formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPaySupplier")).' '.$langs->trans("HelpPaymentHigherThanReminderToPaySupplier");
					}
					// Check date
					if ($datepaye && ($datepaye < $tmpinvoice->date)) {
						$langs->load("errors");
						//$error++;
						setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye, 'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
					}
				}

				$formquestion[$i++] = array('type' => 'hidden', 'name' => $key, 'value' => GETPOSTINT($key));
			}
		}

		// Check parameters
		if (GETPOST('paiementid') <= 0) {
			setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('PaymentMode')), null, 'errors');
			$error++;
		}

		if (isModEnabled("bank")) {
			// If bank module is on, account is required to enter a payment
			if (GETPOST('accountid') <= 0) {
				setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('AccountToCredit')), null, 'errors');
				$error++;
			}
		}

		if (empty($totalpayment) && empty($multicurrency_totalpayment) && empty($atleastonepaymentnotnull)) {
			setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->trans('PaymentAmount')), null, 'errors');
			$error++;
		}

		if (empty($datepaye)) {
			setEventMessages($langs->transnoentities('ErrorFieldRequired', $langs->transnoentities('Date')), null, 'errors');
			$error++;
		}

		// Check if payments in both currency
		if ($totalpayment > 0 && $multicurrency_totalpayment > 0) {
			setEventMessages($langs->transnoentities('ErrorPaymentInBothCurrency'), null, 'errors');
			$error++;
		}
	}

	/*
	 * Action add_paiement
	 */
	if ($action == 'add_paiement') {
		if ($error) {
			$action = 'create';
		}
		// All the next of this action is displayed at the page's bottom.
	}


	/*
	 * Action confirm_paiement
	 */
	if ($action == 'confirm_paiement' && $confirm == 'yes') {
		$error = 0;

		$datepaye = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));

		$multicurrency_code = array();
		$multicurrency_tx = array();

		// Clean parameters amount if payment is for a credit note
		foreach ($amounts as $key => $value) {	// How payment is dispatched
			$tmpinvoice = new FactureFournisseur($db);
			$tmpinvoice->fetch($key);
			if ($tmpinvoice->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
				$newvalue = price2num($value, 'MT');
				$amounts[$key] = - abs((float) $newvalue);
			}
			$multicurrency_code[$key] = $tmpinvoice->multicurrency_code;
			$multicurrency_tx[$key] = $tmpinvoice->multicurrency_tx;
		}

		foreach ($multicurrency_amounts as $key => $value) {	// How payment is dispatched
			$tmpinvoice = new FactureFournisseur($db);
			$tmpinvoice->fetch($key);
			if ($tmpinvoice->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
				$newvalue = price2num($value, 'MT');
				$multicurrency_amounts[$key] = - abs((float) $newvalue);
			}
			$multicurrency_code[$key] = $tmpinvoice->multicurrency_code;
			$multicurrency_tx[$key] = $tmpinvoice->multicurrency_tx;
		}

		//var_dump($amounts);
		//var_dump($multicurrency_amounts);
		//exit;

		if (!$error) {
			$db->begin();

			$thirdparty = new Societe($db);
			if ($socid > 0) {
				$thirdparty->fetch($socid);
			}

			// Creation of payment line
			$paiement = new PaiementFourn($db);
			$paiement->datepaye     = $datepaye;

			$correctedAmounts = [];
			foreach ($amounts as $key => $value) {
				$correctedAmounts[$key] = (float) $value;
			}

			$paiement->amounts      = $correctedAmounts; // Array of amounts
			$paiement->multicurrency_amounts = $multicurrency_amounts;
			$paiement->multicurrency_code = $multicurrency_code; // Array with all currency of payments dispatching
			$paiement->multicurrency_tx = $multicurrency_tx; // Array with all currency tx of payments dispatching
			$paiement->paiementid   = GETPOSTINT('paiementid');
			$paiement->num_payment  = GETPOST('num_paiement', 'alphanohtml');
			$paiement->note_private = GETPOST('comment', 'alpha');
			$paiement->fk_account   = GETPOSTINT('accountid');

			if (!$error) {
				// Create payment and update this->multicurrency_amounts if this->amounts filled or
				// this->amounts if this->multicurrency_amounts filled.
				// This also set ->amount and ->multicurrency_amount
				$paiement_id = $paiement->create($user, (GETPOST('closepaidinvoices') == 'on' ? 1 : 0), $thirdparty);
				if ($paiement_id < 0) {
					setEventMessages($paiement->error, $paiement->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$result = $paiement->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $accountid, GETPOST('chqemetteur'), GETPOST('chqbank'));
				if ($result < 0) {
					setEventMessages($paiement->error, $paiement->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$db->commit();

				// If payment dispatching on more than one invoice, we stay on summary page, otherwise go on invoice card
				$invoiceid = 0;
				foreach ($paiement->amounts as $key => $amount) {
					$facid = $key;
					if (is_numeric($amount) && $amount != 0) {
						if ($invoiceid != 0) {
							$invoiceid = -1; // There is more than one invoice paid by this payment
						} else {
							$invoiceid = $facid;
						}
					}
				}
				if ($invoiceid > 0) {
					$loc = DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$invoiceid;
				} else {
					$loc = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$paiement_id;
				}
				header('Location: '.$loc);
				exit;
			} else {
				$db->rollback();
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$supplierstatic = new Societe($db);
$invoicesupplierstatic = new FactureFournisseur($db);

llxHeader('', $langs->trans('ListPayment'));

if ($action == 'create' || $action == 'confirm_paiement' || $action == 'add_paiement') {
	$object = new FactureFournisseur($db);
	$result = $object->fetch($facid);

	$datefacture = dol_mktime(12, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
	$dateinvoice = ($datefacture == '' ? (!getDolGlobalString('MAIN_AUTOFILL_DATE') ? -1 : '') : $datefacture);

	$sql = 'SELECT s.nom as name, s.rowid as socid,';
	$sql .= ' f.rowid, f.ref, f.ref_supplier, f.total_ttc as total, f.fk_mode_reglement, f.fk_account';
	if (!$user->hasRight("societe", "client", "voir") && !$socid) {
		$sql .= ", sc.fk_soc, sc.fk_user ";
	}
	$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'facture_fourn as f';
	if (!$user->hasRight("societe", "client", "voir") && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= ' WHERE f.fk_soc = s.rowid';
	$sql .= ' AND f.rowid = '.((int) $facid);
	if (!$user->hasRight("societe", "client", "voir") && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			$obj = $db->fetch_object($resql);
			$total = $obj->total;

			print load_fiche_titre($langs->trans('DoPayment'));

			// Add realtime total information
			if (!empty($conf->use_javascript_ajax)) {
				print "\n".'<script type="text/javascript">';
				print '$(document).ready(function () {

						function _elemToJson(selector)
						{
							var subJson = {};
							$.map(selector.serializeArray(), function(n,i)
							{
								subJson[n["name"]] = n["value"];
							});

							return subJson;
						}
						function callForResult(imgId)
						{
							console.log("callForResult Calculate total of payment");
							var json = {};
							var form = $("#payment_form");

							json["invoice_type"] = $("#invoice_type").val();
            				json["amountPayment"] = $("#amountpayment").attr("value");
							json["amounts"] = _elemToJson(form.find("input.amount"));
							json["remains"] = _elemToJson(form.find("input.remain"));
							json["token"] = "'.currentToken().'";
							if (imgId != null) {
								json["imgClicked"] = imgId;
							}

							$.post("'.DOL_URL_ROOT.'/compta/ajaxpayment.php", json, function(data)
							{
								json = $.parseJSON(data);

								form.data(json);

								for (var key in json)
								{
									if (key == "result")	{
										if (json["makeRed"]) {
											$("#"+key).addClass("error");
										} else {
											$("#"+key).removeClass("error");
										}
										json[key]=json["label"]+" "+json[key];
										$("#"+key).text(json[key]);
									} else {console.log(key);
										form.find("input[name*=\""+key+"\"]").each(function() {
											$(this).attr("value", json[key]);
										});
									}
								}
							});
						}
						callForResult();
						$("#payment_form").find("input.amount").change(function() {
							callForResult();
						});
						$("#payment_form").find("input.amount").keyup(function() {
							callForResult();
						});
			';

				print '	});'."\n";

				//Add js for AutoFill
				print ' $(document).ready(function () {';
				print ' 	$(".AutoFillAmount").on(\'click touchstart\', function(){
							$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value")).trigger("change");
						});';
				print '	});'."\n";

				print '	</script>'."\n";
			}

			print '<form id="payment_form" name="addpaiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="add_paiement">';
			print '<input type="hidden" name="facid" value="'.$facid.'">';
			print '<input type="hidden" name="ref_supplier" value="'.$obj->ref_supplier.'">';
			print '<input type="hidden" name="socid" value="'.$obj->socid.'">';
			print '<input type="hidden" name="type" id="invoice_type" value="'.$object->type.'">';
			print '<input type="hidden" name="societe" value="'.$obj->name.'">';

			print dol_get_fiche_head(null);

			print '<table class="border centpercent">';

			print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans('Company').'</td><td>';
			$supplierstatic->id = $obj->socid;
			$supplierstatic->name = $obj->name;
			print $supplierstatic->getNomUrl(1, 'supplier');
			print '</td></tr>';

			print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
			// $object is default vendor invoice
			$adddateof = array(array('adddateof' => $object->date));
			$adddateof[] = array('adddateof' => $object->date_echeance, 'labeladddateof' => $langs->transnoentities('DateDue'));
			print $form->selectDate($dateinvoice, '', 0, 0, 0, "addpaiement", 1, 1, 0, '', '', $adddateof);
			print '</td></tr>';
			print '<tr><td class="fieldrequired">'.$langs->trans('PaymentMode').'</td><td>';
			$form->select_types_paiements(!GETPOST('paiementid') ? $obj->fk_mode_reglement : GETPOST('paiementid'), 'paiementid');
			print '</td>';
			if (isModEnabled("bank")) {
				print '<tr><td class="fieldrequired">'.$langs->trans('Account').'</td><td>';
				print img_picto('', 'bank_account', 'class="pictofixedwidth"');
				print $form->select_comptes(empty($accountid) ? $obj->fk_account : $accountid, 'accountid', 0, '', 2, '', 0, 'widthcentpercentminusx maxwidth500', 1);
				print '</td></tr>';
			} else {
				print '<tr><td>&nbsp;</td></tr>';
			}
			print '<tr><td>'.$langs->trans('Numero').'</td><td><input name="num_paiement" type="text" value="'.(!GETPOST('num_paiement') ? '' : GETPOST('num_paiement')).'"></td></tr>';
			print '<tr><td>'.$langs->trans('Comments').'</td>';
			print '<td class="tdtop">';
			print '<textarea name="comment" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.(!GETPOST('comment') ? '' : GETPOST('comment')).'</textarea></td></tr>';
			print '</table>';
			print dol_get_fiche_end();


			$parameters = array('facid' => $facid, 'ref' => $ref, 'objcanvas' => $objcanvas);
			$reshook = $hookmanager->executeHooks('paymentsupplierinvoices', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
			$error = $hookmanager->error;
			$errors = $hookmanager->errors;
			if (empty($reshook)) {
				/*
				 * All unpaid supplier invoices
				 */
				$sql = 'SELECT f.rowid as facid, f.ref, f.ref_supplier, f.type, f.total_ht, f.total_ttc,';
				$sql .= ' f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc,';
				$sql .= ' f.datef as df, f.date_lim_reglement as dlr,';
				$sql .= ' SUM(pf.amount) as am, SUM(pf.multicurrency_amount) as multicurrency_am';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
				$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
				$sql .= " WHERE f.entity = ".((int) $conf->entity);
				$sql .= ' AND f.fk_soc = '.((int) $object->socid);
				$sql .= ' AND f.paye = 0';
				$sql .= ' AND f.fk_statut = 1'; // Status=0 => unvalidated, Status=2 => canceled
				if ($object->type != FactureFournisseur::TYPE_CREDIT_NOTE) {
					$sql .= ' AND f.type IN (0,1,3,5)'; // Standard invoice, replacement, deposit, situation
				} else {
					$sql .= ' AND f.type = 2'; // If paying back a credit note, we show all credit notes
				}
				// Group by because we have a total
				$sql .= ' GROUP BY f.datef, f.ref, f.ref_supplier, f.rowid, f.type, f.total_ht, f.total_ttc,';
				$sql .= ' f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc,';
				$sql .= ' f.datef, f.date_lim_reglement';
				// Sort invoices by date and serial number: the older one comes first
				$sql .= ' ORDER BY f.datef ASC, f.ref ASC';

				$resql = $db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					if ($num > 0) {
						$i = 0;
						print '<br>';

						if (!empty($conf->use_javascript_ajax)) {
							//Add js for AutoFill
							print "\n".'<script type="text/javascript">';
							print ' $(document).ready(function () {';
							print ' 	$(".AutoFillAmount").on(\'click touchstart\', function(){
											$("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value"));
										});';
							print '	});'."\n";
							print '	</script>'."\n";
						}

						print '<div class="div-table-responsive-no-min">';
						print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

						print '<tr class="liste_titre">';
						print '<td>'.$langs->trans('Invoice').'</td>';
						print '<td>'.$langs->trans('RefSupplier').'</td>';
						print '<td class="center">'.$langs->trans('Date').'</td>';
						print '<td class="center">'.$langs->trans('DateMaxPayment').'</td>';
						if (isModEnabled("multicurrency")) {
							print '<td>'.$langs->trans('Currency').'</td>';
							print '<td class="right">'.$langs->trans('MulticurrencyAmountTTC').'</td>';
							print '<td class="right">'.$langs->trans('MulticurrencyAlreadyPaid').'</td>';
							print '<td class="right">'.$langs->trans('MulticurrencyRemainderToPay').'</td>';
							print '<td class="center">'.$langs->trans('MulticurrencyPaymentAmount').'</td>';
						}
						print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
						print '<td class="right">'.$langs->trans('AlreadyPaid').'</td>';
						print '<td class="right">'.$langs->trans('RemainderToPay').'</td>';
						print '<td class="center">'.$langs->trans('PaymentAmount').'</td>';
						print '</tr>';

						$total = 0;
						$total_ttc = 0;
						$totalrecu = 0;
						$totalrecucreditnote = 0;	// PHP Warning:  Undefined variable $totalrecucreditnote
						$totalrecudeposits = 0;		// PHP Warning:  Undefined variable $totalrecudeposits
						while ($i < $num) {
							$objp = $db->fetch_object($resql);

							$sign = 1;
							if ($objp->type == FactureFournisseur::TYPE_CREDIT_NOTE) {
								$sign = -1;
							}

							$invoice = new FactureFournisseur($db);
							$invoice->fetch($objp->facid);

							$invoicesupplierstatic->ref = $objp->ref;
							$invoicesupplierstatic->id = $objp->facid;

							$paiement = $invoice->getSommePaiement();
							$creditnotes = $invoice->getSumCreditNotesUsed();
							$deposits = $invoice->getSumDepositsUsed();
							$alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
							$remaintopay = price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits, 'MT');

							// Multicurrency Price
							if (isModEnabled("multicurrency")) {
								$multicurrency_payment = $invoice->getSommePaiement(1);
								$multicurrency_creditnotes = $invoice->getSumCreditNotesUsed(1);
								$multicurrency_deposits = $invoice->getSumDepositsUsed(1);
								$multicurrency_alreadypayed = price2num($multicurrency_payment + $multicurrency_creditnotes + $multicurrency_deposits, 'MT');
								$multicurrency_remaintopay = price2num($invoice->multicurrency_total_ttc - $multicurrency_payment - $multicurrency_creditnotes - $multicurrency_deposits, 'MT');
							}

							print '<tr class="oddeven'.(($invoice->id == $facid) ? ' highlight' : '').'">';

							// Ref
							print '<td class="nowraponall">';
							print $invoicesupplierstatic->getNomUrl(1);
							print '</td>';

							// Ref supplier
							print '<td>'.$objp->ref_supplier.'</td>';

							// Date
							if ($objp->df > 0) {
								print '<td class="center nowraponall">';
								print dol_print_date($db->jdate($objp->df), 'day').'</td>';
							} else {
								print '<td class="center"><b>!!!</b></td>';
							}

							// Date Max Payment
							if ($objp->dlr > 0) {
								print '<td class="center nowraponall">';
								print dol_print_date($db->jdate($objp->dlr), 'day');

								if ($invoice->hasDelay()) {
									print img_warning($langs->trans('Late'));
								}

								print '</td>';
							} else {
								print '<td class="center"><b>--</b></td>';
							}

							// Multicurrency
							if (isModEnabled("multicurrency")) {
								// Currency
								print '<td class="center">'.$objp->multicurrency_code."</td>\n";

								print '<td class="right">';
								if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
									print price($objp->multicurrency_total_ttc);
								}
								print '</td>';

								print '<td class="right">';
								if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
									print price($sign * $multicurrency_payment);
									if ($multicurrency_creditnotes) {
										print '+'.price($multicurrency_creditnotes);
									}
									if ($multicurrency_deposits) {
										print '+'.price($multicurrency_deposits);
									}
								}
								print '</td>';

								print '<td class="right">';
								if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
									print price($sign * (float) $multicurrency_remaintopay);
								}
								print '</td>';

								print '<td class="right">';
								// Add remind multicurrency amount
								$namef = 'multicurrency_amount_'.$objp->facid;
								$nameRemain = 'multicurrency_remain_'.$objp->facid;
								if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) {
									if ($action != 'add_paiement') {
										if (!empty($conf->use_javascript_ajax)) {
											print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmount' data-rowname='".$namef."' data-value='".($sign * (float) $multicurrency_remaintopay)."'");
										}
										print '<input type=hidden class="multicurrency_remain" name="'.$nameRemain.'" value="'.$multicurrency_remaintopay.'">';
										print '<input type="text" size="8" class="multicurrency_amount" name="'.$namef.'" value="'.GETPOST($namef).'">';
									} else {
										print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.GETPOST($namef).'" disabled>';
										print '<input type="hidden" name="'.$namef.'" value="'.GETPOST($namef).'">';
									}
								}
								print "</td>";
							}

							print '<td class="right">'.price($sign * $objp->total_ttc).'</td>';

							print '<td class="right">'.price($sign * $objp->am);
							if ($creditnotes) {
								print '+'.price($creditnotes);
							}
							if ($deposits) {
								print '+'.price($deposits);
							}
							print '</td>';

							print '<td class="right">';
							print price($sign * (float) $remaintopay);
							if (isModEnabled('paymentbybanktransfer')) {
								$numdirectdebitopen = 0;
								$totaldirectdebit = 0;
								$sql = "SELECT COUNT(pfd.rowid) as nb, SUM(pfd.amount) as amount";
								$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_demande as pfd";
								$sql .= " WHERE fk_facture_fourn = ".((int) $objp->facid);
								$sql .= " AND pfd.traite = 0";
								$sql .= " AND pfd.ext_payment_id IS NULL";

								$result_sql = $db->query($sql);
								if ($result_sql) {
									$obj = $db->fetch_object($result_sql);
									$numdirectdebitopen = $obj->nb;
									$totaldirectdebit = $obj->amount;
								} else {
									dol_print_error($db);
								}
								if ($numdirectdebitopen) {
									$langs->load("withdrawals");
									print img_warning($langs->trans("WarningSomeCreditTransferAlreadyExists", $numdirectdebitopen, price(price2num($totaldirectdebit, 'MT'), 0, $langs, 1, -1, -1, $conf->currency)), '', 'classfortooltip');
								}
							}
							print '</td>';

							// Amount
							print '<td class="center nowraponall">';

							$namef = 'amount_'.$objp->facid;
							$nameRemain = 'remain_'.$objp->facid;

							if ($action != 'add_paiement') {
								if (!empty($conf->use_javascript_ajax)) {
									print img_picto("Auto fill", 'rightarrow', "class='AutoFillAmount' data-rowname='".$namef."' data-value='".($sign * (float) $remaintopay)."'");
								}
								print '<input type="hidden" class="remain" name="'.$nameRemain.'" value="'.$remaintopay.'">';
								print '<input type="text" size="8" class="amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">'; // class is required to be used by javascript callForResult();
							} else {
								print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.dol_escape_htmltag(GETPOST($namef)).'" disabled>';
								print '<input type="hidden" class="amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">'; // class is required to be used by javascript callForResult();
							}
							print "</td>";

							print "</tr>\n";
							$total += $objp->total_ht;
							$total_ttc += $objp->total_ttc;
							$totalrecu += $objp->am;
							$totalrecucreditnote += $creditnotes;
							$totalrecudeposits += $deposits;
							$i++;
						}
						if ($i > 1) {
							// Print total
							print '<tr class="liste_total">';
							print '<td colspan="4" class="left">'.$langs->trans('TotalTTC').':</td>';
							if (isModEnabled("multicurrency")) {
								print '<td>&nbsp;</td>';
								print '<td>&nbsp;</td>';
								print '<td>&nbsp;</td>';
								print '<td>&nbsp;</td>';
								print '<td class="right" id="multicurrency_result" style="font-weight: bold;"></td>';
							}
							print '<td class="right"><b>'.price($sign * $total_ttc).'</b></td>';
							print '<td class="right"><b>'.price($sign * $totalrecu);
							if ($totalrecucreditnote) {
								print '+'.price($totalrecucreditnote);
							}
							if ($totalrecudeposits) {
								print '+'.price($totalrecudeposits);
							}
							print	'</b></td>';
							print '<td class="right"><b>'.price($sign * (float) price2num($total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits, 'MT')).'</b></td>';
							print '<td class="center" id="result" style="font-weight: bold;"></td>'; // Autofilled
							print "</tr>\n";
						}
						print "</table>\n";

						print "</div>";
					}
					$db->free($resql);
				} else {
					dol_print_error($db);
				}
			}

			// Save + Cancel Buttons
			if ($action != 'add_paiement') {
				print '<br><div class="center">';
				print '<input type="checkbox" checked id="closepaidinvoices" name="closepaidinvoices"> <label for="closepaidinvoices">'.$langs->trans("ClosePaidInvoicesAutomatically").'</label><br>';
				print '<input type="submit" class="button" value="'.$langs->trans('ToMakePayment').'">';
				print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';
			}

			// Form to confirm payment
			if ($action == 'add_paiement') {
				$preselectedchoice = $addwarning ? 'no' : 'yes';

				print '<br>';
				if (!empty($totalpayment)) {
					$text = $langs->trans('ConfirmSupplierPayment', price($totalpayment), $langs->transnoentitiesnoconv("Currency".$conf->currency));
				}
				if (!empty($multicurrency_totalpayment)) {
					$text .= '<br>'.$langs->trans('ConfirmSupplierPayment', price($multicurrency_totalpayment), $langs->transnoentitiesnoconv("paymentInInvoiceCurrency"));
				}
				if (GETPOST('closepaidinvoices')) {
					$text .= '<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
					print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
				}
				print $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id.'&socid='.$object->socid.'&type='.$object->type, $langs->trans('PayedSuppliersPayments'), $text, 'confirm_paiement', $formquestion, $preselectedchoice);
			}

			print '</form>';
		}
	} else {
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
